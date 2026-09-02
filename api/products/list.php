<?php
/**
 * Product Listing API
 * Endpoint: /api/products/list.php
 * Method: GET
 * 
 * Parameters (optional):
 * - category: Filter by category
 * - search: Search by name/description
 * - min_price: Minimum price
 * - max_price: Maximum price
 * - sort: Sort by (price_low, price_high, popularity, newest)
 * - limit: Number of products per page (default: 12)
 * - page: Page number (default: 1)
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

// Get query parameters
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 100000;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Cap pagination values to prevent resource-exhaustion via huge LIMIT/OFFSET
$limit = max(1, min($limit, 100)); // 100 = shop page 'View All' ceiling
$page  = max(1, $page);
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = ["is_active = 1"];
$params = [];
$types = "";

if (!empty($category)) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ? OR category LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if ($min_price > 0) {
    $where_conditions[] = "price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price < 100000) {
    $where_conditions[] = "price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$where_clause = implode(" AND ", $where_conditions);

// Build ORDER BY clause
$order_by = "";
switch ($sort) {
    case 'price_low':
        $order_by = "ORDER BY price ASC";
        break;
    case 'price_high':
        $order_by = "ORDER BY price DESC";
        break;
    case 'popularity':
        $order_by = "ORDER BY popularity DESC";
        break;
    case 'newest':
        $order_by = "ORDER BY created_at DESC";
        break;
    default:
        $order_by = "ORDER BY id DESC";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM products WHERE {$where_clause}";
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_products = $count_result->fetch_assoc()['total'];
$count_stmt->close();

// Get products
$query = "SELECT id, name, category, price, stock, size, color, image, description, 
                 brand, popularity, is_featured, created_at 
          FROM products 
          WHERE {$where_clause} 
          {$order_by} 
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    // Format price
    $row['price'] = (float)$row['price'];
    $row['formatted_price'] = "Rs " . number_format($row['price']);
    
    // Add default image if missing
    if (empty($row['image']) || !file_exists($_SERVER['DOCUMENT_ROOT'] . '/urban-thread/' . $row['image'])) {
        $row['image'] = 'img/placeholder.jpg';
    }
    
    $products[] = $row;
}
$stmt->close();

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Return response
echo json_encode([
    'success' => true,
    'products' => $products,
    'pagination' => [
        'total' => $total_products,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total_products / $limit)
    ],
    'filters' => [
        'categories' => $categories,
        'price_range' => [
            'min' => 0,
            'max' => 100000
        ]
    ]
]);
?>