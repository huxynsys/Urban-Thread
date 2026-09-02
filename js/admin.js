// js/admin.js - Admin page with Database Integration
// Now reads from MySQL database via PHP APIs

// Global variables for filtered data
let allProducts = [];
let currentProductFilter = 'all';
let allMessages = [];

// Admin dashboard initialization
document.addEventListener('DOMContentLoaded', async function() {
  // Check if user is admin via session
  const isAdmin = await checkAdminAccess();
  if (!isAdmin) return;
  
  // Initialize admin dashboard
  await initializeAdminDashboard();
  
  // Setup event listeners
  setupEventListeners();
  
  // Setup sidebar tab switching
  setupAdminSidebar();
  
  // Setup dropdown menu for products only
  setupProductsDropdown();
  
  // Setup product category filters
  setupProductFilters();
  
  // By default, products tab is active - this is already set in HTML
  // (products-tab has class "active")
});

// Check if user has admin access via PHP session
async function checkAdminAccess() {
  try {
    const response = await fetch('/urban-thread/api/auth/check.php', {
      credentials: 'include'
    });
    const data = await response.json();
    
    if (!data.logged_in) {
      alert('Please login to access admin panel.');
      window.location.href = 'reg.php';
      return false;
    }
    
    if (data.user.role !== 'admin') {
      alert('Access denied. Admin privileges required.');
      window.location.href = 'index.php';
      return false;
    }
    
    return true;
  } catch (error) {
    console.error('Error checking admin access:', error);
    // Fallback to localStorage check
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (!currentUser || currentUser.role !== 'admin') {
      alert('Access denied. Admin privileges required.');
      window.location.href = 'index.php';
      return false;
    }
    return true;
  }
}

// Initialize all dashboard components
async function initializeAdminDashboard() {
  // Show loading
  showLoading(true);
  
  // Load dashboard statistics from database
  await loadDashboardStats();
  
  // Load products table from database
  await loadProductsTable();
  
  // Load users table from database
  await loadUsersTable();
  
  // Load orders table from database
  await loadOrdersTable();

  // Load contact messages
  await loadContactMessages();
  
  // Update cart count
  updateCartCount();
  
  // Hide loading
  showLoading(false);
}

// Show/hide loading indicator
function showLoading(show) {
  console.log(show ? 'Loading...' : 'Loading complete');
}

// Setup all event listeners
function setupEventListeners() {
  // Product form submission
  const productForm = document.getElementById('admin-product-form');
  if (productForm) {
    productForm.addEventListener('submit', handleProductSubmit);
  }
  
  // Back to top button
  const btnTop = document.getElementById('btn-top');
  if (btnTop) {
    setupBackToTop();
  }
}

// ============================================
// PRODUCTS DROPDOWN SETUP (only for products)
// ============================================

function setupProductsDropdown() {
    const dropdownToggle = document.querySelector('.sidebar-nav-btn.dropdown-toggle');
    const dropdown = document.getElementById('products-dropdown');
    
    if (dropdownToggle && dropdown) {
        dropdownToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isExpanded = dropdownToggle.getAttribute('aria-expanded') === 'true';
            
            if (isExpanded) {
                dropdown.classList.remove('show');
                dropdownToggle.setAttribute('aria-expanded', 'false');
            } else {
                dropdown.classList.add('show');
                dropdownToggle.setAttribute('aria-expanded', 'true');
            }
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.sidebar-nav-dropdown')) {
            const dropdownMenu = document.getElementById('products-dropdown');
            const toggle = document.querySelector('.sidebar-nav-btn.dropdown-toggle');
            if (dropdownMenu && toggle) {
                dropdownMenu.classList.remove('show');
                toggle.setAttribute('aria-expanded', 'false');
            }
        }
    });
}

// ============================================
// PRODUCT FILTER FUNCTIONS
// ============================================

