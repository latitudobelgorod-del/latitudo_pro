<?php
/**
 * Миграция блока «Марквиз». Идемпотентна: повторный запуск ничего не ломает.
 *
 * Зачем нужна: база у локалки и у сервера РАЗНЫЕ, поэтому одинаковые свойства надо
 * завести в каждой. Запускать после `git pull`:
 *
 *   локально (Git Bash):  C:/OSPanel/modules/PHP-8.2/php.exe -d short_open_tag=On -f tools/setup-marquiz.php
 *   на проде (Reg.ru):    ssh regru-latitudo "cd www/latitudo.pro && php tools/setup-marquiz.php"
 *
 * Что делает — добавляет элементам инфоблока «Марквизы» (CODE=marquiz) свойство:
 *   MARQUIZ_TITLE — строка, заголовок над квизом на странице (необязательный).
 *
 * Остальные свойства (MARQUIZ_ID, MARQUIZ_REGION, MARQUIZ_ELEMENT) и сам инфоблок
 * заведены вручную в админке — скрипт их только проверяет и сообщает, если чего-то нет.
 *
 * ⚠ ГРАБЛИ: свойство создано, а в админке его НЕ ВИДНО. Причина не в кэше: если
 * пользователь когда-то сохранял настройки формы элемента, Битрикс кладёт в b_user_option
 * запись `form_element_<IBLOCK_ID>` с ЖЁСТКИМ перечнем полей и рисует форму строго по ней.
 * Лечится сбросом раскладки формы (у каждого пользователя своя!):
 *   DELETE FROM b_user_option WHERE CATEGORY='form' AND NAME='form_element_<IBLOCK_ID>';
 * Либо руками: шестерёнка справа от вкладок → вернуть настройки формы по умолчанию.
 */
// Скрипт правит схему инфоблоков с NOT_CHECK_PERMISSIONS — запускать только из консоли.
// Папку закрывает ещё и tools/.htaccess; эта проверка дублирует его на случай AllowOverride None.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}
$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $docRoot;

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
require($docRoot . '/bitrix/modules/main/include/prolog_before.php');

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    exit("Модуль iblock не загружен\n");
}

function say(string $msg): void { echo $msg . "\n"; }

$iblockId = latitudoMarquizIblockId();
if (!$iblockId) {
    exit("! Инфоблок с кодом «marquiz» не найден. Создайте его в админке и повторите.\n");
}
say("Инфоблок «Марквизы»: ID={$iblockId}");

// Свойства, заведённые вручную. Без них блок не заработает — предупреждаем явно.
$required = [
    'MARQUIZ_ID'      => 'ID марквиза',
    'MARQUIZ_REGION'  => 'Привязка к региону',
    'MARQUIZ_ELEMENT' => 'Привязка к разделу каталога',
];
foreach ($required as $code => $label) {
    $has = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    say($has ? "· Свойство {$code} на месте (ID={$has['ID']})." : "! Свойства {$code} «{$label}» НЕТ — блок работать не будет.");
}

// Добавляемое свойство.
$code = 'MARQUIZ_TITLE';
$has  = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
if ($has) {
    say("· Свойство {$code} уже есть (ID={$has['ID']}).");
} else {
    $prop = new CIBlockProperty();
    $id   = $prop->Add([
        'IBLOCK_ID'     => $iblockId,
        'NAME'          => 'Заголовок над квизом',
        'CODE'          => $code,
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE'      => 'N',
        'IS_REQUIRED'   => 'N',
        'SORT'          => 500,
        'ROW_COUNT'     => 2,
        'COL_COUNT'     => 60,
        'HINT'          => 'Текст над квизом, например: «Получите детальный расчёт террасы с визуализацией напрямую от производителя, потратив 1 минуту на простой тест». Оставьте пустым — квиз выведется без заголовка.',
    ]);
    say($id ? "+ Свойство {$code} добавлено (ID={$id})." : "! Не удалось добавить {$code}: " . $prop->LAST_ERROR);
}

say("\nГотово. Поле появится в админке: Контент → Марквизы → «Изменить элемент».");
