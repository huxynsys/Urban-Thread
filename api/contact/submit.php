<?php
/**
 * Contact Form Submit API
 * Endpoint: /api/contact/submit.php
 * Method: POST
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if (!$input) {
    sendJsonResponse(false, 'Invalid request data', [], 400);
}

// Validate required fields
$required = ['name', 'email', 'subject', 'message'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        sendJsonResponse(false, "Field '$field' is required", [], 400);
    }
}

$name = sanitize(trim($input['name']));
$email = strtolower(trim($input['email']));
$subject = sanitize(trim($input['subject']));
$message = trim($input['message']);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(false, 'Please enter a valid email address', [], 400);
}

// Validate message length
if (strlen($message) < 10) {
    sendJsonResponse(false, 'Message must be at least 10 characters', [], 400);
}

$conn = getDBConnection();

// Simple flood control: max 3 messages per email address per 10 minutes
$tenMinAgo = date('Y-m-d H:i:s', strtotime('-10 minutes'));
$floodStmt = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM contact_messages WHERE email = ? AND created_at > ?"
);
$floodStmt->bind_param('ss', $email, $tenMinAgo);
$floodStmt->execute();
$floodRow = $floodStmt->get_result()->fetch_assoc();
$floodStmt->close();

if ((int) $floodRow['cnt'] >= 3) {
    sendJsonResponse(false, 'You have sent several messages recently. Please wait a little before sending another.', [], 429);
}

// Insert message
$insert_query = "INSERT INTO contact_messages (name, email, subject, message, status) 
                 VALUES (?, ?, ?, ?, 'unread')";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param('ssss', $name, $email, $subject, $message);

if ($stmt->execute()) {
    $message_id = $stmt->insert_id;
    $stmt->close();
    
    // ========== SEND EMAILS ==========
    // Send Auto-reply to user and Notification to admin
    require_once __DIR__ . '/../email/send.php';
    $emailService = new EmailService();
    
    // Send auto-reply to the person who contacted
    $emailService->sendContactAutoReply($name, $email, $message);
    
    // Send notification to admin
    $emailService->sendAdminNotification($name, $email, $subject, $message);
    // ================================
    
    sendJsonResponse(true, 'Your message has been sent successfully! We will get back to you within 24 hours.', ['message_id' => $message_id], 200);
} else {
    sendJsonResponse(false, 'Failed to submit message. Please try again.', [], 500);
}
?>