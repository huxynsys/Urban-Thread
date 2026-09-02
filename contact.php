<?php
$current_page = basename($_SERVER['PHP_SELF']);
/*
PROJECT_MAP:
- /css/common.css                  -> global layout, navbar, footer
- /css/contact.css                 -> page-specific styles for contact page
- /js/common.js                    -> shared JS utilities & state
- /js/authNavbar.js                -> navbar authentication state
- /js/contact.js                   -> page-specific JS for contact page
- /includes/header.php             -> reusable header (future)
- /includes/footer.php             -> reusable footer (future)
- /config/db.php                   -> database connection (future)
- /api/                            -> backend endpoints (future)

DATA_FLOW:
- Current source: LocalStorage (client-side) for contact messages
- Future source: MySQL via PHP APIs
- Contact messages: Stored in localStorage, will move to contact_messages table
- Form submissions: Currently simulated, will be handled by backend API

ROLE:
- This file represents: Contact page
- Displays contact form and information
- Shows FAQ section with quick links
- Handles form submission with validation
- AUTH_REQUIRED: No (public page)
- SESSION_DEPENDENT: Cart count display (needs session for logged-in users)
- FORM HANDLING: Contact form with validation and submission
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact | Urban Thread</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Common Styles -->
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/navbar.css">
  <!-- Contact Page Specific Styles -->
  <link rel="stylesheet" href="css/contact.css">
  <style>
  /* Contact Header - Dark color matching footer */
  .contact-header {
    background: linear-gradient(135deg, #141E30 0%, #243B55 100%) !important;
  }
  
  /* Contact Information Card - Dark color matching footer */
  .contact-info-card {
    background: linear-gradient(135deg, #141E30 0%, #243B55 100%) !important;
  }
  
  /* Text colors inside contact info card */
  .contact-info-card h2,
  .contact-info-card h5,
  .contact-info-card p,
  .contact-info-card small {
    color: white !important;
  }
  
  /* Contact info items background */
  .contact-info-item {
    background: rgba(255, 255, 255, 0.1) !important;
  }
  
  .contact-info-item:hover {
    background: rgba(255, 255, 255, 0.2) !important;
  }
  
  /* Social connect buttons */
  .social-contact-btn {
    border: 2px solid rgba(255, 255, 255, 0.3) !important;
  }
  
  .social-contact-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.2);
  }
</style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <!-- Contact Page Header - Admin Sidebar Color -->
  <section class="contact-header py-5 text-center text-white">
    <div class="container">
      <h1 class="display-4 fw-bold mb-3">Get in Touch</h1>
      <p class="lead mb-4">We're here to help! Reach out to us anytime.</p>
    </div>
  </section>

  <!-- Main Contact Section -->
  <div class="container mt-5 mb-5">
    <div class="row g-4">
      <!-- Contact Form -->
      <div class="col-lg-7">
        <div class="contact-card p-4 p-md-5">
          <h2 class="fw-bold mb-4">Send Us a Message</h2>
          <form id="contact-form" method="POST" action="/api/contact/submit.php" autocomplete="off">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Your Name</label>
                <input type="text" class="form-control contact-input" id="contact-name" name="name" placeholder="Enter your name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" class="form-control contact-input" id="contact-email" name="email" placeholder="your.email@example.com" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Subject</label>
              <input type="text" class="form-control contact-input" id="contact-subject" name="subject" placeholder="What is this regarding?" required>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold">Message</label>
              <textarea class="form-control contact-input" id="contact-message" name="message" rows="5" placeholder="Tell us how we can help..." required></textarea>
            </div>
            <button type="submit" class="btn btn-dark btn-lg w-100 contact-submit-btn">
              <i class="bi bi-send me-2"></i>Send Message
            </button>
            <div id="contact-msg" class="mt-3 text-center"></div>
          </form>
        </div>
      </div>
      
      <!-- Contact Information Card - Admin Sidebar Color -->
      <div class="col-lg-5">
        <div class="contact-info-card p-4 p-md-5 h-100">
          <h2 class="fw-bold mb-4">Contact Information</h2>
          
          <div class="contact-info-item mb-4">
            <div class="contact-icon bg-primary">
              <i class="bi bi-envelope"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-1">Email</h5>
              <p class="mb-0">support@urbanthread.com</p>
              <small class="text-light opacity-75">Typically respond within 24 hours</small>
            </div>
          </div>
          
          <div class="contact-info-item mb-4">
            <div class="contact-icon bg-success">
              <i class="bi bi-telephone"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-1">Phone</h5>
              <p class="mb-0">+92-300-1234567</p>
              <small class="text-light opacity-75">Mon-Fri, 9:00 AM - 6:00 PM</small>
            </div>
          </div>
          
          <div class="contact-info-item mb-4">
            <div class="contact-icon bg-warning">
              <i class="bi bi-geo-alt"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-1">Address</h5>
              <p class="mb-0">Urban Thread HQ, Fashion Plaza<br>Lahore, Pakistan</p>
              <small class="text-light opacity-75">Visit us at our flagship store</small>
            </div>
          </div>
          
          <div class="social-connect mt-5">
            <h5 class="fw-bold mb-3">Connect With Us</h5>
            <div class="d-flex gap-3">
              <a href="https://instagram.com" target="_blank" class="social-contact-btn instagram">
                <i class="bi bi-instagram"></i> Instagram
              </a>
              <a href="https://tiktok.com" target="_blank" class="social-contact-btn tiktok">
                <i class="bi bi-tiktok"></i> TikTok
              </a>
              <a href="https://facebook.com" target="_blank" class="social-contact-btn facebook">
                <i class="bi bi-facebook"></i> Facebook
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="row mt-5">
      <div class="col-12">
        <div class="faq-contact-card p-4 p-md-5">
          <h2 class="fw-bold text-center mb-4">Frequently Asked Questions</h2>
          <div class="row g-4">
            <div class="col-md-4">
              <div class="faq-item p-3">
                <h5 class="fw-bold"><i class="bi bi-truck me-2"></i>Shipping & Delivery</h5>
                <p class="mb-0">Fast delivery across Pakistan. Our admin will manually update you about your order status.</p>
                <a href="faq.php" class="btn btn-link btn-sm p-0 mt-2">Learn more →</a>
              </div>
            </div>
            <div class="col-md-4">
              <div class="faq-item p-3">
                <h5 class="fw-bold"><i class="bi bi-credit-card me-2"></i>Payment Methods</h5>
                <p class="mb-0">We only accept Urban Card for online payments. No other cards accepted.</p>
                <a href="faq.php" class="btn btn-link btn-sm p-0 mt-2">Learn more →</a>
              </div>
            </div>
            <div class="col-md-4">
              <div class="faq-item p-3">
                <h5 class="fw-bold"><i class="bi bi-arrow-return-left me-2"></i>Returns & Exchanges</h5>
                <p class="mb-0">14-day return policy. Our admin will manually process your returns.</p>
                <a href="returns.php" class="btn btn-link btn-sm p-0 mt-2">Learn more →</a>
              </div>
            </div>
          </div>
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
  <script src="js/contact.js"></script>
</body>
</html>