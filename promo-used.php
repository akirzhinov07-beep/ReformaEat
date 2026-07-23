<?php
/**
 * reForma eat — проверка одноразового промокода
 * POST /promo-used.php
 * Body: { action: "check", phone: "...", promo: "SALE15" }
 * Resp: { ok: true, used: false }
 */
header('Content-Type: application/json; charset=utf-8');

$allowed_origins = ['https://reformaeat.ru', 'https://www.reformaeat.ru'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$cors_origin = in_array($origin, $allowed_origins) ? $origin : 'https://reformaeat.ru';
header('Access-Control-Allow-Origin: ' . $cors_origin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

require_once __DIR__ . '/promo-store.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || ($data['action'] ?? '') !== 'check') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad request']);
    exit;
}

$phone = preg_replace('/\D/', '', $data['phone'] ?? '');
$promo = strtoupper(trim($data['promo'] ?? ''));

if (!$phone || !$promo) {
    echo json_encode(['ok' => false, 'error' => 'missing params']);
    exit;
}

if (!promoIsOneTime($promo)) {
    echo json_encode(['ok' => true, 'used' => false]);
    exit;
}

echo json_encode(['ok' => true, 'used' => promoCheckUsed($phone, $promo)]);
