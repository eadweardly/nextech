<?php
header('Content-Type: application/json');

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

if (!empty($data['dismissOnly'])) {
    setcookie('cookie_consent_dismissed', '1', time() + 86400, "/", "", false, true);
    echo json_encode(['success' => true, 'dismissed' => true]);
    exit;
}

$consent = [
    'necessary' => true, 
    'analytics' => isset($data['analytics']) && $data['analytics'] === true,
    'marketing' => isset($data['marketing']) && $data['marketing'] === true,
    'timestamp' => time(),
];

setcookie('cookie_consent', json_encode($consent), time() + 86400, "/", "", false, true);

echo json_encode(['success' => true, 'consent' => $consent]);
exit;