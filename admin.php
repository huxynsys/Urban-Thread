<?php
$current_page = basename($_SERVER['PHP_SELF']);
// BACKEND CLUE: This checks if user is logged in and has admin role
// If not admin, redirects to index.php
require_once __DIR__ . '/api/config/db.php';
requireAdmin();


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | Urban Thread</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap and Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Common Styles -->
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/navbar.css">
  <!-- Admin-specific minimal styles -->
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <!-- Common Navbar -->
  <?php include 'includes/navbar.php'; ?>

  <!-- ADMIN DASHBOARD WITH LEFT SIDEBAR LAYOUT -->
  <div class="admin-dashboard-wrapper">
    <!-- LEFT SIDEBAR -->
    <aside class="admin-sidebar">
      <div class="sidebar-header">
        <h3><i class="bi bi-shield-lock"></i> Admin Panel</h3>
      </div>
      <nav class="sidebar-nav">
        <button class="sidebar-nav-btn active" data-tab="products-tab">
          <i class="bi bi-box-seam"></i>
          <span>Products</span>
        </button>

        <button class="sidebar-nav-btn" data-tab="users-tab">
          <i class="bi bi-people"></i>
          <span>Users</span>
        </button>
        
        <button class="sidebar-nav-btn" data-tab="orders-tab">
          <i class="bi bi-receipt"></i>
          <span>Orders</span>
        </button>
        
        <!-- Contact Messages - Direct button (no dropdown) -->
        <button class="sidebar-nav-btn" data-tab="messages-tab">
          <i class="bi bi-envelope"></i>
          <span>Contact Messages</span>
        </button>
      </nav>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="admin-main-content">
      
      <!-- STATISTICS CARDS (visible on all tabs) -->
      <div class="admin-stats-row" id="admin-stats-row">
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-people"></i></div>
          <div class="stat-info">
            <h3 id="admin-users">0</h3>
            <p>Total Users</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-receipt"></i></div>
          <div class="stat-info">
            <h3 id="admin-orders">0</h3>
            <p>Total Orders</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-box"></i></div>
          <div class="stat-info">
            <h3 id="admin-products">0</h3>
            <p>Total Products</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
          <div class="stat-info">
            <h3 id="admin-revenue">Rs 0</h3>
            <p>Total Revenue</p>
          </div>
        </div>
      </div>

      <!-- PRODUCTS TAB CONTENT (active by default) -->
      <div id="products-tab" class="admin-tab-content active">
        <div class="tab-header">
          <h2><i class="bi bi-box-seam"></i> Product Management</h2>
          <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#productModal">
            <i class="bi bi-plus-circle me-1"></i> Add Product
          </button>
        </div>
        <!-- Active filter indicator for products -->
        <div id="product-filter-indicator" class="filter-indicator mb-3" style="display: none;">
          <span class="badge bg-info d-inline-flex align-items-center gap-2 p-2">
            <i class="bi bi-funnel-fill"></i> Filtered by: <strong id="current-product-filter">All</strong>
            <button class="btn btn-sm btn-link text-white p-0 ms-2" onclick="resetProductFilter()">✕</button>
          </span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="admin-product-table">
              <!-- BACKEND CLUE: Products should be loaded from database
                   Table: products
                   Fields: id, name, category, price, stock, size, color, image, description
                   Query: SELECT * FROM products ORDER BY id DESC
              -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- USERS TAB CONTENT -->
      <div id="users-tab" class="admin-tab-content">
        <div class="tab-header">
          <h2><i class="bi bi-people"></i> Registered Users</h2>
        </div>
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
              </tr>
            </thead>
            <tbody id="admin-user-table">
              <!-- BACKEND CLUE: Users should be loaded from database-->
            </tbody>
          </table>
        </div>
      </div>

      <!-- ORDERS TAB CONTENT -->
      <div id="orders-tab" class="admin-tab-content">
        <div class="tab-header">
          <h2><i class="bi bi-receipt"></i> Recent Orders</h2>
        </div>
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Order ID</th>
                <th>User</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="admin-order-table">
              <!-- BACKEND CLUE: Orders should be loaded from database
                   Table: orders
                   Fields: id, user_id, date, status, total
                   Query: SELECT * FROM orders ORDER BY created_at DESC LIMIT 10
              -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- MESSAGES TAB CONTENT -->
      <div id="messages-tab" class="admin-tab-content">
        <div class="tab-header">
          <h2><i class="bi bi-envelope"></i> Contact Messages</h2>
          <button class="btn btn-sm btn-outline-primary" onclick="loadContactMessages()">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
          </button>
        </div>
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="admin-messages-table">
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Loading messages...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>

  <!-- Product Modal (for Add/Edit) - UNCHANGED -->
  <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content border-0">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title" id="productModalLabel"><i class="bi bi-box me-2"></i>Add / Edit Product</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="admin-product-form" method="POST" action="/api/admin/products/save.php">
            <!-- BACKEND CLUE: Form should submit to /api/admin/products/save.php
                 Table: products
                 Fields: id, name, category, price, stock, size, color, image, description
                 CRUD_OPERATION: CREATE / UPDATE
                 Validation:
                   - name: required
                   - category: required
                   - price: required, numeric, min 1
                   - stock: required, numeric, min 0
                   - size: required
                   - color: required
                   - image: required
                 On success: Return success message, refresh table
                 On failure: Return error message
            -->
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Product Name *</label>
                <input type="text" class="form-control" id="admin-prod-name" name="name" required>
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Category *</label>
                <select class="form-select" id="admin-prod-category" name="category" required>
                  <option value="">Select Category</option>
                  <option value="Hoodies">Hoodies</option>
                  <option value="Pants">Pants</option>
                  <option value="Shirts">Shirts</option>
                  <option value="Shoes">Shoes</option>
                  <option value="Bags">Bags</option>
                  <option value="Jackets">Jackets</option>
                </select>
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Price (Rs) *</label>
                <input type="number" class="form-control" id="admin-prod-price" name="price" required min="1" step="0.01">
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Stock *</label>
                <input type="number" class="form-control" id="admin-prod-stock" name="stock" required min="0">
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Size *</label>
                <select class="form-select" id="admin-prod-size" name="size" required>
                  <option value="S">Small (S)</option>
                  <option value="M">Medium (M)</option>
                  <option value="L">Large (L)</option>
                  <option value="XL">Extra Large (XL)</option>
                </select>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Color *</label>
                <select class="form-select" id="admin-prod-color" name="color" required>
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
              <div class="col-md-12 mb-3">
                <label class="form-label">Image URL *</label>
                <input type="text" class="form-control" id="admin-prod-image" name="image" required placeholder="img/product-name.jpg">
                <small class="text-muted">Path to product image</small>
              </div>
              <div class="col-md-12 mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" id="admin-prod-desc" name="description" rows="2" placeholder="Enter product description..."></textarea>
              </div>
            </div>
            <input type="hidden" id="admin-prod-id" name="id">
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-dark">
                <i class="bi bi-save me-2"></i>Save Product
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Common Footer -->
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
  <script src="js/admin.js"></script>
</body>
</html>