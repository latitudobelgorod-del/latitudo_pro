<?php
/**
 * Заводит UF-поле раздела «Каталога продукции» для блока «С этими товарами покупают».
 *
 *   UF_ELEMENTS_CATALOG — привязка к элементам инфоблока 3, множественная.
 *   В админке (Контент → Каталог продукции → раздел → вкладка «Доп. поля») это список,
 *   где менеджер отмечает готовые товары, обычно из ЧУЖИХ разделов.
 *
 * На проде поле уже создано вручную — скрипт идемпотентный и там ничего не тронет.
 * Нужен для локалки и любой новой базы, чтобы блок не «пропадал» без объяснений.
 *
 * Запуск:  php tools/setup-related-products.php
 *          (локально — C:\OSPanel\modules\PHP-8.2\php.exe, см. память local-php-cli)
 *
 * ЕСЛИ ПОЛЕ СОЗДАНО, А В ФОРМЕ РАЗДЕЛА ЕГО НЕ ВИДНО — это не кэш: у Битрикса
 * сохранена раскладка формы в b_user_option (form_section_<IBLOCK_ID>).
 * Лечится «Настроить» → «Сбросить» в самой форме (см. docs/bitrix-uf-form-layout.md).
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

if (!Loader::includeModule('iblock')) {
    fwrite(STDERR, "Модуль iblock не подключён\n");
    exit(1);
}

const CATALOG_IBLOCK_ID = 3;

$field = [
    'ENTITY_ID'         => 'IBLOCK_' . CATALOG_IBLOCK_ID . '_SECTION',
    'FIELD_NAME'        => 'UF_ELEMENTS_CATALOG',
    'USER_TYPE_ID'      => 'iblock_element',
    'MULTIPLE'          => 'Y',
    'MANDATORY'         => 'N',
    'SHOW_FILTER'       => 'N',
    'SORT'              => 820,
    'SETTINGS'          => [
        'DISPLAY'       => 'LIST',
        'LIST_HEIGHT'   => 1,
        'IBLOCK_ID'     => CATALOG_IBLOCK_ID,
        'DEFAULT_VALUE' => '',
        'ACTIVE_FILTER' => 'N',
    ],
    'EDIT_FORM_LABEL'   => ['ru' => 'С этими товарами покупают'],
    'LIST_COLUMN_LABEL' => ['ru' => 'С этими товарами покупают'],
    'HELP_MESSAGE'      => ['ru' => 'Товары, которые покажутся отдельным блоком под «Товарами и ценами». Обычно — из других разделов; товар из этого же раздела выведется на странице дважды.'],
];

$ufType = new CUserTypeEntity();
$exists = $ufType->GetList([], [
    'ENTITY_ID'  => $field['ENTITY_ID'],
    'FIELD_NAME' => $field['FIELD_NAME'],
])->Fetch();

if ($exists) {
    echo "OK: {$field['FIELD_NAME']} уже есть (ID {$exists['ID']}) — ничего не меняю\n";
    exit(0);
}

$id = $ufType->Add($field);
if (!$id) {
    global $APPLICATION;
    fwrite(STDERR, "Не удалось создать {$field['FIELD_NAME']}: " . $APPLICATION->GetException()->GetString() . "\n");
    exit(1);
}

echo "Создано: {$field['FIELD_NAME']} (ID {$id})\n";
