<?php
/**
 * Product Details API
 * Endpoint: /api/products/single.php?id={product_id}
 * Method: GET
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

$conn = getDBConnection();

// Get product details
$query = "SELECT id, name, category, price, stock, size, color, image, description, 
                 brand, material, care, popularity, is_featured, created_at 
          FROM products 
          WHERE id = ? AND is_active = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Format price
$product['price'] = (float)$product['price'];
$product['formatted_price'] = "Rs " . number_format($product['price']);

// Format created date
$product['formatted_date'] = date('F j, Y', strtotime($product['created_at']));

// Set default image if missing
if (empty($product['image']) || !file_exists($_SERVER['DOCUMENT_ROOT'] . '/urban-thread/' . $product['image'])) {
    $product['image'] = 'img/placeholder.jpg';
}

// Get product images
$images = [];
$images_query = "SELECT image_url, is_primary FROM product_images WHERE product_id = ? ORDER BY sort_order";
$images_stmt = $conn->prepare($images_query);
$images_stmt->bind_param('i', $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();

while ($row = $images_result->fetch_assoc()) {
    $images[] = $row;
}
$images_stmt->close();

if (empty($images)) {
    $images[] = ['image_url' => $product['image'], 'is_primary' => 1];
}
$product['images'] = $images;

// Get available sizes
$sizes = [];
$sizes_query = "SELECT size, stock_quantity FROM product_sizes WHERE product_id = ? AND stock_quantity > 0 ORDER BY FIELD(size, 'XS', 'S', 'M', 'L', 'XL', 'XXL')";
$sizes_stmt = $conn->prepare($sizes_query);
$sizes_stmt->bind_param('i', $product_id);
$sizes_stmt->execute();
$sizes_result = $sizes_stmt->get_result();

while ($row = $sizes_result->fetch_assoc()) {
    $sizes[] = $row['size'];
}
$sizes_stmt->close();

if (empty($sizes) && !empty($product['size'])) {
    $sizes = explode(',', $product['size']);
}
$product['available_sizes'] = $sizes;

// Get available colors
$colors = [];
$colors_query = "SELECT color, color_code FROM product_colors WHERE product_id = ? AND stock_quantity > 0";
$colors_stmt = $conn->prepare($colors_query);
$colors_stmt->bind_param('i', $product_id);
$colors_stmt->execute();
$colors_result = $colors_stmt->get_result();

while ($row = $colors_result->fetch_assoc()) {
    $colors[] = $row['color'];
}
$colors_stmt->close();

if (empty($colors) && !empty($product['color'])) {
    $colors = explode(',', $product['color']);
}
$product['available_colors'] = $colors;

// Get product specifications
$specs = [];
$specs_query = "SELECT spec_name, spec_value FROM product_specs WHERE product_id = ?";
$specs_stmt = $conn->prepare($specs_query);
$specs_stmt->bind_param('i', $product_id);
$specs_stmt->execute();
$specs_result = $specs_stmt->get_result();

while ($row = $specs_result->fetch_assoc()) {
    $specs[] = $row;
}
$specs_stmt->close();

// If no specs, create default ones
if (empty($specs)) {
    $specs = [
        ['spec_name' => 'Material', 'spec_value' => $product['material'] ?? 'Premium Quality'],
        ['spec_name' => 'Care Instructions', 'spec_value' => $product['care'] ?? 'Machine wash cold, tumble dry low'],
        ['spec_name' => 'Fit Type', 'spec_value' => 'Regular Fit'],
        ['spec_name' => 'Season', 'spec_value' => 'All Season']
    ];
}
$product['specifications'] = $specs;

// Get reviews (with error handling if table doesn't exist)
$reviews = [];
$avg_rating = 0;
$review_count = 0;

// Check if reviews table exists
$table_check = $conn->query("SHOW TABLES LIKE 'reviews'");
if ($table_check->num_rows > 0) {
    try {
        $reviews_query = "SELECT r.*, u.name as reviewer_name 
                          FROM reviews r 
                          LEFT JOIN users u ON r.user_id = u.id 
                          WHERE r.product_id = ? 
                          ORDER BY r.created_at DESC 
                          LIMIT 10";
        $reviews_stmt = $conn->prepare($reviews_query);
        $reviews_stmt->bind_param('i', $product_id);
        $reviews_stmt->execute();
        $reviews_result = $reviews_stmt->get_result();

        while ($row = $reviews_result->fetch_assoc()) {
            $reviews[] = [
                'id' => $row['id'],
                'user_name' => $row['reviewer_name'] ?? 'Anonymous',
                'rating' => (int)$row['rating'],
                'comment' => $row['comment'],
                'date' => date('M d, Y', strtotime($row['created_at']))
            ];
        }
        $reviews_stmt->close();
        
        // Calculate average rating
        if (!empty($reviews)) {
            $total_rating = array_sum(array_column($reviews, 'rating'));
            $avg_rating = round($total_rating / count($reviews), 1);
            $review_count = count($reviews);
        }
    } catch (Exception $e) {
        // Table exists but query failed
        error_log("Reviews query error: " . $e->getMessage());
    }
}

$product['reviews'] = [
    'average' => $avg_rating,
    'count' => $review_count,
    'list' => $reviews
];

// Get same category products
$same_category = [];
$same_category_query = "SELECT id, name, price, image, category 
                        FROM products 
                        WHERE category = ? AND id != ? AND is_active = 1 
                        ORDER BY RAND() 
                        LIMIT 4";
$same_stmt = $conn->prepare($same_category_query);
$same_stmt->bind_param('si', $product['category'], $product_id);
$same_stmt->execute();
$same_result = $same_stmt->get_result();

while ($row = $same_result->fetch_assoc()) {
    $row['price'] = (float)$row['price'];
    $row['formatted_price'] = "Rs " . number_format($row['price']);
    if (empty($row['image']) || !file_exists($_SERVER['DOCUMENT_ROOT'] . '/urban-thread/' . $row['image'])) {
        $row['image'] = 'img/placeholder.jpg';
    }
    $same_category[] = $row;
}
$same_stmt->close();

$product['same_category'] = $same_category;

// Get complementary products
$complementary_mapping = [
    'Hoodies' => ['Pants', 'Shoes', 'Bags'],
    'Pants' => ['Shirts', 'Shoes', 'Jackets'],
    'Shirts' => ['Pants', 'Shoes', 'Jackets'],
    'Shoes' => ['Pants', 'Shirts', 'Bags'],
    'Bags' => ['Jackets', 'Shirts', 'Shoes'],
    'Jackets' => ['Pants', 'Shirts', 'Shoes']
];

$complementary_categories = $complementary_mapping[$product['category']] ?? ['Pants', 'Shirts', 'Shoes'];

$complementary = [];
$complementary_query = "SELECT id, name, price, image, category 
                        FROM products 
                        WHERE category IN (?, ?, ?) AND id != ? AND is_active = 1 
                        ORDER BY RAND() 
                        LIMIT 4";
$comp_stmt = $conn->prepare($complementary_query);
$comp_stmt->bind_param('sssi', 
    $complementary_categories[0], 
    $complementary_categories[1], 
    $complementary_categories[2], 
    $product_id
);
$comp_stmt->execute();
$comp_result = $comp_stmt->get_result();

while ($row = $comp_result->fetch_assoc()) {
    $row['price'] = (float)$row['price'];
    $row['formatted_price'] = "Rs " . number_format($row['price']);
    if (empty($row['image']) || !file_exists($_SERVER['DOCUMENT_ROOT'] . '/urban-thread/' . $row['image'])) {
        $row['image'] = 'img/placeholder.jpg';
    }
    $complementary[] = $row;
}
$comp_stmt->close();

$product['complementary'] = $complementary;

// Return response
echo json_encode([
    'success' => true,
    'product' => $product
]);
?>