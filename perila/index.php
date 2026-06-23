<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Перила и ограждения");

$heroImageUrl = '';
if (\Bitrix\Main\Loader::includeModule('iblock')) {
    $rsHeroSection = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => 3, 'CODE' => 'perila', 'ACTIVE' => 'Y'],
        false,
        ['ID', 'PICTURE', 'DETAIL_PICTURE']
    );
    if ($arHeroSection = $rsHeroSection->GetNext(false, false)) {
        $fileId = $arHeroSection['DETAIL_PICTURE'] ?: $arHeroSection['PICTURE'];
        if ($fileId) {
            $arHeroFile = CFile::GetFileArray($fileId);
            if ($arHeroFile) $heroImageUrl = $arHeroFile['SRC'];
        }
    }
}
?>

<section class="hero"<?= $heroImageUrl ? ' style="background-image:url(\'' . htmlspecialcharsbx($heroImageUrl) . '\')"' : '' ?>>
    <div class="container">
        <h1 class="hero__title">Перила и ограждения</h1>
        <p class="hero__subtitle">Стильные и безопасные ограждения из ДПК</p>
    </div>
</section>

<section class="section" id="catalog">
    <div class="container">
        <h2 class="section__title">Каталог перил и ограждений</h2>
        <p style="text-align:center; color:#999;">Здесь будет каталог товаров из инфоблока</p>
    </div>
</section>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
