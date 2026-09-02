<?php
$current_page = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product Details | Urban Thread</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/navbar.css">
  <link rel="stylesheet" href="css/product.css">
</head>
<body>
  <!-- Navbar (Same as index.php) -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Product Details Section -->
  <div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
        <li class="breadcrumb-item active" id="product-breadcrumb">Product Details</li>
      </ol>
    </nav>

    <div class="row g-4">
      <!-- Product Gallery & Same Category Suggestions -->
      <div class="col-lg-6">
        <div class="card product-gallery-card border-0 shadow-sm h-100">
          <div class="card-body p-4">
            <!-- Main Product Image -->
            <div class="main-image-container text-center mb-3">
              <img id="main-product-image" src="" alt="Product Image" class="img-fluid rounded-3 product-main-image">
            </div>
            
            <!-- Thumbnails -->
            <div class="thumbnail-row d-flex justify-content-center gap-2 flex-wrap mb-4">
              <!-- Thumbnails will be added by JavaScript -->
              <!-- BACKEND_CLUE: Product images should come from database table: `product_images`
                   Fields: id, product_id, image_url, is_primary, sort_order
              -->
            </div>
            
            <!-- Same Category Suggestions -->
            <div class="same-category-suggestions mt-4 pt-4 border-top">
              <h5 class="fw-bold mb-3 text-dark">More from this collection</h5>
              <div class="row g-2" id="same-category-products">
                <!-- BACKEND_CLUE: Same category products should come from database
                     Table: products
                     Query: SELECT * FROM products WHERE category = current_category AND id != current_id LIMIT 4
                -->
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Product Info -->
      <div class="col-lg-6">
        <div class="card product-info-card border-0 shadow-sm h-100">
          <div class="card-body p-4 d-flex flex-column">
            <!-- Product Header -->
            <div class="product-header mb-3">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h1 class="product-title fw-bold mb-2" id="product-name">Loading...</h1>
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-primary" id="product-category"></span>
                    <span class="badge bg-success" id="product-brand"></span>
                    <span class="badge bg-warning text-dark">In Stock</span>
                  </div>
                </div>
                <button class="btn btn-outline-danger wishlist-btn" id="wishlist-btn">
                  <i class="bi bi-heart"></i>
                </button>
              </div>
            </div>
            
            <!-- Price & SKU -->
            <div class="product-price-section mb-4 py-3 border-top border-bottom">
              <div class="d-flex align-items-center justify-content-between">
                <h2 class="text-primary fw-bold mb-0" id="product-price">Rs 0</h2>
                <span class="text-muted" id="product-sku">SKU: <span id="sku-value">-</span></span>
              </div>
            </div>
            
            <!-- Description -->
            <div class="product-description mb-4">
              <h5 class="fw-bold mb-3 text-dark">Description</h5>
              <p id="product-description" class="text-secondary">Loading description...</p>
            </div>
            
            <!-- Variants -->
            <div class="product-variants mb-4">
              <h5 class="fw-bold mb-3 text-dark">Select Options</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Size</label>
                  <select class="form-select form-select-lg" id="size-select">
                    <option value="">Select Size</option>
                    <option value="S">Small (S)</option>
                    <option value="M">Medium (M)</option>
                    <option value="L">Large (L)</option>
                    <option value="XL">Extra Large (XL)</option>
                    <!-- BACKEND_CLUE: Available sizes should come from database table: `product_sizes`
                         Fields: id, product_id, size, stock_quantity
                    -->
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Color</label>
                  <select class="form-select form-select-lg" id="color-select">
                    <option value="">Select Color</option>
                    <option value="Black">Black</option>
                    <option value="White">White</option>
                    <option value="Blue">Blue</option>
                    <option value="Red">Red</option>
                    <option value="Green">Green</option>
                    <option value="Gray">Gray</option>
                    <option value="Navy">Navy</option>
                    <option value="Brown">Brown</option>
                    <!-- BACKEND_CLUE: Available colors should come from database table: `product_colors`
                         Fields: id, product_id, color, stock_quantity, color_code
                    -->
                  </select>
                </div>
              </div>
            </div>
            
            <!-- Quantity & Actions -->
            <div class="product-actions mt-auto">
              <div class="row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label fw-semibold">Quantity</label>
                  <div class="input-group input-group-lg">
                    <button class="btn btn-outline-secondary" type="button" id="decrease-qty">-</button>
                    <input type="number" class="form-control text-center" value="1" min="1" max="99" id="quantity-input">
                    <button class="btn btn-outline-secondary" type="button" id="increase-qty">+</button>
                  </div>
                </div>
                <div class="col-md-8">
                  <div class="d-grid gap-2">
                    <button class="btn btn-dark btn-lg py-3" id="add-to-cart-btn">
                      <i class="bi bi-cart-plus me-2"></i>Add to Cart
                    </button>
                    <button class="btn btn-outline-dark btn-lg py-3" id="buy-now-btn">
                      <i class="bi bi-lightning me-2"></i>Buy Now
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Product Features -->
            <div class="product-features mt-4 pt-4 border-top">
              <div class="row g-3">
                <div class="col-6">
                  <div class="feature-card text-center p-3 bg-light rounded-3">
                    <i class="bi bi-truck text-primary fs-4 mb-2"></i>
                    <h6 class="fw-bold mb-1">Free Shipping</h6>
                    <p class="small mb-0">Over Rs 5000</p>
                  </div>
                </div>
                <div class="col-6">
                  <div class="feature-card text-center p-3 bg-light rounded-3">
                    <i class="bi bi-arrow-clockwise text-primary fs-4 mb-2"></i>
                    <h6 class="fw-bold mb-1">Easy Returns</h6>
                    <p class="small mb-0">30 Days Return</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Product Tabs -->
    <div class="row mt-5">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-0">
            <ul class="nav nav-tabs nav-fill border-0" id="productTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active py-3" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">
                  <i class="bi bi-info-circle me-2"></i>Product Details
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link py-3" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button">
                  <i class="bi bi-star me-2"></i>Reviews <span class="badge bg-primary ms-1">4.5</span>
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link py-3" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button">
                  <i class="bi bi-truck me-2"></i>Shipping Info
                </button>
              </li>
            </ul>
            <div class="tab-content p-4" id="productTabsContent">
              <div class="tab-pane fade show active" id="details">
                <div id="additional-details" class="row g-4">
                  <!-- BACKEND_CLUE: Product details should come from database table: `product_specs`
                       Fields: id, product_id, spec_name, spec_value
                  -->
                </div>
              </div>
              <div class="tab-pane fade" id="reviews">
                <div id="reviews-content">
                  <!-- BACKEND_CLUE: Reviews should come from database table: `reviews`
                       Fields: id, product_id, user_id, rating, comment, created_at
                       Join with users table to get reviewer name
                  -->
                </div>
              </div>
              <div class="tab-pane fade" id="shipping">
                <div class="row g-4">
                  <div class="col-md-6">
                    <h5 class="fw-bold mb-3">Shipping Information</h5>
                    <ul class="list-unstyled">
                      <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Free shipping on orders over Rs 5000</li>
                      <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Express shipping: Rs 500</li>
                      <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Delivery: 3-5 business days</li>
                      <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>International shipping available</li>
                    </ul>
                  </div>
                  <div class="col-md-6">
                    <h5 class="fw-bold mb-3">Return Policy</h5>
                    <ul class="list-unstyled">
                      <li class="mb-2"><i class="bi bi-arrow-clockwise text-primary me-2"></i>30-day return policy</li>
                      <li class="mb-2"><i class="bi bi-arrow-clockwise text-primary me-2"></i>Free returns for defective items</li>
                      <li class="mb-2"><i class="bi bi-arrow-clockwise text-primary me-2"></i>Original tags must be attached</li>
                      <li class="mb-2"><i class="bi bi-arrow-clockwise text-primary me-2"></i>Refund processed in 5-7 days</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Complete The Look / You May Also Like -->
    <div class="row mt-5">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h3 class="fw-bold mb-0">Complete The Look</h3>
          <a href="shop.php" class="btn btn-outline-primary btn-sm">View All</a>
        </div>
        <div class="row g-3" id="complementary-products">
          <!-- BACKEND_CLUE: Complementary products should come from database
               Table: products
               Logic: Select products from related categories (based on COMPLEMENTARY_PRODUCTS mapping)
               Limit: 4 products
          -->
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-dark text-white mt-5">
    <div class="container py-4">
      <div class="row align-items-center">
        <div class="col-md-2 mb-3 text-center text-md-start">
          <img src="img/logo1.png" alt="Urban Thread" class="logo-footer mb-2">
          <h5 class="fw-bold mb-0">Urban Thread</h5>
        </div>
        <div class="col-md-3 mb-3">
          <p class="mb-0 text-light">Fashion for every story. Join our community for exclusive offers and trends.</p>
        </div>
        <div class="col-md-3 mb-3">
          <h5 class="fw-bold mb-3">Quick Links</h5>
          <ul class="list-unstyled">
            <li class="mb-2"><a href="shop.php" class="text-light text-decoration-none">Shop</a></li>
            <li class="mb-2"><a href="about.php" class="text-light text-decoration-none">About Us</a></li>
            <li class="mb-2"><a href="contact.php" class="text-light text-decoration-none">Contact</a></li>
            <li class="mb-2"><a href="faq.php" class="text-light text-decoration-none">FAQ</a></li>
          </ul>
        </div>
        <div class="col-md-4 mb-3">
          <h5 class="fw-bold mb-3">Connect With Us</h5>
          <div class="d-flex gap-3">
            <a href="https://instagram.com" target="_blank" class="social-icon">
              <i class="bi bi-instagram"></i>
            </a>
            <a href="https://tiktok.com" target="_blank" class="social-icon">
              <i class="bi bi-tiktok"></i>
            </a>
            <a href="https://facebook.com" target="_blank" class="social-icon">
              <i class="bi bi-facebook"></i>
            </a>
            <a href="https://twitter.com" target="_blank" class="social-icon">
              <i class="bi bi-twitter"></i>
            </a>
          </div>
          <div class="mt-3">
            <p class="small mb-1"><i class="bi bi-envelope me-2"></i>support@urbanthread.com</p>
            <p class="small mb-0"><i class="bi bi-telephone me-2"></i>+1 234 567 8900</p>
          </div>
        </div>
      </div>
      <div class="row mt-4 pt-3 border-top border-secondary">
        <div class="col text-center">
          <small class="text-light">&copy; 2025 Urban Thread. All Rights Reserved.</small>
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
  <script src="js/product.js"></script>
</body>
</html>