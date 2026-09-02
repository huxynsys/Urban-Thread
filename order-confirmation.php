<?php
session_start();
require_once __DIR__ . '/api/config/db.php';

// Check if user is logged in
$user = isLoggedIn();
if (!$user) {
    header('Location: reg.php');
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header('Location: account.php');
    exit();
}

$conn = getDBConnection();

// Get order details
$order_query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param('ii', $order_id, $user['id']);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    header('Location: account.php');
    exit();
}

$order = $order_result->fetch_assoc();
$order_stmt->close();

// Get order items
$items_query = "SELECT * FROM order_items WHERE order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$order_items = [];
while ($row = $items_result->fetch_assoc()) {
    $order_items[] = $row;
}
$items_stmt->close();

$shipping_address = json_decode($order['shipping_address'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation | Urban Thread</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/navbar.css">
    <style>
        .confirmation-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 2rem;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
        }
        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .order-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .order-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm bg-white">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <img src="img/logo.png" alt="Urban Thread" class="logo me-2">
                <span class="brand-text">Urban Thread</span>
            </a>
        </div>
    </nav>

    <section class="confirmation-header">
        <div class="container text-center">
            <i class="bi bi-check-circle-fill display-1"></i>
            <h1 class="display-4 fw-bold mt-3">Order Confirmed!</h1>
            <p class="lead">Thank you for your purchase</p>
        </div>
    </section>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="order-card text-center">
                    <h3 class="fw-bold">Order #<?php echo $order['order_number']; ?></h3>
                    <p class="text-muted">Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                    <div class="alert alert-success">
                        <i class="bi bi-envelope me-2"></i>
                        A confirmation email has been sent to <?php echo htmlspecialchars($shipping_address['email'] ?? $user['email']); ?>
                    </div>
                </div>
                
                <div class="order-card">
                    <h4 class="fw-bold mb-3">Order Summary</h4>
                    <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div class="flex-grow-1">
                            <div class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div class="small text-muted">Qty: <?php echo $item['quantity']; ?></div>
                            <?php if ($item['size']): ?>
                            <div class="small text-muted">Size: <?php echo $item['size']; ?></div>
                            <?php endif; ?>
                            <?php if ($item['color']): ?>
                            <div class="small text-muted">Color: <?php echo $item['color']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="fw-bold">Rs <?php echo number_format($item['subtotal']); ?></div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="border-top pt-3 mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>Rs <?php echo number_format($order['subtotal']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span><?php echo $order['shipping'] > 0 ? 'Rs ' . number_format($order['shipping']) : 'FREE'; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (8%)</span>
                            <span>Rs <?php echo number_format($order['tax']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mt-3 pt-2 border-top">
                            <span class="fw-bold fs-5">Total</span>
                            <span class="fw-bold fs-5 text-success">Rs <?php echo number_format($order['total']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="order-card">
                    <h4 class="fw-bold mb-3">Shipping Address</h4>
                    <p class="mb-0"><?php echo htmlspecialchars($shipping_address['full_name']); ?></p>
                    <p class="mb-0"><?php echo htmlspecialchars($shipping_address['address']); ?></p>
                    <p class="mb-0"><?php echo htmlspecialchars($shipping_address['city']); ?>, <?php echo htmlspecialchars($shipping_address['state']); ?> <?php echo htmlspecialchars($shipping_address['zip']); ?></p>
                    <p class="mb-0">Phone: <?php echo htmlspecialchars($shipping_address['phone']); ?></p>
                </div>
                
                <div class="text-center mt-4">
                    <a href="account.php" class="btn btn-dark btn-lg me-2">
                        <i class="bi bi-person me-2"></i>View Orders
                    </a>
                    <a href="shop.php" class="btn btn-outline-dark btn-lg">
                        <i class="bi bi-shop me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-dark text-white mt-4">
        <div class="container py-3">
            <div class="row align-items-center">
                <div class="col-md-2 mb-2 text-start">
                    <img src="img/logo1.png" alt="Urban Thread" class="logo-footer">
                </div>
                <div class="col-md-3 mb-2">
                    <h5 class="fw-bold">Urban Thread</h5>
                    <p>Fashion for every story. Join our community for exclusive offers and trends.</p>
                </div>
                <div class="col-md-3 mb-2">
                    <h5 class="fw-bold">Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="shop.php" class="text-white">Shop</a></li>
                        <li><a href="about.php" class="text-white">About</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-2">
                    <h5 class="fw-bold">Connect</h5>
                    <a href="https://instagram.com" target="_blank" class="text-white me-2"><i class="bi bi-instagram"></i></a>
                    <a href="https://tiktok.com" target="_blank" class="text-white me-2"><i class="bi bi-tiktok"></i></a>
                    <a href="https://facebook.com" target="_blank" class="text-white me-2"><i class="bi bi-facebook"></i></a>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col text-center">
                    <small>&copy; 2025 Urban Thread. All Rights Reserved.</small>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>