<?php


/**
 * Базов драйвер за плащане с карта през онлайн шлюз (виртуален ПОС)
 *
 * Наследникът описва само обмена с конкретния шлюз - заявката, отговора и номера на поръчката.
 * Опитите, повторните известия и предаването на платеното към поръчката са в cond_CardPayments.
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
     * Заявката към шлюза за опита за плащане
     *
     * @param stdClass $rec       - опитът (cond_CardPayments)
     * @param stdClass $methodRec - начинът на плащане с полетата на драйвера
     *
     * @return array - [url] адрес на шлюза, [fields] полета на формата
     */
    abstract public function getGatewayRequest($rec, $methodRec);


    /**
     * Полетата, които шлюзът връща към BACKREF
     *
     * @return array
     */
    abstract public function getResponseFields();


    /**
     * Проверява подписа и нормализира отговора - еднакво за BACKREF и за проверката на статус
     *
     * @param stdClass $methodRec - начинът на плащане с полетата на драйвера
     * @param array    $data      - получените полета
     *
     * @return array|null - null при невалиден подпис, иначе:
     *                    [status] approved - успешно; declined - окончателен отказ;
     *                             pending - междинен статус или техническа грешка, след която може да дойде друг отговор
     *                    [orderNum], [nonce] и [operation] (payment/refund) - за съпоставяне с изпратеното,
     *                    [gatewayStatus] кодът от шлюза, [message], [cardholderInfo] - за показване на картодържателя,
     *                    [transactionRef], [approvalCode], [cardNumber] маскиран, [posId] терминалът от подписания отговор,
     *                    [amount], [currencyCode], [paidOn], [raw] - целият отговор
     *                    При одобрено плащане сумата, валутата и терминалът са задължителни.
     */
    abstract public function parseResponse($methodRec, $data);


    /**
     * Полетата от проверката на статус направо в шлюза (напр. TRTYPE=90 при БОРИКА)
     *
     * @param stdClass $rec       - опитът (cond_CardPayments)
     * @param stdClass $methodRec - начинът на плащане
     *
     * @return array|null - null, ако шлюзът не поддържа проверка
     */
    public function checkStatus($rec, $methodRec)
    {
        return null;
    }


    /**
     * Номерът на опита в шлюза - 6 цифри, уникален за терминала в рамките на 24 часа
     *
     * @param stdClass $rec - опитът (cond_CardPayments)
     *
     * @return string
     */
    public function getOrderNum($rec)
    {
        return str_pad($rec->id % 1000000, 6, '0', STR_PAD_LEFT);
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
     * Адрес за отговора на шлюза (BACKREF)
     *
     * @param int $paymentId
     *
     * @return string
     */
    public function getResponseUrl($paymentId)
    {
        // Методът не се вижда в адреса и не може да се подмени
        Request::setProtected('pm');
        $url = toUrl(array('cond_CardPayments', 'response', 'pm' => $paymentId), 'absolute');
        Request::removeProtected('pm');

        return $url;
    }


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('ownAccountId', 'key(mvc=bank_OwnAccounts,select=bankAccountId,allowEmpty)', 'caption=Виртуален ПОС->Банкова сметка,mandatory,after=onlinePaymentText');
        $fieldset->FLD('merchantId', 'varchar(32)', 'caption=Виртуален ПОС->Merchant ID,mandatory,after=ownAccountId');
        $fieldset->FLD('terminalId', 'varchar(32)', 'caption=Виртуален ПОС->Терминал (TID),mandatory,after=merchantId');
        $fieldset->FLD('n18PaymentCode', 'enum(2=Виртуален ПОС,4=Доставчик на платежни услуги)', 'caption=Виртуален ПОС->Вид по Н-18,notNull,value=2,after=terminalId');
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

        return !empty($code) ? (int) $code : 2;
    }
}
