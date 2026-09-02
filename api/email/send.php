<?php
/**
 * Simple Email Functions using PHP mail()
 * Works with MailHog for testing
 */

require_once __DIR__ . '/../config/db.php';

class EmailService {
    private $conn;
    private $site_url;
    private $admin_email;
    
    public function __construct() {
        $this->conn = getDBConnection();
        $this->site_url = SITE_URL;
        $this->admin_email = 'admin@urbanthread.com';
    }
    
    /**
     * Send email using PHP mail()
     */
    private function sendEmail($to, $name, $subject, $message, $email_type, $user_id = null) {
        // Headers for HTML email
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Urban Thread <noreply@urbanthread.com>\r\n";
        $headers .= "Reply-To: support@urbanthread.com\r\n";
        
        // Send email
        $sent = @mail($to, $subject, $message, $headers); // @: mail failures are logged to email_logs; a warning here would corrupt JSON responses on hosts with display_errors=On
        
        // Log to database
        $status = $sent ? 'sent' : 'failed';
        $error_message = $sent ? null : 'Mail function failed';
        
        $log_query = "INSERT INTO email_logs (user_id, email_type, recipient_email, recipient_name, subject, message, status, error_message) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $log_stmt = $this->conn->prepare($log_query);
        $log_stmt->bind_param('isssssss', $user_id, $email_type, $to, $name, $subject, $message, $status, $error_message);
        $log_stmt->execute();
        $log_stmt->close();
        
        return $sent;
    }
    
    /**
     * Send Welcome Email
     */
    public function sendWelcomeEmail($user_id, $name, $email) {
        $subject = "Welcome to Urban Thread!";
        $message = $this->getWelcomeEmailHTML($name);
        
        return $this->sendEmail($email, $name, $subject, $message, 'welcome_email', $user_id);
    }
    
    /**
     * Send Order Confirmation Email
     */
    public function sendOrderConfirmation($order_id, $user_id, $name, $email, $order_number, $total, $items) {
        $subject = "Order Confirmation #$order_number";
        $message = $this->getOrderEmailHTML($name, $order_number, $total, $items);
        
        return $this->sendEmail($email, $name, $subject, $message, 'order_confirmation', $user_id);
    }
    
    /**
     * Send Contact Auto-reply
     */
    public function sendContactAutoReply($name, $email, $message_text) {
        $subject = "Thank you for contacting Urban Thread";
        $message = $this->getContactAutoReplyHTML($name, $message_text);
        
        return $this->sendEmail($email, $name, $subject, $message, 'contact_auto_reply');
    }
    
    /**
     * Send Admin Notification
     */
    public function sendAdminNotification($name, $email, $subject, $message_text) {
        $mail_subject = "New Contact Message from $name";
        $mail_message = $this->getAdminNotificationHTML($name, $email, $subject, $message_text);
        
        return $this->sendEmail($this->admin_email, 'Admin', $mail_subject, $mail_message, 'admin_notification');
    }
    
    /**
     * Welcome Email HTML Template
     */
    private function getWelcomeEmailHTML($name) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #4361ee, #3a0ca3); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .button { display: inline-block; background: #4361ee; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to Urban Thread!</h1>
                </div>
                <div class='content'>
                    <h2>Hello $name,</h2>
                    <p>Thank you for joining Urban Thread! We're excited to have you as part of our fashion community.</p>
                    <p>As a member, you'll enjoy exclusive discounts and earn points on every purchase.</p>
                    <a href='{$this->site_url}/shop.php' class='button'>Start Shopping</a>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Urban Thread. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Order Confirmation Email HTML
     */
    private function getOrderEmailHTML($name, $order_number, $total, $items) {
        $items_html = '';
        foreach ($items as $item) {
            $items_html .= "<div style='border-bottom: 1px solid #eee; padding: 10px 0;'>
                            <strong>{$item['name']}</strong><br>
                            Quantity: {$item['quantity']} x Rs " . number_format($item['price']) . "
                           </div>";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; }
                .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .total { font-size: 18px; font-weight: bold; color: #28a745; margin-top: 15px; }
                .button { background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Order Confirmed!</h1>
                </div>
                <div class='content'>
                    <h2>Thank you for your order, $name!</h2>
                    <p>Your order #$order_number has been confirmed.</p>
                    <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h3>Order Summary</h3>
                        $items_html
                        <div class='total'>Total: Rs " . number_format($total) . "</div>
                    </div>
                    <a href='{$this->site_url}/account.php' class='button'>View Order Status</a>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Urban Thread. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Contact Auto-reply Email HTML
     */
    private function getContactAutoReplyHTML($name, $message) {
        // Escape user-controlled values (plain-text input embedded into HTML email).
        // html_entity_decode first prevents double-encoding values that were
        // already entity-encoded when stored.
        $name = htmlspecialchars(html_entity_decode($name, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; }
                .header { background: linear-gradient(135deg, #ff6b6b, #ffa8a8); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .message-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; font-style: italic; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>We've Received Your Message</h1>
                </div>
                <div class='content'>
                    <h2>Hello $name,</h2>
                    <p>Thank you for contacting Urban Thread. We'll respond within 24 hours.</p>
                    <div class='message-box'>
                        <strong>Your Message:</strong><br>
                        \"$message\"
                    </div>
                    <p>For immediate assistance, call +92-300-1234567</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Urban Thread</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Admin Notification Email HTML
     */
    private function getAdminNotificationHTML($name, $email, $subject, $message) {
        // Escape user-controlled values (plain-text input embedded into HTML email).
        // html_entity_decode first prevents double-encoding values that were
        // already entity-encoded when stored.
        $name = htmlspecialchars(html_entity_decode($name, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; }
                .header { background: linear-gradient(135deg, #dc3545, #ff6b6b); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .message-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>New Contact Message</h1>
                </div>
                <div class='content'>
                    <p><strong>From:</strong> $name ($email)</p>
                    <p><strong>Subject:</strong> $subject</p>
                    <div class='message-box'>
                        <strong>Message:</strong><br>
                        $message
                    </div>
                    <a href='{$this->site_url}/admin.php'>View in Admin Panel</a>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get email logs
     */
    public function getEmailLogs($limit = 50) {
        $query = "SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        $stmt->close();
        
        return $logs;
    }
}
?>