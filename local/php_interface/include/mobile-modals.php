<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Две шторки нижней панели навигации (только смартфон).
 *
 *  • «Напишите нам» (Figma 537:39728) — открывает кнопка «Написать» в tabbar.
 *    Список каналов связи филиала: почта + MAX + Telegram + WhatsApp.
 *  • «Телефон» (Figma 537:39541) — открывает кнопка «Позвонить» в tabbar.
 *    Номер филиала + кнопка «Заказать обратный звонок» → общая форма заявки.
 *
 * Механика та же, что у формы заявки и правовых попапов: скрытый <div> внизу
 * страницы + Fancybox.show([{src:'#id', type:'inline'}]) по делегированному клику.
 * Намеренно НЕ data-fancybox: иначе Fancybox склеит несколько окон в галерею.
 * Свой mainClass у каждой шторки — под него в styles.css прижатие к низу экрана.
 *
 * Данные каналов — свойства инфоблока «Магазины/Регионы» (ID 6): EMAIL, MAX,
 * TELEGRAM, WHATSAPP (см. latitudoCurrentStore()). Незаполненное свойство =
 * строки в списке просто нет: заглушек и мёртвых ссылок быть не должно.
 */

/**
 * Каналы связи текущего филиала в порядке макета.
 * Возвращает только заполненные: [['key','label','href'], …].
 */
function latitudoContactChannels(): array
{
    $store = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
    if (!$store) {
        return [];
    }

    $channels = [];

    if ($store['EMAIL'] !== '') {
        $channels[] = ['key' => 'email', 'label' => $store['EMAIL'], 'href' => 'mailto:' . $store['EMAIL']];
    }
    // Порядок MAX → Telegram → WhatsApp зафиксирован макетом.
    foreach (['max' => 'MAX', 'telegram' => 'Telegram', 'whatsapp' => 'WhatsApp'] as $key => $label) {
        $url = trim($store[mb_strtoupper($key)]);
        if ($url !== '') {
            $channels[] = ['key' => $key, 'label' => $label, 'href' => $url];
        }
    }

    return $channels;
}

/**
 * Иконка канала (Figma 537:39730, бокс 32×32, цвета из макета).
 */
function latitudoChannelIcon(string $key): string
{
    switch ($key) {
        case 'email':
            return '<svg viewBox="0 0 24 24" width="24" height="20" fill="#8B0000" aria-hidden="true">'
                 . '<path d="M2 5.5A1.5 1.5 0 0 1 3.5 4h17A1.5 1.5 0 0 1 22 5.5v13a1.5 1.5 0 0 1-1.5 1.5h-17A1.5 1.5 0 0 1 2 18.5v-13Zm2.2.5L12 12.2 19.8 6H4.2Z"/></svg>';
        case 'max':
            return '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">'
                 . '<defs><linearGradient id="latMaxGrad" x1="0" y1="0" x2="1" y2="1">'
                 . '<stop offset="0" stop-color="#7B4DFF"/><stop offset="1" stop-color="#2E7BFF"/>'
                 . '</linearGradient></defs>'
                 . '<path fill="url(#latMaxGrad)" d="M12 2a10 10 0 0 0-8.7 14.94L2 22l5.2-1.27A10 10 0 1 0 12 2Z"/></svg>';
        case 'telegram':
            return '<svg viewBox="0 0 24 24" width="24" height="24" fill="#4299D3" aria-hidden="true">'
                 . '<path d="M21.9 4.3 18.9 19c-.2 1-.8 1.3-1.7.8l-4.6-3.4-2.2 2.1c-.25.25-.45.45-.9.45l.32-4.6 8.4-7.6c.37-.32-.08-.5-.57-.18L7.3 12.1 2.8 10.7c-.98-.3-1-.98.2-1.45l17.6-6.8c.82-.3 1.53.2 1.3 1.85Z"/></svg>';
        case 'whatsapp':
            return '<svg viewBox="0 0 24 24" width="24" height="24" fill="#1DB353" aria-hidden="true">'
                 . '<path d="M12 2a9.9 9.9 0 0 0-8.5 15.05L2 22l5.1-1.45A9.9 9.9 0 1 0 12 2Zm5.1 13.9c-.24.67-1.2 1.25-1.9 1.35-.5.07-1.13.12-3.34-.78-2.8-1.15-4.6-4.03-4.74-4.22-.14-.19-1.13-1.5-1.13-2.86 0-1.36.7-2.02.95-2.3.25-.28.55-.35.73-.35h.53c.17 0 .4-.06.62.48l.85 2.05c.09.18.15.4.03.63-.12.23-.18.37-.36.57l-.27.31c-.18.18-.37.38-.16.74.21.36.94 1.55 2.02 2.51 1.39 1.24 2.56 1.62 2.92 1.8.36.18.57.15.78-.09l1.11-1.3c.21-.24.39-.19.65-.1l2.14 1.01c.26.12.44.18.5.28.06.1.06.6-.18 1.27Z"/></svg>';
    }
    return '';
}

