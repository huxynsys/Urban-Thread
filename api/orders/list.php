<?php
/**
 * Get Orders List API
 * Endpoint: /api/orders/list.php
 * Method: GET
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

$user = isLoggedIn();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit();
}

$conn = getDBConnection();

$query = "SELECT id, order_number, status, total, created_at 
          FROM orders 
          WHERE user_id = ? 
          ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $row['total'] = (float)$row['total'];
    $orders[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'orders' => $orders,
    'count' => count($orders)
]);
?>