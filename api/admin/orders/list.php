<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/db.php';

// Check if user is admin
$user = isLoggedIn();
if (!$user || $user['role'] !== 'admin') {
    sendJsonResponse(false, 'Unauthorized access', [], 401);
}

$conn = getDBConnection();

$query = "SELECT o.*, u.name as user_name 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          ORDER BY o.created_at DESC 
          LIMIT 20";
$result = $conn->query($query);

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

sendJsonResponse(true, 'Orders fetched successfully', [
    'orders' => $orders,
    'total' => count($orders)
]);
?>