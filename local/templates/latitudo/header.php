<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <? $APPLICATION->ShowHead(); ?>
    <title><? $APPLICATION->ShowTitle(); ?></title>
    <? // Иконки сайта. /favicon.ico лежит в корне (браузеры и поисковики дёргают его напрямую,
       // даже без <link>), остальные размеры — в шаблоне. PNG-иконки с прозрачным фоном,
       // apple-touch-icon с белым: iOS прозрачность заливает чёрным. ?>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_TEMPLATE_PATH ?>/images/favicon/favicon-32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= SITE_TEMPLATE_PATH ?>/images/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#CC2200">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=PT+Sans:wght@400;700&family=PT+Sans+Caption:wght@400;700&display=swap" rel="stylesheet">
    <? // Токены грузим отдельным <link> ДО styles.css — надёжнее, чем @import внутри styles.css
       // (Bitrix при оптимизации CSS может «потерять» @import → переменные не определятся и шапка «схлопнется»).
       $varsPath = $_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/css/variables.css";
       $cssPath  = $_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/styles.css"; ?>
    <? // Библиотеки (Swiper, Fancybox) лежат в шаблоне — /vendor/, не CDN: jsdelivr не всегда
       // доступен из России, а расхождение «локально не грузится / на проде грузится» уже давало
       // баги-невидимки. Подключаем их ДО наших стилей: библиотека — фундамент, styles.css — отделка
       // поверх; иначе .swiper{position:relative} перебивает нашу вёрстку карточек портфолио. ?>
    <link rel="stylesheet" href="<?= SITE_TEMPLATE_PATH ?>/vendor/swiper/swiper-bundle.min.css">
    <link rel="stylesheet" href="<?= SITE_TEMPLATE_PATH ?>/vendor/fancybox/fancybox.css">
    <link rel="stylesheet" href="<?= SITE_TEMPLATE_PATH ?>/css/variables.css?v=<?= @filemtime($varsPath) ?>">
    <link rel="stylesheet" href="<?= SITE_TEMPLATE_PATH ?>/styles.css?v=<?= @filemtime($cssPath) ?>">
    <script src="<?= SITE_TEMPLATE_PATH ?>/vendor/swiper/swiper-bundle.min.js" defer></script>
    <script src="<?= SITE_TEMPLATE_PATH ?>/vendor/fancybox/fancybox.umd.js" defer></script>
</head>
<body>
    <? $APPLICATION->ShowPanel(); ?>

    <header class="header" id="header">

        <!-- Верхняя полоса: контакты филиала (меняются по городу/поддомену из инфоблока «Магазины») -->
        <? $store = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null; ?>
        <div class="topbar">
            <div class="topbar__inner">
                <? // Адрес — своя колонка слева: длинный текст переносится ВНУТРИ себя,
                   // не расталкивая email/часы/телефон (они держатся вместе справа). ?>
                <div class="topbar__left">
                    <? if ($store && $store['ADDRESS'] !== ''): ?>
                    <span class="topbar__item topbar__item--address">
                        <svg class="topbar__icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
                        <span class="topbar__text"><?= htmlspecialcharsbx($store['ADDRESS']) ?></span>
                    </span>
                    <? endif ?>
                </div>
                <div class="topbar__right">
                    <? if ($store && $store['EMAIL'] !== ''): ?>
                    <a class="topbar__item" href="mailto:<?= htmlspecialcharsbx($store['EMAIL']) ?>">
                        <svg class="topbar__icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5z"/></svg>
                        <span class="topbar__text"><?= htmlspecialcharsbx($store['EMAIL']) ?></span>
                    </a>
                    <? endif ?>
                    <? if ($store && $store['WORK_HOURS'] !== ''): ?>
                    <span class="topbar__item">
                        <svg class="topbar__icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 11h-4V7h2v4h2z"/></svg>
                        <span class="topbar__text"><?= htmlspecialcharsbx($store['WORK_HOURS']) ?></span>
                    </span>
                    <? endif ?>
                    <? if ($store && $store['PHONE'] !== ''): ?>
                    <a class="topbar__item topbar__item--phone" href="<?= htmlspecialcharsbx($store['PHONE_HREF']) ?>">
                        <svg class="topbar__icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M6.6 10.8a15.5 15.5 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.24 11.4 11.4 0 0 0 3.6.6 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.6 3.6a1 1 0 0 1-.25 1z"/></svg>
                        <span class="topbar__text"><?= htmlspecialcharsbx($store['PHONE']) ?></span>
                    </a>
                    <? endif ?>
                </div>
            </div>
        </div>

        <!-- Основная строка: логотип + «Все продукты» + меню-якоря -->
        <div class="header__main">
            <div class="header__container">
                <a href="/" class="header__logo">
                    <img src="<?= SITE_TEMPLATE_PATH ?>/images/logo.png" alt="Latitudo" width="178" height="40">
                </a>

                <div class="header__menu" id="menu">
                    <? // На смартфоне этот блок превращается в полноэкранное меню (макет 537:44552),
                       // открывается кнопкой «Меню» в нижней панели (footer.php) ?>
                    <button class="header__menu-close" id="menuClose" type="button" aria-label="Закрыть меню">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6L6 18"/>
                        </svg>
                    </button>

                    <div class="dropdown" id="dropdown">
                        <button class="dropdown__toggle" id="dropdownToggle" type="button" aria-expanded="false">
                            Все продукты
                            <svg class="dropdown__caret" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
                        </button>
                        <div class="dropdown__menu">
                            <? $APPLICATION->IncludeComponent(
                                "bitrix:menu",
                                "latitudo_catalog",
                                Array(
                                    "ROOT_MENU_TYPE" => "catalog",
                                    "MAX_LEVEL" => "1",
                                    "CHILD_MENU_TYPE" => "",
                                    "USE_EXT" => "Y", // подключить .catalog.menu_ext.php (разделы инфоблока)
                                    "MENU_CACHE_TYPE" => "N",
                                ),
                                false
                            ); ?>
                        </div>
                    </div>

                    <nav class="header__nav" id="nav">
                        <? $APPLICATION->IncludeComponent(
                            "bitrix:menu",
                            "latitudo_top",
                            Array(
                                "ROOT_MENU_TYPE" => "top",
                                "MAX_LEVEL" => "1",
                                "CHILD_MENU_TYPE" => "",
                                "USE_EXT" => "N",
                                "MENU_CACHE_TYPE" => "N",
                            ),
                            false
                        ); ?>
                    </nav>
                </div>

            </div>
        </div>
    </header>
    <div class="nav-overlay" id="navOverlay" hidden></div>

    <main class="main">
