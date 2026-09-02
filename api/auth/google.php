<?php


// Log errors, never display them (prevents info disclosure in JSON APIs)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Allow requests from frontend (CORS) - same origin as the app
$allowedOrigin = 'http://localhost:8080';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
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

// Include database configuration
require_once __DIR__ . '/../../config/db.php';

// Get client IP for logging
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['credential'])) {
    logGoogleLogin('unknown', $clientIP, $userAgent, false, 'No credential provided');
    sendJsonResponse(false, 'Invalid request: No credential provided', [], 400);
}

$credential = $input['credential'];

// Google OAuth Configuration
$googleClientId = '479471245753-hi8bg3e9opac0om3lr33puhlrainhgql.apps.googleusercontent.com';

// Full token verification: cryptographic signature check against Google's
// published JWKS keys + issuer/audience/expiry/email_verified claim checks.
// Previously only the token shape was checked, which allowed forged tokens
// (full account takeover). Reject anything that fails any check.
$payload = verifyGoogleIdToken($credential, $googleClientId);

if ($payload === null) {
    logGoogleLogin('unknown', $clientIP, $userAgent, false, 'Invalid or unverifiable credential');
    sendJsonResponse(false, 'Google sign-in could not be verified. Please try again or use email/password login.', [], 401);
}

// Extract user data from the *verified* Google payload
$googleId = $payload['sub'] ?? null;
$email = $payload['email'];
$name = $payload['name'] ?? explode('@', $email)[0];
$avatar = $payload['picture'] ?? null;
$emailVerified = $payload['email_verified'] ?? false;
$locale = $payload['locale'] ?? 'en';
$givenName = $payload['given_name'] ?? $name;
$familyName = $payload['family_name'] ?? '';

// Defense-in-depth: never let unverified emails create/claim accounts
if (empty($emailVerified)) {
    logGoogleLogin($email, $clientIP, $userAgent, false, 'Email not verified with Google');
    sendJsonResponse(false, 'Your Google account email is not verified. Please verify it and try again.', [], 401);
}

// Sanitize the display name coming from Google before it reaches the DB/session
$name = sanitize($name);

// Get database connection
$conn = getDBConnection();
$isNewUser = false;

// Start transaction
$conn->begin_transaction();

try {
    // Check if user exists by email
    $query = "SELECT id, name, email, role, avatar, google_id, created_at FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User exists
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Update google_id and avatar if not set
        if (empty($user['google_id']) || $user['google_id'] !== $googleId) {
            $updateQuery = "UPDATE users SET google_id = ?, avatar = ?, last_login = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('ssi', $googleId, $avatar, $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            // Just update last login
            $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('i', $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        $isNewUser = false;
        $welcomeMessage = "Welcome back, " . explode(' ', $user['name'])[0] . "!";
    } else {
        // Create new user
        $isNewUser = true;
        
        // Use Google name or email username
        if (empty($name) || $name === $email) {
            $name = explode('@', $email)[0];
            // Format the name nicely
            $nameParts = explode('.', $name);
            if (count($nameParts) > 1) {
                $name = ucfirst($nameParts[0]) . ' ' . ucfirst($nameParts[1]);
            } else {
                $name = ucfirst($name);
            }
        }
        
        $insertQuery = "INSERT INTO users (name, email, google_id, avatar, role, email_verified, auth_provider, created_at, last_login) 
                        VALUES (?, ?, ?, ?, 'user', ?, 'google', NOW(), NOW())";
        $insertStmt = $conn->prepare($insertQuery);
        $emailVerifiedInt = $emailVerified ? 1 : 0;
        $insertStmt->bind_param('ssssi', $name, $email, $googleId, $avatar, $emailVerifiedInt);
        $insertStmt->execute();
        
        $userId = $insertStmt->insert_id;
        $insertStmt->close();
        
        // Create default virtual card for new user
        createDefaultVirtualCard($userId, $conn);
        
        // Get the new user data
        $userQuery = "SELECT id, name, email, role, avatar, created_at FROM users WHERE id = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();
        $userStmt->close();
        
        $welcomeMessage = "Welcome to Urban Thread, " . explode(' ', $name)[0] . "!";
    }
    
    // Commit transaction
    $conn->commit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    logGoogleLogin($email, $clientIP, $userAgent, false, 'Database error: ' . $e->getMessage());
    sendJsonResponse(false, 'An error occurred during Google login. Please try again.', [], 500);
}

// Log successful Google login
logGoogleLogin($email, $clientIP, $userAgent, true, $isNewUser ? 'New user created' : 'Existing user logged in');

// Set session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['auth_provider'] = 'google';
$_SESSION['logged_in_at'] = time();
$_SESSION['ip_address'] = $clientIP;

// Rotate the session ID on authentication (prevents session fixation)
regenerateSessionId();

// Determine redirect based on user role
$redirect = ($user['role'] === 'admin') ? 'admin.php' : 'account.php';

// Return success response with role-based redirect
sendJsonResponse(true, $welcomeMessage, [
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'avatar' => $user['avatar'] ?? $avatar,
        'auth_provider' => 'google'
    ],
    'redirect' => $redirect,
    'is_new_user' => $isNewUser,
    'session_id' => null // session ID no longer exposed to the client (hijack risk)
], 200);

