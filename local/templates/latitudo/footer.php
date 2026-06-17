    </main>

    <footer class="footer">
        <div class="footer__container">
            <a href="/" class="footer__logo">
                <img src="<?= SITE_TEMPLATE_PATH ?>/images/logo.png" alt="Latitudo" width="178" height="40">
            </a>

            <div class="footer__cols">
                <div class="footer__col">
                    <h4 class="footer__title">Продукция</h4>
                    <ul class="footer__list">
                        <li><a href="/terrasnaya-doska/">Террасная доска</a></li>
                        <li><a href="/stroitelstvo-terras/">Строительство террас</a></li>
                        <li><a href="/zabory/">Заборы</a></li>
                        <li><a href="/perila/">Перила и ограждения</a></li>
                        <li><a href="/stupeni/">Ступени</a></li>
                        <li><a href="/izdeliya-dpk/">Все изделия из ДПК</a></li>
                    </ul>
                </div>

                <div class="footer__col">
                    <h4 class="footer__title">Филиалы</h4>
                    <ul class="footer__list">
                        <li><a href="https://krd.latitudo.pro/">Краснодар</a></li>
                        <li><a href="https://rnd.latitudo.pro/">Ростов-на-Дону</a></li>
                        <li><a href="https://msk.latitudo.pro/">Москва</a></li>
                        <li><a href="https://vrn.latitudo.pro/">Воронеж</a></li>
                        <li><a href="https://belgorod.latitudo.pro/">Белгород</a></li>
                    </ul>
                </div>

                <div class="footer__col">
                    <h4 class="footer__title">Реквизиты</h4>
                    <ul class="footer__list">
                        <li><a href="#documents">Документы</a></li>
                    </ul>
                </div>

                <div class="footer__col">
                    <h4 class="footer__title">Контакты</h4>
                    <? $footerTel = "tel:" . preg_replace('/[^\d+]/', '', file_get_contents($_SERVER['DOCUMENT_ROOT'].'/include/phone.php')); ?>
                    <div class="footer__phone">
                        <a href="<?= $footerTel ?>">
                            <? $APPLICATION->IncludeFile("/include/phone.php"); ?>
                        </a>
                    </div>
                    <div class="footer__schedule">
                        <? $APPLICATION->IncludeFile("/include/shedule.php"); ?>
                    </div>
                    <div class="footer__social">
                        <a href="#" aria-label="Telegram"><img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/telegram.svg" alt="Telegram" width="28" height="28"></a>
                        <a href="#" aria-label="WhatsApp"><img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/whatsapp.svg" alt="WhatsApp" width="28" height="28"></a>
                        <a href="<?= $footerTel ?>" aria-label="Телефон"><img src="<?= SITE_TEMPLATE_PATH ?>/images/icons/phone.svg" alt="Телефон" width="28" height="28"></a>
                    </div>
                </div>
            </div>

            <div class="footer__bottom">
                <? $APPLICATION->IncludeFile("/include/copyright.php"); ?>
            </div>
        </div>
    </footer>

    <script src="<?= SITE_TEMPLATE_PATH ?>/js/main.js" defer></script>
</body>
</html>
