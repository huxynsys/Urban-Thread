-- ============================================
-- COMPLETE DATABASE SETUP SCRIPT
-- urban_thread_new
-- ============================================

-- Create Database (if not exists)
CREATE DATABASE IF NOT EXISTS urban_thread_new;
USE urban_thread_new;

-- Disable foreign key checks for clean setup
SET FOREIGN_KEY_CHECKS = 0;


-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    avatar VARCHAR(500) DEFAULT NULL,
    google_id VARCHAR(255) DEFAULT NULL,
    auth_provider ENUM('local', 'google') DEFAULT 'local',
    email_verified TINYINT(1) DEFAULT 0,
    notification_preferences TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- ============================================
-- 2. PRODUCTS TABLE
-- ============================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    size VARCHAR(10),
    color VARCHAR(50),
    image VARCHAR(500),
    description TEXT,
    brand VARCHAR(100),
    material VARCHAR(100),
    care VARCHAR(200),
    popularity INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_price (price),
    INDEX idx_featured (is_featured)
);

-- ============================================
-- 3. PRODUCT IMAGES TABLE
-- ============================================
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
);

-- ============================================
-- 4. PRODUCT SIZES TABLE
-- ============================================
CREATE TABLE product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(10) NOT NULL,
    stock_quantity INT DEFAULT 0,
    additional_price DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    UNIQUE KEY unique_product_size (product_id, size)
);

-- ============================================
-- 5. PRODUCT COLORS TABLE
-- ============================================
CREATE TABLE product_colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    color VARCHAR(50) NOT NULL,
    color_code VARCHAR(7),
    stock_quantity INT DEFAULT 0,
    additional_price DECIMAL(10,2) DEFAULT 0,
    image_url VARCHAR(500),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    UNIQUE KEY unique_product_color (product_id, color)
);

-- ============================================
-- 6. PRODUCT SPECIFICATIONS TABLE
-- ============================================
CREATE TABLE product_specs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    spec_name VARCHAR(100) NOT NULL,
    spec_value TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
);

-- ============================================
-- 7. CART TABLE
-- ============================================
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(100) DEFAULT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    selected_size VARCHAR(10),
    selected_color VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    UNIQUE KEY unique_cart_item (user_id, product_id, selected_size, selected_color)
);

-- ============================================
-- 8. ORDERS TABLE
-- ============================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10, 2) NOT NULL,
    shipping DECIMAL(10, 2) DEFAULT 0,
    tax DECIMAL(10, 2) DEFAULT 0,
    discount DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    promo_code VARCHAR(50) DEFAULT NULL,
    payment_method VARCHAR(50),
    shipping_address TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_order_number (order_number)
);

-- ============================================
-- 9. ORDER ITEMS TABLE
-- ============================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    size VARCHAR(10),
    color VARCHAR(50),
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order (order_id)
);

-- ============================================
-- 10. WISHLIST TABLE
-- ============================================
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id),
    INDEX idx_user (user_id)
);

-- ============================================
-- 11. VIRTUAL CARDS TABLE
-- ============================================
CREATE TABLE virtual_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    card_number VARCHAR(50) NOT NULL,
    balance DECIMAL(10, 2) DEFAULT 0,
    points INT DEFAULT 0,
    tier ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    points_multiplier DECIMAL(3, 2) DEFAULT 1.0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

-- ============================================
-- 12. CARD TRANSACTIONS TABLE
-- ============================================
CREATE TABLE card_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES virtual_cards(id) ON DELETE CASCADE,
    INDEX idx_card (card_id),
    INDEX idx_type (type)
);

-- ============================================
-- 13. PROMO CODES TABLE
-- ============================================
CREATE TABLE promo_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10, 2) NOT NULL,
    min_order DECIMAL(10, 2) DEFAULT 0,
    max_discount DECIMAL(10, 2) DEFAULT NULL,
    valid_from DATETIME NOT NULL,
    valid_until DATETIME NOT NULL,
    max_uses INT DEFAULT NULL,
    current_uses INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_valid (valid_from, valid_until)
);

