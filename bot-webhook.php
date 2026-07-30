<?php
/**
 * reForma eat — Telegram Bot Webhook
 * Обрабатывает pre_checkout_query и successful_payment
 *
 * Зарегистрировать вебхук (выполнить один раз в браузере):
 * https://api.telegram.org/bot{TG_TOKEN}/setWebhook?url=https://reformaeat.ru/bot-webhook.php
 */
require_once __DIR__ . '/payment-config.php';

$input  = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) { http_response_code(400); exit; }

function tgH($s) {
    return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], (string)$s);
}

function tgPost($method, array $params) {
    $ch = curl_init('https://api.telegram.org/bot' . TG_TOKEN . '/' . $method);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 9,   // под лимит 10 сек для pre_checkout_query
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result = curl_exec($ch);
    $errno  = curl_errno($ch);
    curl_close($ch);
    if ($result === false) {
        error_log('bot-webhook tgPost ' . $method . ' curl error: ' . $errno);
        return null;
    }
    return json_decode($result, true);
}

// ── Обязательно: подтвердить pre_checkout_query в течение 10 сек ──
if (isset($update['pre_checkout_query'])) {
    $pcq = $update['pre_checkout_query'];
    $resp = tgPost('answerPreCheckoutQuery', [
        'pre_checkout_query_id' => $pcq['id'],
        'ok'                    => 'true',
    ]);
    if (!($resp['ok'] ?? false)) {
        error_log('answerPreCheckoutQuery failed: ' . json_encode($resp));
    }
    http_response_code(200); echo 'OK'; exit;
}

// ── Успешная оплата ──
if (isset($update['message']['successful_payment'])) {
    $msg      = $update['message'];
    $payment  = $msg['successful_payment'];
    $amount   = $payment['total_amount'] / 100; // из копеек в рубли
    $chargeId = $payment['telegram_payment_charge_id'];
    $payload  = json_decode($payment['invoice_payload'], true);

    $orderId = $payload['order_id'] ?? '—';
    $name    = $payload['name']     ?? ($msg['from']['first_name'] ?? '—');
    $phone   = $payload['phone']    ?? '—';

    // Уведомление администратору
    $text = "✅ <b>Оплата через Telegram — reForma eat</b>\n\n"
          . "👤 " . tgH($name) . "  📞 " . tgH($phone) . "\n"
          . "💰 Сумма: <b>" . tgH($amount) . " ₽</b>\n"
          . "🔖 Заказ: <code>" . tgH($orderId) . "</code>\n"
          . "🔑 Charge ID: <code>" . tgH($chargeId) . "</code>";

    $adminResp = tgPost('sendMessage', [
        'chat_id'    => TG_CHAT,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ]);
    if (!($adminResp['ok'] ?? false)) {
        error_log('bot-webhook admin notify failed: ' . json_encode($adminResp));
    }

    // Подтверждение пользователю (plain text — спецсимволы безопасны)
    tgPost('sendMessage', [
        'chat_id' => $msg['chat']['id'],
        'text'    => "✅ Оплата получена! Заказ принят в работу. Спасибо, {$name}!",
    ]);
}

http_response_code(200);
echo 'OK';
