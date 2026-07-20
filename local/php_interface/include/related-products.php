<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Блок «С этими товарами покупают» — сопутствующие товары под «Товарами и ценами».
 *
 * КОНТЕНТ. UF-поле РАЗДЕЛА каталога `UF_ELEMENTS_CATALOG` (тип «привязка к элементам»,
 * множественное, инфоблок 3). Контент-менеджер отмечает в разделе готовые товары —
 * обычно из ЧУЖИХ разделов. Раньше для этого товар копировали в чужой раздел, и цены
 * приходилось править в двух местах; поле убирает копирование.
 * Поле пустое → блока нет совсем.
 *
 * Макет: Figma 537:19724 (десктоп, сразу под «Товарами и ценами»),
 *        237:12180 (смартфон — та же лента с точками).
 */

use Bitrix\Main\Loader;

/**
 * Вывести блок для раздела по slug лендинга ('zabory', 'fasady', …).
 * Вызывается со страницы — НЕ из шаблона компонента: у catalog.section кэш на час,
 * и правка галочек в админке была бы видна только после его сброса.
 */
function latitudoShowRelatedProducts(string $slug): void
{
    latitudoShowRelatedProductsForSection(latitudoCatalogSectionId($slug));
}

/** То же самое, но когда ID раздела уже известен (страница /catalog/). */
function latitudoShowRelatedProductsForSection(int $sectionId): void
{
    $cards = latitudoRelatedProductCards($sectionId);
    if (!$cards) {
        return;
    }
    ?>
    <section class="section products-section products-section--related" id="related-products">
        <div class="container">
            <h2 class="section__title">С этими товарами покупают</h2>
            <? latitudoProductsGridOpen(); ?>
            <? foreach ($cards as $card) { latitudoRenderProductCard($card); } ?>
            <? latitudoProductsGridClose(); ?>
        </div>
    </section>
    <? latitudoProductsSliderJs();
}

/**
 * Карточки сопутствующих товаров раздела. Пустой массив — блок не нужен.
 *
 * Показываем ровно то, что отмечено в админке, без «умной» фильтрации. Соблазн был
 * отсеивать товары, уже выведенные блоком «Товары и цены» выше, но надёжно узнать их
 * список нельзя: catalog.section отдаёт готовый HTML из кэша, и на кэш-попадании PHP
 * карточек не выполняется. Поведение получилось бы разным до и после сброса кэша.
 * Правило простое и предсказуемое: не отмечайте товар из этого же раздела.
 */
function latitudoRelatedProductCards(int $sectionId): array
{
    if ($sectionId <= 0 || !Loader::includeModule('iblock')) {
        return [];
    }

    $section = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => LATITUDO_CATALOG_IBLOCK_ID, 'ID' => $sectionId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID', 'UF_ELEMENTS_CATALOG']
    )->Fetch();

    $ids = array_values(array_filter(array_map('intval', (array)($section['UF_ELEMENTS_CATALOG'] ?? []))));
    if (!$ids) {
        return [];
    }

    $cards = [];
    $rs = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID'         => LATITUDO_CATALOG_IBLOCK_ID,
            'ACTIVE'            => 'Y',
            '=ID'               => $ids,
            'CHECK_PERMISSIONS' => 'N',
        ],
        false,
        false,
        false
    );
    while ($el = $rs->GetNextElement(false, false)) {
        $fields = $el->GetFields();
        $cards[(int)$fields['ID']] = latitudoProductCardData($fields, $el->GetProperties());
    }

    // Возвращаем в порядке, заданном менеджером в админке, а не в порядке выборки
    $ordered = [];
    foreach ($ids as $id) {
        if (isset($cards[$id])) {
            $ordered[] = $cards[$id];
        }
    }
    return $ordered;
}
