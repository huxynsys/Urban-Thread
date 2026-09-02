<?php
/**
 * Remove from Cart API
 * Endpoint: /api/cart/remove.php
 * Method: POST
 * 
 * Body: {
 *     "product_id": 1
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

if (!$input || !isset($input['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$product_id = (int)$input['product_id'];

$conn = getDBConnection();
$user = isLoggedIn();
$session_id = session_id();

// Delete cart item
$delete_query = "DELETE FROM cart WHERE product_id = ? AND ";
if ($user) {
    $delete_query .= "user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $product_id, $user['id']);
} else {
    $delete_query .= "session_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('is', $product_id, $session_id);
}
$delete_stmt->execute();
$deleted = $delete_stmt->affected_rows;
$delete_stmt->close();

echo json_encode([
    'success' => true,
    'message' => $deleted ? 'Item removed from cart' : 'Item not found',
    'removed' => $deleted > 0
]);
?>