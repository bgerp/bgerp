<?php


/**
 * Тестов шлюз за плащане с карта - само в дебъг режим, за изпробване на Н-18 без истински виртуален ПОС
 *
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 * @title     Тестово плащане с карта
 *
 * @since     v 0.1
 */
class bgfisc_driver_TestCardPayment extends cond_ProtoCardPaymentDriver
{
    /**
     * Заглавие
     */
    public $title = 'Тестово плащане с карта';


    /**
     * Полетата на заявката и отговора, които се подписват
     */
    const REQUEST_FIELDS = 'ORDER,NONCE,AMOUNT,CURRENCY,TERMINAL,MERCHANT,BACKREF';
    const RESPONSE_FIELDS = 'ORDER,NONCE,RESULT,AMOUNT,CURRENCY,TERMINAL,RRN,APPROVAL,CARD,CARDHOLDERINFO';


    /**
     * Може ли драйверът да се избира
     *
     * @param int|null $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return isDebug();
    }


    /**
     * Заявката към шлюза за опита за плащане
     *
     * @param stdClass $rec
     * @param stdClass $methodRec
     *
     * @return array
     */
    public function getGatewayRequest($rec, $methodRec)
    {
        $fields = array('ORDER' => $rec->orderNum, 'NONCE' => $rec->nonce, 'AMOUNT' => number_format($rec->amount, 2, '.', ''), 'CURRENCY' => $rec->currencyCode,
            'TERMINAL' => $methodRec->terminalId ?? '', 'MERCHANT' => $methodRec->merchantId ?? '', 'BACKREF' => $this->getResponseUrl($rec->paymentId));
        $fields['P_SIGN'] = self::sign($fields, self::REQUEST_FIELDS);

        // Номерът на поръчката е в защитения адрес - като сесия на истински шлюз
        Request::setProtected('ORDER');
        $url = toUrl(array($this, 'gateway', 'ORDER' => $fields['ORDER']), 'absolute');
        Request::removeProtected('ORDER');
        unset($fields['ORDER']);

        return array('url' => $url, 'fields' => $fields);
    }


    /**
     * Проверява и нормализира отговора на тестовия шлюз
     *
     * @param stdClass $methodRec
     *
     * @return array|null
     */
    public function parseResponse($methodRec, $data)
    {
        expect(isDebug());
        $raw = array();
        foreach (arr::make(self::RESPONSE_FIELDS) as $field) {
            $raw[$field] = $data[$field] ?? null;
        }

        if (($data['P_SIGN'] ?? null) !== self::sign($raw, self::RESPONSE_FIELDS)) {

            return null;
        }

        $statuses = array('declined' => 'declined', 'pending' => 'pending');
        $status = $statuses[$raw['RESULT']] ?? 'approved';

        return array('status' => $status, 'gatewayStatus' => $raw['RESULT'], 'orderNum' => $raw['ORDER'], 'nonce' => $raw['NONCE'],
            'transactionRef' => $raw['RRN'], 'approvalCode' => $raw['APPROVAL'], 'cardNumber' => $raw['CARD'], 'cardholderInfo' => $raw['CARDHOLDERINFO'],
            'posId' => $raw['TERMINAL'], 'amount' => (float) $raw['AMOUNT'], 'currencyCode' => $raw['CURRENCY'],
            'message' => ($status == 'approved') ? null : 'Отговор на тестовия шлюз: ' . $raw['RESULT'], 'raw' => $raw);
    }


    /**
     * Полетата, които тестовият шлюз връща към BACKREF
     *
     * @return array
     */
    public function getResponseFields()
    {
        return array_merge(arr::make(self::RESPONSE_FIELDS), array('P_SIGN'));
    }


    /**
     * При сценария с неуспешно отразяване първият опит пада, а повторният отговор го довършва
     *
     * @param stdClass $rec
     */
    public function beforeNotifyInitiator($rec)
    {
        if (($rec->response['RESULT'] ?? null) == 'failNotify' && ($rec->notifyTries ?? 0) <= 1) {
            expect(false, 'Тестова грешка при отразяване на платеното');
        }
    }


    /**
     * Страницата на тестовия шлюз - купувачът избира как да завърши плащането
     */
    public function act_Gateway()
    {
        expect(isDebug());
        Request::setProtected('ORDER');
        $req = array();
        foreach (arr::make(self::REQUEST_FIELDS) as $field) {
            $req[$field] = Request::get($field, 'varchar');
        }

        // Иначе скритите полета на отговора също се защитават и ORDER не стига до шлюза
        Request::removeProtected('ORDER');
        expect(Request::get('P_SIGN', 'varchar') === self::sign($req, self::REQUEST_FIELDS), 'Невалиден подпис на заявката');

        $tpl = getTplFromFile('bgfisc/tpl/TestCardGateway.shtml');
        $tpl->placeArray($req);

        // Изходите, които трябва да се изпробват при Н-18
        $buttons = array('approved' => array('PAY', tr('Плати') . " {$req['AMOUNT']} {$req['CURRENCY']}", 'tcg-pay'),
            'noRef' => array('SCENARIOS', tr('Плати без референция'), ''),
            'failNotify' => array('SCENARIOS', tr('Плати с грешка при отразяване'), ''),
            'wrongAmount' => array('SCENARIOS', tr('Плати друга сума'), ''),
            'pending' => array('SCENARIOS', tr('Отговор в обработка'), ''),
            'declined' => array('DECLINE', tr('Откажи'), 'tcg-decline'));
        foreach ($buttons as $result => $btn) {
            list($place, $caption, $class) = $btn;
            $amount = ($result == 'wrongAmount') ? number_format($req['AMOUNT'] + 1, 2, '.', '') : $req['AMOUNT'];
            $isPaid = !in_array($result, array('declined', 'pending'));
            $res = array('ORDER' => $req['ORDER'], 'NONCE' => $req['NONCE'], 'RESULT' => $result, 'AMOUNT' => $amount, 'CURRENCY' => $req['CURRENCY'],
                'TERMINAL' => $req['TERMINAL'],
                'RRN' => ($isPaid && $result != 'noRef') ? 'T' . date('ymdHis') . $req['ORDER'] : '',
                'APPROVAL' => $isPaid ? strtoupper(substr(md5($req['ORDER'] . microtime()), 0, 6)) : '',
                'CARD' => '434179XXXXXX0044',
                'CARDHOLDERINFO' => ($result == 'declined') ? 'Недостатъчна наличност (тест)' : '');
            $res['P_SIGN'] = self::sign($res, self::RESPONSE_FIELDS);
            $button = ht::createElement('button', array('type' => 'submit', 'class' => trim("tcg-btn {$class}")), $caption, true);
            $tpl->append(ht::createElement('form', array('method' => 'POST', 'action' => $req['BACKREF']), ht::createHidden($res) . $button, true), $place);
        }

        $tpl->push('bgfisc/tpl/TestCardGateway.css', 'CSS');
        Mode::set('wrapper', 'page_Empty');

        return $tpl;
    }


    /**
     * Подпис на полетата с ключа на инсталацията
     *
     * @param array  $data
     * @param string $fields
     *
     * @return string
     */
    private static function sign($data, $fields)
    {
        $str = '';
        foreach (arr::make($fields) as $field) {
            $str .= ($data[$field] ?? '') . '|';
        }

        return md5(EF_SALT . 'bgfiscTestCard' . $str);
    }
}