-- ============================================
-- 14. CONTACT MESSAGES TABLE
-- ============================================
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
    admin_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_email (email)
);

-- ============================================
-- 15. FAQ CATEGORIES TABLE
-- ============================================
CREATE TABLE faq_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
);

-- ============================================
-- 16. FAQ ITEMS TABLE
-- ============================================
CREATE TABLE faq_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (category_id) REFERENCES faq_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id)
);

-- ============================================
-- 17. BENEFITS TABLE
-- ============================================
CREATE TABLE benefits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    benefit_key VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    details_json JSON,
    icon VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1
);

-- ============================================
-- 18. LOGIN ATTEMPTS TABLE
-- ============================================
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    success TINYINT(1) DEFAULT 0,
    reason VARCHAR(255),
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempted_at)
);

-- ============================================
-- 19. REMEMBER TOKENS TABLE
-- ============================================
CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);

-- ============================================
-- 20. REGISTRATION LOGS TABLE
-- ============================================
CREATE TABLE registration_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    name VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    success TINYINT(1) DEFAULT 0,
    details VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);

-- ============================================
-- 21. GOOGLE LOGIN LOGS TABLE
-- ============================================
CREATE TABLE google_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    success TINYINT(1) DEFAULT 0,
    details VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);

-- ============================================
-- 22. CHAT LOGS TABLE
-- ============================================
CREATE TABLE chat_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    response TEXT,
    session_id VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id)
);

-- ============================================
-- 23. SITE CONTENT TABLE
-- ============================================
CREATE TABLE site_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page VARCHAR(50) NOT NULL,
    section VARCHAR(50) NOT NULL,
    title VARCHAR(200),
    content TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_page_section (page, section)
);

-- ============================================
-- 24. REVIEWS TABLE
-- ============================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id)
);

-- ============================================
-- 25. EMAIL TEMPLATES TABLE
-- ============================================
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    variables TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_key (template_key)
);

-- ============================================
-- 26. EMAIL LOGS TABLE
-- ============================================
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    email_type VARCHAR(50) NOT NULL,
    recipient_email VARCHAR(100) NOT NULL,
    recipient_name VARCHAR(100),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    error_message TEXT,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_email_type (email_type),
    INDEX idx_recipient (recipient_email)
);

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Insert Admin User (password: admin123)
INSERT INTO users (name, email, password, role, email_verified, created_at)
VALUES ('Admin', 'admin@urbanthread.com', 'admin123', 'admin', 1, NOW());

-- Insert Regular User (password: user123)
INSERT INTO users (name, email, password, role, email_verified, created_at)
VALUES ('John Doe', 'john@example.com', 'user123', 'user', 1, NOW());

-- Insert Sample Products (60 products)
INSERT INTO products (name, category, price, stock, size, color, image, description, is_featured, is_active) VALUES

-- Hoodies (10)
('Urban Hoodie Pro', 'Hoodies', 2200, 25, 'L', 'Black', 'img/hoodie1.jpg', 'Premium urban hoodie with modern design.', 1, 1),
('Classic Zip Hoodie', 'Hoodies', 2400, 30, 'M', 'Gray', 'img/hoodie2.jpg', 'Classic zip-up hoodie with comfortable fit.', 0, 1),
('Oversized Hoodie', 'Hoodies', 2600, 20, 'XL', 'Beige', 'img/hoodie3.jpg', 'Trendy oversized hoodie for relaxed style.', 1, 1),
('Winter Fleece Hoodie', 'Hoodies', 2800, 15, 'L', 'Navy', 'img/hoodie4.jpg', 'Warm fleece-lined hoodie for cold weather.', 0, 1),
('Graphic Print Hoodie', 'Hoodies', 2300, 28, 'M', 'White', 'img/hoodie5.jpg', 'Stylish hoodie with unique graphic print.', 0, 1),
('Essential Hoodie', 'Hoodies', 2000, 40, 'L', 'Black', 'img/hoodie6.jpg', 'Basic essential hoodie for everyday wear.', 0, 1),
('Cropped Hoodie', 'Hoodies', 2100, 22, 'S', 'Pink', 'img/hoodie7.jpg', 'Fashionable cropped hoodie for trendy look.', 0, 1),
('Color Block Hoodie', 'Hoodies', 2500, 18, 'L', 'Multi', 'img/hoodie8.jpg', 'Vibrant color block design hoodie.', 0, 1),
('Pullover Hoodie', 'Hoodies', 2150, 35, 'XL', 'Green', 'img/hoodie9.jpg', 'Classic pullover hoodie with front pocket.', 0, 1),
('Lightweight Hoodie', 'Hoodies', 1900, 45, 'M', 'Blue', 'img/hoodie10.jpg', 'Lightweight hoodie perfect for summer.', 0, 1),

