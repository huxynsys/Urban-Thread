<?php
/*
 * Admin Contact Messages List API
 * Endpoint: /api/admin/messages/list.php
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

require_once __DIR__ . '/../../config/db.php';

// Check if user is admin
$user = isLoggedIn();
if (!$user || $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$conn = getDBConnection();

// Get all messages (no filtering)
$query = "SELECT id, name, email, subject, message, status, admin_notes, created_at 
          FROM contact_messages 
          ORDER BY created_at DESC LIMIT 500 -- hard cap";
$result = $conn->query($query);

$messages = [];
while ($row = $result->fetch_assoc()) {
    $row['created_at_formatted'] = date('M d, Y h:i A', strtotime($row['created_at']));
    $messages[] = $row;
}

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages),
    'unread_count' => count(array_filter($messages, fn($m) => $m['status'] === 'unread'))
]);
?>