function setupProductFilters() {
    const filterButtons = document.querySelectorAll('#products-dropdown .dropdown-item-category');
    
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const category = btn.getAttribute('data-category');
            
            // Update active state on buttons
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Set current filter and apply
            currentProductFilter = category;
            applyProductFilter();
            
            // Show filter indicator
            const filterIndicator = document.getElementById('product-filter-indicator');
            const currentFilterText = document.getElementById('current-product-filter');
            if (filterIndicator && currentFilterText) {
                filterIndicator.style.display = 'block';
                currentFilterText.textContent = category === 'all' ? 'All Products' : category;
            }
            
            // Close dropdown after selection (optional)
            const dropdown = document.getElementById('products-dropdown');
            const toggle = document.querySelector('.sidebar-nav-btn.dropdown-toggle');
            if (dropdown && toggle) {
                dropdown.classList.remove('show');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    });
}

function applyProductFilter() {
    if (currentProductFilter === 'all') {
        renderProductsTable(allProducts);
    } else {
        const filteredProducts = allProducts.filter(product => 
            product.category && product.category.toLowerCase() === currentProductFilter.toLowerCase()
        );
        renderProductsTable(filteredProducts);
    }
}

function resetProductFilter() {
    currentProductFilter = 'all';
    
    // Update active state on filter buttons
    const filterButtons = document.querySelectorAll('#products-dropdown .dropdown-item-category');
    filterButtons.forEach(btn => {
        if (btn.getAttribute('data-category') === 'all') {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    // Hide filter indicator
    const filterIndicator = document.getElementById('product-filter-indicator');
    if (filterIndicator) {
        filterIndicator.style.display = 'none';
    }
    
    // Render all products
    renderProductsTable(allProducts);
}

// ============================================
// SIDEBAR TAB SWITCHING FUNCTIONALITY
// ============================================

function setupAdminSidebar() {
    const sidebarBtns = document.querySelectorAll('.sidebar-nav-btn');
    const tabContents = document.querySelectorAll('.admin-tab-content');
    
    if (sidebarBtns.length === 0) return;
    
    sidebarBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            if (!tabId) return;

            // Remove active class from all buttons
            sidebarBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Hide all tab contents
            tabContents.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            const activeTab = document.getElementById(tabId);
            if (activeTab) {
                activeTab.classList.add('active');
            }
        });
    });
}

// ============================================
// DASHBOARD STATISTICS FUNCTIONS
// ============================================

async function loadDashboardStats() {
  try {
    const response = await fetch('/urban-thread/api/admin/stats.php', {
      credentials: 'include'
    });
    const data = await response.json();
    console.log('Stats API response:', data);
    
    if (data.success && data.stats) {
      document.getElementById('admin-users').textContent = data.stats.total_users || 0;
      document.getElementById('admin-orders').textContent = data.stats.total_orders || 0;
      document.getElementById('admin-products').textContent = data.stats.total_products || 0;
      document.getElementById('admin-revenue').textContent = `Rs ${(data.stats.total_revenue || 0).toLocaleString()}`;
    } else {
      console.error('Invalid stats response:', data);
      loadDashboardStatsFallback();
    }
  } catch (error) {
    console.error('Error loading stats:', error);
    loadDashboardStatsFallback();
  }
}

// Fallback when API is not available
function loadDashboardStatsFallback() {
  const users = JSON.parse(localStorage.getItem('users')) || [];
  const orders = JSON.parse(localStorage.getItem('orders')) || [];
  const products = getAdminProductsFallback();
  
  document.getElementById('admin-users').textContent = users.length;
  document.getElementById('admin-orders').textContent = orders.length;
  document.getElementById('admin-products').textContent = products.length;
  
  let totalRevenue = 0;
  orders.forEach(order => {
    if (order.cart && order.cart.length > 0) {
      order.cart.forEach(item => {
        totalRevenue += (item.price || 0) * (item.quantity || 1);
      });
    }
  });
  document.getElementById('admin-revenue').textContent = `Rs ${totalRevenue.toLocaleString()}`;
}

// ============================================
// PRODUCT MANAGEMENT FUNCTIONS
// ============================================

async function loadProductsTable() {
  try {
    const response = await fetch('/urban-thread/api/admin/products/list.php', {
      credentials: 'include'
    });
    const data = await response.json();
    console.log('Products API response:', data);
    
    if (data.success && data.products) {
      allProducts = data.products;
      applyProductFilter();
    } else {
      console.log('API failed, using fallback');
      allProducts = getAdminProductsFallback();
      applyProductFilter();
    }
  } catch (error) {
    console.error('Error loading products:', error);
    allProducts = getAdminProductsFallback();
    applyProductFilter();
  }
}

