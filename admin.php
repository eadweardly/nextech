<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_usernames = ['admin', 'nextech_admin', 'hihi'];

$is_admin = false;

try {
    $stmt = $conn->prepare("SELECT id, username, is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin_user) {
        if (!empty($admin_user['is_admin'])) {
            $is_admin = true;
        } elseif (in_array($admin_user['username'], $admin_usernames)) {
            $is_admin = true;
        }
    }
} catch (PDOException $e) {
    try {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin_user && in_array($admin_user['username'], $admin_usernames)) {
            $is_admin = true;
        }
    } catch (PDOException $e2) {
        $is_admin = false;
    }
}

if (!$is_admin && !empty($_SESSION['username']) && in_array($_SESSION['username'], $admin_usernames)) {
    $is_admin = true;
}

if (!$is_admin) {
    header("Location: index.php");
    exit();
}

$active_tab = $_GET['tab'] ?? 'dashboard';
$message    = '';
$msg_type   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $stock       = intval($_POST['stock'] ?? 0);
    $category    = trim($_POST['category'] ?? '');
    $image_url   = trim($_POST['image_url'] ?? '');

    if (empty($name) || $price <= 0 || empty($category)) {
        $message  = 'Name, price, and category are required.';
        $msg_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, category, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $description, $price, $stock, $category, $image_url]);
            $message  = 'Product added successfully.';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $message  = 'Error adding product: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }
    $active_tab = 'products';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id          = intval($_POST['product_id']);
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $stock       = intval($_POST['stock'] ?? 0);
    $category    = trim($_POST['category'] ?? '');
    $image_url   = trim($_POST['image_url'] ?? '');

    try {
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category=?, image_url=? WHERE id=?");
        $stmt->execute([$name, $description, $price, $stock, $category, $image_url, $id]);
        $message  = 'Product updated successfully.';
        $msg_type = 'success';
    } catch (PDOException $e) {
        $message  = 'Error updating product.';
        $msg_type = 'error';
    }
    $active_tab = 'products';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $id = intval($_POST['product_id']);
    try {
        $conn->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        $message  = 'Product deleted.';
        $msg_type = 'success';
    } catch (PDOException $e) {
        $message  = 'Cannot delete — product may be linked to orders.';
        $msg_type = 'error';
    }
    $active_tab = 'products';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $order_id   = intval($_POST['order_id']);
    $new_status = $_POST['order_status'] ?? '';
    $allowed    = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $allowed)) {
        try {
            $conn->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
            $message  = "Order #$order_id status updated to '$new_status'.";
            $msg_type = 'success';
        } catch (PDOException $e) {
            $message  = 'Error updating order status.';
            $msg_type = 'error';
        }
    }
    $active_tab = 'orders';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_concern'])) {
    $concern_id = intval($_POST['concern_id']);
    try {
        $conn->prepare("UPDATE concerns SET status = 'resolved' WHERE id = ?")->execute([$concern_id]);
        $message  = "Concern #$concern_id marked as resolved.";
        $msg_type = 'success';
    } catch (PDOException $e) {
        $message  = 'Error updating concern.';
        $msg_type = 'error';
    }
    $active_tab = 'concerns';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = intval($_POST['user_id']);
    if ($uid === (int)$_SESSION['user_id']) {
        $message  = 'You cannot delete your own account.';
        $msg_type = 'error';
    } else {
        try {
            $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
            $message  = 'User deleted.';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $message  = 'Cannot delete user — they may have linked orders.';
            $msg_type = 'error';
        }
    }
    $active_tab = 'users';
}

$stats = ['users' => 0, 'products' => 0, 'orders' => 0, 'revenue' => 0, 'pending_orders' => 0, 'open_concerns' => 0];
try {
    $stats['users']         = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['products']      = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['orders']        = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['revenue']       = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'")->fetchColumn();
    $stats['pending_orders']= $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
    $stats['open_concerns'] = $conn->query("SELECT COUNT(*) FROM concerns WHERE status='open'")->fetchColumn();
} catch (PDOException $e) {}

