<?php
/**
 * Update Cart API
 * Endpoint: /api/cart/update.php
 * Method: POST
 * 
 * Body: {
 *     "product_id": 1,
 *     "quantity": 2
 * }
 */

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

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['product_id']) || !isset($input['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$product_id = (int)$input['product_id'];
$quantity = (int)$input['quantity'];

if ($quantity <= 0) {
    // Remove item if quantity is 0 or negative
    include 'remove.php';
    exit();
}

$conn = getDBConnection();
$user = isLoggedIn();
$session_id = session_id();

// Validate the product exists and clamp quantity to available stock
// (prevents cart quantities exceeding inventory)
$stockStmt = $conn->prepare("SELECT stock FROM products WHERE id = ? AND is_active = 1");
$stockStmt->bind_param('i', $product_id);
$stockStmt->execute();
$stockRow = $stockStmt->get_result()->fetch_assoc();
$stockStmt->close();

if ($stockRow === null) {
    echo json_encode(['success' => false, 'message' => 'Product not found or unavailable']);
    exit();
}

$availableStock = (int) $stockRow['stock'];
if ($quantity > $availableStock) {
    if ($availableStock <= 0) {
        echo json_encode(['success' => false, 'message' => 'This product is out of stock']);
        exit();
    }
    $quantity = $availableStock; // clamp to what is available
}

// Find cart item
$find_query = "SELECT id FROM cart WHERE product_id = ? AND ";
if ($user) {
    $find_query .= "user_id = ?";
    $find_stmt = $conn->prepare($find_query);
    $find_stmt->bind_param('ii', $product_id, $user['id']);
} else {
    $find_query .= "session_id = ?";
    $find_stmt = $conn->prepare($find_query);
    $find_stmt->bind_param('is', $product_id, $session_id);
}
$find_stmt->execute();
$result = $find_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    exit();
}

$cart_item = $result->fetch_assoc();

// Update quantity
$update_query = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param('ii', $quantity, $cart_item['id']);
$update_stmt->execute();
$update_stmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Cart updated successfully',
    'quantity' => $quantity
]);
?>