// js/index.js - Home page specific functionality ONLY
// BACKEND_CLUE: This entire file will be replaced by PHP + AJAX calls
// Database tables needed: products, cart, users, benefits

window.addEventListener('DOMContentLoaded', async () => {
  updateCartCount();
  updateNavbarAuth();
  setupBackToTop();
  setupBenefitCards();
});

// Cart count update
function updateCartCount() {
  const cartData = JSON.parse(localStorage.getItem('cart')) || [];
  const cartCount = document.getElementById('cart-count');
  if (cartCount) {
    cartCount.textContent = cartData.length;
  }
}

// Update navbar auth (login/profile dropdown)
function updateNavbarAuth() {
  const authArea = document.getElementById('navbar-auth-area');
  if (!authArea) return;
  
  const user = JSON.parse(localStorage.getItem('currentUser'));
  
  if (user) {
    authArea.innerHTML = `
      <div class="dropdown">
        <button class="btn btn-outline-dark dropdown-toggle d-flex align-items-center" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-person-circle me-1"></i>
          <span class="d-none d-md-inline">${user.name.split(' ')[0]}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
          <li><a class="dropdown-item" href="account.php"><i class="bi bi-person me-2"></i>My Account</a></li>
          <li><a class="dropdown-item" href="card.php"><i class="bi bi-credit-card me-2"></i>Virtual Card</a></li>
          <li><a class="dropdown-item" href="shop.php"><i class="bi bi-shop me-2"></i>Shop</a></li>
          ${user.role === 'admin' ? '<li><a class="dropdown-item" href="admin.php"><i class="bi bi-gear me-2"></i>Admin Panel</a></li>' : ''}
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="#" id="logout-nav"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    `;
    
    document.getElementById('logout-nav')?.addEventListener('click', function(e) {
      e.preventDefault();
      localStorage.removeItem('currentUser');
      window.location.reload();
    });
  } else {
    authArea.innerHTML = `
      <a href="reg.php" class="btn btn-outline-dark d-flex align-items-center">
        <i class="bi bi-person me-1"></i>
        <span class="d-none d-md-inline">Login</span>
      </a>
    `;
  }
}

// Back to Top Button
function setupBackToTop() {
  const btnTop = document.getElementById('btn-top');
  if (!btnTop) return;
  
  window.addEventListener('scroll', () => {
    btnTop.style.display = (window.scrollY > 200) ? 'flex' : 'none';
  });
  btnTop.addEventListener('click', () => {
    window.scrollTo({top: 0, behavior: 'smooth'});
  });
  btnTop.style.display = 'none';
}

// Benefits Modal - Popup when clicking Fast Delivery, Easy Returns, Sustainable Fashion
function setupBenefitCards() {
  const modalElement = document.getElementById('benefit-modal');
  if (!modalElement) return;
  
  const modal = new bootstrap.Modal(modalElement);
  const modalLabel = document.getElementById('benefitModalLabel');
  const modalBody = document.getElementById('benefit-modal-body');

  const benefitData = {
    "fast-delivery": {
      title: "Fast Delivery",
      content: `
        <table class="table table-bordered table-sm mb-0">
          <tbody>
            <tr><th>Courier</th><td>TCS, Leopards, CallCourier, M&P</td></tr>
            <tr><th>Avg Delivery Time</th><td>1-2 days (Local), 2-4 days (Nationwide)</td></tr>
            <tr><th>Tracking</th><td>Available</td></tr>
            <tr><th>Extra Charges</th><td>Rs. 250 (Nationwide), Rs. 150 (Local)</td></tr>
          </tbody>
        </table>
      `
    },
    "easy-returns": {
      title: "Easy Returns",
      content: `
        <table class="table table-bordered table-sm mb-0">
          <tbody>
            <tr><th>Return Window</th><td>14 days</td></tr>
            <tr><th>Exchange Option</th><td>Yes</td></tr>
            <tr><th>Refund Method</th><td>Bank Transfer, Store Credit</td></tr>
            <tr><th>Return Shipping</th><td>Paid by customer</td></tr>
          </tbody>
        </table>
      `
    },
    "sustainable-fashion": {
      title: "Sustainable Fashion",
      content: `
        <table class="table table-bordered table-sm mb-0">
          <tbody>
            <tr><th>Materials</th><td>Organic Cotton, Recycled Polyester</td></tr>
            <tr><th>Certifications</th><td>GOTS, OEKO-TEX</td></tr>
            <tr><th>Packaging</th><td>Biodegradable</td></tr>
            <tr><th>Production</th><td>Ethical, Fair Wages</td></tr>
          </tbody>
        </table>
      `
    }
  };

  const benefitCards = document.querySelectorAll('.benefit-card-clickable');
  
  benefitCards.forEach(card => {
    card.addEventListener('click', function(e) {
      const benefitKey = this.getAttribute('data-benefit');
      const data = benefitData[benefitKey];
      
      if (data) {
        modalLabel.textContent = data.title;
        modalBody.innerHTML = data.content;
        modal.show();
      }
    });
  });
}