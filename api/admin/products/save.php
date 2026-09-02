<?php
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

require_once __DIR__ . '/../../config/db.php';

// Check admin access
$user = isLoggedIn();
if (!$user || $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Validate required fields
$required = ['name', 'category', 'price', 'stock'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit();
    }
}

$conn = getDBConnection();
$product_id = isset($input['id']) && $input['id'] ? (int)$input['id'] : 0;

// Sanitize text fields (strip tags/entities) and cast numerics.
// Values are bound via prepared statements below (previously this endpoint
// built SQL by string concatenation).
$name        = sanitize($input['name']);
$category    = sanitize($input['category']);
$price       = (float)$input['price'];
$stock       = (int)$input['stock'];
$size        = isset($input['size']) ? sanitize($input['size']) : '';
$color       = isset($input['color']) ? sanitize($input['color']) : '';
$image       = isset($input['image']) ? sanitize($input['image']) : '';
$description = isset($input['description']) ? sanitize($input['description']) : '';

if ($product_id > 0) {
    // Update existing product
    $query = "UPDATE products SET 
              name=?, category=?, price=?, stock=?, 
              size=?, color=?, image=?, description=? 
              WHERE id=?";
    $types  = 'ssdissssi'; // name, category, price, stock, size, color, image, description, id
    $params = [$name, $category, $price, $stock, $size, $color, $image, $description, $product_id];
} else {
    // Insert new product
    $query = "INSERT INTO products (name, category, price, stock, size, color, image, description, is_active, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
    $types  = 'ssdisss';
    $params = [$name, $category, $price, $stock, $size, $color, $image, $description];
}

$stmt = $conn->prepare($query);
if (!$stmt) {
    // Log the real error server-side; return a generic message to the client
    error_log('Product save prepare failed: ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Could not save product. Please try again.']);
    exit();
}
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => $product_id > 0 ? 'Product updated' : 'Product added']);
} else {
    error_log('Product save failed: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Could not save product. Please try again.']);
}
$stmt->close();
$conn->close();
?>