function renderProductsTable(products) {
  const tableBody = document.getElementById('admin-product-table');
  if (!tableBody) return;
  
  if (!products || products.length === 0) {
    tableBody.innerHTML = `
        <tr>
          <td colspan="5" class="text-center text-muted py-4">
            <i class="bi bi-box-seam display-6"></i>
            <p class="mt-2">No products found</p>
            <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#productModal">
              Add Your First Product
            </button>
          <\/td>
        <\/tr>
    `;
    return;
  }
  
  let tableHTML = '';
  products.forEach(product => {
    const productId = parseInt(product.id);
    
    tableHTML += `
        <tr>
          <td>
          <div class="d-flex align-items-center">
            <img src="${product.image || 'img/default-product.jpg'}" 
                 alt="${product.name}" 
                 class="rounded me-3" 
                 style="width: 50px; height: 50px; object-fit: cover;"
                 onerror="this.src='img/placeholder.jpg'">
            <div>
              <strong>${escapeHtml(product.name)}</strong>
              <div class="text-muted small">${product.color || 'N/A'}, Size: ${product.size || 'N/A'}</div>
            </div>
          </div>
        <\/td>
        <td><span class="badge bg-secondary">${escapeHtml(product.category)}<\/span><\/td>
        <td><strong>Rs ${parseFloat(product.price).toLocaleString()}<\/strong><\/td>
        <td><span class="badge ${product.stock > 10 ? 'bg-success' : 'bg-warning'}">${product.stock} in stock<\/span><\/td>
        <td>
          <button class="btn btn-admin-edit" onclick="editProduct(${productId})">
            <i class="bi bi-pencil"></i> Edit
          </button>
          <button class="btn btn-admin-delete" onclick="deleteProduct(${productId})">
            <i class="bi bi-trash"></i> Delete
          </button>
        <\/td>
       <\/tr>
    `;
  });
  
  tableBody.innerHTML = tableHTML;
}

// Get products from localStorage (fallback)
function getAdminProductsFallback() {
  let products = JSON.parse(localStorage.getItem('adminProducts')) || [];
  
  if (products.length === 0) {
    products = [
      {
        id: 1,
        name: 'Urban Hoodie Pro',
        category: 'Hoodies',
        price: 2200,
        stock: 15,
        size: 'L',
        color: 'Black',
        image: 'img/hoodie1.jpg',
        description: 'Premium urban hoodie with modern design'
      },
      {
        id: 2,
        name: 'Classic Cotton Shirt',
        category: 'Shirts',
        price: 1500,
        stock: 30,
        size: 'M',
        color: 'White',
        image: 'img/shirt1.jpg',
        description: 'Classic cotton shirt for everyday wear'
      },
      {
        id: 3,
        name: 'Urban Sneakers',
        category: 'Shoes',
        price: 3500,
        stock: 20,
        size: '9',
        color: 'Black',
        image: 'img/shoes1.jpg',
        description: 'Comfortable urban sneakers'
      },
      {
        id: 4,
        name: 'Messenger Bag',
        category: 'Bags',
        price: 1800,
        stock: 12,
        size: 'One Size',
        color: 'Brown',
        image: 'img/bag1.jpg',
        description: 'Stylish messenger bag'
      }
    ];
  }
  
  return products;
}

