// js/contact.js - Contact page with database integration

document.addEventListener('DOMContentLoaded', () => {
    // Initialize common functionality
    if (typeof updateCartCount === 'function') updateCartCount();
    if (typeof updateNavbarAuth === 'function') updateNavbarAuth();
    if (typeof setupBackToTop === 'function') setupBackToTop();
    if (typeof setupChatBot === 'function') setupChatBot();
    
    // Contact form handling
    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactSubmit);
    }
    
    animateContactItems();
    setupFloatingLabels();
});

// Handle contact form submission
async function handleContactSubmit(e) {
    e.preventDefault();
    
    const name = document.getElementById('contact-name').value.trim();
    const email = document.getElementById('contact-email').value.trim();
    const subject = document.getElementById('contact-subject').value.trim();
    const message = document.getElementById('contact-message').value.trim();
    const msgDiv = document.getElementById('contact-msg');
    const submitBtn = document.querySelector('#contact-form button[type="submit"]');
    
    // Validation
    if (!name || !email || !subject || !message) {
        showMessage('Please fill in all fields.', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showMessage('Please enter a valid email address.', 'error');
        return;
    }
    
    if (message.length < 10) {
        showMessage('Message must be at least 10 characters.', 'error');
        return;
    }
    
    // Show loading state
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
    
    try {
        const response = await fetch('/urban-thread/api/contact/submit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email, subject, message })
        });
        
        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Invalid JSON response from contact API:', responseText);
            showMessage('Server error. Please try again later.', 'error');
            return;
        }
        
        if (data.success) {
            showMessage(data.message, 'success');
            document.getElementById('contact-form').reset();
        } else {
            showMessage(data.message || 'Server error. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Contact form error:', error);
        showMessage('Connection error. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Show message with animation
function showMessage(text, type) {
    const msgDiv = document.getElementById('contact-msg');
    if (!msgDiv) return;
    
    msgDiv.textContent = text;
    msgDiv.className = '';
    
    switch(type) {
        case 'success':
            msgDiv.classList.add('alert', 'alert-success');
            break;
        case 'error':
            msgDiv.classList.add('alert', 'alert-danger');
            break;
        case 'info':
            msgDiv.classList.add('alert', 'alert-info');
            break;
    }
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            msgDiv.textContent = '';
            msgDiv.className = '';
        }, 5000);
    }
}

// Email validation
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Animate contact items on scroll
function animateContactItems() {
    const items = document.querySelectorAll('.contact-info-item, .faq-item');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });
    
    items.forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(item);
    });
}

// Add floating label effect to form inputs
function setupFloatingLabels() {
    const inputs = document.querySelectorAll('.contact-input');
    
    inputs.forEach(input => {
        if (input.value) {
            input.parentElement.classList.add('has-value');
        }
        
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
            if (this.value) {
                this.parentElement.classList.add('has-value');
            } else {
                this.parentElement.classList.remove('has-value');
            }
        });
    });
}

// Make functions global
window.handleContactSubmit = handleContactSubmit;