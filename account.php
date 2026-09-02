<?php
$current_page = basename($_SERVER['PHP_SELF']);
// BACKEND CLUE: This checks if user is logged in
// If not logged in, redirects to reg.php
require_once __DIR__ . '/api/config/db.php';
requireLogin();


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Account | Urban Thread</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Google Sign-In API -->
  <meta name="google-signin-client_id" content="479471245753-hi8bg3e9opac0om3lr33puhlrainhgql.apps.googleusercontent.com">
  <script src="https://apis.google.com/js/platform.js" async defer></script>
  <!-- Common Styles -->
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/navbar.css">
  <!-- Account Page Specific Styles -->
  <link rel="stylesheet" href="css/account.css">
</head>
<body>
  <!-- Navbar (Same structure as index.php but WITHOUT search bar) -->
  <?php include 'includes/navbar.php'; ?>

  <div class="container mt-5 mb-5">
    <h2 class="fw-bold mb-4 text-center">My Account</h2>
    <div class="row">
      <div class="col-md-4 mb-3">
        <div class="card p-4 shadow-sm">
          <h5 class="fw-bold mb-2">Profile</h5>
          <div id="profile-details">
            <!-- BACKEND CLUE: Profile data should come from database
                 Table: users
                 Fields: name, email, virtual_card balance, points
                 Query: SELECT * FROM users WHERE id = {session_user_id}
            -->
          </div>
          <button class="btn btn-outline-danger mt-3" id="logout-btn">Logout</button>
        </div>
        <div class="card p-4 shadow-sm mt-3">
          <h5 class="fw-bold mb-2">Rewards & Card</h5>
          <div>
            <a href="card.php" class="btn btn-dark btn-sm">Manage Virtual Card</a>
          </div>
        </div>
      </div>
      <div class="col-md-8 mb-3">
        <div class="card p-4 shadow-sm">
          <h5 class="fw-bold mb-2">Order History</h5>
          <div id="order-history">
            <!-- BACKEND CLUE: Order history should come from database
                 Table: orders
                 Fields: id, date, status, items, total
                 Query: SELECT * FROM orders WHERE user_id = {session_user_id} ORDER BY created_at DESC
            -->
          </div>
        </div>
        <div class="card p-4 shadow-sm mt-3">
          <h5 class="fw-bold mb-2">Wishlist</h5>
          <div id="account-wishlist" class="row">
            <!-- BACKEND CLUE: Wishlist items should come from database
                 Table: wishlist JOIN products
                 Query: SELECT p.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = {session_user_id}
            -->
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

  <!-- JS includes -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/common.js"></script>
  <script src="js/authNavbar.js"></script>
  <script src="js/account.js"></script>
</body>
</html>