/**
 * Log Google login attempt
 */
function logGoogleLogin($email, $ip, $userAgent, $success, $details = '') {
    $conn = getDBConnection();
    $query = "INSERT INTO google_login_logs (email, ip_address, user_agent, success, details) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $successInt = $success ? 1 : 0;
    $stmt->bind_param('sssis', $email, $ip, $userAgent, $successInt, $details);
    $stmt->execute();
    $stmt->close();
}

/**
 * Create default virtual card for new user
 */
function createDefaultVirtualCard($userId, $conn) {
    // Generate a random card number
    $prefix = '3221';
    $middle = rand(1000, 9999) . ' ' . rand(1000, 9999);
    $suffix = rand(1000, 9999);
    $cardNumber = $prefix . ' ' . $middle . ' ' . $suffix;
    
    // Default values
    $balance = 50.00;
    $points = 150;
    $tier = 'silver';
    $pointsMultiplier = 1.0;
    
    $query = "INSERT INTO virtual_cards (user_id, card_number, balance, points, tier, points_multiplier, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isdids', $userId, $cardNumber, $balance, $points, $tier, $pointsMultiplier);
    $stmt->execute();
    $cardId = $stmt->insert_id;
    $stmt->close();
    
    // Create welcome bonus transaction
    $transactionQuery = "INSERT INTO card_transactions (card_id, amount, type, description, status, created_at) 
                        VALUES (?, ?, 'Welcome Bonus', 'Initial account credit', 'completed', NOW())";
    $transactionStmt = $conn->prepare($transactionQuery);
    $transactionStmt->bind_param('id', $cardId, $balance);
    $transactionStmt->execute();
    $transactionStmt->close();
}

/**
 * Verify a Google ID token (RS256 JWT):
 *  1. Cryptographic signature against Google's published public keys (JWKS)
 *  2. issuer (iss) must be Google
 *  3. audience (aud) must be our client ID
 *  4. not expired (exp)
 * Returns the validated payload array, or null on ANY failure.
 */
function verifyGoogleIdToken($credential, $googleClientId) {
    $parts = explode('.', $credential);
    if (count($parts) !== 3) {
        return null;
    }

    $header  = json_decode(b64urlDecode($parts[0]), true);
    $payload = json_decode(b64urlDecode($parts[1]), true);
    if (!is_array($header) || !is_array($payload)) {
        return null;
    }

    // --- Claim checks (cheap, do these before network/crypto work) ---
    $iss = $payload['iss'] ?? '';
    if ($iss !== 'https://accounts.google.com' && $iss !== 'accounts.google.com') {
        return null;
    }
    if (($payload['aud'] ?? '') !== $googleClientId) {
        return null;
    }
    if (!isset($payload['exp']) || (int) $payload['exp'] < time()) {
        return null;
    }

    // --- Signature check (RS256 via Google's JWKS) ---
    if (!function_exists('openssl_verify')) {
        // Without OpenSSL we cannot verify signatures -> fail closed.
        return null;
    }

    $jwk = getGoogleJwk($header['kid'] ?? '');
    if (!$jwk) {
        return null;
    }

    $pem = jwkToPem($jwk);
    if (!$pem) {
        return null;
    }

    $signature = b64urlDecode($parts[2]);
    if ($signature === false || $signature === '') {
        return null;
    }

    $signingInput = $parts[0] . '.' . $parts[1];
    $ok = openssl_verify($signingInput, $signature, $pem, OPENSSL_ALGO_SHA256);

    return ($ok === 1) ? $payload : null;
}

