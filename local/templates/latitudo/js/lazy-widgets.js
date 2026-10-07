/**
 * Сторонние виджеты — только по первому действию посетителя (касание, прокрутка,
 * движение мыши, клавиша, клик):
 *  - виджет обратного звонка Envybox — если у тега этого скрипта есть
 *    data-envybox="<код виджета>" (header.php ставит его только на боевом домене);
 *  - карты в «Контактах»: <iframe data-lazy-src="…"> из шаблона
 *    news.list/latitudo_contacts — адрес переносится в src.
 *
 * Зачем (07.10.2026): проверка 152-ФЗ (vlip.site) ставила «загрузку трекеров до
 * согласия», пока Envybox и карта грузились сразу с открытием страницы. Так же
 * сделано на latitudo.ru, там замечание ушло. Метрика здесь не трогается: она
 * считает все посещения (решение заказчика, см. header.php).
 */
(function () {
	var me = document.currentScript;
	var envyboxCode = me && me.getAttribute('data-envybox');
	var done = false, evs = ['touchstart', 'scroll', 'mousemove', 'keydown', 'click'];

	function loadEnvybox() {
		if (!envyboxCode) return;
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

	function showMaps() {
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
