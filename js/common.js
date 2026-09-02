// common.js - Common functionality for ALL pages
// BACKEND_CLUE: This entire file will be replaced by PHP + AJAX calls
// Database tables needed: users, products, cart

window.addEventListener('DOMContentLoaded', () => {
  // Setup common features for all pages
  setupCommonFeatures();
  
  // Update cart count
  updateCartCount();
});

// Setup all common features
function setupCommonFeatures() {
  // 1. Setup navbar auth state (login/profile dropdown)
  setupNavbarAuth();
  
  // 2. Setup search functionality
  setupSearchBar();
  
  // 3. Setup back to top button
  setupBackToTop();
  
  // 4. Setup chatbot
  setupChatBot();
  
  // 5. Setup footer admin link
  setupFooterAdminLink();
}

// 1. Navbar Auth State - Add login icon/profile dropdown
function setupNavbarAuth() {
  const authArea = document.getElementById('navbar-auth-area');
  if (!authArea) return;
  
  // BACKEND_CLUE: 
  // This should be replaced with PHP session check
  // Table: users
  // Logic: if(isset($_SESSION['user_id'])) { show profile } else { show login }
  const user = JSON.parse(localStorage.getItem('currentUser'));
  
  if (user) {
    // User is logged in - show profile dropdown
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
    
    // Add logout functionality
    document.getElementById('logout-nav')?.addEventListener('click', function(e) {
      e.preventDefault();
      // BACKEND_CLUE: This should call /api/auth/logout.php
      localStorage.removeItem('currentUser');
      window.location.reload();
    });
  } else {
    // User is not logged in - show login button
    authArea.innerHTML = `
      <a href="reg.php" class="btn btn-outline-dark d-flex align-items-center">
        <i class="bi bi-person me-1"></i>
        <span class="d-none d-md-inline">Login</span>
      </a>
    `;
  }
}

// 2. Search Bar functionality
function setupSearchBar() {
  const searchForm = document.getElementById('search-form');
  const searchInput = document.getElementById('search-input');
  const searchResults = document.getElementById('search-results');
  
  if (!searchForm || !searchInput || !searchResults) return;
  
  let productsCache = [];
  
  // BACKEND_CLUE: 
  // This fetch should be replaced with PHP API endpoint: /api/products/search.php
  // Table: products
  // Query: SELECT * FROM products WHERE name LIKE %search% OR category LIKE %search%
  fetch('data/products.json')
    .then(res => res.json())
    .then(data => {
      productsCache = data;
    })
    .catch(() => {
      // Fallback sample products
      productsCache = [
        {id:1, name:'Urban Hoodie', category:'Men', price:59, image:'img/hoodie.jpg'},
        {id:2, name:'Classic T-shirt', category:'Women', price:29, image:'img/tshirt.jpg'},
        {id:3, name:'Kids Denim Jacket', category:'Kids', price:45, image:'img/jacket.jpg'},
        {id:4, name:'Fashion Sneakers', category:'Men', price:120, image:'img/sneakers.jpg'}
      ];
    });
  
  // Search input event
  searchInput.addEventListener('input', function() {
    const val = this.value.trim().toLowerCase();
    if (!val || !productsCache.length) {
      searchResults.style.display = 'none';
      return;
    }
    
    const matches = productsCache.filter(p =>
      p.name.toLowerCase().includes(val) ||
      (p.category && p.category.toLowerCase().includes(val)) ||
      (p.description && p.description.toLowerCase().includes(val))
    ).slice(0, 6);
    
    if (matches.length === 0) {
      searchResults.innerHTML = '<div class="search-result-item text-center text-muted">No results found.</div>';
    } else {
      searchResults.innerHTML = matches.map(p =>
        `<div class="search-result-item" onclick="location.href='shop.php?search=${encodeURIComponent(p.name)}'">
          <img src="${p.image}" alt="${p.name}" style="width:34px;height:34px;object-fit:cover;border-radius:6px;margin-right:10px;">
          <span>${p.name} - Rs ${p.price}</span>
        </div>`
      ).join('');
    }
    
    searchResults.style.display = 'block';
  });
  
  // Hide results when clicking outside
  searchInput.addEventListener('blur', () => {
    setTimeout(() => {
      searchResults.style.display = 'none';
    }, 100);
  });
  
  // Search form submit
  searchForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const val = searchInput.value.trim();
    if (val) {
      window.location.href = `shop.php?search=${encodeURIComponent(val)}`;
    }
  });
}

