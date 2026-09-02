// js/reg.js - Premium Authentication with Backend Integration
// BACKEND CLUE: This file now makes API calls to real backend endpoints
// Database tables needed: users

// Escape user-controlled strings before inserting into HTML (XSS defense)
function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

class PremiumAuth {
  constructor() {
    this.currentTheme = 'default';
    this.isLoginMode = true;
    this.themes = {
      default: {
        primary: '#4361ee',
        primaryLight: '#4895ef',
        primaryDark: '#3a0ca3',
        accent: '#f72585',
        success: '#4cc9f0'
      },
      sunset: {
        primary: '#ff6b6b',
        primaryLight: '#ff8e8e',
        primaryDark: '#ff4757',
        accent: '#ffa502',
        success: '#1dd1a1'
      },
      forest: {
        primary: '#2ecc71',
        primaryLight: '#4cd964',
        primaryDark: '#27ae60',
        accent: '#f39c12',
        success: '#3498db'
      },
      ocean: {
        primary: '#00cec9',
        primaryLight: '#81ecec',
        primaryDark: '#00b894',
        accent: '#fd79a8',
        success: '#0984e3'
      }
    };
    
    this.init();
  }
  
  init() {
    this.setupTheme();
    this.setupEventListeners();
    this.setupPasswordStrength();
    this.checkExistingUser();
    this.updateCartCount();
    this.checkUrlParams();
  }
  
  checkUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const message = urlParams.get('message');
    
