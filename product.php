<?php
/**
 * Updated product.php using OOP concepts
 */

session_start();
require_once 'db_config.php';
require_once 'product_class.php';
require_once 'autoload.php';

/** @var Product $product */

$message = '';
$message_type = '';

define('DEFAULT_PRODUCT_IMAGE', '/uploads/products/default.png');

function validate_product_name(string $name): string|false {
    $name = trim($name);
    if (empty($name)) return false;
    if (strlen($name) > 150) return false;
    return $name;
}

function validate_price(mixed $price): float|false {
    if (!is_numeric($price)) return false;
    $price = (float) $price;
    if (!is_float($price) || $price <= 0) return false;
    return $price;
}

function validate_quantity(mixed $qty): int|false {
    if (!is_numeric($qty)) return false;
    $qty = (int) $qty;
    if (!is_int($qty) || $qty < 1) return false;
    return $qty;
}

// Handle add to cart (with optional variant)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $message = 'Please login to add items to cart';
        $message_type = 'error';
    } else {
        try {
            $product_id = intval($_POST['product_id']);
            $user_id    = $_SESSION['user_id'];
            $quantity   = validate_quantity($_POST['quantity'] ?? 1);
            if ($quantity === false) $quantity = 1;

            $variant_id = isset($_POST['variant_id']) && $_POST['variant_id'] !== ''
                ? intval($_POST['variant_id'])
                : null;

            $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                if ($variant_id === null) {
                    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND variant_id IS NULL");
                    $stmt->execute([$user_id, $product_id]);
                } else {
                    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND variant_id = ?");
                    $stmt->execute([$user_id, $product_id, $variant_id]);
                }
                $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($cart_item) {
                    $new_quantity = $cart_item['quantity'] + $quantity;
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $stmt->execute([$new_quantity, $cart_item['id']]);
                    $message = 'Product quantity updated in cart';
                    $message_type = 'success';
                } else {
                    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $product_id, $variant_id, $quantity]);
                    $message = 'Product added to cart successfully';
                    $message_type = 'success';
                }
            } else {
                $message = 'Product not found';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Fetch products and convert to OOP objects
$productObjects = [];
$variantsByProduct = [];

try {
    $result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
    $products_data = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products_data as $row) {
        $product = new Product(
            (int)$row['id'],
            $row['name'],
            $row['description'],
            (float)$row['price'],
            $row['image_url'],
            $row['category'],
            (int)$row['stock'],
            $row['created_at']
        );
        $productObjects[] = $product;
    }

    // Fetch all variants grouped by product_id
    $variant_result = $conn->query("SELECT * FROM product_variants ORDER BY variant_price ASC");
    $variants_data = $variant_result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($variants_data as $v) {
        $variantsByProduct[$v['product_id']][] = $v;
    }
} catch (PDOException $e) {
    $message = 'Error loading products: ' . $e->getMessage();
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - NEXTECH</title>
    <link rel="stylesheet" href="css/product.css">
    <style>
        .variant-select {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border: 1px solid var(--border-grey);
            border-radius: var(--radius);
            background-color: #ffffff;
            color: var(--text-primary);
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
        }

        .variant-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light);
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">NEXTECH</div>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="product.php" class="nav-link active">Products</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
                <li><a href="feedback.php" class="nav-link">Feedback</a></li>
                <li><a href="concerns.php" class="nav-link">Concerns</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="cart.php" class="nav-link">Cart</a></li>
                    <li><a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="cart-icon">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'cart.php' : 'login.php'; ?>">🛒</a>
            </div>
        </div>
    </nav>

    <section class="page-header">
        <h1>Our Premium Collection</h1>
        <p>Explore our complete range of cutting-edge technology products</p>
    </section>

    <?php if ($message): ?>
        <div class="message message-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <section class="filter-section">
        <div class="filter-container">
            <div class="filter-group">
                <label for="category">Category</label>
                <select id="category" class="filter-select">
                    <option value="">All Categories</option>
                    <option value="PC Components &amp; GPUs">PC Components &amp; GPUs</option>
                    <option value="Laptops &amp; Monitors">Laptops &amp; Monitors</option>
                    <option value="Gaming Peripherals">Gaming Peripherals</option>
                    <option value="Smartphones &amp; Accessories">Smartphones &amp; Accessories</option>
                    <option value="Audio &amp; Headphones">Audio &amp; Headphones</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="price">Price Range</label>
                <select id="price" class="filter-select">
                    <option value="">All Prices</option>
                    <option value="0-100">Under $100</option>
                    <option value="100-500">$100 - $500</option>
                    <option value="500-1000">$500 - $1000</option>
                    <option value="1000+">$1000+</option>
                </select>
            </div>
        </div>
    </section>

    <section class="products-section">
        <div class="products-container" id="productsContainer">

            <?php foreach ($productObjects as $product): ?>
                <?php
                    $product_id       = $product->__get('id');
                    $product_category = $product->__get('category');
                    $product_price    = $product->__get('price');
                    $variants         = $variantsByProduct[$product_id] ?? [];
                ?>
                <div class="product-item"
                     data-category="<?php echo htmlspecialchars($product_category); ?>"
                     data-price="<?php echo $product_price; ?>">

                    <div class="product-image-wrapper">
                        <img src="<?php echo htmlspecialchars($product->getImage()); ?>"
                             alt="<?php echo htmlspecialchars($product->__toString()); ?>"
                             class="product-image">

                        <div class="product-badge">
                            <?php echo htmlspecialchars($product->getBadge()); ?>
                        </div>
                    </div>

                    <div class="product-details">
                        <h3 class="product-title">
                            <?php echo htmlspecialchars((string)$product); ?>
                        </h3>

                        <p class="product-description">
                            <?php echo htmlspecialchars($product->__get('description')); ?>
                        </p>

                        <div class="product-specs">
                            <span><?php echo htmlspecialchars($product_category); ?></span>
                            <span><?php echo $product->__get('stock'); ?> in stock</span>
                        </div>

                        <p class="product-date">Added: <?php echo htmlspecialchars($product->getFormattedDate()); ?></p>

                        <div class="product-footer" style="flex-direction: column; align-items: stretch; gap: 0.75rem;">
                            <span class="product-cost">
                                <?php echo $product->getFormattedPrice(); ?>
                            </span>

                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form method="POST" style="display: flex; flex-direction: column;">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <input type="hidden" name="quantity" value="1">

                                    <?php if (!empty($variants)): ?>
                                        <select name="variant_id" class="variant-select">
                                            <option value="">Standard — <?php echo $product->getFormattedPrice(); ?></option>
                                            <?php foreach ($variants as $v): ?>
                                                <option value="<?php echo $v['id']; ?>">
                                                    <?php echo htmlspecialchars($v['variant_description']); ?> — $<?php echo number_format($v['variant_price'], 2); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>

                                    <button type="submit" name="add_to_cart" class="add-to-cart-btn" <?php echo !$product->isInStock() ? 'disabled' : ''; ?>>
                                        <?php echo $product->isInStock() ? 'Add to Cart' : 'Out of Stock'; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="login.php" class="add-to-cart-btn" style="text-align:center;">Login to Buy</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </section>

    <script>
        const categorySelect = document.getElementById('category');
        const priceSelect    = document.getElementById('price');
        const productItems   = document.querySelectorAll('.product-item');

        function filterProducts() {
            const selectedCategory = categorySelect.value;
            const selectedPrice    = priceSelect.value;

            productItems.forEach(item => {
                const itemCategory = item.getAttribute('data-category');
                const itemPrice    = parseFloat(item.getAttribute('data-price'));
                let showItem = true;

                if (selectedCategory && itemCategory !== selectedCategory) showItem = false;

                if (selectedPrice && showItem) {
                    if      (selectedPrice === '0-100'    && itemPrice >= 100)                      showItem = false;
                    else if (selectedPrice === '100-500'  && (itemPrice < 100 || itemPrice > 500))  showItem = false;
                    else if (selectedPrice === '500-1000' && (itemPrice < 500 || itemPrice > 1000)) showItem = false;
                    else if (selectedPrice === '1000+'    && itemPrice < 1000)                      showItem = false;
                }

                if (showItem) {
                    item.style.display = 'block';
                    setTimeout(() => item.style.opacity = '1', 10);
                } else {
                    item.style.opacity = '0';
                    setTimeout(() => item.style.display = 'none', 300);
                }
            });
        }

        categorySelect.addEventListener('change', filterProducts);
        priceSelect.addEventListener('change', filterProducts);

        productItems.forEach(item => {
            item.style.opacity    = '1';
            item.style.transition = 'opacity 0.3s ease';
        });
    </script>

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
                    <li><a href="feedback.php">Feedback</a></li>
                    <li><a href="concerns.php">Concerns</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> NEXTECH. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>