-- Shirts (10)
('Classic Cotton Shirt', 'Shirts', 1500, 30, 'M', 'White', 'img/shirt1.jpg', 'Classic cotton shirt for everyday wear.', 1, 1),
('Oxford Button Down', 'Shirts', 1800, 25, 'L', 'Blue', 'img/shirt2.jpg', 'Premium Oxford shirt with button-down collar.', 0, 1),
('Linen Summer Shirt', 'Shirts', 1900, 28, 'M', 'Beige', 'img/shirt3.jpg', 'Breathable linen shirt perfect for hot weather.', 0, 1),
('Polo Shirt', 'Shirts', 1600, 35, 'L', 'Navy', 'img/shirt4.jpg', 'Classic polo shirt with embroidered logo.', 0, 1),
('Denim Shirt', 'Shirts', 2000, 22, 'XL', 'Blue', 'img/shirt5.jpg', 'Stylish denim shirt for casual occasions.', 0, 1),
('Flannel Shirt', 'Shirts', 1700, 30, 'L', 'Red', 'img/shirt6.jpg', 'Warm flannel shirt perfect for layering.', 0, 1),
('Slim Fit Shirt', 'Shirts', 1650, 32, 'M', 'Gray', 'img/shirt7.jpg', 'Modern slim fit shirt for a sharp look.', 0, 1),
('Hawaiian Shirt', 'Shirts', 1850, 20, 'L', 'Print', 'img/shirt8.jpg', 'Fun tropical print shirt for summer vibes.', 0, 1),
('French Cuff Shirt', 'Shirts', 2100, 15, 'XL', 'Black', 'img/shirt9.jpg', 'Elegant French cuff shirt for formal occasions.', 0, 1),
('Casual Chambray', 'Shirts', 1750, 28, 'M', 'Light Blue', 'img/shirt10.jpg', 'Lightweight chambray shirt for casual wear.', 0, 1),

-- Pants (10)
('Slim Fit Pants', 'Pants', 1800, 35, 'M', 'Navy', 'img/pant1.jpg', 'Slim fit pants with stretch comfort.', 0, 1),
('Cargo Pants', 'Pants', 2100, 32, 'L', 'Khaki', 'img/pant2.jpg', 'Utility cargo pants with multiple pockets.', 0, 1),
('Jogger Pants', 'Pants', 1700, 38, 'M', 'Gray', 'img/pant3.jpg', 'Comfortable jogger pants with elastic cuffs.', 0, 1),
('Chino Pants', 'Pants', 1900, 34, '32', 'Beige', 'img/pant4.jpg', 'Classic chino pants with slim fit.', 0, 1),
('Denim Jeans', 'Pants', 2200, 40, 'L', 'Blue', 'img/pant5.jpg', 'Classic denim jeans with regular fit.', 1, 1),
('Skinny Jeans', 'Pants', 2000, 28, 'M', 'Black', 'img/pant6.jpg', 'Modern skinny fit jeans.', 0, 1),
('Wool Trousers', 'Pants', 2500, 20, 'L', 'Charcoal', 'img/pant7.jpg', 'Premium wool trousers for formal wear.', 0, 1),
('Sweatpants', 'Pants', 1600, 45, 'XL', 'Black', 'img/pant8.jpg', 'Comfortable sweatpants for lounging.', 0, 1),
('Corduroy Pants', 'Pants', 1950, 25, 'M', 'Brown', 'img/pant9.jpg', 'Vintage style corduroy pants.', 0, 1),
('Pleated Pants', 'Pants', 2150, 22, 'L', 'Navy', 'img/pant10.jpg', 'Elegant pleated pants for office wear.', 0, 1),

