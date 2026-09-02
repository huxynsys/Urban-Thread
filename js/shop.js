// js/shop.js - Updated with Database Cart and Wishlist Integration
// Now loads products from MySQL database via PHP API

const PRODUCTS_PER_PAGE = 12;
let products = [];
let filtered = [];
let currentPage = 1;
let totalProducts = 0;
let totalPages = 0;
let wishlistItems = [];
let cartItems = [];
let isLoggedIn = false;

// Debounce helper: batches rapid events (typing, slider drags) into a single
// API call + re-render. Without it the price slider and search box fired a
// full product reload on every pixel/keystroke, which made the product grid blink.
function debounce(fn, wait = 300) {
  let t;
  return function (...args) {
    clearTimeout(t);
    t = setTimeout(() => fn.apply(this, args), wait);
  };
}
const debouncedLoadProducts = debounce(() => loadProductsFromDatabase(), 300);

// Category images mapping
const categoryImages = {
  "Hoodies": "img/cat-hoodie.png",
  "Pants": "img/cat-pants.png",
  "Shirts": "img/cat-shirt.png",
  "Jackets": "img/cat-jacket.png",
  "Shoes": "img/cat-shoes.png",
  "Bags": "img/cat-bags.png"
};

window.addEventListener('DOMContentLoaded', async () => {
  await checkLoginStatus();
  renderCategoryCards();
  await loadCart();
  await loadWishlist();
  await loadProductsFromDatabase();
  setupShopSearchBar();
  setupFilters();
  checkUrlParams();
  setupViewAllButton(); // Added: Setup View All button
});

// ============================================
// VIEW ALL BUTTON FUNCTIONALITY
// ============================================

function setupViewAllButton() {
    const viewAllBtn = document.getElementById('view-all-btn');
    if (viewAllBtn) {
        // Remove disabled attribute and update styling
        viewAllBtn.disabled = false;
        viewAllBtn.innerHTML = '<i class="bi bi-eye me-2"></i>View All Products';
        viewAllBtn.classList.remove('btn-success');
        viewAllBtn.classList.add('btn-primary');
        
        viewAllBtn.addEventListener('click', async () => {
            await loadAllProducts();
        });
    }
}

