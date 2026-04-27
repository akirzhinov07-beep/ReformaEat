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

// ── Обязательно: подтвердить pre_checkout_query в течение 10 сек ──
if (isset($update['pre_checkout_query'])) {
    $pcq = $update['pre_checkout_query'];
    file_get_contents('https://api.telegram.org/bot' . TG_TOKEN . '/answerPreCheckoutQuery?' . http_build_query([
        'pre_checkout_query_id' => $pcq['id'],
        'ok'                    => true
    ]));
    http_response_code(200); echo 'OK'; exit;
}

// ── Успешная оплата ──
if (isset($update['message']['successful_payment'])) {
    $msg      = $update['message'];
    $payment  = $msg['successful_payment'];
    $amount   = $payment['total_amount'] / 100; // из копеек в рубли
    $currency = $payment['currency'];
    $chargeId = $payment['telegram_payment_charge_id'];
    $payload  = json_decode($payment['invoice_payload'], true);

    $orderId = $payload['order_id'] ?? '—';
    $name    = $payload['name']     ?? ($msg['from']['first_name'] ?? '—');
    $phone   = $payload['phone']    ?? '—';

    // Уведомление администратору
    $text = "✅ *Оплата через Telegram — reForma eat*\n\n"
          . "👤 {$name}  📞 {$phone}\n"
          . "💰 Сумма: *{$amount} ₽*\n"
          . "🔖 Заказ: `{$orderId}`\n"
          . "🔑 Charge ID: `{$chargeId}`";

    file_get_contents('https://api.telegram.org/bot' . TG_TOKEN . '/sendMessage?' . http_build_query([
        'chat_id'    => TG_CHAT,
        'text'       => $text,
        'parse_mode' => 'Markdown'
    ]));

    // Подтверждение пользователю
    file_get_contents('https://api.telegram.org/bot' . TG_TOKEN . '/sendMessage?' . http_build_query([
        'chat_id'    => $msg['chat']['id'],
        'text'       => "✅ Оплата получена! Заказ принят в работу. Спасибо, {$name}!",
    ]));
}

http_response_code(200);
echo 'OK';
