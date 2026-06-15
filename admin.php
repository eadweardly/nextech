<?php
session_start();
require_once 'db_config.php';

// ── Admin Guard ───────────────────────────────────────────────────────────────
// OPTION A (recommended): Add `is_admin TINYINT(1) DEFAULT 0` to your users table
//   and set it to 1 for admin accounts.
// OPTION B (quick start): Hardcode admin usernames below.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Hardcoded admin usernames - add your own username to this list
$admin_usernames = ['admin', 'nextech_admin', 'hihi']; // <- your username 'hihi' is included

// Check admin status
$is_admin = false;

// Step 1: Try with is_admin column (if it exists in DB)
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
    // Step 2: is_admin column doesn't exist - query without it
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

// Step 3: Final safety net - trust session username if DB checks above failed
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

// ─────────────────────────────────────────────────────────────────────────────
// POST HANDLERS
// ─────────────────────────────────────────────────────────────────────────────

// ── Add Product ──────────────────────────────────────────────────────────────
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

// ── Edit Product ─────────────────────────────────────────────────────────────
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

// ── Delete Product ────────────────────────────────────────────────────────────
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

// ── Update Order Status ───────────────────────────────────────────────────────
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

// ── Update Concern Status ─────────────────────────────────────────────────────
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

// ── Delete User ───────────────────────────────────────────────────────────────
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

// ─────────────────────────────────────────────────────────────────────────────
// DATA FETCHING
// ─────────────────────────────────────────────────────────────────────────────

// Dashboard stats
$stats = ['users' => 0, 'products' => 0, 'orders' => 0, 'revenue' => 0, 'pending_orders' => 0, 'open_concerns' => 0];
try {
    $stats['users']         = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['products']      = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['orders']        = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['revenue']       = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'")->fetchColumn();
    $stats['pending_orders']= $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
    $stats['open_concerns'] = $conn->query("SELECT COUNT(*) FROM concerns WHERE status='open'")->fetchColumn();
} catch (PDOException $e) { /* silently skip */ }