async function loadAllProducts() {
    const viewAllBtn = document.getElementById('view-all-btn');
    const loadMoreContainer = document.getElementById('load-more-container');
    const viewAllContainer = document.getElementById('view-all-container');
    const loadingIndicator = document.getElementById('loading-indicator');
    
    if (!viewAllBtn) return;
    
    // Show loading state
    const originalText = viewAllBtn.innerHTML;
    viewAllBtn.disabled = true;
    viewAllBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading all products...';
    
    if (loadingIndicator) loadingIndicator.style.display = 'block';
    
    try {
        // Get current filter values
        const category = document.getElementById('filter-category')?.value || '';
        const searchTerm = document.getElementById('shop-search-bar')?.value || '';
        const maxPrice = document.getElementById('filter-price')?.value || 10000;
        const sort = document.getElementById('sort-select')?.value || 'default';
        
        let sortParam = 'default';
        if (sort === 'lowhigh') sortParam = 'price_low';
        if (sort === 'highlow') sortParam = 'price_high';
        if (sort === 'popularity') sortParam = 'popularity';
        if (sort === 'newest') sortParam = 'newest';
        
        // Build URL with large limit to get all products (server cap is 100)
        let url = `/urban-thread/api/products/list.php?limit=100&page=1&max_price=${maxPrice}`;
        if (category) url += `&category=${encodeURIComponent(category)}`;
        if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;
        if (sortParam !== 'default') url += `&sort=${sortParam}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            products = data.products;
            filtered = products;
            totalProducts = data.pagination.total;
            
            // Update product count
            const productCount = document.getElementById('product-count');
            if (productCount) {
                productCount.textContent = `Showing all ${totalProducts} products`;
            }
            
            // Render all products
            renderAllProducts();
            
            // Hide load more and view all containers
            if (loadMoreContainer) loadMoreContainer.style.display = 'none';
            if (viewAllContainer) viewAllContainer.style.display = 'none';
            
            showToast(`Showing all ${totalProducts} products`, 'success');
        } else {
            console.error('Failed to load all products:', data.message);
            showToast('Failed to load all products', 'error');
        }
    } catch (error) {
        console.error('Error loading all products:', error);
        showToast('Error loading products. Please try again.', 'error');
        // Fallback to regular loading
        await loadProductsFromDatabase();
    } finally {
        viewAllBtn.disabled = false;
        viewAllBtn.innerHTML = originalText;
        if (loadingIndicator) loadingIndicator.style.display = 'none';
    }
}

function renderAllProducts() {
    const grid = document.getElementById('product-grid');
    const noProducts = document.getElementById('no-products');
    
    if (!grid) return;
    
    let displayProducts = applyFilters(filtered);
    displayProducts = searchProducts(displayProducts);
    displayProducts = sortProducts(displayProducts);
    
    if (noProducts) {
        noProducts.style.display = displayProducts.length === 0 ? 'block' : 'none';
    }
    
    if (displayProducts.length === 0) {
        grid.innerHTML = '';
        return;
    }
    
    let html = '';
    for (const p of displayProducts) {
        const inWishlist = wishlistItems.includes(p.id);
        const inCart = cartItems.includes(p.id);
        
        html += `
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="product-card h-100">
                    <div class="position-relative">
                        <img src="${p.image || 'img/placeholder.jpg'}" 
                             alt="${p.name}" 
                             class="product-image w-100 mb-2"
                             onerror="this.src='img/placeholder.jpg'"/>
                        <span class="badge bg-dark position-absolute top-0 start-0 m-2">New</span>
                        <span class="wishlist-icon position-absolute top-0 end-0 m-2 ${inWishlist ? 'active' : ''}" 
                               onclick="toggleWishlist(${p.id})" 
                               style="cursor:pointer;">
                            ${inWishlist ? '♥' : '♡'}
                        </span>
                    </div>
                    <div class="product-card-content">
                        <div class="product-name">${escapeHtml(p.name)}</div>
                        <div class="product-price">Rs ${p.price.toLocaleString()}</div>
                        <div class="text-muted small mb-1">${p.category} • ${p.size || 'One Size'} • ${p.color || 'Standard'}</div>
                        <div class="product-card-buttons">
                            <div class="button-row">
                                <button class="btn btn-cart flex-grow-1 ${inCart ? 'added' : ''}" 
                                        onclick="addToCart(${p.id})" 
                                        ${inCart ? 'disabled' : ''}>
                                    ${inCart ? '<i class="bi bi-check-circle me-1"></i>Added' : '<i class="bi bi-cart-plus me-1"></i>Cart'}
                                </button>
                                <a href="product.php?id=${p.id}" class="btn btn-details flex-grow-1">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    // Single assignment (same anti-blink fix as renderProducts)
    grid.innerHTML = html;
}

// Check if user is logged in
async function checkLoginStatus() {
  try {
    const response = await fetch('/urban-thread/api/auth/check.php', {
      credentials: 'include'
    });
    const data = await response.json();
    isLoggedIn = data.logged_in;
  } catch (error) {
    console.error('Error checking login status:', error);
    isLoggedIn = false;
  }
}

// Load cart from database or localStorage
async function loadCart() {
  try {
    if (isLoggedIn) {
      const response = await fetch('/urban-thread/api/cart/get.php', {
        credentials: 'include'
      });
      const data = await response.json();
      if (data.success && data.cart) {
        cartItems = data.cart.map(item => item.product_id || item.id);
      }
    } else {
      const cart = JSON.parse(localStorage.getItem('cart')) || [];
      cartItems = cart.map(item => item.id);
    }
  } catch (error) {
    console.error('Error loading cart:', error);
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    cartItems = cart.map(item => item.id);
  }
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

// Render category cards
function renderCategoryCards() {
  const row = document.getElementById('category-row');
  if (!row) return;
  
  const categories = ["Hoodies", "Pants", "Shirts", "Jackets", "Shoes", "Bags"];
  
  let catHtml = '';
  categories.forEach(cat => {
    catHtml += `
      <div class="col-6 col-md-3">
        <div class="category-card" data-category="${cat}">
          <img src="${categoryImages[cat] || 'img/cat-default.png'}" alt="${cat}" />
          <h5>${cat}</h5>
        </div>
      </div>
    `;
  });
  row.innerHTML = catHtml;
  
  document.querySelectorAll('.category-card').forEach(card => {
    card.addEventListener('click', function() {
      const cat = this.getAttribute('data-category');
      document.getElementById('filter-category').value = cat;
      document.getElementById('shop-search-bar').value = '';
      currentPage = 1;
      // Reset view all button
      const viewAllBtn = document.getElementById('view-all-btn');
      if (viewAllBtn) {
          viewAllBtn.disabled = false;
          viewAllBtn.innerHTML = '<i class="bi bi-eye me-2"></i>View All Products';
          viewAllBtn.classList.remove('btn-success');
          viewAllBtn.classList.add('btn-primary');
      }
      const viewAllContainer = document.getElementById('view-all-container');
      if (viewAllContainer) viewAllContainer.style.display = 'block';
      loadProductsFromDatabase();
      document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
      this.classList.add('active');
    });
  });
}

// Load products from database API
async function loadProductsFromDatabase() {
  try {
    showLoading(true);
    
    const category = document.getElementById('filter-category')?.value || '';
    const searchTerm = document.getElementById('shop-search-bar')?.value || '';
    const maxPrice = document.getElementById('filter-price')?.value || 10000;
    const sort = document.getElementById('sort-select')?.value || 'default';
    
    let sortParam = 'default';
    if (sort === 'lowhigh') sortParam = 'price_low';
    if (sort === 'highlow') sortParam = 'price_high';
    if (sort === 'popularity') sortParam = 'popularity';
    if (sort === 'newest') sortParam = 'newest';
    
    let url = `/urban-thread/api/products/list.php?limit=${PRODUCTS_PER_PAGE}&page=${currentPage}&max_price=${maxPrice}`;
    if (category) url += `&category=${encodeURIComponent(category)}`;
    if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;
    if (sortParam !== 'default') url += `&sort=${sortParam}`;
    
    const response = await fetch(url);
    const data = await response.json();
    
    if (data.success) {
      products = data.products;
      filtered = products;
      totalProducts = data.pagination.total;
      totalPages = data.pagination.total_pages;
      
      updateProductCount();
      renderProducts();
      
      // Show/hide view all button based on total products
      const viewAllContainer = document.getElementById('view-all-container');
      if (viewAllContainer) {
          if (totalProducts > PRODUCTS_PER_PAGE) {
              viewAllContainer.style.display = 'block';
          } else {
              viewAllContainer.style.display = 'none';
          }
      }
    } else {
      console.error('Failed to load products:', data.message);
      loadFallbackProducts();
    }
  } catch (error) {
    console.error('Error loading products:', error);
    loadFallbackProducts();
  } finally {
    showLoading(false);
  }
}

// Fallback function when API fails
function loadFallbackProducts() {
  console.log('Using fallback sample products');
  products = getSampleProducts();
  filtered = products;
  totalProducts = products.length;
  totalPages = Math.ceil(totalProducts / PRODUCTS_PER_PAGE);
  updateProductCount();
  renderProducts();
  
  // Show view all button for fallback
  const viewAllContainer = document.getElementById('view-all-container');
  if (viewAllContainer && totalProducts > PRODUCTS_PER_PAGE) {
      viewAllContainer.style.display = 'block';
  }
}

// Sample products (fallback)
function getSampleProducts() {
  const categories = ["Hoodies", "Pants", "Shirts", "Jackets", "Shoes", "Bags"];
  const sizes = ["S", "M", "L", "XL"];
  const colors = ["Black", "White", "Blue", "Red", "Green", "Gray"];
  let products = [];
  let id = 1;
  
  for (let i = 0; i < 40; i++) {
    const category = categories[i % categories.length];
    let basePrice;
    switch(category) {
      case "Jackets": basePrice = 4200; break;
      case "Hoodies": basePrice = 2200; break;
      default: basePrice = 1800;
    }
    
    products.push({
      id: id++,
      name: `${category} ${Math.floor(i/10) + 1}`,
      category: category,
      price: basePrice + (i * 50),
      size: sizes[i % sizes.length],
      color: colors[i % colors.length],
      image: `img/${category.toLowerCase()}${(i % 10) + 1}.jpg`,
      description: `Premium ${category.toLowerCase()} with modern design.`,
      stock: 25
    });
  }
  
  return products;
}

// Update product count display
function updateProductCount() {
  const productCount = document.getElementById('product-count');
  if (productCount) {
    const start = (currentPage - 1) * PRODUCTS_PER_PAGE + 1;
    const end = Math.min(currentPage * PRODUCTS_PER_PAGE, totalProducts);
    productCount.textContent = `Showing ${start}-${end} of ${totalProducts} products`;
  }
}

// Show/hide loading
function showLoading(show) {
  const loadingIndicator = document.getElementById('loading-indicator');
  if (loadingIndicator) {
    loadingIndicator.style.display = show ? 'flex' : 'none';
  }
}

// Render products grid
function renderProducts() {
  const grid = document.getElementById('product-grid');
  const noProducts = document.getElementById('no-products');
  const loadMoreContainer = document.getElementById('load-more-container');
  
  if (!grid) return;
  
  let displayProducts = applyFilters(filtered);
  displayProducts = searchProducts(displayProducts);
  displayProducts = sortProducts(displayProducts);
  
  const start = (currentPage - 1) * PRODUCTS_PER_PAGE;
  const currentProducts = displayProducts.slice(start, start + PRODUCTS_PER_PAGE);
  
  if (noProducts) {
    noProducts.style.display = displayProducts.length === 0 ? 'block' : 'none';
  }
  
  if (loadMoreContainer) {
    loadMoreContainer.style.display = (start + PRODUCTS_PER_PAGE) < displayProducts.length ? 'block' : 'none';
  }
  
  if (currentProducts.length === 0) {
    grid.innerHTML = '';
    return;
  }
  
  let html = '';
  for (const p of currentProducts) {
    const inWishlist = wishlistItems.includes(p.id);
    const inCart = cartItems.includes(p.id);
    
    html += `
      <div class="col-md-4 col-lg-3 mb-4">
        <div class="product-card h-100">
          <div class="position-relative">
            <img src="${p.image || 'img/placeholder.jpg'}" 
                 alt="${p.name}" 
                 class="product-image w-100 mb-2"
                 onerror="this.src='img/placeholder.jpg'"/>
            <span class="badge bg-dark position-absolute top-0 start-0 m-2">New</span>
            <span class="wishlist-icon position-absolute top-0 end-0 m-2 ${inWishlist ? 'active' : ''}" 
                   onclick="toggleWishlist(${p.id})" 
                   style="cursor:pointer;">
              ${inWishlist ? '♥' : '♡'}
            </span>
          </div>
          <div class="product-card-content">
            <div class="product-name">${escapeHtml(p.name)}</div>
            <div class="product-price">Rs ${p.price.toLocaleString()}</div>
            <div class="text-muted small mb-1">${p.category} • ${p.size || 'One Size'} • ${p.color || 'Standard'}</div>
            <div class="product-card-buttons">
              <div class="button-row">
                <button class="btn btn-cart flex-grow-1 ${inCart ? 'added' : ''}" 
                        onclick="addToCart(${p.id})" 
                        ${inCart ? 'disabled' : ''}>
                  ${inCart ? '<i class="bi bi-check-circle me-1"></i>Added' : '<i class="bi bi-cart-plus me-1"></i>Cart'}
                </button>
                <a href="product.php?id=${p.id}" class="btn btn-details flex-grow-1">
                  <i class="bi bi-eye me-1"></i>View
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }
  // Single assignment: rebuilding with innerHTML += made the browser re-parse
  // the grid (and reload every product image) on each pass, causing the blink
  grid.innerHTML = html;
}

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

// Apply filters
function applyFilters(list) {
  let out = [...list];
  const cat = document.getElementById('filter-category')?.value || '';
  const size = document.getElementById('filter-size')?.value || '';
  const color = document.getElementById('filter-color')?.value || '';
  const price = document.getElementById('filter-price')?.value || 10000;
  
  if (cat) out = out.filter(x => x.category === cat);
  if (size) out = out.filter(x => x.size === size);
  if (color) out = out.filter(x => x.color === color);
  out = out.filter(x => x.price <= parseInt(price));
  
  return out;
}

// Search products
function searchProducts(list) {
  const term = document.getElementById('shop-search-bar')?.value.trim().toLowerCase() || '';
  if (!term) return list;
  
  return list.filter(x => {
    return (x.name && x.name.toLowerCase().includes(term)) ||
           (x.category && x.category.toLowerCase().includes(term)) ||
           (x.description && x.description.toLowerCase().includes(term));
  });
}

// Sort products
function sortProducts(list) {
  const sort = document.getElementById('sort-select')?.value || 'default';
  
  switch(sort) {
    case 'lowhigh':
      return [...list].sort((a, b) => a.price - b.price);
    case 'highlow':
      return [...list].sort((a, b) => b.price - a.price);
    case 'popularity':
      return [...list].sort((a, b) => (b.popularity || 0) - (a.popularity || 0));
    default:
      return list;
  }
}

// Check URL parameters
function checkUrlParams() {
  const urlParams = new URLSearchParams(window.location.search);
  const searchTerm = urlParams.get('search');
  const categoryParam = urlParams.get('category');
  
  if (searchTerm) {
    document.getElementById('shop-search-bar').value = searchTerm;
    loadProductsFromDatabase();
  }
  
  if (categoryParam) {
    document.getElementById('filter-category').value = categoryParam;
    document.querySelectorAll('.category-card').forEach(card => {
      if (card.getAttribute('data-category') === categoryParam) {
        card.classList.add('active');
      } else {
        card.classList.remove('active');
      }
    });
    loadProductsFromDatabase();
  }
}

// Setup search bar
function setupShopSearchBar() {
  const shopSearchBar = document.getElementById('shop-search-bar');
  const clearSearchBtn = document.getElementById('clear-search-btn');
  const resetFiltersBtn = document.getElementById('reset-filters');
  const loadMoreBtn = document.getElementById('load-more-btn');
  
  if (shopSearchBar) {
    shopSearchBar.addEventListener('input', function() {
      currentPage = 1;
      // Reset view all button
      const viewAllBtn = document.getElementById('view-all-btn');
      if (viewAllBtn) {
          viewAllBtn.disabled = false;
          viewAllBtn.innerHTML = '<i class="bi bi-eye me-2"></i>View All Products';
      }
      const viewAllContainer = document.getElementById('view-all-container');
      if (viewAllContainer) viewAllContainer.style.display = 'block';
      debouncedLoadProducts(); // debounced: was reloading per keystroke and blinking
    });
  }
  
  if (clearSearchBtn) {
    clearSearchBtn.addEventListener('click', function() {
      document.getElementById('shop-search-bar').value = '';
      document.getElementById('filter-category').value = '';
      document.getElementById('filter-size').value = '';
      document.getElementById('filter-color').value = '';
      document.getElementById('filter-price').value = 10000;
      document.getElementById('price-max').textContent = 'Rs 10000';
      document.getElementById('sort-select').value = 'default';
      document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
      currentPage = 1;
      // Reset view all button
      const viewAllBtn = document.getElementById('view-all-btn');
      if (viewAllBtn) {
          viewAllBtn.disabled = false;
          viewAllBtn.innerHTML = '<i class="bi bi-eye me-2"></i>View All Products';
      }
      const viewAllContainer = document.getElementById('view-all-container');
      if (viewAllContainer) viewAllContainer.style.display = 'block';
      loadProductsFromDatabase();
    });
  }
  
  if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener('click', function() {
      document.getElementById('filter-category').value = '';
      document.getElementById('filter-size').value = '';
      document.getElementById('filter-color').value = '';
      document.getElementById('filter-price').value = 10000;
      document.getElementById('price-max').textContent = 'Rs 10000';
      document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
      currentPage = 1;
      // Reset view all button
      const viewAllBtn = document.getElementById('view-all-btn');
      if (viewAllBtn) {
          viewAllBtn.disabled = false;
          viewAllBtn.innerHTML = '<i class="bi bi-eye me-2"></i>View All Products';
      }
      const viewAllContainer = document.getElementById('view-all-container');
      if (viewAllContainer) viewAllContainer.style.display = 'block';
      loadProductsFromDatabase();
    });
  }
  
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function() {
      currentPage++;
      loadProductsFromDatabase();
    });
  }
}

// Setup filters
function setupFilters() {
  const filterCategory = document.getElementById('filter-category');
  const filterSize = document.getElementById('filter-size');
  const filterColor = document.getElementById('filter-color');
  const filterPrice = document.getElementById('filter-price');
  const priceMax = document.getElementById('price-max');
  const sortSelect = document.getElementById('sort-select');
  
  if (filterCategory) {
    filterCategory.addEventListener('change', () => {
      currentPage = 1;
      document.getElementById('shop-search-bar').value = '';
      // Reset view all button
      const viewAllBtn = document.getElementById('view-all-btn');
      if (viewAllBtn) {
          viewAllBtn.disabled = false;
          viewAllBtn.innerHTML = '<i class="bi bi-eye me-2"></i>View All Products';
      }
      const viewAllContainer = document.getElementById('view-all-container');
      if (viewAllContainer) viewAllContainer.style.display = 'block';
      document.querySelectorAll('.category-card').forEach(c => {
        if (c.getAttribute('data-category') === filterCategory.value) {
          c.classList.add('active');
        } else {
          c.classList.remove('active');
        }
      });
      loadProductsFromDatabase();
    });
  }
  
  if (filterSize) {
    filterSize.addEventListener('change', () => {
      currentPage = 1;
      renderProducts();
    });
  }
  
  if (filterColor) {
    filterColor.addEventListener('change', () => {
      currentPage = 1;
      renderProducts();
    });
  }
  
  if (filterPrice) {
    filterPrice.addEventListener('input', function() {
      if (priceMax) {
        priceMax.textContent = `Rs ${this.value}`;
      }
      currentPage = 1;
      // Reset view all button
      const viewAllBtn = document.getElementById('view-all-btn');
      if (viewAllBtn) {
          viewAllBtn.disabled = false;
          viewAllBtn.innerHTML = '<i class="bi bi-eye me-2"></i>View All Products';
      }
      const viewAllContainer = document.getElementById('view-all-container');
      if (viewAllContainer) viewAllContainer.style.display = 'block';
      debouncedLoadProducts(); // debounced: was reloading per pixel of slider drag and blinking
    });
  }
  
  if (sortSelect) {
    sortSelect.addEventListener('change', () => {
      currentPage = 1;
      // Reset view all button
      const viewAllBtn = document.getElementById('view-all-btn');
      if (viewAllBtn) {
          viewAllBtn.disabled = false;
          viewAllBtn.innerHTML = '<i class="bi bi-eye me-2"></i>View All Products';
      }
      const viewAllContainer = document.getElementById('view-all-container');
      if (viewAllContainer) viewAllContainer.style.display = 'block';
      loadProductsFromDatabase();
    });
  }
}

// ============================================
// CART FUNCTIONS - DATABASE INTEGRATED
// ============================================

async function addToCart(id) {
  try {
    if (isLoggedIn) {
      const response = await fetch('/urban-thread/api/cart/add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ product_id: id, quantity: 1 })
      });
      
      const data = await response.json();
      
      if (data.success) {
        if (!cartItems.includes(id)) {
          cartItems.push(id);
        }
        showToast('Product added to cart!', 'success');
        const cartCount = document.getElementById('cart-count');
        if (cartCount) cartCount.textContent = data.cart_count;
        renderProducts();
      } else {
        showToast(data.message || 'Failed to add to cart', 'error');
      }
    } else {
      addToCartLocal(id);
    }
  } catch (error) {
    console.error('Error adding to cart:', error);
    addToCartLocal(id);
  }
}

function addToCartLocal(id) {
  let cart = JSON.parse(localStorage.getItem('cart')) || [];
  const prod = products.find(p => p.id === id);
  if (!prod) return;
  
  if (!cart.some(c => c.id === id)) {
    cart.push({ ...prod, quantity: 1 });
    cartItems.push(id);
  } else {
    cart = cart.map(c => c.id === id ? { ...c, quantity: c.quantity + 1 } : c);
  }
  
  localStorage.setItem('cart', JSON.stringify(cart));
  
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
  const cartCount = document.getElementById('cart-count');
  if (cartCount) cartCount.textContent = totalItems;
  
  renderProducts();
  showToast('Product added to cart!', 'success');
}

// ============================================
// WISHLIST FUNCTIONS - DATABASE INTEGRATED
// ============================================

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
            renderProducts();
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
        toggleWishlistFallback(productId);
    }
}

function toggleWishlistFallback(productId) {
    let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    if (wishlist.includes(productId)) {
        wishlist = wishlist.filter(id => id !== productId);
        wishlistItems = wishlist;
        showToast('Removed from wishlist', 'info');
    } else {
        wishlist.push(productId);
        wishlistItems = wishlist;
        showToast('Added to wishlist!', 'success');
    }
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    renderProducts();
}

// ============================================
// TOAST NOTIFICATION
// ============================================

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

// Make functions global
window.addToCart = addToCart;
window.toggleWishlist = toggleWishlist;