// Handle product form submission
async function handleProductSubmit(event) {
    event.preventDefault();
    
    const productId = document.getElementById('admin-prod-id').value;
    const productName = document.getElementById('admin-prod-name').value;
    const productCategory = document.getElementById('admin-prod-category').value;
    const productPrice = parseFloat(document.getElementById('admin-prod-price').value);
    const productStock = parseInt(document.getElementById('admin-prod-stock').value);
    const productSize = document.getElementById('admin-prod-size').value;
    const productColor = document.getElementById('admin-prod-color').value;
    const productImage = document.getElementById('admin-prod-image').value;
    const productDesc = document.getElementById('admin-prod-desc').value;
    
    if (!productName || !productCategory || !productPrice || isNaN(productPrice)) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }
    
    const productData = {
        id: productId || null,
        name: productName,
        category: productCategory,
        price: productPrice,
        stock: productStock,
        size: productSize,
        color: productColor,
        image: productImage,
        description: productDesc
    };
    
    const submitBtn = document.querySelector('#admin-product-form button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    try {
        const response = await fetch('/urban-thread/api/admin/products/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(productData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            modal.hide();
            document.getElementById('admin-product-form').reset();
            document.getElementById('admin-prod-id').value = '';
            await loadProductsTable();
            await loadDashboardStats();
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error saving product:', error);
        showNotification('Error saving product. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Edit product
async function editProduct(productId) {
    console.log('Edit product called for ID:', productId);
    
    // Search in allProducts (not filtered)
    const targetId = parseInt(productId);
    const product = allProducts.find(p => parseInt(p.id) === targetId);
    
    if (product) {
        fillProductForm(product);
    } else {
        // Fallback to API call
        try {
            const response = await fetch('/urban-thread/api/admin/products/list.php', {
                credentials: 'include'
            });
            const data = await response.json();
            
            if (data.success && data.products) {
                const fallbackProduct = data.products.find(p => parseInt(p.id) === targetId);
                if (fallbackProduct) {
                    fillProductForm(fallbackProduct);
                } else {
                    showNotification('Product not found', 'error');
                }
            } else {
                showNotification('Product not found', 'error');
            }
        } catch (error) {
            console.error('Error in editProduct:', error);
            showNotification('Product not found', 'error');
        }
    }
}

function fillProductForm(product) {
    document.getElementById('admin-prod-id').value = product.id;
    document.getElementById('admin-prod-name').value = product.name;
    document.getElementById('admin-prod-category').value = product.category;
    document.getElementById('admin-prod-price').value = product.price;
    document.getElementById('admin-prod-stock').value = product.stock;
    document.getElementById('admin-prod-size').value = product.size || '';
    document.getElementById('admin-prod-color').value = product.color || '';
    document.getElementById('admin-prod-image').value = product.image || '';
    document.getElementById('admin-prod-desc').value = product.description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

// Delete product
async function deleteProduct(productId) {
  if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
    return;
  }
  
  try {
    let products = getAdminProductsFallback();
    products = products.filter(p => p.id !== productId);
    localStorage.setItem('adminProducts', JSON.stringify(products));
    await loadProductsTable();
    await loadDashboardStats();
    showNotification('Product deleted successfully!', 'success');
  } catch (error) {
    console.error('Error deleting product:', error);
    showNotification('Error deleting product. Please try again.', 'error');
  }
}

// ============================================
// USER MANAGEMENT FUNCTIONS
// ============================================

async function loadUsersTable() {
  try {
    const response = await fetch('/urban-thread/api/admin/users/list.php', {
      credentials: 'include'
    });
    const data = await response.json();
    console.log('Users API response:', data);
    
    if (data.success && data.users) {
      renderUsersTable(data.users);
    } else {
      renderUsersTableFallback();
    }
  } catch (error) {
    console.error('Error loading users:', error);
    renderUsersTableFallback();
  }
}

function renderUsersTable(users) {
  const tableBody = document.getElementById('admin-user-table');
  if (!tableBody) return;
  
  if (!users || users.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="3" class="text-center text-muted py-4">
          <i class="bi bi-people display-6"></i>
          <p class="mt-2">No users registered yet</p>
        <\/td>
      </tr>
    `;
    return;
  }
  
  let tableHTML = '';
  users.forEach(user => {
    const createdDate = user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A';
    tableHTML += `
      <tr>
        <td>
          <strong>${escapeHtml(user.name)}</strong>
          <div class="text-muted small">Joined: ${createdDate}</div>
        <\/td>
        <td>${escapeHtml(user.email)}<\/td>
        <td>
          <span class="role-badge ${user.role === 'admin' ? 'role-admin' : 'role-user'}">
            ${user.role}
          </span>
        <\/td>
      </tr>
    `;
  });
  
  tableBody.innerHTML = tableHTML;
}

function renderUsersTableFallback() {
  const users = JSON.parse(localStorage.getItem('users')) || [];
  renderUsersTable(users);
}

// ============================================
// ORDER MANAGEMENT FUNCTIONS
// ============================================

async function loadOrdersTable() {
  try {
    const response = await fetch('/urban-thread/api/admin/orders/list.php', {
      credentials: 'include'
    });
    const data = await response.json();
    console.log('Orders API response:', data);
    
    if (data.success && data.orders) {
      renderOrdersTable(data.orders);
    } else {
      renderOrdersTableFallback();
    }
  } catch (error) {
    console.error('Error loading orders:', error);
    renderOrdersTableFallback();
  }
}

function renderOrdersTable(orders) {
  const tableBody = document.getElementById('admin-order-table');
  if (!tableBody) return;
  
  if (!orders || orders.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-muted py-4">
          <i class="bi bi-receipt display-6"></i>
          <p class="mt-2">No orders found</p>
        <\/td>
      </tr>
    `;
    return;
  }
  
  let tableHTML = '';
  orders.forEach(order => {
    const orderDate = order.created_at ? new Date(order.created_at).toLocaleDateString() : 'N/A';
    const orderStatus = order.status || 'pending';
    const statusClass = getStatusClass(orderStatus);
    const orderActions = getOrderActionButton(order.id, orderStatus);
    
    tableHTML += `
      <tr>
        <td><strong>#${order.id}</strong><\/td>
        <td>${escapeHtml(order.user_name || 'Guest')}<\/td>
        <td>${orderDate}<\/td>
        <td>
          <span class="status-badge ${statusClass}">
            ${orderStatus}
          </span>
        <\/td>
        <td><strong>Rs ${parseFloat(order.total || 0).toLocaleString()}<\/strong><\/td>
        <td>${orderActions}<\/td>
      </tr>
    `;
  });
  
  tableBody.innerHTML = tableHTML;
}

function getOrderActionButton(orderId, orderStatus) {
  const status = orderStatus.toLowerCase();
  if (status === 'pending' || status === 'processing') {
    return `<button class="btn btn-admin-edit btn-sm" onclick="updateOrderStatus(${orderId}, 'shipped')">Mark Dispatched</button>`;
  }
  if (status === 'shipped') {
    return `<button class="btn btn-admin-edit btn-sm" onclick="updateOrderStatus(${orderId}, 'delivered')">Mark Delivered</button>`;
  }
  return '<span class="text-muted">No action</span>';
}

async function updateOrderStatus(orderId, newStatus) {
  try {
    const response = await fetch('/urban-thread/api/admin/orders/update.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_id: orderId, status: newStatus })
    });

    const data = await response.json();
    if (data.success) {
      showNotification(data.message, 'success');
      await loadOrdersTable();
      await loadDashboardStats();
    } else {
      showNotification(data.message, 'error');
      if (response.status === 401) {
        console.warn('Unauthorized order update attempt');
      }
    }
  } catch (error) {
    console.error('Error updating order status:', error);
    showNotification('Unable to update order status. Please try again.', 'error');

    const orders = JSON.parse(localStorage.getItem('orders')) || [];
    const orderIndex = orders.findIndex(order => parseInt(order.id, 10) === parseInt(orderId, 10));
    if (orderIndex !== -1) {
      orders[orderIndex].status = newStatus;
      localStorage.setItem('orders', JSON.stringify(orders));
      renderOrdersTableFallback();
    }
  }
}

function getStatusClass(status) {
  switch(status.toLowerCase()) {
    case 'processing': return 'status-processing';
    case 'shipped': return 'status-shipped';
    case 'delivered': return 'status-delivered';
    case 'cancelled': return 'status-cancelled';
    default: return 'status-pending';
  }
}

function renderOrdersTableFallback() {
  const orders = JSON.parse(localStorage.getItem('orders')) || [];
  renderOrdersTable(orders);
}

// ============================================
// CONTACT MESSAGES FUNCTIONS
// ============================================

// Load contact messages
async function loadContactMessages() {
    try {
        const response = await fetch('/urban-thread/api/admin/messages/list.php', {
            credentials: 'include'
        });
        const data = await response.json();
        console.log('Messages API response:', data);
        
        const tableBody = document.getElementById('admin-messages-table');
        const unreadBadge = document.getElementById('unread-count');
        
        if (!tableBody) return;
        
        if (unreadBadge && data.unread_count) {
            if (data.unread_count > 0) {
                unreadBadge.textContent = data.unread_count;
                unreadBadge.style.display = 'inline-block';
            } else {
                unreadBadge.style.display = 'none';
            }
        }
        
        if (!data.success || !data.messages || data.messages.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-inbox display-6"></i>
                        <p class="mt-2">No messages yet</p>
                    <\/td>
                </tr>
            `;
            return;
        }
        
        allMessages = data.messages;
        renderMessagesTable(allMessages);
    } catch (error) {
        console.error('Error loading messages:', error);
        const tableBody = document.getElementById('admin-messages-table');
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger py-4">
                        Error loading messages. Please refresh.
                    <\/td>
                </tr>
            `;
        }
    }
}

function renderMessagesTable(messages) {
    const tableBody = document.getElementById('admin-messages-table');
    
    if (!tableBody) return;
    
    if (!messages || messages.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-inbox display-6"></i>
                    <p class="mt-2">No messages found</p>
                <\/td>
            </tr>
        `;
        return;
    }
    
    tableBody.innerHTML = messages.map(msg => `
        <tr class="${msg.status === 'unread' ? 'table-warning' : ''}">
            <td><small>${msg.created_at_formatted || msg.created_at || 'N/A'}</small><\/td>
            <td><strong>${escapeHtml(msg.name)}</strong><\/td>
            <td><small>${escapeHtml(msg.email)}</small><\/td>
            <td>${escapeHtml(msg.subject)}<\/td>
            <td>
                <span class="badge ${getMessageStatusBadgeClass(msg.status)}">
                    ${msg.status}
                </span>
            <\/td>
            <td>
                <button class="btn btn-sm btn-info" onclick="viewMessage(${parseInt(msg.id)})">
                    <i class="bi bi-eye"></i> View
                </button>
            <\/td>
        </tr>
    `).join('');
}

function getMessageStatusBadgeClass(status) {
    switch(status) {
        case 'unread': return 'bg-danger';
        case 'read': return 'bg-secondary';
        case 'replied': return 'bg-success';
        default: return 'bg-secondary';
    }
}

// View message details
async function viewMessage(messageId) {
    const id = parseInt(messageId);
    console.log('View message clicked for ID:', id);
    
    // Search in allMessages array
    const message = allMessages.find(m => parseInt(m.id) === id);
    
    if (message) {
        displayMessageModal(message);
    } else {
        // Fallback to API call
        try {
            const response = await fetch('/urban-thread/api/admin/messages/list.php', {
                credentials: 'include'
            });
            const data = await response.json();
            
            if (data.success && data.messages) {
                const foundMessage = data.messages.find(m => parseInt(m.id) === id);
                if (foundMessage) {
                    displayMessageModal(foundMessage);
                } else {
                    showNotification(`Message with ID ${id} not found`, 'error');
                }
            } else {
                showNotification('Failed to load message', 'error');
            }
        } catch (error) {
            console.error('Error viewing message:', error);
            showNotification('Error loading message: ' + error.message, 'error');
        }
    }
}

function displayMessageModal(message) {
    const existingModal = document.getElementById('messageModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modalHtml = `
        <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="messageModalLabel">
                            <i class="bi bi-envelope me-2"></i>Message from ${escapeHtml(message.name)}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Name:</strong> ${escapeHtml(message.name)}
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong> ${escapeHtml(message.email)}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Subject:</strong> ${escapeHtml(message.subject)}
                            </div>
                            <div class="col-md-6">
                                <strong>Date:</strong> ${message.created_at_formatted || message.created_at || 'N/A'}
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <strong>Message:</strong>
                            <div class="bg-light p-3 rounded mt-2" style="white-space: pre-wrap;">${escapeHtml(message.message)}</div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="fw-bold" for="modal-admin-notes">Admin Notes</label>
                            <textarea class="form-control mt-2" id="modal-admin-notes" rows="3">${escapeHtml(message.admin_notes || '')}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold" for="modal-message-status">Update Status</label>
                            <select class="form-select mt-2" id="modal-message-status">
                                <option value="unread" ${message.status === 'unread' ? 'selected' : ''}>Unread</option>
                                <option value="read" ${message.status === 'read' ? 'selected' : ''}>Read</option>
                                <option value="replied" ${message.status === 'replied' ? 'selected' : ''}>Replied</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="btn-save-message">
                            <i class="bi bi-save me-1"></i>Save Changes
                        </button>
                        <button type="button" class="btn btn-success" onclick="replyToMessage('${escapeHtml(message.email)}', '${escapeHtml(message.subject)}')">
                            <i class="bi bi-reply me-1"></i>Reply via Email
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modalElement = document.getElementById('messageModal');
    const modal = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true
    });
    
    modal.show();
    
    document.getElementById('btn-save-message').addEventListener('click', function() {
        updateMessage(message.id);
    });
    
    modalElement.addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
    
    // Mark as read if unread
    if (message.status === 'unread') {
        updateMessageStatus(message.id, 'read').then(() => {
            loadContactMessages();
        });
    }
}

// Update message
async function updateMessage(messageId) {
    console.log('Updating message ID:', messageId);
    const statusSelect = document.getElementById('modal-message-status');
    const adminNotesTextarea = document.getElementById('modal-admin-notes');

    const status = statusSelect ? statusSelect.value : 'read';
    const adminNotes = adminNotesTextarea ? adminNotesTextarea.value : '';

    try {
        const response = await fetch('/urban-thread/api/admin/messages/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ 
                message_id: parseInt(messageId), 
                status: status, 
                admin_notes: adminNotes 
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON:', text);
            throw new Error('Invalid server response');
        }
        
        if (data.success) {
            showNotification('Message updated successfully!', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('messageModal'));
            if (modal) {
                modal.hide();
                document.getElementById('messageModal').addEventListener('hidden.bs.modal', function () {
                    this.remove();
                }, { once: true });
            }
            await loadContactMessages();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error updating message:', error);
        showNotification('Error: ' + error.message, 'error');
    }
}

// Update message status only
async function updateMessageStatus(messageId, status) {
    console.log('Updating message status:', messageId, status);
    try {
        const response = await fetch('/urban-thread/api/admin/messages/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ 
                message_id: parseInt(messageId), 
                status: status 
            })
        });

        if (!response.ok) {
            console.error('HTTP error:', response.status);
            return false;
        }

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON:', text);
            return false;
        }
        
        return data.success;
    } catch (error) {
        console.error('Error updating status:', error);
        return false;
    }
}

