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

function tgH($s) {
    return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], (string)$s);
}

function tgSend($token, $chatId, $text) {
    $ch = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML'
        ]),
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result = curl_exec($ch);
    if ($result === false) {
        error_log('tgSend curl error: ' . curl_errno($ch));
    } else {
        $resp = json_decode($result, true);
        if (!($resp['ok'] ?? false)) error_log('tgSend API error: ' . $result);
    }
    curl_close($ch);
}

$DAYS_RU   = ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'];
$MONTHS_RU = ['января','февраля','марта','апреля','мая','июня',
               'июля','августа','сентября','октября','ноября','декабря'];

if ($event['event'] === 'payment.succeeded') {

    // Читаем временный файл с полным заказом ДО отправки уведомления
    $tmpFile   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rforder_' . $orderId . '.json';
    $orderData = null;
    if (file_exists($tmpFile)) {
        $orderData = json_decode(file_get_contents($tmpFile), true);
        @unlink($tmpFile);
    }

    // Строим уведомление об оплате
    $mode = $orderData['mode'] ?? '1day';

    if ($mode === '7day') {
        $plan = $orderData['plan'] ?? '';
        $city    = $orderData['city']    ?? '';
        $street  = $orderData['street']  ?? '';
        $house   = $orderData['house']   ?? '';
        $apt     = $orderData['apt']     ?? '';
        $comment = $orderData['comment'] ?? '';
        $promo   = $orderData['promo']   ?? '';
        $discount= $orderData['discount'] ?? 0;
        $days    = $orderData['days']    ?? [];

        $payMsg = "✅ <b>Оплата — 7 дней — reForma eat</b>\n\n"
                . "👤 " . tgH($name) . "  📞 " . tgH($phone) . "\n"
                . "💰 Сумма: <b>" . tgH($amount) . " ₽</b>\n"
                . "🥗 План: " . tgH($plan) . "\n";

        if ($promo)   $payMsg .= "🎟 Промокод: " . tgH($promo) . ($discount ? " (−" . tgH($discount) . " ₽)" : '') . "\n";
        if ($city)    $payMsg .= "📍 " . tgH($city) . ($street ? ', ' . tgH($street) : '') . ($house ? ', д. ' . tgH($house) : '') . ($apt ? ', кв. ' . tgH($apt) : '') . "\n";
        if ($comment) $payMsg .= "💬 " . tgH($comment) . "\n";
        $payMsg .= "🔖 <code>" . tgH($orderId) . "</code>\n\n";

        foreach ($days as $wd) {
            $dateStr = $wd['date'] ?? '';
            if ($dateStr) {
                try {
                    $d      = new DateTime($dateStr . 'T00:00:00');
                    $wdow   = (int)$d->format('w');
                    $wday   = (int)$d->format('j');
                    $wmonth = (int)$d->format('n') - 1;
                    $label  = $DAYS_RU[$wdow] . ', ' . $wday . ' ' . $MONTHS_RU[$wmonth];
                } catch (Exception $e) { $label = $dateStr; }
            } else {
                $label = '—';
            }
            $payMsg .= "📅 <b>{$label}:</b>\n";
            foreach (($wd['dishes'] ?? []) as $type => $dish) {
                $payMsg .= "  • " . tgH($type) . ": " . tgH($dish) . "\n";
            }
            $payMsg .= "\n";
        }

    } else {
        // 1-day
        $plan         = $orderData['plan']         ?? '';
        $deliveryDate = $orderData['deliveryDate'] ?? '';
        $city         = $orderData['city']         ?? '';
        $street       = $orderData['street']       ?? '';
        $house        = $orderData['house']        ?? '';
        $apt          = $orderData['apt']          ?? '';
        $comment      = $orderData['comment']      ?? '';
        $promo        = $orderData['promo']        ?? '';
        $discount     = $orderData['discount']     ?? 0;
        $dishes       = $orderData['dishes']       ?? [];

        $deliveryLabel = $deliveryDate;
        if ($deliveryDate) {
            try {
                $dd      = new DateTime($deliveryDate . 'T00:00:00');
                $ddow    = (int)$dd->format('w');
                $dday    = (int)$dd->format('j');
                $dmonth  = (int)$dd->format('n') - 1;
                $deliveryLabel = $DAYS_RU[$ddow] . ', ' . $dday . ' ' . $MONTHS_RU[$dmonth];
            } catch (Exception $e) {}
        }

        $payMsg = "✅ <b>Оплата — 1 день — reForma eat</b>\n\n"
                . "👤 " . tgH($name) . "  📞 " . tgH($phone) . "\n"
                . "💰 Сумма: <b>" . tgH($amount) . " ₽</b>\n"
                . "🥗 План: " . tgH($plan) . "\n"
                . "📅 Доставка: " . tgH($deliveryLabel) . "\n";

        if ($promo)   $payMsg .= "🎟 Промокод: " . tgH($promo) . ($discount ? " (−" . tgH($discount) . " ₽)" : '') . "\n";
        if ($city)    $payMsg .= "📍 " . tgH($city) . ($street ? ', ' . tgH($street) : '') . ($house ? ', д. ' . tgH($house) : '') . ($apt ? ', кв. ' . tgH($apt) : '') . "\n";
        if ($comment) $payMsg .= "💬 " . tgH($comment) . "\n";
        $payMsg .= "🔖 <code>" . tgH($orderId) . "</code>\n";

        if (!empty($dishes)) {
            $payMsg .= "\n<b>Блюда:</b>\n";
            foreach ($dishes as $type => $dish) {
                $payMsg .= "  • " . tgH($type) . ": " . tgH($dish) . "\n";
            }
        }
    }

    tgSend(TG_TOKEN, TG_CHAT, $payMsg);

    // Помечаем одноразовый промокод как использованный
    if ($orderData) {
        $oPromo = $orderData['promo'] ?? '';
        $oPhone = $orderData['phone'] ?? $phone;
        if ($oPromo && promoIsOneTime($oPromo)) {
            promoMarkUsed($oPhone, $oPromo);
        }
    }
}

if ($event['event'] === 'payment.canceled') {
    $msg = "❌ <b>Оплата отменена</b>\n\n"
         . "👤 " . tgH($name) . "  📞 " . tgH($phone) . "\n"
         . "💰 " . tgH($amount) . " ₽ · Заказ: <code>" . tgH($orderId) . "</code>";

    tgSend(TG_TOKEN, TG_CHAT, $msg);

    // Удаляем временный файл если он остался
    $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rforder_' . $orderId . '.json';
    @unlink($tmpFile);
}

http_response_code(200);
echo 'OK';
