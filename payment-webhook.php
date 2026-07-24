<?php
/**
 * reForma eat — вебхук ЮКасса
 * Укажи этот URL в ЮКасса → Интеграция → HTTP-уведомления:
 * https://reformaeat.ru/payment-webhook.php
 */
require_once __DIR__ . '/payment-config.php';
require_once __DIR__ . '/promostore.php';

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

function tgSend($token, $chatId, $text) {
    $ch = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown'
        ]),
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result = curl_exec($ch);
    if ($result === false) error_log('tgSend curl error: ' . curl_errno($ch));
    curl_close($ch);
}

$DAYS_RU   = ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'];
$MONTHS_RU = ['января','февраля','марта','апреля','мая','июня',
               'июля','августа','сентября','октября','ноября','декабря'];

if ($event['event'] === 'payment.succeeded') {

    // 1. Краткое уведомление об оплате
    $payMsg = "✅ *Оплата получена — reForma eat*\n\n"
            . "👤 {$name}  📞 {$phone}\n"
            . "💰 Сумма: *{$amount} ₽*\n"
            . "🔖 Заказ: `{$orderId}`\n"
            . "🔑 Payment: `{$paymentId}`";
    tgSend(TG_TOKEN, TG_CHAT, $payMsg);

    // 2. Полное уведомление с составом заказа — читаем из временного файла
    $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rforder_' . $orderId . '.json';
    $orderData = null;
    if (file_exists($tmpFile)) {
        $orderData = json_decode(file_get_contents($tmpFile), true);
        @unlink($tmpFile);
    }

    if ($orderData) {
        // Помечаем одноразовый промокод как использованный после подтверждённой оплаты
        $oPromoCheck = $orderData['promo'] ?? '';
        $oPhoneCheck = $orderData['phone'] ?? $phone;
        if ($oPromoCheck && promoIsOneTime($oPromoCheck)) {
            promoMarkUsed($oPhoneCheck, $oPromoCheck);
        }

        date_default_timezone_set('Europe/Moscow');
        $now     = new DateTime();
        $dow     = (int)$now->format('w');
        $day     = (int)$now->format('j');
        $month   = (int)$now->format('n') - 1;
        $orderAt = $DAYS_RU[$dow] . ', ' . $day . ' ' . $MONTHS_RU[$month]
                 . ' ' . $now->format('Y') . ' в ' . $now->format('H:i');

        $oName    = $orderData['name']    ?? $name;
        $oPhone   = $orderData['phone']   ?? $phone;
        $oCity    = $orderData['city']    ?? '';
        $oStreet  = $orderData['street']  ?? '';
        $oHouse   = $orderData['house']   ?? '';
        $oApt     = $orderData['apt']     ?? '';
        $oComment = $orderData['comment'] ?? '';
        $oPromo   = $orderData['promo']   ?? '';
        $oDiscount= $orderData['discount'] ?? 0;
        $oPlan    = $orderData['plan']    ?? '';
        $oMode    = $orderData['mode']    ?? '1day';

        if ($oMode === '7day') {
            $oTotal = $orderData['totalPrice'] ?? $amount;
            $oDays  = $orderData['days']       ?? [];

            $richMsg = "🗓 *Заказ на 7 дней оплачен — reForma eat*\n\n"
                     . "📋 Оформлен: {$orderAt}\n\n"
                     . "👤 {$oName}\n"
                     . "📞 {$oPhone}\n"
                     . "🥗 План: {$oPlan}\n"
                     . "💰 Сумма: " . number_format((float)$oTotal, 0, '.', ' ') . " ₽\n";

            if ($oPromo)   $richMsg .= "🎟 Промокод: {$oPromo}" . ($oDiscount ? " (−{$oDiscount} ₽)" : '') . "\n";
            if ($oCity)    $richMsg .= "📍 {$oCity}" . ($oStreet ? ", {$oStreet}" : '') . ($oHouse ? ", д. {$oHouse}" : '') . ($oApt ? ", кв. {$oApt}" : '') . "\n";
            if ($oComment) $richMsg .= "💬 {$oComment}\n";

            $richMsg .= "\n";

            foreach ($oDays as $wd) {
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
                $richMsg .= "📅 *{$label}:*\n";
                foreach (($wd['dishes'] ?? []) as $type => $dish) {
                    $richMsg .= "  • {$type}: {$dish}\n";
                }
                $richMsg .= "\n";
            }

        } else {
            // 1-day
            $oDishes      = $orderData['dishes']       ?? [];
            $oDelivDate   = $orderData['deliveryDate'] ?? '';

            $deliveryLabel = $oDelivDate;
            if ($oDelivDate) {
                $dd      = new DateTime($oDelivDate . 'T00:00:00');
                $ddow    = (int)$dd->format('w');
                $dday    = (int)$dd->format('j');
                $dmonth  = (int)$dd->format('n') - 1;
                $deliveryLabel = $DAYS_RU[$ddow] . ', ' . $dday . ' ' . $MONTHS_RU[$dmonth];
            }

            $richMsg = "🍽 *Заказ на 1 день оплачен — reForma eat*\n\n"
                     . "📋 Оформлен: {$orderAt}\n\n"
                     . "👤 {$oName}\n"
                     . "📞 {$oPhone}\n"
                     . "📅 Доставка: {$deliveryLabel}\n"
                     . "🥗 План: {$oPlan}\n";

            if ($oCity)    $richMsg .= "📍 {$oCity}" . ($oStreet ? ", {$oStreet}" : '') . ($oHouse ? ", д. {$oHouse}" : '') . ($oApt ? ", кв. {$oApt}" : '') . "\n";
            if ($oPromo)   $richMsg .= "🎟 Промокод: {$oPromo}" . ($oDiscount ? " (−{$oDiscount} ₽)" : '') . "\n";
            if ($oComment) $richMsg .= "💬 {$oComment}\n";

            $richMsg .= "\n*Блюда:*\n";
            if (is_array($oDishes)) {
                foreach ($oDishes as $type => $dish) {
                    $richMsg .= "  • {$type}: {$dish}\n";
                }
            }
        }

        tgSend(TG_TOKEN, TG_CHAT, $richMsg);
    }
}

if ($event['event'] === 'payment.canceled') {
    $msg = "❌ *Оплата отменена*\n\n"
         . "👤 {$name}  📞 {$phone}\n"
         . "💰 {$amount} ₽ · Заказ: `{$orderId}`";

    tgSend(TG_TOKEN, TG_CHAT, $msg);

    // Удаляем временный файл если он остался
    $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rforder_' . $orderId . '.json';
    @unlink($tmpFile);
}

http_response_code(200);
echo 'OK';