-- Shoes (10)
('Street Style Sneakers', 'Shoes', 4200, 20, '42', 'Gray', 'img/shoes1.jpg', 'Comfortable street style sneakers.', 1, 1),
('Running Shoes', 'Shoes', 3800, 22, '43', 'Red', 'img/shoes2.jpg', 'Lightweight running shoes with breathable mesh.', 0, 1),
('High Top Sneakers', 'Shoes', 4500, 18, '41', 'White', 'img/shoes3.jpg', 'High top sneakers with ankle support.', 0, 1),
('Slip-on Shoes', 'Shoes', 2900, 24, '42', 'Black', 'img/shoes4.jpg', 'Easy slip-on shoes with memory foam.', 0, 1),
('Leather Boots', 'Shoes', 5500, 15, '43', 'Brown', 'img/shoes5.jpg', 'Premium leather boots for rugged style.', 0, 1),
('Loafers', 'Shoes', 3200, 28, '41', 'Navy', 'img/shoes6.jpg', 'Classic loafers for smart casual look.', 0, 1),
('Sandals', 'Shoes', 1800, 35, '42', 'Brown', 'img/shoes7.jpg', 'Comfortable sandals for summer.', 0, 1),
('Basketball Shoes', 'Shoes', 4900, 16, '44', 'Black', 'img/shoes8.jpg', 'High-performance basketball shoes.', 0, 1),
('Hiking Boots', 'Shoes', 5200, 12, '43', 'Green', 'img/shoes9.jpg', 'Durable hiking boots for outdoor adventures.', 0, 1),
('Espadrilles', 'Shoes', 2100, 30, '40', 'Beige', 'img/shoes10.jpg', 'Casual espadrilles for beach days.', 0, 1),

-- Bags (10)
('Urban Backpack', 'Bags', 2800, 40, 'One Size', 'Black', 'img/bag1.jpg', 'Stylish and durable backpack.', 0, 1),
('Messenger Bag', 'Bags', 2200, 25, 'One Size', 'Brown', 'img/bag2.jpg', 'Classic messenger bag with adjustable strap.', 0, 1),
('Tote Bag', 'Bags', 1500, 50, 'One Size', 'Natural', 'img/bag3.jpg', 'Eco-friendly canvas tote bag.', 0, 1),
('Laptop Backpack', 'Bags', 3200, 28, 'One Size', 'Black', 'img/bag4.jpg', 'Professional laptop backpack with padded compartment.', 0, 1),
('Crossbody Bag', 'Bags', 1200, 45, 'One Size', 'Beige', 'img/bag5.jpg', 'Compact crossbody bag for essentials.', 0, 1),
('Duffel Bag', 'Bags', 3500, 20, 'One Size', 'Navy', 'img/bag6.jpg', 'Spacious duffel bag for gym or travel.', 0, 1),
('Shoulder Bag', 'Bags', 1900, 32, 'One Size', 'Black', 'img/bag7.jpg', 'Elegant shoulder bag for daily use.', 0, 1),
('Clutch Bag', 'Bags', 800, 60, 'One Size', 'Gold', 'img/bag8.jpg', 'Stylish clutch for evening events.', 0, 1),
('Hiking Backpack', 'Bags', 3800, 18, 'One Size', 'Green', 'img/bag9.jpg', 'Durable hiking backpack with hydration pocket.', 0, 1),
('Kids Backpack', 'Bags', 1100, 55, 'One Size', 'Pink', 'img/bag10.jpg', 'Fun kids backpack with cartoon design.', 0, 1),

