<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Строительство террас");

$heroImageUrl = '';
if (\Bitrix\Main\Loader::includeModule('iblock')) {
    $rsHeroSection = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => 3, 'CODE' => 'stroitelstvo-terras', 'ACTIVE' => 'Y'],
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
        <h1 class="hero__title">Строительство террас</h1>
        <p class="hero__subtitle">Профессиональный монтаж террас под ключ</p>
    </div>
</section>

<section class="section" id="catalog">
    <div class="container">
        <h2 class="section__title">Услуги по строительству террас</h2>
        <p style="text-align:center; color:#999;">Здесь будет каталог услуг из инфоблока</p>
    </div>
</section>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
