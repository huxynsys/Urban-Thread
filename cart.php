<?php
$current_page = basename($_SERVER['PHP_SELF']);
/*
PROJECT_MAP:
- /css/common.css                  -> global layout, navbar, footer
- /css/cart.css                    -> page-specific styles for cart page
- /js/common.js                    -> shared JS utilities & state
- /js/authNavbar.js                -> navbar authentication state
- /js/cart.js                      -> page-specific JS for cart page
- /includes/header.php             -> reusable header (future)
- /includes/footer.php             -> reusable footer (future)
- /config/db.php                   -> database connection (future)
- /api/                            -> backend endpoints (future)

DATA_FLOW:
- Current source: LocalStorage (client-side) for cart data
- Future source: MySQL via PHP APIs
- Cart items: Stored in localStorage, will move to database cart table
- Products: Currently fetched from data/products.json, will move to products table
- Promo codes: Stored in localStorage, will move to promo_codes table

ROLE:
- This file represents: Shopping Cart page
- Displays cart items with quantity controls and remove options
- Shows order summary with subtotal, shipping, tax, and discounts
- Allows applying promo codes
- AUTH_REQUIRED: No (guest users can use cart)
- SESSION_DEPENDENT: Cart data should be saved to user account when logged in
- DATABASE TABLES: cart, products, promo_codes
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shopping Cart | Urban Thread</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Common Styles -->
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/navbar.css">
  <!-- Cart Page Specific Styles -->
  <link rel="stylesheet" href="css/cart.css">
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <!-- Cart Page Header (Dark gradient matching footer) -->
  <section class="cart-header py-5">
    <div class="container text-center text-white">
      <h1 class="display-4 fw-bold mb-3">Your Shopping Cart</h1>
      <p class="lead">Review your items before checkout</p>
    </div>
  </section>

  <!-- Cart Content -->
  <div class="container my-5">
    <div class="row">
      <!-- Cart Items Section -->
      <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3 border-bottom">
            <h4 class="mb-0 fw-bold">
              <i class="bi bi-cart3 me-2"></i>
              Shopping Cart (<span id="item-count">0</span> items)
            </h4>
          </div>
          <div class="card-body p-0">
            <div id="cart-items-container">
              <!-- BACKEND_CLUE: Cart items should be loaded from database
                   Table: cart
                   Fields: id, user_id, product_id, quantity, selected_size, selected_color
                   Query: SELECT c.*, p.name, p.price, p.image, p.category 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = ? OR c.session_id = ?
              -->
            </div>
            
            <!-- Empty Cart State -->
            <div id="empty-cart" class="text-center py-5" style="display: none;">
              <div class="empty-cart-icon mb-4">
                <i class="bi bi-cart-x display-1 text-muted"></i>
              </div>
              <h3 class="fw-bold mb-3">Your cart is empty</h3>
              <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
              <a href="shop.php" class="btn btn-dark btn-lg px-4">
                <i class="bi bi-shop me-2"></i>Continue Shopping
              </a>
            </div>
          </div>
        </div>
        
        <!-- Continue Shopping -->
        <div class="mt-4">
          <a href="shop.php" class="btn btn-outline-dark">
            <i class="bi bi-arrow-left me-2"></i>Continue Shopping
          </a>
        </div>
      </div>
      
      <!-- Order Summary Section -->
      <div class="col-lg-4">
        <!-- CHANGE: Removed 'sticky-top' class, added custom class. inline style preserved -->
        <div class="card shadow-sm border-0 order-summary-sticky" style="top: 100px;">
          <div class="card-header bg-white py-3 border-bottom">
            <h4 class="mb-0 fw-bold">
              <i class="bi bi-receipt me-2"></i>
              Order Summary
            </h4>
          </div>
          <div class="card-body">
            <div id="order-summary">
              <!-- BACKEND_CLUE: Order summary should be calculated from cart data
                   Tables: cart, promo_codes
                   Calculations: subtotal, shipping, tax, discount
                   Promo codes: stored in promo_codes table with discount rates
              -->
            </div>
            
            <!-- ONLY ONE Checkout Button -->
            <div class="mt-4">
              <a href="checkout.php" class="btn btn-dark btn-lg w-100 py-3" id="checkout-btn">
                <i class="bi bi-lock me-2"></i>Proceed to Checkout
              </a>
            </div>
            
            <!-- Payment Methods - ONLY Urban Card and COD -->
            <div class="mt-4">
              <h6 class="fw-bold mb-2">Accepted Payment Methods</h6>
              <div class="d-flex gap-2">
                <span class="badge bg-light text-dark p-2"><i class="bi bi-wallet2 me-1"></i>Urban Card</span>
                <span class="badge bg-light text-dark p-2"><i class="bi bi-cash me-1"></i>Cash on Delivery</span>
              </div>
            </div>
            
            <!-- Security Info -->
            <div class="mt-4 pt-3 border-top">
              <div class="d-flex align-items-center text-muted">
                <i class="bi bi-shield-check me-2 text-success"></i>
                <small>Secure checkout • 100% safe & encrypted</small>
              </div>
            </div>
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

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/common.js"></script>
  <script src="js/authNavbar.js"></script>
  <script src="js/cart.js"></script>
</body>
</html>