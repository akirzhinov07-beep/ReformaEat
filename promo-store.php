<?php
/**
 * reForma eat — хелперы для одноразовых промокодов
 * Подключается через require_once в payment-webhook.php
 * Данные хранятся в promo_used.json рядом с этим файлом
 */

$_PROMO_STORE_FILE = __DIR__ . '/promo_used.json';

function promoIsOneTime(string $promo): bool {
    return in_array(strtoupper(trim($promo)), ['SALE15']);
}

function promoCheckUsed(string $phone, string $promo): bool {
    global $_PROMO_STORE_FILE;
    $phone = preg_replace('/\D/', '', $phone);
    $promo = strtoupper(trim($promo));
    if (!file_exists($_PROMO_STORE_FILE)) return false;
    $fp = @fopen($_PROMO_STORE_FILE, 'r');
    if (!$fp) return false;
    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $used = $content ? json_decode($content, true) : [];
    if (!is_array($used)) return false;
    return in_array($phone . ':' . $promo, $used);
}

function promoMarkUsed(string $phone, string $promo): void {
    global $_PROMO_STORE_FILE;
    $phone = preg_replace('/\D/', '', $phone);
    $promo = strtoupper(trim($promo));
    $key   = $phone . ':' . $promo;
    $fp = @fopen($_PROMO_STORE_FILE, 'c+');
    if (!$fp) return;
    flock($fp, LOCK_EX);
    $content = stream_get_contents($fp);
    $used = $content ? json_decode($content, true) : [];
    if (!is_array($used)) $used = [];
    if (!in_array($key, $used)) {
        $used[] = $key;
        fseek($fp, 0);
        ftruncate($fp, 0);
        fwrite($fp, json_encode($used));
    }
    flock($fp, LOCK_UN);
    fclose($fp);
}
