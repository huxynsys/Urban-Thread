<?php
/**
 * Add Funds to Virtual Card API
 * Endpoint: /api/card/topup.php
 * Method: POST
 * Body: { "amount": 50 }
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
    echo json_encode(['success' => false, 'message' => 'Please login to add funds']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Amount is required']);
    exit();
}

$amount = (float)$input['amount'];

// Validate amount
if ($amount < 10) {
    echo json_encode(['success' => false, 'message' => 'Minimum top-up amount is Rs 10']);
    exit();
}

if ($amount > 50000) {
    echo json_encode(['success' => false, 'message' => 'Maximum top-up amount is Rs 50,000']);
    exit();
}

$conn = getDBConnection();

// Top-up rate limit: max 5 top-ups per user per hour. The top-up flow credits
// balance without a real payment gateway, so unthrottled calls would let a
// user mint unlimited funds.
$hourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
$rateStmt = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM card_transactions
     WHERE card_id = ? AND type = 'Top Up' AND created_at > ?"
);
$rateStmt->bind_param('is', $card['id'], $hourAgo);
$rateStmt->execute();
$rateRow = $rateStmt->get_result()->fetch_assoc();
$rateStmt->close();

if ((int) $rateRow['cnt'] >= 5) {
    echo json_encode(['success' => false, 'message' => 'Too many top-ups in a short period. Please try again later.']);
    exit();
}

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

// Calculate points earned (10 points per Rs 1 spent)
$points_earned = floor($amount * 10);
$new_balance = $card['balance'] + $amount;
$new_points = $card['points'] + $points_earned;

// Update card balance and points
$update_query = "UPDATE virtual_cards SET balance = ?, points = ?, updated_at = NOW() WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param('dii', $new_balance, $new_points, $card['id']);
$update_stmt->execute();
$update_stmt->close();

// Record transaction
$transaction_query = "INSERT INTO card_transactions (card_id, amount, type, description, status) 
                      VALUES (?, ?, 'Top Up', ?, 'completed')";
$description = "Added Rs " . number_format($amount, 2) . " to card";
$trans_stmt = $conn->prepare($transaction_query);
$trans_stmt->bind_param('ids', $card['id'], $amount, $description);
$trans_stmt->execute();
$trans_stmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Funds added successfully!',
    'new_balance' => $new_balance,
    'points_earned' => $points_earned,
    'new_points' => $new_points
]);
?>