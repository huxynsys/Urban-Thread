<?php
/**
 * Create Order API
 * Endpoint: /api/orders/create.php
 * Method: POST
 */

// Log errors, never display them (prevents info disclosure in JSON APIs)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

// Check if user is logged in
$user = isLoggedIn();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Please login to place order']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

// Validate required fields
$required = ['full_name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'payment_method'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit();
    }
}

$conn = getDBConnection();

// Get cart items
$cart_query = "SELECT c.*, p.name, p.price, p.stock 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = ?";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param('i', $user['id']);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$cart_items = [];
$subtotal = 0;

while ($row = $cart_result->fetch_assoc()) {
    $item_subtotal = $row['price'] * $row['quantity'];
    $subtotal += $item_subtotal;
    $cart_items[] = $row;
}
$cart_stmt->close();

if (empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
    exit();
}

// Calculate totals
$shipping = $subtotal > 3000 ? 0 : 250;
$tax = round($subtotal * 0.08, 2);
$total = $subtotal + $shipping + $tax;

// Check payment method
$payment_method = $input['payment_method'];

if ($payment_method === 'urban_card') {
    // Get user's virtual card
    $card_query = "SELECT balance FROM virtual_cards WHERE user_id = ?";
    $card_stmt = $conn->prepare($card_query);
    $card_stmt->bind_param('i', $user['id']);
    $card_stmt->execute();
    $card_result = $card_stmt->get_result();
    
    if ($card_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No virtual card found. Please contact support.']);
        exit();
    }
    
    $card = $card_result->fetch_assoc();
    $card_stmt->close();
    
    if ($card['balance'] < $total) {
        echo json_encode(['success' => false, 'message' => 'Insufficient balance in your Urban Card. Please add funds or choose another payment method.']);
        exit();
    }
}

// Generate order number
$order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

// Format shipping address
$shipping_address = json_encode([
    'full_name' => $input['full_name'],
    'email' => $input['email'],
    'phone' => $input['phone'],
    'address' => $input['address'],
    'city' => $input['city'],
    'state' => $input['state'],
    'zip' => $input['zip']
]);

// Everything below (order, items, stock, payment, cart clear) runs inside a
// single transaction: either ALL steps succeed or ALL are rolled back.
// This prevents orphan orders, double-charges and negative stock.
$conn->begin_transaction();

// Atomically re-verify stock (FOR UPDATE takes row locks so concurrent
// checkouts cannot oversell the same inventory)
foreach ($cart_items as $item) {
    $stockCheck = $conn->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
    $stockCheck->bind_param('i', $item['product_id']);
    $stockCheck->execute();
    $stockRow = $stockCheck->get_result()->fetch_assoc();
    $stockCheck->close();

    if ($stockRow === null || (int)$stockRow['stock'] < (int)$item['quantity']) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Insufficient stock for "' . $item['name'] . '". Please update your cart and try again.']);
        exit();
    }
}

// Insert order
$insert_order = "INSERT INTO orders (user_id, order_number, status, subtotal, shipping, tax, total, payment_method, shipping_address) 
                 VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?)";
$order_stmt = $conn->prepare($insert_order);
$order_stmt->bind_param('isddddss', $user['id'], $order_number, $subtotal, $shipping, $tax, $total, $payment_method, $shipping_address);

if (!$order_stmt->execute()) {
    $conn->rollback();
    error_log('Order create failed: ' . $order_stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to create order. Please try again.']);
    exit();
}

$order_id = $order_stmt->insert_id;
$order_stmt->close();

// Insert order items and update stock
foreach ($cart_items as $item) {
    $item_subtotal = $item['price'] * $item['quantity'];
    
    $insert_item = "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, size, color, subtotal) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $item_stmt = $conn->prepare($insert_item);
    $item_stmt->bind_param('iisdssdd', $order_id, $item['product_id'], $item['name'], $item['price'], $item['quantity'], $item['selected_size'], $item['selected_color'], $item_subtotal);
    
    if (!$item_stmt->execute()) {
        $conn->rollback();
        error_log('Order item insert failed: ' . $item_stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to create order. Please try again.']);
        exit();
    }
    $item_stmt->close();
    
    // Update product stock
    $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
    $stock_stmt = $conn->prepare($update_stock);
    $stock_stmt->bind_param('ii', $item['quantity'], $item['product_id']);
    if (!$stock_stmt->execute()) {
        $conn->rollback();
        error_log('Stock update failed: ' . $stock_stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to create order. Please try again.']);
        exit();
    }
    $stock_stmt->close();
}

// Process payment if using Urban Card
if ($payment_method === 'urban_card') {
    // Get card ID and lock the balance row (FOR UPDATE) so concurrent
    // checkouts cannot both spend the same balance (double-spend defense)
    $card_id_query = "SELECT id, balance FROM virtual_cards WHERE user_id = ? FOR UPDATE";
    $card_id_stmt = $conn->prepare($card_id_query);
    $card_id_stmt->bind_param('i', $user['id']);
    $card_id_stmt->execute();
    $card_id_result = $card_id_stmt->get_result();
    $card_row = $card_id_result->fetch_assoc();
    $card_id = $card_row['id'];
    $card_id_stmt->close();
    
    // Verify balance under lock
    if ($card_row === null || (float) $card_row['balance'] < $total) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Insufficient balance in your Urban Card. Please add funds or choose another payment method.']);
        exit();
    }
    
    // Deduct from virtual card balance
    $new_balance = $card_row['balance'] - $total;
    $update_balance = "UPDATE virtual_cards SET balance = ? WHERE user_id = ?";
    $balance_stmt = $conn->prepare($update_balance);
    $balance_stmt->bind_param('di', $new_balance, $user['id']);
    $balance_stmt->execute();
    $balance_stmt->close();
    
    // Add transaction record
    $transaction_query = "INSERT INTO card_transactions (card_id, amount, type, description, status) 
                          VALUES (?, ?, 'Purchase', ?, 'completed')";
    $trans_stmt = $conn->prepare($transaction_query);
    $trans_desc = "Order #$order_number";
    $trans_stmt->bind_param('ids', $card_id, $total, $trans_desc);
    $trans_stmt->execute();
    $trans_stmt->close();
}

// Clear cart after successful order
$clear_cart = "DELETE FROM cart WHERE user_id = ?";
$clear_stmt = $conn->prepare($clear_cart);
$clear_stmt->bind_param('i', $user['id']);
$clear_stmt->execute();
$clear_stmt->close();

// All steps succeeded -> make the order permanent
$conn->commit();

// ========== SEND ORDER CONFIRMATION EMAIL ==========
// Send Order Confirmation Email
/*
require_once __DIR__ . '/../email/send.php';
$emailService = new EmailService();
*/

// Send Order Confirmation Email - Only once
require_once __DIR__ . '/../email/send.php';
$emailService = new EmailService();

$order_items_for_email = [];
foreach ($cart_items as $item) {
    $order_items_for_email[] = [
        'name' => $item['name'],
        'quantity' => $item['quantity'],
        'price' => $item['price']
    ];
}

$emailService->sendOrderConfirmation(
    $order_id, 
    $user['id'], 
    $user['name'], 
    $user['email'], 
    $order_number, 
    $total, 
    $order_items_for_email
);
// ===================================================

echo json_encode([
    'success' => true,
    'message' => 'Order placed successfully!',
    'order_id' => $order_id,
    'order_number' => $order_number,
    'total' => $total
]);
?>