<?php
$current_page = basename($_SERVER['PHP_SELF']);
/*
PROJECT_MAP:
- /css/common.css                  -> global layout, navbar, footer
- /css/returns.css                 -> page-specific styles for returns page
- /js/common.js                    -> shared JS utilities & state
- /js/authNavbar.js                -> navbar authentication state
- /js/returns.js                   -> page-specific JS for returns page
- /includes/header.php             -> reusable header (future)
- /includes/footer.php             -> reusable footer (future)
- /config/db.php                   -> database connection (future)
- /api/                            -> backend endpoints (future)

DATA_FLOW:
- Current source: LocalStorage (client-side) for return requests
- Future source: MySQL via PHP APIs
- Return requests: Stored in localStorage, will move to return_requests table
- Cart count: Stored in localStorage, will move to database cart table

ROLE:
- This file represents: Returns & Exchanges page
- Displays return policy, process steps, conditions, FAQ
- Contains contact information and return request simulation
- AUTH_REQUIRED: No (public page)
- SESSION_DEPENDENT: Cart count display (needs session for logged-in users)
- FORM HANDLING: Return request form will be added in future
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Returns & Exchanges | Urban Thread</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Common Styles -->
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/navbar.css">
  <!-- Returns Page Specific Styles -->
  <link rel="stylesheet" href="css/returns.css">
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <!-- Returns Header - Dark color matching footer -->
  <section class="returns-header py-5 text-center text-white">
    <div class="container">
      <h1 class="display-4 fw-bold mb-3">Returns & Exchanges</h1>
      <p class="lead mb-4">Easy returns and exchanges for your peace of mind</p>
    </div>
  </section>

  <!-- Main Returns Section -->
  <div class="container mt-5 mb-5">
    <!-- Policy Overview -->
    <div class="row mb-5">
      <div class="col-12">
        <div class="policy-overview-card p-4 p-md-5 text-center">
          <h2 class="fw-bold mb-3">Our 14-Day Return Policy</h2>
          <p class="lead mb-4">We want you to love what you buy. If you don't, we're here to help.</p>
          
          <div class="row g-4 mt-4">
            <div class="col-md-4">
              <div class="policy-icon-card">
                <div class="policy-icon days">
                  <i class="bi bi-calendar-check"></i>
                </div>
                <h4>14 Days</h4>
                <p>Return window from delivery date</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="policy-icon-card">
                <div class="policy-icon manual">
                  <i class="bi bi-person-check"></i>
                </div>
                <h4>Admin Processed</h4>
                <p>Manual approval by our team</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="policy-icon-card">
                <div class="policy-icon refund">
                  <i class="bi bi-currency-exchange"></i>
                </div>
                <h4>Easy Refunds</h4>
                <p>To original payment method</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-5">
      <!-- Return Process -->
      <div class="col-lg-8">
        <!-- How to Return -->
        <div class="return-process-card p-4 p-md-5 mb-5">
          <h2 class="fw-bold mb-4"><i class="bi bi-arrow-right-circle me-2"></i>How to Return an Item</h2>
          
          <div class="process-steps">
            <div class="process-step">
              <div class="step-number">1</div>
              <div class="step-content">
                <h4 class="fw-bold">Contact Our Support</h4>
                <p>Email us at <strong>returns@urbanthread.com</strong> or use our <a href="contact.php" class="returns-link">Contact Form</a> within 14 days of receiving your order.</p>
                <p><strong>Note:</strong> Our admin team will manually review and approve your return request. No automated system is used.</p>
              </div>
            </div>
            
            <div class="process-step">
              <div class="step-number">2</div>
              <div class="step-content">
                <h4 class="fw-bold">Wait for Instructions</h4>
                <p>Our admin will email you detailed return instructions including:</p>
                <ul>
                  <li>Approved return authorization</li>
                  <li>Packaging instructions</li>
                  <li>Designated drop-off locations</li>
                  <li>Expected timeline for processing</li>
                </ul>
              </div>
            </div>
            
            <div class="process-step">
              <div class="step-number">3</div>
              <div class="step-content">
                <h4 class="fw-bold">Package & Ship</h4>
                <p>Prepare your item with:</p>
                <ul>
                  <li>All original tags attached</li>
                  <li>Unworn and unwashed condition</li>
                  <li>Original packaging if available</li>
                  <li>Include the order receipt or invoice</li>
                </ul>
                <p><strong>Shipping Cost:</strong> Return shipping is paid by the customer unless the item is defective or wrong item was sent.</p>
              </div>
            </div>
            
            <div class="process-step">
              <div class="step-number">4</div>
              <div class="step-content">
                <h4 class="fw-bold">Receive Refund/Exchange</h4>
                <p>Once we receive and inspect your return:</p>
                <ul>
                  <li><strong>Refunds:</strong> Processed within 5-7 business days to your original Urban Card</li>
                  <li><strong>Exchanges:</strong> New item shipped within 2-3 business days after approval</li>
                  <li><strong>Store Credit:</strong> Available if preferred</li>
                </ul>
                <p>Our admin will notify you via email at each step of the process.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Conditions & Exceptions -->
        <div class="conditions-card p-4 p-md-5">
          <h2 class="fw-bold mb-4"><i class="bi bi-clipboard-check me-2"></i>Return Conditions</h2>
          
          <div class="row">
            <div class="col-md-6">
              <div class="condition-item accepted">
                <h5 class="fw-bold"><i class="bi bi-check-circle me-2"></i>Accepted Returns</h5>
                <ul>
                  <li>Items in original condition</li>
                  <li>With all tags attached</li>
                  <li>Unworn and unwashed</li>
                  <li>Original packaging</li>
                  <li>Within 14 days of delivery</li>
                  <li>Wrong size or color</li>
                  <li>Defective items</li>
                </ul>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="condition-item not-accepted">
                <h5 class="fw-bold"><i class="bi bi-x-circle me-2"></i>Not Accepted</h5>
                <ul>
                  <li>Items without tags</li>
                  <li>Worn or washed items</li>
                  <li>Damaged by customer</li>
                  <li>After 14-day period</li>
                  <li>Customized items</li>
                  <li>Underwear & swimwear</li>
                  <li>Final sale items</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="note-box mt-4">
            <h5 class="fw-bold"><i class="bi bi-info-circle me-2"></i>Important Note</h5>
            <p>All returns are manually reviewed by our admin team. Final approval is at Urban Thread's discretion. We reserve the right to refuse returns that don't meet our conditions.</p>
          </div>
        </div>
      </div>
      
      <!-- Sidebar - Quick Info -->
      <div class="col-lg-4">
        <!-- Exchange Options -->
        <div class="sidebar-card exchange-card p-4 mb-4">
          <h4 class="fw-bold mb-3"><i class="bi bi-arrow-left-right me-2"></i>Exchange Options</h4>
          <p>Prefer an exchange instead of a refund? We offer:</p>
          <ul>
            <li><strong>Size Exchange:</strong> Different size of same item</li>
            <li><strong>Color Exchange:</strong> Different color of same item</li>
            <li><strong>Item Exchange:</strong> Different item of equal value</li>
            <li><strong>Store Credit:</strong> For future purchases</li>
          </ul>
          <div class="exchange-note">
            <small><i class="bi bi-lightbulb me-1"></i>Exchanges are processed faster than refunds</small>
          </div>
        </div>
        
        <!-- Refund Timeline -->
        <div class="sidebar-card timeline-card p-4 mb-4">
          <h4 class="fw-bold mb-3"><i class="bi bi-clock me-2"></i>Refund Timeline</h4>
          <div class="timeline">
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <h6>Return Received</h6>
                <small>1-2 days inspection</small>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <h6>Approval Process</h6>
                <small>Admin manual review</small>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <h6>Refund Initiated</h6>
                <small>To your Urban Card</small>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <h6>Funds Available</h6>
                <small>5-7 business days</small>
              </div>
            </div>
          </div>
          <div class="refund-note mt-3">
            <small><i class="bi bi-credit-card me-1"></i>Refunds only to original Urban Card payment</small>
          </div>
        </div>
        
        <!-- Contact Help -->
        <div class="sidebar-card help-card p-4">
          <h4 class="fw-bold mb-3"><i class="bi bi-headset me-2"></i>Need Help?</h4>
          <p>Our support team is here to assist you with returns and exchanges.</p>
          <div class="contact-info">
            <p><i class="bi bi-envelope me-2"></i> <strong>Email:</strong> returns@urbanthread.com</p>
            <p><i class="bi bi-telephone me-2"></i> <strong>Phone:</strong> +92-300-1234567</p>
            <p><i class="bi bi-clock me-2"></i> <strong>Hours:</strong> Mon-Fri, 9AM-6PM</p>
          </div>
          <a href="contact.php" class="btn btn-outline-dark w-100 mt-3">
            <i class="bi bi-chat-dots me-2"></i>Contact Support
          </a>
        </div>
      </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="row mt-5">
      <div class="col-12">
        <div class="faq-returns-card p-4 p-md-5">
          <h2 class="fw-bold text-center mb-4">Common Return Questions</h2>
          <div class="row g-4">
            <div class="col-md-6">
              <div class="faq-item">
                <h5 class="fw-bold">How do I know if my return is approved?</h5>
                <p>Our admin will email you with approval status. No automated tracking - all communication is manual.</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="faq-item">
                <h5 class="fw-bold">Can I return sale items?</h5>
                <p>Yes, sale items can be returned within 14 days unless marked as "Final Sale".</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="faq-item">
                <h5 class="fw-bold">What if I receive a wrong/damaged item?</h5>
                <p>Contact us immediately. We'll arrange a free return and expedite replacement at no cost to you.</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="faq-item">
                <h5 class="fw-bold">How long do exchanges take?</h5>
                <p>Exchanges are typically processed within 3-5 business days after we receive your return.</p>
              </div>
            </div>
          </div>
          <div class="text-center mt-4">
            <a href="faq.php" class="btn btn-link">View More FAQs →</a>
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
  <script src="js/returns.js"></script>
</body>
</html>