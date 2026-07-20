<?php
/**
 * Миграция ярлыков карточки товара. Идемпотентна: повторный запуск ничего не ломает.
 *
 * Зачем нужна: свойства завели вручную в админке ПРОДА, а база у локалки своя. Скрипт
 * повторяет их один в один, чтобы вёрстку можно было проверять локально, а не на живом сайте.
 *
 *   локально (Git Bash):  C:/OSPanel/modules/PHP-8.2/php.exe -d short_open_tag=On -f tools/setup-catalog-badges.php
 *   на проде (Reg.ru):    ssh regru-latitudo "cd www/latitudo.pro && php tools/setup-catalog-badges.php"
 *
 * Что делает — добавляет элементам «Каталога продукции» (ID=3) три свойства-списка:
 *   GARANTY       — «Гарантия 15 лет» / «Гарантия 25 лет». Слово «Гарантия» ВНУТРИ значения:
 *                   шаблон печатает его как есть и ничего не дописывает.
 *   FREE_DOSTAVKA — «да» / «нет» → зелёный ярлык «Бесплатная доставка».
 *   IN_STOCK      — «да» / «нет» → отметка «В наличии» в строке с ценами.
 * Пустое значение или «нет» = ярлыка нет. Разметка — include/catalog-badges.php.
 *
 * ⚠ ГРАБЛИ: свойство создано, а в админке его НЕ ВИДНО. Причина не в кэше: если
 * пользователь когда-то сохранял настройки формы элемента, Битрикс кладёт в b_user_option
 * запись `form_element_3` с ЖЁСТКИМ перечнем полей и рисует форму строго по ней.
 * Лечится сбросом раскладки формы (у каждого пользователя своя!):
 *   DELETE FROM b_user_option WHERE CATEGORY='form' AND NAME='form_element_3';
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

const CATALOG_IBLOCK_ID = 3; // Каталог продукции

function say(string $msg): void { echo $msg . "\n"; }

// XML_ID у вариантов задаём явно: по нему значения сопоставляются между базами при
// экспорте/импорте. Без него Битрикс сгенерирует случайные — и списки разъедутся.
$properties = [
    [
        'CODE'   => 'GARANTY',
        'NAME'   => 'Гарантия',
        'SORT'   => 600,
        'HINT'   => 'Ярлык в правом верхнем углу фото. Слово «Гарантия» уже входит в вариант — на странице печатается как есть.',
        'VALUES' => [
            ['XML_ID' => 'warranty_15_years', 'VALUE' => 'Гарантия 15 лет', 'SORT' => 100],
            ['XML_ID' => 'warranty_25_years', 'VALUE' => 'Гарантия 25 лет', 'SORT' => 200],
        ],
    ],
    [
        'CODE'   => 'FREE_DOSTAVKA',
        'NAME'   => 'Бесплатная доставка',
        'SORT'   => 610,
        'HINT'   => '«да» — на фото появится зелёный ярлык «Бесплатная доставка». «нет» или пусто — ярлыка не будет.',
        'VALUES' => [
            ['XML_ID' => 'yes', 'VALUE' => 'да',  'SORT' => 100],
            ['XML_ID' => 'no',  'VALUE' => 'нет', 'SORT' => 200],
        ],
    ],
    [
        'CODE'   => 'IN_STOCK',
        'NAME'   => 'В наличии',
        'SORT'   => 620,
        'HINT'   => '«да» — рядом с ценой появится зелёная точка и надпись «В наличии». «нет» или пусто — надписи не будет.',
        'VALUES' => [
            ['XML_ID' => 'yes', 'VALUE' => 'да',  'SORT' => 100],
            ['XML_ID' => 'no',  'VALUE' => 'нет', 'SORT' => 200],
        ],
    ],
];

foreach ($properties as $p) {
    $existing = CIBlockProperty::GetList([], [
        'IBLOCK_ID'         => CATALOG_IBLOCK_ID,
        'CODE'              => $p['CODE'],
        'CHECK_PERMISSIONS' => 'N',
    ])->Fetch();

    if ($existing) {
        // На проде свойства уже заведены руками — ничего не трогаем, чтобы не затереть
        // выставленные значения и не переименовать варианты списка.
        say("· Свойство {$p['CODE']} уже есть (ID={$existing['ID']}) — пропускаем.");
        continue;
    }

    $obj = new CIBlockProperty();
    $id  = $obj->Add([
        'IBLOCK_ID'     => CATALOG_IBLOCK_ID,
        'NAME'          => $p['NAME'],
        'CODE'          => $p['CODE'],
        'PROPERTY_TYPE' => 'L',        // список
        'LIST_TYPE'     => 'L',        // выпадающий, а не радиокнопки
        'MULTIPLE'      => 'N',
        'IS_REQUIRED'   => 'N',
        'SORT'          => $p['SORT'],
        'HINT'          => $p['HINT'],
        'VALUES'        => $p['VALUES'],
    ]);
    say($id ? "+ Свойство {$p['CODE']} добавлено (ID={$id})." : "! Не удалось добавить {$p['CODE']}: " . $obj->LAST_ERROR);
}

say("\nГотово. Поля появятся в админке: Контент → Каталог продукции → «Изменить элемент».");
