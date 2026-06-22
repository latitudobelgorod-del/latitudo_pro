<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?
// Включаемая область «Дилерам и партнёрам». Статический блок, редактируется в админке
// (визуальный редактор по кнопке на включаемой области). Вёрстка — по макету Figma 389:11574.
// Фотографии в ряду — плейсхолдеры: заменяются на реальные изображения через админку.
?>
<div class="dealers">
    <div class="dealers__head">
        <h2 class="dealers__title">Дилерам и партнёрам</h2>
        <p class="dealers__subtitle">Материалы от Латитудо применяются по всей стране. Показываем только свои объекты — никаких «фото из интернета». Доставим в любую точку РФ.</p>
    </div>

    <div class="dealers__offer">
        <h3 class="dealers__offer-title">Мы предлагаем вам:</h3>
        <ul class="dealers__grid">
            <li class="dealers__item">
                <span class="dealers__marker" aria-hidden="true"></span>
                <span class="dealers__item-body">
                    <span class="dealers__item-title">Онлайн остатки</span>
                    <span class="dealers__item-text">актуальные остатки, которые обновляются ежесекундно с нашей базой</span>
                </span>
            </li>
            <li class="dealers__item">
                <span class="dealers__marker" aria-hidden="true"></span>
                <span class="dealers__item-body">
                    <span class="dealers__item-title">Маркетинговая поддержка</span>
                    <span class="dealers__item-text">офлайн вывески, помощь с рекламой на популярных площадках, добавление продукции на сайт</span>
                </span>
            </li>
            <li class="dealers__item">
                <span class="dealers__marker" aria-hidden="true"></span>
                <span class="dealers__item-body">
                    <span class="dealers__item-title">Обустройство шоурума материалами</span>
                    <span class="dealers__item-text">пришлём стенды и образцы продукции</span>
                </span>
            </li>
            <li class="dealers__item">
                <span class="dealers__marker" aria-hidden="true"></span>
                <span class="dealers__item-body">
                    <span class="dealers__item-title">Уникальный промобокс с образцами</span>
                    <span class="dealers__item-text">мобильный промобокс с полной линейкой продукции</span>
                </span>
            </li>
            <li class="dealers__item">
                <span class="dealers__marker" aria-hidden="true"></span>
                <span class="dealers__item-body">
                    <span class="dealers__item-title">Склады в Москве и Краснодаре</span>
                    <span class="dealers__item-text">отлаженная логистическая сеть по РФ — гарантия быстрой логистики</span>
                </span>
            </li>
            <li class="dealers__item">
                <span class="dealers__marker" aria-hidden="true"></span>
                <span class="dealers__item-body">
                    <span class="dealers__item-title">Материал с гарантией 15 и 25 лет</span>
                    <span class="dealers__item-text">официальная гарантия от производителя</span>
                </span>
            </li>
        </ul>
    </div>

    <? // Фото дилеров. Файлы лежат в /upload/dealers/ (вне git, заливаются на сервер отдельно). ?>
    <div class="dealers__photos">
        <div class="dealers__photo dealers__photo--sm"><img src="/upload/dealers/dealers-1.png" alt="Шоурум Latitudo — образцы террасной доски"></div>
        <div class="dealers__photo dealers__photo--sm"><img src="/upload/dealers/dealers-2.png" alt="Подбор материалов в шоуруме Latitudo"></div>
        <div class="dealers__photo dealers__photo--lg"><img src="/upload/dealers/dealers-3.png" alt="Команда Latitudo"></div>
    </div>
</div>
