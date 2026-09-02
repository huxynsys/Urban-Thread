<?php
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
require_once __DIR__ . '/api/config/db.php';

// Check if user is logged in
$user = isLoggedIn();
if (!$user) {
    header('Location: reg.php');
    exit();
}

// Get cart items
$cart_items = [];
$cart_total = 0;

$conn = getDBConnection();
$cart_query = "SELECT c.*, p.name, p.price, p.image, p.category, p.stock 
               FROM cart c 
               JOIN products p ON c.product_id = p.id 
               WHERE c.user_id = ?";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param('i', $user['id']);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

while ($row = $cart_result->fetch_assoc()) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $cart_total += $row['subtotal'];
    $cart_items[] = $row;
}
$cart_stmt->close();

// If cart is empty, redirect to shop
if (empty($cart_items)) {
    header('Location: shop.php');
    exit();
}

// Calculate totals
$shipping = $cart_total > 3000 ? 0 : 250;
$tax = round($cart_total * 0.08, 2);
$grand_total = $cart_total + $shipping + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout | Urban Thread</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/navbar.css">
    <style>
        .checkout-header {
            background: linear-gradient(135deg, #141E30 0%, #243B55 100%);
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 2rem;
        }
        .order-summary-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            position: sticky;
            top: 100px;
        }
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method:hover, .payment-method.active {
            border-color: #28a745;
            background: #f8f9fa;
        }
        .payment-method input {
            margin-right: 10px;
        }
        .checkout-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .checkout-item:last-child {
            border-bottom: none;
        }
        .checkout-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }
        .btn-checkout {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            padding: 1rem;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .btn-checkout:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        }
        .btn-checkout:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40,167,69,0.25);
        }
    </style>
</head>
<body>
    <!-- Navbar (EXACTLY from index.php - NO search bar) -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Checkout Header - Dark gradient matching footer -->
    <section class="checkout-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold">Checkout</h1>
            <p class="lead">Complete your purchase</p>
        </div>
    </section>

    <div class="container mb-5">
        <div class="row g-4">
            <!-- Checkout Form -->
            <div class="col-lg-7">
                <div class="form-card">
                    <h3 class="fw-bold mb-4">Shipping Information</h3>
                    
                    <form id="checkout-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Full Name</label>
                                <input type="text" class="form-control" id="full-name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" placeholder="+92 300 1234567" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea class="form-control" id="address" rows="3" placeholder="Enter your full address" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">City</label>
                                <input type="text" class="form-control" id="city" placeholder="City" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">State</label>
                                <input type="text" class="form-control" id="state" placeholder="State/Province" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Zip Code</label>
                                <input type="text" class="form-control" id="zip" placeholder="Zip Code" required>
                            </div>
                        </div>
                        
                        <h3 class="fw-bold mt-4 mb-3">Payment Method</h3>
                        
                        <div class="payment-method active" onclick="selectPayment('urban_card')">
                            <input type="radio" name="payment_method" id="payment_urban_card" value="urban_card" checked>
                            <label for="payment_urban_card" class="fw-semibold">Urban Card</label>
                            <p class="small text-muted mb-0 ms-4">Pay using your Urban Thread Virtual Card balance</p>
                        </div>
                        
                        <div class="payment-method" onclick="selectPayment('cod')">
                            <input type="radio" name="payment_method" id="payment_cod" value="cod">
                            <label for="payment_cod" class="fw-semibold">Cash on Delivery</label>
                            <p class="small text-muted mb-0 ms-4">Pay when you receive your order</p>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-checkout w-100" id="place-order-btn">
                                <i class="bi bi-check-circle me-2"></i>Place Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-5">
                <div class="order-summary-card p-4">
                    <h4 class="fw-bold mb-3">Order Summary</h4>
                    
                    <div id="order-items" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="checkout-item">
                            <img src="<?php echo $item['image'] ?? 'img/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='img/placeholder.jpg'">
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="small text-muted">Qty: <?php echo $item['quantity']; ?></div>
                                <?php if ($item['selected_size']): ?>
                                <div class="small text-muted">Size: <?php echo $item['selected_size']; ?></div>
                                <?php endif; ?>
                                <?php if ($item['selected_color']): ?>
                                <div class="small text-muted">Color: <?php echo $item['selected_color']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="fw-bold">Rs <?php echo number_format($item['subtotal']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="border-top pt-3 mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>Rs <?php echo number_format($cart_total); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span><?php echo $shipping > 0 ? 'Rs ' . number_format($shipping) : 'FREE'; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (8%)</span>
                            <span>Rs <?php echo number_format($tax); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mt-3 pt-2 border-top">
                            <span class="fw-bold fs-5">Total</span>
                            <span class="fw-bold fs-5 text-success">Rs <?php echo number_format($grand_total); ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-shield-check me-1"></i>
                            Your information is secure and encrypted
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer (EXACTLY from index.php) -->
    <footer class="bg-dark text-white mt-4">
        <div class="container py-3">
            <div class="row align-items-center">
                <div class="col-md-2 mb-2 text-start">
                    <img src="img/logo1.png" alt="Urban Thread" class="logo-footer" id="footer-logo">
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
                        <li><a href="faq.php" class="text-white">FAQ</a></li>
                        <li><a href="returns.php" class="text-white">Returns</a></li>
                        <li><a href="admin.php" class="text-white" id="footer-admin-link" style="display:none;">Admin</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-2">
                    <h5 class="fw-bold">Connect</h5>
                    <div class="social-icons">
                        <a href="https://instagram.com" target="_blank" class="social-icon"><i class="bi bi-instagram"></i></a>
                        <a href="https://tiktok.com" target="_blank" class="social-icon"><i class="bi bi-tiktok"></i></a>
                        <a href="https://facebook.com" target="_blank" class="social-icon"><i class="bi bi-facebook"></i></a>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col text-center">
                    <small>&copy; 2025 Urban Thread. All Rights Reserved.</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating Back to Top Button -->
    <button id="btn-top" class="btn btn-dark btn-floating-top" title="Back to Top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/common.js"></script>
    <script src="js/authNavbar.js"></script>
    <script>
    function selectPayment(method) {
        document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
        document.getElementById(`payment_${method}`).checked = true;
        event.currentTarget.classList.add('active');
    }
    
    document.getElementById('checkout-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('place-order-btn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        const orderData = {
            full_name: document.getElementById('full-name').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            city: document.getElementById('city').value,
            state: document.getElementById('state').value,
            zip: document.getElementById('zip').value,
            payment_method: document.querySelector('input[name="payment_method"]:checked').value
        };
        
        console.log('Sending order:', orderData);
        
        try {
            const response = await fetch('/urban-thread/api/orders/create.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(orderData)
            });
            
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('Failed to parse JSON:', responseText);
                alert('Server error. Please check console.');
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }
            
            if (data.success) {
                window.location.href = `order-confirmation.php?order_id=${data.order_id}`;
            } else {
                alert(data.message || 'Order failed. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Order error:', error);
            alert('Connection error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
    
    // Update cart count
    async function updateCartCount() {
        try {
            const response = await fetch('/urban-thread/api/cart/get.php', { credentials: 'include' });
            const data = await response.json();
            if (data.success) {
                const totalItems = data.cart.reduce((sum, item) => sum + item.quantity, 0);
                const cartCount = document.getElementById('cart-count');
                if (cartCount) cartCount.textContent = totalItems;
            }
        } catch (error) {
            console.error('Error updating cart count:', error);
        }
    }
    
    updateCartCount();
    </script>
</body>
</html>