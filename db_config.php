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
            // PC Components & GPUs
            ['ASUS ROG Strix GeForce RTX 4090 OC', 'NVIDIA Ada Lovelace architecture, 24GB GDDR6X, boost clock 2640MHz, triple-fan cooling, PCIe 4.0', 1999.99, '/images/rtx4090.jpg', 'PC Components & GPUs', 15],
            ['MSI GeForce RTX 4070 Ti SUPRIM X', '12GB GDDR6X, Tri FROZR 3S cooling, 2760MHz boost clock, DLSS 3, ray tracing, Ada Lovelace GPU', 849.99, '/images/rtx4070ti.jpg', 'PC Components & GPUs', 28],
            ['AMD Radeon RX 7900 XTX Sapphire NITRO+', '24GB GDDR6, Infinity Cache, 355W TBP, triple-fan cooler, RDNA 3 architecture, DisplayPort 2.1', 999.99, '/images/rx7900xtx.jpg', 'PC Components & GPUs', 20],
            ['Gigabyte AORUS GeForce RTX 4080 MASTER', '16GB GDDR6X, 2565MHz boost, LCD Edge View display, WindForce Stack 3x100mm fans, PCIe 4.0', 1199.99, '/images/rtx4080.jpg', 'PC Components & GPUs', 18],
            ['Intel Core i9-14900K Processor', '24 cores (8P+16E), 5.8GHz max turbo, 36MB L3 cache, LGA1700, unlocked for overclocking, PCIe 5.0', 549.99, '/images/i9-14900k.jpg', 'PC Components & GPUs', 35],

            // Laptops & Monitors
            ['ASUS ROG Zephyrus G16 (2024)', 'AMD Ryzen 9 8945HS, RTX 4090 16GB, 16" QHD+ 240Hz OLED, 32GB DDR5, 2TB NVMe SSD', 2999.99, '/images/zephyrus-g16.jpg', 'Laptops & Monitors', 12],
            ['Razer Blade 16 (2024)', 'Intel Core i9-14900HX, RTX 4090 16GB, 16" UHD+ 120Hz Mini-LED, 32GB DDR5, 2TB SSD, CNC aluminum', 3999.99, '/images/blade16.jpg', 'Laptops & Monitors', 8],
            ['LG UltraGear 27GR95QE-B OLED', '27" QHD OLED, 240Hz, 0.03ms response, G-Sync Compatible, DisplayHDR True Black 400, USB-C 90W', 799.99, '/images/lg-oled-27.jpg', 'Laptops & Monitors', 22],
            ['Samsung Odyssey G9 49" DQHD', '49" 5120x1440 VA, 240Hz, 1ms, 1000R curve, G-Sync+FreeSync, HDR1000, Quantum Mini-LED', 1299.99, '/images/odyssey-g9.jpg', 'Laptops & Monitors', 10],
            ['ASUS ProArt PA32UCR-K', '32" 4K IPS, 144Hz, 99.5% Adobe RGB, Calman Verified, Thunderbolt 4, built-in color calibrator', 1599.99, '/images/proart-pa32.jpg', 'Laptops & Monitors', 9],

            // Gaming Peripherals
            ['Logitech G Pro X Superlight 2', '60g ultra-lightweight wireless mouse, HERO 2 25K sensor, 95hr battery, LIGHTSPEED, 5 programmable buttons', 159.99, '/images/gprox2.jpg', 'Gaming Peripherals', 60],
            ['Razer DeathAdder V3 HyperSpeed', 'Asymmetric ergonomic wireless mouse, Focus Pro 30K optical sensor, 6 buttons, 300hr battery life', 99.99, '/images/deathadder-v3.jpg', 'Gaming Peripherals', 75],
            ['SteelSeries Apex Pro TKL Wireless', 'OmniPoint 2.0 magnetic switches (0.1–4.0mm actuation), OLED display, RGB, 2.4GHz wireless + Bluetooth', 219.99, '/images/apex-pro-tkl.jpg', 'Gaming Peripherals', 40],
            ['Corsair K100 RGB Optical-Mechanical', 'OPX optical-mechanical switches, per-key RGB, iCUE AXON 44-zone lighting, USB Type-C, aluminum frame', 199.99, '/images/k100-rgb.jpg', 'Gaming Peripherals', 35],
            ['HyperX Pulsefire Haste 2 Wireless', '61g honeycomb shell, 26K DPI optical sensor, 100hr battery, LIGHTSPEED-like 2.4GHz, TTC Golden micro switches', 79.99, '/images/pulsefire-haste2.jpg', 'Gaming Peripherals', 80],

            // Smartphones & Accessories
            ['Samsung Galaxy S24 Ultra', '6.8" Dynamic AMOLED 2X, 200MP camera, Snapdragon 8 Gen 3, 12GB RAM, 256GB, S Pen, titanium frame', 1299.99, '/images/s24-ultra.jpg', 'Smartphones & Accessories', 45],
            ['Apple iPhone 15 Pro Max', '6.7" Super Retina XDR ProMotion, A17 Pro chip, 48MP main + 12MP 5x telephoto, titanium design, USB-C', 1199.99, '/images/iphone15promax.jpg', 'Smartphones & Accessories', 50],
            ['Sony Xperia 1 VI', '6.5" 4K HDR OLED 120Hz, Snapdragon 8 Gen 3, 52MP Zeiss triple camera, 5000mAh, IP68, 3.5mm jack', 1299.99, '/images/xperia1vi.jpg', 'Smartphones & Accessories', 20],
            ['Anker MagSafe 3-in-1 Wireless Charger', '15W MagSafe pad + Apple Watch fast charger + AirPods pad, foldable stand, USB-C 30W adapter included', 89.99, '/images/anker-magsafe.jpg', 'Smartphones & Accessories', 120],
            ['Spigen Tough Armor for S24 Ultra', 'Dual-layer protection, Air Cushion technology, kickstand, MIL-STD-810G certified, compatible with wireless charging', 29.99, '/images/spigen-s24u.jpg', 'Smartphones & Accessories', 200],

            // Audio & Headphones
            ['Sony WH-1000XM5', 'Industry-leading ANC, 30hr battery, 8-mic array, LDAC Hi-Res, Multipoint Bluetooth, Speak-to-Chat, foldable', 349.99, '/images/wh1000xm5.jpg', 'Audio & Headphones', 55],
            ['Apple AirPods Pro (2nd Gen)', 'H2 chip, Adaptive Transparency, Personalized Spatial Audio, USB-C, 30hr total ANC, IP54, Conversation Boost', 249.99, '/images/airpods-pro2.jpg', 'Audio & Headphones', 90],
            ['Sennheiser HD 800 S', 'Open-back reference headphone, 300Ω impedance, 56mm ring radiator drivers, 4Hz–51kHz, includes balanced cable', 1799.99, '/images/hd800s.jpg', 'Audio & Headphones', 8],
            ['SteelSeries Arctis Nova Pro Wireless', 'Dual wireless (2.4GHz + Bluetooth), ANC, hot-swappable battery, 360° Spatial Audio, ClearCast Gen 2 mic, multi-system', 349.99, '/images/arctis-nova-pro.jpg', 'Audio & Headphones', 30],
            ['Beyerdynamic DT 900 PRO X', 'Open-back studio headphone, STELLAR.45 driver, 48Ω, 5Hz–40kHz, detachable cable, velour pads, made in Germany', 299.99, '/images/dt900prox.jpg', 'Audio & Headphones', 18],
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