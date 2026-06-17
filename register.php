<?php
session_start();
require_once 'db_config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $error = 'Email or username is already taken.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password]);

                $_SESSION['user_id']  = $conn->lastInsertId();
                $_SESSION['username'] = $username;

                header("Location: index.php");
                exit();
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - NEXTECH</title>
    <link rel="stylesheet" href="css/login.css">
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
                <li><a href="login.php" class="nav-link">Login</a></li>
                <li><a href="register.php" class="nav-link active">Register</a></li>
            </ul>
            <div class="cart-icon"><i class="bi bi-cart3"></i></div>
        </div>
    </nav>

    <section class="login-section">
        <div class="login-container">

            <div class="login-box">
                <h1 class="login-title">Create Account</h1>
                <p class="login-subtitle">Join NEXTECH for exclusive deals</p>

                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form class="login-form" method="POST" novalidate>
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-input"
                               placeholder="Choose a username" minlength="3"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input"
                               placeholder="you@example.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" class="form-input"
                                   placeholder="••••••••" minlength="6" required>
                            <button type="button" class="toggle-password" id="toggle-pwd"><i class="bi bi-eye"></i>️</button>
                        </div>
                        <small style="color: var(--text-secondary); font-size: 0.85rem;">Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input"
                                   placeholder="••••••••" minlength="6" required>
                            <button type="button" class="toggle-password" id="toggle-confirm-pwd"><i class="bi bi-eye"></i>️</button>
                        </div>
                    </div>

                    <button type="submit" class="login-button">Create Account</button>
                </form>

                <div class="signup-link">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
                </div>
            </div>

            <div class="login-visual">
                <div class="visual-content">
                    <h2>Experience Premium Tech</h2>
                    <p>Create your NEXTECH account to access exclusive products, track your orders, and enjoy a personalized shopping experience.</p>
                    <div class="feature-list">
                        <div class="feature-item">
                            <span class="feature-icon"><i class="bi bi-check-lg"></i></span>
                            <span>Fast & Secure Checkout</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon"><i class="bi bi-check-lg"></i></span>
                            <span>Order History & Tracking</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon"><i class="bi bi-check-lg"></i></span>
                            <span>Exclusive Deals & Offers</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon"><i class="bi bi-check-lg"></i></span>
                            <span>Personalized Wishlist</span>
                        </div>
                    </div>
                </div>
                <div class="visual-image">
                    <svg viewBox="0 0 300 400" xmlns="http://www.w3.org/2000/svg">
                        <rect width="300" height="400" fill="#f5f5f5"/>
                        <rect x="50" y="100" width="200" height="200" fill="#333" rx="16"/>
                        <rect x="70" y="120" width="160" height="160" fill="#555" rx="12"/>
                        <circle cx="150" cy="200" r="30" fill="#1a1a1a"/>
                        <g fill="#f5f5f5" opacity="0.3">
                            <circle cx="100" cy="80" r="15"/>
                            <circle cx="200" cy="320" r="20"/>
                            <circle cx="80" cy="350" r="12"/>
                        </g>
                    </svg>
                </div>
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

    <script>
        const togglePwd = document.getElementById('toggle-pwd');
        if (togglePwd) {
            togglePwd.addEventListener('click', function () {
                const input = document.getElementById('password');
                input.type = input.type === 'password' ? 'text' : 'password';
                this.innerHTML = input.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            });
        }

        const toggleConfirmPwd = document.getElementById('toggle-confirm-pwd');
        if (toggleConfirmPwd) {
            toggleConfirmPwd.addEventListener('click', function () {
                const input = document.getElementById('confirm_password');
                input.type = input.type === 'password' ? 'text' : 'password';
                this.innerHTML = input.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            });
        }

        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function () {
                this.style.borderColor = '#1a1a1a';
            });
            input.addEventListener('blur', function () {
                this.style.borderColor = '#e0e0e0';
            });
        });
    </script>

</body>
</html>
