<?php
session_start();
require_once 'db_config.php';

$message      = '';
$message_type = '';
$form_data    = []; // repopulate form fields on error

// ── HELPER FUNCTIONS ─────────────────────────────────────────────────────────

/**
 * Validates a full name.
 * Uses: trim(), empty(), strlen(), preg_match()
 */
function validate_name(string $name): string|false {
    $name = trim($name);                            // trim() — remove whitespace
    if (empty($name)) return false;                 // empty() — reject blank
    if (strlen($name) < 2 || strlen($name) > 100)  // strlen() — length limits
        return false;
    if (!preg_match('/^[\p{L}\s\'-]+$/u', $name))  // preg_match() — letters, spaces, hyphens only
        return false;
    return $name;
}

/**
 * Validates an email address.
 * Uses: trim(), empty(), preg_match()
 */
function validate_email(string $email): string|false {
    $email = trim($email);                          // trim()
    if (empty($email)) return false;                // empty()
    if (!preg_match('/^[\w\.\+\-]+@[\w\-]+\.[a-zA-Z]{2,}$/', $email)) // preg_match()
        return false;
    return $email;
}

/**
 * Validates an optional phone number.
 * Uses: trim(), empty(), preg_match()
 */
function validate_phone(string $phone): string {
    $phone = trim($phone);                          // trim()
    if (empty($phone)) return '';                   // empty() — phone is optional
    if (!preg_match('/^[\+\d\s\(\)\-]{7,20}$/', $phone)) // preg_match() — digits, spaces, +, ()
        return '';
    return $phone;
}

/**
 * Validates the subject dropdown against allowed values.
 * Uses: trim(), empty(), strcasecmp()
 */
function validate_subject(string $subject): string|false {
    $subject  = trim($subject);                     // trim()
    if (empty($subject)) return false;              // empty()
    $allowed  = ['product-inquiry', 'support', 'feedback', 'business', 'other'];
    foreach ($allowed as $option) {
        if (strcasecmp($subject, $option) === 0)    // strcasecmp() — case-insensitive match
            return $subject;
    }
    return false;
}

/**
 * Validates the message body.
 * Uses: trim(), empty(), strlen(), str_word_count()
 */
function validate_message(string $msg): string|false {
    $msg = trim($msg);                              // trim()
    if (empty($msg)) return false;                  // empty()
    if (strlen($msg) < 10 || strlen($msg) > 2000)  // strlen() — min/max length
        return false;
    if (str_word_count($msg) < 3)                  // str_word_count() — at least 3 words
        return false;
    return $msg;
}

/**
 * Checks the consent checkbox.
 * Uses: isset(), is_bool() via strict comparison
 */
function validate_consent(mixed $consent): bool {
    $checked = isset($consent) && $consent === 'on'; // is_bool() result
    return is_bool($checked) && $checked;            // is_bool() — ensure it's truly boolean
}

/**
 * Compares the submitted subject to a specific value.
 * Uses: strcmp() — case-sensitive exact match
 */
function subject_is(string $submitted, string $expected): bool {
    return strcmp($submitted, $expected) === 0;      // strcmp() — exact case-sensitive compare
}