-- Jackets (10)
('Denim Jacket Premium', 'Jackets', 3500, 15, 'XL', 'Blue', 'img/jacket1.jpg', 'Premium denim jacket with modern fit.', 1, 1),
('Leather Jacket', 'Jackets', 5500, 10, 'L', 'Black', 'img/jacket2.jpg', 'Premium leather jacket with classic biker style.', 0, 1),
('Bomber Jacket', 'Jackets', 3900, 16, 'M', 'Green', 'img/jacket3.jpg', 'Classic bomber jacket with ribbed cuffs.', 0, 1),
('Rain Jacket', 'Jackets', 3200, 20, 'L', 'Yellow', 'img/jacket4.jpg', 'Waterproof rain jacket with hood.', 0, 1),
('Puffer Jacket', 'Jackets', 4800, 14, 'XL', 'Black', 'img/jacket5.jpg', 'Warm puffer jacket for extreme cold.', 0, 1),
('Trench Coat', 'Jackets', 5200, 12, 'L', 'Beige', 'img/jacket6.jpg', 'Classic trench coat for rainy days.', 0, 1),
('Varsity Jacket', 'Jackets', 3600, 18, 'M', 'Black', 'img/jacket7.jpg', 'Retro varsity jacket with wool sleeves.', 0, 1),
('Windbreaker', 'Jackets', 2500, 25, 'L', 'Blue', 'img/jacket8.jpg', 'Lightweight windbreaker for outdoor activities.', 0, 1),
('Fleece Jacket', 'Jackets', 2800, 22, 'XL', 'Gray', 'img/jacket9.jpg', 'Warm fleece jacket for layering.', 0, 1),
('Blazer', 'Jackets', 4500, 15, 'M', 'Navy', 'img/jacket10.jpg', 'Smart blazer for formal occasions.', 0, 1);

-- Insert Promo Codes
INSERT INTO promo_codes (code, discount_type, discount_value, min_order, valid_from, valid_until, max_uses) VALUES
('URBAN10', 'percentage', 10, 1000, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 100),
('WELCOME20', 'percentage', 20, 0, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 50);

-- Insert FAQ Categories
INSERT INTO faq_categories (name, icon, sort_order) VALUES
('Order & Shipping', 'bi-truck', 1),
('Payments', 'bi-credit-card', 2),
('Returns & Exchanges', 'bi-arrow-return-left', 3);

-- Insert FAQ Items
INSERT INTO faq_items (category_id, question, answer, sort_order) VALUES
(1, 'How can I track my order?', 'Our admin team will manually update you about your order status via email or SMS.', 1),
(1, 'What are your shipping charges?', 'Local: Rs. 150 | Nationwide: Rs. 250 | Free on orders above Rs. 5000', 2),
(2, 'What payment methods do you accept?', 'We only accept Urban Card for online payments.', 1),
(3, 'What is your return policy?', '14-day return window. Items must be unworn with original tags.', 1);

-- Insert Benefits
INSERT INTO benefits (benefit_key, title, description, icon) VALUES
('fast-delivery', 'Fast Delivery', 'Get your orders delivered swiftly to your doorstep.', 'bi-truck'),
('easy-returns', 'Easy Returns', '14-day hassle-free returns & exchanges.', 'bi-arrow-repeat'),
('sustainable-fashion', 'Sustainable Fashion', 'Ethically sourced, eco-friendly clothing.', 'bi-leaf');

-- Insert Site Content
INSERT INTO site_content (page, section, title, content) VALUES
('about', 'story', 'Our Story', 'Urban Thread was born from a passion for contemporary fashion.'),
('about', 'mission', 'Mission & Vision', 'To empower self-expression through fashion.'),
('about', 'sustainability', 'Sustainability Values', 'Eco-friendly fabrics, ethical sourcing, sustainable packaging.');

-- Create Virtual Card for John Doe (user_id = 2)
INSERT INTO virtual_cards (user_id, card_number, balance, points, tier) VALUES
(2, '3221 1412 2200 1234', 50.00, 150, 'silver');

-- Insert Sample Card Transaction
INSERT INTO card_transactions (card_id, amount, type, description, status) VALUES
(1, 50.00, 'Welcome Bonus', 'Initial account credit', 'completed');

