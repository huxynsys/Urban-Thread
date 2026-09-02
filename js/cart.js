// js/cart.js - Updated with Database Integration

class ShoppingCart {
    constructor() {
        this.cart = [];
        this.totals = { subtotal: 0, shipping: 0, tax: 0, total: 0 };
        this.isLoggedIn = false;
        this.user = null;
        this.init();
    }

    async init() {
        await this.checkLoginStatus();
        await this.loadCart();
        this.setupEventListeners();
        this.render();
    }

    async checkLoginStatus() {
        try {
            const response = await fetch('/urban-thread/api/auth/check.php', {
                credentials: 'include'
            });
            const data = await response.json();
            this.isLoggedIn = data.logged_in;
            this.user = data.user;
            console.log('Login status:', this.isLoggedIn);
        } catch (error) {
            console.error('Error checking login status:', error);
            this.isLoggedIn = false;
        }
    }

    async loadCart() {
        try {
            console.log('Loading cart...');
            const response = await fetch('/urban-thread/api/cart/get.php', {
                credentials: 'include'
            });
            const data = await response.json();
            console.log('Cart API response:', data);
            
            if (data.success) {
                this.cart = data.cart;
                this.totals = data.totals;
                console.log('Cart loaded:', this.cart.length, 'items');
                this.updateCartCount();
                this.render();
            } else {
                console.error('Failed to load cart:', data.message);
                this.loadCartFromLocalStorage();
            }
        } catch (error) {
            console.error('Error loading cart:', error);
            this.loadCartFromLocalStorage();
        }
    }

    loadCartFromLocalStorage() {
        console.log('Loading cart from localStorage');
        this.cart = JSON.parse(localStorage.getItem('cart')) || [];
        this.calculateTotals();
        this.updateCartCount();
        this.render();
    }

    calculateTotals() {
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const shipping = subtotal > 3000 ? 0 : 250;
        const tax = Math.round(subtotal * 0.08);
        this.totals = { subtotal, shipping, tax, total: subtotal + shipping + tax };
    }

    async addToCart(productId, quantity = 1, size = null, color = null) {
        try {
            console.log('Adding to cart:', productId);
            const response = await fetch('/urban-thread/api/cart/add.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ product_id: productId, quantity, size, color })
            });
            
            const data = await response.json();
            console.log('Add to cart response:', data);
            
            if (data.success) {
                await this.loadCart();
                this.showToast(data.message, 'success');
                return true;
            } else {
                this.showToast(data.message, 'error');
                return false;
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            this.addToCartLocalStorage(productId, quantity, size, color);
            return false;
        }
    }