// Recent orders (for dashboard)
$recent_orders = [];
try {
    $recent_orders = $conn->query("SELECT o.id, o.status, o.total, o.created_at, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Products
$products = [];
try {
    $products = $conn->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Orders (full list)
$orders = [];
try {
    $orders = $conn->query("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Users
$users = [];
try {
    $users = $conn->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Payments
$payments = [];
try {
    $payments = $conn->query("SELECT p.*, u.username FROM payments p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Feedbacks
$feedbacks = [];
try {
    $feedbacks = $conn->query("SELECT f.*, u.username FROM feedbacks f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Concerns
$concerns = [];
try {
    $concerns = $conn->query("SELECT c.*, u.username FROM concerns c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Product for edit modal
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
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary-white: #ffffff;
            --primary-grey:  #1a1a1a;
            --secondary-grey:#333333;
            --light-grey:    #f5f5f5;
            --border-grey:   #e0e0e0;
            --text-primary:  #1a1a1a;
            --text-secondary:#666666;
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --radius: 0.5rem;

            --success: #1a7a4c;
            --success-bg: #eaf6f0;
            --error:   #b3261e;
            --error-bg:#fdecea;
            --warning: #8a5a00;
            --warning-bg:#fdf4e3;
            --info:    #1a1a1a;
            --info-bg: #f0f0f0;

            --sidebar-w: 240px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            color: var(--text-primary);
            background-color: var(--light-grey);
            line-height: 1.6;
            letter-spacing: 0.3px;
            display: flex;
            min-height: 100vh;
            font-size: 0.92rem;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--primary-white);
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            overflow-y: auto;
            border-right: 1px solid var(--border-grey);
        }

        .sidebar-logo {
            padding: var(--spacing-md) var(--spacing-md) var(--spacing-sm);
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--primary-grey);
            border-bottom: 1px solid var(--border-grey);
            position: relative;
        }

        .sidebar-logo::after {
            content: '';
            display: block;
            height: 3px;
            margin-top: var(--spacing-sm);
            margin-left: calc(-1 * var(--spacing-md));
            margin-right: calc(-1 * var(--spacing-md));
            background: linear-gradient(90deg, #1a1a1a 0%, #555 50%, #1a1a1a 100%);
            background-size: 200% auto;
            animation: navShimmer 4s linear infinite;
        }

        @keyframes navShimmer {
            0%   { background-position: 0% center; }
            100% { background-position: 200% center; }
        }

        .sidebar-logo span { font-size: 0.65rem; display: block; font-weight: 500; color: var(--text-secondary); letter-spacing: 0.12em; margin-top: 4px; text-transform: uppercase; }

        .sidebar-nav { padding: var(--spacing-sm) 0; flex: 1; }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.7rem var(--spacing-md);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: var(--transition);
            border-left: 2px solid transparent;
        }

        .nav-item:hover { color: var(--primary-grey); background: var(--light-grey); }
        .nav-item.active { background: var(--light-grey); color: var(--primary-grey); border-left-color: var(--primary-grey); font-weight: 700; }
        .nav-item .icon { font-size: 1rem; width: 20px; text-align: center; }

        .sidebar-footer {
            padding: var(--spacing-sm) var(--spacing-md);
            border-top: 1px solid var(--border-grey);
            font-size: 0.78rem;
            color: var(--text-secondary);
        }

        .sidebar-footer strong { color: var(--primary-grey); display: block; margin-bottom: 2px; letter-spacing: 0.5px; }

        .sidebar-footer a { color: var(--text-secondary); text-decoration: none; font-size: 0.8rem; border-bottom: 1px solid transparent; transition: var(--transition); }
        .sidebar-footer a:hover { color: var(--primary-grey); border-bottom-color: var(--primary-grey); }

        /* ── Main ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: var(--spacing-lg);
            min-height: 100vh;
        }

        .page-title { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; letter-spacing: -0.5px; color: var(--primary-grey); }
        .page-subtitle { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: var(--spacing-md); }

        /* ── Toast ── */
        .toast {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.8rem 1.1rem;
            border-radius: var(--radius);
            margin-bottom: var(--spacing-md);
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid var(--border-grey);
        }
        .toast.success { background: var(--success-bg); color: var(--success); border-color: #cdebd9; }
        .toast.error   { background: var(--error-bg);   color: var(--error);   border-color: #f6cdc9; }

        /* ── Stat Cards ── */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: var(--spacing-sm); margin-bottom: var(--spacing-md); }
        .stat-card {
            background: var(--primary-white);
            border: 1px solid var(--border-grey);
            border-radius: var(--radius);
            padding: var(--spacing-md);
            transition: var(--transition);
        }
        .stat-card:hover { border-color: var(--primary-grey); transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
        .stat-label { font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem; font-weight: 600; }
        .stat-value { font-size: 1.9rem; font-weight: 700; line-height: 1; color: var(--primary-grey); letter-spacing: -0.5px; }
        .stat-sub   { font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.35rem; }

        /* ── Card ── */
        .card {
            background: var(--primary-white);
            border: 1px solid var(--border-grey);
            border-radius: var(--radius);
            margin-bottom: var(--spacing-md);
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-sm) var(--spacing-md);
            border-bottom: 1px solid var(--border-grey);
        }

        .card-header h2 { font-size: 1rem; font-weight: 700; letter-spacing: 0.3px; color: var(--primary-grey); }
        .card-body { padding: var(--spacing-md); }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th { text-align: left; padding: 0.7rem var(--spacing-sm); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary); border-bottom: 1px solid var(--border-grey); background: var(--light-grey); font-weight: 700; }
        td { padding: 0.75rem var(--spacing-sm); border-bottom: 1px solid var(--border-grey); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--light-grey); }

        /* ── Badges ── */
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 100px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: capitalize;
            letter-spacing: 0.3px;
            border: 1px solid transparent;
        }
        .badge-pending    { background: var(--warning-bg); color: var(--warning); border-color: #f3e2bb; }
        .badge-processing { background: var(--info-bg);    color: var(--info);   border-color: var(--border-grey); }
        .badge-shipped    { background: var(--success-bg); color: var(--success); border-color: #cdebd9; }
        .badge-delivered  { background: var(--success-bg); color: var(--success); border-color: #cdebd9; }
        .badge-cancelled  { background: var(--error-bg);   color: var(--error);  border-color: #f6cdc9; }
        .badge-completed  { background: var(--success-bg); color: var(--success); border-color: #cdebd9; }
        .badge-open       { background: var(--warning-bg); color: var(--warning); border-color: #f3e2bb; }
        .badge-resolved   { background: var(--success-bg); color: var(--success); border-color: #cdebd9; }
        .badge-in-stock   { background: var(--success-bg); color: var(--success); border-color: #cdebd9; }
        .badge-out-stock  { background: var(--error-bg);   color: var(--error);  border-color: #f6cdc9; }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1.1rem;
            border-radius: var(--radius);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid transparent;
            transition: var(--transition);
            text-decoration: none;
            letter-spacing: 0.3px;
        }
        .btn-primary   { background: var(--primary-grey); color: var(--primary-white); border-color: var(--primary-grey); }
        .btn-primary:hover { background: var(--primary-white); color: var(--primary-grey); }
        .btn-danger    { background: var(--error-bg); color: var(--error); border-color: #f6cdc9; }
        .btn-danger:hover  { background: var(--error); color: var(--primary-white); border-color: var(--error); }
        .btn-secondary { background: var(--primary-white); color: var(--primary-grey); border-color: var(--border-grey); }
        .btn-secondary:hover { background: var(--primary-grey); color: var(--primary-white); border-color: var(--primary-grey); }
        .btn-sm { padding: 0.35rem 0.8rem; font-size: 0.78rem; }
        .btn-success { background: var(--success-bg); color: var(--success); border-color: #cdebd9; }
        .btn-success:hover { background: var(--success); color: var(--primary-white); border-color: var(--success); }

        /* ── Forms ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-sm); }
        .form-grid .full { grid-column: 1 / -1; }
        .form-group { display: flex; flex-direction: column; gap: 0.35rem; }
        .form-label { font-size: 0.8rem; font-weight: 600; color: var(--primary-grey); letter-spacing: 0.3px; }
        .form-input, .form-select, .form-textarea {
            padding: 0.6rem 0.85rem;
            border: 1px solid var(--border-grey);
            border-radius: var(--radius);
            font-size: 0.875rem;
            width: 100%;
            background: var(--primary-white);
            color: var(--text-primary);
            font-family: inherit;
            transition: var(--transition);
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-grey);
            box-shadow: 0 0 0 3px rgba(26,26,26,.06);
        }
        .form-textarea { resize: vertical; min-height: 90px; }
        .form-actions { display: flex; gap: 0.75rem; margin-top: 0.5rem; }

        /* ── Inline status form ── */
        .status-form { display: flex; gap: 0.5rem; align-items: center; }
        .status-form select { padding: 0.35rem 0.6rem; border: 1px solid var(--border-grey); border-radius: var(--radius); font-size: 0.8rem; font-family: inherit; }

        /* ── Empty state ── */
        .empty { text-align: center; padding: var(--spacing-xl) var(--spacing-sm); color: var(--text-secondary); }
        .empty .icon { font-size: 2.5rem; margin-bottom: 0.5rem; opacity: 0.6; }
        .empty a { color: var(--primary-grey); font-weight: 600; border-bottom: 1px solid var(--primary-grey); text-decoration: none; }

        /* ── Mobile sidebar toggle ── */
        @media (max-width: 768px) {
            .sidebar { width: 200px; transform: translateX(-100%); transition: transform 0.25s; }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; padding: var(--spacing-sm); }
            .form-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .mobile-toggle { display: flex !important; }
        }

        .mobile-toggle {
            display: none;
            position: fixed;
            top: var(--spacing-sm); left: var(--spacing-sm);
            z-index: 200;
            background: var(--primary-grey);
            color: var(--primary-white);
            border: none;
            border-radius: var(--radius);
            padding: 0.5rem 0.7rem;
            cursor: pointer;
            font-size: 1.1rem;
        }

        /* ── Star rating ── */
        .stars { color: var(--primary-grey); letter-spacing: 1px; }

        /* ── Image thumb ── */
        .img-thumb { width: 44px; height: 44px; object-fit: contain; border-radius: var(--radius); border: 1px solid var(--border-grey); background: var(--light-grey); padding: 4px; }

        /* ── Overlay modal for add/edit ── */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(26,26,26,.45);
            z-index: 300;
            display: flex; align-items: center; justify-content: center;
            padding: var(--spacing-sm);
        }
        .modal {
            background: var(--primary-white);
            border-radius: var(--radius);
            width: 100%;
            max-width: 620px;
            max-height: 90vh;
            overflow-y: auto;
            padding: var(--spacing-lg);
            box-shadow: 0 20px 60px rgba(0,0,0,.18);
            border: 1px solid var(--border-grey);
        }
        .modal h3 { font-size: 1.2rem; font-weight: 700; margin-bottom: var(--spacing-md); letter-spacing: -0.3px; color: var(--primary-grey); }
        .modal-close { float: right; background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--text-secondary); margin-top: -4px; transition: var(--transition); }
        .modal-close:hover { color: var(--primary-grey); }
    </style>

</head>
<body>

<button class="mobile-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

<!-- ── Sidebar ── -->
<aside class="sidebar">
    <div class="sidebar-logo">
        NEXTECH
        <span>Admin Panel</span>
    </div>
    <nav class="sidebar-nav">
        <?php
        $tabs = [
            'dashboard' => ['icon' => '📊', 'label' => 'Dashboard'],
            'products'  => ['icon' => '📦', 'label' => 'Products'],
            'orders'    => ['icon' => '🛒', 'label' => 'Orders'],
            'users'     => ['icon' => '👥', 'label' => 'Users'],
            'payments'  => ['icon' => '💳', 'label' => 'Payments'],
            'feedbacks' => ['icon' => '⭐', 'label' => 'Feedback'],
            'concerns'  => ['icon' => '📩', 'label' => 'Concerns'],
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

<!-- ── Main Content ── -->
<main class="main">

    <?php if ($message): ?>
        <div class="toast <?php echo $msg_type; ?>">
            <?php echo $msg_type === 'success' ? '✅' : '❌'; ?>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════ DASHBOARD -->
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
                    <div class="empty"><div class="icon">📋</div>No orders yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <!-- ═══════════════════════════════════════════════════════ PRODUCTS -->
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
                                    <div class="img-thumb" style="display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📦</div>
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
                    <div class="empty"><div class="icon">📦</div>No products found. <a href="?tab=products&add=1">Add one now.</a></div>
                <?php endif; ?>
            </div>
        </div>

    <!-- ═══════════════════════════════════════════════════════ ORDERS -->
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
                    <div class="empty"><div class="icon">🛒</div>No orders yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <!-- ═══════════════════════════════════════════════════════ USERS -->
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
                    <div class="empty"><div class="icon">👥</div>No users found.</div>
                <?php endif; ?>
            </div>
        </div>

    <!-- ═══════════════════════════════════════════════════════ PAYMENTS -->
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
                            <td><?php echo date('M j, Y', strtotime($pay['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty"><div class="icon">💳</div>No payments recorded yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <!-- ═══════════════════════════════════════════════════════ FEEDBACKS -->
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

    <!-- ═══════════════════════════════════════════════════════ CONCERNS -->
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
                                        <button type="submit" name="resolve_concern" class="btn btn-success btn-sm">✓ Resolve</button>
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
                    <div class="empty"><div class="icon">📩</div>No concerns submitted yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

</main>

<script>
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const overlay = document.querySelector('.modal-overlay');
            if (overlay) window.location = '?tab=products';
        }
    });
</script>

</body>
</html>