<?php
// SQLite Database Configuration
define('DB_PATH', __DIR__ . '/nextech_ecommerce.db');

try {
    $conn = new PDO('sqlite:' . DB_PATH);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    echo "<div style='background-color: #ffebee; border: 2px solid #ef5350; color: #c62828; padding: 20px; margin: 20px; border-radius: 4px; font-family: Arial, sans-serif;'>";
    echo "<h2 style='margin-top: 0;'>Database Connection Error</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please ensure the file directory is writable.</p>";
    echo "</div>";
    exit;
}

// Create Users table
$create_users = "CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
try { $conn->exec($create_users); } catch (PDOException $e) { echo "Error creating users table: " . htmlspecialchars($e->getMessage()); }

// Create Products table
$create_products = "CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    price REAL NOT NULL,
    image_url TEXT,
    category TEXT,
    stock INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
try { $conn->exec($create_products); } catch (PDOException $e) { echo "Error creating products table: " . htmlspecialchars($e->getMessage()); }

// Create Cart table
$create_cart = "CREATE TABLE IF NOT EXISTS cart (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE(user_id, product_id)
)";
try { $conn->exec($create_cart); } catch (PDOException $e) { echo "Error creating cart table: " . htmlspecialchars($e->getMessage()); }

// Insert sample products if table is empty
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row['count'] == 0) {
        $sample_products = [
    [
        'NexChill Pro 360 Liquid Cooler',
        'Triple-fan 360mm AIO liquid cooler with LCD temperature display, copper cold plate, high-density radiator, and PWM smart control. Handles up to 350W TDP. Compatible with Intel LGA1700 and AMD AM5/AM4.',
        149.99,
        'images/cooling1.png',
        'PC Components & GPUs',
        25
    ],
    [
        'NexDock Pro 100W Docking Station',
        'Premium docking station with 100W power delivery, dual 4K display output via HDMI and DisplayPort, Gigabit Ethernet, 10Gbps USB-C, SD/TF card slots, and 3.5mm audio jack. Works on Windows, macOS, and ChromeOS.',
        129.99,
        'images/dock1.png',
        'Laptops & Monitors',
        30
    ],
    [
        'NexHub Wireless 10-Port USB-C Hub',
        'Advanced 10-port wireless USB-C hub with 40Gbps data transfer per port, 100W power delivery pass-through, 8K@60Hz display support, and built-in wireless charging pad.',
        199.99,
        'images/hub1.png',
        'PC Components & GPUs',
        20
    ],
    [
        'NexView 32" 4K Ultra HD Display',
        '32-inch 4K IPS professional monitor with 3840x2160 resolution, 10-bit color, 100% sRGB, 350 nits brightness, USB-C 65W power delivery, HDMI 2.0, and DisplayPort 1.4. Height adjustable ergonomic stand included.',
        499.99,
        'images/monitor1.png',
        'Laptops & Monitors',
        15
    ],
    [
        'NexTrack AI Wireless Mouse',
        'Ergonomic wireless mouse with AI tracking technology, 26,000 DPI precision sensor, dual connectivity via 2.4GHz and Bluetooth 5.2, 6 programmable buttons, silent clicks, and 90-day battery life.',
        89.99,
        'images/mouse1.png',
        'Gaming Peripherals',
        50
    ],
    [
        'NexGlow RGB Gaming Setup',
        'Complete RGB ambient lighting kit with modular hexagonal wall panels and LED strip lights. Fully customizable colors and effects to transform any gaming battlestation.',
        79.99,
        'images/rgblights1.png',
        'Gaming Peripherals',
        40
    ],
    [
        'NexSound 360° Bluetooth Speaker',
        'Premium cylindrical Bluetooth 5.3 speaker delivering 360° omnidirectional sound with 30W RMS output, deep bass drivers, 20-hour battery life, IPX6 water resistance, and USB-C charging.',
        99.99,
        'images/speaker.png',
        'Audio & Headphones',
        35
    ],
    [
        'NexDrive 2TB NVMe SSD',
        'High-performance PCIe 4.0 x4 NVMe 1.4 SSD with 7400MB/s read and 6500MB/s write speeds, 3D NAND technology, S.M.A.R.T. support, advanced thermal control, and a 5-year warranty.',
        109.99,
        'images/ssd2.png',
        'PC Components & GPUs',
        45
    ],
];

        $insert_stmt = $conn->prepare("INSERT INTO products (name, description, price, image_url, category, stock) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($sample_products as $product) {
            $insert_stmt->execute($product);
        }
    }
} catch (PDOException $e) {
    echo "Error inserting products: " . htmlspecialchars($e->getMessage());
}
?>