/** Base64url decode (JWT alphabet, unpadded) */
function b64urlDecode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Fetch Google's JWKS and return the key matching $kid.
 * Cached on disk for 24h; refreshed once if an unknown kid is presented
 * (Google rotates keys).
 */
function getGoogleJwk($kid) {
    if (!$kid) {
        return null;
    }

    $cacheFile = sys_get_temp_dir() . '/google_jwks_cache.json';
    $certs = null;

    if (is_file($cacheFile) && (time() - (int) filemtime($cacheFile)) < 86400) {
        $certs = json_decode((string) file_get_contents($cacheFile), true);
    }

    if (!is_array($certs) || empty($certs['keys'])) {
        $certs = fetchGoogleJwks(true);
        if ($certs === null) {
            return null;
        }
    }

    $key = findJwkByKeyId($certs, $kid);
    if ($key) {
        return $key;
    }

    // Unknown kid -> Google may have rotated keys; force a refresh once
    $certs = fetchGoogleJwks(true);
    return $certs === null ? null : findJwkByKeyId($certs, $kid);
}

function findJwkByKeyId($certs, $kid) {
    if (!is_array($certs) || empty($certs['keys'])) {
        return null;
    }
    foreach ($certs['keys'] as $key) {
        if (($key['kid'] ?? '') === $kid && ($key['kty'] ?? '') === 'RSA') {
            return $key;
        }
    }
    return null;
}

/**
 * Download Google's JWKS. $forceRefresh bypasses the disk cache.
 * TLS certificate verification is ON (never disable it here).
 */
function fetchGoogleJwks($forceRefresh = false) {
    static $fetched = false;
    $cacheFile = sys_get_temp_dir() . '/google_jwks_cache.json';

    if ($forceRefresh || !$fetched) {
        $json = httpGetBody('https://www.googleapis.com/oauth2/v3/certs');
        if ($json) {
            $certs = json_decode($json, true);
            if (is_array($certs) && !empty($certs['keys'])) {
                @file_put_contents($cacheFile, $json);
                $fetched = true;
                return $certs;
            }
        }
        return null;
    }

    $certs = is_file($cacheFile)
        ? json_decode((string) file_get_contents($cacheFile), true)
        : null;
    return is_array($certs) ? $certs : null;
}

/** Simple HTTPS GET with certificate verification enabled */
function httpGetBody($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($code === 200 && is_string($body)) ? $body : null;
    }
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        return $body !== false ? $body : null;
    }
    return null;
}

/** Convert a Google RSA JWK (n, e) into a PEM public key for openssl_verify */
function jwkToPem(array $jwk) {
    if (empty($jwk['n']) || empty($jwk['e'])) {
        return null;
    }

    $n = b64urlDecode($jwk['n']);
    $e = b64urlDecode($jwk['e']);
    if ($n === false || $e === false || $n === '' || $e === '') {
        return null;
    }

    // Build DER: SubjectPublicKeyInfo = SEQUENCE { SEQUENCE(RSA n,e), BIT STRING }
    $rsa = derSequence(derInteger($n) . derInteger($e));
    $bitString = "\x03" . derLength(strlen($rsa) + 1) . "\x00" . $rsa;
    $spki = derSequence($bitString);

    $pem = "-----BEGIN PUBLIC KEY-----\n";
    foreach (str_split(base64_encode($spki), 64) as $line) {
        $pem .= $line . "\n";
    }
    return $pem . "-----END PUBLIC KEY-----\n";
}

/* ---------- Minimal DER encoding helpers ---------- */

function derInteger($bytes) {
    $bytes = ltrim($bytes, "\x00");
    if ($bytes === '') {
        $bytes = "\x00";
    }
    if (ord($bytes[0]) > 0x7F) {
        $bytes = "\x00" . $bytes; // keep the integer positive
    }
    return "\x02" . derLength(strlen($bytes)) . $bytes;
}

function derLength($len) {
    if ($len < 128) {
        return chr($len);
    }
    $out = '';
    while ($len > 0) {
        $out = chr($len % 256) . $out;
        $len = intdiv($len, 256);
    }
    return chr(0x80 | strlen($out)) . $out;
}

function derSequence($content) {
    return "\x30" . derLength(strlen($content)) . $content;
}
?>