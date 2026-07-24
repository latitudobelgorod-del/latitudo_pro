<?php
/**
 * Миграция «динамических лендингов разделов». Идемпотентна: повторный запуск ничего не ломает.
 *
 * Зачем нужна: база у локалки и у сервера РАЗНЫЕ, поэтому одинаковые пользовательские
 * поля надо завести в каждой. Запускать после `git pull`:
 *
 *   локально (Git Bash):  C:/OSPanel/modules/PHP-8.2/php.exe -d short_open_tag=On -f tools/setup-landing-blocks.php
 *   на проде (Reg.ru):    ssh regru-latitudo "cd www/latitudo.pro && php tools/setup-landing-blocks.php"
 *
 * КОНТЕКСТ. Раньше каждый раздел каталога имел свою папку в корне сайта
 * (/zabory/index.php и ещё шесть таких же). Папок больше нет: все разделы рисует один
 * диспетчер local/routes/catalog-landing.php, а состав разделов живёт в инфоблоке.
 * Два блока были «зашиты» в отдельные файлы страниц и превращаются в галочки у раздела:
 *
 *   1. UF_SHOW_ABOUT        — «Компания Латитудо — производитель…» (/include/latitudo-about.php).
 *                             Было на «Террасной доске» и «Перголах».
 *   2. UF_SHOW_HOW_WE_WORK  — «Как мы работаем», 6 шагов (/include/how-we-work.php).
 *                             Было на «Ступенях», причём ПОСЛЕ блока «Посетите магазин».
 *
 * Обе по умолчанию ВЫКЛЮЧЕНЫ: новый раздел получает стандартный набор блоков,
 * а не чужой текст про производство.
 *
 * Третье отличие — блоки между hero и товарами — миграции не требует: шаблон
 * latitudo_products сам подхватывает включаемые области /include/<slug>-story.php
 * и /include/<slug>-benefits.php по слагу раздела (файла нет → блока нет).
 *
 * ⚠ ГРАБЛИ: поле создано, а в админке его НЕ ВИДНО (вкладка «Доп. поля» показывает
 * только старые поля). Причина не в кэше и не в правах: если пользователь когда-то
 * сохранял настройки формы раздела, Битрикс кладёт в b_user_option запись
 * `form_section_<IBLOCK_ID>` с ЖЁСТКИМ перечнем полей и рисует форму строго по ней.
 * Лечится сбросом раскладки формы (у каждого пользователя своя!):
 *   DELETE FROM b_user_option WHERE CATEGORY='form' AND NAME='form_section_3';
 *   rm -rf bitrix/managed_cache/MYSQL/user_option
 * Либо руками: шестерёнка справа от вкладок → вернуть настройки формы по умолчанию.
 */
// Скрипт правит схему инфоблоков с NOT_CHECK_PERMISSIONS — запускать только из консоли.
// Папку закрывает ещё и tools/.htaccess; эта проверка дублирует его на случай AllowOverride None.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}
// В CLI DOCUMENT_ROOT либо пустой, либо отсутствует — берём корень проекта от самого файла.
$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $docRoot;

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
require($docRoot . '/bitrix/modules/main/include/prolog_before.php');

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    exit("Модуль iblock не загружен\n");
}

const CATALOG_IBLOCK_ID = 3; // Каталог продукции

function say(string $msg): void { echo $msg . "\n"; }

// У разделов инфоблока в Битриксе не «свойства», а пользовательские поля (UF_*).
$ufEntity = 'IBLOCK_' . CATALOG_IBLOCK_ID . '_SECTION';
$ufType   = new CUserTypeEntity();

