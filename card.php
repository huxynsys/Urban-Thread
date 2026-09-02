<?php
// Require an authenticated session before rendering the card page
require_once __DIR__ . '/api/config/db.php';
startSession();
$user = isLoggedIn();
if (!$user) {
    header('Location: ' . SITE_URL . '/reg.php');
    exit();
}
$current_page = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Urban Thread Virtual Card - Your Digital Fashion Wallet</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Manage your Urban Thread Virtual Card - Earn points, redeem rewards, and track transactions">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  
  <!-- COMMON CSS (Navbar, Footer, Global Styles) -->
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/navbar.css">
  
  <!-- Card Page Specific CSS -->
  <link rel="stylesheet" href="css/card.css">
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="img/favicon.ico">
  
  <style>
    /* Ensure buttons are clickable */
    .btn-action {
        cursor: pointer !important;
        pointer-events: auto !important;
        z-index: 100 !important;
    }
    #redeem-btn {
        background-color: #ffc107 !important;
        color: #000 !important;
        font-weight: bold !important;
        border: none !important;
    }
    #redeem-btn:hover {
        background-color: #ffda6a !important;
        transform: scale(1.02);
    }
  </style>
</head>
<body>
  <!-- NAVBAR - IDENTICAL TO INDEX.PHP (7 options, NO search bar) -->
  <?php include 'includes/navbar.php'; ?>

  <!-- Search Section (From Common CSS) -->
  <section class="search-section">
    <div class="container">
      <h2 class="search-title">Manage Your Virtual Card</h2>
      <div class="search-container">
        <form id="main-search-form" method="GET" action="shop.php">
          <input type="text" id="main-search-input" name="search" placeholder="Search for products to purchase with your card..." autocomplete="off">
          <button type="submit" id="main-search-btn">
            <i class="bi bi-search"></i> Search Products
          </button>
        </form>
        <div id="main-search-results" class="mt-2"></div>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <main class="container my-4 my-lg-5 flex-grow-1">
    <!-- Page Header -->
    <div class="row mb-4">
      <div class="col-12">
        <h1 class="fw-bold mb-2 text-center">
          <i class="bi bi-credit-card-2-front me-2"></i>Urban Thread Virtual Card
        </h1>
        <p class="text-muted text-center mb-4">Your digital fashion wallet for exclusive rewards and seamless shopping</p>
      </div>
    </div>

    <!-- Card and Actions Section -->
    <div class="row g-4 justify-content-center">
      <!-- Virtual Card Display -->
      <div class="col-lg-6 col-md-8">
        <div class="card-virtual p-4 p-lg-5 position-relative">
          <!-- Card Chip -->
          <div class="position-absolute top-0 end-0 m-4">
            <i class="bi bi-cpu text-warning" style="font-size: 2rem;"></i>
          </div>
          
          <!-- Card Logo -->
          <div class="virtual-card-logo mb-4">
            <img src="img/logo1.png" alt="Urban Thread Logo" style="width: 80px;">
          </div>
          
          <!-- Card Number -->
          <div class="virtual-card-number mb-3" id="card-number">
            Loading...
          </div>
          
          <!-- Cardholder Info -->
          <div class="mb-3">
            <small class="text-light">Cardholder</small>
            <div id="cardholder-name" class="fw-bold">URBAN THREAD MEMBER</div>
          </div>
          
          <!-- Card Balance -->
          <div class="virtual-card-balance mb-4">
            <i class="bi bi-wallet2 me-2"></i>
            Available Balance: <span class="fw-bold fs-4" id="card-balance">Rs 0.00</span>
          </div>
          
          <!-- Action Buttons -->
          <div class="d-flex flex-wrap gap-2 mt-4">
            <button class="btn btn-dark flex-grow-1 btn-action" id="topup-btn">
              <i class="bi bi-plus-circle me-2"></i>Add Funds
            </button>
            <button class="btn btn-warning flex-grow-1 btn-action" id="redeem-btn" style="background-color: #ffc107; color: #000; font-weight: bold;">
              <i class="bi bi-gift me-2"></i>REDEEM POINTS
            </button>
            <button class="btn btn-outline-warning flex-grow-1 btn-action" id="transfer-btn">
              <i class="bi bi-arrow-left-right me-2"></i>Transfer
            </button>
          </div>
          
          <!-- Reward Points -->
          <div class="mt-4 pt-3 border-top border-light border-opacity-25">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <i class="bi bi-star-fill text-warning me-2"></i>
                <span>Reward Points:</span>
              </div>
              <span class="fw-bold fs-5" id="reward-points">0</span>
            </div>
            <small class="text-light opacity-75">100 points = Rs 5 credit</small>
          </div>
          
          <!-- Tier Badge -->
          <div class="mt-2">
            <span class="badge bg-secondary" id="card-tier">Silver</span>
          </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="row mt-4 g-3">
          <div class="col-6">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body text-center">
                <div class="text-primary">
                  <i class="bi bi-arrow-up-circle fs-2"></i>
                </div>
                <h5 class="mt-2" id="total-topups">Rs 0</h5>
                <small class="text-muted">Total Added</small>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body text-center">
                <div class="text-success">
                  <i class="bi bi-cash-coin fs-2"></i>
                </div>
                <h5 class="mt-2" id="total-saved">Rs 0</h5>
                <small class="text-muted">Total Saved</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Transaction History -->
      <div class="col-lg-6 col-md-8">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-0 py-3">
            <h5 class="fw-bold mb-0 d-flex align-items-center">
              <i class="bi bi-clock-history me-2"></i>Transaction History
            </h5>
            <div class="mt-2">
              <small class="text-muted">Showing last 20 transactions</small>
            </div>
          </div>
          <div class="card-body p-0">
            <div id="card-transactions" class="p-3">
              <div class="text-center py-5">
                <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3">Loading transactions...</p>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between">
              <button class="btn btn-sm btn-outline-secondary" id="export-btn">
                <i class="bi bi-download me-1"></i>Export
              </button>
              <button class="btn btn-sm btn-outline-primary" id="refresh-btn">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Info Section -->
    <div class="row mt-5">
      <div class="col-12">
        <div class="card border-0 bg-light">
          <div class="card-body">
            <h5 class="fw-bold mb-3">
              <i class="bi bi-info-circle me-2"></i>How It Works
            </h5>
            <div class="row">
              <div class="col-md-3 mb-3">
                <div class="d-flex align-items-center">
                  <div class="bg-primary text-white rounded-circle p-2 me-3">
                    <i class="bi bi-1-circle"></i>
                  </div>
                  <div>
                    <h6 class="mb-1">Shop & Earn</h6>
                    <small class="text-muted">Earn 10 points for every Rs 1 spent</small>
                  </div>
                </div>
              </div>
              <div class="col-md-3 mb-3">
                <div class="d-flex align-items-center">
                  <div class="bg-success text-white rounded-circle p-2 me-3">
                    <i class="bi bi-2-circle"></i>
                  </div>
                  <div>
                    <h6 class="mb-1">Redeem Points</h6>
                    <small class="text-muted">100 points = Rs 1 credit</small>
                  </div>
                </div>
              </div>
              <div class="col-md-3 mb-3">
                <div class="d-flex align-items-center">
                  <div class="bg-warning text-white rounded-circle p-2 me-3">
                    <i class="bi bi-3-circle"></i>
                  </div>
                  <div>
                    <h6 class="mb-1">Exclusive Offers</h6>
                    <small class="text-muted">Special discounts for cardholders</small>
                  </div>
                </div>
              </div>
              <div class="col-md-3 mb-3">
                <div class="d-flex align-items-center">
                  <div class="bg-info text-white rounded-circle p-2 me-3">
                    <i class="bi bi-4-circle"></i>
                  </div>
                  <div>
                    <h6 class="mb-1">No Expiry</h6>
                    <small class="text-muted">Points never expire</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- FOOTER - IDENTICAL TO INDEX.PHP -->
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
  <button id="btn-top" class="btn-floating-top">
    <i class="bi bi-arrow-up"></i>
  </button>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/common.js"></script>
  <script src="js/authNavbar.js"></script>
  <script src="js/card.js"></script>

  <!-- Main Functions - This will override any conflicts -->
  <script>
    // Function to load and display card data
    async function loadCardDisplay() {
        try {
            const response = await fetch('/urban-thread/api/card/get.php', {
                credentials: 'include'
            });
            const data = await response.json();
            
            if (data.success && data.card) {
                // Update balance
                const balanceEl = document.getElementById('card-balance');
                if (balanceEl) balanceEl.textContent = `Rs ${data.card.balance.toLocaleString()}`;
                
                // Update points
                const pointsEl = document.getElementById('reward-points');
                if (pointsEl) pointsEl.textContent = data.card.points.toLocaleString();
                
                // Update card number
                const cardNumberEl = document.getElementById('card-number');
                if (cardNumberEl && data.card.card_number) {
                    const formatted = data.card.card_number.replace(/(\d{4})/g, '$1 ').trim();
                    cardNumberEl.textContent = formatted;
                }
                
                // Update stats
                if (data.stats) {
                    const totalTopups = document.getElementById('total-topups');
                    const totalSaved = document.getElementById('total-saved');
                    if (totalTopups) totalTopups.textContent = `Rs ${(data.stats.total_added || 0).toLocaleString()}`;
                    if (totalSaved) totalSaved.textContent = `Rs ${(data.stats.total_redeemed || 0).toLocaleString()}`;
                }
                
                // Update transactions
                const transactionsContainer = document.getElementById('card-transactions');
                if (transactionsContainer && data.transactions && data.transactions.length > 0) {
                    let html = '';
                    data.transactions.slice(0, 10).forEach(tx => {
                        const isPositive = tx.type === 'Top Up' || tx.type === 'Welcome Bonus' || tx.type === 'Points Redemption';
                        const sign = isPositive ? '+' : '-';
                        const amountClass = isPositive ? 'text-success' : 'text-danger';
                        const date = new Date(tx.created_at).toLocaleDateString();
                        html += `
                            <div class="card-transaction border-bottom p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 fw-bold">${tx.type}</h6>
                                        <small class="text-muted">${tx.description || ''}</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold ${amountClass}">${sign} Rs ${Math.abs(tx.amount).toLocaleString()}</div>
                                        <small class="text-muted">${date}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    transactionsContainer.innerHTML = html;
                }
            }
        } catch (error) {
            console.error('Error loading card:', error);
        }
    }
    
    // Redeem points function
    async function redeemPoints() {
        console.log('Redeem button clicked!');
        
        const btn = document.getElementById('redeem-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        btn.disabled = true;
        
        try {
            // Get current card data
            const getResponse = await fetch('/urban-thread/api/card/get.php', {
                credentials: 'include'
            });
            const getData = await getResponse.json();
            
            if (!getData.success || !getData.card) {
                alert('Failed to load card data');
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }
            
            const points = getData.card.points;
            
            if (points < 100) {
                alert(`You need at least 100 points. You have ${points} points.`);
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }
            
            const maxRedeem = Math.floor(points / 100) * 100;
            const value = (maxRedeem / 100) * 5;
            
            const confirmed = confirm(`━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                                      `    REDEEM POINTS\n` +
                                      `━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                                      `Points: ${points.toLocaleString()}\n` +
                                      `Redeem: ${maxRedeem.toLocaleString()} points\n` +
                                      `Get: Rs ${value.toLocaleString()}\n` +
                                      `━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                                      `Proceed?`);
            
            if (!confirmed) {
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }
            
            // Call redeem API
            const redeemResponse = await fetch('/urban-thread/api/card/redeem.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ points: maxRedeem })
            });
            
            const redeemData = await redeemResponse.json();
            
            if (redeemData.success) {
                alert(`✓ SUCCESS!\n\nRedeemed ${maxRedeem.toLocaleString()} points\nGot Rs ${value.toLocaleString()}`);
                location.reload();
            } else {
                alert(`Error: ${redeemData.message}`);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        } catch (error) {
            console.error('Redeem error:', error);
            alert('Error: ' + error.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
    
    // Top up function
    async function topUp() {
        const amount = prompt('Enter amount to add (minimum Rs 10, maximum Rs 50,000):', '50');
        if (!amount) return;
        
        const parsedAmount = parseFloat(amount);
        if (isNaN(parsedAmount) || parsedAmount < 10 || parsedAmount > 50000) {
            alert('Please enter an amount between Rs 10 and Rs 50,000');
            return;
        }
        
        const btn = document.getElementById('topup-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        btn.disabled = true;
        
        try {
            const response = await fetch('/urban-thread/api/card/topup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ amount: parsedAmount })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(`Success! Added Rs ${parsedAmount.toLocaleString()} to your card.`);
                location.reload();
            } else {
                alert(`Error: ${data.message}`);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        } catch (error) {
            alert('Error: ' + error.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
    
    // Export transactions
    async function exportTransactions() {
        try {
            const response = await fetch('/urban-thread/api/card/get.php', {
                credentials: 'include'
            });
            const data = await response.json();
            
            if (data.success && data.transactions && data.transactions.length > 0) {
                let csv = 'Date,Type,Description,Amount,Status\n';
                data.transactions.forEach(tx => {
                    const date = new Date(tx.created_at).toLocaleDateString();
                    const amount = `Rs ${Math.abs(tx.amount).toLocaleString()}`;
                    csv += `"${date}","${tx.type}","${tx.description || ''}","${amount}","${tx.status}"\n`;
                });
                
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `urban-thread-transactions-${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                alert('Transactions exported successfully!');
            } else {
                alert('No transactions to export');
            }
        } catch (error) {
            alert('Error exporting transactions');
        }
    }
    
    // Setup all buttons when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Load card data
        loadCardDisplay();
        
        // Setup redeem button
        const redeemBtn = document.getElementById('redeem-btn');
        if (redeemBtn) {
            redeemBtn.onclick = function(e) {
                e.preventDefault();
                redeemPoints();
            };
            console.log('Redeem button ready');
        }
        
        // Setup topup button
        const topupBtn = document.getElementById('topup-btn');
        if (topupBtn) {
            topupBtn.onclick = function(e) {
                e.preventDefault();
                topUp();
            };
        }
        
        // Setup transfer button
        const transferBtn = document.getElementById('transfer-btn');
        if (transferBtn) {
            transferBtn.onclick = function(e) {
                e.preventDefault();
                alert('Transfer feature coming soon!');
            };
        }
        
        // Setup export button
        const exportBtn = document.getElementById('export-btn');
        if (exportBtn) {
            exportBtn.onclick = function(e) {
                e.preventDefault();
                exportTransactions();
            };
        }
        
        // Setup refresh button
        const refreshBtn = document.getElementById('refresh-btn');
        if (refreshBtn) {
            refreshBtn.onclick = function(e) {
                e.preventDefault();
                location.reload();
            };
        }
        
        // Update cart count
        fetch('/urban-thread/api/cart/get.php', { credentials: 'include' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const totalItems = data.cart.reduce((sum, item) => sum + item.quantity, 0);
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) cartCount.textContent = totalItems;
                }
            });
    });
  </script>
</body>
</html>