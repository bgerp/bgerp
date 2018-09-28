<?php


/**
 * Интерфейс за инициатори на плащане по ePay
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
 * @title     Интерфейс за инициатори на плащане по ePay
 */
class eshop_InitiatorPaymentIntf
{
    

    /**
     * Клас имплементиращ мениджъра
     */
    public $class;
    
    
    /**
     * Приемане на плащане към инциаторът
     * 
     * @param int $objId
     * @param string $reason
     * @param string|null $payer
     * @param double|null $amount
     * @param string $currencyCode
     * 
     * @return void
     */
    public function receivePayment($objId, $reason, $payer, $amount, $currencyCode)
    {
        return $this->class->receivePayment($objId, $reason, $payer, $amount, $currencyCode);
    }
}