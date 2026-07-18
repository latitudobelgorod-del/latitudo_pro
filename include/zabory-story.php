<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?
// Включаемая область «История одного забора» (страница /zabory/).
// Макет: Figma раунд 4, компонент 537:19989 — кейс «было → замер и проект → стало».
// Три подблока: серая карточка (6 пунктов + фото), чертёж проекта, две фотографии.
// Правится в админке ТОЛЬКО в режиме HTML-кода.
?>
<h2 class="story__heading">История одного забора</h2>

<div class="story__card">
    <ol class="story__list">
        <li class="story__item">
            <span class="story__num" aria-hidden="true">1</span>
            <span class="story__text">Черная краска постоянно облазила из-за жаркого южного солнца</span>
        </li>
        <li class="story__item">
            <span class="story__num" aria-hidden="true">2</span>
            <span class="story__text">Древесина начала подгнивать и разрушаться</span>
        </li>
        <li class="story__item">
            <span class="story__num" aria-hidden="true">3</span>
            <span class="story__text">Отдельные доски перестали держать крепеж и &laquo;отстрелили&raquo;</span>
        </li>
        <li class="story__item">
            <span class="story__num" aria-hidden="true">4</span>
            <span class="story__text">Забор начал стремительно терять престижный внешний вид и требовать внимания</span>
        </li>
        <li class="story__item">
            <span class="story__num" aria-hidden="true">5</span>
            <span class="story__text">Ремонт забора стал невозможен без демонтажа</span>
        </li>
        <li class="story__item">
            <span class="story__num" aria-hidden="true">6</span>
            <span class="story__text">Терпение хозяев закончилось и они позвонили в ЛАТИТУДО</span>
        </li>
    </ol>
    <img class="story__photo"
         src="/local/templates/latitudo/images/story-before.webp"
         width="610" height="530" loading="lazy" decoding="async"
         alt="Старый деревянный забор с облезшей чёрной краской">
</div>

<h2 class="story__heading">Мы сделали замер, расчет и проект</h2>

<div class="story__plan">
    <img class="story__plan-img"
         src="/local/templates/latitudo/images/story-plan.webp"
         width="1320" height="620" loading="lazy" decoding="async"
         alt="Чертёж проекта: 221081, Забор и ограждения. Вид спереди">
</div>

<h2 class="story__heading">Подобрали подходящую Доску ДПК цвета Графит шлифованную, и выполнили монтаж</h2>

<div class="story__gallery">
    <img class="story__shot"
         src="/local/templates/latitudo/images/story-after-1.webp"
         width="648" height="400" loading="lazy" decoding="async"
         alt="Готовые ворота из ДПК цвета Графит шлифованный">
    <img class="story__shot"
         src="/local/templates/latitudo/images/story-after-2.webp"
         width="648" height="400" loading="lazy" decoding="async"
         alt="Забор из ДПК цвета Графит шлифованный вдоль участка">
</div>
