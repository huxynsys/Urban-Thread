<?php
/*
DATABASE CONNECTION CONFIGURATION
=================================
Host: localhost
Database: urban_thread_new
Username: root
Password: '' (empty for XAMPP)
*/

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'urban_thread_new');
define('DB_USER', 'root');
define('DB_PASS', ''); // Empty for XAMPP

// Disable display_errors for API responses to avoid invalid JSON output
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Site URL (change this based on your setup)
define('SITE_URL', 'http://localhost:8080/urban-thread');

/**
 * Shared static connection handle (by reference) so both
 * getDBConnection() and closeDBConnection() operate on the same value.
 */
function &dbSharedConnection() {
    static $conn = null;
    return $conn;
}

/**
 * Get database connection
 */
function getDBConnection() {
    $conn = &dbSharedConnection();
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please try again later.'
            ]));
        }
        
        // Set charset to UTF-8
        $conn->set_charset('utf8mb4');
    }
    
    return $conn;
}

/**
 * Close database connection
 */
function closeDBConnection() {
    $conn = &dbSharedConnection();
    if ($conn instanceof mysqli) {
        $conn->close();
    }
    $conn = null; // reset so a later getDBConnection() reopens cleanly
}

/**
 * Start session if not already started (with hardened cookie params)
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Harden the session cookie: HttpOnly + SameSite=Lax blocks cross-site
        // (CSRF) POSTs from carrying the session cookie, without breaking
        // normal navigation or same-site fetch() calls.
        if (PHP_VERSION_ID >= 70300) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secure,      // auto: only sent over HTTPS when HTTPS is on
                'httponly' => true,       // JS cannot read the session cookie
                'samesite' => 'Lax'
            ]);
        }
        session_start();
    }
}

/**
 * Rotate the session ID after a privilege change (login/register/OAuth).
 * Prevents session-fixation attacks.
 */
function regenerateSessionId() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * CSRF protection via Origin/Referer validation.
 *
 * Modern browsers always send an Origin (or Referer) header on POST requests.
 * If the request declares an origin that is NOT our own host, it is by
 * definition a cross-site request -> reject it (403).
 * Requests with no Origin/Referer (curl, server-to-server) are allowed,
 * because CSRF attacks can only be mounted from a browser.
 *
 * This runs automatically for all state-changing methods when db.php loads,
 * protecting every API endpoint without touching each file.
 */
function enforceCsrfProtection() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return; // only state-changing requests need protection
    }

    // Hosts we accept as "same-site"
    $allowedHosts = [];
    if (!empty($_SERVER['HTTP_HOST'])) {
        $allowedHosts[] = strtolower($_SERVER['HTTP_HOST']);
    }
    $siteHost = parse_url(SITE_URL, PHP_URL_HOST);
    if ($siteHost) {
        $allowedHosts[] = strtolower($siteHost);
        $sitePort = parse_url(SITE_URL, PHP_URL_PORT);
        if ($sitePort) {
            $allowedHosts[] = strtolower($siteHost) . ':' . $sitePort;
        }
    }
    $allowedHosts = array_unique(array_filter($allowedHosts));

    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $host = strtolower((string) parse_url($_SERVER[$key], PHP_URL_HOST));
        $port = parse_url($_SERVER[$key], PHP_URL_PORT);
        $candidate = $host . ($port ? ':' . $port : '');

        if ($candidate === '' || !in_array($candidate, $allowedHosts, true)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Request blocked: cross-site request detected.'
            ]);
            exit();
        }
        // First matching header found and validated -> request is same-site
        return;
    }
    // No Origin/Referer header -> non-browser client, allow
}

/**
 * Apply baseline security headers (called automatically on db.php load)
 */
function applySecurityHeaders() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 1; mode=block');
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ];
    }
    
    return false;
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/reg.php');
        exit();
    }
}

/**
 * Require admin privileges
 */
function requireAdmin() {
    $user = isLoggedIn();
    if (!$user || $user['role'] !== 'admin') {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}

/**
 * Send JSON response
 */
function sendJsonResponse($success, $message, $data = [], $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit();
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize input string
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Initialize session on file load
startSession();

// Apply baseline security headers and CSRF (origin) validation on every
// API request that includes this config file.
applySecurityHeaders();
enforceCsrfProtection();
?>