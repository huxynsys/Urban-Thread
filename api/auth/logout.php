<?php
/*
USER LOGOUT API
===============
*/

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Fix the path: from api/auth/ to api/config/db.php
require_once __DIR__ . '/../config/db.php';

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    $conn = getDBConnection();
    $token = $_COOKIE['remember_token'];
    $deleteQuery = "DELETE FROM remember_tokens WHERE token = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param('s', $token);
    $deleteStmt->execute();
    $deleteStmt->close();
    
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Return success response
sendJsonResponse(true, 'Logged out successfully', [], 200);
?>