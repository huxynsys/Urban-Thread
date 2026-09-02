// js/about.js - About page specific functionality
// BACKEND_CLUE: This file handles cart count and user navigation
// Database tables needed: cart, users

window.addEventListener('DOMContentLoaded', () => {
  updateCartCount();
});

// Utility for navbar cart count
function updateCartCount() {
  // BACKEND_CLUE: 
  // This data should come from database table: `cart`
  // Expected fields: id, user_id, product_id, quantity, created_at
  // AUTH_REQUIRED: For logged-in users, get cart from database
  // For guest users, continue using localStorage temporarily
  const cartData = JSON.parse(localStorage.getItem('cart')) || [];
  document.getElementById('cart-count').textContent = cartData.length;
}

// Navbar user state
document.getElementById('user-nav')?.addEventListener('click', function(e){
  // BACKEND_CLUE: 
  // This should check PHP session instead of localStorage
  // Table: users
  // Logic: if(isset($_SESSION['user_id'])) { redirect to account.php } else { redirect to reg.php }
  const user = JSON.parse(localStorage.getItem('currentUser'));
  if (user) {
    window.location.href = 'account.php';
  } else {
    window.location.href = 'reg.php';
  }
});