/* Latitudo — шапка, мобильное меню (нижняя панель) и простые карусели */
(function () {
    'use strict';

    var header = document.getElementById('header');
    var menu = document.getElementById('menu');
    var menuToggle = document.getElementById('menuToggle');   // кнопка «Меню» в нижней панели
    var menuClose = document.getElementById('menuClose');     // крестик внутри панели
    var overlay = document.getElementById('navOverlay');
    var dropdown = document.getElementById('dropdown');
    var dropdownToggle = document.getElementById('dropdownToggle');

    /* --- Полноэкранное меню на смартфоне (макет 537:44552) --- */
    function openMenu() {
        document.body.classList.add('nav-open');
        if (menuToggle) menuToggle.setAttribute('aria-expanded', 'true');
        if (overlay) overlay.hidden = false;
    }
    function closeMenu() {
        document.body.classList.remove('nav-open');
        if (menuToggle) menuToggle.setAttribute('aria-expanded', 'false');
        if (overlay) overlay.hidden = true;
    }
    function toggleMenu() {
        if (document.body.classList.contains('nav-open')) closeMenu();
        else openMenu();
    }

    if (menuToggle) menuToggle.addEventListener('click', toggleMenu);
    if (menuClose) menuClose.addEventListener('click', closeMenu);
    if (overlay) overlay.addEventListener('click', closeMenu);
    if (menu) {
        menu.addEventListener('click', function (e) {
            if (e.target.closest('a')) closeMenu();
        });
    }

    /* --- Выпадающее меню «Все продукты» --- */
    function closeDropdown() {
        if (dropdown) dropdown.classList.remove('open');
        if (dropdownToggle) dropdownToggle.setAttribute('aria-expanded', 'false');
    }
    if (dropdownToggle) {
        dropdownToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var willOpen = !dropdown.classList.contains('open');
            dropdown.classList.toggle('open', willOpen);
            dropdownToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    }
    /* клик вне дропдауна — закрыть (на десктопе он абсолютный) */
    document.addEventListener('click', function (e) {
        if (dropdown && !dropdown.contains(e.target)) closeDropdown();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeMenu(); closeDropdown(); }
    });

    /* --- «Липкая» шапка --- */
    function onScroll() {
        if (!header) return;
        if (window.scrollY > 20) header.classList.add('header--scrolled');
        else header.classList.remove('header--scrolled');
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* --- Карусели с точками (преимущества в hero, галерея магазина).
       Разметка: [data-carousel] > [data-carousel-track] + [data-carousel-dots].
       Листание — обычным скроллом со scroll-snap, точки лишь показывают позицию. --- */
    document.querySelectorAll('[data-carousel]').forEach(function (root) {
        var track = root.querySelector('[data-carousel-track]');
        var dots = root.querySelector('[data-carousel-dots]');
        if (!track || !dots) return;

        var slides = Array.prototype.slice.call(track.children);
        if (slides.length < 2) return;

        slides.forEach(function (slide, i) {
            var dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'carousel-dots__dot';
            dot.setAttribute('aria-label', 'Слайд ' + (i + 1));
            dot.addEventListener('click', function () {
                track.scrollTo({ left: slide.offsetLeft - track.offsetLeft, behavior: 'smooth' });
            });
            dots.appendChild(dot);
        });
        var dotList = dots.querySelectorAll('.carousel-dots__dot');

        function step() {
            return (slides[1].offsetLeft - slides[0].offsetLeft) || slides[0].offsetWidth;
        }
        function sync() {
            var i = Math.round(track.scrollLeft / step());
            dotList.forEach(function (d, n) { d.classList.toggle('is-active', n === i); });
        }

        var raf;
        track.addEventListener('scroll', function () {
            cancelAnimationFrame(raf);
            raf = requestAnimationFrame(sync);
        }, { passive: true });
        window.addEventListener('resize', sync);
        sync();
    });
})();
