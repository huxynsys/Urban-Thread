// js/product.js - Product page with database wishlist integration

let currentProduct = null;
let currentQuantity = 1;
let wishlistItems = [];

// Get product ID from URL
function getProductId() {
    const urlParams = new URLSearchParams(window.location.search);
    const id = parseInt(urlParams.get('id'), 10);
    return isNaN(id) ? null : id;
}

// Load wishlist from database
async function loadWishlist() {
    try {
        const response = await fetch('/urban-thread/api/wishlist/get.php', {
            credentials: 'include'
        });
        const data = await response.json();
        if (data.success && data.wishlist) {
            wishlistItems = data.wishlist.map(item => item.id);
        }
    } catch (error) {
        console.error('Error loading wishlist:', error);
        wishlistItems = JSON.parse(localStorage.getItem('wishlist')) || [];
    }
}

// Load product from database
async function loadProductFromDatabase(productId) {
    try {
        showLoading(true);
        
        const response = await fetch(`/urban-thread/api/products/single.php?id=${productId}`);
        const data = await response.json();
        
        if (data.success && data.product) {
            currentProduct = data.product;
            await loadWishlist(); // Load wishlist before rendering
            renderProductPage(currentProduct);
        } else {
            showError(data.message || 'Product not found');
        }
    } catch (error) {
        console.error('Error loading product:', error);
        showError('Failed to load product. Please try again.');
    } finally {
        showLoading(false);
    }
}

// Render full product page
function renderProductPage(product) {
    const breadcrumb = document.getElementById('product-breadcrumb');
    if (breadcrumb) breadcrumb.textContent = product.name;
    
    renderProductGallery(product);
    renderProductInfo(product);
    renderProductDetails(product);
    renderReviews(product);
    renderSameCategoryProducts(product.same_category || []);
    renderComplementaryProducts(product.complementary || []);
    
    setupEventListeners();
}

// Render product gallery
function renderProductGallery(product) {
    const mainImage = document.getElementById('main-product-image');
    const thumbnailRow = document.querySelector('.thumbnail-row');
    
    if (!mainImage) return;
    
    mainImage.src = product.image;
    mainImage.alt = product.name;
    mainImage.onerror = function() { this.src = 'img/placeholder.jpg'; };
    
    if (thumbnailRow && product.images && product.images.length > 1) {
        thumbnailRow.innerHTML = '';
        product.images.forEach(img => {
            const thumb = document.createElement('img');
            thumb.src = img.image_url;
            thumb.alt = product.name;
            thumb.className = img.is_primary ? 'active' : '';
            thumb.onerror = function() { this.src = 'img/placeholder.jpg'; };
            thumb.addEventListener('click', () => {
                mainImage.src = img.image_url;
                thumbnailRow.querySelectorAll('img').forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            });
            thumbnailRow.appendChild(thumb);
        });
    }
}

// Render product info
function renderProductInfo(product) {
    document.getElementById('product-name').textContent = product.name;
    document.getElementById('product-category').textContent = product.category;
    document.getElementById('product-brand').textContent = product.brand || 'Urban Thread';
    document.getElementById('product-price').textContent = `Rs ${product.price.toLocaleString()}`;
    document.getElementById('product-description').textContent = product.description || 'No description available.';
    document.getElementById('sku-value').textContent = `UT${product.id.toString().padStart(3, '0')}`;
    
    const sizeSelect = document.getElementById('size-select');
    if (sizeSelect && product.available_sizes && product.available_sizes.length > 0) {
        sizeSelect.innerHTML = '<option value="">Select Size</option>';
        product.available_sizes.forEach(size => {
            const option = document.createElement('option');
            option.value = size;
            option.textContent = size;
            sizeSelect.appendChild(option);
        });
    }
    
    const colorSelect = document.getElementById('color-select');
    if (colorSelect && product.available_colors && product.available_colors.length > 0) {
        colorSelect.innerHTML = '<option value="">Select Color</option>';
        product.available_colors.forEach(color => {
            const option = document.createElement('option');
            option.value = color;
            option.textContent = color;
            colorSelect.appendChild(option);
        });
    }
    
    const wishlistBtn = document.getElementById('wishlist-btn');
    if (wishlistBtn) {
        const inWishlist = wishlistItems.includes(product.id);
        if (inWishlist) {
            wishlistBtn.classList.add('active');
            wishlistBtn.innerHTML = '<i class="bi bi-heart-fill"></i>';
        } else {
            wishlistBtn.classList.remove('active');
            wishlistBtn.innerHTML = '<i class="bi bi-heart"></i>';
        }
    }
}

