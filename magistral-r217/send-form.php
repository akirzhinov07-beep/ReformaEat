<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contacts.php');
    exit;
}

$name    = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES);
$phone   = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES);
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES);

if (!$name || !$phone) {
    header('Location: contacts.php?status=error');
    exit;
}

// Send email — замените на ваш email
$to      = 'info@magistral-r217.ru';
$subject = 'Новая заявка с сайта Магистраль Р-217';
$body    = "Имя: $name\nТелефон: $phone\nEmail: $email\n\nСообщение:\n$message";
$headers = "From: noreply@magistral-r217.ru\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";

mail($to, $subject, $body, $headers);

header('Location: contacts.php?status=ok');
exit;
