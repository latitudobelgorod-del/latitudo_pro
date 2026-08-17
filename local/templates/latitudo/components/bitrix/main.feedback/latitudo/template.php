<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Шаблон «Формы заявки» для bitrix:main.feedback (site-шаблон «latitudo»).
 * Поля по макету Figma 537:19344: Имя, Телефон, Ник (Telegram), мессенджер.
 *
 * Компонент несёт только 3 поля: user_name → #AUTHOR#, user_email → #AUTHOR_EMAIL#,
 * MESSAGE → #TEXT#. Телефон/ник/мессенджер JS собирает в скрытое MESSAGE перед сабмитом
 * (см. local/php_interface/include/request-form.php). Открывается и отправляется через JS,
 * поэтому no-JS фоллбэк не нужен — без JS модалка Fancybox всё равно не откроется.
 *
 * @var array $arResult
 * @var CBitrixComponent $this
 */
$hasError = !empty($arResult['ERROR_MESSAGE']);
?>
<form class="request-form" method="post" action="" novalidate>
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="PARAMS_HASH" value="<?= $arResult['PARAMS_HASH'] ?>">
    <input type="hidden" name="submit" value="Y">
    <input type="hidden" name="user_email" value="">
    <input type="hidden" name="MESSAGE" value="">

    <? // Скрытые поля для лида в Битрикс24 (заполняет JS перед сабмитом, см. request-form.php).
       // Сервер (include/b24-lead.php) читает их из $_POST и раскладывает по полям лида. ?>
    <input type="hidden" name="b24_page_url" value="">
    <input type="hidden" name="b24_form_name" value="">
    <input type="hidden" name="b24_utm_source" value="">
    <input type="hidden" name="b24_utm_medium" value="">
    <input type="hidden" name="b24_utm_campaign" value="">
    <input type="hidden" name="b24_utm_content" value="">
    <input type="hidden" name="b24_utm_term" value="">
    <input type="hidden" name="b24_utm_geo" value="">

    <? // Пара для офлайн-конверсий Яндекс.Метрики (include/metrika-conversions.php):
       // yclid — клик по объявлению Директа, consent — состояние куки согласия ('', '0', '1').
       // По ним сервер решает, досылать ли конверсию за тех, у кого счётчик не грузился. ?>
    <input type="hidden" name="b24_yclid" value="">
    <input type="hidden" name="b24_consent" value="">

    <? // honeypot: бот заполнит это поле, человек его не видит; проверка — в request-form.php ?>
    <div class="request-form__hp" aria-hidden="true">
        <label>Оставьте это поле пустым
            <input type="text" name="latitudo_hp" tabindex="-1" autocomplete="off">
        </label>
    </div>

    <p class="request-form__notice" role="alert"<?= $hasError ? '' : ' hidden' ?>><?
        if ($hasError) {
            echo implode('<br>', array_map('htmlspecialcharsbx', $arResult['ERROR_MESSAGE']));
        }
    ?></p>

    <div class="request-form__field">
        <label class="request-form__label" for="rf-name">Имя <span class="request-form__req">*</span></label>
        <input class="request-form__input" type="text" id="rf-name" name="user_name"
               value="<?= $arResult['AUTHOR_NAME'] ?? '' ?>" placeholder="Иванов Иван" autocomplete="name">
    </div>

    <div class="request-form__field">
        <label class="request-form__label" for="rf-phone">Номер телефона <span class="request-form__req">*</span></label>
        <input class="request-form__input" type="tel" id="rf-phone" name="rf_phone"
               placeholder="+7 (999) 999-99-99" autocomplete="tel">
    </div>

    <div class="request-form__field">
        <label class="request-form__label" for="rf-nick">Никнейм (для аккаунта Telegram)</label>
        <input class="request-form__input" type="text" id="rf-nick" name="rf_nick" placeholder="@ivanov">
    </div>

    <div class="request-form__field">
        <span class="request-form__label">Предпочтительный мессенджер</span>
        <div class="request-form__radios">
            <label class="request-form__radio"><input type="radio" name="rf_messenger" value="Max" checked> Max</label>
            <label class="request-form__radio"><input type="radio" name="rf_messenger" value="Telegram"> Telegram</label>
            <label class="request-form__radio"><input type="radio" name="rf_messenger" value="WhatsApp"> WhatsApp</label>
        </div>
    </div>

    <? // Согласие на обработку ПДн (152-ФЗ). По закону галочка НЕ должна стоять по умолчанию;
       // без неё JS не отправит форму (см. request-form.php). ?>
    <label class="request-form__consent">
        <input class="request-form__consent-box" type="checkbox" name="rf_agree" value="Y">
        <span class="request-form__consent-text">
            Я даю согласие ООО «Латитудо-М» на обработку моих персональных данных
            (имя, телефон, e-mail) в соответствии с Федеральным законом №152-ФЗ
            «О персональных данных» в целях обработки моего обращения.
            Ознакомлен(а) с Политикой обработки персональных данных.
        </span>
    </label>

    <button class="request-form__submit" type="submit">Отправить заявку</button>

    <p class="request-form__disclaimer">
        Если вы оставили заявку и ожидаете ответ в Max или Telegram, проверьте и скорректируйте
        настройки конфиденциальности, чтобы мы могли вам написать.
    </p>
</form>
