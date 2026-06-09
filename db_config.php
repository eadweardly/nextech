<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nextech_db');

try {
    $conn = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div style='background-color: #ffebee; border: 2px solid #ef5350; color: #c62828; padding: 20px; margin: 20px; border-radius: 4px; font-family: Arial, sans-serif;'>";
    echo "<h2 style='margin-top: 0;'>Database Connection Error</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please make sure XAMPP is running and the database <strong>nextech_db</strong> exists in phpMyAdmin.</p>";
    echo "</div>";
    exit;
}
?>
