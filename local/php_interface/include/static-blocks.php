<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Сквозные СТАТИЧНЫЕ блоки лендингов — обёртки над включаемыми областями.
 * На странице достаточно одной строки, например: <? latitudoShowHowWeWork(); ?>
 *
 * Отличие от блоков вроде «Акций» и «Отзывов»: здесь нет инфоблока и выборок из базы.
 * Текст и картинки лежат прямо во включаемой области в /include/ и правятся в админке
 * (Контент → Структура → include/…) визуальным редактором — как «О компании».
 *
 * ВНИМАНИЕ ПРО ГОРОДА. Тексты статичные и одинаковые на всех поддоменах. В блоке
 * «Как мы работаем» упомянуты склад и адрес магазина в Краснодаре — так в макете
 * (Figma 537:24150). Если заказчик захочет свой текст для Москвы и Воронежа,
 * блок придётся переводить на инфоблок с привязкой к региону (см. promos.php).
 */

/**
 * ХУК «после блока Посетите магазин».
 *
 * Зачем нужен. Блок «Посетите магазин» выводится из шаблона footer.php, то есть ПОСЛЕ
 * всего содержимого страницы. Поэтому страница физически не может поставить свой блок
 * сразу за ним — обычный вызов в index.php окажется выше. Хук решает это: страница
 * заранее регистрирует, что нарисовать, а footer.php вызывает это в нужном месте.
 *
 * Использование в index.php раздела (ДО подключения bitrix/footer.php):
 *     latitudoAfterVisitStore(function () {
 *         latitudoShowHowWeWork();
 *     });
 *
 * Страницы, которые хук не регистрируют, работают ровно как раньше.
 */
function latitudoAfterVisitStore(?callable $render = null): void
{
    static $queue = [];

    if ($render !== null) {
        $queue[] = $render;   // регистрация со страницы
        return;
    }

    foreach ($queue as $fn) { // вызов из footer.php
        $fn();
    }
    $queue = [];
}

/**
 * Блок «Компания Латитудо — производитель…» (Figma: компонент «Латитудо» 537:19923).
 * Серая плашка: слева заголовок и три пункта с иконками, справа фото шоу-рума.
 */
function latitudoShowAboutProduction(): void
{
    global $APPLICATION;
    ?>
    <section class="section" id="about-production">
        <div class="container">
            <? $APPLICATION->IncludeFile(
                "/include/latitudo-about.php",
                Array(),
                Array("MODE" => "html", "NAME" => "Блок «Компания Латитудо — производитель»")
            ); ?>
        </div>
    </section>
    <?
}

/**
 * Блок «Вы можете просто купить материал или заказать монтаж под ключ»
 * (Figma 537:24150) — шесть пронумерованных шагов сеткой 3×2, у каждого фото.
 */
function latitudoShowHowWeWork(): void
{
    global $APPLICATION;
    ?>
    <section class="section how-work" id="how-we-work">
        <div class="container">
            <? $APPLICATION->IncludeFile(
                "/include/how-we-work.php",
                Array(),
                Array("MODE" => "html", "NAME" => "Блок «Как мы работаем» (6 шагов)")
            ); ?>
        </div>
    </section>
    <?
}