-- Insert sample reviews
INSERT INTO reviews (product_id, rating, comment) VALUES
(1, 5, 'Amazing hoodie! Very comfortable and fits perfectly.'),
(1, 4, 'Great quality, but runs a bit small.'),
(2, 5, 'Love this shirt! The fabric is soft and breathable.'),
(2, 4, 'Good shirt, nice fit.'),
(3, 5, 'Best jacket I have ever owned!'),
(3, 4, 'Nice jacket, good quality.'),
(4, 5, 'Very comfortable sneakers!'),
(4, 4, 'Good shoes, stylish design.');

-- Insert contact messages sample
INSERT INTO contact_messages (name, email, subject, message, status) VALUES
('John Doe', 'john@example.com', 'Shipping Question', 'How long does shipping take to Lahore?', 'unread'),
('Jane Smith', 'jane@example.com', 'Product Sizing', 'I need help with sizing for hoodies', 'read'),
('Ali Khan', 'ali@example.com', 'Return Policy', 'What is your return policy?', 'unread');

-- Insert Email Templates
INSERT INTO email_templates (template_key, name, subject, body, variables) VALUES
('welcome_email', 'Welcome Email', 'Welcome to Urban Thread!', '<!DOCTYPE html><html><head><style>body { font-family: Arial, sans-serif; }</style></head><body><h1>Welcome {{name}}!</h1><p>Thank you for joining Urban Thread!</p></body></html>', 'name,site_url,points'),
('order_confirmation', 'Order Confirmation', 'Order Confirmation #{{order_number}}', '<!DOCTYPE html><html><head><style>body { font-family: Arial, sans-serif; }</style></head><body><h1>Order Confirmed!</h1><p>Thank you {{name}} for your order.</p></body></html>', 'name,order_number,order_items,total,site_url'),
('contact_auto_reply', 'Contact Auto-reply', 'Thank you for contacting Urban Thread', '<!DOCTYPE html><html><head><style>body { font-family: Arial, sans-serif; }</style></head><body><h1>We received your message</h1><p>Hello {{name}}, we will respond shortly.</p></body></html>', 'name,message,site_url'),
('admin_notification', 'Admin Notification', 'New Contact Message from {{name}}', '<!DOCTYPE html><html><head><style>body { font-family: Arial, sans-serif; }</style></head><body><h1>New Message</h1><p>From: {{name}} ({{email}})</p></body></html>', 'name,email,subject,message,site_url');

-- Insert product sizes for product ID 1
INSERT INTO product_sizes (product_id, size, stock_quantity, additional_price) VALUES
(1, 'S', 10, 0),
(1, 'M', 15, 0),
(1, 'L', 20, 0),
(1, 'XL', 10, 0);

-- Insert product colors for product ID 1
INSERT INTO product_colors (product_id, color, color_code, stock_quantity, additional_price) VALUES
(1, 'Black', '#000000', 25, 0),
(1, 'Gray', '#808080', 15, 0),
(1, 'Navy', '#000080', 10, 0);

-- Insert product images for product ID 1
INSERT INTO product_images (product_id, image_url, is_primary, sort_order) VALUES
(1, 'img/hoodie1.jpg', 1, 1),
(1, 'img/hoodie1_alt1.jpg', 0, 2),
(1, 'img/hoodie1_alt2.jpg', 0, 3);

-- Insert product specs for product ID 1
INSERT INTO product_specs (product_id, spec_name, spec_value, sort_order) VALUES
(1, 'Material', '80% Cotton, 20% Polyester', 1),
(1, 'Care Instructions', 'Machine wash cold, tumble dry low', 2),
(1, 'Fit Type', 'Regular Fit', 3),
(1, 'Season', 'All Season', 4),
(1, 'Brand', 'Urban Thread', 5);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- VERIFICATION QUERIES (Optional - Comment out if not needed)
-- ============================================
-- SELECT COUNT(*) as total_products FROM products;
-- SELECT COUNT(*) as total_users FROM users;
-- SELECT * FROM contact_messages ORDER BY created_at DESC;
-- SELECT * FROM product_images WHERE product_id = 1;
-- SELECT * FROM product_sizes WHERE product_id = 1;
-- SELECT * FROM product_colors WHERE product_id = 1;
-- SELECT * FROM product_specs WHERE product_id = 1;

-- Final confirmation
SELECT 'Database setup completed successfully!' AS status;