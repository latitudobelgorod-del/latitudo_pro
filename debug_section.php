<?php
// ВРЕМЕННЫЙ ДИАГНОСТИЧЕСКИЙ СКРИПТ — удалить после проверки
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
\Bitrix\Main\Loader::includeModule('iblock');

$rs = CIBlockSection::GetList(
    ['SORT' => 'ASC'],
    ['IBLOCK_ID' => 3, 'ACTIVE' => 'Y'],
    false,
    ['ID', 'NAME', 'CODE', 'DESCRIPTION', 'DESCRIPTION_TYPE', 'PICTURE', 'DETAIL_PICTURE']
);
echo '<pre style="background:#f5f5f5;padding:20px;font-size:13px">';
while ($ar = $rs->GetNext(false, false)) {
    echo 'ID:'.$ar['ID'].' CODE:'.$ar['CODE']."\n";
    echo '  NAME: '.$ar['NAME']."\n";
    echo '  DESC_TYPE: '.($ar['DESCRIPTION_TYPE'] ?? 'n/a')."\n";
    echo '  DESC_LEN: '.mb_strlen($ar['DESCRIPTION'] ?? '')."\n";
    echo '  DESC: '.mb_substr($ar['DESCRIPTION'] ?? '', 0, 200)."\n\n";
}
echo '</pre>';
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
