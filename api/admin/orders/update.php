<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/db.php';

$user = isLoggedIn();
if (!$user || $user['role'] !== 'admin') {
    sendJsonResponse(false, 'Unauthorized access', [], 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = isset($input['order_id']) ? intval($input['order_id']) : 0;
$status = isset($input['status']) ? trim($input['status']) : '';

$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if ($orderId <= 0 || $status === '' || !in_array($status, $validStatuses, true)) {
    sendJsonResponse(false, 'Invalid order ID or status provided', [], 400);
}

$conn = getDBConnection();

$selectStmt = $conn->prepare('SELECT status FROM orders WHERE id = ?');
$selectStmt->bind_param('i', $orderId);
$selectStmt->execute();
$result = $selectStmt->get_result();
$order = $result->fetch_assoc();
$selectStmt->close();

if (!$order) {
    sendJsonResponse(false, 'Order not found', [], 404);
}

$currentStatus = $order['status'];
$allowedTransitions = [
    'pending' => ['processing', 'shipped', 'delivered', 'cancelled'],
    'processing' => ['shipped', 'delivered', 'cancelled'],
    'shipped' => ['delivered', 'cancelled'],
    'delivered' => ['cancelled'],
    'cancelled' => []
];

if ($currentStatus === $status) {
    sendJsonResponse(true, 'Order status is already set to ' . $status, ['order_id' => $orderId, 'status' => $status]);
}

if (!in_array($status, $allowedTransitions[$currentStatus], true)) {
    sendJsonResponse(false, 'Cannot update order status from ' . $currentStatus . ' to ' . $status, [], 400);
}

$updateStmt = $conn->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
$updateStmt->bind_param('si', $status, $orderId);
$success = $updateStmt->execute();
$updateStmt->close();

if (!$success) {
    sendJsonResponse(false, 'Failed to update order status. Please try again.', [], 500);
}

sendJsonResponse(true, 'Order status updated successfully', ['order_id' => $orderId, 'status' => $status]);
