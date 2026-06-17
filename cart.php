<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    try {
        $cart_id = intval($_POST['cart_id']);

        // Fetch the cart row first so we can restore its stock
        $stmt = $conn->prepare("SELECT product_id, quantity FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
        $cart_row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart_row) {
            $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")->execute([$cart_id, $user_id]);
            // Give the held units back to product stock
            $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$cart_row['quantity'], $cart_row['product_id']]);
            $message = 'Item removed from cart';
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error removing item';
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    try {
        $cart_id      = intval($_POST['cart_id']);
        $new_quantity = intval($_POST['quantity']);

        if ($new_quantity > 0) {
            // Fetch the current cart row and matching product stock
            $stmt = $conn->prepare("
                SELECT c.quantity AS cart_qty, c.product_id,
                       p.stock AS product_stock
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.id = ? AND c.user_id = ?
            ");
            $stmt->execute([$cart_id, $user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $old_qty       = (int)$row['cart_qty'];
                $product_stock = (int)$row['product_stock'];
                $product_id    = (int)$row['product_id'];

                // Available = DB stock + units already held by this cart row
                $available  = $product_stock + $old_qty;
                $capped_qty = min($new_quantity, $available, 20);

                if ($capped_qty !== $old_qty) {
                    $diff = $capped_qty - $old_qty; // + means more, - means fewer
                    $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?")->execute([$capped_qty, $cart_id, $user_id]);
                    $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$diff, $product_id]);
                }

                if ($new_quantity > $available) {
                    $message = "Quantity capped to available stock ({$available})";
                    $message_type = 'error';
                } else {
                    $message = 'Quantity updated';
                    $message_type = 'success';
                }
            }
        }
    } catch (PDOException $e) {
        $message = 'Error updating quantity';
        $message_type = 'error';
    }
}

$cart_items  = [];
$total_price = 0;

try {
    $stmt = $conn->prepare("
    SELECT c.id, c.quantity, c.variant_id,
           p.id as product_id, p.name, p.price, p.description, p.image_url, p.stock,
           pv.variant_price, pv.variant_description
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_variants pv ON c.variant_id = pv.id
    WHERE c.user_id = ?
");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $effective_price = $item['variant_id'] ? (float)$item['variant_price'] : (float)$item['price'];
        $total_price += $effective_price * $item['quantity'];
    }
} catch (PDOException $e) {
    $message = 'Error loading cart items';
    $message_type = 'error';
}

$shipping = $total_price >= 100 ? 0.00 : 9.99;
$tax      = $total_price * 0.08;
$total    = $total_price + $shipping + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - NEXTECH</title>
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">NEXTECH</div>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="product.php" class="nav-link">Products</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
                <li><a href="feedback.php" class="nav-link">Feedback</a></li>
                <li><a href="concerns.php" class="nav-link">Concerns</a></li>
                <li><a href="cart.php" class="nav-link active">Cart</a></li>
                <li><a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
            </ul>
            <div class="cart-icon"><i class="bi bi-cart3"></i></div>
        </div>
    </nav>

    <section class="page-header">
        <h1>Shopping Cart</h1>
        <p>Review and manage your items</p>
    </section>

    <?php if ($message): ?>
        <div class="message message-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <section class="cart-section">
        <div class="cart-container">
            <?php if (count($cart_items) > 0): ?>

                <div class="cart-items">
                    <h2 class="cart-title">Cart Items (<?php echo count($cart_items); ?>)</h2>
                    <div class="items-list">
                        <?php foreach ($cart_items as $item): ?>
                            <?php
                                $effective_price = $item['variant_id'] ? (float)$item['variant_price'] : (float)$item['price'];
                            ?>
                            <div class="cart-item">
                                <div class="item-image">
    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
</div>
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <?php if ($item['variant_id']): ?>
                                        <p style="color: var(--accent); font-weight: 600; margin-bottom: 0.25rem;">
                                            Variant: <?php echo htmlspecialchars($item['variant_description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="item-meta">
                                        <span class="item-price">$<?php echo number_format($effective_price, 2); ?></span>
                                        <form method="POST" class="quantity-form">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <label for="qty-<?php echo $item['id']; ?>">Qty:</label>
                                            <input type="number" id="qty-<?php echo $item['id']; ?>" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo min((int)$item['stock'] + (int)$item['quantity'], 20); ?>">
                                            <button type="submit" name="update_quantity" class="update-btn">Update</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="item-total">
                                    <p>$<?php echo number_format($effective_price * $item['quantity'], 2); ?></p>
                                    <form method="POST" class="remove-form">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_item" class="remove-btn">Remove</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($total_price, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?php echo $shipping == 0 ? 'Free' : '$' . number_format($shipping, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (8%)</span>
                        <span>$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="checkout-btn" style="display:block; text-align:center; text-decoration:none;">Proceed to Checkout</a>
                    <a href="product.php" class="continue-shopping">Continue Shopping</a>
                </div>

            <?php else: ?>
                <div class="empty-cart">
                    <div class="empty-icon"><i class="bi bi-cart3"></i></div>
                    <h2>Your Cart is Empty</h2>
                    <p>Looks like you haven't added anything yet. Explore our premium collection!</p>
                    <a href="product.php" class="shop-now-btn">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

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