// js/card.js - Urban Thread Virtual Card with Full Database Integration

class VirtualCard {
    constructor() {
        this.card = null;
        this.transactions = [];
        this.stats = {};
        this.init();
    }

    async init() {
        console.log('Virtual Card initialized');
        await this.checkLogin();
        await this.loadCardData();
        this.setupEventListeners();
        this.updateCartCount();
    }

    async checkLogin() {
        try {
            const response = await fetch('/urban-thread/api/auth/check.php', {
                credentials: 'include'
            });
            const data = await response.json();
            
            if (!data.logged_in) {
                this.showToast('Please login to access your virtual card', 'warning');
                setTimeout(() => {
                    window.location.href = 'reg.php';
                }, 1500);
                return false;
            }
            return true;
        } catch (error) {
            console.error('Error checking login:', error);
            return false;
        }
    }

    async loadCardData() {
        try {
            this.showLoading(true);
            console.log('Loading card data...');
            
            const response = await fetch('/urban-thread/api/card/get.php', {
                credentials: 'include'
            });
            const data = await response.json();
            console.log('Card data received:', data);
            
            if (data.success) {
                this.card = data.card;
                this.transactions = data.transactions || [];
                this.stats = data.stats || { total_added: 0, total_redeemed: 0, total_spent: 0 };
                this.render();
                this.updateTransactionStats();
            } else {
                console.error('Failed to load card:', data.message);
                this.showToast('Failed to load card data', 'error');
                this.loadFromLocalStorage();
            }
        } catch (error) {
            console.error('Error loading card:', error);
            this.showToast('Error loading card data', 'error');
            this.loadFromLocalStorage();
        } finally {
            this.showLoading(false);
        }
    }

    loadFromLocalStorage() {
        const user = this.getCurrentUser();
        if (user && user.virtualCard) {
            this.card = user.virtualCard;
            this.transactions = user.virtualCard.transactions || [];
            this.render();
            this.updateTransactionStats();
        } else {
            this.showDemoCard();
        }
    }

