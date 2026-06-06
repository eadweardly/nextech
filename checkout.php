<?php
require_once 'header.php';

function formatCurrency(float $value): string
{
    return '$' . number_format($value, 2);
}

function calculateShipping(float $subtotal): float
{
    return $subtotal >= 100 ? 0.00 : 9.99;
}

$orderItems = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $productIds = $_POST['product_ids'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

    if (count($productIds) !== count($quantities) || empty($productIds)) {
        $message = 'Checkout data is invalid. Please return to the product catalog.';
    } else {
        for ($i = 0; $i < count($productIds); $i++) {
            $id = intval($productIds[$i]);
            $qty = max(1, intval($quantities[$i]));

            // In a real system, product prices would come from the database.
            // For this demo, we map ids to fixed product values.
            $catalog = [
                1 => ['name' => 'Wireless Keyboard', 'price' => 49.99],
                2 => ['name' => 'USB-C Charging Hub', 'price' => 79.50],
                3 => ['name' => 'Gaming Headset', 'price' => 129.00],
            ];

            if (isset($catalog[$id])) {
                $product = $catalog[$id];
                $orderItems[] = [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $qty,
                    'line_total' => $product['price'] * $qty,
                ];
            }
        }
    }
}

$subtotal = 0.0;
foreach ($orderItems as $item) {
    $subtotal += $item['line_total'];
}
$shipping = calculateShipping($subtotal);
$tax = $subtotal * 0.07;
$total = $subtotal + $shipping + $tax;
?>

<section class="card">
    <h2>Checkout Calculation</h2>
    <p>This page processes the form data from the product catalog and calculates final totals.</p>
</section>

<?php if ($message): ?>
    <div class="message error"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (empty($orderItems)): ?>
    <section class="card">
        <h3>No checkout data received.</h3>
        <p>Please return to the <a href="product_catalog.php">Product Catalog</a> and add items to your cart.</p>
    </section>
<?php else: ?>
    <section class="card">
        <h3>Order Review</h3>
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="background:#f1f1f1; text-align:left;">
                    <th style="padding: 10px;">Product</th>
                    <th style="padding: 10px;">Qty</th>
                    <th style="padding: 10px;">Unit Price</th>
                    <th style="padding: 10px;">Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td style="padding: 10px; border-top: 1px solid #e1e1e1;"><?php echo htmlspecialchars($item['name']); ?></td>
                        <td style="padding: 10px; border-top: 1px solid #e1e1e1;"><?php echo $item['quantity']; ?></td>
                        <td style="padding: 10px; border-top: 1px solid #e1e1e1;"><?php echo formatCurrency($item['price']); ?></td>
                        <td style="padding: 10px; border-top: 1px solid #e1e1e1;"><?php echo formatCurrency($item['line_total']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 20px;">
            <p>Subtotal: <?php echo formatCurrency($subtotal); ?></p>
            <p>Shipping: <?php echo formatCurrency($shipping); ?></p>
            <p>Tax (7%): <?php echo formatCurrency($tax); ?></p>
            <p><strong>Total: <?php echo formatCurrency($total); ?></strong></p>
        </div>

        <form method="POST" action="checkout.php">
            <button type="submit" name="confirm_order" class="button">Confirm Order</button>
        </form>
    </section>
<?php endif; ?>

<?php require_once 'footer.php';
