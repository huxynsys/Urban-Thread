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

$query = "SELECT * FROM products ORDER BY id DESC LIMIT 500 -- hard cap";
$result = $conn->query($query);

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

sendJsonResponse(true, 'Products fetched successfully', [
    'products' => $products,
    'total' => count($products)
]);
?>