CREATE DATABASE IF NOT EXISTS nextech_db;
USE nextech_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    category VARCHAR(100),
    stock INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_price DECIMAL(10,2) NOT NULL,
    variant_description VARCHAR(100) NOT NULL,
    var_stock INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id, variant_id)
);

CREATE TABLE IF NOT EXISTS delivery_address (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    secondary_address VARCHAR(255),
    region VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    barangay VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_id INT,
    status VARCHAR(50) DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    shipping DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (address_id) REFERENCES delivery_address(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT,
    product_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    transaction_ref VARCHAR(100),
    paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS concerns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    rating INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO products (name, description, price, image_url, category, stock) VALUES
('NexChill Pro 360 Liquid Cooler', 'Triple-fan 360mm AIO liquid cooler with LCD temperature display, copper cold plate, high-density radiator, and PWM smart control. Handles up to 350W TDP. Compatible with Intel LGA1700 and AMD AM5/AM4.', 149.99, 'images/cooling1.png', 'PC Components & GPUs', 25),
('NexDock Pro 100W Docking Station', 'Premium docking station with 100W power delivery, dual 4K display output via HDMI and DisplayPort, Gigabit Ethernet, 10Gbps USB-C, SD/TF card slots, and 3.5mm audio jack. Works on Windows, macOS, and ChromeOS.', 129.99, 'images/dock1.png', 'Laptops & Monitors', 30),
('NexHub Wireless 10-Port USB-C Hub', 'Advanced 10-port wireless USB-C hub with 40Gbps data transfer per port, 100W power delivery pass-through, 8K@60Hz display support, and built-in wireless charging pad.', 199.99, 'images/hub1.png', 'PC Components & GPUs', 20),
('NexView 32 inch 4K Ultra HD Display', '32-inch 4K IPS professional monitor with 3840x2160 resolution, 10-bit color, 100% sRGB, 350 nits brightness, USB-C 65W power delivery, HDMI 2.0, and DisplayPort 1.4. Height adjustable ergonomic stand included.', 499.99, 'images/monitor1.png', 'Laptops & Monitors', 15),
('NexTrack AI Wireless Mouse', 'Ergonomic wireless mouse with AI tracking technology, 26,000 DPI precision sensor, dual connectivity via 2.4GHz and Bluetooth 5.2, 6 programmable buttons, silent clicks, and 90-day battery life.', 89.99, 'images/mouse1.png', 'Gaming Peripherals', 50),
('NexGlow RGB Gaming Setup', 'Complete RGB ambient lighting kit with modular hexagonal wall panels and LED strip lights. Fully customizable colors and effects to transform any gaming battlestation.', 79.99, 'images/rgblights1.png', 'Gaming Peripherals', 40),
('NexSound 360 Bluetooth Speaker', 'Premium cylindrical Bluetooth 5.3 speaker delivering 360 degree omnidirectional sound with 30W RMS output, deep bass drivers, 20-hour battery life, IPX6 water resistance, and USB-C charging.', 99.99, 'images/speaker.png', 'Audio & Headphones', 35),
('NexDrive 2TB NVMe SSD', 'High-performance PCIe 4.0 x4 NVMe 1.4 SSD with 7400MB/s read and 6500MB/s write speeds, 3D NAND technology, S.M.A.R.T. support, advanced thermal control, and a 5-year warranty.', 109.99, 'images/ssd2.png', 'PC Components & GPUs', 45);

INSERT INTO product_variants (product_id, variant_price, variant_description, var_stock) VALUES
(1, 159.99, '420mm Radiator', 15),
(1, 179.99, '420mm Radiator + Extra Fan Kit', 10),
(2, 149.99, 'With Extra HDMI Cable', 12),
(3, 219.99, 'With Wireless Charging Pad Upgrade', 8),
(4, 549.99, 'With Wall Mount Kit', 6),
(5, 99.99, 'With Extended Warranty', 20),
(7, 119.99, 'With Charging Dock', 14),
(8, 139.99, '4TB Variant', 10);
