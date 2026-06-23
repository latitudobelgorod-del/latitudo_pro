<?php
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    die('Модуль iblock не загружен');
}

$IBLOCK_ID = 3;

$props = [
    [
        'IBLOCK_ID'     => $IBLOCK_ID,
        'NAME'          => 'Галерея товара',
        'CODE'          => 'GALLERY',
        'PROPERTY_TYPE' => 'F',   // Файл
        'MULTIPLE'      => 'Y',
        'ACTIVE'        => 'Y',
        'SORT'          => 100,
        'FILE_TYPE'     => 'jpg, jpeg, png, webp, gif',
    ],
    [
        'IBLOCK_ID'     => $IBLOCK_ID,
        'NAME'          => 'Актуальная цена',
        'CODE'          => 'PRICE_CURRENT',
        'PROPERTY_TYPE' => 'S',   // Строка
        'MULTIPLE'      => 'N',
        'ACTIVE'        => 'Y',
        'SORT'          => 110,
    ],
    [
        'IBLOCK_ID'     => $IBLOCK_ID,
        'NAME'          => 'Старая цена',
        'CODE'          => 'PRICE_OLD',
        'PROPERTY_TYPE' => 'S',   // Строка
        'MULTIPLE'      => 'N',
        'ACTIVE'        => 'Y',
        'SORT'          => 120,
    ],
];

$obProp = new CIBlockProperty();
$results = [];

foreach ($props as $arFields) {
    // Проверяем — вдруг свойство уже есть
    $existing = CIBlockProperty::GetList(
        [],
        ['IBLOCK_ID' => $IBLOCK_ID, 'CODE' => $arFields['CODE']]
    )->Fetch();

    if ($existing) {
        $results[] = "⚠️  <b>{$arFields['CODE']}</b> — уже существует (ID={$existing['ID']}), пропускаем.";
        continue;
    }

    $id = $obProp->Add($arFields);
    if ($id) {
        $results[] = "✅ <b>{$arFields['CODE']}</b> — добавлено (ID={$id}).";
    } else {
        $err = $obProp->LAST_ERROR;
        $results[] = "❌ <b>{$arFields['CODE']}</b> — ошибка: {$err}";
    }
}

// Удаляем этот файл после выполнения
@unlink(__FILE__);
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Добавление свойств инфоблока</title>
<style>body{font-family:sans-serif;padding:32px;max-width:600px} li{margin:8px 0;font-size:16px}</style>
</head>
<body>
<h2>Свойства инфоблока #<?= $IBLOCK_ID ?> (Каталог продукции)</h2>
<ul>
<?php foreach ($results as $r): ?>
    <li><?= $r ?></li>
<?php endforeach; ?>
</ul>
<p style="color:#888;margin-top:24px">Файл <code>add-iblock-props.php</code> удалён автоматически.</p>
</body>
</html>
