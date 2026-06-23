    <section class="section" id="about">
        <div class="container">
            <? $APPLICATION->IncludeFile(
                "/include/about.php",
                Array(),
                Array("MODE" => "html", "NAME" => "Блок «О компании»")
            ); ?>
        </div>
    </section>

    <section class="section" id="dealers">
        <div class="container">
            <? $APPLICATION->IncludeFile(
                "/include/dealers.php",
                Array(),
                Array("MODE" => "html", "NAME" => "Блок «Дилерам и партнёрам»")
            ); ?>
        </div>
    </section>

    <? $GLOBALS['arVisitStoreFilter'] = ['=PROPERTY_SUBDOMAIN' => latitudoCurrentRegionCode()];
    $APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "latitudo_visit_store",
        Array(
            "IBLOCK_TYPE"          => "latitudo_content",
            "IBLOCK_ID"            => "6",
            "NEWS_COUNT"           => "1",
            "SORT_BY1"             => "ID",
            "SORT_ORDER1"          => "ASC",
            "FIELD_CODE"           => Array("NAME", ""),
            "PROPERTY_CODE"        => Array("GALLERY", "MANAGER_PHOTO", "MANAGER_NAME", "MANAGER_POSITION", "SUBDOMAIN", ""),
            "FILTER_NAME"          => "arVisitStoreFilter",
            "DISPLAY_TOP_PAGER"    => "N",
            "DISPLAY_BOTTOM_PAGER" => "N",
            "CACHE_TYPE"           => "N",
            "SET_TITLE"            => "N",
            "CHECK_DATES"          => "Y",
        ),
        false
    ); ?>

    <? $GLOBALS['arContactsFilter'] = ['=PROPERTY_SUBDOMAIN' => latitudoCurrentRegionCode()];
    $APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "latitudo_contacts",
        Array(
            "IBLOCK_TYPE"          => "latitudo_content",
            "IBLOCK_ID"            => "6",
            "NEWS_COUNT"           => "1",
            "SORT_BY1"             => "ID",
            "SORT_ORDER1"          => "ASC",
            "FIELD_CODE"           => Array("NAME", ""),
            "PROPERTY_CODE"        => Array("ORGANIZATION", "ADDRESS", "ADDRESS_WAREHOUSE", "PHONE", "EMAIL", "WORK_HOURS", "MAP_EMBED", "SUBDOMAIN", ""),
            "FILTER_NAME"          => "arContactsFilter",
            "DISPLAY_TOP_PAGER"    => "N",
            "DISPLAY_BOTTOM_PAGER" => "N",
            "CACHE_TYPE"           => "N",
            "SET_TITLE"            => "N",
            "CHECK_DATES"          => "Y",
        ),
        false
    ); ?>

    </main>

    <footer class="footer">
        <div class="footer__container">
            <a href="/" class="footer__logo">
                <img src="<?= SITE_TEMPLATE_PATH ?>/images/logo.png" alt="Latitudo" width="178" height="40">
            </a>

            <div class="footer__cols">
                <div class="footer__col">
                    <h4 class="footer__title">Продукция</h4>
                    <ul class="footer__list">
                        <li><a href="/terrasnaya-doska/">Террасная доска</a></li>
                        <li><a href="/stroitelstvo-terras/">Строительство террас</a></li>
                        <li><a href="/zabory/">Заборы</a></li>
                        <li><a href="/perila/">Перила и ограждения</a></li>
                        <li><a href="/stupeni/">Ступени</a></li>
                        <li><a href="/izdeliya-dpk/">Все изделия из ДПК</a></li>
                    </ul>
                </div>

                <div class="footer__col">
                    <h4 class="footer__title">Филиалы</h4>
                    <ul class="footer__list">
                        <li><a href="https://krd.latitudo.pro/">Краснодар</a></li>
                        <li><a href="https://rnd.latitudo.pro/">Ростов-на-Дону</a></li>
                        <li><a href="https://msk.latitudo.pro/">Москва</a></li>
                        <li><a href="https://vrn.latitudo.pro/">Воронеж</a></li>
                        <li><a href="https://belgorod.latitudo.pro/">Белгород</a></li>
                    </ul>
                </div>

                <div class="footer__col">
                    <h4 class="footer__title">Реквизиты</h4>
                    <ul class="footer__list">
                        <li><a href="#documents">Документы</a></li>
                    </ul>
                </div>

                <div class="footer__col">
                    <h4 class="footer__title">Контакты</h4>
                    <? $store = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null; ?>
                    <? $footerTel = $store ? $store['PHONE_HREF'] : ''; ?>
                    <div class="footer__phone">
                        <a href="<?= htmlspecialcharsbx($footerTel) ?>">
                            <?= $store ? htmlspecialcharsbx($store['PHONE']) : '' ?>
                        </a>
                    </div>
                    <div class="footer__schedule">
                        <?= $store ? htmlspecialcharsbx($store['WORK_HOURS']) : '' ?>
                    </div>
                    <div class="footer__social">
                        <a href="#" aria-label="Telegram"><img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/telegram.svg" alt="Telegram" width="28" height="28"></a>
                        <a href="#" aria-label="WhatsApp"><img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/whatsapp.svg" alt="WhatsApp" width="28" height="28"></a>
                        <a href="<?= $footerTel ?>" aria-label="Телефон"><img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/phone.svg" alt="Телефон" width="28" height="28"></a>
                    </div>
                </div>
            </div>

            <div class="footer__bottom">
                <? $APPLICATION->IncludeFile("/include/copyright.php"); ?>
            </div>
        </div>
    </footer>

    <script src="<?= SITE_TEMPLATE_PATH ?>/js/main.js" defer></script>
</body>
</html>