/**
 * Вывод обеих шторок. Ставится один раз на страницу (footer.php).
 */
function latitudoShowMobileModals(): void
{
    static $rendered = false;
    if ($rendered) {
        return;
    }
    $rendered = true;

    $store    = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
    $channels = latitudoContactChannels();
    $phone    = $store ? $store['PHONE'] : '';
    $phoneTel = $store ? $store['PHONE_HREF'] : '';
    ?>

    <? // ─── Шторка «Напишите нам» (Figma 537:39728) ─────────────────────────── ?>
    <div class="sheet sheet--write" id="write-sheet" style="display:none" role="dialog" aria-label="Напишите нам">
        <h3 class="sheet__title">Напишите нам</h3>
        <? if ($channels): ?>
        <ul class="sheet__channels">
            <? foreach ($channels as $ch): ?>
            <li class="sheet__channel">
                <a class="sheet__channel-link" href="<?= htmlspecialcharsbx($ch['href']) ?>"<?= $ch['key'] === 'email' ? '' : ' target="_blank" rel="noopener"' ?>>
                    <span class="sheet__channel-icon" aria-hidden="true"><?= latitudoChannelIcon($ch['key']) ?></span>
                    <span class="sheet__channel-label"><?= htmlspecialcharsbx($ch['label']) ?></span>
                </a>
            </li>
            <? endforeach ?>
        </ul>
        <? else: ?>
        <p class="sheet__empty">Контакты филиала скоро появятся.</p>
        <? endif ?>
    </div>

    <? // ─── Шторка «Телефон» (Figma 537:39541) ──────────────────────────────── ?>
    <div class="sheet sheet--phone" id="phone-sheet" style="display:none" role="dialog" aria-label="Телефон">
        <p class="sheet__caption">Телефон</p>
        <? if ($phone !== ''): ?>
        <a class="sheet__phone" href="<?= htmlspecialcharsbx($phoneTel) ?>"><?= htmlspecialcharsbx($phone) ?></a>
        <? endif ?>
        <p class="sheet__note">Отдел продаж</p>
        <button class="sheet__cta js-callback" type="button">
            Заказать обратный звонок
        </button>
    </div>

    <? // Невидимый дублёр кнопки заявки: по нему «кликает» JS, чтобы переиспользовать
       // готовый обработчик .js-request-form вместо копии его логики (см. request-form.php). ?>
    <button class="js-request-form" id="callbackProxy" type="button"
            data-form-title="Заказать обратный звонок" hidden aria-hidden="true" tabindex="-1"></button>

    <script>
    (function () {
        /* Открытие шторки: тот же приём, что у формы заявки — Fancybox.show по
           делегированному клику, у каждой шторки свой mainClass под стили. */
        function openSheet(id, cls) {
            if (!window.Fancybox) return;
            Fancybox.show([{ src: id, type: 'inline' }], { mainClass: cls, Thumbs: false });
        }

        document.addEventListener('click', function (e) {
            if (e.target.closest('.js-write-sheet')) {
                e.preventDefault();
                openSheet('#write-sheet', 'fancybox-sheet fancybox-sheet--write');
                return;
            }
            if (e.target.closest('.js-phone-sheet')) {
                e.preventDefault();
                openSheet('#phone-sheet', 'fancybox-sheet fancybox-sheet--phone');
            }
        });

        /* «Заказать обратный звонок» — не своя форма, а общая модалка заявки.
           Двух окон Fancybox одновременно быть не должно: сначала закрываем
           шторку и ЖДЁМ конца её анимации, иначе закрытие первого окна гасит
           подложку уже открывшегося второго и форма остаётся без затемнения. */
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-callback');
            if (!btn) return;
            e.preventDefault();
            if (!window.Fancybox) return;

            var proxy = document.getElementById('callbackProxy');
            if (!proxy) return;

            var instance = Fancybox.getInstance();
            if (instance) {
                instance.close();
                setTimeout(function () { proxy.click(); }, 260);
            } else {
                proxy.click();
            }
        });
    })();
    </script>
    <?
}
