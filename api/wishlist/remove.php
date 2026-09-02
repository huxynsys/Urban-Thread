<?php
/**
 * Remove from Wishlist API
 * Endpoint: /api/wishlist/remove.php
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

// Remove from wishlist
$delete_query = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param('ii', $user['id'], $product_id);

if ($delete_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Removed from wishlist'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove from wishlist']);
}
$delete_stmt->close();
?>