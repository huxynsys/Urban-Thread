<?php
/*
USER REGISTRATION API - PLAIN TEXT VERSION
===========================================
Endpoint: /api/auth/register.php
Method: POST
Headers: Content-Type: application/json
Body: {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "confirm_password": "password123",
    "terms": true
}

Response Success (201):
{
    "success": true,
    "message": "Registration successful! Welcome to Urban Thread, John!",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "user",
        "avatar": null,
        "created_at": "2024-01-01 12:00:00"
    },
    "redirect": "account.php"
}

Response Error (400/409/500):
{
    "success": false,
    "message": "Error message here"
}
*/

// Start session at the VERY TOP
session_start();

// Log errors, never display them (prevents info disclosure in JSON APIs)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// CORS headers - Allow requests from frontend
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
$required_fields = ['name', 'email', 'password', 'confirm_password'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        sendJsonResponse(false, "Field '$field' is required", [], 400);
    }
}

// Sanitize and validate input (sanitize() strips tags & encodes entities —
// stored-XSS defense for the name, which is rendered in navbar/toasts)
$name = sanitize(trim($input['name']));
$email = strtolower(trim($input['email']));
$password = $input['password'];
$confirm_password = $input['confirm_password'];
$terms = isset($input['terms']) ? filter_var($input['terms'], FILTER_VALIDATE_BOOLEAN) : false;

// Validate name
if (strlen($name) < 2) {
    sendJsonResponse(false, 'Name must be at least 2 characters long', [], 400);
}

if (strlen($name) > 100) {
    sendJsonResponse(false, 'Name must not exceed 100 characters', [], 400);
}

// Validate email
if (!isValidEmail($email)) {
    sendJsonResponse(false, 'Please enter a valid email address', [], 400);
}

// Validate password
if (strlen($password) < 6) {
    sendJsonResponse(false, 'Password must be at least 6 characters long', [], 400);
}

// Password strength validation (optional but good)
$hasUpperCase = preg_match('/[A-Z]/', $password);
$hasLowerCase = preg_match('/[a-z]/', $password);
$hasNumber = preg_match('/[0-9]/', $password);

$strengthScore = 0;
$strengthMessages = [];

if ($hasUpperCase) $strengthScore++;
else $strengthMessages[] = 'uppercase letter';

if ($hasLowerCase) $strengthScore++;
else $strengthMessages[] = 'lowercase letter';

if ($hasNumber) $strengthScore++;
else $strengthMessages[] = 'number';

// Only enforce strength for stronger passwords (optional)
if ($strengthScore < 2 && strlen($password) >= 8) {
    $missing = implode(', ', $strengthMessages);
    sendJsonResponse(false, "Password must contain at least one $missing", [], 400);
}

// Check if passwords match
if ($password !== $confirm_password) {
    sendJsonResponse(false, 'Passwords do not match', [], 400);
}

// Check terms agreement
if (!$terms) {
    sendJsonResponse(false, 'You must agree to the Terms & Conditions and Privacy Policy', [], 400);
}

// Get database connection
$conn = getDBConnection();

// Registration rate limit: max 5 accounts per IP per hour (anti account-farming;
// the free welcome card/points makes scripted signups profitable for attackers)
$hourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
$rateStmt = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM registration_logs
     WHERE ip_address = ? AND created_at > ?"
);
$rateStmt->bind_param('ss', $clientIP, $hourAgo);
$rateStmt->execute();
$rateRow = $rateStmt->get_result()->fetch_assoc();
$rateStmt->close();

if ((int) $rateRow['cnt'] >= 5) {
    sendJsonResponse(false, 'Too many accounts created from this network. Please try again later.', [], 429);
}

// Check if email already exists
$checkQuery = "SELECT id, name, email FROM users WHERE email = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param('s', $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $checkStmt->close();
    
    // Log failed registration attempt
    logRegistration($email, $name, $clientIP, $userAgent, false, 'Email already exists');
    
    sendJsonResponse(false, 'Email already registered. Please use a different email or try logging in.', [], 409);
}
$checkStmt->close();

// Store password as a secure one-way hash (bcrypt via PASSWORD_DEFAULT)
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Generate avatar placeholder (using UI Avatars)
$avatar = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=4361ee&color=fff&size=128";

// Insert new user (always as 'user' role, never admin)
$insertQuery = "INSERT INTO users (name, email, password, role, avatar, email_verified, created_at, last_login) 
                VALUES (?, ?, ?, 'user', ?, 1, NOW(), NOW())";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param('ssss', $name, $email, $passwordHash, $avatar);

if (!$insertStmt->execute()) {
    $insertStmt->close();
    
    // Log failed registration attempt
    logRegistration($email, $name, $clientIP, $userAgent, false, 'Database error: ' . $conn->error);
    
    sendJsonResponse(false, 'Registration failed. Please try again.', [], 500);
}

$userId = $insertStmt->insert_id;
$insertStmt->close();

// Create default virtual card for new user
$cardNumber = '3221 ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999);
$cardQuery = "INSERT INTO virtual_cards (user_id, card_number, balance, points, tier) VALUES (?, ?, 50.00, 150, 'silver')";
$cardStmt = $conn->prepare($cardQuery);
$cardStmt->bind_param('is', $userId, $cardNumber);
$cardStmt->execute();
$cardStmt->close();

// Log successful registration
logRegistration($email, $name, $clientIP, $userAgent, true, 'Registration successful');

// Get the newly created user data
$userQuery = "SELECT id, name, email, role, avatar, created_at FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

// Set session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['logged_in_at'] = time();
$_SESSION['ip_address'] = $clientIP;

// Rotate the session ID on authentication (prevents session fixation)
regenerateSessionId();

// Create personalized welcome message
$firstName = explode(' ', $user['name'])[0];
$welcomeMessage = "Registration successful! Welcome to Urban Thread, $firstName!";


// After successful registration (around line where you have $userId)
// Add this code:

// ========== SEND WELCOME EMAIL ==========
// Send Welcome Email to new user
require_once __DIR__ . '/../email/send.php';
$emailService = new EmailService();
$emailService->sendWelcomeEmail($userId, $name, $email);
// ========================================

// Return success response
sendJsonResponse(true, $welcomeMessage, [
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'avatar' => $user['avatar'],
        'created_at' => $user['created_at']
    ],
    'redirect' => 'account.php',
    'session_id' => null // session ID no longer exposed to the client (hijack risk)
], 201);

/**
 * Log registration attempt
 */
function logRegistration($email, $name, $ip, $userAgent, $success, $details = '') {
    $conn = getDBConnection();
    $query = "INSERT INTO registration_logs (email, name, ip_address, user_agent, success, details) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $successInt = $success ? 1 : 0;
    $stmt->bind_param('ssssis', $email, $name, $ip, $userAgent, $successInt, $details);
    $stmt->execute();
    $stmt->close();
}
?>