<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

require_once __DIR__ . '/include/region.php';
require_once __DIR__ . '/include/catalog-sections.php';
require_once __DIR__ . '/include/catalog-badges.php';
require_once __DIR__ . '/include/product-card.php';
require_once __DIR__ . '/include/related-products.php';
require_once __DIR__ . '/include/reviews.php';
require_once __DIR__ . '/include/promos.php';
require_once __DIR__ . '/include/feedback.php';
require_once __DIR__ . '/include/request-form.php';
require_once __DIR__ . '/include/b24-lead.php';
require_once __DIR__ . '/include/metrika-conversions.php';
require_once __DIR__ . '/include/mobile-modals.php';
require_once __DIR__ . '/include/projects.php';
require_once __DIR__ . '/include/video.php';
require_once __DIR__ . '/include/marquiz.php';
require_once __DIR__ . '/include/cookie-banner.php';
require_once __DIR__ . '/include/static-blocks.php';

// Дублирующий хост (www., msk., rnd., krd.) → 301 на канонический. Стоит первым:
// незачем поднимать страницу целиком, чтобы в конце её выбросить. Реализация
// и список дублей — в include/region.php.
latitudoCanonicalHostRedirect();

// Подстановка региональных переменных #REGION_*# во всём HTML страницы (SEO, свойства,
// тексты) по текущему поддомену — как в Aspro. Карта и обработчик — в include/region.php.
AddEventHandler('main', 'OnEndBufferContent', 'latitudoRegionVarsReplace');

// «Форма заявки» работает на компоненте ядра bitrix:main.feedback (см. include/request-form.php).
// Заявки уходят письмом через событие FEEDBACK_FORM. Модуль «Веб-формы» (form) НЕ нужен —
// он не входит в редакцию «Старт» (см. закрытие LICENSE_VIOLATION в WORKFLOW.md).

// Куда падают заявки — ОДИН адрес. Список через запятую сюда класть нельзя:
// Bitrix\Main\Mail\Mail::toPunycode() (mail.php:1300) режет строку по «@» и считает
// доменом всё между первой и второй собакой, из-за чего адрес ломается и письмо
// уходит только первому получателю. Дубль на marketolog@ подключён скрытой копией
// в самом почтовом шаблоне — см. tools/setup-feedback-mail.php.
if (!defined('LATITUDO_FEEDBACK_EMAIL')) {
    define('LATITUDO_FEEDBACK_EMAIL', 'content@latitudo.ru');
}

// ID почтового шаблона FEEDBACK_FORM. Обычно НЕ трогаем: код сам находит нужный шаблон
// по метке (см. latitudoFeedbackMailId() в include/request-form.php), т.к. ID у локалки и
// прода разный. Константа — лишь ручной override на крайний случай (>0 — приоритетнее метки).
if (!defined('LATITUDO_FEEDBACK_MAIL_ID')) {
    define('LATITUDO_FEEDBACK_MAIL_ID', 0);
}
