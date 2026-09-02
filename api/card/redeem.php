<?php
/**
 * Redeem Points API
 * Endpoint: /api/card/redeem.php
 * Method: POST
 * Body: { "points": 100 }
 */

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

require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
$user = isLoggedIn();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Please login to redeem points']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['points'])) {
    echo json_encode(['success' => false, 'message' => 'Points amount is required']);
    exit();
}

$points_to_redeem = (int)$input['points'];

if ($points_to_redeem <= 0) {
    echo json_encode(['success' => false, 'message' => 'Points amount must be greater than 0']);
    exit();
}

$conn = getDBConnection();

// Get virtual card
$card_query = "SELECT id, balance, points FROM virtual_cards WHERE user_id = ?";
$card_stmt = $conn->prepare($card_query);
$card_stmt->bind_param('i', $user['id']);
$card_stmt->execute();
$card_result = $card_stmt->get_result();

if ($card_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Virtual card not found']);
    exit();
}

$card = $card_result->fetch_assoc();
$card_stmt->close();

// Validate points
if ($points_to_redeem < 100) {
    echo json_encode(['success' => false, 'message' => 'Minimum redemption is 100 points']);
    exit();
}

if ($points_to_redeem > $card['points']) {
    echo json_encode(['success' => false, 'message' => 'Insufficient points. You have ' . $card['points'] . ' points']);
    exit();
}

// Calculate value (100 points = Rs 5)
$redeem_value = ($points_to_redeem / 100) * 5;
$new_balance = $card['balance'] + $redeem_value;
$new_points = $card['points'] - $points_to_redeem;

// Update card
$update_query = "UPDATE virtual_cards SET balance = ?, points = ?, updated_at = NOW() WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param('dii', $new_balance, $new_points, $card['id']);
$update_stmt->execute();
$update_stmt->close();

// Record transaction
$transaction_query = "INSERT INTO card_transactions (card_id, amount, type, description, status) 
                      VALUES (?, ?, 'Points Redemption', ?, 'completed')";
$description = "Redeemed $points_to_redeem points for Rs " . number_format($redeem_value, 2);
$trans_stmt = $conn->prepare($transaction_query);
$trans_stmt->bind_param('ids', $card['id'], $redeem_value, $description);
$trans_stmt->execute();
$trans_stmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Points redeemed successfully!',
    'redeem_value' => $redeem_value,
    'new_balance' => $new_balance,
    'new_points' => $new_points,
    'points_used' => $points_to_redeem
]);
?>