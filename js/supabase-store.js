(function () {
  function getConfig() {
    return window.UT_CONFIG || {};
  }

  function isConfigured() {
    const { supabaseUrl, supabaseAnonKey } = getConfig();
    return Boolean(
      supabaseUrl &&
      supabaseAnonKey &&
      supabaseUrl !== 'https://YOUR_PROJECT_REF.supabase.co' &&
      supabaseAnonKey !== 'YOUR_SUPABASE_ANON_KEY'
    );
  }

  function getClient() {
    if (!window.UrbanThreadSupabase || !window.UrbanThreadSupabase.isConfigured) {
      return null;
    }

    try {
      return window.UrbanThreadSupabase.getClient();
    } catch (error) {
      console.warn('Supabase store unavailable:', error.message);
      return null;
    }
  }

  function normalizeProduct(row = {}) {
    const image = row.image || row.product_image || row.image_url || 'img/placeholder.jpg';
    return {
      id: row.id,
      name: row.name || 'Untitled Product',
      category: row.category || 'General',
      price: Number(row.price || 0),
      stock: Number(row.stock || 0),
      size: row.size || 'One Size',
      color: row.color || 'Standard',
      image,
      description: row.description || '',
      popularity: Number(row.popularity || 0),
      is_featured: Boolean(row.is_featured),
      created_at: row.created_at || new Date().toISOString()
    };
  }

  function getLocalCart() {
    try {
      return JSON.parse(localStorage.getItem('cart')) || [];
    } catch (error) {
      return [];
    }
  }

  function getLocalWishlist() {
    try {
      return JSON.parse(localStorage.getItem('wishlist')) || [];
    } catch (error) {
      return [];
    }
  }

  async function listProducts(options = {}) {
    const supabase = getClient();
    if (!supabase) {
      return { success: false, message: 'Supabase is not configured.' };
    }

    try {
      const {
        category = '',
        search = '',
        page = 1,
        limit = 12,
        maxPrice = 20000,
        sort = 'default'
      } = options;

      let query = supabase
        .from('products')
        .select('*', { count: 'exact' })
        .eq('is_active', true);

      if (category) {
        query = query.eq('category', category);
      }

      if (Number(maxPrice) > 0) {
        query = query.lte('price', Number(maxPrice));
      }

      if (search) {
        const escaped = search.trim().replace(/'/g, "''");
        query = query.or(`name.ilike.%${escaped}%,description.ilike.%${escaped}%`);
      }

      switch (sort) {
        case 'price_low':
          query = query.order('price', { ascending: true });
          break;
        case 'price_high':
          query = query.order('price', { ascending: false });
          break;
        case 'popularity':
          query = query.order('popularity', { ascending: false });
          break;
        case 'newest':
          query = query.order('created_at', { ascending: false });
          break;
        default:
          query = query.order('created_at', { ascending: false });
          break;
      }

      const from = (page - 1) * limit;
      const to = from + limit - 1;

      const { data, error, count } = await query.range(from, to);
      if (error) {
        throw error;
      }

      const normalized = (data || []).map(normalizeProduct);
      const total = Number(count || normalized.length || 0);

      return {
        success: true,
        products: normalized,
        pagination: {
          total,
          page,
          limit,
          total_pages: Math.max(Math.ceil(total / limit), 1)
        }
      };
    } catch (error) {
      console.error('Supabase product list error:', error);
      return { success: false, message: error.message || 'Unable to load products.' };
    }
  }

  async function getProduct(productId) {
    const supabase = getClient();
    if (!supabase) {
      return { success: false, message: 'Supabase is not configured.' };
    }

    try {
      const { data, error } = await supabase
        .from('products')
        .select('*')
        .eq('id', productId)
        .maybeSingle();

      if (error) {
        throw error;
      }

      return { success: true, product: data ? normalizeProduct(data) : null };
    } catch (error) {
      console.error('Supabase product lookup error:', error);
      return { success: false, message: error.message || 'Unable to load product.' };
    }
  }

  async function saveProduct(product) {
    const supabase = getClient();
    if (!supabase) {
      return { success: false, message: 'Supabase is not configured.' };
    }

    try {
      const payload = {
        ...product,
        price: Number(product.price || 0),
        stock: Number(product.stock || 0),
        updated_at: new Date().toISOString()
      };

      let result;
      if (product.id) {
        result = await supabase.from('products').update(payload).eq('id', product.id).select();
      } else {
        const { data: createdData, error: insertError } = await supabase.from('products').insert([payload]).select();
        if (insertError) throw insertError;
        result = { data: createdData };
      }

      const { data, error } = result;
      if (error) {
        throw error;
      }

      return { success: true, product: data && data[0] ? normalizeProduct(data[0]) : payload };
    } catch (error) {
      console.error('Supabase save product error:', error);
      return { success: false, message: error.message || 'Unable to save product.' };
    }
  }

  async function deleteProduct(productId) {
    const supabase = getClient();
    if (!supabase) {
      return { success: false, message: 'Supabase is not configured.' };
    }

    try {
      const { error } = await supabase.from('products').delete().eq('id', productId);
      if (error) {
        throw error;
      }
      return { success: true };
    } catch (error) {
      console.error('Supabase delete product error:', error);
      return { success: false, message: error.message || 'Unable to delete product.' };
    }
  }

  async function getCartItems() {
    if (!isConfigured()) {
      return getLocalCart();
    }

    const supabase = getClient();
    if (!supabase) {
      return getLocalCart();
    }

    try {
      const user = await window.UrbanThreadSupabase.getCurrentUser();
      if (!user || !user.user) {
        return getLocalCart();
      }

      const { data, error } = await supabase
        .from('cart')
        .select('*, products(*)')
        .eq('user_id', user.user.id);

      if (error) {
        throw error;
      }

      return (data || []).map(item => ({
        id: item.product_id,
        product_id: item.product_id,
        quantity: Number(item.quantity || 0),
        name: item.products?.name || 'Product',
        price: Number(item.products?.price || 0),
        image: item.products?.image || 'img/placeholder.jpg',
        category: item.products?.category || 'General',
        selected_size: item.selected_size || null,
        selected_color: item.selected_color || null
      }));
    } catch (error) {
      console.warn('Supabase cart load error, falling back to localStorage:', error);
      return getLocalCart();
    }
  }

  async function addCartItem(productId, quantity = 1, size = null, color = null) {
    const localCart = getLocalCart();
    if (!isConfigured()) {
      const productKey = `${productId}:${size || 'n'}:${color || 'n'}`;
      const itemIndex = localCart.findIndex(item => `${item.id}:${item.selected_size || 'n'}:${item.selected_color || 'n'}` === productKey);

      if (itemIndex >= 0) {
        localCart[itemIndex].quantity += Number(quantity || 1);
      } else {
        const product = await getProduct(productId);
        const existing = product.success && product.product ? product.product : { id: productId, name: 'Product', price: 0, image: 'img/placeholder.jpg', category: 'General' };
        localCart.push({ ...existing, quantity: Number(quantity || 1), selected_size: size, selected_color: color });
      }

      localStorage.setItem('cart', JSON.stringify(localCart));
      return { success: true, local_only: true, cart: localCart };
    }

    const supabase = getClient();
    if (!supabase) {
      return { success: false, message: 'Supabase is not configured.' };
    }

    try {
      const currentUser = await window.UrbanThreadSupabase.getCurrentUser();
      if (!currentUser || !currentUser.user) {
        const fallbackCart = getLocalCart();
        const productKey = `${productId}:${size || 'n'}:${color || 'n'}`;
        const itemIndex = fallbackCart.findIndex(item => `${item.id}:${item.selected_size || 'n'}:${item.selected_color || 'n'}` === productKey);
        if (itemIndex >= 0) {
          fallbackCart[itemIndex].quantity += Number(quantity || 1);
        } else {
          const product = await getProduct(productId);
          const existing = product.success && product.product ? product.product : { id: productId, name: 'Product', price: 0, image: 'img/placeholder.jpg', category: 'General' };
          fallbackCart.push({ ...existing, quantity: Number(quantity || 1), selected_size: size, selected_color: color });
        }
        localStorage.setItem('cart', JSON.stringify(fallbackCart));
        return { success: true, local_only: true, cart: fallbackCart };
      }

      const { data, error } = await supabase
        .from('cart')
        .upsert({
          user_id: currentUser.user.id,
          product_id: productId,
          quantity: Number(quantity || 1),
          selected_size: size || null,
          selected_color: color || null
        }, { onConflict: 'user_id,product_id,selected_size,selected_color' })
        .select();

      if (error) {
        throw error;
      }

      return { success: true, local_only: false, cart: data || [] };
    } catch (error) {
      console.warn('Supabase cart add error, using localStorage fallback:', error);
      const fallbackCart = getLocalCart();
      const productKey = `${productId}:${size || 'n'}:${color || 'n'}`;
      const itemIndex = fallbackCart.findIndex(item => `${item.id}:${item.selected_size || 'n'}:${item.selected_color || 'n'}` === productKey);
      if (itemIndex >= 0) {
        fallbackCart[itemIndex].quantity += Number(quantity || 1);
      } else {
        const product = await getProduct(productId);
        const existing = product.success && product.product ? product.product : { id: productId, name: 'Product', price: 0, image: 'img/placeholder.jpg', category: 'General' };
        fallbackCart.push({ ...existing, quantity: Number(quantity || 1), selected_size: size, selected_color: color });
      }
      localStorage.setItem('cart', JSON.stringify(fallbackCart));
      return { success: true, local_only: true, cart: fallbackCart };
    }
  }

  async function updateCartQuantity(productId, quantity) {
    const localCart = getLocalCart();
    if (!isConfigured()) {
      const nextCart = localCart.map(item => item.id === Number(productId) ? { ...item, quantity: Math.max(0, Number(quantity || 0)) } : item).filter(item => item.quantity > 0);
      localStorage.setItem('cart', JSON.stringify(nextCart));
      return { success: true, local_only: true, cart: nextCart };
    }

    const supabase = getClient();
    if (!supabase) {
      return { success: false, message: 'Supabase is not configured.' };
    }

    try {
      const currentUser = await window.UrbanThreadSupabase.getCurrentUser();
      if (!currentUser || !currentUser.user) {
        const nextCart = localCart.map(item => item.id === Number(productId) ? { ...item, quantity: Math.max(0, Number(quantity || 0)) } : item).filter(item => item.quantity > 0);
        localStorage.setItem('cart', JSON.stringify(nextCart));
        return { success: true, local_only: true, cart: nextCart };
      }

      const { error } = await supabase
        .from('cart')
        .update({ quantity: Number(quantity || 0) })
        .eq('user_id', currentUser.user.id)
        .eq('product_id', productId);

      if (error) {
        throw error;
      }
      return { success: true };
    } catch (error) {
      console.warn('Supabase cart update error, using localStorage fallback:', error);
      const nextCart = localCart.map(item => item.id === Number(productId) ? { ...item, quantity: Math.max(0, Number(quantity || 0)) } : item).filter(item => item.quantity > 0);
      localStorage.setItem('cart', JSON.stringify(nextCart));
      return { success: true, local_only: true, cart: nextCart };
    }
  }

  async function removeCartItem(productId) {
    const localCart = getLocalCart();
    if (!isConfigured()) {
      const nextCart = localCart.filter(item => Number(item.id) !== Number(productId));
      localStorage.setItem('cart', JSON.stringify(nextCart));
      return { success: true, local_only: true, cart: nextCart };
    }

    const supabase = getClient();
    if (!supabase) {
      return { success: false, message: 'Supabase is not configured.' };
    }

    try {
      const currentUser = await window.UrbanThreadSupabase.getCurrentUser();
      if (!currentUser || !currentUser.user) {
        const nextCart = localCart.filter(item => Number(item.id) !== Number(productId));
        localStorage.setItem('cart', JSON.stringify(nextCart));
        return { success: true, local_only: true, cart: nextCart };
      }

      const { error } = await supabase
        .from('cart')
        .delete()
        .eq('user_id', currentUser.user.id)
        .eq('product_id', productId);

      if (error) {
        throw error;
      }
      return { success: true };
    } catch (error) {
      console.warn('Supabase cart remove error, using localStorage fallback:', error);
      const nextCart = localCart.filter(item => Number(item.id) !== Number(productId));
      localStorage.setItem('cart', JSON.stringify(nextCart));
      return { success: true, local_only: true, cart: nextCart };
    }
  }

  async function getWishlistItems() {
    if (!isConfigured()) {
      return getLocalWishlist();
    }

    const supabase = getClient();
    if (!supabase) {
      return getLocalWishlist();
    }

    try {
      const currentUser = await window.UrbanThreadSupabase.getCurrentUser();
      if (!currentUser || !currentUser.user) {
        return getLocalWishlist();
      }

      const { data, error } = await supabase
        .from('wishlist')
        .select('product_id')
        .eq('user_id', currentUser.user.id);

      if (error) {
        throw error;
      }

      return (data || []).map(item => item.product_id);
    } catch (error) {
      console.warn('Supabase wishlist error, using localStorage fallback:', error);
      return getLocalWishlist();
    }
  }

  async function listOrders() {
    const supabase = getClient();
    if (!supabase) {
      return { success: false, message: 'Supabase is not configured.' };
    }

    try {
      const currentUser = await window.UrbanThreadSupabase.getCurrentUser();
      if (!currentUser || !currentUser.user) {
        return { success: false, message: 'Please login to view orders.' };
      }

      const { data, error } = await supabase
        .from('orders')
        .select('*')
        .eq('user_id', currentUser.user.id)
        .order('created_at', { ascending: false });

      if (error) {
        throw error;
      }

      return { success: true, orders: data || [] };
    } catch (error) {
      console.error('Supabase orders list error:', error);
      return { success: false, message: error.message || 'Unable to load orders.' };
    }
  }

  async function createOrder(orderPayload = {}) {
    const supabase = getClient();
    if (!supabase) {
      return { success: false, message: 'Supabase is not configured.' };
    }

    try {
      const currentUser = await window.UrbanThreadSupabase.getCurrentUser();
      if (!currentUser || !currentUser.user) {
        return { success: false, message: 'Please login to place an order.' };
      }

      const cartItems = await getCartItems();
      if (!Array.isArray(cartItems) || cartItems.length === 0) {
        return { success: false, message: 'Your cart is empty.' };
      }

      const subtotal = cartItems.reduce((sum, item) => sum + Number(item.price || 0) * Number(item.quantity || 0), 0);
      const shipping = subtotal > 3000 ? 0 : 250;
      const tax = subtotal * 0.08;
      const total = subtotal + shipping + tax;
      const orderNumber = `ORD-${Date.now()}-${Math.random().toString(36).slice(2, 8).toUpperCase()}`;
      const shippingAddress = {
        full_name: orderPayload.full_name,
        email: orderPayload.email,
        phone: orderPayload.phone,
        address: orderPayload.address,
        city: orderPayload.city,
        state: orderPayload.state,
        zip: orderPayload.zip
      };

      const { data: orderData, error: orderError } = await supabase
        .from('orders')
        .insert([{
          user_id: currentUser.user.id,
          order_number: orderNumber,
          status: 'pending',
          subtotal,
          shipping,
          tax,
          total,
          payment_method: orderPayload.payment_method || 'cod',
          shipping_address: shippingAddress
        }])
        .select();

      if (orderError) {
        throw orderError;
      }

      const order = orderData && orderData[0] ? orderData[0] : null;
      if (!order) {
        return { success: false, message: 'Order could not be created.' };
      }

      const itemsPayload = cartItems.map(item => ({
        order_id: order.id,
        product_id: Number(item.product_id ?? item.id),
        product_name: item.name || 'Product',
        product_price: Number(item.price || 0),
        quantity: Number(item.quantity || 1),
        size: item.selected_size || null,
        color: item.selected_color || null,
        subtotal: Number(item.price || 0) * Number(item.quantity || 1)
      }));

      const { error: itemsError } = await supabase.from('order_items').insert(itemsPayload);
      if (itemsError) {
        throw itemsError;
      }

      if (orderPayload.payment_method === 'urban_card') {
        const { data: cardData, error: cardError } = await supabase
          .from('virtual_cards')
          .select('*')
          .eq('user_id', currentUser.user.id)
          .maybeSingle();

        if (cardError) {
          throw cardError;
        }

        if (cardData && Number(cardData.balance) >= Number(total)) {
          const nextBalance = Number(cardData.balance) - Number(total);
          const { error: deductError } = await supabase
            .from('virtual_cards')
            .update({ balance: nextBalance })
            .eq('user_id', currentUser.user.id);

          if (deductError) {
            throw deductError;
          }
        }
      }

      const { error: clearCartError } = await supabase.from('cart').delete().eq('user_id', currentUser.user.id);
      if (clearCartError) {
        throw clearCartError;
      }

      localStorage.removeItem('cart');
      return { success: true, order_id: order.id, order_number: order.order_number };
    } catch (error) {
      console.error('Supabase create order error:', error);
      return { success: false, message: error.message || 'Unable to create order.' };
    }
  }

  async function toggleWishlistItem(productId) {
    if (!isConfigured()) {
      const wishlist = getLocalWishlist();
      const exists = wishlist.includes(productId);
      const next = exists ? wishlist.filter(item => item !== productId) : [...wishlist, productId];
      localStorage.setItem('wishlist', JSON.stringify(next));
      return { success: true, action: exists ? 'removed' : 'added', items: next };
    }

    const supabase = getClient();
    if (!supabase) {
      return { success: false, message: 'Supabase is not configured.' };
    }

    try {
      const currentUser = await window.UrbanThreadSupabase.getCurrentUser();
      if (!currentUser || !currentUser.user) {
        const wishlist = getLocalWishlist();
        const exists = wishlist.includes(productId);
        const next = exists ? wishlist.filter(item => item !== productId) : [...wishlist, productId];
        localStorage.setItem('wishlist', JSON.stringify(next));
        return { success: true, action: exists ? 'removed' : 'added', items: next };
      }

      const current = await getWishlistItems();
      const alreadyAdded = current.includes(productId);

      if (alreadyAdded) {
        const { error } = await supabase
          .from('wishlist')
          .delete()
          .eq('user_id', currentUser.user.id)
          .eq('product_id', productId);
        if (error) throw error;
        return { success: true, action: 'removed' };
      }

      const { error } = await supabase
        .from('wishlist')
        .insert([{ user_id: currentUser.user.id, product_id: productId }])
        .select();

      if (error) throw error;
      return { success: true, action: 'added' };
    } catch (error) {
      console.warn('Supabase wishlist toggle error, using localStorage fallback:', error);
      const wishlist = getLocalWishlist();
      const exists = wishlist.includes(productId);
      const next = exists ? wishlist.filter(item => item !== productId) : [...wishlist, productId];
      localStorage.setItem('wishlist', JSON.stringify(next));
      return { success: true, action: exists ? 'removed' : 'added', items: next };
    }
  }

  window.UrbanThreadSupabaseStore = {
    isConfigured,
    listProducts,
    getProduct,
    saveProduct,
    deleteProduct,
    getCartItems,
    addCartItem,
    updateCartQuantity,
    removeCartItem,
    getWishlistItems,
    listOrders,
    createOrder,
    toggleWishlistItem,
    getLocalCart,
    getLocalWishlist
  };
})();
