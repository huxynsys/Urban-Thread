<?php
/**
 * Get Cart API
 * Endpoint: /api/cart/get.php
 * Method: GET
 * 
 * Returns: Cart items for logged-in user or session
 */

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

$conn = getDBConnection();
$user = isLoggedIn();
$session_id = session_id();

$cart_items = [];

if ($user) {
    // Get cart for logged-in user
    $query = "SELECT c.*, p.name, p.price, p.image, p.category 
              FROM cart c 
              JOIN products p ON c.product_id = p.id 
              WHERE c.user_id = ? 
              ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row['price'] = (float)$row['price'];
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $cart_items[] = $row;
    }
    $stmt->close();
} else {
    // Get cart for guest session
    $query = "SELECT c.*, p.name, p.price, p.image, p.category 
              FROM cart c 
              JOIN products p ON c.product_id = p.id 
              WHERE c.session_id = ? 
              ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row['price'] = (float)$row['price'];
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $cart_items[] = $row;
    }
    $stmt->close();
}

// Calculate totals
$subtotal = array_sum(array_column($cart_items, 'subtotal'));
$shipping = $subtotal > 3000 ? 0 : 250;
$tax = round($subtotal * 0.08, 2);
$total = $subtotal + $shipping + $tax;

echo json_encode([
    'success' => true,
    'cart' => $cart_items,
    'count' => count($cart_items),
    'totals' => [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'tax' => $tax,
        'total' => $total
    ],
    'user' => $user ? ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']] : null
]);
?>