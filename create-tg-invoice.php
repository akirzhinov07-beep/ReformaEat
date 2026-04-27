<?php
/**
 * reForma eat — создание инвойса для Telegram Mini App
 * POST /create-tg-invoice.php
 * Body: { orderId, amount, description, name, phone }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://reformaeat.ru');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }

require_once __DIR__ . '/payment-config.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit; }

$amount      = round(floatval($data['amount'] ?? 0));
$description = mb_substr($data['description'] ?? 'Рацион reForma eat', 0, 255);
$orderId     = preg_replace('/[^a-zA-Z0-9_\-]/', '', $data['orderId'] ?? uniqid('rf_'));
$name        = $data['name']  ?? '';
$phone       = $data['phone'] ?? '';

if ($amount < 10) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid amount']); exit; }

$payload = [
    'title'           => 'reForma eat',
    'description'     => $description,
    'payload'         => json_encode(['order_id'=>$orderId,'name'=>$name,'phone'=>$phone]),
    'provider_token'  => TG_PAYMENT_TOKEN,
    'currency'        => 'RUB',
    'prices'          => [['label'=>'Рацион', 'amount'=>$amount * 100]], // в копейках
    'need_name'            => false,
    'need_phone_number'    => false,
    'need_email'           => false,
    'need_shipping_address'=> false,
    'is_flexible'          => false
];

$ch = curl_init('https://api.telegram.org/bot' . TG_TOKEN . '/createInvoiceLink');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15
]);

$response = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['result'])) {
    echo json_encode(['ok' => true, 'invoice_link' => $result['result']]);
} else {
    error_log('TG invoice error: ' . $response);
    echo json_encode(['ok' => false, 'error' => $result['description'] ?? 'Ошибка создания инвойса']);
}
