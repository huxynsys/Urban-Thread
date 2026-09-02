<?php
$current_page = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Urban Thread | Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap and Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Common/Navbar/Footer Styles -->
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/navbar.css">
  <!-- Page-specific Styles -->
  <link rel="stylesheet" href="css/index.css">
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <!-- Hero Banner Carousel -->
  <section class="hero-section position-relative">
    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4000">
      <div class="carousel-inner">
        <div class="carousel-item active banner-1">
          <div class="container text-center text-white py-5">
            <h1 class="display-4 fw-bold mb-3 banner-shadow">Urban Streetwear Collection</h1>
            <p class="lead mb-4 banner-shadow">Fresh arrivals for every vibe</p>
            <a href="shop.php" class="btn btn-dark btn-lg hero-btn">Shop Now</a>
            <div class="social-media-bar mt-4 d-flex justify-content-center gap-4">
              <a href="https://instagram.com" target="_blank" class="text-white fs-2 social-icon"><i class="bi bi-instagram"></i></a>
              <a href="https://tiktok.com" target="_blank" class="text-white fs-2 social-icon"><i class="bi bi-tiktok"></i></a>
              <a href="https://facebook.com" target="_blank" class="text-white fs-2 social-icon"><i class="bi bi-facebook"></i></a>
            </div>
          </div>
        </div>
        <div class="carousel-item banner-2">
          <div class="container text-center text-white py-5">
            <h1 class="display-4 fw-bold mb-3 banner-shadow">Eco-Friendly Fashion</h1>
            <p class="lead mb-4 banner-shadow">Sustainable style for a better tomorrow</p>
            <a href="shop.php" class="btn btn-success btn-lg hero-btn">Explore Eco Line</a>
          </div>
        </div>
        <div class="carousel-item banner-3">
          <div class="container text-center text-white py-5">
            <h1 class="display-4 fw-bold mb-3 banner-shadow">Kids & Teens Trends</h1>
            <p class="lead mb-4 banner-shadow">Urban looks for every age</p>
            <a href="shop.php" class="btn btn-warning btn-lg hero-btn">View Kids Styles</a>
          </div>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>
  </section>

  <!-- Featured Products -->
  <section class="featured-products py-5 bg-light">
    <div class="container">
      <h2 class="fw-bold text-center mb-4">Featured Products</h2>
      <div class="row" id="featured-products">
        <?php
        
        $featured_products = [
          [
            'id' => 1,
            'name' => 'Urban Hoodie Pro',
            'price' => 2200,
            'image' => 'img/hoodie1.jpg',
            'category' => 'Hoodies'
          ],
          [
            'id' => 2,
            'name' => 'Classic Cotton Shirt',
            'price' => 1500,
            'image' => 'img/shirt1.jpg',
            'category' => 'Shirts'
          ],
          [
            'id' => 3,
            'name' => 'Denim Jacket Premium',
            'price' => 3500,
            'image' => 'img/jacket1.jpg',
            'category' => 'Jackets'
          ],
          [
            'id' => 4,
            'name' => 'Street Style Sneakers',
            'price' => 4200,
            'image' => 'img/shoes1.jpg',
            'category' => 'Shoes'
          ]
        ];
        
        foreach ($featured_products as $product):
        ?>
        <div class="col-md-3 col-sm-6 mb-3">
          <div class="card h-100 border-0">
            <img src="<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>" style="height:230px;object-fit:cover;">
            <div class="card-body text-center">
              <h5 class="card-title fw-bold"><?php echo $product['name']; ?></h5>
              <p class="card-text text-muted">Rs <?php echo $product['price']; ?></p>
              <a href="shop.php?category=<?php echo urlencode($product['category']); ?>" class="btn btn-outline-dark btn-sm">
                View <?php echo $product['category']; ?>
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Benefits Section - ALL THREE CARDS HAVE ICONS AND EQUAL SIZE -->
  <section class="benefits-section py-4">
    <div class="container">
      <div class="row text-center">
        <div class="col-md-4 mb-3">
          <div class="benefit-card p-3 benefit-card-clickable h-100" data-benefit="fast-delivery">
            <span class="benefit-icon"><i class="bi bi-truck"></i></span>
            <h5>Fast Delivery</h5>
            <p>Get your orders delivered swiftly to your doorstep.</p>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="benefit-card p-3 benefit-card-clickable h-100" data-benefit="easy-returns">
            <span class="benefit-icon"><i class="bi bi-arrow-repeat"></i></span>
            <h5>Easy Returns</h5>
            <p>14-day hassle-free returns & exchanges.</p>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="benefit-card p-3 benefit-card-clickable h-100" data-benefit="sustainable-fashion">
            <span class="benefit-icon"><i class="bi bi-leaf"></i></span>
            <h5>Sustainable Fashion</h5>
            <p>Ethically sourced, eco-friendly clothing.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
  
  <!-- Benefit Modal - Popup message when clicking any benefit card -->
  <div class="modal fade" id="benefit-modal" tabindex="-1" aria-labelledby="benefitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content benefit-modal-content">
        <div class="modal-header benefit-modal-header">
          <h5 class="modal-title" id="benefitModalLabel"></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body benefit-modal-body" id="benefit-modal-body">
          <!-- BACKEND_CLUE: Modal content should come from database table: `benefits`
               Fields: benefit_key, title, description, details_json
          -->
        </div>
      </div>
    </div>
  </div>
  
  <!-- Footer -->
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
  
  <!-- JS includes -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/common.js"></script>
  <script src="js/authNavbar.js"></script>
  <script src="js/index.js"></script>
</body>
</html>