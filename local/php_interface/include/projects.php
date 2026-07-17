<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Блок «Портфолио объектов» (инфоблок «Реализованные проекты», IBLOCK_ID=4).
 * Сквозной: выводится на главной и на всех страницах разделов каталога.
 * Табы = РАЗДЕЛЫ инфоблока (см. шаблон latitudo_projects/template.php); свойство
 * «Применение» больше не используется — проекты просто лежат в разделах.
 */

function latitudoShowProjects(): void
{
    global $APPLICATION;
    ?>
    <section class="section" id="projects">
        <div class="container">
            <div class="section__head">
                <h2 class="section__title">Портфолио объектов — наша гордость!</h2>
                <p class="section__subtitle">Материалы от Латитудо применяются по всей стране. Показываем только свои объекты — никаких «фото из интернета». Доставим в любую точку РФ.</p>
            </div>
            <? $APPLICATION->IncludeComponent(
                "bitrix:news.list",
                "latitudo_projects",
                Array(
                    "IBLOCK_TYPE"               => "latitudo_content",
                    "IBLOCK_ID"                 => "4",
                    "NEWS_COUNT"                => "100", // все сразу — фильтр по разделам на JS
                    "SORT_BY1"                  => "SORT",
                    "SORT_ORDER1"               => "ASC",
                    "SORT_BY2"                  => "ID",
                    "SORT_ORDER2"               => "DESC",
                    "FIELD_CODE"                => Array("PREVIEW_PICTURE", "PREVIEW_TEXT", ""),
                    "PROPERTY_CODE"             => Array("GALLERY", ""), // «Применение» убрано — категории берём из разделов
                    "DETAIL_URL"                => "",
                    "AJAX_MODE"                 => "N",
                    "DISPLAY_TOP_PAGER"         => "N",
                    "DISPLAY_BOTTOM_PAGER"      => "N",
                    "CACHE_TYPE"                => "A",
                    "CACHE_TIME"                => "36000",
                    "CACHE_GROUPS"              => "Y",
                    "SET_TITLE"                 => "N",
                    "ADD_SECTIONS_CHAIN"        => "N",
                    "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
                    "PARENT_SECTION"            => "", // проекты всех разделов
                    "CHECK_DATES"               => "Y",
                ),
                false
            ); ?>
        </div>
    </section>
    <?
}
