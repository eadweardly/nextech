<?php
session_start();

// ── Home Page Utility Functions ──────────────────────────────────────────────

/**
 * Truncates a string to a max character length using strlen().
 */
function truncate_text(string $text, int $max = 80): string {
    $text = trim($text); // trim() — remove stray whitespace
    if (strlen($text) > $max) {
        return substr($text, 0, $max) . '...';
    }
    return $text;
}

/**
 * Sanitizes a subtitle: trims it, checks it isn't empty,
 * and enforces a word-count limit using str_word_count().
 */
function sanitize_subtitle(string $text, int $max_words = 15): string {
    $text = trim($text);
    if (empty($text)) {
        return 'Premium tech for the modern lifestyle.';
    }
    if (str_word_count($text) > $max_words) {
        $words = explode(' ', $text);
        $text  = implode(' ', array_slice($words, 0, $max_words)) . '...';
    }
    return $text;
}

// ── Page Data ────────────────────────────────────────────────────────────────

$hero_subtitle = sanitize_subtitle(
    'Cutting-edge tech products designed for the modern lifestyle'
);

// ── Admin Check (for navbar link) ──────────────────────────────────────────
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $admin_usernames = ['admin', 'nextech_admin', 'hihi'];
    if (!empty($_SESSION['username']) && in_array($_SESSION['username'], $admin_usernames)) {
        $is_admin = true;
    } else {
        require_once 'db_config.php';
        try {
            $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['is_admin'])) {
                $is_admin = true;
            }
        } catch (PDOException $e) {
            // is_admin column may not exist - ignore
        }
    }
}

$featured_products = [
    [
        'name'        => 'NexView 32" 4K Ultra HD Display',
        'price'       => '$499.99',
        'image'       => 'images/monitor1.png',
        'description' => 'Ultra-wide 4K display with 144Hz refresh rate and HDR support for professionals.',
    ],
    [
        'name'        => 'NexHub Wireless 10-Port USB-C Hub',
        'price'       => '$199.99',
        'image'       => 'images/hub1.png',
        'description' => 'All-in-one 10-port USB-C hub with 100W power delivery and dual 4K output.',
    ],
    [
        'name'        => 'NexDock Pro 100W Docking Station',
        'price'       => '$129.99',
        'image'       => 'images/dock1.png',
        'description' => 'Wireless charging dock with 65W fast charge and multi-device support.',
    ],
    [
        'name'        => 'NexTrack AI Wireless Mouse',
        'price'       => '$89.99',
        'image'       => 'images/mouse1.png',
        'description' => 'Ergonomic wireless mouse with AI tracking technology, 26,000 DPI precision sensor, dual connectivity via 2.4GHz and Bluetooth 5.2, 6 programmable buttons, silent clicks, and 90-day battery life.',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXTECH - Premium Tech Products</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">NEXTECH</div>
            <ul class="nav-menu">
                <li><a href="index.php"   class="nav-link active">Home</a></li>
                <li><a href="product.php" class="nav-link">Products</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
                <li><a href="feedback.php" class="nav-link">Feedback</a></li>
                <li><a href="concerns.php" class="nav-link">Concerns</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($is_admin): ?>
                        <li><a href="admin.php" class="nav-link">Admin Panel</a></li>
                    <?php endif; ?>
                    <li class="nav-user">
                        <span class="nav-username"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="logout.php" class="nav-link nav-logout">Logout</a>
                    </li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="cart-icon"><a href="<?php echo isset($_SESSION['user_id']) ? 'cart.php' : 'login.php'; ?>"><i class="bi bi-cart3"></i></a></div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Experience the Future of Technology</h1>
            <p class="hero-subtitle">
                <?php echo htmlspecialchars($hero_subtitle); ?>
                <!-- sanitize_subtitle() used above: trim() + empty() + str_word_count() -->
            </p>
            <a href="product.php" class="cta-button">Shop Now</a>
        </div>
        <div class="hero-image">
            <div class="product-showcase">
                <img src="images/logoz.png" alt="NEXTECH Logo" class="showcase-img">
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-section">
        <h2 class="section-title">Featured Collections</h2>
        
        <div class="product-grid">
            <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <!-- ↑ forward slashes fixed (imgs/image-x.png) -->
                    </div>
                    <h3 class="product-name">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h3>
                    <p class="product-desc">
                        <?php echo htmlspecialchars(truncate_text($product['description'], 80)); ?>
                        <!-- ↑ truncate_text() uses trim() + strlen() -->
                    </p>
                    <p class="product-price">
                        <?php echo htmlspecialchars($product['price']); ?>
                    </p>
                    <a href="product.php" class="product-btn">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <h2>Discover Our Complete Collection</h2>
        <p>Browse our curated selection of premium tech products</p>
        <a href="product.php" class="cta-button-large">Shop All Products</a>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>About NEXTECH</h4>
                <p>Premium technology products for the modern lifestyle.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="product.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">Shipping Info</a></li>
                    <li><a href="#">Returns</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <!-- date("Y") — always shows the current year automatically -->
            <p>&copy; <?php echo date("Y"); ?> NEXTECH. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>