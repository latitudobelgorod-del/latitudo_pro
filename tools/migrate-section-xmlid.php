<?php
/**
 * Проставляет разделам каталога стабильный якорь XML_ID = slug папки лендинга.
 * После этого переименование раздела в админке (и смена его символьного кода)
 * больше не ломает лендинги — см. local/php_interface/include/catalog-sections.php.
 *
 * Запуск из корня сайта:
 *   прод:    php tools/migrate-section-xmlid.php
 *   локалка: php -d short_open_tag=On tools/migrate-section-xmlid.php
 *
 * Идемпотентен: уже проставленные якоря не трогает.
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('iblock')) {
    fwrite(STDERR, "Модуль iblock не подключился\n");
    exit(1);
}

$iblockId = defined('LATITUDO_CATALOG_IBLOCK_ID') ? LATITUDO_CATALOG_IBLOCK_ID : 3;
$landings = defined('LATITUDO_CATALOG_LANDINGS') ? LATITUDO_CATALOG_LANDINGS : [];

if (!$landings) {
    fwrite(STDERR, "LATITUDO_CATALOG_LANDINGS не определён — проверь local/php_interface/init.php\n");
    exit(1);
}

$sectionApi = new CIBlockSection();

foreach ($landings as $slug => $codes) {
    // Уже есть якорь — ничего не делаем
    $byXml = CIBlockSection::GetList([], [
        'IBLOCK_ID' => $iblockId, 'XML_ID' => $slug, 'CHECK_PERMISSIONS' => 'N',
    ], false, ['ID', 'NAME'])->Fetch();
    if ($byXml) {
        echo "= {$slug}: якорь уже стоит (ID {$byXml['ID']}, «{$byXml['NAME']}»)\n";
        continue;
    }

    // Ищем раздел по известным символьным кодам (актуальный + исторические)
    $section = null;
    foreach ($codes as $code) {
        $section = CIBlockSection::GetList([], [
            'IBLOCK_ID' => $iblockId, 'CODE' => $code, 'CHECK_PERMISSIONS' => 'N',
        ], false, ['ID', 'NAME', 'CODE'])->Fetch();
        if ($section) {
            break;
        }
    }

    if (!$section) {
        echo "! {$slug}: раздел не найден ни по одному из кодов (" . implode(', ', $codes) . ") — пропуск\n";
        continue;
    }

    if ($sectionApi->Update((int)$section['ID'], ['XML_ID' => $slug])) {
        echo "+ {$slug}: якорь проставлен разделу ID {$section['ID']} («{$section['NAME']}», CODE={$section['CODE']})\n";
    } else {
        echo "! {$slug}: не удалось обновить раздел ID {$section['ID']}: {$sectionApi->LAST_ERROR}\n";
    }
}

echo "Готово.\n";
