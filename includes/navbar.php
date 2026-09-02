<?php
$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);
function nav_active($page, $current_page) {
  return $page === $current_page ? ' active' : '';
}
?>
<nav class="navbar navbar-expand-lg sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
      <img src="img/logo.png" alt="Urban Thread" class="logo me-2" id="main-logo">
      <span class="brand-text">Urban Thread</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="mobile-action-links d-flex d-md-none align-items-center gap-2">
      <a href="shop.php" class="nav-link nav-link-icon-group mobile-quick-link">
        <i class="bi bi-shop"></i>
        <span class="mobile-quick-text">Shop</span>
      </a>
      <a href="cart.php" class="nav-link nav-link-icon-group position-relative mobile-quick-link">
        <i class="bi bi-bag"></i>
        <span class="mobile-quick-text">Cart</span>
        <span class="badge bg-danger mobile-cart-count">0</span>
      </a>
    </div>
    <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
      <ul class="navbar-nav mb-2 mb-lg-0 mx-auto">
        <li class="nav-item"><a class="nav-link nav-link-icon-group<?= nav_active('index.php', $current_page) ?>" href="index.php">
          <i class="bi bi-house-door"></i>
          <span class="nav-link-text">Home</span>
        </a></li>
        <li class="nav-item shop-link"><a class="nav-link nav-link-icon-group<?= nav_active('shop.php', $current_page) ?>" href="shop.php">
          <i class="bi bi-shop"></i>
          <span class="nav-link-text">Shop</span>
        </a></li>
        <li class="nav-item"><a class="nav-link nav-link-icon-group<?= nav_active('about.php', $current_page) ?>" href="about.php">
          <i class="bi bi-info-circle"></i>
          <span class="nav-link-text">About</span>
        </a></li>
        <li class="nav-item"><a class="nav-link nav-link-icon-group<?= nav_active('contact.php', $current_page) ?>" href="contact.php">
          <i class="bi bi-envelope"></i>
          <span class="nav-link-text">Contact</span>
        </a></li>
        <li class="nav-item"><a class="nav-link nav-link-icon-group<?= nav_active('faq.php', $current_page) ?>" href="faq.php">
          <i class="bi bi-question-circle"></i>
          <span class="nav-link-text">FAQ</span>
        </a></li>
        <li class="nav-item"><a class="nav-link nav-link-icon-group<?= nav_active('returns.php', $current_page) ?>" href="returns.php">
          <i class="bi bi-arrow-return-left"></i>
          <span class="nav-link-text">Returns</span>
        </a></li>
        <li class="nav-item cart-link"><a class="nav-link nav-link-icon-group position-relative<?= nav_active('cart.php', $current_page) ?>" href="cart.php">
          <i class="bi bi-bag"></i>
          <span class="nav-link-text">Cart</span>
          <span class="badge bg-danger" id="cart-count">0</span>
        </a></li>
      </ul>
      <div class="d-flex gap-2 align-items-center" id="navbar-auth-area">
        <!-- BACKEND_CLUE: This area should be populated by PHP session check
             Table: users
             Logic: if(isset($_SESSION['user_id'])) { show profile dropdown } else { show login button }
        -->
      </div>
    </div>
  </div>
</nav>
