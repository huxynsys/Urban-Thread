<?php
/*
USER LOGIN API - PLAIN TEXT VERSION
====================================
*/

// Start session at the VERY TOP
session_start();

// Log errors, never display them (prevents info disclosure in JSON APIs)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Add CORS headers
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Fix the path: from api/auth/ to api/config/db.php
require_once __DIR__ . '/../config/db.php';

// Get client IP for logging
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// If JSON decode failed, try form data
if (!$input) {
    $input = $_POST;
}

// Validate required fields
if (!isset($input['email']) || empty(trim($input['email']))) {
    sendJsonResponse(false, 'Email is required', [], 400);
}

if (!isset($input['password']) || empty(trim($input['password']))) {
    sendJsonResponse(false, 'Password is required', [], 400);
}

// Sanitize input
$email = strtolower(trim($input['email']));
$password = $input['password'];
$remember = isset($input['remember']) ? filter_var($input['remember'], FILTER_VALIDATE_BOOLEAN) : false;

// Validate email format
if (!isValidEmail($email)) {
    logLoginAttempt($email, $clientIP, $userAgent, false, 'Invalid email format');
    sendJsonResponse(false, 'Invalid email or password', [], 401);
}

// Check for too many failed attempts
$attemptCheck = isTooManyFailedAttempts($email, $clientIP);
if ($attemptCheck['blocked']) {
    sendJsonResponse(false, $attemptCheck['message'], [], 429);
}

// Get database connection
$conn = getDBConnection();

// Look the user up by email only; password verification happens in PHP so we
// can support both hashed (preferred) and legacy plaintext rows, and
// transparently upgrade legacy rows to secure hashes on successful login.
$query = "SELECT id, name, email, password, role, avatar, created_at, last_login, email_verified 
          FROM users WHERE email = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    logLoginAttempt($email, $clientIP, $userAgent, false, 'Invalid email or password');
    $stmt->close();
    sendJsonResponse(false, 'Invalid email or password', [], 401);
}

$user = $result->fetch_assoc();
$stmt->close();

// Verify password: bcrypt/argon2 hashes via password_verify(), legacy
// plaintext rows via a timing-safe comparison.
$storedPassword = (string) $user['password'];
$isHashed = preg_match('/^\$2[axy]\$|^\$argon2/', $storedPassword) === 1;
$passwordOk = $isHashed
    ? password_verify($password, $storedPassword)
    : hash_equals($storedPassword, $password);

if (!$passwordOk) {
    logLoginAttempt($email, $clientIP, $userAgent, false, 'Invalid email or password');
    sendJsonResponse(false, 'Invalid email or password', [], 401);
}

// Transparently upgrade legacy plaintext passwords to a secure hash so the
// database becomes hashed over time without breaking existing accounts.
if (!$isHashed) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $upgradeStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $upgradeStmt->bind_param('si', $newHash, $user['id']);
    $upgradeStmt->execute();
    $upgradeStmt->close();
}

// Sanitize stored name before it reaches the session/UI (stored-XSS defense)
$user['name'] = sanitize($user['name']);

// Update last login timestamp
$now = date('Y-m-d H:i:s');
$updateLoginQuery = "UPDATE users SET last_login = ? WHERE id = ?";
$updateStmt = $conn->prepare($updateLoginQuery);
$updateStmt->bind_param('si', $now, $user['id']);
$updateStmt->execute();
$updateStmt->close();

// Log successful login
logLoginAttempt($email, $clientIP, $userAgent, true, 'Login successful');

// Set session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['logged_in_at'] = time();
$_SESSION['ip_address'] = $clientIP;

// Rotate the session ID on authentication (prevents session fixation)
regenerateSessionId();

// Set remember me cookie if requested (30 days)
if ($remember) {
    $token = bin2hex(random_bytes(32));
    $expires = time() + (30 * 24 * 60 * 60);
    
    $tokenQuery = "INSERT INTO remember_tokens (user_id, token, expires_at, user_agent) VALUES (?, ?, ?, ?)";
    $tokenStmt = $conn->prepare($tokenQuery);
    $expiresDatetime = date('Y-m-d H:i:s', $expires);
    $tokenStmt->bind_param('isss', $user['id'], $token, $expiresDatetime, $userAgent);
    $tokenStmt->execute();
    $tokenStmt->close();
    
    setcookie(
        'remember_token', 
        $token, 
        [
            'expires' => $expires,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

// Remove password from user data before sending
unset($user['password']);

// Add last_login to response
$user['last_login'] = $now;

// Create personalized welcome message
$firstName = explode(' ', $user['name'])[0];
$welcomeMessage = "Login successful! Welcome back, $firstName!";

// Determine redirect based on user role
$redirect = ($user['role'] === 'admin') ? 'admin.php' : 'account.php';

// Return success response
sendJsonResponse(true, $welcomeMessage, [
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'avatar' => $user['avatar'],
        'last_login' => $user['last_login'],
        'member_since' => $user['created_at']
    ],
    'redirect' => $redirect,
    'session_id' => null // session ID no longer exposed to the client (hijack risk)
], 200);

/**
 * Log login attempt to database
 */
function logLoginAttempt($email, $ip, $userAgent, $success, $reason = '') {
    $conn = getDBConnection();
    $query = "INSERT INTO login_attempts (email, ip_address, user_agent, success, reason) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $successInt = $success ? 1 : 0;
    $stmt->bind_param('sssis', $email, $ip, $userAgent, $successInt, $reason);
    $stmt->execute();
    $stmt->close();
}

/**
 * Check for too many failed attempts
 */
function isTooManyFailedAttempts($email, $ip) {
    $conn = getDBConnection();
    $fifteenMinutesAgo = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    
    $query = "SELECT COUNT(*) as attempts FROM login_attempts 
              WHERE (email = ? OR ip_address = ?) 
              AND attempted_at > ? 
              AND success = 0";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sss', $email, $ip, $fifteenMinutesAgo);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $recentAttempts = $row['attempts'];
    
    if ($recentAttempts >= 5) {
        return [
            'blocked' => true,
            'message' => 'Too many failed attempts. Please try again after 15 minutes.'
        ];
    }
    
    return ['blocked' => false, 'message' => ''];
}
?>