$recent_orders = [];
try {
    $recent_orders = $conn->query("SELECT o.id, o.status, o.total, o.created_at, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$products = [];
try {
    $products = $conn->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$orders = [];
try {
    $orders = $conn->query("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$users = [];
try {
    $users = $conn->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}


$payments = [];
try {
    $payments = $conn->query("SELECT p.*, u.username FROM payments p JOIN users u ON p.user_id = u.id ORDER BY p.paid_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$feedbacks = [];
try {
    $feedbacks = $conn->query("SELECT f.*, u.username FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$concerns = [];
try {
    $concerns = $conn->query("SELECT c.*, u.username FROM concerns c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$edit_product = null;
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([intval($_GET['edit_id'])]);
        $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
        $active_tab   = 'products';
    } catch (PDOException $e) {}
}

$categories = ['PC Components & GPUs', 'Laptops & Monitors', 'Gaming Peripherals', 'Smartphones & Accessories', 'Audio & Headphones'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - NEXTECH</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<button class="mobile-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')"><i class="bi bi-list"></i></button>

<aside class="sidebar">
    <div class="sidebar-logo">
        NEXTECH
        <span>Admin Panel</span>
    </div>
    <nav class="sidebar-nav">
        <?php
        $tabs = [
            'dashboard' => ['icon' => '<i class="bi bi-bar-chart-fill"></i>', 'label' => 'Dashboard'],
            'products'  => ['icon' => '<i class="bi bi-box-seam"></i>', 'label' => 'Products'],
            'orders'    => ['icon' => '<i class="bi bi-cart3"></i>', 'label' => 'Orders'],
            'users'     => ['icon' => '<i class="bi bi-people-fill"></i>', 'label' => 'Users'],
            'payments'  => ['icon' => '<i class="bi bi-credit-card"></i>', 'label' => 'Payments'],
            'feedbacks' => ['icon' => '⭐', 'label' => 'Feedback'],
            'concerns'  => ['icon' => '<i class="bi bi-envelope-arrow-down"></i>', 'label' => 'Concerns'],
        ];
        foreach ($tabs as $key => $info):
        ?>
            <a href="?tab=<?php echo $key; ?>" class="nav-item <?php echo $active_tab === $key ? 'active' : ''; ?>">
                <span class="icon"><?php echo $info['icon']; ?></span>
                <?php echo $info['label']; ?>
                <?php if ($key === 'concerns' && $stats['open_concerns'] > 0): ?>
                    <span style="margin-left:auto; background:rgba(255,255,255,.2); border-radius:100px; padding:1px 7px; font-size:0.72rem;"><?php echo $stats['open_concerns']; ?></span>
                <?php endif; ?>
                <?php if ($key === 'orders' && $stats['pending_orders'] > 0): ?>
                    <span style="margin-left:auto; background:rgba(255,255,255,.2); border-radius:100px; padding:1px 7px; font-size:0.72rem;"><?php echo $stats['pending_orders']; ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        Administrator
        <br><br>
        <a href="index.php">← Back to Site</a> &nbsp;|&nbsp; <a href="logout.php">Logout</a>
    </div>
</aside>

<main class="main">

    <?php if ($message): ?>
        <div class="toast <?php echo $msg_type; ?>">
            <?php echo $msg_type === 'success' ? '<i class="bi bi-check-circle-fill"></i>' : '<i class="bi bi-x-circle-fill"></i>'; ?>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>


    <?php if ($active_tab === 'dashboard'): ?>

        <div class="page-title">Dashboard</div>
        <div class="page-subtitle">Overview of your NEXTECH store</div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo number_format($stats['users']); ?></div>
                <div class="stat-sub">Registered accounts</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Products</div>
                <div class="stat-value"><?php echo number_format($stats['products']); ?></div>
                <div class="stat-sub">In catalog</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?php echo number_format($stats['orders']); ?></div>
                <div class="stat-sub"><?php echo $stats['pending_orders']; ?> pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Revenue</div>
                <div class="stat-value">$<?php echo number_format($stats['revenue'], 2); ?></div>
                <div class="stat-sub">From completed payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Open Concerns</div>
                <div class="stat-value"><?php echo number_format($stats['open_concerns']); ?></div>
                <div class="stat-sub">Awaiting response</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Recent Orders</h2> <a href="?tab=orders" class="btn btn-secondary btn-sm">View All</a></div>
            <div class="table-wrap">
                <?php if (!empty($recent_orders)): ?>
                <table>
                    <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_orders as $row): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>$<?php echo number_format($row['total'], 2); ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty"><div class="icon"><i class="bi bi-clipboard-check"></i></div>No orders yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($active_tab === 'products'): ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem;">
            <div class="page-title">Products</div>
            <a href="?tab=products&add=1" class="btn btn-primary">+ Add Product</a>
        </div>
        <div class="page-subtitle">Manage your product catalog</div>

        <?php if (isset($_GET['add']) || $edit_product): ?>
        <div class="modal-overlay" onclick="if(event.target===this)window.location='?tab=products'">
            <div class="modal">
                <button class="modal-close" onclick="window.location='?tab=products'">×</button>
                <h3><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h3>
                <form method="POST">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required maxlength="150">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Price (USD) *</label>
                            <input type="number" name="price" class="form-input" step="0.01" min="0.01" value="<?php echo htmlspecialchars($edit_product['price'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stock Quantity *</label>
                            <input type="number" name="stock" class="form-input" min="0" value="<?php echo htmlspecialchars($edit_product['stock'] ?? 0); ?>" required>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Category *</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (($edit_product['category'] ?? '') === $cat) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Image URL</label>
                            <input type="text" name="image_url" class="form-input" placeholder="https://... or /uploads/products/filename.jpg" value="<?php echo htmlspecialchars($edit_product['image_url'] ?? ''); ?>">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-textarea"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <?php if ($edit_product): ?>
                            <button type="submit" name="edit_product" class="btn btn-primary">Save Changes</button>
                        <?php else: ?>
                            <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                        <?php endif; ?>
                        <a href="?tab=products" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h2>All Products (<?php echo count($products); ?>)</h2></div>
            <div class="table-wrap">
                <?php if (!empty($products)): ?>
                <table>
                    <thead><tr><th>Img</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Added</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <?php if (!empty($p['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($p['image_url']); ?>" class="img-thumb" alt="">
                                <?php else: ?>
                                    <div class="img-thumb" style="display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="bi bi-box-seam"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['category']); ?></td>
                            <td>$<?php echo number_format($p['price'], 2); ?></td>
                            <td><?php echo (int)$p['stock']; ?></td>
                            <td>
                                <?php if ($p['stock'] > 0): ?>
                                    <span class="badge badge-in-stock">In Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-out-stock">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($p['created_at'])); ?></td>
                            <td>
                                <div style="display:flex;gap:0.4rem;">
                                    <a href="?tab=products&edit_id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" onsubmit="return confirm('Delete this product?')">
                                        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty"><div class="icon"><i class="bi bi-box-seam"></i></div>No products found. <a href="?tab=products&add=1">Add one now.</a></div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($active_tab === 'orders'): ?>

        <div class="page-title">Orders</div>
        <div class="page-subtitle">Manage all customer orders</div>

        <div class="card">
            <div class="card-header"><h2>All Orders (<?php echo count($orders); ?>)</h2></div>
            <div class="table-wrap">
                <?php if (!empty($orders)): ?>
                <table>
                    <thead><tr><th>Order #</th><th>Customer</th><th>Email</th><th>Subtotal</th><th>Ship</th><th>Tax</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?php echo $o['id']; ?></td>
                            <td><?php echo htmlspecialchars($o['username']); ?></td>
                            <td style="color:var(--muted)"><?php echo htmlspecialchars($o['email']); ?></td>
                            <td>$<?php echo number_format($o['subtotal'], 2); ?></td>
                            <td><?php echo $o['shipping'] == 0 ? 'Free' : '$'.number_format($o['shipping'],2); ?></td>
                            <td>$<?php echo number_format($o['tax'], 2); ?></td>
                            <td><strong>$<?php echo number_format($o['total'], 2); ?></strong></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($o['status']); ?>"><?php echo htmlspecialchars($o['status']); ?></span></td>
                            <td><?php echo date('M j, Y', strtotime($o['created_at'])); ?></td>
                            <td>
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                    <select name="order_status">
                                        <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                            <option value="<?php echo $s; ?>" <?php echo $o['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="update_order_status" class="btn btn-primary btn-sm">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty"><div class="icon"><i class="bi bi-cart3"></i></div>No orders yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($active_tab === 'users'): ?>

        <div class="page-title">Users</div>
        <div class="page-subtitle">All registered customer accounts</div>

        <div class="card">
            <div class="card-header"><h2>All Users (<?php echo count($users); ?>)</h2></div>
            <div class="table-wrap">
                <?php if (!empty($users)): ?>
                <table>
                    <thead><tr><th>#</th><th>Username</th><th>Email</th><th>Joined</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge badge-processing" style="margin-left:4px;">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" onsubmit="return confirm('Delete user <?php echo htmlspecialchars(addslashes($u['username'])); ?>? This cannot be undone.')">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                                <?php else: ?>
                                    <span style="color:var(--muted);font-size:0.78rem;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty"><div class="icon"><i class="bi bi-people-fill"></i></div>No users found.</div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($active_tab === 'payments'): ?>

        <div class="page-title">Payments</div>
        <div class="page-subtitle">All transaction records</div>

        <div class="card">
            <div class="card-header"><h2>Transactions (<?php echo count($payments); ?>)</h2></div>
            <div class="table-wrap">
                <?php if (!empty($payments)): ?>
                <table>
                    <thead><tr><th>#</th><th>Customer</th><th>Order #</th><th>Amount</th><th>Method</th><th>Status</th><th>Reference</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><?php echo $pay['id']; ?></td>
                            <td><?php echo htmlspecialchars($pay['username']); ?></td>
                            <td>#<?php echo $pay['order_id']; ?></td>
                            <td><strong>$<?php echo number_format($pay['amount'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars(str_replace('_', ' ', $pay['method'])); ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($pay['status']); ?>"><?php echo htmlspecialchars($pay['status']); ?></span></td>
                            <td style="font-family:monospace;font-size:0.78rem;"><?php echo htmlspecialchars($pay['transaction_ref']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($pay['paid_at'])); ?></td>>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty"><div class="icon"><i class="bi bi-credit-card"></i></div>No payments recorded yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($active_tab === 'feedbacks'): ?>

        <div class="page-title">Customer Feedback</div>
        <div class="page-subtitle">What customers are saying about NEXTECH</div>

        <div class="card">
            <div class="card-header"><h2>All Feedback (<?php echo count($feedbacks); ?>)</h2></div>
            <div class="table-wrap">
                <?php if (!empty($feedbacks)): ?>
                <table>
                    <thead><tr><th>#</th><th>Customer</th><th>Rating</th><th>Message</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($feedbacks as $f): ?>
                        <tr>
                            <td><?php echo $f['id']; ?></td>
                            <td><?php echo htmlspecialchars($f['username']); ?></td>
                            <td>
                                <span class="stars"><?php echo str_repeat('★', $f['rating']) . str_repeat('☆', 5 - $f['rating']); ?></span>
                                <span style="color:var(--muted);font-size:0.78rem;margin-left:4px;">(<?php echo $f['rating']; ?>/5)</span>
                            </td>
                            <td style="max-width:320px;"><?php echo htmlspecialchars($f['message']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($f['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty"><div class="icon">⭐</div>No feedback submitted yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($active_tab === 'concerns'): ?>

        <div class="page-title">Customer Concerns</div>
        <div class="page-subtitle">Support tickets from your customers</div>

        <div class="card">
            <div class="card-header"><h2>All Concerns (<?php echo count($concerns); ?>)</h2></div>
            <div class="table-wrap">
                <?php if (!empty($concerns)): ?>
                <table>
                    <thead><tr><th>Ticket #</th><th>Customer</th><th>Message</th><th>Status</th><th>Submitted</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($concerns as $c): ?>
                        <tr>
                            <td>#<?php echo $c['id']; ?></td>
                            <td><?php echo htmlspecialchars($c['username']); ?></td>
                            <td style="max-width:340px;"><?php echo htmlspecialchars($c['message']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo htmlspecialchars($c['status']); ?>">
                                    <?php echo htmlspecialchars($c['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($c['created_at'])); ?></td>
                            <td>
                                <?php if ($c['status'] === 'open'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="concern_id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" name="resolve_concern" class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i> Resolve</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:var(--muted);font-size:0.78rem;">Resolved</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty"><div class="icon"><i class="bi bi-envelope-arrow-down"></i></div>No concerns submitted yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

</main>

<script>
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const overlay = document.querySelector('.modal-overlay');
            if (overlay) window.location = '?tab=products';
        }
    });
</script>

</body>
</html>