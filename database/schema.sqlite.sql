-- E-Commerce Database Schema (SQLite)
-- Applied automatically on first run; can also be run manually:
--   sqlite3 database/ecommerce.db < database/schema.sqlite.sql

PRAGMA foreign_keys = ON;

-- 1. Admins
CREATE TABLE Admins (
    admin_id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- 2. Categories
CREATE TABLE Categories (
    category_id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_name TEXT NOT NULL,
    description TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- 3. Products
CREATE TABLE Products (
    product_id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER REFERENCES Categories(category_id),
    name TEXT NOT NULL,
    size TEXT,
    color TEXT,
    material TEXT,
    price REAL NOT NULL,
    stock INTEGER NOT NULL DEFAULT 0,
    description TEXT,
    image_url TEXT,
    is_featured INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- 4. Payment Methods
CREATE TABLE PaymentMethods (
    payment_method_id INTEGER PRIMARY KEY AUTOINCREMENT,
    method_name TEXT NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- 5. Orders
CREATE TABLE Orders (
    order_id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT NOT NULL,
    customer_email TEXT NOT NULL,
    customer_phone TEXT NOT NULL,
    shipping_address TEXT NOT NULL,
    total_amount REAL NOT NULL,
    order_status TEXT DEFAULT 'Pending', -- Pending, Confirmed, Shipped, Cancelled
    payment_method_id INTEGER REFERENCES PaymentMethods(payment_method_id),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- 6. Order Items
CREATE TABLE OrderItems (
    order_item_id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL REFERENCES Orders(order_id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES Products(product_id),
    quantity INTEGER NOT NULL,
    unit_price REAL NOT NULL
);

-- 7. Contact Inquiries
CREATE TABLE ContactInquiries (
    inquiry_id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    message TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- Seed data
-- =========================================

-- Default admin  (username: admin / password: Admin@123)
INSERT INTO Admins (username, email, password_hash)
VALUES ('admin', 'admin@example.com', '$2y$10$HqJv0MXDxbVRmmaLZFo3Buj9FXOT35yZWIl0t3oHrYd65JiIosfBG');

INSERT INTO PaymentMethods (method_name, is_active) VALUES
('Cash on Delivery', 1),
('KBZPay', 1),
('Wave Money', 1),
('Bank Transfer', 1),
('Credit / Debit Card', 0);

INSERT INTO Categories (category_name, description) VALUES
('Electronics', 'Gadgets, devices and accessories'),
('Fashion', 'Clothing, shoes and accessories'),
('Home & Living', 'Furniture, decor and household items'),
('Sports', 'Sportswear and equipment');

INSERT INTO Products (category_id, name, size, color, material, price, stock, description, image_url, is_featured) VALUES
(1, 'Wireless Mouse', 'Standard', 'Black', 'ABS Plastic', 15.99, 50, 'Ergonomic 2.4GHz wireless mouse with adjustable DPI.', 'assets/uploads/product-placeholder.svg', 1),
(1, 'Mechanical Keyboard', 'Full Size', 'White', 'ABS / PBT Keycaps', 69.00, 30, 'Hot-swappable RGB mechanical keyboard, brown switches.', 'assets/uploads/product-placeholder.svg', 1),
(1, '27" LED Monitor', '27 inch', 'Grey', 'Aluminium & Plastic', 189.00, 12, '1080p 75Hz ultra-thin bezel monitor with HDMI.', 'assets/uploads/product-placeholder.svg', 0),
(2, 'Cotton T-Shirt', 'M', 'Navy', '100% Cotton', 12.50, 100, 'Breathable crew-neck everyday t-shirt.', 'assets/uploads/product-placeholder.svg', 0),
(2, 'Denim Jacket', 'L', 'Blue', 'Denim', 45.00, 25, 'Classic-fit denim jacket with button front.', 'assets/uploads/product-placeholder.svg', 1),
(2, 'Running Sneakers', '42', 'Red', 'Mesh & Rubber', 55.00, 40, 'Lightweight sneakers with cushioned soles.', 'assets/uploads/product-placeholder.svg', 0),
(3, 'Ceramic Table Lamp', 'Medium', 'Beige', 'Ceramic', 29.99, 18, 'Warm-light table lamp for bedroom or living room.', 'assets/uploads/product-placeholder.svg', 0),
(3, 'Storage Ottoman', 'Large', 'Grey', 'Linen Fabric', 59.00, 9, 'Soft-top ottoman with hidden interior storage.', 'assets/uploads/product-placeholder.svg', 1),
(4, 'Yoga Mat', '6mm', 'Purple', 'TPE Foam', 19.00, 60, 'Non-slip eco-friendly yoga mat with carry strap.', 'assets/uploads/product-placeholder.svg', 0),
(4, 'Dumbbell Set 20kg', '20 kg', 'Black', 'Cast Iron', 89.00, 7, 'Adjustable pair of hex dumbbells for home gyms.', 'assets/uploads/product-placeholder.svg', 0);
