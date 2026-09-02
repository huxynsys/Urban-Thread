<?php
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

// Fix the path: from api/auth/ to api/config/db.php
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in via session
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ]
    ]);
} else {
    // No active session -> try to restore one from the "Remember me" cookie
    $restored = tryRememberTokenLogin();

    if ($restored) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $restored['id'],
                'name' => $restored['name'],
                'email' => $restored['email'],
                'role' => $restored['role']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'logged_in' => false
        ]);
    }
}

/**
 * Restore a session from the "Remember me" cookie (login.php sets this).
 * Security measures:
 *  - the cookie value must be exactly 64 hex chars
 *  - token must exist in remember_tokens and not be expired
 *  - the session ID is rotated on restore (anti-fixation)
 *  - the token is rotated after each use (a stolen token stops working
 *    the next time the legitimate user is auto-logged-in)
 * Returns the user array on success, or null.
 */
function tryRememberTokenLogin() {
    $cookieToken = $_COOKIE['remember_token'] ?? '';
    if (!is_string($cookieToken) || !preg_match('/^[a-f0-9]{64}$/', $cookieToken)) {
        return null;
    }

    $conn = getDBConnection();

    $stmt = $conn->prepare(
        "SELECT rt.id AS token_id, u.id, u.name, u.email, u.role
         FROM remember_tokens rt
         JOIN users u ON rt.user_id = u.id
         WHERE rt.token = ? AND rt.expires_at > NOW()
         LIMIT 1"
    );
    $stmt->bind_param('s', $cookieToken);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        // Invalid/expired token -> clear the cookie so it isn't retried
        setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
        return null;
    }

    // Rotate the used token: delete old, issue a fresh one
    $del = $conn->prepare("DELETE FROM remember_tokens WHERE id = ?");
    $del->bind_param('i', $user['token_id']);
    $del->execute();
    $del->close();

    $newToken = bin2hex(random_bytes(32));
    $expires = time() + (30 * 24 * 60 * 60);
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    $ins = $conn->prepare(
        "INSERT INTO remember_tokens (user_id, token, expires_at, user_agent) VALUES (?, ?, ?, ?)"
    );
    $expiresDatetime = date('Y-m-d H:i:s', $expires);
    $ins->bind_param('isss', $user['id'], $newToken, $expiresDatetime, $userAgent);
    $ins->execute();
    $ins->close();
    $conn->close();

    // Restore the session (same variables login.php sets)
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in_at'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

    // Rotate session ID on authentication (anti session fixation)
    regenerateSessionId();

    // Re-issue the cookie with the new token
    setcookie(
        'remember_token',
        $newToken,
        [
            'expires' => $expires,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );

    return [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
}
?>