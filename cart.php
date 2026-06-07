<?php
session_start();
require_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    try {
        $cart_id = intval($_POST['cart_id']);
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$cart_id, $user_id])) {
            $message = 'Item removed from cart';
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error removing item';
        $message_type = 'error';
    }
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    try {
        $cart_id = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $user_id]);
            $message = 'Quantity updated';
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error updating quantity';
        $message_type = 'error';
    }
}

// Fetch cart items
$cart_items = [];
$total_price = 0;

try {
    $stmt = $conn->prepare("
        SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.description 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cart_items as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    $message = 'Error loading cart items';
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - NEXTECH</title>
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">NEXTECH</div>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="product.php" class="nav-link">Products</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
                <li><a href="cart.php" class="nav-link active">Cart</a></li>
                <li><a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
            </ul>
            <div class="cart-icon">🛒</div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <h1>Shopping Cart</h1>
        <p>Review and manage your items</p>
    </section>

    <!-- Message Display -->
    <?php if ($message): ?>
        <div class="message message-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <section class="cart-section">
        <div class="cart-container">
            <?php if (count($cart_items) > 0): ?>
                <!-- Cart Items -->
                <div class="cart-items">
                    <h2 class="cart-title">Cart Items (<?php echo count($cart_items); ?>)</h2>
                    <div class="items-list">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="item-image">
                                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="100" height="100" fill="#f5f5f5"/>
                                        <rect x="15" y="20" width="70" height="60" fill="#333" rx="4"/>
                                        <rect x="20" y="25" width="60" height="45" fill="#555" rx="2"/>
                                        <circle cx="50" cy="47" r="10" fill="#1a1a1a"/>
                                    </svg>
                                </div>
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="item-meta">
                                        <span class="item-price">$<?php echo number_format($item['price'], 2); ?></span>
                                        <form method="POST" class="quantity-form">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <label for="qty-<?php echo $item['id']; ?>">Qty:</label>
                                            <input type="number" id="qty-<?php echo $item['id']; ?>" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="100">
                                            <button type="submit" name="update_quantity" class="update-btn">Update</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="item-total">
                                    <p>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                    <form method="POST" class="remove-form">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_item" class="remove-btn">Remove</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($total_price, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>$9.99</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>$<?php echo number_format($total_price * 0.08, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($total_price + 9.99 + ($total_price * 0.08), 2); ?></span>
                    </div>
                    <button class="checkout-btn">Proceed to Checkout</button>
                    <a href="product.php" class="continue-shopping">Continue Shopping</a>
                </div>
            <?php else: ?>
                <!-- Empty Cart -->
                <div class="empty-cart">
                    <div class="empty-icon">🛒</div>
                    <h2>Your Cart is Empty</h2>
                    <p>Looks like you haven't added anything yet. Explore our premium collection!</p>
                    <a href="product.php" class="shop-now-btn">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
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
            <p>&copy; 2024 NEXTECH. All rights reserved.</p>
        </div>
    </footer>

    <script>
        /**
         * Browser Session Cart Logic
         * 
         * sessionStorage is cleared automatically when the entire browser closes.
         * It persists across tab refreshes and new tabs opened within the same session.
         *
         * Flow:
         *  - First load in a new browser session → no token in sessionStorage
         *    → call clear_cart.php to wipe the DB cart → set the token
         *  - Subsequent loads (same browser session, new tabs, refreshes)
         *    → token already exists → skip clearing
         */
        (function () {
            const SESSION_KEY = 'nextech_browser_session';

            if (!sessionStorage.getItem(SESSION_KEY)) {
                // Fresh browser open — clear the cart on the server first,
                // then mark this browser session as active.
                fetch('clear_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    // Credentials ensures the PHP session cookie is sent along
                    credentials: 'same-origin'
                })
                .then(function (res) {
                    if (res.ok) {
                        // Mark session so subsequent loads don't clear again
                        sessionStorage.setItem(SESSION_KEY, '1');
                        // Reload the page so the now-empty cart is reflected
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    console.error('Cart session check failed:', err);
                });
            }
        })();
    </script>
</body>
</html>