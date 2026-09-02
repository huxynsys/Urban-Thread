<?php
/**
 * Get Wishlist API
 * Endpoint: /api/wishlist/get.php
 * Method: GET
 * 
 * Returns: User's wishlist items with product details
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
$user = isLoggedIn();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Please login to view wishlist']);
    exit();
}

$conn = getDBConnection();

// Get wishlist items with product details
$query = "SELECT w.id as wishlist_id, w.created_at, 
                 p.id, p.name, p.price, p.image, p.category, p.stock
          FROM wishlist w
          JOIN products p ON w.product_id = p.id
          WHERE w.user_id = ?
          ORDER BY w.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$wishlist = [];
while ($row = $result->fetch_assoc()) {
    $row['price'] = (float)$row['price'];
    $row['formatted_price'] = "Rs " . number_format($row['price']);
    if (empty($row['image']) || !file_exists($_SERVER['DOCUMENT_ROOT'] . '/urban-thread/' . $row['image'])) {
        $row['image'] = 'img/placeholder.jpg';
    }
    $wishlist[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'wishlist' => $wishlist,
    'count' => count($wishlist)
]);
?>