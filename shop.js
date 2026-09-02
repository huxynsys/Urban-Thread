// js/shop.js - Updated to use Database API

// Replace the loadProducts function
async function loadProducts() {
  try {
    // Get filter values
    const category = document.getElementById('filter-category')?.value || '';
    const searchTerm = document.getElementById('shop-search-bar')?.value || '';
    const minPrice = 0;
    const maxPrice = document.getElementById('filter-price')?.value || 10000;
    const sort = document.getElementById('sort-select')?.value || 'default';
    const page = currentPage || 1;
    
    // Map sort values to API format
    let sortParam = 'default';
    if (sort === 'lowhigh') sortParam = 'price_low';
    if (sort === 'highlow') sortParam = 'price_high';
    if (sort === 'popularity') sortParam = 'popularity';
    if (sort === 'newest') sortParam = 'newest';
    
    // Build URL
    let url = `/urban-thread/api/products/list.php?limit=${PRODUCTS_PER_PAGE}&page=${page}&max_price=${maxPrice}`;
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
      
      // Store categories from API
      if (data.filters && data.filters.categories) {
        updateCategoryFilters(data.filters.categories);
      }
      
      renderProducts();
      updateProductCount();
    } else {
      console.error('Failed to load products:', data.message);
      // Fallback to sample products
      products = getSampleProducts();
      filtered = products;
      renderProducts();
    }
  } catch (error) {
    console.error('Error loading products:', error);
    // Fallback to sample products
    products = getSampleProducts();
    filtered = products;
    renderProducts();
  }
}

// Update category filter options
function updateCategoryFilters(categories) {
  const filterCategory = document.getElementById('filter-category');
  if (!filterCategory) return;
  
  // Store current value
  const currentValue = filterCategory.value;
  
  // Clear existing options except first
  while (filterCategory.options.length > 1) {
    filterCategory.remove(1);
  }
  
  // Add categories from database
  categories.forEach(cat => {
    const option = document.createElement('option');
    option.value = cat;
    option.textContent = cat;
    filterCategory.appendChild(option);
  });
  
  // Restore selected value if still exists
  if (currentValue && categories.includes(currentValue)) {
    filterCategory.value = currentValue;
  }
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