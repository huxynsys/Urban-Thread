// js/account.js - Account page with database wishlist and orders

let currentUser = null;
let wishlistItems = [];

window.addEventListener('DOMContentLoaded', async () => {
    await checkUserLogin();
    await loadUserData();
    await loadOrders();
    await loadWishlist();
    updateCartCount();
    
    document.getElementById('logout-btn')?.addEventListener('click', logout);
});

// Check if user is logged in
async function checkUserLogin() {
    try {
        const response = await fetch('/urban-thread/api/auth/check.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (!data.logged_in) {
            window.location.href = 'reg.php';
            return;
        }
        
        currentUser = data.user;
    } catch (error) {
        console.error('Error checking login:', error);
        window.location.href = 'reg.php';
    }
}

// Load user data from database
async function loadUserData() {
    try {
        const response = await fetch('/urban-thread/api/auth/check.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.logged_in && data.user) {
            currentUser = data.user;
            renderProfile(currentUser);
            await loadVirtualCard();
        }
    } catch (error) {
        console.error('Error loading user data:', error);
    }
}

// Load virtual card from database
async function loadVirtualCard() {
    try {
        const response = await fetch('/urban-thread/api/card/get.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success && data.card) {
            const profileDetails = document.getElementById('profile-details');
            if (profileDetails && currentUser) {
                profileDetails.innerHTML = `
                    <div><span class="fw-bold">Name:</span> ${escapeHtml(currentUser.name)}</div>
                    <div><span class="fw-bold">Email:</span> ${escapeHtml(currentUser.email)}</div>
                    <div><span class="fw-bold">Card Balance:</span> Rs ${data.card.balance.toFixed(2)}</div>
                    <div><span class="fw-bold">Reward Points:</span> ${data.card.points}</div>
                    <div><span class="fw-bold">Card Tier:</span> <span class="badge bg-${getTierColor(data.card.tier)}">${data.card.tier}</span></div>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading virtual card:', error);
        // Fallback to localStorage
        if (currentUser) {
            const profileDetails = document.getElementById('profile-details');
            if (profileDetails) {
                profileDetails.innerHTML = `
                    <div><span class="fw-bold">Name:</span> ${escapeHtml(currentUser.name)}</div>
                    <div><span class="fw-bold">Email:</span> ${escapeHtml(currentUser.email)}</div>
                    <div><span class="fw-bold">Card Balance:</span> Rs ${currentUser.virtualCard?.balance?.toFixed(2) ?? '0.00'}</div>
                    <div><span class="fw-bold">Reward Points:</span> ${currentUser.virtualCard?.points ?? 0}</div>
                `;
            }
        }
    }
}

function getTierColor(tier) {
    switch(tier) {
        case 'gold': return 'warning';
        case 'platinum': return 'info';
        case 'silver': return 'secondary';
        default: return 'primary';
    }
}

function renderProfile(user) {
    const details = document.getElementById('profile-details');
    if (details) {
        details.innerHTML = `
            <div><span class="fw-bold">Name:</span> ${escapeHtml(user.name)}</div>
            <div><span class="fw-bold">Email:</span> ${escapeHtml(user.email)}</div>
            <div><span class="fw-bold">Member Since:</span> ${new Date().toLocaleDateString()}</div>
            <div><span class="fw-bold">Role:</span> <span class="badge ${user.role === 'admin' ? 'bg-danger' : 'bg-secondary'}">${user.role}</span></div>
        `;
    }
}

// Load orders from database
async function loadOrders() {
    try {
        const response = await fetch('/urban-thread/api/orders/list.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        const history = document.getElementById('order-history');
        
        if (!history) return;
        
        if (!data.success || !data.orders || data.orders.length === 0) {
            history.innerHTML = '<div class="text-muted text-center py-4">No orders yet.</div>';
            return;
        }
        
        history.innerHTML = data.orders.map(order => `
            <div class="order-card border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="fw-bold">Order #${order.order_number}</span>
                        <span class="badge ${getOrderStatusClass(order.status)} ms-2">${order.status}</span>
                    </div>
                    <small class="text-muted">${new Date(order.created_at).toLocaleDateString()}</small>
                </div>
                <div class="small text-muted mb-2">Total: Rs ${parseFloat(order.total).toLocaleString()}</div>
                <a href="order-confirmation.php?order_id=${order.id}" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-eye me-1"></i>View Details
                </a>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading orders:', error);
        loadOrdersFallback();
    }
}

function getOrderStatusClass(status) {
    switch(status) {
        case 'delivered': return 'bg-success';
        case 'shipped': return 'bg-info';
        case 'processing': return 'bg-warning';
        case 'cancelled': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function loadOrdersFallback() {
    const orders = JSON.parse(localStorage.getItem('orders')) || [];
    const history = document.getElementById('order-history');
    
    if (orders.length === 0) {
        if (history) history.innerHTML = '<div class="text-muted text-center py-4">No orders yet.</div>';
        return;
    }
    
    if (history) {
        history.innerHTML = orders.filter(o => o.email === currentUser?.email).map(order => `
            <div class="order-card border rounded p-3 mb-3">
                <div><span class="fw-bold">${new Date(order.date).toLocaleDateString()}</span> | <span>${order.status}</span></div>
                <div>${order.cart?.map(c => `${c.name} (x${c.quantity})`).join(', ') || 'Items'}</div>
                <div>Total: <span class="fw-bold">Rs ${order.cart?.reduce((sum, item) => sum + (item.price || 0) * (item.quantity || 1), 0).toLocaleString() || 0}</span></div>
            </div>
        `).join('');
    }
}

// Load wishlist from database
async function loadWishlist() {
    try {
        const response = await fetch('/urban-thread/api/wishlist/get.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        const div = document.getElementById('account-wishlist');
        if (!div) return;
        
        if (!data.success || !data.wishlist || data.wishlist.length === 0) {
            div.innerHTML = '<div class="text-muted text-center py-4">No wishlist items yet.</div>';
            wishlistItems = [];
            return;
        }
        
        wishlistItems = data.wishlist;
        
        div.innerHTML = data.wishlist.map(product => `
            <div class="col-md-4 wishlist-card mb-3">
                <div class="card h-100">
                    <img src="${product.image || 'img/placeholder.jpg'}" alt="${product.name}" class="card-img-top" style="height: 150px; object-fit: cover;">
                    <div class="card-body text-center">
                        <div class="fw-bold">${escapeHtml(product.name)}</div>
                        <div class="text-muted">${product.formatted_price || 'Rs ' + parseFloat(product.price).toLocaleString()}</div>
                        <div class="small text-secondary mb-2">${product.category}</div>
                        <div class="d-grid gap-2">
                            <button class="btn btn-danger btn-sm" onclick="removeFromWishlist(${product.id})">
                                <i class="bi bi-heart-broken"></i> Remove
                            </button>
                            <button class="btn btn-dark btn-sm" onclick="addToCartFromWishlist(${product.id}, '${escapeHtml(product.name)}', ${product.price}, '${product.image}')">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading wishlist:', error);
        loadWishlistFallback();
    }
}

// Fallback to localStorage
function loadWishlistFallback() {
    const wishlistIds = JSON.parse(localStorage.getItem('wishlist')) || [];
    const div = document.getElementById('account-wishlist');
    
    if (wishlistIds.length === 0) {
        if (div) div.innerHTML = '<div class="text-muted text-center py-4">No wishlist items yet.</div>';
        return;
    }
    
    // Fetch product details for wishlist IDs
    fetch('/urban-thread/api/products/list.php?limit=100')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const wishlistProducts = data.products.filter(p => wishlistIds.includes(p.id));
                if (div) {
                    div.innerHTML = wishlistProducts.map(product => `
                        <div class="col-md-4 wishlist-card mb-3">
                            <div class="card h-100">
                                <img src="${product.image || 'img/placeholder.jpg'}" alt="${product.name}" class="card-img-top" style="height: 150px; object-fit: cover;">
                                <div class="card-body text-center">
                                    <div class="fw-bold">${escapeHtml(product.name)}</div>
                                    <div class="text-muted">Rs ${parseFloat(product.price).toLocaleString()}</div>
                                    <div class="small text-secondary mb-2">${product.category}</div>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-danger btn-sm" onclick="removeFromWishlist(${product.id})">
                                            <i class="bi bi-heart-broken"></i> Remove
                                        </button>
                                        <button class="btn btn-dark btn-sm" onclick="addToCartFromWishlist(${product.id}, '${escapeHtml(product.name)}', ${product.price}, '${product.image}')">
                                            <i class="bi bi-cart-plus"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
            }
        })
        .catch(console.error);
}

// Remove from wishlist (database)
window.removeFromWishlist = async function(productId) {
    try {
        const response = await fetch('/urban-thread/api/wishlist/remove.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ product_id: productId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Also remove from localStorage if present
            let localWishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
            localWishlist = localWishlist.filter(id => id !== productId);
            localStorage.setItem('wishlist', JSON.stringify(localWishlist));
            
            // Reload wishlist to refresh display
            await loadWishlist();
            showToast('Removed from wishlist', 'success');
        } else {
            showToast(data.message || 'Failed to remove', 'error');
        }
    } catch (error) {
        console.error('Error removing from wishlist:', error);
        // Fallback to localStorage
        let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
        wishlist = wishlist.filter(id => id !== productId);
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
        await loadWishlist();
        showToast('Removed from wishlist', 'success');
    }
};

// Add to cart from wishlist
window.addToCartFromWishlist = async function(productId, productName, productPrice, productImage) {
    try {
        // Try to add to database cart if logged in
        const response = await fetch('/urban-thread/api/cart/add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ product_id: productId, quantity: 1 })
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateCartCount();
            showToast('Added to cart!', 'success');
        } else {
            // Fallback to localStorage
            addToCartLocal(productId, productName, productPrice, productImage);
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        // Fallback to localStorage
        addToCartLocal(productId, productName, productPrice, productImage);
    }
};

function addToCartLocal(productId, productName, productPrice, productImage) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: productId,
            name: productName,
            price: productPrice,
            image: productImage,
            quantity: 1
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    showToast('Added to cart!', 'success');
}

// Update cart count from database
function updateCartCount() {
    fetch('/urban-thread/api/cart/get.php', { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            const cartCount = document.getElementById('cart-count');
            if (cartCount && data.success) {
                const totalItems = data.cart.reduce((sum, item) => sum + item.quantity, 0);
                cartCount.textContent = totalItems;
            }
        })
        .catch(() => {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartCount = document.getElementById('cart-count');
            if (cartCount) cartCount.textContent = cart.length;
        });
}

// Logout function
async function logout() {
    try {
        await fetch('/urban-thread/api/auth/logout.php', { 
            method: 'POST',
            credentials: 'include'
        });
        localStorage.removeItem('currentUser');
        localStorage.removeItem('cart');
        localStorage.removeItem('wishlist');
        window.location.href = 'index.php';
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = 'index.php';
    }
}

// Show toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 280px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 8px;
        animation: slideInRight 0.3s ease;
    `;
    
    const icon = type === 'success' ? 'bi-check-circle' : type === 'error' ? 'bi-exclamation-triangle' : 'bi-info-circle';
    
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