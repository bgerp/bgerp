<?php


/**
 * Базов драйвер за плащане с карта през онлайн шлюз (виртуален ПОС)
 *
 * Наследникът описва само обмена с конкретния шлюз - пренасочване с подписана форма (БОРИКА),
 * сесия през API на шлюза (Stripe, PayPal) или вградена форма. Опитите, повторните известия,
 * проверката на висящите и предаването на платеното към поръчката са в cond_CardPayments.
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class cond_ProtoCardPaymentDriver extends core_BaseClass
{
    /**
     * Поддържани интерфейси
     *
     * var string|array
     */
    public $interfaces = 'cond_OnlinePaymentIntf';


    /**
     * Код по Н-18 по подразбиране: 2 - виртуален ПОС на банка, 4 - доставчик на платежни услуги (Stripe, PayPal)
     */
    public $defaultN18PaymentCode = 2;


    /**
     * Заявката към шлюза за опита за плащане
     *
     * @param stdClass $rec       - опитът (cond_CardPayments)
     * @param stdClass $methodRec - начинът на плащане с полетата на драйвера
     *
     * @return array - [url] адрес на шлюза, [fields] полета на формата, [method] POST (по подразбиране) или GET - пренасочване;
     *               или [html] вградена форма на шлюза на нашата страница;
     *               [gatewayRef] - ид на сесията, създадена през API на шлюза, по което после идва отговорът
     */
    abstract public function getGatewayRequest($rec, $methodRec);


    /**
     * Проверява подписа и нормализира отговора - еднакво за връщането, известието и проверката на статус
     *
     * @param stdClass $methodRec - начинът на плащане с полетата на драйвера
     * @param array    $data      - получените полета
     *
     * @return array|null - null при невалиден подпис, иначе:
     *                    [status] approved - успешно; declined - окончателен отказ;
     *                             pending - междинен статус или техническа грешка, след която може да дойде друг отговор
     *                    за съпоставяне с изпратеното - едно от: [id] опитът, само ако е потвърден от шлюза;
     *                             [gatewayRef] сесията от getGatewayRequest; [orderNum] и [nonce];
     *                    [operation] (payment/refund), [gatewayStatus] кодът от шлюза, [message],
     *                    [cardholderInfo] - за показване на картодържателя,
     *                    [transactionRef], [approvalCode], [cardNumber] маскиран, [posId] терминалът от подписания отговор,
     *                    [amount] в единици на валутата (не в стотинки), [currencyCode] ISO с главни букви, [paidOn], [raw] - целият отговор
     *                    При одобрено плащане сумата и валутата са задължителни, а терминалът - ако е в настройките.
     */
    abstract public function parseResponse($methodRec, $data);


    /**
     * Полетата, които шлюзът връща с купувача (BACKREF)
     *
     * @return array
     */
    public function getResponseFields()
    {
        return array();
    }


    /**
     * Отговорът при връщането на купувача от шлюза
     *
     * Шлюзовете с API (Stripe, PayPal) връщат само ид на сесията - тогава тук се взема резултатът
     * от шлюза (или се финализира плащането) и се подава на parseResponse().
     *
     * @param stdClass      $methodRec - начинът на плащане
     * @param stdClass|null $rec       - опитът от защитения адрес за връщане (@see getResponseUrl), ако е в него
     *
     * @return array|null - нормализиран отговор или null при невалиден
     */
    public function readResponse($methodRec, $rec = null)
    {
        $data = array();
        foreach ($this->getResponseFields() as $field) {
            $data[$field] = Request::get($field, 'varchar');
        }

        return $this->parseResponse($methodRec, $data);
    }


    /**
     * Известие от сървъра на шлюза (webhook) - чете суровото тяло и заглавките и проверява подписа им
     *
     * @param stdClass $methodRec - начинът на плащане
     *
     * @return array|null - нормализиран отговор, false - събитие, което не засяга плащането, или null при невалидно
     */
    public function readNotification($methodRec)
    {
        return null;
    }


    /**
     * Полетата от проверката на статус направо в шлюза (TRTYPE=90 при БОРИКА, сесията при Stripe и PayPal)
     *
     * @param stdClass $rec       - опитът (cond_CardPayments)
     * @param stdClass $methodRec - начинът на плащане
     *
     * @return array|null - данните за parseResponse() или null, ако шлюзът не поддържа проверка
     */
    public function checkStatus($rec, $methodRec)
    {
        return null;
    }


    /**
     * Номерът на опита в шлюза - шлюзовете с ограничение на дължината (БОРИКА - 6 цифри) го предефинират
     *
     * @param stdClass $rec - опитът (cond_CardPayments)
     *
     * @return string
     */
    public function getOrderNum($rec)
    {
        return (string) $rec->id;
    }


    /**
     * Еднократна стойност за заявката - шлюзът я връща в отговора
     *
     * @param stdClass $rec - опитът (cond_CardPayments)
     *
     * @return string
     */
    public function getNonce($rec)
    {
        return strtoupper(bin2hex(random_bytes(16)));
    }


    /**
     * Сумата и валутата, с които се плаща в шлюза - валутата на банковата сметка
     *
     * @param stdClass $methodRec  - начинът на плащане с полетата на драйвера
     * @param float    $baseAmount - сума в основна валута
     *
     * @return array - [0] сума, [1] код на валутата
     */
    public function getGatewayAmount($methodRec, $baseAmount)
    {
        $accountId = $methodRec->ownAccountId ?? null;
        $currencyCode = !empty($accountId) ? bank_OwnAccounts::getOwnAccountInfo($accountId)->currencyCode : acc_Periods::getBaseCurrencyCode();

        return array(round(currency_CurrencyRates::convertAmount($baseAmount, null, null, $currencyCode), 2), $currencyCode);
    }


    /**
     * Преди предаване на платеното към поръчката - изключение го отлага за повторен опит
     *
     * @param stdClass $rec - опитът (cond_CardPayments)
     */
    public function beforeNotifyInitiator($rec)
    {
    }


    /**
     * Адрес за връщането на купувача от шлюза (BACKREF, success_url, return_url)
     *
     * @param int      $paymentId
     * @param int|null $id        - опитът; без него, ако адресът е общ за метода (БОРИКА го иска фиксиран)
     *
     * @return string
     */
    public function getResponseUrl($paymentId, $id = null)
    {
        // Методът и опитът не се виждат в адреса и не могат да се подменят
        Request::setProtected('pm,cp');
        $url = toUrl(array('cond_CardPayments', 'response', 'pm' => $paymentId, 'cp' => $id), 'absolute');
        Request::removeProtected('pm,cp');

        return $url;
    }


    /**
     * Адрес за известията от сървъра на шлюза (webhook) - въвежда се в профила на търговеца
     *
     * @param int $paymentId
     *
     * @return string
     */
    public function getNotifyUrl($paymentId)
    {
        Request::setProtected('pm');
        $url = toUrl(array('cond_CardPayments', 'notify', 'pm' => $paymentId), 'absolute');
        Request::removeProtected('pm');

        return $url;
    }


    /**
     * Настройките на метода, които се запазват в опита при изпращането - по тях се проверява отговорът
     *
     * @param stdClass $methodRec
     *
     * @return array - [ownAccountId], [merchantId], [terminalId] и други на драйвера
     */
    public function getConfig($methodRec)
    {
        return array('ownAccountId' => $methodRec->ownAccountId ?? null, 'terminalId' => $methodRec->terminalId ?? null, 'merchantId' => $methodRec->merchantId ?? null);
    }


    /**
     * Добавя полетата на драйвера към Fieldset - общите за всички шлюзове, ключовете и терминалите са в наследника
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('ownAccountId', 'key(mvc=bank_OwnAccounts,select=bankAccountId,allowEmpty)', 'caption=Плащане с карта->Банкова сметка,mandatory,after=onlinePaymentText');
        $fieldset->FLD('n18PaymentCode', "enum(2=Виртуален ПОС,4=Доставчик на платежни услуги)", "caption=Плащане с карта->Вид по Н-18,notNull,value={$this->defaultN18PaymentCode},after=ownAccountId");
    }


    /**
     * Генериране на бутон за онлайн плащане
     *
     * @param int $paymentId         - начин на плащане
     * @param double $amount         - сума на плащане в основна валута
     * @param string $currency       - валута на плащане
     * @param string $okUrl          - урл при потвърждение
     * @param string $cancelUrl      - урл при отказ
     * @param mixed $initiatorClass  - класа инициатор
     * @param int $initiatorId       - ид на инициатор
     * @param array $soldItems
     *
     * @return string $button        - бутон за онлайн плащане
     */
    public function getPaymentBtn($paymentId, $amount, $currency, $okUrl, $cancelUrl, $initiatorClass, $initiatorId, $soldItems = array())
    {
        // Сумата се взема от поръчката при натискането - стар бутон не плаща стара сума
        $url = cond_CardPayments::getPayUrl($paymentId, $okUrl, $cancelUrl, $initiatorClass, $initiatorId);

        return ht::createBtn('Плащане с карта', $url, null, null, 'title=Плащане с банкова карта,class=order-btn eshop-btn,rel=nofollow');
    }


    /**
     * Задължително ли е онлайн плащането или е опционално
     *
     * @param int $paymentId
     * @param mixed $initiatorClass
     * @param int $initiatorId
     * @return boolean
     */
    public function isPaymentMandatory($paymentId, $initiatorClass, $initiatorId)
    {
        return true;
    }


    /**
     * Добавя за уведомителния имейл
     *
     * @param int $paymentId
     * @param stdClass $cartRec
     *
     * @return string|null
     */
    public function getText4Email($paymentId, $cartRec)
    {
        return null;
    }


    /**
     * Информативния текст за онлайн плащането
     *
     * @param mixed $rec
     * @return string|null
     */
    public function getDisplayHtml($rec)
    {
        return tr('Моля, завършете поръчката, като платите от бутона') . ' <b>"' . tr('Плащане с карта') . '"</b>';
    }


    /**
     * Хтмл за показване след финализиране на плащането
     *
     * @param int $id
     * @param stdClass $cartRec
     * @return core_ET|null $tpl
     */
    public function displayHtmlAfterPayment($id, $cartRec)
    {
        return null;
    }


    /**
     * Връща типа на метода на плащане
     *
     * @param stdClass $rec
     * @return string
     */
    public function getPaymentType($rec)
    {
        return 'card';
    }


    /**
     * Код на картовото плащане по Наредба Н-18 (поле PAYM)
     *
     * @param int $paymentId
     *
     * @return int
     */
    public function getN18PaymentCode($paymentId)
    {
        $code = cond_PaymentMethods::fetch($paymentId)->n18PaymentCode ?? null;

        return !empty($code) ? (int) $code : (int) $this->defaultN18PaymentCode;
    }
}