    addToCartLocalStorage(productId, quantity, size, color) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const existingItem = cart.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.fetchProductAndAdd(productId, quantity, size, color);
            return;
        }
        
        localStorage.setItem('cart', JSON.stringify(cart));
        this.loadCartFromLocalStorage();
        this.showToast('Product added to cart!', 'success');
    }

    async fetchProductAndAdd(productId, quantity, size, color) {
        try {
            const response = await fetch(`/urban-thread/api/products/single.php?id=${productId}`);
            const data = await response.json();
            if (data.success && data.product) {
                let cart = JSON.parse(localStorage.getItem('cart')) || [];
                cart.push({
                    id: data.product.id,
                    name: data.product.name,
                    price: data.product.price,
                    image: data.product.image,
                    category: data.product.category,
                    quantity: quantity,
                    selected_size: size,
                    selected_color: color
                });
                localStorage.setItem('cart', JSON.stringify(cart));
                this.loadCartFromLocalStorage();
                this.showToast('Product added to cart!', 'success');
            }
        } catch (error) {
            console.error('Error fetching product:', error);
        }
    }

    async updateQuantity(productId, quantity) {
        if (quantity <= 0) {
            await this.removeItem(productId);
            return;
        }
        
        try {
            const response = await fetch('/urban-thread/api/cart/update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ product_id: productId, quantity })
            });
            
            const data = await response.json();
            
            if (data.success) {
                await this.loadCart();
                this.showToast('Cart updated', 'success');
            }
        } catch (error) {
            console.error('Error updating cart:', error);
            this.updateQuantityLocalStorage(productId, quantity);
        }
    }

    updateQuantityLocalStorage(productId, quantity) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const item = cart.find(i => i.id === productId);
        if (item) {
            if (quantity <= 0) {
                cart = cart.filter(i => i.id !== productId);
            } else {
                item.quantity = quantity;
            }
            localStorage.setItem('cart', JSON.stringify(cart));
            this.loadCartFromLocalStorage();
        }
    }

    async removeItem(productId) {
        try {
            const response = await fetch('/urban-thread/api/cart/remove.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ product_id: productId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                await this.loadCart();
                this.showToast('Item removed from cart', 'info');
            }
        } catch (error) {
            console.error('Error removing item:', error);
            this.removeItemLocalStorage(productId);
        }
    }

    removeItemLocalStorage(productId) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        cart = cart.filter(i => i.id !== productId);
        localStorage.setItem('cart', JSON.stringify(cart));
        this.loadCartFromLocalStorage();
        this.showToast('Item removed from cart', 'info');
    }

    async syncCartAfterLogin() {
        const localCart = JSON.parse(localStorage.getItem('cart')) || [];
        
        if (localCart.length > 0) {
            try {
                const response = await fetch('/urban-thread/api/cart/sync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ local_cart: localCart })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    localStorage.removeItem('cart');
                    await this.loadCart();
                    this.showToast('Cart synced with your account!', 'success');
                }
            } catch (error) {
                console.error('Error syncing cart:', error);
            }
        }
    }

    updateCartCount() {
        const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        const cartCount = document.getElementById('cart-count');
        const itemCount = document.getElementById('item-count');
        
        if (cartCount) cartCount.textContent = totalItems;
        if (itemCount) itemCount.textContent = totalItems;
        console.log('Cart count updated:', totalItems);
    }

    render() {
        const container = document.getElementById('cart-items-container');
        const emptyCart = document.getElementById('empty-cart');
        const orderSummary = document.getElementById('order-summary');
        
        console.log('Rendering cart, items:', this.cart.length);
        
        if (!this.cart || this.cart.length === 0) {
            if (container) container.style.display = 'none';
            if (emptyCart) emptyCart.style.display = 'block';
            if (orderSummary) orderSummary.innerHTML = '<div class="text-center py-4">Your cart is empty</div>';
            return;
        }
        
        if (container) {
            container.style.display = 'block';
            container.innerHTML = this.renderCartItems();
        }
        if (emptyCart) emptyCart.style.display = 'none';
        if (orderSummary) orderSummary.innerHTML = this.renderOrderSummary();
    }

    renderCartItems() {
        return this.cart.map(item => {
            const productId = item.product_id || item.id;
            const productName = item.name;
            const productPrice = parseFloat(item.price);
            const productImage = item.image || 'img/placeholder.jpg';
            const productQuantity = item.quantity;
            const productSubtotal = productPrice * productQuantity;
            
            return `
            <div class="cart-item" data-id="${productId}">
                <div class="cart-item-img-container">
                    <img src="${productImage}" alt="${this.escapeHtml(productName)}" class="cart-item-img" onerror="this.src='img/placeholder.jpg'">
                    <div class="cart-item-img-overlay">${productQuantity}</div>
                </div>
                <div class="cart-item-details">
                    <div class="cart-item-name">${this.escapeHtml(productName)}</div>
                    <div class="cart-item-info">
                        <span class="cart-item-category">${item.category || 'Fashion'}</span>
                        ${item.selected_size ? `<span class="cart-item-size">Size: ${item.selected_size}</span>` : ''}
                        ${item.selected_color ? `<span class="cart-item-color">Color: ${item.selected_color}</span>` : ''}
                    </div>
                    <div class="cart-item-price">Rs ${productPrice.toLocaleString()}</div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn" onclick="shoppingCart.updateQuantity(${productId}, ${productQuantity - 1})" ${productQuantity <= 1 ? 'disabled' : ''}>-</button>
                        <span class="quantity-display">${productQuantity}</span>
                        <button class="quantity-btn" onclick="shoppingCart.updateQuantity(${productId}, ${productQuantity + 1})">+</button>
                        <span class="quantity-text">× Rs ${productPrice.toLocaleString()} each</span>
                    </div>
                </div>
                <div class="cart-item-total">
                    <div class="item-total-price">Rs ${productSubtotal.toLocaleString()}</div>
                    <button class="remove-btn" onclick="shoppingCart.removeItem(${productId})">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
            </div>
        `}).join('');
    }

    renderOrderSummary() {
        const subtotal = this.totals?.subtotal || 0;
        const shipping = this.totals?.shipping || 0;
        const tax = this.totals?.tax || 0;
        const total = this.totals?.total || 0;
        
        return `
            <div class="order-summary-item">
                <span>Subtotal (${this.cart.reduce((s, i) => s + i.quantity, 0)} items)</span>
                <span class="fw-semibold">Rs ${subtotal.toLocaleString()}</span>
            </div>
            <div class="order-summary-item">
                <span>Shipping</span>
                <span class="${shipping === 0 ? 'text-success' : ''}">${shipping === 0 ? 'FREE' : `Rs ${shipping.toLocaleString()}`}</span>
            </div>
            <div class="order-summary-item">
                <span>Tax (8%)</span>
                <span>Rs ${tax.toLocaleString()}</span>
            </div>
            <div class="order-summary-item total">
                <span>Total Amount</span>
                <span class="fs-5">Rs ${total.toLocaleString()}</span>
            </div>
        `;
    }

    escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
        toast.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            min-width: 250px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
            animation: slideInRight 0.3s ease;
        `;
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-info-circle'} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    setupEventListeners() {
        // Checkout button
        document.addEventListener('click', (e) => {
            if (e.target.id === 'checkout-btn' || e.target.closest('#checkout-btn')) {
                if (this.cart.length === 0) {
                    e.preventDefault();
                    this.showToast('Your cart is empty', 'warning');
                }
            }
        });
    }
}

// Initialize shopping cart
let shoppingCart;
document.addEventListener('DOMContentLoaded', async () => {
    console.log('DOM loaded, initializing cart...');
    shoppingCart = new ShoppingCart();
});

// Make available globally
window.shoppingCart = shoppingCart;