// ── HANDLE FORM SUBMISSION ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect raw inputs
    $raw_name    = $_POST['name']    ?? '';
    $raw_email   = $_POST['email']   ?? '';
    $raw_phone   = $_POST['phone']   ?? '';
    $raw_subject = $_POST['subject'] ?? '';
    $raw_message = $_POST['message'] ?? '';
    $raw_consent = $_POST['consent'] ?? null;

    // Keep form data for repopulation on error
    $form_data = [
        'name'    => htmlspecialchars(trim($raw_name)),
        'email'   => htmlspecialchars(trim($raw_email)),
        'phone'   => htmlspecialchars(trim($raw_phone)),
        'subject' => htmlspecialchars(trim($raw_subject)),
        'message' => htmlspecialchars(trim($raw_message)),
    ];

    // Validate each field
    $name    = validate_name($raw_name);
    $email   = validate_email($raw_email);
    $phone   = validate_phone($raw_phone);
    $subject = validate_subject($raw_subject);
    $msg     = validate_message($raw_message);
    $consent = validate_consent($raw_consent);

    // Collect errors
    $errors = [];
    if ($name    === false) $errors[] = 'Please enter a valid full name (letters only, 2–100 characters).';
    if ($email   === false) $errors[] = 'Please enter a valid email address.';
    if ($subject === false) $errors[] = 'Please select a valid subject.';
    if ($msg     === false) $errors[] = 'Message must be 10–2000 characters and at least 3 words.';
    if (!$consent)          $errors[] = 'You must agree to the privacy policy to continue.';

    if (empty($errors)) {
        // ── All valid — save to database ──────────────────────────────────
        try {
            $user_id = $_SESSION['user_id'] ?? null;

            $stmt = $conn->prepare("
                INSERT INTO contact_messages (user_id, name, email, phone, subject, message)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $name, $email, $phone, $subject, $msg]);

            $message      = 'Thank you, ' . htmlspecialchars($name) . '! Your message has been sent. We\'ll get back to you shortly.';
            $message_type = 'success';
            $form_data    = []; // clear form on success

        } catch (PDOException $e) {
            $message      = 'Error sending your message. Please try again.';
            $message_type = 'error';
        }
    } else {
        $message      = implode('<br>', $errors);
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - NEXTECH</title>
    <link rel="stylesheet" href="css/contact.css">
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">NEXTECH</div>
            <ul class="nav-menu">
                <li><a href="index.php"   class="nav-link">Home</a></li>
                <li><a href="product.php" class="nav-link">Products</a></li>
                <li><a href="contact.php" class="nav-link active">Contact</a></li>
                <li><a href="feedback.php" class="nav-link">Feedback</a></li>
                <li><a href="concerns.php" class="nav-link">Concerns</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="cart.php"   class="nav-link">Cart</a></li>
                    <li><a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="cart-icon">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'cart.php' : 'login.php'; ?>">🛒</a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <h1>Get In Touch</h1>
        <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
    </section>

    <!-- Message / Error Display -->
    <?php if ($message): ?>
        <div class="message message-<?php echo $message_type; ?>">
            <?php echo $message; // may contain <br> for multiple errors ?>
        </div>
    <?php endif; ?>

    <!-- Contact Content -->
    <section class="contact-section">
        <div class="contact-container">

            <!-- Contact Info -->
            <div class="contact-info">
                <div class="info-card">
                    <div class="info-icon">📍</div>
                    <h3>Address</h3>
                    <p>123 Tech Avenue<br>San Francisco, CA 94105<br>United States</p>
                </div>
                <div class="info-card">
                    <div class="info-icon">📞</div>
                    <h3>Phone</h3>
                    <p>+1 (555) 123-4567<br>Mon - Fri, 9am - 6pm PST<br>Closed on weekends</p>
                </div>
                <div class="info-card">
                    <div class="info-icon">✉️</div>
                    <h3>Email</h3>
                    <p>support@nextech.com<br>sales@nextech.com<br>info@nextech.com</p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-wrapper">
                <h2 class="form-title">Send Us a Message</h2>

                <form class="contact-form" action="contact.php" method="POST">

                    <!-- Full Name — validate_name(): trim() + empty() + strlen() + preg_match() -->
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text"
                               id="name"
                               name="name"
                               required
                               class="form-input <?php echo isset($form_data['name']) && empty($form_data['name']) ? 'input-error' : ''; ?>"
                               placeholder="John Doe"
                               value="<?php echo $form_data['name'] ?? ''; ?>">
                    </div>

                    <!-- Email — validate_email(): trim() + empty() + preg_match() -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email"
                               id="email"
                               name="email"
                               required
                               class="form-input"
                               placeholder="john@example.com"
                               value="<?php echo $form_data['email'] ?? ''; ?>">
                    </div>

                    <!-- Phone — validate_phone(): trim() + empty() + preg_match() (optional) -->
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number (Optional)</label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               class="form-input"
                               placeholder="+1 (555) 000-0000"
                               value="<?php echo $form_data['phone'] ?? ''; ?>">
                    </div>

                    <!-- Subject — validate_subject(): trim() + empty() + strcasecmp() -->
                    <div class="form-group">
                        <label for="subject" class="form-label">Subject</label>
                        <select id="subject" name="subject" required class="form-select">
                            <option value="">Select a subject</option>
                            <?php
                                $subjects = [
                                    'product-inquiry' => 'Product Inquiry',
                                    'support'         => 'Customer Support',
                                    'feedback'        => 'Feedback',
                                    'business'        => 'Business Inquiry',
                                    'other'           => 'Other',
                                ];
                                foreach ($subjects as $val => $label):
                                    $selected = isset($form_data['subject']) && $form_data['subject'] === $val ? 'selected' : '';
                            ?>
                                <option value="<?php echo $val; ?>" <?php echo $selected; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Message — validate_message(): trim() + empty() + strlen() + str_word_count() -->
                    <div class="form-group">
                        <label for="message" class="form-label">
                            Message
                            <span class="char-count" id="charCount">0 / 2000</span>
                        </label>
                        <textarea id="message"
                                  name="message"
                                  required
                                  class="form-textarea"
                                  rows="6"
                                  maxlength="2000"
                                  placeholder="Tell us what you think..."><?php echo $form_data['message'] ?? ''; ?></textarea>
                    </div>

                    <!-- Consent — validate_consent(): is_bool() -->
                    <div class="form-group checkbox">
                        <input type="checkbox"
                               id="consent"
                               name="consent"
                               <?php echo (!empty($form_data) && isset($_POST['consent'])) ? 'checked' : ''; ?>
                               required>
                        <label for="consent" class="checkbox-label">
                            I agree to the privacy policy and terms of service
                        </label>
                    </div>

                    <button type="submit" class="submit-button">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-container">
            <div class="faq-item">
                <h3 class="faq-question">What is your return policy?</h3>
                <p class="faq-answer">We offer a 30-day money-back guarantee on all products. If you're not satisfied, simply return the item in its original condition for a full refund.</p>
            </div>
            <div class="faq-item">
                <h3 class="faq-question">How long does shipping take?</h3>
                <p class="faq-answer">Standard shipping takes 5-7 business days. Express shipping is available for 2-3 business day delivery at an additional cost.</p>
            </div>
            <div class="faq-item">
                <h3 class="faq-question">Do you offer international shipping?</h3>
                <p class="faq-answer">Yes, we ship to over 100 countries worldwide. Shipping costs and delivery times vary by location.</p>
            </div>
            <div class="faq-item">
                <h3 class="faq-question">What is your warranty coverage?</h3>
                <p class="faq-answer">All products come with a 1-year manufacturer's warranty covering defects in materials and workmanship.</p>
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
                    <li><a href="feedback.php">Feedback</a></li>
                    <li><a href="concerns.php">Concerns</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <!-- date("Y") — dynamic copyright year -->
            <p>&copy; <?php echo date("Y"); ?> NEXTECH. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Live character counter for the message textarea
        const textarea  = document.getElementById('message');
        const charCount = document.getElementById('charCount');

        textarea.addEventListener('input', () => {
            const len = textarea.value.length;
            charCount.textContent = len + ' / 2000';
            charCount.style.color = len > 1800 ? '#d32f2f' : '#666';
        });
    </script>

</body>
</html>