// Render product details tab
function renderProductDetails(product) {
    const detailsContainer = document.getElementById('additional-details');
    if (!detailsContainer) return;
    
    const specs = product.specifications || [];
    
    detailsContainer.innerHTML = `
        <div class="col-md-6">
            <h6 class="fw-bold mb-3">Product Specifications</h6>
            <ul class="list-unstyled">
                ${specs.map(spec => `
                    <li class="mb-2"><strong>${spec.spec_name}:</strong> ${spec.spec_value}</li>
                `).join('')}
                <li class="mb-2"><strong>SKU:</strong> UT${product.id.toString().padStart(3, '0')}</li>
                <li class="mb-2"><strong>Stock:</strong> ${product.stock} items available</li>
            </ul>
        </div>
        <div class="col-md-6">
            <h6 class="fw-bold mb-3">Key Features</h6>
            <ul class="list-unstyled">
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>High-quality materials</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Comfortable fit</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Modern design</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Durable construction</li>
            </ul>
        </div>
    `;
}

// Render reviews
function renderReviews(product) {
    const reviewsContainer = document.getElementById('reviews-content');
    if (!reviewsContainer) return;
    
    const reviews = product.reviews || { average: 0, count: 0, list: [] };
    
    if (reviews.list.length === 0) {
        reviewsContainer.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-chat-dots display-1 text-muted"></i>
                <h5 class="mt-3">No reviews yet</h5>
                <p class="text-muted">Be the first to review this product!</p>
            </div>
        `;
        return;
    }
    
    reviewsContainer.innerHTML = `
        <div class="mb-4 p-3 bg-light rounded">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-0">${reviews.average} / 5.0</h4>
                    <div class="text-warning">
                        ${'★'.repeat(Math.floor(reviews.average))}${'☆'.repeat(5 - Math.floor(reviews.average))}
                    </div>
                    <small class="text-muted">Based on ${reviews.count} reviews</small>
                </div>
                <button class="btn btn-outline-primary" onclick="alert('Review feature coming soon!')">
                    <i class="bi bi-pencil-square me-2"></i>Write a Review
                </button>
            </div>
        </div>
        ${reviews.list.map(review => `
            <div class="review-card mb-3 p-3 border rounded">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong>${escapeHtml(review.user_name)}</strong>
                        <div class="text-warning small">
                            ${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}
                        </div>
                    </div>
                    <small class="text-muted">${review.date}</small>
                </div>
                <p class="mb-0">${escapeHtml(review.comment)}</p>
            </div>
        `).join('')}
    `;
}

// Render same category products
function renderSameCategoryProducts(products) {
    const container = document.getElementById('same-category-products');
    if (!container) return;
    
    if (!products || products.length === 0) {
        container.innerHTML = '<div class="col-12"><p class="text-muted text-center">No other products in this category.</p></div>';
        return;
    }
    
    container.innerHTML = products.map(p => `
        <div class="col-6 col-sm-3 mb-3">
            <div class="text-center">
                <img src="${p.image}" alt="${p.name}" class="img-fluid rounded mb-2" style="height: 100px; object-fit: cover; width: 100%;">
                <div class="small fw-bold">${escapeHtml(p.name)}</div>
                <div class="small text-primary">${p.formatted_price}</div>
                <a href="product.php?id=${p.id}" class="btn btn-sm btn-outline-primary mt-1">View</a>
            </div>
        </div>
    `).join('');
}

// Render complementary products
function renderComplementaryProducts(products) {
    const container = document.getElementById('complementary-products');
    if (!container) return;
    
    if (!products || products.length === 0) {
        container.innerHTML = '<div class="col-12"><div class="alert alert-info text-center">No complementary products found.</div></div>';
        return;
    }
    
    container.innerHTML = products.map(p => `
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 border">
                <img src="${p.image}" alt="${p.name}" class="card-img-top" style="height: 180px; object-fit: cover;">
                <div class="card-body text-center">
                    <h6 class="card-title fw-bold">${escapeHtml(p.name)}</h6>
                    <div class="text-primary fw-bold">${p.formatted_price}</div>
                    <div class="small text-muted mb-2">${p.category}</div>
                    <a href="product.php?id=${p.id}" class="btn btn-outline-dark btn-sm w-100">View Details</a>
                </div>
            </div>
        </div>
    `).join('');
}

// Setup event listeners
function setupEventListeners() {
    const quantityInput = document.getElementById('quantity-input');
    const decreaseBtn = document.getElementById('decrease-qty');
    const increaseBtn = document.getElementById('increase-qty');
    
    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', () => {
            if (currentQuantity > 1) {
                currentQuantity--;
                if (quantityInput) quantityInput.value = currentQuantity;
            }
        });
    }
    
    if (increaseBtn) {
        increaseBtn.addEventListener('click', () => {
            if (currentQuantity < 99) {
                currentQuantity++;
                if (quantityInput) quantityInput.value = currentQuantity;
            }
        });
    }
    
    if (quantityInput) {
        // Handle real-time input changes
        quantityInput.addEventListener('input', (e) => {
            let value = e.target.value;
            if (value < 1) {
                e.target.value = 1;
                currentQuantity = 1;
            }
            if (value > 99) {
                e.target.value = 99;
                currentQuantity = 99;
            }
        });
        
        quantityInput.addEventListener('change', (e) => {
            let value = parseInt(e.target.value);
            if (isNaN(value) || value < 1) value = 1;
            if (value > 99) value = 99;
            currentQuantity = value;
            e.target.value = value;
        });
    }
    
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    if (addToCartBtn && currentProduct) {
        addToCartBtn.addEventListener('click', () => {
            addToCart(currentProduct.id, currentQuantity);
        });
    }
    
    const buyNowBtn = document.getElementById('buy-now-btn');
    if (buyNowBtn && currentProduct) {
        buyNowBtn.addEventListener('click', async () => {
            const success = await addToCart(currentProduct.id, currentQuantity);
            if (success) {
                setTimeout(() => window.location.href = 'cart.php', 500);
            }
        });
    }
    
    const wishlistBtn = document.getElementById('wishlist-btn');
    if (wishlistBtn && currentProduct) {
        wishlistBtn.addEventListener('click', () => {
            toggleWishlist(currentProduct.id);
        });
    }
}

// Cart functions
async function addToCart(productId, quantity) {
    if (!productId || quantity < 1) return false;
    
    const product = currentProduct;
    if (!product) return false;
    
    const selectedSize = document.getElementById('size-select')?.value;
    const selectedColor = document.getElementById('color-select')?.value;
    
    try {
        // Try to save to database for logged-in users
        const response = await fetch('/urban-thread/api/cart/add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity,
                size: selectedSize,
                color: selectedColor
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateCartCount();
            showToast(`${product.name} added to cart!`, 'success');
            return true;
        } else if (data.message && data.message.includes('login')) {
            // Not logged in, use localStorage
            addToCartLocalStorage(productId, quantity, selectedSize, selectedColor);
            return true;
        } else {
            showToast(data.message || 'Failed to add to cart', 'error');
            return false;
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        // Fallback to localStorage
        addToCartLocalStorage(productId, quantity, selectedSize, selectedColor);
        return true;
    }
}

// Add to cart using localStorage (for guests)
function addToCartLocalStorage(productId, quantity, selectedSize, selectedColor) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const product = currentProduct;
    
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            category: product.category,
            quantity: quantity,
            selectedSize: selectedSize,
            selectedColor: selectedColor
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    showToast(`${product.name} added to cart!`, 'success');
}

// Toggle wishlist using database
async function toggleWishlist(productId) {
    try {
        const response = await fetch('/urban-thread/api/wishlist/toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ product_id: productId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.action === 'added') {
                wishlistItems.push(productId);
                showToast('Added to wishlist!', 'success');
            } else {
                wishlistItems = wishlistItems.filter(id => id !== productId);
                showToast('Removed from wishlist', 'info');
            }
            // Update wishlist button
            const wishlistBtn = document.getElementById('wishlist-btn');
            if (wishlistBtn) {
                if (data.action === 'added') {
                    wishlistBtn.classList.add('active');
                    wishlistBtn.innerHTML = '<i class="bi bi-heart-fill"></i>';
                } else {
                    wishlistBtn.classList.remove('active');
                    wishlistBtn.innerHTML = '<i class="bi bi-heart"></i>';
                }
            }
        } else {
            if (data.message === 'Please login to manage wishlist') {
                showToast('Please login to add to wishlist', 'warning');
                setTimeout(() => {
                    window.location.href = 'reg.php';
                }, 1500);
            } else {
                showToast(data.message, 'error');
            }
        }
    } catch (error) {
        console.error('Error toggling wishlist:', error);
        // Fallback to localStorage
        toggleWishlistFallback(productId);
    }
}

// Fallback for guests (localStorage)
function toggleWishlistFallback(productId) {
    let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    const wishlistBtn = document.getElementById('wishlist-btn');
    
    if (wishlist.includes(productId)) {
        wishlist = wishlist.filter(id => id !== productId);
        if (wishlistBtn) {
            wishlistBtn.classList.remove('active');
            wishlistBtn.innerHTML = '<i class="bi bi-heart"></i>';
        }
        showToast('Removed from wishlist', 'info');
    } else {
        wishlist.push(productId);
        if (wishlistBtn) {
            wishlistBtn.classList.add('active');
            wishlistBtn.innerHTML = '<i class="bi bi-heart-fill"></i>';
        }
        showToast('Added to wishlist!', 'success');
    }
    
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    wishlistItems = wishlist;
}

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCount = document.getElementById('cart-count');
    if (cartCount) cartCount.textContent = totalItems;
}

function showLoading(show) {
    const loadingDiv = document.querySelector('.product-loading');
    if (loadingDiv) loadingDiv.style.display = show ? 'block' : 'none';
}

function showError(message) {
    const container = document.querySelector('.container.my-5');
    if (container) {
        container.innerHTML = `
            <div class="alert alert-danger text-center py-5">
                <i class="bi bi-exclamation-triangle display-4 mb-3"></i>
                <h4>Product Not Found</h4>
                <p class="mb-4">${message}</p>
                <a href="shop.php" class="btn btn-primary btn-lg">Return to Shop</a>
            </div>
        `;
    }
}

function showToast(message, type = 'info') {
    const existingToast = document.querySelector('.custom-toast');
    if (existingToast) existingToast.remove();
    
    const toast = document.createElement('div');
    toast.className = `custom-toast alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info'} alert-dismissible fade show position-fixed`;
    toast.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        min-width: 280px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        border-radius: 10px;
        animation: slideInRight 0.3s ease;
    `;
    
    const icon = type === 'success' ? 'bi-check-circle' : type === 'warning' ? 'bi-exclamation-triangle' : 'bi-info-circle';
    
    toast.innerHTML = `
        <div class="d-flex align-items-center p-2">
            <i class="bi ${icon} me-2 fs-5"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    const productId = getProductId();
    if (productId) {
        loadProductFromDatabase(productId);
    } else {
        showError('No product ID specified');
    }
});

// Make functions global
window.addToCart = addToCart;
window.toggleWishlist = toggleWishlist;