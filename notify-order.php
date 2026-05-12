<?php
/**
 * reForma eat — отправка уведомления о заказе в Telegram
 * POST /notify-order.php
 * Body: JSON с данными заказа
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://reformaeat.ru');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false]); exit; }

require_once __DIR__ . '/payment-config.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo json_encode(['ok'=>false]); exit; }

$mode = $data['mode'] ?? '1day';

$DAYS_RU   = ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'];
$MONTHS_RU = ['января','февраля','марта','апреля','мая','июня',
               'июля','августа','сентября','октября','ноября','декабря'];

// Время оформления заказа по Москве
date_default_timezone_set('Europe/Moscow');
$now     = new DateTime();
$dow     = (int)$now->format('w');
$day     = (int)$now->format('j');
$month   = (int)$now->format('n') - 1;
$orderAt = $DAYS_RU[$dow] . ', ' . $day . ' ' . $MONTHS_RU[$month]
         . ' ' . $now->format('Y') . ' в ' . $now->format('H:i');

$name    = $data['name']    ?? '—';
$phone   = $data['phone']   ?? '—';
$city    = $data['city']    ?? '';
$street  = $data['street']  ?? '';
$house   = $data['house']   ?? '';
$comment = $data['comment'] ?? '';
$promo   = $data['promo']   ?? '';
$discount= $data['discount'] ?? 0;

if ($mode === '7day') {
    $plan       = $data['plan']       ?? '';
    $totalPrice = $data['totalPrice'] ?? 0;
    $days       = $data['days']       ?? [];

    $msg = "🗓 *Заказ на 7 дней — reForma Eat*\n\n"
         . "📋 Оформлен: {$orderAt}\n\n"
         . "👤 {$name}\n"
         . "📞 {$phone}\n"
         . "🥗 План: {$plan}\n"
         . "💰 Сумма: " . number_format($totalPrice, 0, '.', ' ') . " ₽\n";

    if ($promo)   $msg .= "🎟 Промокод: {$promo}" . ($discount ? " (−{$discount} ₽)" : '') . "\n";
    if ($city)    $msg .= "📍 {$city}" . ($street ? ", {$street}" : '') . ($house ? ", {$house}" : '') . "\n";
    if ($comment) $msg .= "💬 {$comment}\n";

    $msg .= "\n";

    foreach ($days as $wd) {
        $dateStr = $wd['date'] ?? ''; // "2026-05-13"
        if ($dateStr) {
            $d      = new DateTime($dateStr . 'T00:00:00');
            $wdow   = (int)$d->format('w');
            $wday   = (int)$d->format('j');
            $wmonth = (int)$d->format('n') - 1;
            $label  = $DAYS_RU[$wdow] . ', ' . $wday . ' ' . $MONTHS_RU[$wmonth];
        } else {
            $label = $dateStr;
        }
        $msg .= "📅 *{$label}:*\n";
        foreach (($wd['dishes'] ?? []) as $type => $dish) {
            $msg .= "  • {$type}: {$dish}\n";
        }
        $msg .= "\n";
    }

} else {
    // 1-day order
    $plan         = $data['plan']         ?? '';
    $deliveryDate = $data['deliveryDate'] ?? '';
    $dishes       = $data['dishes']       ?? [];

    // Форматируем дату доставки
    $deliveryLabel = $deliveryDate;
    if ($deliveryDate) {
        $dd      = new DateTime($deliveryDate . 'T00:00:00');
        $ddow    = (int)$dd->format('w');
        $dday    = (int)$dd->format('j');
        $dmonth  = (int)$dd->format('n') - 1;
        $deliveryLabel = $DAYS_RU[$ddow] . ', ' . $dday . ' ' . $MONTHS_RU[$dmonth];
    }

    $msg = "🍽 *Новый заказ reForma Eat (1 день)*\n\n"
         . "📋 Оформлен: {$orderAt}\n\n"
         . "👤 {$name}\n"
         . "📞 {$phone}\n"
         . "📅 Доставка: {$deliveryLabel}\n"
         . "🥗 План: {$plan}\n";

    if ($promo)   $msg .= "🎟 Промокод: {$promo}" . ($discount ? " (−{$discount} ₽)" : '') . "\n";
    if ($comment) $msg .= "💬 {$comment}\n";

    $msg .= "\n*Блюда:*\n";
    foreach ($dishes as $type => $dish) {
        $msg .= "  • {$type}: {$dish}\n";
    }
}

$result = file_get_contents(
    'https://api.telegram.org/bot' . TG_TOKEN . '/sendMessage?' . http_build_query([
        'chat_id'    => TG_CHAT,
        'text'       => $msg,
        'parse_mode' => 'Markdown'
    ])
);

$resp = json_decode($result, true);
echo json_encode(['ok' => $resp['ok'] ?? false]);
