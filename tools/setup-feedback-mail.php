<?php
/**
 * Настройка почты для «Формы заявки» (компонент bitrix:main.feedback).
 * Идемпотентна: повторный запуск не плодит дубли — находит свой шаблон по метке.
 *
 * Зачем: база у локалки и у сервера РАЗНЫЕ, поэтому почтовый шаблон события
 * FEEDBACK_FORM нужно создать в каждой. Запускать после `git pull`:
 *
 *   локально (Git Bash):  C:/OSPanel/modules/PHP-8.2/php.exe -d short_open_tag=On -f tools/setup-feedback-mail.php
 *   на проде (Reg.ru):    ssh regru-latitudo "cd www/latitudo.pro && php tools/setup-feedback-mail.php"
 *
 * Что делает:
 *   1. Гарантирует наличие типа почтового события FEEDBACK_FORM.
 *   2. Создаёт почтовый шаблон (Кому = #EMAIL_TO#, тело с #AUTHOR# и #TEXT#) для всех сайтов.
 *   3. Печатает ID шаблона.
 *
 * Править init.php НЕ нужно: код сам находит наш шаблон по метке [LATITUDO_FEEDBACK]
 * (см. latitudoFeedbackMailId() в include/request-form.php). ID у локалки и прода разный —
 * поэтому и не хардкодим. Достаточно один раз запустить этот скрипт в каждой среде.
 */

// В CLI DOCUMENT_ROOT либо пустой, либо отсутствует — берём корень проекта от самого файла.
$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $docRoot;

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
require($docRoot . '/bitrix/modules/main/include/prolog_before.php');

const FEEDBACK_EVENT = 'FEEDBACK_FORM';
const TEMPLATE_MARK  = '[LATITUDO_FEEDBACK]'; // метка в теле — по ней ищем свой шаблон при повторе

function say(string $msg): void { echo $msg . "\n"; }

// ─── 1. Тип почтового события FEEDBACK_FORM ──────────────────────────────────
$typeExists = false;
$rsType = CEventType::GetList(['EVENT_NAME' => FEEDBACK_EVENT]);
while ($t = $rsType->Fetch()) {
    if ($t['EVENT_NAME'] === FEEDBACK_EVENT) { $typeExists = true; break; }
}

if (!$typeExists) {
    $et = new CEventType();
    $et->Add([
        'LID'         => 'ru',
        'EVENT_NAME'  => FEEDBACK_EVENT,
        'NAME'        => 'Заявка с сайта (форма обратной связи)',
        'DESCRIPTION' => "#AUTHOR# - имя\n#AUTHOR_EMAIL# - e-mail клиента\n#TEXT# - текст заявки (телефон/мессенджер/ник)\n#EMAIL_TO# - получатель",
    ]);
    say('Тип события ' . FEEDBACK_EVENT . ' создан.');
} else {
    say('Тип события ' . FEEDBACK_EVENT . ' уже есть.');
}

// ─── 2. Список активных сайтов (шаблон письма привязан к сайту) ───────────────
$sites = [];
$rsSites = CSite::GetList('sort', 'asc', ['ACTIVE' => 'Y']);
while ($s = $rsSites->Fetch()) {
    $sites[] = $s['LID'];
}
if (!$sites) {
    $sites[] = 's1';
}

// ─── 3. Почтовый шаблон FEEDBACK_FORM (идемпотентно по метке) ──────────────────
$existingId = 0;
$by = 'id'; $order = 'desc';
$rsMsg = CEventMessage::GetList($by, $order, ['TYPE_ID' => FEEDBACK_EVENT]);
while ($m = $rsMsg->Fetch()) {
    if (mb_strpos((string)$m['MESSAGE'], TEMPLATE_MARK) !== false) {
        $existingId = (int)$m['ID'];
        break;
    }
}

if ($existingId > 0) {
    say('Почтовый шаблон уже создан ранее (ID ' . $existingId . '). Код найдёт его по метке — править ничего не нужно.');
    return;
}

$body =
    "Новая заявка с сайта Latitudo (#SERVER_NAME#)\n" .
    "\n" .
    "Имя: #AUTHOR#\n" .
    "#TEXT#\n" .
    "\n" .
    "E-mail клиента (если указан): #AUTHOR_EMAIL#\n" .
    "\n" .
    "-- \n" .
    "Письмо отправлено получателю: #EMAIL_TO#\n" .
    TEMPLATE_MARK . "\n";

$em = new CEventMessage();
$id = $em->Add([
    'ACTIVE'     => 'Y',
    'EVENT_NAME' => FEEDBACK_EVENT,
    'LID'        => $sites,               // все активные сайты
    'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#', // адрес отправителя сайта — так письмо реже улетает в спам
    'EMAIL_TO'   => '#EMAIL_TO#',           // подставляется из EMAIL_TO компонента (LATITUDO_FEEDBACK_EMAIL)
    'BCC'        => '',
    'SUBJECT'    => 'Новая заявка с сайта Latitudo (#SERVER_NAME#)',
    'BODY_TYPE'  => 'text',
    'MESSAGE'    => $body,
]);

if (!$id) {
    say('ОШИБКА: не удалось создать почтовый шаблон.');
    return;
}

say('Почтовый шаблон создан (ID ' . $id . ') для сайтов: ' . implode(', ', $sites) . '.');
say('Править init.php не нужно — код найдёт шаблон по метке ' . TEMPLATE_MARK . '.');
say('Получатель берётся из EMAIL_TO компонента (константа LATITUDO_FEEDBACK_EMAIL).');
