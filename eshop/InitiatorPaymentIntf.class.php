<?php


/**
 * Интерфейс за инициатори на онлайн плащане
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
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
     * @param int $objId           - ид на обекта
     * @param string $reason       - основания за плащане
     * @param string|null $payer   - име на наредителя
     * @param double|null $amount  - сума за нареждане
     * @param string $currencyCode - валута за нареждане
     * @param int|NULL $accountId  - ид на наша сметка, или NULL ако няма
     *
     * @return void
     */
    public function receivePayment($objId, $reason, $payer, $amount, $currencyCode, $accountId = NULL)
    {
        return $this->class->receivePayment($objId, $reason, $payer, $amount, $currencyCode, $accountId);
    }
}