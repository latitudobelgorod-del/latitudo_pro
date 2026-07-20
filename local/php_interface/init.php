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
require_once __DIR__ . '/include/projects.php';
require_once __DIR__ . '/include/video.php';
require_once __DIR__ . '/include/marquiz.php';
require_once __DIR__ . '/include/cookie-banner.php';

// «Форма заявки» работает на компоненте ядра bitrix:main.feedback (см. include/request-form.php).
// Заявки уходят письмом через событие FEEDBACK_FORM. Модуль «Веб-формы» (form) НЕ нужен —
// он не входит в редакцию «Старт» (см. закрытие LICENSE_VIOLATION в WORKFLOW.md).

// Куда падают заявки. Пока единый адрес; позже можно завести почту филиала
// (region.php уже отдаёт EMAIL текущего города — достаточно подставить в EMAIL_TO).
if (!defined('LATITUDO_FEEDBACK_EMAIL')) {
    define('LATITUDO_FEEDBACK_EMAIL', 'content@latitudo.ru');
}

// ID почтового шаблона FEEDBACK_FORM. Обычно НЕ трогаем: код сам находит нужный шаблон
// по метке (см. latitudoFeedbackMailId() в include/request-form.php), т.к. ID у локалки и
// прода разный. Константа — лишь ручной override на крайний случай (>0 — приоритетнее метки).
if (!defined('LATITUDO_FEEDBACK_MAIL_ID')) {
    define('LATITUDO_FEEDBACK_MAIL_ID', 0);
}
