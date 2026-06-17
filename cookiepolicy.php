<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie Policy - NEXTECH</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/cookies.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .policy-section {
            max-width: 760px;
            margin: 0 auto;
            padding: 64px 24px 80px;
            color: var(--text-primary, #1a1a1a);
            line-height: 1.7;
        }

        .policy-section h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-grey, #1a1a1a);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .policy-updated {
            font-size: 0.85rem;
            color: var(--text-secondary, #666666);
            margin-bottom: 36px;
        }

        .policy-section h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-grey, #1a1a1a);
            margin: 40px 0 14px;
        }

        .policy-section p {
            margin-bottom: 16px;
            font-size: 0.96rem;
        }

        .policy-section ul {
            margin: 0 0 16px 20px;
            font-size: 0.96rem;
        }

        .policy-section li {
            margin-bottom: 8px;
        }

        .cookie-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0 28px;
            font-size: 0.88rem;
        }

        .cookie-table th,
        .cookie-table td {
            text-align: left;
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-grey, #e0e0e0);
            vertical-align: top;
        }

        .cookie-table th {
            background-color: var(--light-grey, #f5f5f5);
            font-weight: 700;
            color: var(--primary-grey, #1a1a1a);
        }

        .cookie-table td {
            color: var(--text-primary, #1a1a1a);
        }

        .policy-manage {
            background-color: var(--light-grey, #f5f5f5);
            border-radius: var(--radius, 0.5rem);
            padding: 24px;
            margin: 32px 0;
        }

        .policy-manage p {
            margin-bottom: 16px;
        }

        .policy-manage-btn {
            background-color: var(--primary-grey, #1a1a1a);
            color: #ffffff;
            border: 2px solid var(--primary-grey, #1a1a1a);
            border-radius: var(--radius, 0.5rem);
            padding: 12px 22px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            font-family: inherit;
            transition: var(--transition, all 0.3s ease);
        }

        .policy-manage-btn:hover {
            background-color: var(--secondary-grey, #333333);
            border-color: var(--secondary-grey, #333333);
        }
    </style>
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
                <li><a href="cart.php" class="nav-link">Cart</a></li>
                <li><a href="feedback.php" class="nav-link">Feedback</a></li>
                <li><a href="concerns.php" class="nav-link">Concerns</a></li>
            </ul>
            <div class="cart-icon"><i class="bi bi-cart3"></i></div>
        </div>
    </nav>

    <section class="policy-section">
        <h1>Cookie Policy</h1>
        <p class="policy-updated">Last updated: June 17, 2026</p>

        <p>
            This policy explains what cookies are, which ones NEXTECH uses, and how you
            can control them. It applies whenever you visit or use this website.
        </p>

        <h2>What are cookies?</h2>
        <p>
            Cookies are small text files that a website stores on your device. They let
            the site remember things between visits or pages, such as whether you're
            logged in or which preferences you've chosen.
        </p>

        <h2>Types of cookies we use</h2>
        <ul>
            <li><strong>Necessary</strong> &mdash; required for core site functionality, such as staying logged in. These can't be turned off.</li>
            <li><strong>Analytics</strong> &mdash; help us understand how visitors use the site so we can improve it.</li>
            <li><strong>Marketing</strong> &mdash; used to personalize ads and measure their performance.</li>
        </ul>

        <h2>Cookies set on this site</h2>
        <table class="cookie-table">
            <thead>
                <tr>
                    <th>Cookie</th>
                    <th>Purpose</th>
                    <th>Duration</th>
                    <th>Category</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>cookie_consent</td>
                    <td>Stores your saved cookie preferences. Expires daily so you're asked again once per day.</td>
                    <td>1 day</td>
                    <td>Necessary</td>
                </tr>
                <tr>
                    <td>cookie_consent_dismissed</td>
                    <td>Remembers that you closed the banner so it doesn't reappear right away.</td>
                    <td>1 day</td>
                    <td>Necessary</td>
                </tr>
                <tr>
                    <td>user_email</td>
                    <td>Pre-fills your email address on the login page when "Remember me" is checked.</td>
                    <td>Up to 30 days</td>
                    <td>Necessary</td>
                </tr>
                <tr>
                    <td>PHPSESSID</td>
                    <td>Keeps you logged in as you move between pages.</td>
                    <td>Session (until browser closes)</td>
                    <td>Necessary</td>
                </tr>
            </tbody>
        </table>
        <p>
            We don't currently set any Analytics or Marketing cookies of our own. If that
            changes, this table will be updated to list them along with their purpose and
            duration.
        </p>

        <div class="policy-manage">
            <p>You can change your cookie preferences at any time.</p>
            <button type="button" class="policy-manage-btn" id="reopen-preferences">
                Manage Cookie Preferences
            </button>
        </div>

        <h2>Controlling cookies in your browser</h2>
        <p>
            Most browsers let you block or delete cookies through their settings. Doing
            so may affect how parts of this site work, for example staying logged in.
        </p>

        <h2>Changes to this policy</h2>
        <p>
            We may update this policy from time to time. Significant changes will be
            reflected by the "Last updated" date above.
        </p>

        <h2>Contact us</h2>
        <p>
            Questions about this policy? Reach out via our
            <a href="contact.php" class="cookie-link">Contact page</a>.
        </p>
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
            <p>&copy; 2026 NEXTECH. All rights reserved.</p>
        </div>
    </footer>

    <?php include 'cookies.php'; ?>

    <script>
        document.getElementById('reopen-preferences').addEventListener('click', function () {
            document.cookie = 'cookie_consent=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
            document.cookie = 'cookie_consent_dismissed=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
            window.location.reload();
        });
    </script>
</body>
</html>