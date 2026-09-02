<?php
$current_page = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About | Urban Thread</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/navbar.css">
  <link rel="stylesheet" href="css/about.css">
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <div class="container mt-5 mb-5">
    <h2 class="fw-bold text-center mb-4">About Urban Thread</h2>
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card p-4 shadow-sm mb-4">
          <h4 class="fw-bold mb-2">Our Story</h4>
          <p>
            <!-- BACKEND_CLUE: This content can be moved to database table: `site_content`
                 Fields: id, page, section, title, content, updated_at
                 Query: SELECT content FROM site_content WHERE page = 'about' AND section = 'story'
            -->
            Urban Thread was born from a passion for contemporary fashion and a vision to make stylish clothing accessible to everyone. 
            What started as a small online boutique has evolved into a vibrant fashion destination, offering curated collections for Men, Women, and Kids.
          </p>
        </div>
        <div class="card p-4 shadow-sm mb-4">
          <h4 class="fw-bold mb-2">Mission & Vision</h4>
          <ul>
            <!-- BACKEND_CLUE: This content can be moved to database table: `site_content`
                 Fields: id, page, section, title, content, updated_at
                 Query: SELECT content FROM site_content WHERE page = 'about' AND section = 'mission'
            -->
            <li><b>Mission:</b> To empower self-expression through fashion, blending urban aesthetics with everyday comfort.</li>
            <li><b>Vision:</b> To become the leading fashion hub for trendsetters, families, and everyone in between.</li>
          </ul>
        </div>
        <div class="card p-4 shadow-sm mb-4">
          <h4 class="fw-bold mb-2">Sustainability Values</h4>
          <p>
            <!-- BACKEND_CLUE: This content can be moved to database table: `site_content`
                 Fields: id, page, section, title, content, updated_at
                 Query: SELECT content FROM site_content WHERE page = 'about' AND section = 'sustainability'
            -->
            We believe in responsible fashion. Our collections feature eco-friendly fabrics, ethical sourcing, and sustainable packaging. 
            Urban Thread is committed to reducing our carbon footprint and giving back to our community through charitable programs.
          </p>
        </div>
        <div class="card p-4 shadow-sm mb-4 text-center">
          <h5 class="fw-bold mb-2">Follow Us</h5>
          <!-- BACKEND_CLUE: Social media links can be stored in database table: `social_links`
               Fields: id, platform, url, icon, sort_order, is_active
               Query: SELECT * FROM social_links WHERE is_active = 1 ORDER BY sort_order
          -->
          <a href="https://instagram.com/urbanthread" target="_blank" class="text-dark me-2"><i class="bi bi-instagram"></i></a>
          <a href="https://tiktok.com/@urbanthread" target="_blank" class="text-dark me-2"><i class="bi bi-tiktok"></i></a>
          <a href="https://facebook.com/urbanthread" target="_blank" class="text-dark me-2"><i class="bi bi-facebook"></i></a>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer (Same as index) -->
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
  <script src="js/about.js"></script>
</body>
</html>