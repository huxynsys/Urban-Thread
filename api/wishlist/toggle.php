<?php
/**
 * Toggle Wishlist API - Adds if not exists, removes if exists
 * Endpoint: /api/wishlist/toggle.php
 * Method: POST
 * 
 * Body: {
 *     "product_id": 1
 * }
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

// Check if user is logged in
$user = isLoggedIn();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Please login to manage wishlist']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit();
}

$product_id = (int)$input['product_id'];

$conn = getDBConnection();

// Check if product exists
$product_check = $conn->prepare("SELECT id, name FROM products WHERE id = ? AND is_active = 1");
$product_check->bind_param('i', $product_id);
$product_check->execute();
$product_result = $product_check->get_result();

if ($product_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}
$product = $product_result->fetch_assoc();
$product_check->close();

// Check if already in wishlist
$check_query = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('ii', $user['id'], $product_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Remove from wishlist
    $delete_query = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $user['id'], $product_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    echo json_encode([
        'success' => true,
        'action' => 'removed',
        'message' => 'Removed from wishlist'
    ]);
} else {
    // Add to wishlist
    $insert_query = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('ii', $user['id'], $product_id);
    $insert_stmt->execute();
    $insert_stmt->close();
    
    echo json_encode([
        'success' => true,
        'action' => 'added',
        'message' => 'Added to wishlist',
        'product' => [
            'id' => $product_id,
            'name' => $product['name']
        ]
    ]);
}
$check_stmt->close();
?>