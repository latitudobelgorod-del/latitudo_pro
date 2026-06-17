<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Latitudo — террасная доска, заборы и фасады из ДПК");
?>

<section class="hero">
    <div class="container">
        <h1 class="hero__title">Latitudo</h1>
        <p class="hero__subtitle">Террасная доска, заборы и фасады из древесно-полимерного композита</p>
        <a href="#catalog" class="hero__btn">Смотреть каталог</a>
    </div>
</section>

<section class="section" id="catalog">
    <div class="container">
        <h2 class="section__title">Каталог продукции</h2>
        <div class="catalog-grid">
            <a href="/terrasnaya-doska/" class="catalog-card">
                <div class="catalog-card__body">
                    <h3 class="catalog-card__title">Террасная доска</h3>
                    <p class="catalog-card__text">Долговечные решения для террас и веранд</p>
                </div>
            </a>
            <a href="/stroitelstvo-terras/" class="catalog-card">
                <div class="catalog-card__body">
                    <h3 class="catalog-card__title">Строительство террас</h3>
                    <p class="catalog-card__text">Профессиональный монтаж под ключ</p>
                </div>
            </a>
            <a href="/zabory/" class="catalog-card">
                <div class="catalog-card__body">
                    <h3 class="catalog-card__title">Заборы</h3>
                    <p class="catalog-card__text">Надёжные и красивые заборы из ДПК</p>
                </div>
            </a>
            <a href="/perila/" class="catalog-card">
                <div class="catalog-card__body">
                    <h3 class="catalog-card__title">Перила и ограждения</h3>
                    <p class="catalog-card__text">Стильные и безопасные ограждения</p>
                </div>
            </a>
            <a href="/stupeni/" class="catalog-card">
                <div class="catalog-card__body">
                    <h3 class="catalog-card__title">Ступени</h3>
                    <p class="catalog-card__text">Прочные и нескользящие ступени</p>
                </div>
            </a>
            <a href="/izdeliya-dpk/" class="catalog-card">
                <div class="catalog-card__body">
                    <h3 class="catalog-card__title">Все изделия из ДПК</h3>
                    <p class="catalog-card__text">Полный ассортимент продукции</p>
                </div>
            </a>
        </div>
    </div>
</section>

<section class="section" id="advantages">
    <div class="container">
        <h2 class="section__title">Преимущества</h2>
        <p style="text-align:center; color:#999;">Блок «Преимущества» — будет добавлен в фазе 4</p>
    </div>
</section>

<section class="section" id="about">
    <div class="container">
        <h2 class="section__title">О компании</h2>
        <p style="text-align:center; color:#999;">Блок «О компании» — будет добавлен в фазе 4</p>
    </div>
</section>

<section class="section" id="projects">
    <div class="container">
        <h2 class="section__title">Реализованные проекты</h2>
        <p style="text-align:center; color:#999;">Блок «Проекты» — будет подключён из инфоблока в фазе 3</p>
    </div>
</section>

<section class="section" id="reviews">
    <div class="container">
        <h2 class="section__title">Отзывы</h2>
        <p style="text-align:center; color:#999;">Блок «Отзывы» — будет подключён из инфоблока в фазе 3</p>
    </div>
</section>

<section class="section" id="contacts">
    <div class="container">
        <h2 class="section__title">Контакты</h2>
        <p style="text-align:center; color:#999;">Блок «Контакты» — будет добавлен в фазе 4</p>
    </div>
</section>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
