// js/faq.js - FAQ page specific functionality ONLY
// BACKEND_CLUE: This file handles FAQ search and accordion interactions
// Database tables needed: faq_categories, faq_items (for future dynamic content)

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
  
  // Initialize FAQ functionality
  setupFAQSearch();
  setupAccordionAnimations();
  highlightCurrentCategory();
});

// FAQ Search Functionality
function setupFAQSearch() {
  const searchInput = document.getElementById('faq-search');
  const searchBtn = document.getElementById('faq-search-btn');
  const resultsContainer = document.getElementById('faq-search-results');
  
  if (!searchInput || !searchBtn) return;
  
  // Search when button is clicked
  searchBtn.addEventListener('click', performSearch);
  
  // Search when Enter key is pressed
  searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      performSearch();
    }
  });
  
  // Clear results when input is cleared
  searchInput.addEventListener('input', function() {
    if (this.value.trim() === '') {
      clearSearchResults();
    }
  });
  
  function performSearch() {
    const searchTerm = searchInput.value.trim().toLowerCase();
    
    if (!searchTerm) {
      showSearchMessage('Please enter a search term.', 'info');
      return;
    }
    
    clearSearchResults();
    showSearchMessage('Searching...', 'info');
    
    // Simulate search delay
    setTimeout(() => {
      const results = searchFAQ(searchTerm);
      displaySearchResults(results, searchTerm);
    }, 300);
  }
  
  function searchFAQ(term) {
    const faqItems = document.querySelectorAll('.accordion-item');
    const results = [];
    
    faqItems.forEach((item, index) => {
      const question = item.querySelector('.accordion-button').textContent;
      const answer = item.querySelector('.accordion-body').textContent;
      const category = item.closest('.faq-category').querySelector('.category-title').textContent;
      
      // Check if search term is in question or answer
      if (question.toLowerCase().includes(term) || answer.toLowerCase().includes(term)) {
        results.push({
          id: index,
          question,
          answer: answer.substring(0, 150) + '...', // Truncate answer
          category,
          element: item
        });
      }
    });
    
    return results;
  }
  
  function displaySearchResults(results, searchTerm) {
    clearSearchResults();
    
    if (results.length === 0) {
      showSearchMessage('No results found. Try different keywords.', 'warning');
      return;
    }
    
    // Show number of results
    const resultsHeader = document.createElement('div');
    resultsHeader.className = 'alert alert-info mb-3';
    resultsHeader.innerHTML = `<strong>${results.length}</strong> results found for "<strong>${searchTerm}</strong>"`;
    resultsContainer.appendChild(resultsHeader);
    
    // Display each result
    results.forEach(result => {
      const resultItem = document.createElement('div');
      resultItem.className = 'search-result-item';
      resultItem.innerHTML = `
        <h5 class="fw-bold mb-2">${result.question}</h5>
        <p class="mb-2 text-muted">${result.answer}</p>
        <small class="text-primary"><strong>Category:</strong> ${result.category}</small>
        <button class="btn btn-link btn-sm p-0 mt-2 view-faq-btn" data-faq-id="${result.id}">
          View Full Answer →
        </button>
      `;
      resultsContainer.appendChild(resultItem);
    });
    
    // Add click event to view buttons
    document.querySelectorAll('.view-faq-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const faqId = this.getAttribute('data-faq-id');
        const faqItem = document.querySelectorAll('.accordion-item')[faqId];
        
        if (faqItem) {
          // Scroll to the FAQ item
          faqItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
          
          // Open the accordion if it's closed
          const button = faqItem.querySelector('.accordion-button');
          if (button.classList.contains('collapsed')) {
            button.click();
          }
          
          // Highlight the item temporarily
          faqItem.style.backgroundColor = '#fff8e1';
          faqItem.style.boxShadow = '0 0 0 3px rgba(255, 193, 7, 0.3)';
          
          setTimeout(() => {
            faqItem.style.backgroundColor = '';
            faqItem.style.boxShadow = '';
          }, 2000);
        }
      });
    });
  }
  
  function showSearchMessage(message, type) {
    clearSearchResults();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    resultsContainer.appendChild(alert);
  }
  
  function clearSearchResults() {
    if (resultsContainer) {
      resultsContainer.innerHTML = '';
    }
  }
}

// Accordion Animations
function setupAccordionAnimations() {
  const accordionButtons = document.querySelectorAll('.accordion-button');
  
  accordionButtons.forEach(button => {
    button.addEventListener('click', function() {
      const accordionItem = this.closest('.accordion-item');
      
      // Add animation class
      accordionItem.style.transition = 'all 0.3s ease';
      
      // Highlight active item
      document.querySelectorAll('.accordion-item').forEach(item => {
        if (item !== accordionItem) {
          item.style.borderColor = '#e9ecef';
          item.style.boxShadow = 'none';
        }
      });
      
      if (!this.classList.contains('collapsed')) {
        accordionItem.style.borderColor = '#00b09b';
        accordionItem.style.boxShadow = '0 5px 20px rgba(0, 176, 155, 0.15)';
      }
    });
  });
}

// Highlight current category based on URL hash
function highlightCurrentCategory() {
  const hash = window.location.hash;
  if (hash) {
    const targetElement = document.querySelector(hash);
    if (targetElement) {
      setTimeout(() => {
        targetElement.scrollIntoView({ behavior: 'smooth' });
        
        // Open the accordion if it's closed
        const button = targetElement.querySelector('.accordion-button');
        if (button && button.classList.contains('collapsed')) {
          button.click();
        }
      }, 300);
    }
  }
}

// Add FAQ analytics (optional - tracks which FAQs are opened)
function setupFAQAnalytics() {
  const accordionButtons = document.querySelectorAll('.accordion-button');
  
  accordionButtons.forEach(button => {
    button.addEventListener('click', function() {
      const question = this.textContent.trim();
      
      // BACKEND_CLUE: 
      // This analytics data can be sent to database table: `faq_analytics`
      // Fields: id, faq_id, question, viewed_at, user_id (if logged in)
      // This would help identify most frequently asked questions
      
      // Store in localStorage (for demo purposes)
      const faqStats = JSON.parse(localStorage.getItem('faqStats')) || {};
      faqStats[question] = (faqStats[question] || 0) + 1;
      localStorage.setItem('faqStats', JSON.stringify(faqStats));
      
      console.log(`FAQ viewed: ${question} (Total views: ${faqStats[question]})`);
    });
  });
}

// Initialize FAQ analytics
document.addEventListener('DOMContentLoaded', setupFAQAnalytics);