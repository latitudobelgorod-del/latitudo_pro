<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Создание ЛИДА в Битрикс24 из «Формы заявки» (bitrix:main.feedback).
 *
 * КАК ЛОВИМ ЗАЯВКУ. Форма при успешной отправке шлёт письмо через событие FEEDBACK_FORM.
 * Вешаемся на main::OnBeforeEventAdd для этого события — оно срабатывает ровно тогда,
 * когда компонент принял заявку (боты с заполненным honeypot сюда не доходят: для них
 * компонент вообще не запускается, см. request-form.php). Возвращаем true, чтобы письмо
 * ушло как обычно — CRM добавляется ПАРАЛЛЕЛЬНО почте, а не вместо неё.
 *
 * ДАННЫЕ. Структурные поля (телефон, UTM, страница) фронт кладёт в скрытые input
 * b24_* и они приходят в $_POST — парсить текст письма не нужно.
 *
 * СЕКРЕТ. URL входящего вебхука лежит в файле b24.webhook в корне (право CRM).
 * Он закрыт от HTTP (.htaccess) и от git (.gitignore) — как figma.token. На прод
 * заливается вручную, в репозиторий не попадает. Нет файла → интеграция молча спит,
 * заявки по-прежнему уходят письмом.
 *
 * ОТКАЗОУСТОЙЧИВОСТЬ. Любая ошибка вебхука (сеть, таймаут, ответ Б24) не должна ломать
 * отправку формы и показ «Спасибо» — всё в try/catch, ошибки только в лог.
 */

use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

/**
 * URL вебхука из b24.webhook (корень сайта) с гарантированным слешем на конце.
 * '' — файла нет (интеграция выключена).
 */
function latitudoB24WebhookUrl(): string
{
    static $url = null;
    if ($url !== null) {
        return $url;
    }
    $file = $_SERVER['DOCUMENT_ROOT'] . '/b24.webhook';
    $url = is_readable($file) ? trim((string)file_get_contents($file)) : '';
    if ($url !== '' && substr($url, -1) !== '/') {
        $url .= '/';
    }
    return $url;
}

/**
 * Первый сегмент пути URL — slug лендинга (/pergoly/ → 'pergoly'). '' для главной
 * и путей без сегмента. Чистая функция, тестируется офлайн.
 */
function latitudoSlugFromUrl(string $url): string
{
    $path = (string)parse_url($url, PHP_URL_PATH);
    $path = trim($path, '/');
    if ($path === '') {
        return '';
    }
    $seg = explode('/', $path)[0];

    return preg_match('~^[a-z0-9_-]+$~', $seg) ? $seg : '';
}

/**
 * Заголовок заявки: значение поля раздела UF_HEAD_ZAYAVKA + домен региона в скобках.
 * Идёт и в тему письма, и в название лида. Раздел определяем по URL страницы отправки.
 * Заявка не с лендинга раздела (главная и т.п.) или поле пустое → «Заявка с сайта (домен)».
 */
function latitudoZayavkaTitle(array $post): string
{
    $url  = trim((string)($post['b24_page_url'] ?? ''));
    $host = $url !== '' ? (string)parse_url($url, PHP_URL_HOST) : '';
    if ($host === '') {
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    }

    $head = '';
    $slug = latitudoSlugFromUrl($url);
    if ($slug !== '' && function_exists('latitudoCatalogSectionBySlug')) {
        $section = latitudoCatalogSectionBySlug($slug);
        $head = $section ? trim((string)($section['UF_HEAD_ZAYAVKA'] ?? '')) : '';
    }
    if ($head === '') {
        $head = 'Заявка с сайта';
    }

    return $head . ($host !== '' ? ' (' . $host . ')' : '');
}

/**
 * Сборка полей лида из данных формы. Чистая функция (без $_POST/хоста внутри) —
 * тестируется офлайн. $post — поля формы, $regionCode — код филиала,
 * $title — готовый заголовок заявки (см. latitudoZayavkaTitle),
 * $assignedId — ответственный (0 = не передавать, портал назначит сам).
 */
