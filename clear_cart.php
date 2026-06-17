<?php

session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Cart cleared for new browser session']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to clear cart']);
}