    if (status === 'google_success') {
      this.showWelcomeToast('Successfully logged in with Google!');
      // Update navbar immediately
      if (window.updateNavbarAuth) {
        window.updateNavbarAuth();
      }
      setTimeout(() => {
        window.location.href = 'account.php';
      }, 2000);
    } else if (status === 'google_error') {
      this.showMessage(document.getElementById('authMessage'), 
        message || 'Google login failed. Please try again.', 'error');
    }
  }
  
  setupTheme() {
    // Set initial theme
    this.applyTheme('default');
    
    // Try to detect website theme from existing styles
    this.detectWebsiteTheme();
  }
  
  detectWebsiteTheme() {
    // This function would detect your website's theme colors
    // For now, we'll use the default theme
    console.log('Detecting website theme...');
    
    // You can implement actual theme detection here
    // Example: Check for CSS custom properties or computed styles
  }
  
  applyTheme(themeName) {
    const theme = this.themes[themeName];
    if (!theme) return;
    
    // Update CSS custom properties
    const root = document.documentElement;
    root.style.setProperty('--primary-color', theme.primary);
    root.style.setProperty('--primary-light', theme.primaryLight);
    root.style.setProperty('--primary-dark', theme.primaryDark);
    root.style.setProperty('--accent-color', theme.accent);
    root.style.setProperty('--success-color', theme.success);
    
    // Convert primary color to RGB for rgba usage
    const rgb = this.hexToRgb(theme.primary);
    root.style.setProperty('--primary-color-rgb', `${rgb.r}, ${rgb.g}, ${rgb.b}`);
    
    this.currentTheme = themeName;
  }
  
  hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
      r: parseInt(result[1], 16),
      g: parseInt(result[2], 16),
      b: parseInt(result[3], 16)
    } : { r: 67, g: 97, b: 238 };
  }
  
  setupEventListeners() {
    // Form submissions
    document.getElementById('login-form')?.addEventListener('submit', (e) => this.handleLogin(e));
    document.getElementById('register-form')?.addEventListener('submit', (e) => this.handleRegister(e));
    
    // Toggle between login/register
    document.querySelectorAll('.toggle-link').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const action = e.currentTarget.getAttribute('data-action');
        if (action === 'switch-to-register') {
          this.switchToRegister();
        } else {
          this.switchToLogin();
        }
      });
    });
    
    // Password visibility toggle
    document.getElementById('toggleLoginPassword')?.addEventListener('click', () => {
      this.togglePasswordVisibility('login-password', 'toggleLoginPassword');
    });
    
    document.getElementById('toggleRegPassword')?.addEventListener('click', () => {
      this.togglePasswordVisibility('reg-password', 'toggleRegPassword');
    });
  }
  
  setupPasswordStrength() {
    const passwordInput = document.getElementById('reg-password');
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('strength-text');
    
    if (passwordInput && strengthBar && strengthText) {
      passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        const strength = this.calculatePasswordStrength(password);
        
        // Update strength bar
        strengthBar.style.width = `${strength.score}%`;
        strengthBar.style.backgroundColor = strength.color;
        
        // Update text
        strengthText.textContent = strength.text;
        strengthText.style.color = strength.color;
      });
    }
  }
  
  calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    if (score === 0) return { score: 0, color: '#dc3545', text: 'Too weak' };
    if (score === 1) return { score: 25, color: '#dc3545', text: 'Weak' };
    if (score === 2) return { score: 50, color: '#ffc107', text: 'Fair' };
    if (score === 3) return { score: 75, color: '#28a745', text: 'Good' };
    return { score: 100, color: '#20c997', text: 'Strong' };
  }
  
  switchToRegister() {
    this.isLoginMode = false;
    document.getElementById('loginForm').classList.remove('active');
    document.getElementById('registerForm').classList.add('active');
    
    // Update mode indicators
    document.querySelectorAll('.mode-dot').forEach((dot, index) => {
      dot.classList.toggle('active', index === 1);
    });
    
    // Animate transition
    this.animateFormTransition('registerForm');
  }
  
  switchToLogin() {
    this.isLoginMode = true;
    document.getElementById('registerForm').classList.remove('active');
    document.getElementById('loginForm').classList.add('active');
    
    // Update mode indicators
    document.querySelectorAll('.mode-dot').forEach((dot, index) => {
      dot.classList.toggle('active', index === 0);
    });
    
    // Animate transition
    this.animateFormTransition('loginForm');
  }
  
  animateFormTransition(formId) {
    const form = document.getElementById(formId);
    form.style.animation = 'none';
    setTimeout(() => {
      form.style.animation = 'slideInUp 0.5s ease-out';
    }, 10);
  }
  
  togglePasswordVisibility(passwordFieldId, toggleButtonId) {
    const passwordField = document.getElementById(passwordFieldId);
    const toggleButton = document.getElementById(toggleButtonId);
    
    if (passwordField.type === 'password') {
      passwordField.type = 'text';
      toggleButton.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
      passwordField.type = 'password';
      toggleButton.innerHTML = '<i class="bi bi-eye"></i>';
    }
  }
  
  async handleLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    const remember = document.getElementById('rememberMe')?.checked || false;
    const btn = document.getElementById('login-btn');
    const messageEl = document.getElementById('authMessage');
    
    // Validation
    if (!email || !password) {
      this.showMessage(messageEl, 'Please fill in all fields', 'error');
      return;
    }
    
    if (!this.isValidEmail(email)) {
      this.showMessage(messageEl, 'Please enter a valid email address', 'error');
      return;
    }
    
    // Show loading state
    this.setButtonLoading(btn, true);
    
    try {
      const response = await fetch('/urban-thread/api/auth/login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: email,
          password: password,
          remember: remember
        })
      });
      
      const responseText = await response.text();
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error('Invalid JSON response from login API:', responseText);
        this.showMessage(messageEl, 'Server error. Please try again later.', 'error');
        this.setButtonLoading(btn, false, 'Login');
        return;
      }
      console.log('Login response:', data);
      
      if (data.success) {
        // Store user data in localStorage for UI purposes
        localStorage.setItem('currentUser', JSON.stringify(data.user));
        
        // *** IMPORTANT: Sync cart BEFORE redirect ***
        const localCart = JSON.parse(localStorage.getItem('cart')) || [];
        if (localCart.length > 0) {
          console.log('Syncing cart before redirect...', localCart.length, 'items');
          
          try {
            const syncResponse = await fetch('/urban-thread/api/cart/sync.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ local_cart: localCart })
            });
            
            const syncData = await syncResponse.json();
            console.log('Sync result:', syncData);
            
            if (syncData.success) {
              // Clear localStorage cart after successful sync
              localStorage.removeItem('cart');
              console.log('Cart synced successfully!');
              
              // *** UPDATE CART COUNT IN NAVBAR ***
              const cartCountElement = document.getElementById('cart-count');
              if (cartCountElement) {
                cartCountElement.textContent = syncData.cart_count || 0;
              }
            }
          } catch (syncError) {
            console.error('Sync error:', syncError);
          }
        } else {
          console.log('No local cart to sync');
        }
        
        this.showMessage(messageEl, data.message, 'success');
        
        // Show welcome toast
        this.showWelcomeToast(data.message);
        
        // Update navbar immediately to show profile
        if (window.updateNavbarAuth) {
          window.updateNavbarAuth();
        }
        
        // Redirect based on user role from API response
        setTimeout(() => {
          window.location.href = data.redirect;
        }, 1500);
      } else {
        this.showMessage(messageEl, data.message, 'error');
        this.shakeElement('login-form');
        this.setButtonLoading(btn, false, 'Login');
      }
    } catch (error) {
      console.error('Login error:', error);
      this.showMessage(messageEl, 'Connection error. Please try again.', 'error');
      this.setButtonLoading(btn, false, 'Login');
    }
  }
  
  async handleRegister(e) {
    e.preventDefault();
    
    const name = document.getElementById('reg-name').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const password = document.getElementById('reg-password').value;
    const confirmPassword = document.getElementById('reg-confirm-password').value;
    const terms = document.getElementById('reg-terms').checked;
    const btn = document.getElementById('register-btn');
    const messageEl = document.getElementById('authMessage');
    
    // Validation
    if (!name || !email || !password || !confirmPassword) {
      this.showMessage(messageEl, 'Please fill in all fields', 'error');
      return;
    }
    
    if (!this.isValidEmail(email)) {
      this.showMessage(messageEl, 'Please enter a valid email address', 'error');
      return;
    }
    
    if (password !== confirmPassword) {
      this.showMessage(messageEl, 'Passwords do not match', 'error');
      this.shakeElement('reg-password');
      this.shakeElement('reg-confirm-password');
      return;
    }
    
    if (!terms) {
      this.showMessage(messageEl, 'Please agree to the terms and conditions', 'error');
      return;
    }
    
    // Show loading state
    this.setButtonLoading(btn, true);
    
    try {
      const response = await fetch('/urban-thread/api/auth/register.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          name: name,
          email: email,
          password: password,
          confirm_password: confirmPassword,
          terms: terms
        })
      });
      
      const responseText = await response.text();
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error('Invalid JSON response from register API:', responseText);
        this.showMessage(messageEl, 'Server error. Please try again later.', 'error');
        this.setButtonLoading(btn, false, 'Sign Up');
        return;
      }
      console.log('Register response:', data);
      
      if (data.success) {
        // Store user data in localStorage for UI purposes
        localStorage.setItem('currentUser', JSON.stringify(data.user));
        
        this.showMessage(messageEl, data.message, 'success');
        
        // Show welcome toast
        this.showWelcomeToast(data.message);
        
        // Update navbar immediately to show profile
        if (window.updateNavbarAuth) {
          window.updateNavbarAuth();
        }
        
        // New users always go to account.php
        setTimeout(() => {
          window.location.href = data.redirect;
        }, 2000);
      } else {
        this.showMessage(messageEl, data.message, 'error');
        this.setButtonLoading(btn, false, 'Sign Up');
      }
    } catch (error) {
      console.error('Registration error:', error);
      this.showMessage(messageEl, 'Connection error. Please try again.', 'error');
      this.setButtonLoading(btn, false, 'Sign Up');
    }
  }
  
  setButtonLoading(button, isLoading, originalText = null) {
    if (isLoading) {
      button.disabled = true;
      button.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Processing...
      `;
    } else {
      button.disabled = false;
      if (originalText) {
        const span = button.querySelector('.btn-text');
        if (span) {
          span.textContent = originalText;
        } else {
          button.innerHTML = `<span class="btn-text">${originalText}</span> <i class="bi bi-arrow-right"></i>`;
        }
      }
    }
  }
  
  showMessage(element, text, type) {
    if (!element) return;
    
    element.textContent = text;
    element.className = `auth-message ${type}`;
    
    // Auto-hide after 5 seconds for success messages
    if (type === 'success') {
      setTimeout(() => {
        element.className = 'auth-message';
        element.textContent = '';
      }, 5000);
    }
  }
  
  showWelcomeToast(message) {
    const toast = document.createElement('div');
    toast.className = 'welcome-toast';
    toast.innerHTML = `
      <i class="bi bi-check-circle-fill" style="font-size: 1.5rem;"></i>
      <div>
        <strong>${escapeHtml(message)}</strong>
        <div style="font-size: 0.9rem; opacity: 0.9;">Redirecting to your account...</div>
      </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
      toast.remove();
    }, 4000);
  }
  
  shakeElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
      element.style.animation = 'shake 0.5s ease-in-out';
      setTimeout(() => {
        element.style.animation = '';
      }, 500);
    }
  }
  
  showSuccessAnimation() {
    const card = document.getElementById('authCard');
    const successOverlay = document.createElement('div');
    successOverlay.className = 'success-overlay';
    successOverlay.innerHTML = `
      <div style="
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius);
        animation: fadeIn 0.3s ease-out;
      ">
        <div style="text-align: center; color: white; padding: 20px;">
          <i class="bi bi-check-circle-fill" style="font-size: 4rem; margin-bottom: 20px;"></i>
          <h3 style="margin-bottom: 10px;">Success!</h3>
          <p>You're being redirected...</p>
        </div>
      </div>
    `;
    
    card.style.position = 'relative';
    card.appendChild(successOverlay);
    
    setTimeout(() => {
      if (successOverlay.parentNode) {
        successOverlay.remove();
        card.style.position = '';
      }
    }, 1500);
  }
  
  showWelcomeAnimation(name) {
    this.showWelcomeToast(`Welcome, ${name}!`);
  }
  
  async checkExistingUser() {
    try {
      const response = await fetch('/urban-thread/api/auth/check.php');
      const data = await response.json();
      
      if (data.logged_in) {
        // User is already logged in, update navbar and redirect based on role
        if (window.updateNavbarAuth) {
          window.updateNavbarAuth();
        }
        
        this.showWelcomeToast(`Welcome back, ${data.user.name}!`);
        
        // Redirect based on user role
        const redirectUrl = data.user.role === 'admin' ? 'admin.php' : 'account.php';
        
        setTimeout(() => {
          window.location.href = redirectUrl;
        }, 2000);
      } else {
        // Clear any stale localStorage data
        localStorage.removeItem('currentUser');
      }
    } catch (error) {
      console.error('Session check error:', error);
      // If session check fails, clear localStorage to be safe
      localStorage.removeItem('currentUser');
    }
  }
  
  updateCartCount() {
    // Try to get cart count from database API first
    fetch('/urban-thread/api/cart/get.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const totalItems = data.cart.reduce((sum, item) => sum + item.quantity, 0);
                const cartCount = document.getElementById('cart-count');
                if (cartCount) {
                    cartCount.textContent = totalItems;
                }
            } else {
                // Fallback to localStorage
                const cartData = JSON.parse(localStorage.getItem('cart')) || [];
                const cartCount = document.getElementById('cart-count');
                if (cartCount) {
                    cartCount.textContent = cartData.length;
                }
            }
        })
        .catch(() => {
            // Fallback to localStorage
            const cartData = JSON.parse(localStorage.getItem('cart')) || [];
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                cartCount.textContent = cartData.length;
            }
        });
  }
  
  isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }
  
  delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

