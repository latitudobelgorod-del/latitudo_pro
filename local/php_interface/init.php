<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

require_once __DIR__ . '/include/region.php';
require_once __DIR__ . '/include/reviews.php';
require_once __DIR__ . '/include/promos.php';
require_once __DIR__ . '/include/feedback.php';
require_once __DIR__ . '/include/request-form.php';
require_once __DIR__ . '/include/projects.php';

// ID веб-формы «Форма заявки» (Сервисы → Веб-формы). 0 = ещё не создана админом →
// показывается статичное превью по макету. Как подключить — см. include/request-form.php.
if (!defined('LATITUDO_REQUEST_FORM_ID')) {
    define('LATITUDO_REQUEST_FORM_ID', 0);
}
