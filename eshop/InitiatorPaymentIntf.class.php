<?php


/**
 * Интерфейс за инициатори на онлайн плащане
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за инициатори на онлайн плащане
 */
class eshop_InitiatorPaymentIntf
{
    

    /**
     * Клас имплементиращ мениджъра
     */
    public $class;
    
    
    /**
     * Приемане на плащане към инциаторът (@see eshop_InitiatorPaymentIntf)
     *
     * @param int $objId             - ид на обекта
     * @param string $reason         - основания за плащане
     * @param string|null $payer     - име на наредителя
     * @param double|null $amount    - сума за нареждане
     * @param string $currencyCode   - валута за нареждане
     * @param int|NULL $accountId    - ид на наша сметка, или NULL ако няма
     * @param int $linkedContainerId - свързан контейнер
     * @param array $paymentData     - данни за картовото плащане: transactionRef, posId, merchantId, paidOn, amount, currencyCode, cardNumber
     *
     * @return bool|null - true, ако платеното е отразено изцяло (картовото плащане повтаря при false)
     */
    public function receivePayment($objId, $reason, $payer, $amount, $currencyCode, $accountId = NULL, $linkedContainerId = null, $paymentData = array())
    {
        return $this->class->receivePayment($objId, $reason, $payer, $amount, $currencyCode, $accountId, $linkedContainerId, $paymentData);
    }


    /**
     * Колко трябва да се плати онлайн в момента
     *
     * @param int $objId     - ид на обекта
     * @param int $paymentId - начин на плащане, с който се плаща
     *
     * @return float|null - сумата в основна валута или null, ако обектът не очаква плащане с този метод
     */
    public function getAmountToPay($objId, $paymentId)
    {
        return $this->class->getAmountToPay($objId, $paymentId);
    }
}