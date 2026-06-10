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
$order_placed = false;
$order_id = null;

// Fetch cart items from DB
$cart_items = [];
$subtotal = 0.0;

try {
    $stmt = $conn->prepare("
        SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image_url
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    $message = 'Error loading cart.';
    $message_type = 'error';
}

$shipping = $subtotal >= 100 ? 0.00 : 9.99;
$tax      = $subtotal * 0.08;
$total    = $subtotal + $shipping + $tax;

// Handle place order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (empty($cart_items)) {
        $message = 'Your cart is empty.';
        $message_type = 'error';
    } else {
        try {
            $conn->beginTransaction();

            // Insert into orders
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, status, subtotal, shipping, tax, total)
                VALUES (?, 'pending', ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $subtotal, $shipping, $tax, $total]);
            $order_id = $conn->lastInsertId();

            // Insert order items
            $item_stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, line_total)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($cart_items as $item) {
                $line_total = $item['price'] * $item['quantity'];
                $item_stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['name'],
                    $item['quantity'],
                    $item['price'],
                    $line_total
                ]);
            }

            // Clear the cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $conn->commit();

            $order_placed = true;
            $cart_items   = [];
            $subtotal     = 0;
            $shipping     = 0;
            $tax          = 0;
            $total        = 0;

            header("Location: payment.php?order_id=" . $order_id);
            exit();

        } catch (PDOException $e) {
            $conn->rollBack();
            $message = 'Error placing order. Please try again.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - NEXTECH</title>
    <link rel="stylesheet" href="css/cart.css">
    <style>
        .checkout-section {
            max-width: 1400px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
        }

        .checkout-box {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 2rem;
        }

        .checkout-box h2 {
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .order-item-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .order-item-qty {
            font-size: 0.85rem;
            color: #666;
        }

        .order-item-price {
            font-weight: 700;
            font-size: 1rem;
        }

        .place-order-btn {
            width: 100%;
            padding: 1rem;
            background-color: #1a1a1a;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
        }

        .place-order-btn:hover {
            background-color: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .back-to-cart {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-to-cart:hover {
            color: #1a1a1a;
        }

        .empty-checkout {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: #f5f5f5;
            border-radius: 12px;
            border: 2px dashed #e0e0e0;
        }

        .empty-checkout h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .empty-checkout p {
            color: #666;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .checkout-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">NEXTECH</div>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="product.php" class="nav-link">Products</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
                <li><a href="cart.php" class="nav-link">Cart</a></li>
                <li><a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
            </ul>
            <div class="cart-icon">🛒</div>
        </div>
    </nav>

    <section class="page-header">
        <h1>Checkout</h1>
        <p>Review your order before placing it</p>
    </section>

    <?php if ($message): ?>
        <div class="message message-<?php echo $message_type; ?>" style="max-width:1400px; margin: 1rem auto; padding: 1rem 2rem;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <section class="checkout-section">
        <?php if (!empty($cart_items)): ?>

            <div class="checkout-box">
                <h2>Order Items (<?php echo count($cart_items); ?>)</h2>
                <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <div class="order-item-info">
                            <span class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="order-item-qty">Qty: <?php echo $item['quantity']; ?> &times; $<?php echo number_format($item['price'], 2); ?></span>
                        </div>
                        <span class="order-item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
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

                <form method="POST">
                    <button type="submit" name="place_order" class="place-order-btn">Place Order</button>
                </form>
                <a href="cart.php" class="back-to-cart">← Back to Cart</a>
            </div>

        <?php else: ?>
            <div class="empty-checkout">
                <div class="empty-icon">🛒</div>
                <h2>No Items to Checkout</h2>
                <p>Your cart is empty. Add some products first!</p>
                <a href="product.php" class="shop-now-btn">Start Shopping</a>
            </div>
        <?php endif; ?>
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
                    <li><a href="#">Shipping Info</a></li>
                    <li><a href="#">Returns</a></li>
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