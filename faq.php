<?php
$current_page = basename($_SERVER['PHP_SELF']);
/*
PROJECT_MAP:
- /css/common.css                  -> global layout, navbar, footer
- /css/faq.css                     -> page-specific styles for FAQ page
- /js/common.js                    -> shared JS utilities & state
- /js/authNavbar.js                -> navbar authentication state
- /js/faq.js                       -> page-specific JS for FAQ page
- /includes/header.php             -> reusable header (future)
- /includes/footer.php             -> reusable footer (future)
- /config/db.php                   -> database connection (future)
- /api/                            -> backend endpoints (future)

DATA_FLOW:
- Current source: Static HTML content with client-side search
- Future source: MySQL via PHP APIs for dynamic FAQ content
- FAQ content: Currently hardcoded in HTML, can be moved to database table
- FAQ search: Client-side JavaScript searching through DOM
- FAQ analytics: Stored in localStorage, will move to database

ROLE:
- This file represents: FAQ page
- Displays categorized frequently asked questions with accordion
- Contains search functionality to filter questions
- Links to contact page for additional support
- AUTH_REQUIRED: No (public page)
- SESSION_DEPENDENT: Cart count display (needs session for logged-in users)
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>FAQ | Urban Thread</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Common Styles -->
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/navbar.css">
  <!-- FAQ Page Specific Styles -->
  <link rel="stylesheet" href="css/faq.css">
  <style>
    .faq-header {
      background: linear-gradient(135deg, #141E30 0%, #243B55 100%);
    }
    .faq-contact-card {
      background: linear-gradient(135deg, #141E30 0%, #243B55 100%);
    }
    .faq-contact-card .btn-dark {
      background: rgba(255, 255, 255, 0.2);
      border: 2px solid rgba(255, 255, 255, 0.3);
    }
    .faq-contact-card .btn-dark:hover {
      background: rgba(255, 255, 255, 0.3);
    }
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <!-- FAQ Header - Dark color matching footer -->
  <section class="faq-header py-5 text-center text-white">
    <div class="container">
      <h1 class="display-4 fw-bold mb-3">Frequently Asked Questions</h1>
      <p class="lead mb-4">Find quick answers to common questions about Urban Thread</p>
    </div>
  </section>

  <!-- Main FAQ Section -->
  <div class="container mt-5 mb-5">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <!-- Search FAQ -->
        <div class="faq-search-card p-4 mb-5">
          <h3 class="fw-bold mb-3">Search Questions</h3>
          <div class="input-group">
            <input type="text" class="form-control faq-search-input" id="faq-search" placeholder="Type your question here...">
            <button class="btn btn-dark" type="button" id="faq-search-btn">
              <i class="bi bi-search"></i> Search
            </button>
          </div>
          <div id="faq-search-results" class="mt-3"></div>
        </div>

        <!-- FAQ Accordion -->
        <div class="faq-accordion" id="faqAccordion">
          <h2 class="fw-bold mb-4 text-center">Common Questions</h2>
          
          <!-- BACKEND_CLUE: FAQ categories and questions can be moved to database
               Table: faq_categories
               Fields: id, name, icon, sort_order
               
               Table: faq_items
               Fields: id, category_id, question, answer, sort_order, is_active
               
               This would allow admin to manage FAQs via admin panel
          -->
          
          <!-- Order & Shipping -->
          <div class="faq-category mb-4">
            <h4 class="category-title"><i class="bi bi-truck me-2"></i>Order & Shipping</h4>
            
            <div class="accordion-item mb-3">
              <h2 class="accordion-header" id="faq1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1-collapse">
                  How can I track my order?
                </button>
              </h2>
              <div id="faq1-collapse" class="accordion-collapse collapse show">
                <div class="accordion-body">
                  <p>Our admin team will manually update you about your order status via email or SMS. We do not provide automated tracking IDs.</p>
                </div>
              </div>
            </div>

            <div class="accordion-item mb-3">
              <h2 class="accordion-header" id="faq2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2-collapse">
                  What are your shipping charges?
                </button>
              </h2>
              <div id="faq2-collapse" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <p>Shipping charges vary based on your location:</p>
                  <ul>
                    <li><strong>Local (Same City):</strong> Rs. 150</li>
                    <li><strong>Nationwide:</strong> Rs. 250</li>
                    <li><strong>Free shipping</strong> on orders above Rs. 5000</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="accordion-item mb-3">
              <h2 class="accordion-header" id="faq3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3-collapse">
                  How long does delivery take?
                </button>
              </h2>
              <div id="faq3-collapse" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <p>Delivery times:</p>
                  <ul>
                    <li><strong>Local:</strong> 1-2 business days</li>
                    <li><strong>Nationwide:</strong> 2-4 business days</li>
                    <li><strong>Express delivery:</strong> Available at additional cost</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Payments -->
          <div class="faq-category mb-4">
            <h4 class="category-title"><i class="bi bi-credit-card me-2"></i>Payments</h4>
            
            <div class="accordion-item mb-3">
              <h2 class="accordion-header" id="faq4">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4-collapse">
                  What payment methods do you accept?
                </button>
              </h2>
              <div id="faq4-collapse" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <p><strong>We only accept Urban Card for online payments.</strong> No other credit or debit cards are accepted.</p>
                  <p>Urban Card is our exclusive payment method that ensures secure and fast transactions.</p>
                </div>
              </div>
            </div>

            <div class="accordion-item mb-3">
              <h2 class="accordion-header" id="faq5">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5-collapse">
                  Is my payment information secure?
                </button>
              </h2>
              <div id="faq5-collapse" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <p>Yes! We use industry-standard encryption to protect your payment information. Urban Card payments are processed through our secure payment gateway.</p>
                  <p>We never store your full card details on our servers.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Returns & Exchanges -->
          <div class="faq-category mb-4">
            <h4 class="category-title"><i class="bi bi-arrow-return-left me-2"></i>Returns & Exchanges</h4>
            
            <div class="accordion-item mb-3">
              <h2 class="accordion-header" id="faq6">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6-collapse">
                  What is your return policy?
                </button>
              </h2>
              <div id="faq6-collapse" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <p>We offer a 14-day return window from the date of delivery. Items must be:</p>
                  <ul>
                    <li>Unworn, unwashed, and in original condition</li>
                    <li>With original tags and packaging</li>
                    <li>Accompanied by proof of purchase</li>
                  </ul>
                  <p>Visit our <a href="returns.php" class="faq-link">Returns page</a> for detailed instructions.</p>
                </div>
              </div>
            </div>

            <div class="accordion-item mb-3">
              <h2 class="accordion-header" id="faq7">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7-collapse">
                  How do I start a return?
                </button>
              </h2>
              <div id="faq7-collapse" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <p>To start a return:</p>
                  <ol>
                    <li>Contact our support team via the <a href="contact.php" class="faq-link">Contact page</a></li>
                    <li>Our admin will provide you with return instructions</li>
                    <li>Package your item securely</li>
                    <li>Drop off at designated location</li>
                  </ol>
                  <p><strong>Note:</strong> Return shipping costs are the customer's responsibility unless the item is defective.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Products & Quality -->
          <div class="faq-category mb-4">
            <h4 class="category-title"><i class="bi bi-star me-2"></i>Products & Quality</h4>
            
            <div class="accordion-item mb-3">
              <h2 class="accordion-header" id="faq8">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8-collapse">
                  Are your products sustainable?
                </button>
              </h2>
              <div id="faq8-collapse" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <p>Yes! We're committed to sustainable fashion:</p>
                  <ul>
                    <li>Organic and recycled materials</li>
                    <li>Ethical production practices</li>
                    <li>Biodegradable packaging</li>
                    <li>Fair wages for workers</li>
                  </ul>
                  <p>Learn more on our <a href="about.php" class="faq-link">About page</a>.</p>
                </div>
              </div>
            </div>

            <div class="accordion-item mb-3">
              <h2 class="accordion-header" id="faq9">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9-collapse">
                  How do I know what size to order?
                </button>
              </h2>
              <div id="faq9-collapse" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <p>Check our size guide available on each product page. If you need help:</p>
                  <ul>
                    <li>Contact our support team for sizing advice</li>
                    <li>We provide detailed measurements for each product</li>
                    <li>Remember we offer exchanges if the size doesn't fit</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Account & Support -->
          <div class="faq-category">
            <h4 class="category-title"><i class="bi bi-person me-2"></i>Account & Support</h4>
            
            <div class="accordion-item mb-3">
              <h2 class="accordion-header" id="faq10">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10-collapse">
                  How do I contact customer support?
                </button>
              </h2>
              <div id="faq10-collapse" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <p>You can reach us through:</p>
                  <ul>
                    <li><strong>Email:</strong> support@urbanthread.com</li>
                    <li><strong>Phone:</strong> +92-300-1234567 (Mon-Fri, 9AM-6PM)</li>
                    <li><strong>Contact Form:</strong> <a href="contact.php" class="faq-link">Visit our Contact page</a></li>
                    <li><strong>Social Media:</strong> DM us on Instagram or Facebook</li>
                  </ul>
                  <p>We typically respond within 24 hours.</p>
                </div>
              </div>
            </div>

            <div class="accordion-item mb-3">
              <h2 class="accordion-header" id="faq11">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq11-collapse">
                  How do I update my account information?
                </button>
              </h2>
              <div id="faq11-collapse" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <p>To update your account information:</p>
                  <ol>
                    <li>Log in to your account</li>
                    <li>Go to "Account Settings"</li>
                    <li>Update your details</li>
                    <li>Click "Save Changes"</li>
                  </ol>
                  <p>If you encounter any issues, contact our support team for assistance.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Still Have Questions -->
        <div class="faq-contact-card p-4 p-md-5 mt-5 text-center">
          <h3 class="fw-bold mb-3">Still Have Questions?</h3>
          <p class="mb-4">Can't find the answer you're looking for? Our team is here to help!</p>
          <a href="contact.php" class="btn btn-dark btn-lg">
            <i class="bi bi-envelope me-2"></i>Contact Support
          </a>
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
  <script src="js/faq.js"></script>
</body>
</html>