<?php
/**
 * Sync Cart API - Merges guest cart with user cart after login
 * Endpoint: /api/cart/sync.php
 * Method: POST
 * 
 * Body: {
 *     "local_cart": [{"id":1,"quantity":2,"selected_size":"L","selected_color":"Black"}, ...]
 * }
 */

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
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['local_cart'])) {
    echo json_encode(['success' => false, 'message' => 'No cart data provided']);
    exit();
}

$local_cart = $input['local_cart'];
$conn = getDBConnection();

foreach ($local_cart as $item) {
    $product_id = (int)($item['id'] ?? 0);
    $quantity = (int)($item['quantity'] ?? 1);
    $size = isset($item['selected_size']) ? trim($item['selected_size']) : null;
    $color = isset($item['selected_color']) ? trim($item['selected_color']) : null;

    // Skip malformed entries
    if ($product_id <= 0 || $quantity <= 0) {
        continue;
    }

    // Clamp quantity to available stock (product must exist and be active)
    $stockStmt = $conn->prepare("SELECT stock FROM products WHERE id = ? AND is_active = 1");
    $stockStmt->bind_param('i', $product_id);
    $stockStmt->execute();
    $stockRow = $stockStmt->get_result()->fetch_assoc();
    $stockStmt->close();

    if ($stockRow === null) {
        continue; // unknown/inactive product -> skip rather than fail the whole sync
    }
    $quantity = min($quantity, (int) $stockRow['stock']);
    if ($quantity <= 0) {
        continue;
    }
    
    // Check if item already exists in user's cart
    $check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $params = [$user['id'], $product_id];
    $types = "ii";
    
    if ($size) {
        $check_query .= " AND (selected_size = ? OR (selected_size IS NULL AND ? IS NULL))";
        $params[] = $size;
        $params[] = $size;
        $types .= "ss";
    }
    
    if ($color) {
        $check_query .= " AND (selected_color = ? OR (selected_color IS NULL AND ? IS NULL))";
        $params[] = $color;
        $params[] = $color;
        $types .= "ss";
    }
    
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param($types, ...$params);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing item
        $existing = $result->fetch_assoc();
        $new_quantity = $existing['quantity'] + $quantity;
        
        $update_query = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('ii', $new_quantity, $existing['id']);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new item
        $insert_query = "INSERT INTO cart (user_id, session_id, product_id, quantity, selected_size, selected_color) 
                         VALUES (?, NULL, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('iiiss', $user['id'], $product_id, $quantity, $size, $color);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// Delete guest cart with same session
$session_id = session_id();
$delete_guest = "DELETE FROM cart WHERE session_id = ? AND user_id IS NULL";
$delete_stmt = $conn->prepare($delete_guest);
$delete_stmt->bind_param('s', $session_id);
$delete_stmt->execute();
$delete_stmt->close();

// Get updated cart count
$count_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param('i', $user['id']);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$cart_count = $count_result->fetch_assoc()['total'] ?? 0;
$count_stmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Cart synced successfully',
    'cart_count' => (int)$cart_count
]);
?>