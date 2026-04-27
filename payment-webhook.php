<?php
/**
 * reForma eat — вебхук ЮКасса
 * Укажи этот URL в ЮКасса → Интеграция → HTTP-уведомления:
 * https://reformaeat.ru/payment-webhook.php
 */
require_once __DIR__ . '/payment-config.php';

$input = file_get_contents('php://input');
$event = json_decode($input, true);

if (!$event || !isset($event['event'], $event['object'])) {
    http_response_code(400); exit;
}

$obj       = $event['object'];
$paymentId = $obj['id']              ?? '—';
$amount    = $obj['amount']['value'] ?? '0';
$orderId   = $obj['metadata']['order_id'] ?? '—';
$name      = $obj['metadata']['name']     ?? '—';
$phone     = $obj['metadata']['phone']    ?? '—';

if ($event['event'] === 'payment.succeeded') {
    // Уведомление в Telegram при подтверждённой оплате
    $msg = "✅ *Оплата получена — reForma eat*\n\n"
         . "👤 {$name}  📞 {$phone}\n"
         . "💰 Сумма: *{$amount} ₽*\n"
         . "🔖 Заказ: `{$orderId}`\n"
         . "🔑 Payment: `{$paymentId}`";

    file_get_contents('https://api.telegram.org/bot' . TG_TOKEN . '/sendMessage?' . http_build_query([
        'chat_id'    => TG_CHAT,
        'text'       => $msg,
        'parse_mode' => 'Markdown'
    ]));
}

if ($event['event'] === 'payment.canceled') {
    $msg = "❌ *Оплата отменена*\n\n"
         . "👤 {$name}  📞 {$phone}\n"
         . "💰 {$amount} ₽ · Заказ: `{$orderId}`";

    file_get_contents('https://api.telegram.org/bot' . TG_TOKEN . '/sendMessage?' . http_build_query([
        'chat_id'    => TG_CHAT,
        'text'       => $msg,
        'parse_mode' => 'Markdown'
    ]));
}

http_response_code(200);
echo 'OK';
