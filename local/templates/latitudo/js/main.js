/* Latitudo — поведение шапки: бургер-меню + «уплотнение» при скролле */
(function () {
    'use strict';

    var header = document.getElementById('header');
    var burger = document.getElementById('burger');
    var menu = document.getElementById('menu');
    var overlay = document.getElementById('navOverlay');
    var dropdown = document.getElementById('dropdown');
    var dropdownToggle = document.getElementById('dropdownToggle');

    /* --- Бургер-меню (мобильные) --- */
    function openMenu() {
        document.body.classList.add('nav-open');
        burger.setAttribute('aria-expanded', 'true');
        if (overlay) overlay.hidden = false;
    }
    function closeMenu() {
        document.body.classList.remove('nav-open');
        burger.setAttribute('aria-expanded', 'false');
        if (overlay) overlay.hidden = true;
    }
    function toggleMenu() {
        if (document.body.classList.contains('nav-open')) closeMenu();
        else openMenu();
    }

    if (burger) burger.addEventListener('click', toggleMenu);
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
    /* клик вне дропдауна — закрыть (только на десктопе, где он абсолютный) */
    document.addEventListener('click', function (e) {
        if (dropdown && !dropdown.contains(e.target)) closeDropdown();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeMenu(); closeDropdown(); }
    });

    /* --- «Липкая» шапка: тень + уплотнение после прокрутки --- */
    function onScroll() {
        if (!header) return;
        if (window.scrollY > 20) header.classList.add('header--scrolled');
        else header.classList.remove('header--scrolled');
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
