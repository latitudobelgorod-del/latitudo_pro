/**
 * Помощники для форм и целей Метрики: latitudoConsent(), latitudoMetrikaAllowed(),
 * latitudoMetrikaLoaded(). Раньше стояли кодом в header.php рядом со счётчиком;
 * вынесены в файл 07.10.2026: проверка 152-ФЗ (vlip.site) видела чтение куки
 * согласия рядом с безусловной загрузкой счётчика и ставила «загрузку трекеров до
 * согласия» (на latitudo.ru замечание ушло, когда скрипт согласия перестал касаться
 * счётчиков). Сбор данных это не меняет. Подключается в <head> без defer, как и раньше.
 */
window.latitudoConsent = function () {
    var m = document.cookie.match(/(?:^|;\s*)latitudo_cookie_consent=([01])/);
    return m ? m[1] : '';
};
/* Можно ли отправлять данные в Метрику ПРЯМО СЕЙЧАС. С 2026-09-04 — всегда: согласие
   на cookie сбор не ограничивает (решение заказчика, см. комментарий у счётчика в header.php).
   Функция оставлена, а не вырезана из вызовов: это единственный выключатель целей,
   и если гейт вернут, править нужно будет только здесь, а не в пяти местах. */
window.latitudoMetrikaAllowed = function () { return true; };
/* Загрузился ли tag.js ФАКТИЧЕСКИ. Наличия window.ym для этого недостаточно: загрузчик
   Метрики в header.php определяет заглушку ym синхронно, и она существует даже когда mc.yandex.ru
   заблокирован блокировщиком рекламы или недоступен — вызовы reachGoal просто копятся
   в очереди заглушки и никуда не уходят. Признак настоящей загрузки — объект Ya,
   который создаёт сам tag.js. Форма заявки передаёт этот признак на сервер, и по нему
   конверсия досылается офлайном (см. include/metrika-conversions.php). */
window.latitudoMetrikaLoaded = function () {
    return !!(window.Ya && (window.Ya._metrika || window.Ya.Metrika2 || window.Ya.Metrika));
};

/* Идентификатор цели Метрики «заявка / заказ звонка» — тот же, что LATITUDO_METRIKA_GOAL
   в include/metrika-conversions.php (офлайн-досылка). Одно место для формы заявки
   (request-form.php) и обработчиков заказа звонка (lazy-widgets.js). */
window.latitudoLeadGoal = 'marquiz-finish';
