<?php
// Восстанавливает MANAGER_NAME и MANAGER_POSITION для элемента Краснодар (ID=10).
// Имена и должности взяты из скриншота сайта.
// Запустить ОДИН РАЗ: http://latituty.beget.tech/tools/repair-managers.php
// После — удалить файл.

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
if (!CModule::IncludeModule("iblock")) die("iblock not loaded");
header("Content-Type: text/plain; charset=utf-8");

// Порядок имён соответствует порядку фото (file ID 4,5,6,7)
CIBlockElement::SetPropertyValuesEx(10, 6, [
    'MANAGER_NAME' => [
        'Евгений Корнилов',
        'Валентина Гаврикова',
        'Евгений Епишкин',
        'Евгений Яровой',
    ],
    'MANAGER_POSITION' => [
        'Менеджер по продажам',
        'Менеджер по продажам',
        'Менеджер по продажам',
        'Менеджер по работе с дилерами',
    ],
]);

echo "Готово. Проверь сайт и удали этот файл.\n";
