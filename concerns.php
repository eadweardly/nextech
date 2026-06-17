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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_concern'])) {
    $concern_message = trim($_POST['message'] ?? '');

    if (empty($concern_message)) {
        $message = 'Please enter your concern.';
        $message_type = 'error';
    } elseif (strlen($concern_message) > 255) {
        $message = 'Your message is too long (max 255 characters).';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO concerns (user_id, message) VALUES (?, ?)");
            $stmt->execute([$user_id, $concern_message]);
            $message = 'Your concern has been submitted. Our support team will get back to you soon.';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error submitting concern. Please try again.';
            $message_type = 'error';
        }
    }
}

$concerns = [];
try {
    $stmt = $conn->prepare("SELECT * FROM concerns WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $concerns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Concerns - NEXTECH</title>
    <link rel="stylesheet" href="css/contact.css">
    <style>
        .concern-item {
            background-color: var(--light-grey);
            padding: 1.2rem 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border-grey);
            border-left: 3px solid var(--accent);
            margin-bottom: 1rem;
        }

        .concern-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .concern-id {
            font-weight: 700;
            color: var(--accent);
            font-size: 0.9rem;
        }

        .concern-status {
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 100px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-open {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-resolved {
            background-color: #ECFDF5;
            color: #065F46;
        }

        .concern-text {
            font-size: 0.95rem;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
        }

        .concern-date {
            font-size: 0.78rem;
            color: var(--text-secondary);
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
                <li><a href="concerns.php" class="nav-link active">Concerns</a></li>
                <li><a href="cart.php" class="nav-link">Cart</a></li>
                <li><a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
            </ul>
            <div class="cart-icon">
                <a href="cart.php"><i class="bi bi-cart3"></i></a>
            </div>
        </div>
    </nav>

    <section class="page-header">
        <h1>Submit a Concern</h1>
        <p>Let us know if you're experiencing any issues with your order or account</p>
    </section>

    <section class="contact-section">
        <div class="contact-container">

            <div class="contact-info">
                <div class="info-card">
                    <span class="info-icon"><i class="bi bi-envelope-arrow-down"></i></span>
                    <h3>Your Submitted Concerns</h3>
                    <?php if (empty($concerns)): ?>
                        <p>You haven't submitted any concerns yet.</p>
                    <?php else: ?>
                        <?php foreach ($concerns as $c): ?>
                            <div class="concern-item">
                                <div class="concern-meta">
                                    <span class="concern-id">Ticket #<?php echo $c['id']; ?></span>
                                    <span class="concern-status status-<?php echo $c['status'] === 'resolved' ? 'resolved' : 'open'; ?>">
                                        <?php echo htmlspecialchars($c['status']); ?>
                                    </span>
                                </div>
                                <p class="concern-text"><?php echo htmlspecialchars($c['message']); ?></p>
                                <p class="concern-date"><?php echo date('M j, Y g:i A', strtotime($c['created_at'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="contact-form-wrapper">
                <h2 class="form-title">Submit a New Concern</h2>

                <?php if ($message): ?>
                    <div class="message message-<?php echo $message_type; ?>" style="margin-bottom: 1.5rem;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form class="contact-form" method="POST">
                    <div class="form-group">
                        <label for="message" class="form-label">
                            <span>Describe your concern</span>
                            <span class="char-count" id="charCount">0 / 255</span>
                        </label>
                        <textarea id="message" name="message" class="form-textarea" maxlength="255"
                                  placeholder="e.g. My order hasn't arrived yet, or I received the wrong item..." required></textarea>
                    </div>

                    <button type="submit" name="submit_concern" class="submit-button">Submit Concern</button>
                </form>
            </div>

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

    <script>
        const textarea = document.getElementById('message');
        const charCount = document.getElementById('charCount');
        textarea.addEventListener('input', () => {
            charCount.textContent = `${textarea.value.length} / 255`;
        });
    </script>

</body>
</html>