    showDemoCard() {
        const cardNumberEl = document.getElementById('card-number');
        const cardBalanceEl = document.getElementById('card-balance');
        const rewardPointsEl = document.getElementById('reward-points');
        
        if (cardNumberEl) cardNumberEl.textContent = '3221 **** **** 1234';
        if (cardBalanceEl) cardBalanceEl.textContent = 'Rs 50.00';
        if (rewardPointsEl) rewardPointsEl.textContent = '150';
        
        const cardholderName = document.getElementById('cardholder-name');
        if (cardholderName) {
            fetch('/urban-thread/api/auth/check.php', { credentials: 'include' })
                .then(res => res.json())
                .then(data => {
                    if (data.logged_in) {
                        cardholderName.textContent = data.user.name.toUpperCase();
                    }
                });
        }
        
        const container = document.getElementById('card-transactions');
        if (container) {
            container.innerHTML = `
                <div class="card-transaction border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-light p-2">
                                <i class="bi bi-arrow-down-right text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold">Welcome Bonus</h6>
                                <small class="text-muted">Initial account credit</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">+ Rs 50.00</div>
                            <small class="text-muted">Just now</small>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    getCurrentUser() {
        try {
            return JSON.parse(localStorage.getItem('currentUser'));
        } catch (error) {
            console.error('Error parsing user data:', error);
            return null;
        }
    }

    render() {
        this.renderCardDisplay();
        this.renderTransactions();
        this.renderStats();
    }

    renderCardDisplay() {
        if (!this.card) return;
        
        const cardNumber = this.card.card_number || '**** **** **** ****';
        const formattedNumber = cardNumber.replace(/(\d{4})/g, '$1 ').trim();
        
        const cardNumberEl = document.getElementById('card-number');
        const cardBalanceEl = document.getElementById('card-balance');
        const rewardPointsEl = document.getElementById('reward-points');
        
        if (cardNumberEl) cardNumberEl.textContent = formattedNumber;
        if (cardBalanceEl) cardBalanceEl.textContent = `Rs ${this.card.balance.toLocaleString()}`;
        if (rewardPointsEl) rewardPointsEl.textContent = this.card.points.toLocaleString();
        
        const cardholderName = document.getElementById('cardholder-name');
        if (cardholderName) {
            fetch('/urban-thread/api/auth/check.php', { credentials: 'include' })
                .then(res => res.json())
                .then(data => {
                    if (data.logged_in) {
                        cardholderName.textContent = data.user.name.toUpperCase();
                    }
                });
        }
        
        const tierElement = document.getElementById('card-tier');
        if (tierElement) {
            const tierColors = {
                'bronze': 'secondary',
                'silver': 'secondary', 
                'gold': 'warning',
                'platinum': 'info'
            };
            const tierColor = tierColors[this.card.tier] || 'primary';
            tierElement.textContent = this.card.tier.toUpperCase();
            tierElement.className = `badge bg-${tierColor}`;
        }
        
        const user = this.getCurrentUser();
        if (user) {
            if (!user.virtualCard) user.virtualCard = {};
            user.virtualCard = this.card;
            localStorage.setItem('currentUser', JSON.stringify(user));
        }
    }

    renderTransactions() {
        const container = document.getElementById('card-transactions');
        if (!container) return;
        
        if (!this.transactions || this.transactions.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No transactions yet</p>
                    <small class="text-muted">Your transactions will appear here</small>
                </div>
            `;
            return;
        }
        
        const sortedTransactions = [...this.transactions].sort((a, b) => 
            new Date(b.created_at || b.date) - new Date(a.created_at || a.date)
        ).slice(0, 20);
        
        container.innerHTML = sortedTransactions.map(tx => {
            const isPositive = tx.type === 'Top Up' || tx.type === 'Welcome Bonus' || tx.type === 'Points Redemption';
            const amountClass = isPositive ? 'text-success' : 'text-danger';
            const icon = isPositive ? 'bi-arrow-down-right' : 'bi-arrow-up-right';
            const sign = isPositive ? '+' : '-';
            const txDate = tx.date || new Date(tx.created_at).toLocaleDateString();
            const txTime = tx.time || new Date(tx.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            return `
                <div class="card-transaction border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-light p-2">
                                <i class="bi ${icon} ${amountClass}"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold">${this.escapeHtml(tx.type)}</h6>
                                <small class="text-muted">${this.escapeHtml(tx.description || '')}</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold ${amountClass}">
                                ${sign} Rs ${Math.abs(tx.amount).toLocaleString()}
                            </div>
                            <small class="text-muted">
                                ${txDate} ${txTime}
                            </small>
                            ${tx.status === 'pending' ? 
                                '<span class="badge bg-warning ms-2">Pending</span>' : 
                                '<span class="badge bg-success ms-2">Completed</span>'
                            }
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    renderStats() {
        const totalTopups = document.getElementById('total-topups');
        const totalSaved = document.getElementById('total-saved');
        
        if (totalTopups) {
            totalTopups.textContent = `Rs ${(this.stats.total_added || 0).toLocaleString()}`;
        }
        
        if (totalSaved) {
            totalSaved.textContent = `Rs ${(this.stats.total_redeemed || 0).toLocaleString()}`;
        }
    }

    updateTransactionStats() {
        if (!this.transactions) return;
        
        const totalTopups = this.transactions
            .filter(tx => tx.type === 'Top Up' || tx.type === 'Welcome Bonus')
            .reduce((sum, tx) => sum + Math.abs(tx.amount), 0);
        
        const totalSaved = this.transactions
            .filter(tx => tx.type === 'Points Redemption')
            .reduce((sum, tx) => sum + Math.abs(tx.amount), 0);
        
        const totalTopupsEl = document.getElementById('total-topups');
        const totalSavedEl = document.getElementById('total-saved');
        
        if (totalTopupsEl) totalTopupsEl.textContent = `Rs ${totalTopups.toLocaleString()}`;
        if (totalSavedEl) totalSavedEl.textContent = `Rs ${totalSaved.toLocaleString()}`;
    }

    async topUp() {
        const amount = prompt('Enter amount to add (minimum Rs 10, maximum Rs 50,000):', '50');
        
        if (!amount) return;
        
        const parsedAmount = parseFloat(amount);
        
        if (isNaN(parsedAmount) || parsedAmount < 10 || parsedAmount > 50000) {
            this.showToast('Please enter an amount between Rs 10 and Rs 50,000', 'error');
            return;
        }
        
        this.showToast('Processing...', 'info');
        
        try {
            const response = await fetch('/urban-thread/api/card/topup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ amount: parsedAmount })
            });
            
            const data = await response.json();
            console.log('Top-up response:', data);
            
            if (data.success) {
                this.showToast(`Successfully added Rs ${parsedAmount.toLocaleString()} to your card!`, 'success');
                await this.loadCardData();
            } else {
                this.showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Top-up error:', error);
            this.showToast('Failed to add funds. Please try again.', 'error');
        }
    }

   async redeemPoints() {
    const redeemBtn = document.getElementById('card-redeem-btn');
    const originalContent = redeemBtn.innerHTML;

    if (this.card.points < 100) {
        this.showToast(`You need at least 100 points. You have ${this.card.points}`, 'warning');
        return;
    }

    const maxRedeemable = Math.floor(this.card.points / 100) * 100;
    const redeemValue = (maxRedeemable / 100) * 5;

    if (confirm(`Redeem ${maxRedeemable} points for Rs ${redeemValue}?`)) {
        try {
            // Lock button to prevent double clicks
            redeemBtn.disabled = true;
            redeemBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            const response = await fetch('/urban-thread/api/card/redeem.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ points: maxRedeemable })
            });
            
            const data = await response.json();
            if (data.success) {
                this.showToast('Success! Points redeemed!', 'success');
                location.reload(); // Refresh to update balance
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.showToast(error.message, 'error');
            redeemBtn.disabled = false;
            redeemBtn.innerHTML = originalContent;
        }
    }
}
    async transfer() {
        this.showToast('Transfer feature coming soon!', 'info');
    }

    async refresh() {
        this.showToast('Refreshing card data...', 'info');
        await this.loadCardData();
        this.showToast('Card data refreshed!', 'success');
    }

    async exportTransactions() {
        if (!this.transactions || this.transactions.length === 0) {
            this.showToast('No transactions to export', 'info');
            return;
        }
        
        let csv = 'Date,Type,Description,Amount,Status\n';
        
        this.transactions.forEach(tx => {
            const date = tx.date || new Date(tx.created_at).toLocaleDateString();
            const amount = `Rs ${Math.abs(tx.amount).toLocaleString()}`;
            csv += `"${date}","${tx.type}","${tx.description || ''}","${amount}","${tx.status}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `urban-thread-transactions-${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        this.showToast('Transactions exported successfully!', 'success');
    }

    setupEventListeners() {
        console.log('Setting up event listeners');
        
        const topupBtn = document.getElementById('card-topup-btn');
        const redeemBtn = document.getElementById('card-redeem-btn');
        const transferBtn = document.getElementById('card-transfer-btn');
        const refreshBtn = document.getElementById('refresh-btn');
        const exportBtn = document.getElementById('export-btn');
        
        console.log('Topup button found:', !!topupBtn);
        console.log('Redeem button found:', !!redeemBtn);
        console.log('Transfer button found:', !!transferBtn);
        
        if (topupBtn) topupBtn.addEventListener('click', () => this.topUp());
        if (redeemBtn) redeemBtn.addEventListener('click', () => this.redeemPoints());
        if (transferBtn) transferBtn.addEventListener('click', () => this.transfer());
        if (refreshBtn) refreshBtn.addEventListener('click', () => this.refresh());
        if (exportBtn) exportBtn.addEventListener('click', () => this.exportTransactions());
        
        window.addEventListener('focus', () => this.refresh());
    }

    showLoading(show) {
        const transactionsContainer = document.getElementById('card-transactions');
        if (transactionsContainer && show) {
            const loadingHtml = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-3">Loading card data...</p>
                </div>
            `;
            if (transactionsContainer.innerHTML !== loadingHtml) {
                transactionsContainer.innerHTML = loadingHtml;
            }
        }
    }

    showToast(message, type = 'info') {
        const existingToast = document.querySelector('.custom-toast');
        if (existingToast) existingToast.remove();
        
        const toast = document.createElement('div');
        toast.className = `custom-toast alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 280px;
            max-width: 350px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border-radius: 12px;
            animation: slideInRight 0.3s ease;
            backdrop-filter: blur(10px);
        `;
        
        const icon = type === 'success' ? 'bi-check-circle-fill' : type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill';
        
        toast.innerHTML = `
            <div class="d-flex align-items-center p-3">
                <i class="bi ${icon} me-3 fs-4 ${type === 'success' ? 'text-success' : type === 'error' ? 'text-danger' : 'text-primary'}"></i>
                <div class="flex-grow-1 fw-semibold">${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }
        }, 4000);
    }

    updateCartCount() {
        fetch('/urban-thread/api/cart/get.php', { credentials: 'include' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const totalItems = data.cart.reduce((sum, item) => sum + item.quantity, 0);
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) cartCount.textContent = totalItems;
                }
            })
            .catch(() => {
                const cart = JSON.parse(localStorage.getItem('cart')) || [];
                const cartCount = document.getElementById('cart-count');
                if (cartCount) cartCount.textContent = cart.length;
            });
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
}

// Initialize virtual card
let virtualCard;
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing VirtualCard...');
    virtualCard = new VirtualCard();
});

// Make available globally
window.virtualCard = virtualCard;