<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?
// Включаемая область «О компании». Статический блок, редактируется в админке.
// Контент перенесён из макета Figma; вёрстка под дизайн — позже.
?>
<div class="about">
    <div class="about__main">
        <h2 class="about__title">О компании</h2>
        <p class="about__lead">Латитудо — профессиональная террасная компания с офисами в Краснодаре, Ростове-на-Дону, Москве, Воронеже и Белгороде.</p>
        <p class="about__text">С 2014 года мы специализируемся на материалах из древесно-полимерного композита (ДПК) для объектов различной сложности: общественных пространств, HoReCa и частных домов.</p>
        <ul class="about__features">
            <li class="about__feature"><img class="about__feature-icon" src="<?= SITE_TEMPLATE_PATH ?>/images/icons/feature-1.svg" width="32" height="32" alt="" aria-hidden="true"><span>Разработка технических решений</span></li>
            <li class="about__feature"><img class="about__feature-icon" src="<?= SITE_TEMPLATE_PATH ?>/images/icons/feature-2.svg" width="32" height="32" alt="" aria-hidden="true"><span>Производство и поставка материалов</span></li>
            <li class="about__feature"><img class="about__feature-icon" src="<?= SITE_TEMPLATE_PATH ?>/images/icons/feature-3.svg" width="32" height="32" alt="" aria-hidden="true"><span>Профессиональный монтаж крупных объектов</span></li>
            <li class="about__feature"><img class="about__feature-icon" src="<?= SITE_TEMPLATE_PATH ?>/images/icons/feature-4.svg" width="32" height="32" alt="" aria-hidden="true"><span>Сопровождение проекта на всех этапах</span></li>
        </ul>
    </div>

    <div class="about__cards">
        <div class="about__card">
            <h3 class="about__card-title">Отдел продаж</h3>
            <p class="about__card-text">Обратитесь в отдел продаж любым удобным способом.</p>
            <div class="about__social">
                <a href="#" aria-label="Telegram"><img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/telegram.svg" alt="Telegram" width="28" height="28"></a>
                <a href="#" aria-label="WhatsApp"><img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/whatsapp.svg" alt="WhatsApp" width="28" height="28"></a>
                <a href="#" aria-label="MAX"><img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/max.svg" alt="MAX" width="28" height="28"></a>
                <a href="#" aria-label="Телефон"><img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/phone.svg" alt="Телефон" width="28" height="28"></a>
            </div>
        </div>

        <div class="about__card">
            <h3 class="about__card-title">Условия</h3>
            <p class="about__card-text">Работаем с юридическими и физическими лицами по любой форме оплаты. Архитекторам и партнёрам — индивидуальные условия!</p>
        </div>

        <div class="about__card about__card--wide">
            <h3 class="about__card-title">Шоу-румы</h3>
            <p class="about__card-text">Для вашего удобства в шоу-румах компании представлены образцы, стенды и каталоги. Опытные менеджеры готовы помочь с расчётами, комплектацией, доставкой в любой регион и услугами монтажа.</p>
        </div>
    </div>
</div>