function replyToMessage(email, subject) {
    window.location.href = `mailto:${email}?subject=Re: ${subject}`;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

// Escape HTML to prevent XSS
function escapeHtml(str) {
  if (!str) return '';
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// Show notification
function showNotification(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
  toast.style.cssText = `
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 9999;
    min-width: 250px;
    max-width: 300px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-radius: 8px;
    animation: slideInRight 0.3s ease;
  `;
  
  toast.innerHTML = `
    <div class="d-flex align-items-center">
      <i class="bi ${type === 'success' ? 'bi-check-circle' : type === 'error' ? 'bi-exclamation-triangle' : 'bi-info-circle'} me-2"></i>
      <span>${message}</span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
  `;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    if (toast.parentNode) {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 300);
    }
  }, 3000);
}

// Setup back to top button
function setupBackToTop() {
  const btnTop = document.getElementById('btn-top');
  if (!btnTop) return;
  
  window.addEventListener('scroll', () => {
    btnTop.style.display = (window.scrollY > 200) ? 'flex' : 'none';
  });
  
  btnTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  
  btnTop.style.display = 'none';
}

// Update cart count
function updateCartCount() {
  const cartData = JSON.parse(localStorage.getItem('cart')) || [];
  const cartCount = document.getElementById('cart-count');
  if (cartCount) {
    cartCount.textContent = cartData.length;
  }
}

// Add CSS animation keyframes
if (!document.querySelector('#admin-animation-styles')) {
  const style = document.createElement('style');
  style.id = 'admin-animation-styles';
  style.textContent = `
    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(100%);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
  `;
  document.head.appendChild(style);
}

// Make functions globally available
window.editProduct = editProduct;
window.deleteProduct = deleteProduct;
window.viewMessage = viewMessage;
window.updateMessage = updateMessage;
window.replyToMessage = replyToMessage;
window.loadContactMessages = loadContactMessages;
window.resetProductFilter = resetProductFilter;