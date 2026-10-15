-- E-Commerce Database Schema (Microsoft SQL Server / T-SQL)
-- Run in SSMS or: sqlcmd -S localhost -d master -i schema.sql

-- Create Database
CREATE DATABASE EcommerceDB;
GO

USE EcommerceDB;
GO

-- 1. Admins Table
CREATE TABLE Admins (
    admin_id INT IDENTITY(1,1) PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- 2. Categories Table
CREATE TABLE Categories (
    category_id INT IDENTITY(1,1) PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description VARCHAR(MAX),
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- 3. Products Table
CREATE TABLE Products (
    product_id INT IDENTITY(1,1) PRIMARY KEY,
    category_id INT FOREIGN KEY REFERENCES Categories(category_id),
    name VARCHAR(150) NOT NULL,
    size VARCHAR(50),
    color VARCHAR(50),
    material VARCHAR(100),
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    description VARCHAR(MAX),
    image_url VARCHAR(255),
    is_featured BIT DEFAULT 0,
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- 4. Payment Methods Table
CREATE TABLE PaymentMethods (
    payment_method_id INT IDENTITY(1,1) PRIMARY KEY,
    method_name VARCHAR(100) NOT NULL,
    is_active BIT DEFAULT 1,
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- 5. Orders Table
CREATE TABLE Orders (
    order_id INT IDENTITY(1,1) PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    shipping_address VARCHAR(MAX) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    order_status VARCHAR(50) DEFAULT 'Pending', -- Pending, Confirmed, Shipped, Cancelled
    payment_method_id INT FOREIGN KEY REFERENCES PaymentMethods(payment_method_id),
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- 6. Order Items Table
CREATE TABLE OrderItems (
    order_item_id INT IDENTITY(1,1) PRIMARY KEY,
    order_id INT FOREIGN KEY REFERENCES Orders(order_id) ON DELETE CASCADE,
    product_id INT FOREIGN KEY REFERENCES Products(product_id),
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL
);
GO

-- 7. Contact Inquiries Table
CREATE TABLE ContactInquiries (
    inquiry_id INT IDENTITY(1,1) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message VARCHAR(MAX) NOT NULL,
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- =========================================
-- Seed data
-- =========================================

-- Default admin  (username: admin / password: Admin@123)
INSERT INTO Admins (username, email, password_hash)
VALUES ('admin', 'admin@example.com', '$2y$10$HqJv0MXDxbVRmmaLZFo3Buj9FXOT35yZWIl0t3oHrYd65JiIosfBG');
GO

INSERT INTO PaymentMethods (method_name, is_active) VALUES
('Cash on Delivery', 1),
('KBZPay', 1),
('Wave Money', 1),
('Bank Transfer', 1),
('Credit / Debit Card', 0);
GO

INSERT INTO Categories (category_name, description) VALUES
('Electronics', 'Gadgets, devices and accessories'),
('Fashion', 'Clothing, shoes and accessories'),
('Home & Living', 'Furniture, decor and household items'),
('Sports', 'Sportswear and equipment');
GO

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
GO
