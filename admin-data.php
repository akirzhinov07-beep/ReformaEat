<?php
/**
 * reForma eat — server-side key-value store for admin panel
 * Used to persist calendar overrides and purchase data across browser sessions.
 * GET  ?key=<key>  → returns stored value
 * POST ?key=<key>  with JSON body → stores value
 */
header('Content-Type: application/json; charset=utf-8');

$TOKEN = 'rf_admin_2025_secret';
$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
if ($token !== $TOKEN) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$ALLOWED = ['rf_cal_override_v1', 'rf_purchase_v1'];
$key = $_GET['key'] ?? '';
if (!in_array($key, $ALLOWED, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid key']);
    exit;
}

$file = __DIR__ . DIRECTORY_SEPARATOR . 'rfadmin_' . $key . '.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($file)) {
        echo json_encode(['ok' => true, 'data' => null]);
        exit;
    }
    $raw = file_get_contents($file);
    echo json_encode(['ok' => true, 'data' => json_decode($raw, true)]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    if ($body === false || $body === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Empty body']);
        exit;
    }
    json_decode($body); // validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
        exit;
    }
    $ok = file_put_contents($file, $body, LOCK_EX);
    echo json_encode(['ok' => $ok !== false]);
} else {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
}
