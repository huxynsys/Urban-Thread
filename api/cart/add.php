<?php
/**
 * Add to Cart API
 * Endpoint: /api/cart/add.php
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
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

// Validate required fields
if (!isset($input['product_id']) || $input['product_id'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit();
}

$product_id = (int)$input['product_id'];
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
$size = isset($input['size']) ? trim($input['size']) : null;
$color = isset($input['color']) ? trim($input['color']) : null;

if ($quantity <= 0) $quantity = 1;

$conn = getDBConnection();
$user = isLoggedIn();
$session_id = session_id();

// Check if product exists and has stock
$product_query = "SELECT id, name, price, stock FROM products WHERE id = ? AND is_active = 1";
$product_stmt = $conn->prepare($product_query);
$product_stmt->bind_param('i', $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if ($product_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

$product = $product_result->fetch_assoc();
$product_stmt->close();

// Check stock
if ($product['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
    exit();
}

// Check if item already exists in cart
$check_query = "SELECT id, quantity FROM cart WHERE ";
$params = [];
$types = "";

if ($user) {
    $check_query .= "user_id = ? AND ";
    $params[] = $user['id'];
    $types .= "i";
} else {
    $check_query .= "session_id = ? AND ";
    $params[] = $session_id;
    $types .= "s";
}

$check_query .= "product_id = ?";
$params[] = $product_id;
$types .= "i";

if ($size) {
    $check_query .= " AND selected_size = ?";
    $params[] = $size;
    $types .= "s";
}

if ($color) {
    $check_query .= " AND selected_color = ?";
    $params[] = $color;
    $types .= "s";
}

$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param($types, ...$params);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Update existing cart item
    $existing = $check_result->fetch_assoc();
    $new_quantity = $existing['quantity'] + $quantity;
    
    $update_query = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('ii', $new_quantity, $existing['id']);
    $update_stmt->execute();
    $update_stmt->close();
    
    $message = "Cart updated successfully";
} else {
    // Insert new cart item
    $insert_query = "INSERT INTO cart (user_id, session_id, product_id, quantity, selected_size, selected_color) 
                     VALUES (?, ?, ?, ?, ?, ?)";
    
    $user_id = $user ? $user['id'] : null;
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('isiiis', $user_id, $session_id, $product_id, $quantity, $size, $color);
    $insert_stmt->execute();
    $insert_stmt->close();
    
    $message = "Product added to cart";
}

// Get updated cart count
$count_query = "SELECT SUM(quantity) as total FROM cart WHERE ";
if ($user) {
    $count_query .= "user_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param('i', $user['id']);
} else {
    $count_query .= "session_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param('s', $session_id);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$cart_count = $count_result->fetch_assoc()['total'] ?? 0;
$count_stmt->close();

echo json_encode([
    'success' => true,
    'message' => $message,
    'cart_count' => (int)$cart_count,
    'item' => [
        'product_id' => $product_id,
        'name' => $product['name'],
        'quantity' => $quantity
    ]
]);
?>