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

require_once __DIR__ . '/../config/db.php';

// Check if user is admin
$user = isLoggedIn();
if (!$user || $user['role'] !== 'admin') {
    sendJsonResponse(false, 'Unauthorized access', [], 401);
}

$conn = getDBConnection();

// Get total users
$usersResult = $conn->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $usersResult->fetch_assoc()['total'];

// Get total orders
$ordersResult = $conn->query("SELECT COUNT(*) as total FROM orders");
$totalOrders = $ordersResult->fetch_assoc()['total'];

// Get total products
$productsResult = $conn->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
$totalProducts = $productsResult->fetch_assoc()['total'];

// Get total revenue
$revenueResult = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status = 'delivered'");
$revenue = $revenueResult->fetch_assoc()['revenue'] ?? 0;

sendJsonResponse(true, 'Stats fetched successfully', [
    'stats' => [
        'total_users' => (int)$totalUsers,
        'total_orders' => (int)$totalOrders,
        'total_products' => (int)$totalProducts,
        'total_revenue' => (float)$revenue
    ]
]);
?>