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

    <? // Филиал ищем по CODE (msk/krd/…), а не по свойству SUBDOMAIN: на проде в SUBDOMAIN
    // лежит полный домен (msk.latitudo.pro), а регион-код короткий — фильтр по свойству не совпадал
    // и блок исчезал. CODE одинаковый на локали и проде (см. latitudoCurrentRegionCode).
    $GLOBALS['arVisitStoreFilter'] = ['=CODE' => latitudoCurrentRegionCode()];
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

    <? // Блоки, которые страница попросила поставить сразу за «Посетите магазин»
    // (см. latitudoAfterVisitStore в include/static-blocks.php). Ничего не
    // зарегистрировано — ничего и не выводится.
    latitudoAfterVisitStore(); ?>

    <? // Баннер «Есть вопросы?» (Figma: «Обратная связь») — мессенджеры текущего филиала
    latitudoShowFeedbackBanner(); ?>

    <? // Тот же фикс, что и у visit-store: филиал по CODE, а не по SUBDOMAIN (см. комментарий выше)
    $GLOBALS['arContactsFilter'] = ['=CODE' => latitudoCurrentRegionCode()];
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

    <? // Единая «Форма заявки» — одна на весь сайт, открывается кнопками-триггерами. См. request-form.php
    latitudoShowRequestForm(); ?>

    <? // Баннер согласия на cookie. См. local/php_interface/include/cookie-banner.php
    latitudoShowCookieBanner(); ?>

    <? // Правовые документы — всплывающие окна (не отдельные страницы). Сам текст лежит
       // в /policy.php и /terms.php — правится в админке (Контент → Структура → корень сайта),
       // здесь остаются только «рамки» окон. Открываются ссылками .js-doc-popup из подвала. ?>
    <div class="doc-modal" id="doc-policy" style="display:none" role="dialog" aria-label="Политика конфиденциальности">
        <h3 class="doc-modal__title">Политика конфиденциальности</h3>
        <div class="doc-modal__text">
            <? $APPLICATION->IncludeFile(
                "/policy.php",
                Array(),
                Array("MODE" => "html", "NAME" => "Политика конфиденциальности")
            ); ?>
        </div>
    </div>

    <div class="doc-modal" id="doc-terms" style="display:none" role="dialog" aria-label="Пользовательское соглашение">
        <h3 class="doc-modal__title">Пользовательское соглашение</h3>
        <div class="doc-modal__text">
            <? $APPLICATION->IncludeFile(
                "/terms.php",
                Array(),
                Array("MODE" => "html", "NAME" => "Пользовательское соглашение")
            ); ?>
        </div>
    </div>

    <script>
    (function () {
        /* Открытие правовых попапов через Fancybox.show (не data-fancybox — чтобы
           две ссылки не склеивались в галерею). Fancybox грузится с defer в header.php. */
        document.addEventListener('click', function (e) {
            var link = e.target.closest('.js-doc-popup');
            if (!link) return;
            e.preventDefault();
            if (!window.Fancybox) return;
            var src = link.getAttribute('data-src');
            if (!src) return;
            Fancybox.show([{ src: src, type: 'inline' }], { mainClass: 'fancybox-doc', Thumbs: false });
        });
    })();
    </script>

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
                        <li><a href="/fasady/">Фасады</a></li>
                    </ul>
                </div>

                <div class="footer__col">
                    <h4 class="footer__title">Филиалы</h4>
                    <ul class="footer__list">
                        <li><a href="<?= latitudoCityUrl('krd') ?>">Краснодар</a></li>
                        <li><a href="<?= latitudoCityUrl('rnd') ?>">Ростов-на-Дону</a></li>
                        <li><a href="<?= latitudoCityUrl('msk') ?>">Москва</a></li>
                        <li><a href="<?= latitudoCityUrl('vrn') ?>">Воронеж</a></li>
                        <li><a href="<?= latitudoCityUrl('belgorod') ?>">Белгород</a></li>
                    </ul>
                </div>

                <div class="footer__col">
                    <h4 class="footer__title">Реквизиты</h4>
                    <? // Текст реквизитов правится в админке: Контент → Структура → include/requisites.php
                    $APPLICATION->IncludeFile(
                        "/include/requisites.php",
                        Array(),
                        Array("MODE" => "html", "NAME" => "Подвал — реквизиты")
                    ); ?>
                    <h4 class="footer__title footer__title--sub">Документы</h4>
                    <ul class="footer__list">
                        <li><a href="#" class="js-doc-popup" data-src="#doc-policy">Политика конфиденциальности</a></li>
                        <li><a href="#" class="js-doc-popup" data-src="#doc-terms">Пользовательское соглашение</a></li>
                    </ul>
                </div>

                <? // Контакты филиала — по макету: адрес, график, почта, телефон с красными иконками
                $store = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
                $footerTel = $store ? $store['PHONE_HREF'] : ''; ?>
                <div class="footer__col">
                    <h4 class="footer__title">Контакты</h4>
                    <ul class="footer__contacts">
                        <? if ($store && $store['ADDRESS'] !== ''): ?>
                        <li class="footer__contact">
                            <span class="footer__contact-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
                            </span>
                            <span class="footer__contact-text"><?= latitudoStoreText($store['ADDRESS']) ?></span>
                        </li>
                        <? endif ?>
                        <? if ($store && $store['WORK_HOURS'] !== ''): ?>
                        <li class="footer__contact">
                            <span class="footer__contact-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 11h-4V7h2v4h2z"/></svg>
                            </span>
                            <span class="footer__contact-text"><?= latitudoStoreText($store['WORK_HOURS']) ?></span>
                        </li>
                        <? endif ?>
                        <? if ($store && $store['EMAIL'] !== ''): ?>
                        <li class="footer__contact">
                            <span class="footer__contact-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5z"/></svg>
                            </span>
                            <a class="footer__contact-text footer__contact-text--strong" href="mailto:<?= htmlspecialcharsbx($store['EMAIL']) ?>"><?= htmlspecialcharsbx($store['EMAIL']) ?></a>
                        </li>
                        <? endif ?>
                        <? if ($store && $store['PHONE'] !== ''): ?>
                        <li class="footer__contact">
                            <span class="footer__contact-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M6.6 10.8a15.5 15.5 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.24 11.4 11.4 0 0 0 3.6.6 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.6 3.6a1 1 0 0 1-.25 1z"/></svg>
                            </span>
                            <a class="footer__contact-text footer__contact-text--strong" href="<?= htmlspecialcharsbx($footerTel) ?>"><?= htmlspecialcharsbx($store['PHONE']) ?></a>
                        </li>
                        <? endif ?>
                    </ul>
                </div>
            </div>

            <div class="footer__bottom">
                <? $APPLICATION->IncludeFile("/include/copyright.php"); ?>
            </div>
        </div>
    </footer>

    <? // Нижняя панель навигации — только смартфон (макет 537:39005: tabbar «Меню / Написать / Позвонить») ?>
    <nav class="tabbar" aria-label="Мобильная навигация">
        <button class="tabbar__item" id="menuToggle" type="button" aria-expanded="false" aria-controls="menu">
            <span class="tabbar__icon" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="currentColor"><path d="M4 13C4 8.76 4 6.64 5.32 5.32 6.64 4 8.76 4 13 4s6.36 0 7.68 1.32C22 6.64 22 8.76 22 13s0 6.36-1.32 7.68C19.36 22 17.24 22 13 22s-6.36 0-7.68-1.32C4 19.36 4 17.24 4 13Z"/><path d="M26 35c0-4.24 0-6.36 1.32-7.68C28.64 26 30.76 26 35 26s6.36 0 7.68 1.32C44 28.64 44 30.76 44 35s0 6.36-1.32 7.68C41.36 44 39.24 44 35 44s-6.36 0-7.68-1.32C26 41.36 26 39.24 26 35Z"/><path d="M4 35c0-4.24 0-6.36 1.32-7.68C6.64 26 8.76 26 13 26s6.36 0 7.68 1.32C22 28.64 22 30.76 22 35s0 6.36-1.32 7.68C19.36 44 17.24 44 13 44s-6.36 0-7.68-1.32C4 41.36 4 39.24 4 35Z"/><path d="M26 13c0-4.24 0-6.36 1.32-7.68C28.64 4 30.76 4 35 4s6.36 0 7.68 1.32C44 6.64 44 8.76 44 13s0 6.36-1.32 7.68C41.36 22 39.24 22 35 22s-6.36 0-7.68-1.32C26 19.36 26 17.24 26 13Z"/></svg>
            </span>
            <span class="tabbar__label">Меню</span>
        </button>

        <a class="tabbar__item" href="#contacts">
            <span class="tabbar__icon" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="currentColor"><path d="M41.21 8.34a11.6 11.6 0 0 0-6.66-2.76H13.41a9.44 9.44 0 0 0-9.42 9.44v13.2a9.44 9.44 0 0 0 9.42 9.44h4.66l3.9 3.88c.5.57 1.23.89 2 .88.38 0 .74-.08 1.09-.22.33-.16.63-.37.88-.62l4-4h4.66a9.44 9.44 0 0 0 9.4-9.44v-13.2a9.44 9.44 0 0 0-2.8-6.6ZM14.73 24.68a3.32 3.32 0 1 1 0-6.64 3.32 3.32 0 0 1 0 6.64Zm9.26 0a3.32 3.32 0 1 1 0-6.64 3.32 3.32 0 0 1 0 6.64Zm9.24 0a3.32 3.32 0 1 1 0-6.64 3.32 3.32 0 0 1 0 6.64Z"/></svg>
            </span>
            <span class="tabbar__label">Написать</span>
        </a>

        <a class="tabbar__item" href="<?= htmlspecialcharsbx($footerTel) ?>">
            <span class="tabbar__icon" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="currentColor"><path d="M20.08 10.63l1.3 2.33c1.17 2.1.7 4.85-1.15 6.7 0 0-2.24 2.24 1.82 6.3 4.06 4.05 6.29 1.82 6.29 1.82 1.85-1.85 4.6-2.32 6.7-1.15l2.33 1.3c3.17 1.77 3.54 6.21.75 9-1.67 1.67-3.72 2.97-5.99 3.06-3.81.14-10.3-.82-16.8-7.32C8.83 26.16 7.87 19.68 8.01 15.87c.09-2.27 1.39-4.32 3.06-5.99 2.79-2.79 7.23-2.41 9 .75Z"/></svg>
            </span>
            <span class="tabbar__label">Позвонить</span>
        </a>
    </nav>

    <script src="<?= SITE_TEMPLATE_PATH ?>/js/main.js" defer></script>
</body>
</html>
