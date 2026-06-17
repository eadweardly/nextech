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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $feedback_message = trim($_POST['message'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);

    if (empty($feedback_message)) {
        $message = 'Please enter your feedback.';
        $message_type = 'error';
    } elseif (strlen($feedback_message) > 255) {
        $message = 'Your message is too long (max 255 characters).';
        $message_type = 'error';
    } elseif ($rating < 1 || $rating > 5) {
        $message = 'Please select a rating from 1 to 5.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO feedbacks (user_id, message, rating) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $feedback_message, $rating]);
            $message = 'Thank you for your feedback!';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error submitting feedback. Please try again.';
            $message_type = 'error';
        }
    }
}

$feedbacks = [];
try {
    $stmt = $conn->prepare("SELECT * FROM feedbacks WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - NEXTECH</title>
    <link rel="stylesheet" href="css/contact.css">
    <style>
        .feedback-item {
            background-color: var(--light-grey);
            padding: 1.2rem 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border-grey);
            border-left: 3px solid var(--accent);
            margin-bottom: 1rem;
        }

        .feedback-stars {
            color: #F59E0B;
            font-size: 1rem;
            margin-bottom: 0.4rem;
        }

        .feedback-text {
            font-size: 0.95rem;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
        }

        .feedback-date {
            font-size: 0.78rem;
            color: var(--text-secondary);
        }

        .rating-group {
            display: flex;
            gap: 0.5rem;
        }

        .rating-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
            cursor: pointer;
            padding: 0.6rem 1rem;
            border: 1px solid var(--border-grey);
            border-radius: var(--radius);
            transition: var(--transition);
            flex: 1;
            text-align: center;
        }

        .rating-option:hover {
            border-color: var(--accent);
            background: var(--accent-light);
        }

        .rating-option input[type="radio"] {
            accent-color: var(--accent);
            width: 18px;
            height: 18px;
        }

        .rating-option:has(input:checked) {
            border-color: var(--accent);
            background: var(--accent-light);
        }

        .rating-star {
            font-size: 1.3rem;
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
                <li><a href="feedback.php" class="nav-link active">Feedback</a></li>
                <li><a href="concerns.php" class="nav-link">Concerns</a></li>
                <li><a href="cart.php" class="nav-link">Cart</a></li>
                <li><a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
            </ul>
            <div class="cart-icon">
                <a href="cart.php"><i class="bi bi-cart3"></i></a>
            </div>
        </div>
    </nav>

    <section class="page-header">
        <h1>Share Your Feedback</h1>
        <p>We'd love to hear about your shopping experience with NEXTECH</p>
    </section>

    <section class="contact-section">
        <div class="contact-container">

            <div class="contact-info">
                <div class="info-card">
                    <span class="info-icon">⭐</span>
                    <h3>Your Submitted Feedback</h3>
                    <?php if (empty($feedbacks)): ?>
                        <p>You haven't submitted any feedback yet.</p>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $f): ?>
                            <div class="feedback-item">
                                <div class="feedback-stars">
                                    <?php echo str_repeat('★', $f['rating']) . str_repeat('☆', 5 - $f['rating']); ?>
                                </div>
                                <p class="feedback-text"><?php echo htmlspecialchars($f['message']); ?></p>
                                <p class="feedback-date"><?php echo date('M j, Y g:i A', strtotime($f['created_at'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="contact-form-wrapper">
                <h2 class="form-title">Leave Feedback</h2>

                <?php if ($message): ?>
                    <div class="message message-<?php echo $message_type; ?>" style="margin-bottom: 1.5rem;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form class="contact-form" method="POST">
                    <div class="form-group">
                        <label class="form-label">Rate your experience</label>
                        <div class="rating-group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="rating-option">
                                    <span class="rating-star">⭐</span>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" <?php echo $i == 5 ? 'checked' : ''; ?>>
                                    <span><?php echo $i; ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="message" class="form-label">
                            <span>Your feedback</span>
                            <span class="char-count" id="charCount">0 / 255</span>
                        </label>
                        <textarea id="message" name="message" class="form-textarea" maxlength="255"
                                  placeholder="Tell us what you liked or what we can improve..." required></textarea>
                    </div>

                    <button type="submit" name="submit_feedback" class="submit-button">Submit Feedback</button>
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