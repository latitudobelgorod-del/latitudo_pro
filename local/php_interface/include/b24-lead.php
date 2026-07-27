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
 * Ответственный за лид по коду филиала (msk/belgorod/vrn/krd/rnd).
 * 0 — не задан: ASSIGNED_BY_ID не передаём, портал назначит по своим правилам.
 * ID проставит заказчик (у каждого поддомена свой менеджер в Б24).
 */
function latitudoB24AssignedId(string $regionCode): int
{
    $map = [
        'msk'      => 0,
        'belgorod' => 0,
        'vrn'      => 0,
        'krd'      => 0,
        'rnd'      => 0,
    ];

    return (int)($map[$regionCode] ?? ($map['msk'] ?? 0));
}

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
 * Сборка полей лида из данных формы. Чистая функция (без $_POST/хоста внутри) —
 * тестируется офлайн. $post — массив полей формы, $regionCode/$city — текущий филиал.
 */
function latitudoB24BuildLeadFields(array $post, string $regionCode, string $city): array
{
    $get = static fn(string $k): string => trim((string)($post[$k] ?? ''));

    $name  = $get('user_name');
    $phone = $get('rf_phone');
    $title = $get('b24_page_title');
    if ($title === '') {
        $title = 'Заявка с сайта Latitudo' . ($city !== '' ? ', ' . $city : '');
    }

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
    if ($assigned = latitudoB24AssignedId($regionCode)) {
        $fields['ASSIGNED_BY_ID'] = $assigned;
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
function latitudoB24CreateLead(array $post): void
{
    try {
        $url = latitudoB24WebhookUrl();
        if ($url === '') {
            return; // интеграция не настроена — молчим
        }

        $regionCode = function_exists('latitudoCurrentRegionCode') ? latitudoCurrentRegionCode() : '';
        $store      = function_exists('latitudoCurrentStore') ? latitudoCurrentStore() : null;
        $city       = $store['CITY'] ?? '';

        $fields = latitudoB24BuildLeadFields($post, $regionCode, (string)$city);

        $client = new HttpClient(['socket_timeout' => 5, 'stream_timeout' => 5]);
        $client->setHeader('Content-Type', 'application/json');
        $client->post($url . 'crm.lead.add', Json::encode(['fields' => $fields]));

        $resp = (string)$client->getResult();
        $data = @json_decode($resp, true);
        if (isset($data['result'])) {
            latitudoB24Log('OK lead=' . $data['result'] . ' region=' . $regionCode);
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
            latitudoB24CreateLead($_POST);
        }
    }
    return true; // письмо отправляется как обычно
}

AddEventHandler('main', 'OnBeforeEventAdd', 'latitudoB24OnBeforeEventAdd');
