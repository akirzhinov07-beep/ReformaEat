<?php
/**
 * reForma eat — создание платежа ЮКасса
 * POST /create-payment.php
 * Body: { orderId, amount, description, name, phone, order }
 */
set_time_limit(20);
header('Content-Type: application/json; charset=utf-8');

$allowed_origins = ['https://reformaeat.ru', 'https://www.reformaeat.ru', 'https://akirzhinov07-beep.github.io'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$cors_origin = in_array($origin, $allowed_origins) ? $origin : 'https://reformaeat.ru';
header('Access-Control-Allow-Origin: ' . $cors_origin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }

require_once __DIR__ . '/payment-config.php';

function _tgNotifyOrder(array $order): void {
    $DAYS_RU   = ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'];
    $MONTHS_RU = ['января','февраля','марта','апреля','мая','июня',
                   'июля','августа','сентября','октября','ноября','декабря'];

    date_default_timezone_set('Europe/Moscow');
    $now     = new DateTime();
    $dow     = (int)$now->format('w');
    $day     = (int)$now->format('j');
    $month   = (int)$now->format('n') - 1;
    $orderAt = $DAYS_RU[$dow] . ', ' . $day . ' ' . $MONTHS_RU[$month]
             . ' ' . $now->format('Y') . ' в ' . $now->format('H:i');

    $mode    = $order['mode']    ?? '1day';
    $name    = $order['name']    ?? '—';
    $phone   = $order['phone']   ?? '—';
    $city    = $order['city']    ?? '';
    $street  = $order['street']  ?? '';
    $house   = $order['house']   ?? '';
    $apt     = $order['apt']     ?? '';
    $comment = $order['comment'] ?? '';
    $promo   = $order['promo']   ?? '';
    $discount= $order['discount'] ?? 0;

    if ($mode === '7day') {
        $plan       = $order['plan']       ?? '';
        $totalPrice = $order['totalPrice'] ?? 0;
        $days       = $order['days']       ?? [];

        $msg = "🗓 <b>Заказ на 7 дней — reForma Eat</b>\n\n"
             . "📋 Оформлен: {$orderAt}\n\n"
             . "👤 {$name}\n"
             . "📞 {$phone}\n"
             . "🥗 План: {$plan}\n"
             . "💰 Сумма: " . number_format((float)$totalPrice, 0, '.', ' ') . " ₽\n";

        if ($promo)   $msg .= "🎟 Промокод: {$promo}" . ($discount ? " (−{$discount} ₽)" : '') . "\n";
        if ($city)    $msg .= "📍 {$city}" . ($street ? ", {$street}" : '') . ($house ? ", д. {$house}" : '') . ($apt ? ", кв. {$apt}" : '') . "\n";
        if ($comment) $msg .= "💬 {$comment}\n";
        $msg .= "\n";

        foreach ($days as $wd) {
            $dateStr = $wd['date'] ?? '';
            if ($dateStr) {
                $d      = new DateTime($dateStr . 'T00:00:00');
                $wdow   = (int)$d->format('w');
                $wday   = (int)$d->format('j');
                $wmonth = (int)$d->format('n') - 1;
                $label  = $DAYS_RU[$wdow] . ', ' . $wday . ' ' . $MONTHS_RU[$wmonth];
            } else {
                $label = $dateStr;
            }
            $msg .= "📅 <b>{$label}:</b>\n";
            foreach (($wd['dishes'] ?? []) as $type => $dish) {
                $msg .= "  • {$type}: {$dish}\n";
            }
            $msg .= "\n";
        }
    } else {
        $plan         = $order['plan']         ?? '';
        $deliveryDate = $order['deliveryDate'] ?? '';
        $dishes       = $order['dishes']       ?? [];

        $deliveryLabel = $deliveryDate;
        if ($deliveryDate) {
            $dd      = new DateTime($deliveryDate . 'T00:00:00');
            $ddow    = (int)$dd->format('w');
            $dday    = (int)$dd->format('j');
            $dmonth  = (int)$dd->format('n') - 1;
            $deliveryLabel = $DAYS_RU[$ddow] . ', ' . $dday . ' ' . $MONTHS_RU[$dmonth];
        }

        $msg = "🍽 <b>Новый заказ reForma Eat (1 день)</b>\n\n"
             . "📋 Оформлен: {$orderAt}\n\n"
             . "👤 {$name}\n"
             . "📞 {$phone}\n"
             . "📅 Доставка: {$deliveryLabel}\n"
             . "🥗 План: {$plan}\n";

        if ($city)    $msg .= "📍 {$city}" . ($street ? ", {$street}" : '') . ($house ? ", д. {$house}" : '') . ($apt ? ", кв. {$apt}" : '') . "\n";
        if ($promo)   $msg .= "🎟 Промокод: {$promo}" . ($discount ? " (−{$discount} ₽)" : '') . "\n";
        if ($comment) $msg .= "💬 {$comment}\n";

        $msg .= "\n<b>Блюда:</b>\n";
        if (is_array($dishes)) {
            foreach ($dishes as $type => $dish) {
                $msg .= "  • {$type}: {$dish}\n";
            }
        }
    }

    $ch = curl_init('https://api.telegram.org/bot' . TG_TOKEN . '/sendMessage');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'chat_id'    => TG_CHAT,
            'text'       => $msg,
            'parse_mode' => 'HTML'
        ]),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $tgResult = curl_exec($ch);
    $tgErrno  = curl_errno($ch);
    curl_close($ch);
    if ($tgResult === false) {
        error_log('create-payment tgNotify curl error: ' . $tgErrno);
    } else {
        $tgResp = json_decode($tgResult, true);
        if (!($tgResp['ok'] ?? false)) {
            error_log('create-payment tgNotify API error: ' . $tgResult);
        }
    }
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit; }

