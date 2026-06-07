<?php
// ── Load Autoloader for OOP Classes ───────────────────────────────────────────
require_once 'autoload.php';

session_start();
require_once 'db_config.php';

$login_error = '';
$login_success = '';
$show_signup = isset($_GET['signup']);

// ── LOAD EMAIL FROM COOKIE (Remember Me) ────────────────────────────────────
$email_value = isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $login_error = 'Please fill in all fields';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // ── SET COOKIE IF "REMEMBER ME" IS CHECKED ──────────────────────────
                if (isset($_POST['remember'])) {
                    setcookie('user_email', $email, time() + (20), "/", "", false, true);
                    
                } else {
                    // Clear cookie if "Remember me" was unchecked
                    setcookie('user_email', '', time() - 3600, "/");
                }
                
                header("Location: index.php");
                exit();
            } else {
                $login_error = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            $login_error = 'Login error. Please try again.';
        }
    }
}

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = trim($_POST['signup_username']);
    $email = trim($_POST['signup_email']);
    $password = $_POST['signup_password'];
    $confirm_password = $_POST['signup_confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $login_error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $login_error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $login_error = 'Password must be at least 6 characters';
    } else {
        try {
            // Check if user already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_user) {
                $login_error = 'Email or username already exists';
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    $_SESSION['user_id'] = $conn->lastInsertId();
                    $_SESSION['username'] = $username;
                    
                    // ── SET COOKIE AFTER SIGNUP (Optional) ──────────────────────────────
                    setcookie('user_email', $email, time() + (86400 * 30), "/", "", false, true);
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $login_error = 'Error creating account. Please try again';
                }
            }
        } catch (PDOException $e) {
            $login_error = 'An error occurred. Please try again.';
        }
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_signup ? 'Sign Up' : 'Login'; ?> - NEXTECH</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">NEXTECH</div>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="product.php" class="nav-link">Products</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
                <li><a href="login.php" class="nav-link active">Login</a></li>
                <li><a href="cart.php" class="nav-link">Cart</a></li>
            </ul>
            <div class="cart-icon">🛒</div>
        </div>
    </nav>

    <!-- Login/Signup Container -->
    <section class="login-section">
        <div class="login-container">
            <!-- Login/Signup Form -->
            <div class="login-box">
                <?php if ($show_signup): ?>
                    <h1 class="login-title">Create Account</h1>
                    <p class="login-subtitle">Join NEXTECH for exclusive deals</p>

                    <?php if ($login_error): ?>
                        <div class="error-message"><?php echo htmlspecialchars($login_error); ?></div>
                    <?php endif; ?>

                    <form class="login-form" method="POST">
                        <?php if ($login_error): ?>
                            <div class="error-message-box" style="background-color: #ffebee; border: 1px solid #ef5350; color: #c62828; padding: 12px 16px; border-radius: 4px; font-size: 0.9rem; margin-bottom: 20px;">
                                <?php echo htmlspecialchars($login_error); ?>
                            </div>
                        <?php endif; ?>
                    </form>

                    <form class="login-form" method="POST" novalidate>
                        <div class="form-group">
                            <label for="signup_username" class="form-label">Username</label>
                            <input type="text" id="signup_username" name="signup_username" required class="form-input" placeholder="Choose a username" minlength="3">
                        </div>

                        <div class="form-group">
                            <label for="signup_email" class="form-label">Email Address</label>
                            <input type="email" id="signup_email" name="signup_email" required class="form-input" placeholder="you@example.com">
                        </div>

                        <div class="form-group">
                            <label for="signup_password" class="form-label">Password</label>
                            <input type="password" id="signup_password" name="signup_password" required class="form-input" placeholder="••••••••" minlength="6">
                            <small style="color: var(--text-secondary); font-size: 0.85rem;">Minimum 6 characters</small>
                        </div>

                        <div class="form-group">
                            <label for="signup_confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" id="signup_confirm_password" name="signup_confirm_password" required class="form-input" placeholder="••••••••" minlength="6">
                        </div>

                        <button type="submit" name="signup" value="1" class="login-button">Create Account</button>
                    </form>

                    <div class="signup-link">
                        <p>Already have an account? <a href="login.php">Sign In</a></p>
                    </div>
                <?php else: ?>
                    <h1 class="login-title">Welcome Back</h1>
                    <p class="login-subtitle">Sign in to your NEXTECH account</p>

                    <?php if ($login_error): ?>
                        <div class="error-message"><?php echo htmlspecialchars($login_error); ?></div>
                    <?php endif; ?>

                    <form class="login-form" method="POST">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo $email_value; ?>" required class="form-input" placeholder="you@example.com">
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="password-wrapper">
                                <input type="password" id="password" name="password" required class="form-input" placeholder="••••••••">
                                <button type="button" class="toggle-password" id="toggle-pwd">👁️</button>
                            </div>
                        </div>

                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="remember" name="remember" class="form-checkbox">
                            <label for="remember" class="checkbox-label">Remember me</label>
                            <a href="#" class="forgot-password">Forgot Password?</a>
                        </div>

                        <button type="submit" name="login" class="login-button">Sign In</button>
                    </form>

                    <div class="divider">
                        <span>OR</span>
                    </div>

                    <div class="social-login">
                        <button class="social-button google" type="button">
                            <span class="social-icon">G</span>
                            Google
                        </button>
                        <button class="social-button apple" type="button">
                            <span class="social-icon">🍎</span>
                            Apple
                        </button>
                    </div>

                    <div class="signup-link">
                        <p>Don&apos;t have an account? <a href="?signup=true">Create one</a></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Side Content -->
            <div class="login-visual">
                <div class="visual-content">
                    <h2>Experience Premium Tech</h2>
                    <p>Sign in to access exclusive products, track orders, and enjoy personalized recommendations from our curated collection.</p>
                    <div class="feature-list">
                        <div class="feature-item">
                            <span class="feature-icon">✓</span>
                            <span>Fast & Secure Checkout</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">✓</span>
                            <span>Order History & Tracking</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">✓</span>
                            <span>Personalized Wishlist</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">✓</span>
                            <span>Exclusive Deals & Offers</span>
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

    <!-- Footer -->
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
            <p>&copy; 2026 NEXTECH. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Password visibility toggle
        const togglePwdBtn = document.getElementById('toggle-pwd');
        if (togglePwdBtn) {
            togglePwdBtn.addEventListener('click', function() {
                const passwordInput = document.getElementById('password');
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.textContent = '🙈';
                } else {
                    passwordInput.type = 'password';
                    this.textContent = '👁️';
                }
            });
        }

        // Form validation for signup
        const signupForm = document.querySelector('.login-form[method="POST"]');
        if (signupForm && document.getElementById('signup_username')) {
            signupForm.addEventListener('submit', function(e) {
                const username = document.getElementById('signup_username')?.value.trim();
                const email = document.getElementById('signup_email')?.value.trim();
                const password = document.getElementById('signup_password')?.value;
                const confirmPassword = document.getElementById('signup_confirm_password')?.value;

                let errors = [];

                if (!username || username.length < 3) {
                    errors.push('Username must be at least 3 characters');
                }

                if (!email || !email.includes('@')) {
                    errors.push('Please enter a valid email address');
                }

                if (!password || password.length < 6) {
                    errors.push('Password must be at least 6 characters');
                }

                if (password !== confirmPassword) {
                    errors.push('Passwords do not match');
                }

                if (errors.length > 0) {
                    e.preventDefault();
                    console.log('[v0] Validation errors:', errors);
                }
            });
        }

        // Focus management
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '#1a1a1a';
            });
            input.addEventListener('blur', function() {
                this.style.borderColor = '#e0e0e0';
            });
        });
    </script>
</body>
</html>
