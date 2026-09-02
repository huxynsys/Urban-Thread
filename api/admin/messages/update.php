<?php
// update.php - FIXED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 0); // CHANGE TO 0 for production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/error.log'); // Log errors to file

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../config/db.php';

$user = isLoggedIn();
if (!$user || $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

if (!isset($input['message_id'])) {
    echo json_encode(['success' => false, 'message' => 'Message ID is required']);
    exit();
}

$message_id = (int)$input['message_id'];
$status = isset($input['status']) ? $input['status'] : 'read';
$admin_notes = isset($input['admin_notes']) ? trim($input['admin_notes']) : '';

try {
    $conn = getDBConnection();
    
    // Check if message exists
    $check_query = "SELECT id FROM contact_messages WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('i', $message_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $check_stmt->close();
        echo json_encode(['success' => false, 'message' => 'Message not found']);
        exit();
    }
    $check_stmt->close();
    
    // Update the message - FIXED: Check if updated_at column exists
    if (!empty($admin_notes)) {
        $query = "UPDATE contact_messages SET status = ?, admin_notes = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssi', $status, $admin_notes, $message_id);
    } else {
        $query = "UPDATE contact_messages SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('si', $status, $message_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Message updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error. Check logs.']);
}
?>