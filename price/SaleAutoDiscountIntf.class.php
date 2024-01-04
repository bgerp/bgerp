<?php


/**
 * Интерфейс за клас за автоматични отстъпки на продажби
 * 
 * @todo work in progress
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за клас за автоматични отстъпки на продажби
 */
class price_SaleAutoDiscountIntf
{
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;


    /**
     * Изчислява автоматичната отстъпка на ред от детайл
     *
     * @param string|core_Detail $Detail - клас на детайла
     * @param int|stdclass $dRec         - ид или запис на детайла
     * @param string|core_Master $Master - клас на мастъра
     * @param int|stdclass $masterRec    - ид или запис на мастъра
     *
     * @return double|null
     */
    public function calcAutoSaleDiscount($Detail, $dRec, $Master, $masterRec)
    {
        return $this->class->calcAutoSaleDiscount($Detail, $dRec, $Master, $masterRec);
    }
}