// js/authNavbar.js - Handles navbar auth state with cart sync
// BACKEND CLUE: This file checks PHP session via API to show profile or login button
// Database tables needed: users (for role information)

// Escape user-controlled strings before inserting into HTML (XSS defense)
function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

async function updateNavbarAuth() {
  const authArea = document.getElementById('navbar-auth-area');
  if (!authArea) return;
  
  try {
    // BACKEND CLUE: This calls the check.php API to verify session
    // The API returns user data if logged in, or logged_in=false if not
    const response = await fetch('/urban-thread/api/auth/check.php');
    const data = await response.json();
    
    // Store previous login state to detect login event
    const wasLoggedIn = localStorage.getItem('wasLoggedIn') === 'true';
    const isNowLoggedIn = data.logged_in;
    
    if (data.logged_in && data.user) {
      // User is logged in - show profile dropdown
      const firstName = data.user.name.split(' ')[0];
      const isAdmin = data.user.role === 'admin';
      
      authArea.innerHTML = `
        <div class="dropdown">
          <button class="btn btn-outline-dark dropdown-toggle d-flex align-items-center" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-1"></i>
            <span class="d-none d-md-inline">${escapeHtml(firstName)}</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
            <li><a class="dropdown-item" href="account.php"><i class="bi bi-person me-2"></i>My Account</a></li>
            <li><a class="dropdown-item" href="card.php"><i class="bi bi-credit-card me-2"></i>Virtual Card</a></li>
            <li><a class="dropdown-item" href="shop.php"><i class="bi bi-shop me-2"></i>Shop</a></li>
            ${isAdmin ? '<li><a class="dropdown-item" href="admin.php"><i class="bi bi-gear me-2"></i>Admin Panel</a></li>' : ''}
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" id="logout-nav"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
      `;
      
      // Sync cart when user just logged in (detect login event)
      if (!wasLoggedIn && isNowLoggedIn) {
        console.log('User just logged in - syncing cart...');
        await syncCartAfterLogin();
      }
      
      // Store current login state
      localStorage.setItem('wasLoggedIn', 'true');
      localStorage.setItem('currentUser', JSON.stringify(data.user));
      
      // Add logout functionality
      document.getElementById('logout-nav')?.addEventListener('click', async function(e) {
        e.preventDefault();
        
        try {
          // BACKEND CLUE: Call logout API to destroy session
          await fetch('/urban-thread/api/auth/logout.php', { method: 'POST' });
          localStorage.removeItem('currentUser');
          localStorage.removeItem('wasLoggedIn');
          window.location.reload();
        } catch (error) {
          console.error('Logout error:', error);
          window.location.reload();
        }
      });
    } else {
      // User is not logged in - show login button
      authArea.innerHTML = `
        <a href="reg.php" class="btn btn-outline-dark d-flex align-items-center">
          <i class="bi bi-person me-1"></i>
          <span class="d-none d-md-inline">Login</span>
        </a>
      `;
      
      // Store login state
      localStorage.setItem('wasLoggedIn', 'false');
    }
  } catch (error) {
    console.error('Error checking auth status:', error);
    // Fallback to login button on error
    authArea.innerHTML = `
      <a href="reg.php" class="btn btn-outline-dark d-flex align-items-center">
        <i class="bi bi-person me-1"></i>
        <span class="d-none d-md-inline">Login</span>
      </a>
    `;
  }
}

/**
 * Sync cart from localStorage to database after login
 * This merges guest cart items with the user's existing cart
 */
async function syncCartAfterLogin() {
  try {
    // Get cart from localStorage
    const localCart = JSON.parse(localStorage.getItem('cart')) || [];
    
    if (localCart.length === 0) {
      console.log('No local cart to sync');
      return;
    }
    
    console.log(`Syncing ${localCart.length} items to database...`);
    
    // Send to sync API
    const response = await fetch('/urban-thread/api/cart/sync.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ local_cart: localCart })
    });
    
    const data = await response.json();
    
    if (data.success) {
      console.log('Cart synced successfully!');
      
      // Clear localStorage cart after successful sync
      localStorage.removeItem('cart');
      
      // Update cart count in UI
      if (typeof updateCartCount === 'function') {
        updateCartCount();
      } else if (window.shoppingCart && typeof window.shoppingCart.loadCart === 'function') {
        await window.shoppingCart.loadCart();
      }
      
      // Show success message
      showCartSyncNotification(`Cart synced! ${data.cart_count} items in your cart.`);
    } else {
      console.error('Failed to sync cart:', data.message);
    }
  } catch (error) {
    console.error('Error syncing cart:', error);
  }
}

/**
 * Show notification for cart sync
 */
function showCartSyncNotification(message) {
  const toast = document.createElement('div');
  toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
  toast.style.cssText = `
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 9999;
    min-width: 280px;
    max-width: 350px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    border-radius: 10px;
    animation: slideInRight 0.3s ease;
  `;
  
  toast.innerHTML = `
    <div class="d-flex align-items-center">
      <i class="bi bi-cart-check-fill me-2 fs-4 text-success"></i>
      <div class="flex-grow-1">
        <strong>Cart Synced!</strong>
        <div class="small">${message}</div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  `;
  
  document.body.appendChild(toast);
  
  // Auto remove after 4 seconds
  setTimeout(() => {
    if (toast.parentNode) {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 300);
    }
  }, 4000);
}

/**
 * Clear cart sync flag (call this on logout)
 */
function clearCartSyncFlag() {
  localStorage.removeItem('wasLoggedIn');
}

// Initialize when DOM loads
document.addEventListener('DOMContentLoaded', () => {
  updateNavbarAuth();
  
  // Listen for storage events (for cross-tab sync)
  window.addEventListener('storage', (e) => {
    if (e.key === 'currentUser') {
      updateNavbarAuth();
    }
  });
});

// Add CSS animation if not present
if (!document.querySelector('#cart-sync-styles')) {
  const style = document.createElement('style');
  style.id = 'cart-sync-styles';
  style.textContent = `
    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(100%);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
  `;
  document.head.appendChild(style);
}

// Export for use in other files
window.updateNavbarAuth = updateNavbarAuth;
window.syncCartAfterLogin = syncCartAfterLogin;
window.clearCartSyncFlag = clearCartSyncFlag;