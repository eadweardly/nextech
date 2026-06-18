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

$cart_items = [];
$subtotal = 0.0;

try {
    $stmt = $conn->prepare("
        SELECT c.id, c.quantity, c.variant_id,
               p.id as product_id, p.name, p.price,
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
        $subtotal += $effective_price * $item['quantity'];
    }
} catch (PDOException $e) {
    $message = 'Error loading cart.';
    $message_type = 'error';
}

$shipping = $subtotal >= 100 ? 0.00 : 9.99;
$tax      = $subtotal * 0.08;
$total    = $subtotal + $shipping + $tax;

$addresses = [];
try {
    $stmt = $conn->prepare("SELECT * FROM delivery_address WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (empty($cart_items)) {
        $message = 'Your cart is empty.';
        $message_type = 'error';
    } else {
        $address_id = null;

        try {
            $conn->beginTransaction();

            $address_choice = $_POST['address_choice'] ?? 'new';

            if ($address_choice === 'new') {
                $secondary_address = trim($_POST['secondary_address'] ?? '');
                $region   = trim($_POST['region'] ?? '');
                $province = trim($_POST['province'] ?? '');
                $city     = trim($_POST['city'] ?? '');
                $barangay = trim($_POST['barangay'] ?? '');

                if (empty($region) || empty($province) || empty($city) || empty($barangay)) {
                    throw new Exception('Please complete the delivery address.');
                }

                $stmt = $conn->prepare("
                    INSERT INTO delivery_address (user_id, secondary_address, region, province, city, barangay)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $secondary_address, $region, $province, $city, $barangay]);
                $address_id = $conn->lastInsertId();
            } else {
                $address_id = intval($address_choice);
                $stmt = $conn->prepare("SELECT id FROM delivery_address WHERE id = ? AND user_id = ?");
                $stmt->execute([$address_id, $user_id]);
                if (!$stmt->fetch()) {
                    throw new Exception('Invalid address selected.');
                }
            }

            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, address_id, status, subtotal, shipping, tax, total)
                VALUES (?, ?, 'pending', ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $address_id, $subtotal, $shipping, $tax, $total]);
            $order_id = $conn->lastInsertId();

            $item_stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, variant_id, product_name, quantity, unit_price, line_total)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($cart_items as $item) {
                $effective_price = $item['variant_id'] ? (float)$item['variant_price'] : (float)$item['price'];
                $line_total = $effective_price * $item['quantity'];
                $product_name = $item['name'];
                if ($item['variant_id']) {
                    $product_name .= ' (' . $item['variant_description'] . ')';
                }
                $item_stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['variant_id'],
                    $product_name,
                    $item['quantity'],
                    $effective_price,
                    $line_total
                ]);
            }

            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $conn->commit();

            header("Location: payment.php?order_id=" . $order_id);
            exit();

        } catch (Exception $e) {
            $conn->rollBack();
            $message = $e->getMessage();
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
            margin-bottom: 2rem;
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

        .order-item-variant {
            font-size: 0.8rem;
            color: var(--accent);
            font-weight: 600;
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

        .address-option {
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            padding: 0.9rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .address-option:hover {
            border-color: var(--accent);
            background: #f9f9f9;
        }

        .address-option input[type="radio"] {
            margin-top: 0.2rem;
            accent-color: var(--accent);
            width: 18px;
            height: 18px;
        }

        .address-text {
            font-size: 0.9rem;
            color: #333;
            line-height: 1.5;
        }

        .address-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .address-form .full-width {
            grid-column: 1 / -1;
        }

        .address-form label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: #333;
        }

        .address-form input {
            width: 100%;
            padding: 0.7rem 0.9rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .address-form input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        @media (max-width: 768px) {
            .checkout-section {
                grid-template-columns: 1fr;
            }
            .address-form {
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
                <li><a href="feedback.php" class="nav-link">Feedback</a></li>
                <li><a href="concerns.php" class="nav-link">Concerns</a></li>
                <li><a href="cart.php" class="nav-link">Cart</a></li>
                <li><a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
            </ul>
            <div class="cart-icon"><i class="bi bi-cart3"></i></div>
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

            <div>
                <div class="checkout-box">
                    <h2>Order Items (<?php echo count($cart_items); ?>)</h2>
                    <?php foreach ($cart_items as $item): ?>
                        <?php $effective_price = $item['variant_id'] ? (float)$item['variant_price'] : (float)$item['price']; ?>
                        <div class="order-item">
                            <div class="order-item-info">
                                <span class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                <?php if ($item['variant_id']): ?>
                                    <span class="order-item-variant"><?php echo htmlspecialchars($item['variant_description']); ?></span>
                                <?php endif; ?>
                                <span class="order-item-qty">Qty: <?php echo $item['quantity']; ?> &times; $<?php echo number_format($effective_price, 2); ?></span>
                            </div>
                            <span class="order-item-price">₱<?php echo number_format($effective_price * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" id="checkoutForm">
                    <div class="checkout-box">
                        <h2>Delivery Address</h2>

                        <?php foreach ($addresses as $addr): ?>
                            <label class="address-option">
                                <input type="radio" name="address_choice" value="<?php echo $addr['id']; ?>">
                                <span class="address-text">
                                    <?php echo htmlspecialchars($addr['secondary_address'] ? $addr['secondary_address'] . ', ' : ''); ?>
                                    <?php echo htmlspecialchars($addr['barangay']); ?>, <?php echo htmlspecialchars($addr['city']); ?>,
                                    <?php echo htmlspecialchars($addr['province']); ?>, <?php echo htmlspecialchars($addr['region']); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>

                        <label class="address-option">
                            <input type="radio" name="address_choice" value="new" <?php echo empty($addresses) ? 'checked' : ''; ?>>
                            <span class="address-text"><strong>Use a new address</strong></span>
                        </label>

                        <div class="address-form" id="newAddressForm">
                            <div class="full-width">
                                <label for="secondary_address">House No. / Street (optional)</label>
                                <input type="text" id="secondary_address" name="secondary_address" placeholder="e.g. Lot 18, Block 5">
                            </div>
                            <div>
                                <label for="region">Region</label>
                                <input type="text" id="region" name="region" placeholder="e.g. Region IV-A (CALABARZON)">
                            </div>
                            <div>
                                <label for="province">Province</label>
                                <input type="text" id="province" name="province" placeholder="e.g. Batangas">
                            </div>
                            <div>
                                <label for="city">City / Municipality</label>
                                <input type="text" id="city" name="city" placeholder="e.g. Sto. Tomas">
                            </div>
                            <div>
                                <label for="barangay">Barangay</label>
                                <input type="text" id="barangay" name="barangay" placeholder="e.g. San Vicente">
                            </div>
                        </div>
                    </div>

                    <div class="cart-summary">
                        <h3>Order Summary</h3>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>₱<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span><?php echo $shipping == 0 ? 'Free' : '₱' . number_format($shipping, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (8%)</span>
                            <span>₱<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>₱<?php echo number_format($total, 2); ?></span>
                        </div>

                        <button type="submit" name="place_order" class="place-order-btn">Place Order</button>
                    </div>
                </form>

                <a href="cart.php" class="back-to-cart">← Back to Cart</a>
            </div>

        <?php else: ?>
            <div class="empty-checkout">
                <div class="empty-icon"><i class="bi bi-cart3"></i></div>
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

    <script>
        const newAddressForm = document.getElementById('newAddressForm');
        const radios = document.querySelectorAll('input[name="address_choice"]');

        function toggleNewAddressForm() {
            const selected = document.querySelector('input[name="address_choice"]:checked');
            if (selected && selected.value === 'new') {
                newAddressForm.style.display = 'grid';
            } else {
                newAddressForm.style.display = 'none';
            }
        }

        radios.forEach(r => r.addEventListener('change', toggleNewAddressForm));
        toggleNewAddressForm();
    </script>

</body>
</html>