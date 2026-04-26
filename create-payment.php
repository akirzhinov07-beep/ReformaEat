<?php
/**
 * reForma eat — создание платежа ЮКасса
 * POST /create-payment.php
 * Body: { orderId, amount, description, name, phone }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://reformaeat.ru');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }

require_once __DIR__ . '/payment-config.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit; }

$amount      = round(floatval($data['amount'] ?? 0), 2);
$description = mb_substr($data['description'] ?? 'Заказ reForma eat', 0, 128);
$orderId     = preg_replace('/[^a-zA-Z0-9_\-]/', '', $data['orderId'] ?? uniqid('rf_'));
$name        = $data['name']  ?? '';
$phone       = $data['phone'] ?? '';

if ($amount < 10) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid amount']); exit; }

$payload = [
    'amount' => [
        'value'    => number_format($amount, 2, '.', ''),
        'currency' => 'RUB'
    ],
    'confirmation' => [
        'type'       => 'redirect',
        'return_url' => RETURN_URL . '&oid=' . urlencode($orderId)
    ],
    'description' => $description,
    'metadata'    => [
        'order_id' => $orderId,
        'name'     => $name,
        'phone'    => $phone
    ],
    'capture'     => true,
    'receipt'     => [   // чек (54-ФЗ) — обязателен для ЮКасса
        'customer' => ['phone' => preg_replace('/\D/', '', $phone)],
        'items'    => [[
            'description'      => $description,
            'quantity'         => '1.00',
            'amount'           => ['value' => number_format($amount, 2, '.', ''), 'currency' => 'RUB'],
            'vat_code'         => 1,   // без НДС
            'payment_subject'  => 'service',
            'payment_mode'     => 'full_payment'
        ]]
    ]
];

$ch = curl_init('https://api.yookassa.ru/v3/payments');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_USERPWD        => YOOKASSA_SHOP_ID . ':' . YOOKASSA_SECRET_KEY,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Idempotence-Key: ' . $orderId . '_' . time()
    ],
    CURLOPT_TIMEOUT        => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['confirmation']['confirmation_url'])) {
    echo json_encode([
        'ok'               => true,
        'payment_id'       => $result['id'],
        'confirmation_url' => $result['confirmation']['confirmation_url']
    ]);
} else {
    error_log('YooKassa error: ' . $response);
    echo json_encode([
        'ok'    => false,
        'error' => $result['description'] ?? 'Ошибка создания платежа'
    ]);
}