// Google Sign-In callback function
window.handleGoogleCredentialResponse = async (response) => {
  const messageEl = document.getElementById('authMessage');
  
  try {
    const res = await fetch('/urban-thread/api/auth/google.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        credential: response.credential
      })
    });
    
    const data = await res.json();
    
    if (data.success) {
      // Store user data in localStorage for UI purposes
      localStorage.setItem('currentUser', JSON.stringify(data.user));
      
      // *** Sync cart for Google login ***
      const localCart = JSON.parse(localStorage.getItem('cart')) || [];
      if (localCart.length > 0) {
        console.log('Syncing cart for Google login...', localCart.length, 'items');
        
        try {
          const syncResponse = await fetch('/urban-thread/api/cart/sync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ local_cart: localCart })
          });
          
          const syncData = await syncResponse.json();
          console.log('Sync result:', syncData);
          
          if (syncData.success) {
            localStorage.removeItem('cart');
            console.log('Cart synced successfully for Google login!');
            
            // *** UPDATE CART COUNT IN NAVBAR ***
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
              cartCountElement.textContent = syncData.cart_count || 0;
            }
          }
        } catch (syncError) {
          console.error('Sync error:', syncError);
        }
      }
      
      // Show welcome toast with appropriate message
      const toast = document.createElement('div');
      toast.className = 'welcome-toast';
      
      // Determine redirect destination for display
      const redirectDestination = data.user.role === 'admin' ? 'Admin Panel' : 'your account';
      
      toast.innerHTML = `
        <i class="bi bi-google" style="font-size: 1.5rem;"></i>
        <div>
          <strong>${escapeHtml(data.message)}</strong>
          <div style="font-size: 0.9rem; opacity: 0.9;">Redirecting to ${escapeHtml(redirectDestination)}...</div>
        </div>
      `;
      document.body.appendChild(toast);
      
      // Update navbar immediately to show profile
      if (window.updateNavbarAuth) {
        window.updateNavbarAuth();
      }
      
      // Redirect based on user role from API response
      const redirectUrl = data.redirect || (data.user.role === 'admin' ? 'admin.php' : 'account.php');
      
      setTimeout(() => {
        toast.remove();
        window.location.href = redirectUrl;
      }, 2000);
    } else {
      alert(data.message || 'Google login failed');
    }
  } catch (error) {
    console.error('Google login error:', error);
    alert('Google login failed. Please try again.');
  }
};

// Initialize the authentication system
document.addEventListener('DOMContentLoaded', () => {
  // Add shake animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    .spinner-border {
      display: inline-block;
      width: 1rem;
      height: 1rem;
      vertical-align: text-bottom;
      border: 0.2em solid currentColor;
      border-right-color: transparent;
      border-radius: 50%;
      animation: spinner-border 0.75s linear infinite;
    }
    
    @keyframes spinner-border {
      to { transform: rotate(360deg); }
    }
  `;
  document.head.appendChild(style);
  
  // Initialize the premium auth system
  window.premiumAuth = new PremiumAuth();
});