$fields = [
    [
        'ENTITY_ID'         => $ufEntity,
        'FIELD_NAME'        => 'UF_SHOW_ABOUT',
        'USER_TYPE_ID'      => 'boolean',
        'MULTIPLE'          => 'N',
        'MANDATORY'         => 'N',
        'SORT'              => 820,
        'SETTINGS'          => ['DEFAULT_VALUE' => 0, 'DISPLAY' => 'CHECKBOX'],
        'EDIT_FORM_LABEL'   => ['ru' => 'Показывать блок «Компания Латитудо»'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Блок «Компания Латитудо»'],
        'LIST_FILTER_LABEL' => ['ru' => 'Блок «Компания Латитудо»'],
        'HELP_MESSAGE'      => ['ru' => 'Серая плашка «Компания Латитудо — производитель…» с фото шоу-рума. Идёт после блока «С этими товарами покупают». Текст общий для всего сайта и правится один раз: Контент → Структура сайта → include/latitudo-about.php.'],
    ],
    [
        'ENTITY_ID'         => $ufEntity,
        'FIELD_NAME'        => 'UF_SHOW_HOW_WE_WORK',
        'USER_TYPE_ID'      => 'boolean',
        'MULTIPLE'          => 'N',
        'MANDATORY'         => 'N',
        'SORT'              => 830,
        'SETTINGS'          => ['DEFAULT_VALUE' => 0, 'DISPLAY' => 'CHECKBOX'],
        'EDIT_FORM_LABEL'   => ['ru' => 'Показывать блок «Как мы работаем»'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Блок «Как мы работаем»'],
        'LIST_FILTER_LABEL' => ['ru' => 'Блок «Как мы работаем»'],
        'HELP_MESSAGE'      => ['ru' => 'Шесть шагов работы с заказом. По макету встаёт в самом низу страницы, между блоками «Посетите магазин» и «Отзывы». Текст общий для всего сайта: Контент → Структура сайта → include/how-we-work.php.'],
    ],
    // Заведено вручную в админке прода 2026-07-24; здесь — чтобы поле появилось
    // и в локальной базе, и в любой новой.
    [
        'ENTITY_ID'         => $ufEntity,
        'FIELD_NAME'        => 'UF_SHOW_ON_MAIN_PAGE',
        'USER_TYPE_ID'      => 'boolean',
        'MULTIPLE'          => 'N',
        'MANDATORY'         => 'N',
        'SORT'              => 810,
        'SETTINGS'          => ['DEFAULT_VALUE' => 0, 'DISPLAY' => 'CHECKBOX'],
        'EDIT_FORM_LABEL'   => ['ru' => 'Показывать раздел на главной странице'],
        'LIST_COLUMN_LABEL' => ['ru' => 'На главной'],
        'LIST_FILTER_LABEL' => ['ru' => 'На главной'],
        'HELP_MESSAGE'      => ['ru' => 'Галочка стоит = раздел показывается на главной в блоке «Каталог продукции». Галочка снята = раздела на главной нет, но его страница /slug/ работает и доступна по прямой ссылке и из меню «Все продукты».'],
    ],
];

foreach ($fields as $arFields) {
    $name = $arFields['FIELD_NAME'];
    $has  = $ufType->GetList([], ['ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $name])->Fetch();
    if ($has) {
        say("· Поле разделов {$name} уже есть (ID={$has['ID']}).");
        continue;
    }
    $id = $ufType->Add($arFields);
    say($id ? "+ Поле разделов {$name} добавлено (ID={$id})." : "! Не удалось добавить {$name}.");
}

// ── Раскладка блоков как была на старых страницах ────────────────────────────
// Ставим значения ровно так, как блоки выводились до перехода на диспетчер, чтобы
// публичный вывод не изменился. Разделам без блока пишем явный 0: так галочка
// в админке видна снятой, а не «поле никогда не заполняли».
// UF_SHOW_ON_MAIN_PAGE — значения проставлены в админке прода; повторяем их здесь,
// чтобы скрипт на проде остался no-op'ом и не сбросил выбор контент-менеджера.
// «Перголы» с главной сняты намеренно — страница /pergoly/ при этом работает.
$layout = [
    'terrasnaya-doska'    => ['UF_SHOW_ABOUT' => 1, 'UF_SHOW_HOW_WE_WORK' => 0, 'UF_SHOW_ON_MAIN_PAGE' => 1],
    'stroitelstvo-terras' => ['UF_SHOW_ABOUT' => 0, 'UF_SHOW_HOW_WE_WORK' => 0, 'UF_SHOW_ON_MAIN_PAGE' => 1],
    'zabory'              => ['UF_SHOW_ABOUT' => 0, 'UF_SHOW_HOW_WE_WORK' => 0, 'UF_SHOW_ON_MAIN_PAGE' => 1],
    'perila'              => ['UF_SHOW_ABOUT' => 0, 'UF_SHOW_HOW_WE_WORK' => 0, 'UF_SHOW_ON_MAIN_PAGE' => 1],
    'stupeni'             => ['UF_SHOW_ABOUT' => 0, 'UF_SHOW_HOW_WE_WORK' => 1, 'UF_SHOW_ON_MAIN_PAGE' => 1],
    'fasady'              => ['UF_SHOW_ABOUT' => 0, 'UF_SHOW_HOW_WE_WORK' => 0, 'UF_SHOW_ON_MAIN_PAGE' => 1],
    'pergoly'             => ['UF_SHOW_ABOUT' => 1, 'UF_SHOW_HOW_WE_WORK' => 0, 'UF_SHOW_ON_MAIN_PAGE' => 0],
];

say('');
$sectionApi = new CIBlockSection();

foreach ($layout as $slug => $values) {
    // Тот же резолвер, что и у сайта: сначала якорь XML_ID, потом символьный код.
    $sectionId = function_exists('latitudoCatalogSectionId')
        ? latitudoCatalogSectionId($slug, CATALOG_IBLOCK_ID)
        : 0;

    if ($sectionId <= 0) {
        say("! {$slug}: раздел не найден — пропуск (в этой базе его может просто не быть).");
        continue;
    }

    $current = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => CATALOG_IBLOCK_ID, 'ID' => $sectionId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID', 'NAME', 'UF_SHOW_ABOUT', 'UF_SHOW_HOW_WE_WORK']
    )->Fetch();

    $diff = [];
    foreach ($values as $field => $value) {
        if ((string)($current[$field] ?? '') !== (string)$value) {
            $diff[$field] = $value;
        }
    }

    if (!$diff) {
        say("· {$slug}: галочки уже стоят как надо (ID {$sectionId}).");
        continue;
    }

    if ($sectionApi->Update($sectionId, $diff)) {
        $shown = [];
        foreach ($diff as $field => $value) {
            $shown[] = "{$field}={$value}";
        }
        say("+ {$slug}: " . implode(', ', $shown) . " (ID {$sectionId}, «{$current['NAME']}»).");
    } else {
        say("! {$slug}: не удалось обновить раздел ID {$sectionId}: {$sectionApi->LAST_ERROR}");
    }
}

say("\nГотово. Галочки: Контент → Каталог продукции → Разделы → «Изменить раздел».");