$amount      = round(floatval($data['amount'] ?? 0), 2);
$description = mb_substr($data['description'] ?? 'Заказ reForma eat', 0, 128);
$orderId     = preg_replace('/[^a-zA-Z0-9_\-]/', '', $data['orderId'] ?? uniqid('rf_'));
$name        = $data['name']  ?? '';
$phone       = $data['phone'] ?? '';

if ($amount < 10) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid amount']); exit; }

// Сохраняем полный заказ во временный файл — payment-webhook прочитает его
// и отправит полное уведомление с составом в Telegram после подтверждения оплаты
$fullOrder = $data['order'] ?? [];
$fullOrder['name']    = $name;
$fullOrder['phone']   = $phone;
$fullOrder['amount']  = $amount;
$fullOrder['orderId'] = $orderId;
$tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rforder_' . $orderId . '.json';
@file_put_contents($tmpFile, json_encode($fullOrder, JSON_UNESCAPED_UNICODE));

// Ключ идемпотентности по минуте — безопасно ретраить в течение минуты
$idempotenceKey = $orderId . '_' . floor(time() / 60);

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
    'receipt'     => [
        'customer' => ['phone' => preg_replace('/\D/', '', $phone)],
        'items'    => [[
            'description'      => $description,
            'quantity'         => '1.00',
            'amount'           => ['value' => number_format($amount, 2, '.', ''), 'currency' => 'RUB'],
            'vat_code'         => 1,
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
        'Idempotence-Key: ' . $idempotenceKey
    ],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT        => 12,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $curlErrno !== 0) {
    error_log('YooKassa cURL error: ' . $curlErrno . ' — ' . $curlError);
    echo json_encode([
        'ok'    => false,
        'error' => 'Не удалось подключиться к платёжному сервису. Попробуйте через несколько секунд.',
        'retry' => true
    ]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['confirmation']['confirmation_url'])) {
    echo json_encode([
        'ok'               => true,
        'payment_id'       => $result['id'],
        'confirmation_url' => $result['confirmation']['confirmation_url']
    ]);
} else {
    error_log('YooKassa error ' . $httpCode . ': ' . $response);
    echo json_encode([
        'ok'    => false,
        'error' => $result['description'] ?? ('Ошибка платёжного сервиса (код ' . $httpCode . ')'),
        'retry' => ($httpCode >= 500 || $httpCode === 0)
    ]);
}
