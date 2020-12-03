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
     * Изчислява очакваната отстъпка на реда от продажбата
     *
     * @param stdClass $dRec         - ред от детайл на продажба
     * @param stdClass $masterRec    - запис на продажба
     *
     * @return double|null $discount
     */
    public function calcAutoSaleDiscount($dRec, $masterRec)
    {
        return $this->class->calcAutoSaleDiscount($dRec, $masterRec);
    }
}