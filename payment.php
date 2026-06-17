<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$order_id = intval($_GET['order_id'] ?? 0);
$message  = '';
$message_type = '';
$payment_done = false;

if ($order_id === 0) {
    header("Location: index.php");
    exit();
}

$order      = null;
$order_items = [];

try {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header("Location: index.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = 'Error loading order details.';
    $message_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    $method = trim($_POST['payment_method'] ?? '');

    $allowed_methods = ['credit_card', 'debit_card', 'gcash', 'maya', 'cod'];

    if (!in_array($method, $allowed_methods)) {
        $message = 'Please select a valid payment method.';
        $message_type = 'error';
    } else {
        try {
            $transaction_ref = strtoupper(uniqid('NXT-'));

            $stmt = $conn->prepare("
                INSERT INTO payments (order_id, user_id, amount, method, status, transaction_ref)
                VALUES (?, ?, ?, ?, 'completed', ?)
            ");
            $stmt->execute([$order_id, $user_id, $order['total'], $method, $transaction_ref]);

            $stmt = $conn->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
            $stmt->execute([$order_id]);

            $payment_done = true;
            $message = 'Payment successful! Your order is now being processed.';
            $message_type = 'success';

        } catch (PDOException $e) {
            $message = 'Error processing payment. Please try again.';
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
    <title>Payment - NEXTECH</title>
    <link rel="stylesheet" href="css/cart.css">
    <style>
        .payment-section {
            max-width: 900px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .payment-box {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 2rem;
        }

        .payment-box h2 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
        }

        .order-item-row {
            display: flex;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.9rem;
        }

        .order-item-row:last-child {
            border-bottom: none;
        }

        .method-group {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .method-option {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.9rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }

        .method-option:hover {
            border-color: #1a1a1a;
            background: #f9f9f9;
        }

        .method-option input[type="radio"] {
            accent-color: #1a1a1a;
            width: 18px;
            height: 18px;
        }

        .method-option input[type="radio"]:checked + span {
            font-weight: 600;
        }

        .method-option:has(input:checked) {
            border-color: #1a1a1a;
            background: #f5f5f5;
        }

        .pay-btn {
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
        }

        .pay-btn:hover {
            background-color: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .pay-btn:disabled {
            background-color: #999;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .success-box {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem 2rem;
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 12px;
        }

        .success-box .success-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        .success-box h2 {
            font-size: 1.8rem;
            color: #2e7d32;
            margin-bottom: 0.5rem;
        }

        .success-box p {
            color: #388e3c;
            margin-bottom: 0.3rem;
            font-size: 0.95rem;
        }

        .success-box .ref {
            font-weight: 700;
            font-size: 1rem;
            color: #1a1a1a;
            margin: 1rem 0;
        }

        .success-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .btn-outline {
            padding: 0.8rem 1.5rem;
            border: 2px solid #1a1a1a;
            background: #fff;
            color: #1a1a1a;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-outline:hover {
            background: #1a1a1a;
            color: #fff;
        }

        .btn-solid {
            padding: 0.8rem 1.5rem;
            background: #1a1a1a;
            color: #fff;
            border: 2px solid #1a1a1a;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-solid:hover {
            background: #333;
        }

        @media (max-width: 768px) {
            .payment-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <li><a href="cart.php" class="nav-link">Cart</a></li>
                <li><a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
            </ul>
            <div class="cart-icon"><i class="bi bi-cart3"></i></div>
        </div>
    </nav>

    <section class="page-header">
        <h1>Payment</h1>
        <p>Complete your order</p>
    </section>

    <?php if ($message && !$payment_done): ?>
        <div class="message message-<?php echo $message_type; ?>" style="max-width:900px; margin: 1rem auto; padding: 1rem 2rem;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <section class="payment-section">

        <?php if ($payment_done): ?>
            <div class="success-box">
                <div class="success-icon"><i class="bi bi-check-circle-fill"></i></div>
                <h2>Payment Successful!</h2>
                <p>Thank you, <?php echo htmlspecialchars($_SESSION['username']); ?>! Your order has been placed.</p>
                <p>Order #<?php echo $order_id; ?> is now being processed.</p>
                <p class="ref">Transaction Reference: <?php echo htmlspecialchars($transaction_ref ?? ''); ?></p>
                <div class="success-actions">
                    <a href="product.php" class="btn-outline">Continue Shopping</a>
                    <a href="index.php" class="btn-solid">Go to Home</a>
                </div>
            </div>

        <?php else: ?>

            <div class="payment-box">
                <h2>Order #<?php echo $order_id; ?> Summary</h2>
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item-row">
                        <span><?php echo htmlspecialchars($item['product_name']); ?> x<?php echo $item['quantity']; ?></span>
                        <span>$<?php echo number_format($item['line_total'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="order-item-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
                </div>
                <div class="order-item-row">
                    <span>Shipping</span>
                    <span><?php echo $order['shipping'] == 0 ? 'Free' : '$' . number_format($order['shipping'], 2); ?></span>
                </div>
                <div class="order-item-row">
                    <span>Tax (8%)</span>
                    <span>$<?php echo number_format($order['tax'], 2); ?></span>
                </div>
                <div class="order-item-row" style="font-weight:700; font-size:1rem; margin-top:0.5rem;">
                    <span>Total</span>
                    <span>$<?php echo number_format($order['total'], 2); ?></span>
                </div>
            </div>

            <div class="payment-box">
                <h2>Select Payment Method</h2>
                <form method="POST">
                    <div class="method-group">
                        <label class="method-option">
                            <input type="radio" name="payment_method" value="credit_card" required>
                            <span><i class="bi bi-credit-card"></i> Credit Card</span>
                        </label>
                        <label class="method-option">
                            <input type="radio" name="payment_method" value="debit_card">
                            <span><i class="bi bi-bank"></i> Debit Card</span>
                        </label>
                        <label class="method-option">
                            <input type="radio" name="payment_method" value="gcash">
                            <span><i class="bi bi-phone"></i> GCash</span>
                        </label>
                        <label class="method-option">
                            <input type="radio" name="payment_method" value="maya">
                            <span><i class="bi bi-phone"></i> Maya</span>
                        </label>
                        <label class="method-option">
                            <input type="radio" name="payment_method" value="cod">
                            <span><i class="bi bi-box-seam"></i> Cash on Delivery</span>
                        </label>
                    </div>
                    <button type="submit" name="pay_now" class="pay-btn">Pay $<?php echo number_format($order['total'], 2); ?></button>
                </form>
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