function latitudoB24BuildLeadFields(array $post, string $title, int $assignedId): array
{
    $get = static fn(string $k): string => trim((string)($post[$k] ?? ''));

    $name  = $get('user_name');
    $phone = $get('rf_phone');

    // Комментарий: то, для чего в лиде нет отдельных полей.
    $commentLines = [];
    if ($m = $get('rf_messenger')) { $commentLines[] = 'Мессенджер: ' . $m; }
    if ($n = $get('rf_nick'))      { $commentLines[] = 'Ник (Telegram): ' . $n; }
    $commentLines[] = 'Согласие на обработку персональных данных: да';

    $fields = [
        'TITLE'              => $title,
        'NAME'               => $name,
        'SOURCE_ID'          => 'WEB',
        'SOURCE_DESCRIPTION' => $get('b24_form_name'),
        'COMMENTS'           => implode("\n", $commentLines),
        // Адрес страницы и UTM — в пользовательские поля лида (ID из настройки заказчика).
        'UF_CRM_1670914333154' => $get('b24_page_url'),
        'UF_CRM_1674473471'    => $get('b24_utm_source'),
        'UF_CRM_1674473526'    => $get('b24_utm_medium'),
        'UF_CRM_1674473492'    => $get('b24_utm_campaign'),
        'UF_CRM_1674473509'    => $get('b24_utm_content'),
        'UF_CRM_1674473502'    => $get('b24_utm_term'),
        'UF_CRM_UTMGEO'        => $get('b24_utm_geo'),
    ];

    if ($phone !== '') {
        $fields['PHONE'] = [['VALUE' => $phone, 'VALUE_TYPE' => 'WORK']];
    }
    $email = $get('user_email');
    if ($email !== '') {
        $fields['EMAIL'] = [['VALUE' => $email, 'VALUE_TYPE' => 'WORK']];
    }
    if ($assignedId > 0) {
        $fields['ASSIGNED_BY_ID'] = $assignedId;
    }

    return $fields;
}

/** Запись в лог интеграции (local/logs/b24-lead.log). Тихо, без падений. */
function latitudoB24Log(string $msg): void
{
    $dir = $_SERVER['DOCUMENT_ROOT'] . '/local/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($dir . '/b24-lead.log', date('c') . ' ' . $msg . "\n", FILE_APPEND);
}

/**
 * Создать лид в Б24. Никогда не бросает наружу: ошибки только в лог.
 */
function latitudoB24CreateLead(array $post, string $title): void
{
    try {
        $url = latitudoB24WebhookUrl();
        if ($url === '') {
            return; // интеграция не настроена — молчим
        }

        // Ответственный — из поля REGION_USER текущего филиала (инфоблок «Магазины»).
        $store      = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
        $assignedId = (int)($store['RESPONSIBLE_ID'] ?? 0);

        $fields = latitudoB24BuildLeadFields($post, $title, $assignedId);

        $client = new HttpClient(['socket_timeout' => 5, 'stream_timeout' => 5]);
        $client->setHeader('Content-Type', 'application/json');
        $client->post($url . 'crm.lead.add', Json::encode(['fields' => $fields]));

        $resp = (string)$client->getResult();
        $data = @json_decode($resp, true);
        if (isset($data['result'])) {
            latitudoB24Log('OK lead=' . $data['result'] . ' assigned=' . $assignedId);
        } else {
            latitudoB24Log('FAIL http=' . $client->getStatus() . ' resp=' . mb_substr($resp, 0, 500));
        }
    } catch (\Throwable $e) {
        latitudoB24Log('EXCEPTION ' . $e->getMessage());
    }
}

/**
 * Обработчик события отправки письма формы. Один раз на запрос (static-гард):
 * FEEDBACK_FORM в рамках одной заявки шлётся один раз, но перестрахуемся.
 */
function latitudoB24OnBeforeEventAdd($event, $lid, &$arFields)
{
    if ($event === 'FEEDBACK_FORM') {
        static $done = false;
        if (!$done) {
            $done = true;
            // Заголовок заявки: UF_HEAD_ZAYAVKA раздела + домен. Идёт и в тему письма
            // (макрос #ZAYAVKA_SUBJECT# в шаблоне FEEDBACK_FORM), и в название лида.
            $title = latitudoZayavkaTitle($_POST);
            $arFields['ZAYAVKA_SUBJECT'] = $title;
            latitudoB24CreateLead($_POST, $title);
        }
    }
    return true; // письмо отправляется как обычно
}

AddEventHandler('main', 'OnBeforeEventAdd', 'latitudoB24OnBeforeEventAdd');
