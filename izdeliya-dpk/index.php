<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Все изделия из ДПК");

$heroImageUrl = '';
if (\Bitrix\Main\Loader::includeModule('iblock')) {
    $rsHeroSection = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => 3, 'CODE' => 'izdeliya-dpk', 'ACTIVE' => 'Y'],
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
        <h1 class="hero__title">Все изделия из ДПК</h1>
        <p class="hero__subtitle">Полный ассортимент продукции Latitudo</p>
    </div>
</section>

<section class="section" id="catalog">
    <div class="container">
        <h2 class="section__title">Каталог изделий из ДПК</h2>
        <p style="text-align:center; color:#999;">Здесь будет каталог товаров из инфоблока</p>
    </div>
</section>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
