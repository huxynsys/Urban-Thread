<?php
$current_page = basename($_SERVER['PHP_SELF']);
/*
PROJECT_MAP:
- /css/common.css                 -> global layout, navbar, footer
- /css/shop.css                   -> page-specific styles for shop page
- /js/common.js                   -> shared JS utilities & state
- /js/authNavbar.js               -> navbar authentication state
- /js/shop.js                     -> page-specific JS for shop page
- /includes/header.php            -> reusable header (future)
- /includes/footer.php            -> reusable footer (future)
- /config/db.php                  -> database connection (future)
- /api/                           -> backend endpoints (future)

DATA_FLOW:
- Current source: LocalStorage (client-side)
- Future source: MySQL via PHP APIs
- Products: Currently fetched from data/products.json or generated in JS
- Cart/Wishlist: Stored in localStorage, will move to database tables

ROLE:
- This file represents: Shop page (Product catalog)
- Displays categories, product grid with filters, search, sorting
- Contains product cards with add to cart and wishlist functionality
- AUTH_REQUIRED: No (public page)
- SESSION_DEPENDENT: Cart and wishlist data (needs session for logged-in users)
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Urban Thread | Shop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/navbar.css">
  <link rel="stylesheet" href="css/shop.css">
  
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <!-- Shop by Category -->
  <section class="container mt-4">
    <h2 class="fw-bold mb-3 text-center">Shop by Category</h2>
    <div class="row g-3 justify-content-center" id="category-row">
      <!-- BACKEND_CLUE: This section should be populated by PHP
           Table: categories
           Fields: id, name, image, folder
           Source will change from JavaScript to PHP include
      -->
    </div>
  </section>

  <!-- Main Shop Section -->
  <div class="container mt-4 mb-5">
    <div class="row mb-3">
      <!-- Filters (sticky left sidebar) -->
      <div class="col-md-3 mb-2">
        <div class="shop-sticky">
          <div class="card p-3 shadow-sm filter-card">
            <h5 class="fw-bold mb-2">Filters</h5>
            <div class="mb-2">
              <label class="form-label">Category</label>
              <select id="filter-category" class="form-select">
                <option value="">All Categories</option>
                <option value="Hoodies">Hoodies</option>
                <option value="Pants">Pants</option>
                <option value="Shirts">Shirts</option>
                <option value="Jackets">Jackets</option>
                <option value="Shoes">Shoes</option>
                <option value="Bags">Bags</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Size</label>
              <select id="filter-size" class="form-select">
                <option value="">All Sizes</option>
                <option value="S">Small (S)</option>
                <option value="M">Medium (M)</option>
                <option value="L">Large (L)</option>
                <option value="XL">Extra Large (XL)</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Color</label>
              <select id="filter-color" class="form-select">
                <option value="">All Colors</option>
                <option value="Black">Black</option>
                <option value="White">White</option>
                <option value="Blue">Blue</option>
                <option value="Red">Red</option>
                <option value="Green">Green</option>
                <option value="Gray">Gray</option>
                <option value="Navy">Navy</option>
                <option value="Brown">Brown</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Price Range</label>
              <input type="range" min="0" max="10000" step="100" id="filter-price" class="form-range" value="10000">
              <div class="d-flex justify-content-between">
                <span>Rs 0</span>
                <span id="price-max">Rs 10000</span>
              </div>
            </div>
            <button class="btn btn-outline-light w-100 mt-2" id="reset-filters">Reset Filters</button>
          </div>
        </div>
      </div>
      
      <!-- Product Grid Section -->
      <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h4 class="fw-bold mb-0">All Products</h4>
            <small class="text-muted" id="product-count">Loading products...</small>
          </div>
          <div class="d-flex gap-3">
            <!-- Search bar inside shop page -->
            <div class="position-relative">
              <input type="text" class="form-control" placeholder="Search products..." id="shop-search-bar" style="width:220px;">
              <button class="btn btn-link position-absolute end-0 top-50 translate-middle-y" style="border:none;background:none;">
                <i class="bi bi-search"></i>
              </button>
            </div>
            <select id="sort-select" class="form-select" style="width:180px;">
              <option value="default">Sort: Featured</option>
              <option value="lowhigh">Price: Low to High</option>
              <option value="highlow">Price: High to Low</option>
              <option value="popularity">Most Popular</option>
              <option value="newest">Newest Arrivals</option>
            </select>
          </div>
        </div>
        
        <div id="product-grid" class="row">
          <!-- BACKEND_CLUE: Products should be loaded from database
               Table: products
               Fields: id, name, price, image, category, size, color, description, popularity, date_added
               Query: SELECT * FROM products WHERE active = 1 LIMIT :offset, :limit
          -->
        </div>
        
        <!-- Loading indicator -->
        <div id="loading-indicator" class="text-center my-4" style="display: none;">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading more products...</p>
        </div>
        
        <!-- No products found -->
        <div id="no-products" class="text-center my-5" style="display:none;">
          <i class="bi bi-search display-1 text-muted"></i>
          <h4 class="mt-3">No products found</h4>
          <p class="text-muted">Try adjusting your filters or search term</p>
          <button class="btn btn-dark" id="clear-search-btn">Clear Search & Filters</button>
        </div>
        
        <!-- CHANGE: LOAD MORE BUTTON - Added for infinite loading functionality -->
        <div class="text-center mt-4" id="load-more-container" style="display: none;">
          <button class="btn btn-outline-dark btn-lg px-5" id="load-more-btn">
            <i class="bi bi-arrow-down-circle me-2"></i>Load More Products
          </button>
        </div>
        
        <!-- CHANGE: VIEW ALL BUTTON - Shows when all products are loaded -->
        <div class="text-center mt-4" id="view-all-container" style="display: none;">
          <button class="btn btn-success btn-lg px-5" id="view-all-btn" disabled>
            <i class="bi bi-check-circle me-2"></i>All Products Loaded
          </button>
        </div>
        
        <!-- CHANGE: BACK TO TOP BUTTON inside shop section (optional) -->
        <div class="text-center mt-3">
          <button class="btn btn-outline-secondary btn-sm" id="scroll-to-top-btn" style="display: none;">
            <i class="bi bi-arrow-up me-1"></i>Back to Top
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer (Common for all pages) -->
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

  <!-- Floating Back to Top Button -->
  <button id="btn-top" class="btn btn-dark btn-floating-top" title="Back to Top">
    <i class="bi bi-arrow-up"></i>
  </button>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/common.js"></script>
  <script src="js/authNavbar.js"></script>
  <script src="js/shop.js"></script>
</body>
</html>