// 3. Back to Top Button
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

// 4. ChatBot Setup
function setupChatBot() {
  const chatbotBtn = document.getElementById('chatbot-btn');
  const chatbotModal = document.getElementById('chatbot-modal');
  
  if (!chatbotBtn || !chatbotModal) return;
  
  const modalInstance = new bootstrap.Modal(chatbotModal);
  const chatForm = document.getElementById('chat-form');
  const chatInput = document.getElementById('chat-input');
  const chatMessages = document.getElementById('chat-messages');
  
  // Open chatbot modal
  chatbotBtn.addEventListener('click', () => modalInstance.show());
  
  // Handle chat form submission
  if (chatForm) {
    chatForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const userMsg = chatInput.value.trim();
      if (!userMsg) return;
      
      appendChat('user', userMsg);
      chatInput.value = '';
      chatInput.disabled = true;
      appendChat('ai', 'Thinking...');
      
      try {
        // BACKEND_CLUE: 
        // This should be replaced with PHP API endpoint: /api/chat.php
        // Table: chat_logs (optional for storing conversations)
        // POST data: { message: userMsg }
        const reply = await getAIReply(userMsg);
        chatMessages.lastChild.textContent = reply || "I'm here to help! Ask me anything about Urban Thread.";
      } catch (err) {
        chatMessages.lastChild.textContent = "I'm here to help! Ask me anything about Urban Thread.";
      }
      
      chatInput.disabled = false;
      chatInput.focus();
      chatMessages.scrollTop = chatMessages.scrollHeight;
    });
  }
  
  // Append chat message
  function appendChat(sender, msg) {
    const div = document.createElement('div');
    div.className = `chat-msg chat-msg-${sender}`;
    div.textContent = msg;
    if (chatMessages) {
      chatMessages.appendChild(div);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
  }
  
  // Get AI reply
  async function getAIReply(msg) {
    try {
      const response = await fetch("https://api-inference.huggingface.co/models/microsoft/DialoGPT-medium", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ inputs: msg })
      });
      const data = await response.json();
      if (data && typeof data.generated_text === "string" && data.generated_text.trim()) {
        return data.generated_text;
      }
    } catch (err) {
      console.error("Chatbot error:", err);
    }
    return "I'm here to help! Ask me anything about Urban Thread.";
  }
}

// 5. Show admin link in footer for admin users
async function setupFooterAdminLink() {
  const footerAdminLink = document.getElementById('footer-admin-link');
  if (!footerAdminLink) return;
  
  try {
    // Check user role from PHP session
    const response = await fetch('/urban-thread/api/auth/check.php', {
      credentials: 'include'
    });
    const data = await response.json();
    
    if (data.logged_in && data.user && data.user.role === 'admin') {
      footerAdminLink.style.display = '';
    } else {
      footerAdminLink.style.display = 'none';
    }
  } catch (error) {
    console.error('Error checking admin status:', error);
    // Fallback to localStorage
    const user = JSON.parse(localStorage.getItem('currentUser'));
    if (user && user.role === 'admin') {
      footerAdminLink.style.display = '';
    } else {
      footerAdminLink.style.display = 'none';
    }
  }
}

// Update cart count (available globally)
async function updateCartCount() {
  const cartCount = document.getElementById('cart-count');
  const mobileCartCount = document.querySelector('.mobile-cart-count');
  if (!cartCount && !mobileCartCount) return;
  
  try {
    // Try to get cart from database for logged-in users
    const response = await fetch('/urban-thread/api/cart/get.php', {
      credentials: 'include'
    });
    const data = await response.json();
    
    if (data.success && data.cart) {
      const totalItems = data.cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
      if (cartCount) cartCount.textContent = totalItems;
      if (mobileCartCount) mobileCartCount.textContent = totalItems;
    } else {
      // Fall back to localStorage
      const cartData = JSON.parse(localStorage.getItem('cart')) || [];
      if (cartCount) cartCount.textContent = cartData.length;
      if (mobileCartCount) mobileCartCount.textContent = cartData.length;
    }
  } catch (error) {
    // Fall back to localStorage
    const cartData = JSON.parse(localStorage.getItem('cart')) || [];
    if (cartCount) cartCount.textContent = cartData.length;
    if (mobileCartCount) mobileCartCount.textContent = cartData.length;
  }
}

// Make functions available globally
window.updateCartCount = updateCartCount;