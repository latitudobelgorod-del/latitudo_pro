<?php
/**
 * Демо-данные для блока «Реализованные проекты» (инфоблок ID 4).
 *
 * Что делает:
 *  1) добавляет свойство APPLICATION («Применение», список) с вариантами из макета,
 *     если его ещё нет — на нём строятся кнопки-фильтры;
 *  2) создаёт 3 проекта с превью-фото и галереей — чтобы был виден слайдер в карточке.
 *
 * Запуск (Git Bash):
 *   /c/OSPanel/modules/PHP-8.2/php.exe -d short_open_tag=1 tools/setup-projects.php
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

$root = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $root;
require($root . '/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('iblock')) {
    exit("Модуль iblock не подключён\n");
}

const IBLOCK_ID = 4;

// --- 1. Свойство «Применение» (кнопки-фильтры по макету) ---
$appProp = null;
$rs = CIBlockProperty::GetList([], ['IBLOCK_ID' => IBLOCK_ID, 'CODE' => 'APPLICATION']);
if ($row = $rs->Fetch()) {
    $appProp = (int)$row['ID'];
    echo "Свойство APPLICATION уже есть (ID {$appProp})\n";
} else {
    $values = [
        ['XML_ID' => 'private',   'VALUE' => 'Для частных домов',        'SORT' => 100],
        ['XML_ID' => 'business',  'VALUE' => 'Для бизнеса',              'SORT' => 200],
        ['XML_ID' => 'city',      'VALUE' => 'Для города',               'SORT' => 300],
        ['XML_ID' => 'landscape', 'VALUE' => 'Ландшафтный дизайн и МАФ', 'SORT' => 400],
    ];
    $ibp = new CIBlockProperty();
    $appProp = $ibp->Add([
        'IBLOCK_ID'     => IBLOCK_ID,
        'NAME'          => 'Применение',
        'CODE'          => 'APPLICATION',
        'PROPERTY_TYPE' => 'L',
        'LIST_TYPE'     => 'C',
        'MULTIPLE'      => 'Y',
        'SORT'          => 500,
        'VALUES'        => $values,
    ]);
    if (!$appProp) {
        exit("Не удалось создать свойство APPLICATION: {$ibp->LAST_ERROR}\n");
    }
    echo "Создано свойство APPLICATION (ID {$appProp})\n";
}

// слаг → ID варианта списка
$enumByCode = [];
$rsEnum = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $appProp]);
while ($e = $rsEnum->Fetch()) {
    $enumByCode[$e['XML_ID']] = $e['ID'];
}

// --- 2. Демо-проекты ---
$photo = static function (string $relPath) use ($root): ?array {
    $abs = $root . $relPath;
    return file_exists($abs) ? CFile::MakeFileArray($abs) : null;
};

$projects = [
    [
        'NAME'    => 'Открытые террасы',
        'TEXT'    => 'Терраса 120 м² из ДПК Latitudo, частный дом в Краснодаре.',
        'APPS'    => ['private'],
        'PREVIEW' => '/upload/dealers/dealers-1.png',
        'GALLERY' => ['/upload/dealers/dealers-2.png', '/upload/dealers/dealers-3.png'],
    ],
    [
        'NAME'    => 'Веранды кафе',
        'TEXT'    => 'Летняя веранда ресторана: террасная доска и ограждения из ДПК.',
        'APPS'    => ['business'],
        'PREVIEW' => '/upload/dealers/dealers-2.png',
        'GALLERY' => ['/upload/dealers/dealers-3.png', '/upload/dealers/dealers-1.png'],
    ],
    [
        'NAME'    => 'Набережная',
        'TEXT'    => 'Городское общественное пространство: настил и малые архитектурные формы.',
        'APPS'    => ['city', 'landscape'],
        'PREVIEW' => '/upload/dealers/dealers-3.png',
        'GALLERY' => ['/upload/dealers/dealers-1.png', '/upload/dealers/dealers-2.png'],
    ],
];

$el = new CIBlockElement();
foreach ($projects as $p) {
    // не плодим дубли при повторном запуске
    $exists = CIBlockElement::GetList([], ['IBLOCK_ID' => IBLOCK_ID, 'NAME' => $p['NAME']], false, false, ['ID']);
    if ($exists->Fetch()) {
        echo "Пропуск (уже есть): {$p['NAME']}\n";
        continue;
    }

    $gallery = [];
    foreach ($p['GALLERY'] as $g) {
        $file = $photo($g);
        if ($file) $gallery[] = ['VALUE' => $file];
    }

    $apps = [];
    foreach ($p['APPS'] as $code) {
        if (isset($enumByCode[$code])) $apps[] = $enumByCode[$code];
    }

    $id = $el->Add([
        'IBLOCK_ID'       => IBLOCK_ID,
        'NAME'            => $p['NAME'],
        'ACTIVE'          => 'Y',
        'PREVIEW_TEXT'    => $p['TEXT'],
        'PREVIEW_PICTURE' => $photo($p['PREVIEW']),
        'PROPERTY_VALUES' => [
            'APPLICATION' => $apps,
            'GALLERY'     => $gallery,
        ],
    ]);

    echo $id
        ? "Добавлен проект «{$p['NAME']}» (ID {$id}), фото в галерее: " . count($gallery) . "\n"
        : "Ошибка «{$p['NAME']}»: {$el->LAST_ERROR}\n";
}

echo "Готово. Не забудь сбросить кэш компонентов.\n";
