<?php
/**
 * Get Virtual Card API
 * Endpoint: /api/card/get.php
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

require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
$user = isLoggedIn();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Please login to view your card']);
    exit();
}

$conn = getDBConnection();

// Get virtual card details
$card_query = "SELECT id, card_number, balance, points, tier, points_multiplier, created_at 
               FROM virtual_cards 
               WHERE user_id = ?";
$card_stmt = $conn->prepare($card_query);
$card_stmt->bind_param('i', $user['id']);
$card_stmt->execute();
$card_result = $card_stmt->get_result();

if ($card_result->num_rows === 0) {
    // Create default card if not exists
    $card_number = '3221 ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999);
    $insert_query = "INSERT INTO virtual_cards (user_id, card_number, balance, points, tier) VALUES (?, ?, 50.00, 150, 'silver')";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('is', $user['id'], $card_number);
    $insert_stmt->execute();
    $card_id = $insert_stmt->insert_id;
    $insert_stmt->close();
    
    // Get the new card
    $card_stmt->execute();
    $card_result = $card_stmt->get_result();
}

$card = $card_result->fetch_assoc();
$card_stmt->close();

// Get recent transactions
$trans_query = "SELECT id, amount, type, description, status, created_at 
                FROM card_transactions 
                WHERE card_id = ? 
                ORDER BY created_at DESC 
                LIMIT 20";
$trans_stmt = $conn->prepare($trans_query);
$trans_stmt->bind_param('i', $card['id']);
$trans_stmt->execute();
$trans_result = $trans_stmt->get_result();

$transactions = [];
while ($row = $trans_result->fetch_assoc()) {
    $row['amount'] = (float)$row['amount'];
    $row['date'] = date('M d, Y', strtotime($row['created_at']));
    $row['time'] = date('h:i A', strtotime($row['created_at']));
    $transactions[] = $row;
}
$trans_stmt->close();

// Calculate statistics
$stats_query = "SELECT 
                    SUM(CASE WHEN type IN ('Top Up', 'Welcome Bonus') THEN amount ELSE 0 END) as total_added,
                    SUM(CASE WHEN type = 'Points Redemption' THEN amount ELSE 0 END) as total_redeemed,
                    SUM(CASE WHEN type = 'Purchase' THEN amount ELSE 0 END) as total_spent
                FROM card_transactions 
                WHERE card_id = ? AND status = 'completed'";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param('i', $card['id']);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

echo json_encode([
    'success' => true,
    'card' => [
        'id' => $card['id'],
        'card_number' => $card['card_number'],
        'balance' => (float)$card['balance'],
        'points' => (int)$card['points'],
        'tier' => $card['tier'],
        'points_multiplier' => (float)$card['points_multiplier'],
        'created_at' => $card['created_at']
    ],
    'transactions' => $transactions,
    'stats' => [
        'total_added' => (float)($stats['total_added'] ?? 0),
        'total_redeemed' => (float)($stats['total_redeemed'] ?? 0),
        'total_spent' => (float)($stats['total_spent'] ?? 0)
    ]
]);
?>