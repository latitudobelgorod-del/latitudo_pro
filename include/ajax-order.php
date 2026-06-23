<?php
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    die();
}

$name    = trim(strip_tags($_POST['name']    ?? ''));
$phone   = trim(strip_tags($_POST['phone']   ?? ''));
$product = trim(strip_tags($_POST['product'] ?? ''));

if (!$name || !$phone) {
    echo json_encode(['ok' => false, 'error' => 'Заполните имя и телефон']);
    die();
}

// Адрес для уведомлений — берём email из настроек сайта
$toEmail = COption::GetOptionString('main', 'email_from', 'admin@' . $_SERVER['HTTP_HOST']);

$subject = 'Новая заявка с сайта Latitudo: ' . ($product ?: 'Расчёт');
$body    = "Новая заявка с сайта.\n\n"
         . "Имя: {$name}\n"
         . "Телефон: {$phone}\n"
         . "Товар: {$product}\n"
         . "Сайт: http://{$_SERVER['HTTP_HOST']}\n";

$event = new CEvent();
$result = bmail($toEmail, $subject, $body, 'From: noreply@' . $_SERVER['HTTP_HOST']);

echo json_encode(['ok' => true]);
die();
