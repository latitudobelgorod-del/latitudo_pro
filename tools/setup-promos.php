<?php
/**
 * Миграция блока «Акции месяца». Идемпотентна: повторный запуск ничего не ломает.
 *
 * База у локалки и у сервера РАЗНЫЕ, поэтому структуру создаём скриптом в каждой.
 * Запускать после `git pull`:
 *
 *   локально (Git Bash):  C:/OSPanel/modules/PHP-8.2/php.exe -d short_open_tag=On -f tools/setup-promos.php
 *   на проде (Reg.ru):    ssh regru-latitudo "cd www/latitudo.pro && php tools/setup-promos.php"
 *
 * Что делает:
 *   1. Создаёт инфоблок «Акции» (код PROMOS): название, картинка-баннер (анонс),
 *      текст «Подробнее об условиях» (детальный текст → выводится в попапе),
 *      даты «Активен с/по» (Битрикс сам скрывает акцию вне дат), сортировка.
 *   2. Добавляет свойства: REGION (привязка к «Магазинам/Регионам», пусто = все регионы)
 *      и SECTIONS (привязка к разделам «Каталога», пусто = все лендинги).
 *   3. Добавляет «Магазинам / Регионам» ссылки мессенджеров (Telegram/WhatsApp/MAX)
 *      для кнопки «Написать в мессенджер» баннера «Есть вопросы?».
 *   4. Выдаёт гостям право на чтение инфоблока «Акции».
 *   5. Заводит 2 демо-акции с баннерами из tools/assets/promos/ (если акций ещё нет).
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

const STORES_IBLOCK_ID  = 6;              // Магазины / Регионы
const CATALOG_IBLOCK_ID = 3;              // Каталог продукции
const IBLOCK_TYPE       = 'latitudo_content';
const PROMOS_CODE       = 'PROMOS';

// Права как у остальных инфоблоков проекта: 1=админы(X), 2=все пользователи(R), 6=контент-редакторы(W)
const IBLOCK_PERMISSIONS = [1 => 'X', 2 => 'R', 6 => 'W'];

function say(string $msg): void { echo $msg . "\n"; }

// ─── 1. Инфоблок «Акции» ─────────────────────────────────────────────────────
$existing = CIBlock::GetList([], ['TYPE' => IBLOCK_TYPE, 'CODE' => PROMOS_CODE, 'CHECK_PERMISSIONS' => 'N'])->Fetch();

if ($existing) {
    $promosId = (int)$existing['ID'];
    say("· Инфоблок «Акции» уже есть (ID={$promosId}).");
} else {
    $ib = new CIBlock();
    $promosId = (int)$ib->Add([
        'ACTIVE'         => 'Y',
        'IBLOCK_TYPE_ID' => IBLOCK_TYPE,
        'LID'            => ['s1'],
        'CODE'           => PROMOS_CODE,
        'NAME'           => 'Акции',
        'SORT'           => 550,
        'INDEX_ELEMENT'  => 'N',
        'INDEX_SECTION'  => 'N',
        'FIELDS'         => [
            'NAME'            => ['NAME' => 'Название акции', 'IS_REQUIRED' => 'Y'],
            'PREVIEW_PICTURE' => [
                'NAME'        => 'Баннер акции',
                'IS_REQUIRED' => 'Y',
                // Подсказка контент-редактору: баннер рисуют дизайнеры целиком, с текстами
                'DEFAULT_VALUE' => ['FROM_DETAIL' => 'N', 'UPDATE_WITH_DETAIL' => 'N'],
            ],
            'DETAIL_TEXT'     => ['NAME' => 'Подробнее об условиях (текст попапа)'],
            'ACTIVE_FROM'     => ['NAME' => 'Дата начала акции'],
            'ACTIVE_TO'       => ['NAME' => 'Дата окончания акции'],
        ],
    ]);

    if (!$promosId) {
        exit("Не удалось создать инфоблок «Акции»: {$ib->LAST_ERROR}\n");
    }
    say("+ Инфоблок «Акции» создан (ID={$promosId}).");
}

// ─── 2–3. Свойства ───────────────────────────────────────────────────────────
$props = [
    [
        // Регион: привязка к элементам «Магазинов/Регионов». Пусто = акция видна во всех городах.
        'IBLOCK_ID'      => $promosId,
        'NAME'           => 'Регион (пусто = все)',
        'CODE'           => 'REGION',
        'PROPERTY_TYPE'  => 'E',
        'LINK_IBLOCK_ID' => STORES_IBLOCK_ID,
        'MULTIPLE'       => 'Y',
        'ACTIVE'         => 'Y',
        'SORT'           => 100,
        'HINT'           => 'В каких городах показывать акцию. Не выбрано — показывается во всех.',
    ],
    [
        // Разделы каталога: на каких лендингах показывать. Пусто = на всех шести.
        'IBLOCK_ID'      => $promosId,
        'NAME'           => 'Разделы каталога (пусто = все)',
        'CODE'           => 'SECTIONS',
        'PROPERTY_TYPE'  => 'G',
        'LINK_IBLOCK_ID' => CATALOG_IBLOCK_ID,
        'MULTIPLE'       => 'Y',
        'ACTIVE'         => 'Y',
        'SORT'           => 110,
        'HINT'           => 'На страницах каких разделов показывать акцию. Не выбрано — на всех.',
    ],
    [
        'IBLOCK_ID'     => STORES_IBLOCK_ID,
        'NAME'          => 'Telegram (ссылка)',
        'CODE'          => 'TELEGRAM',
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE'      => 'N',
        'ACTIVE'        => 'Y',
        'SORT'          => 630,
        'HINT'          => 'Например: https://t.me/latitudo_krd',
    ],
    [
        'IBLOCK_ID'     => STORES_IBLOCK_ID,
        'NAME'          => 'WhatsApp (ссылка)',
        'CODE'          => 'WHATSAPP',
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE'      => 'N',
        'ACTIVE'        => 'Y',
        'SORT'          => 640,
        'HINT'          => 'Например: https://wa.me/79180000000',
    ],
    [
        'IBLOCK_ID'     => STORES_IBLOCK_ID,
        'NAME'          => 'MAX (ссылка)',
        'CODE'          => 'MAX',
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE'      => 'N',
        'ACTIVE'        => 'Y',
        'SORT'          => 650,
        'HINT'          => 'Ссылка на диалог в мессенджере MAX',
    ],
];

$obProp = new CIBlockProperty();
foreach ($props as $arFields) {
    $has = CIBlockProperty::GetList([], ['IBLOCK_ID' => $arFields['IBLOCK_ID'], 'CODE' => $arFields['CODE']])->Fetch();
    if ($has) {
        say("· Свойство {$arFields['CODE']} (инфоблок {$arFields['IBLOCK_ID']}) уже есть.");
        continue;
    }
    say($obProp->Add($arFields)
        ? "+ Свойство {$arFields['CODE']} добавлено в инфоблок {$arFields['IBLOCK_ID']}."
        : "! Свойство {$arFields['CODE']}: {$obProp->LAST_ERROR}");
}

// ─── 4. Права на чтение для гостей ───────────────────────────────────────────
// Без права R у группы 2 штатные компоненты на публичной части отдают «Раздел не найден».
if (!CIBlock::GetGroupPermissions($promosId)) {
    CIBlock::SetPermission($promosId, IBLOCK_PERMISSIONS);
    say('+ Права инфоблока «Акции» выставлены: 1=X, 2=R, 6=W.');
} else {
    say('· Права инфоблока «Акции» уже заданы — не трогаем.');
}

// ─── 5. Демо-акции ───────────────────────────────────────────────────────────
// Заводим, только если в инфоблоке вообще нет элементов, чтобы не мешать боевому контенту.
$hasElements = CIBlockElement::GetList([], ['IBLOCK_ID' => $promosId], false, ['nTopCount' => 1], ['ID'])->Fetch();

if ($hasElements) {
    say('· В инфоблоке «Акции» уже есть элементы — демо не добавляем.');
} else {
    // ID раздела «Террасная доска» — для примера привязки к разделу
    $deckingSection = CIBlockSection::GetList([], [
        'IBLOCK_ID' => CATALOG_IBLOCK_ID, 'CODE' => 'terrasnaya-doska',
    ], false, ['ID'])->Fetch();

    // ID магазина Краснодара — для примера привязки к региону
    $krdStore = CIBlockElement::GetList([], [
        'IBLOCK_ID' => STORES_IBLOCK_ID, 'CODE' => 'krd',
    ], false, false, ['ID'])->Fetch();

    $demos = [
        [
            'NAME'    => 'Террасная доска EasyDecking Wood-X −10%',
            'CODE'    => 'demo-easydecking',
            'SORT'    => 100,
            'PICTURE' => $docRoot . '/tools/assets/promos/promo-easydecking.webp',
            'TEXT'    => '<p>Скидка 10% на террасную доску EasyDecking Wood-X в коричневом цвете. '
                       . 'Действует при заказе от 20 м². Не суммируется с другими акциями.</p>',
            'PROPS'   => array_filter([
                'SECTIONS' => $deckingSection ? [(int)$deckingSection['ID']] : null,
            ]),
        ],
        [
            'NAME'    => 'Бесплатная доставка по Краснодарскому краю',
            'CODE'    => 'demo-delivery',
            'SORT'    => 200,
            'PICTURE' => $docRoot . '/tools/assets/promos/promo-delivery.webp',
            'TEXT'    => '<p>Бесплатная доставка по Краснодарскому краю при единовременной оплате '
                       . 'заказа от 150 000 рублей. Срок доставки — от 2 рабочих дней.</p>',
            'PROPS'   => array_filter([
                'REGION' => $krdStore ? [(int)$krdStore['ID']] : null,
            ]),
        ],
    ];

    $el = new CIBlockElement();
    foreach ($demos as $demo) {
        if (!is_file($demo['PICTURE'])) {
            say("! Демо «{$demo['NAME']}»: нет файла {$demo['PICTURE']} — пропуск.");
            continue;
        }
        $id = $el->Add([
            'IBLOCK_ID'        => $promosId,
            'NAME'             => $demo['NAME'],
            'CODE'             => $demo['CODE'],
            'SORT'             => $demo['SORT'],
            'ACTIVE'           => 'Y',
            'PREVIEW_PICTURE'  => CFile::MakeFileArray($demo['PICTURE']),
            'DETAIL_TEXT'      => $demo['TEXT'],
            'DETAIL_TEXT_TYPE' => 'html',
            'PROPERTY_VALUES'  => $demo['PROPS'],
        ]);
        say($id ? "+ Демо-акция «{$demo['NAME']}» добавлена (ID={$id})." : "! Демо «{$demo['NAME']}»: {$el->LAST_ERROR}");
    }
}

say("\nГотово. ID инфоблока «Акции»: {$promosId}");
