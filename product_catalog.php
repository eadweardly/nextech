<?php
require_once 'header.php';

$products = [
    [
        'id' => 1,
        'name' => 'Logitech G Pro X Superlight 2',
        'price' => 159.99,
        'in_stock' => true,
        'category' => 'Gaming Peripherals',
        'description' => '60g wireless gaming mouse with HERO 2 25K sensor and LIGHTSPEED technology.'
    ],
    [
        'id' => 2,
        'name' => 'SteelSeries Apex Pro TKL Wireless',
        'price' => 219.99,
        'in_stock' => true,
        'category' => 'Gaming Peripherals',
        'description' => 'Adjustable OmniPoint 2.0 magnetic switches, OLED display, 2.4GHz wireless.'
    ],
    [
        'id' => 3,
        'name' => 'SteelSeries Arctis Nova Pro Wireless',
        'price' => 349.99,
        'in_stock' => false,
        'category' => 'Audio & Headphones',
        'description' => 'Dual wireless ANC gaming headset with hot-swappable battery and ClearCast Gen 2 mic.'
    ],
];

function formatCurrency(float $value): string {
    return '$' . number_format($value, 2);
}

function calculateShipping(float $subtotal): float {
    return $subtotal >= 100 ? 0.00 : 9.99;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));

    if (empty($_SESSION['user_logged_in'])) {
        $message = 'You must be logged in to add items to the cart.';
        $message_type = 'error';
    } else {
        $selectedProduct = null;
        foreach ($products as $product) {
            if ($product['id'] === $product_id) {
                $selectedProduct = $product;
                break;
            }
        }

        if ($selectedProduct === null) {
            $message = 'Product not found.';
            $message_type = 'error';
        } elseif (!$selectedProduct['in_stock']) {
            $message = 'Sorry, this product is out of stock.';
            $message_type = 'error';
        } else {
            $found = false;
            foreach ($_SESSION['cart'] as &$cartItem) {
                if ($cartItem['id'] === $product_id) {
                    $cartItem['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            unset($cartItem);

            if (!$found) {
                $_SESSION['cart'][] = [
                    'id'       => $selectedProduct['id'],
                    'name'     => $selectedProduct['name'],
                    'price'    => $selectedProduct['price'],
                    'quantity' => $quantity,
                ];
            }
            $message = 'Product added to cart successfully.';
            $message_type = 'success';
        }
    }
}

$cartSubtotal = 0.0;
foreach ($_SESSION['cart'] as $cartItem) {
    $cartSubtotal += $cartItem['price'] * $cartItem['quantity'];
}
$shipping = calculateShipping($cartSubtotal);
$tax   = $cartSubtotal * 0.07;
$total = $cartSubtotal + $shipping + $tax;
?>

<section class="card">
    <h2>Product Catalog</h2>
    <p>This demo page uses PHP variables, data types, loops, operators, logic control, functions, and form handling.</p>
</section>

<?php if ($message): ?>
    <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<section class="card">
    <h3>Available Products</h3>
    <?php foreach ($products as $product): ?>
        <div style="padding: 16px; border-bottom: 1px solid #e1e1e1;">
            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
            <p><?php echo htmlspecialchars($product['description']); ?></p>
            <p>Category: <?php echo htmlspecialchars($product['category']); ?></p>
            <p>Price: <?php echo formatCurrency($product['price']); ?></p>
            <p>Stock status:
                <?php if ($product['in_stock']): ?>
                    <span style="color: green;">In stock</span>
                <?php else: ?>
                    <span style="color: red;">Out of stock</span>
                <?php endif; ?>
            </p>
            <form method="POST" style="display: inline-block; margin-top: 8px;">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="number" name="quantity" min="1" value="1" style="width: 70px;">
                <button type="submit" name="add_to_cart" class="button" <?php echo $product['in_stock'] ? '' : 'disabled'; ?>>Add to Cart</button>
            </form>
        </div>
    <?php endforeach; ?>
</section>

<section class="card">
    <h3>Cart Summary</h3>
    <?php if (count($_SESSION['cart']) === 0): ?>
        <p>Your cart is empty. Add a product to begin checkout.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($_SESSION['cart'] as $item): ?>
                <li><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?> = <?php echo formatCurrency($item['price'] * $item['quantity']); ?></li>
            <?php endforeach; ?>
        </ul>
        <p>Subtotal: <?php echo formatCurrency($cartSubtotal); ?></p>
        <p>Shipping: <?php echo formatCurrency($shipping); ?></p>
        <p>Tax: <?php echo formatCurrency($tax); ?></p>
        <p><strong>Total: <?php echo formatCurrency($total); ?></strong></p>
        <form method="POST" action="checkout.php">
            <?php foreach ($_SESSION['cart'] as $item): ?>
                <input type="hidden" name="product_ids[]" value="<?php echo $item['id']; ?>">
                <input type="hidden" name="quantities[]" value="<?php echo $item['quantity']; ?>">
            <?php endforeach; ?>
            <button type="submit" name="checkout" class="button">Go to Checkout</button>
        </form>
    <?php endif; ?>
</section>

<?php require_once 'footer.php'; ?>