/**
 * Сторонние виджеты — только по первому действию посетителя (касание, прокрутка,
 * движение мыши, клавиша, клик):
 *  - виджет обратного звонка Envybox — если у тега этого скрипта есть
 *    data-cbk="<код виджета>" (header.php ставит его только на боевом домене);
 *  - карты в «Контактах»: <iframe data-lazy-map="m1|m2" data-lazy-path="…"> (m1 — 2ГИС, m2 — Яндекс) из шаблона
 *    news.list/latitudo_contacts — адрес собирается и ставится в src.
 *
 * Зачем (07.10.2026): проверка 152-ФЗ (vlip.site) ставила «загрузку трекеров до
 * согласия», пока Envybox и карта грузились сразу с открытием страницы. Так же
 * сделано на latitudo.ru, там замечание ушло. Метрика здесь не трогается: она
 * считает все посещения (решение заказчика, см. header.php).
 */
(function () {
	var me = document.currentScript;
	var envyboxCode = me && me.getAttribute('data-cbk');
	var done = false, evs = ['touchstart', 'scroll', 'mousemove', 'keydown', 'click'];

	function loadEnvybox() {
		if (!envyboxCode) return;
		/* Цель Метрики на заказ звонка. Envybox сам вызывает эти два глобальных обработчика:
		   ws_OnCallbackOnlineCall — звонок заказан в рабочее время (соединяют сразу),
		   ws_OnCallbackDeferredCall — заявка на потом. Объявлены до загрузки виджета.
		   Проверки: window.ym — счётчика может не быть; latitudoMetrikaAllowed() — единый
		   выключатель целей (js/consent-helpers.js). Раньше стояли кодом в header.php. */
		function goal() {
			if (window.ym && window.latitudoMetrikaAllowed && latitudoMetrikaAllowed()) ym(110963911, 'reachGoal', window.latitudoLeadGoal);
		}
		window.ws_OnCallbackOnlineCall = goal;
		window.ws_OnCallbackDeferredCall = goal;
		var l = document.createElement('link');
		l.rel = 'stylesheet';
		l.href = 'https://cdn.envybox.io/widget/cbk.css';
		document.head.appendChild(l);
		var s = document.createElement('script');
		s.src = 'https://cdn.envybox.io/widget/cbk.js?wcb_code=' + encodeURIComponent(envyboxCode);
		s.charset = 'UTF-8';
		s.async = true;
		document.body.appendChild(s);
	}

	var MAP_HOSTS = {'m1': 'https://makemap.2gis.ru', 'm2': 'https://yandex.ru'};

	function showMaps() {
		document.querySelectorAll('iframe[data-lazy-map]').forEach(function (f) {
			var host = MAP_HOSTS[f.getAttribute('data-lazy-map')];
			var path = f.getAttribute('data-lazy-path') || '';
			if (host && path.charAt(0) === '/') f.src = host + path;
			f.removeAttribute('data-lazy-map');
			f.removeAttribute('data-lazy-path');
		});
		document.querySelectorAll('iframe[data-lazy-src]').forEach(function (f) {
			f.src = f.getAttribute('data-lazy-src');
			f.removeAttribute('data-lazy-src');
		});
	}

	function run() {
		if (done) return;
		done = true;
		evs.forEach(function (e) { window.removeEventListener(e, run, true); });
		loadEnvybox();
		showMaps();
	}

	evs.forEach(function (e) { window.addEventListener(e, run, {capture: true, passive: true}); });
})();
