<?php

/**
 * Клас 'sales_PaymentIntf' - Интерфейс за извличане на данни за плащане
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_PaymentIntf
{
    /**
     * Информация за платежен документ
     * 
     * @param int|stdClass $id ключ (int) или запис (stdClass) на модел 
     * @return stdClass Обект със следните полета:
     *
     *   o amount       - обща сума на платежния документ във валутата `currencyCode`
     *   o currencyCode - key(mvc=currency_Currencies, key=code): ISO код на валутата
     *   o currencyRate - double - валутен курс към основната (към датата на док.) валута  
     *   o valior       - date - вальор на документа
     */
    public function getPaymentInfo($id)
    {
        return $this->class->getAmount($id);
    }
}