// js/returns.js - Returns page specific functionality ONLY
// BACKEND_CLUE: This file handles return page animations and future form submissions
// Database tables needed: return_requests, users, orders

window.addEventListener('DOMContentLoaded', () => {
  // Initialize common functionality
  if (typeof updateCartCount === 'function') {
    updateCartCount();
  }
  
  if (typeof updateNavbarAuth === 'function') {
    updateNavbarAuth();
  }
  
  if (typeof setupBackToTop === 'function') {
    setupBackToTop();
  }
  
  if (typeof setupChatBot === 'function') {
    setupChatBot();
  }
  
  // Initialize returns page functionality
  setupReturnProcessAnimation();
  setupFAQAccordion();
  setupContactButtons();
});

// Animate return process steps on scroll
function setupReturnProcessAnimation() {
  const processSteps = document.querySelectorAll('.process-step');
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        // Delay each step's animation for a staggered effect
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 200);
      }
    });
  }, { threshold: 0.1 });
  
  processSteps.forEach(step => {
    step.style.opacity = '0';
    step.style.transform = 'translateY(20px)';
    step.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    observer.observe(step);
  });
}

// Setup FAQ accordion for return questions
function setupFAQAccordion() {
  const faqItems = document.querySelectorAll('.faq-item');
  
  faqItems.forEach(item => {
    item.style.cursor = 'pointer';
    item.style.transition = 'all 0.3s ease';
    
    item.addEventListener('click', function() {
      // Toggle active state
      faqItems.forEach(f => {
        if (f !== this) {
          f.style.boxShadow = 'none';
          f.style.borderLeftColor = '#ff6b6b';
        }
      });
      
      if (this.style.boxShadow === 'none' || !this.style.boxShadow) {
        this.style.boxShadow = '0 10px 20px rgba(255, 107, 107, 0.2)';
        this.style.borderLeftColor = '#ff4757';
      } else {
        this.style.boxShadow = 'none';
        this.style.borderLeftColor = '#ff6b6b';
      }
    });
  });
}

// Setup contact buttons with animations
function setupContactButtons() {
  const contactButtons = document.querySelectorAll('.btn-outline-dark, .btn-link');
  
  contactButtons.forEach(button => {
    button.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-2px)';
      this.style.transition = 'transform 0.3s ease';
    });
    
    button.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
    });
  });
}

// Return policy calculator (if user wants to check eligibility)
function checkReturnEligibility() {
  // BACKEND_CLUE: 
  // This should check against actual order data from database
  // Table: orders
  // Query: SELECT delivery_date FROM orders WHERE order_id = ? AND user_id = ?
  const purchaseDate = new Date(); // This would come from user input or order data
  const returnWindow = 14; // 14 days
  const today = new Date();
  const daysSincePurchase = Math.floor((today - purchaseDate) / (1000 * 60 * 60 * 24));
  
  return daysSincePurchase <= returnWindow;
}

// Simulate return request submission
function submitReturnRequest(formData) {
  // BACKEND_CLUE: 
  // This should send data to PHP API endpoint: /api/returns/submit.php
  // Table: return_requests
  // Fields: id, order_id, user_id, reason, status, created_at
  // POST data: { order_id, reason, items, comments }
  // AUTH_REQUIRED: Yes (user must be logged in)
  // Validation: order belongs to user, within return window
  
  console.log('Return request submitted:', formData);
  
  // Store in localStorage for demo purposes
  const returnRequests = JSON.parse(localStorage.getItem('returnRequests')) || [];
  returnRequests.push({
    id: Date.now(),
    ...formData,
    date: new Date().toISOString(),
    status: 'pending'
  });
  
  localStorage.setItem('returnRequests', JSON.stringify(returnRequests));
  
  return {
    success: true,
    message: 'Return request submitted successfully. Our admin will contact you within 24 hours.',
    requestId: Date.now()
  };
}

// Display return status message
function showReturnStatus(message, type = 'info') {
  // Create a notification element
  const notification = document.createElement('div');
  notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
  notification.style.cssText = `
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-radius: 10px;
  `;
  
  notification.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  
  document.body.appendChild(notification);
  
  // Auto-remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, 5000);
}

// Initialize tooltips for return information
function initializeReturnTooltips() {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
}

// Add example tooltips to important information
function addReturnTooltips() {
  // Example: Add tooltip to the 14-day policy
  const policyElement = document.querySelector('.policy-overview-card h2');
  if (policyElement && !policyElement.hasAttribute('data-bs-toggle')) {
    policyElement.setAttribute('data-bs-toggle', 'tooltip');
    policyElement.setAttribute('data-bs-placement', 'top');
    policyElement.setAttribute('title', '14 days from delivery date. Contact us immediately if you receive a wrong/damaged item.');
    
    // Re-initialize tooltips
    initializeReturnTooltips();
  }
}

// Initialize everything when page loads
document.addEventListener('DOMContentLoaded', function() {
  // Add tooltips after a short delay to ensure elements are rendered
  setTimeout(addReturnTooltips, 1000);
});

// Make functions available globally if needed
window.checkReturnEligibility = checkReturnEligibility;
window.submitReturnRequest = submitReturnRequest;
window.showReturnStatus = showReturnStatus;