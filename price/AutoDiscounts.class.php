<?php


/**
 * Автоматични отстъпки към продажби
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Автоматични отстъпки към продажби
 */
class price_AutoDiscounts extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Автоматични отстъпки към продажби';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Автоматична отстъпки към продажби';
    
    
    /**
     * Плъгини за зареждане
     */
    //public $loadList = 'plg_Created, price_Wrapper, plg_RowTools2';
    
    
    /**
     * Интерфейс за ценова политика
     */
    public $interfaces = 'price_SaleAutoDiscountIntf';
    
    
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
        //@todo mockup реализация
        $totalWithoutDiscount = $masterRec->amountDeal + $masterRec->amountDiscount;
        $totalWithoutDiscount = round($totalWithoutDiscount / $masterRec->currencyRate, 6);
        if($totalWithoutDiscount < 1000){
            
            return null;
        }
        
        $in =  floor($totalWithoutDiscount / 1000);
        $discount = round($in * 0.01, 2);
        $discount = min($discount, 0.9);
        
        if(haveRole('debug')){
            core_Statuses::newStatus("{$totalWithoutDiscount}-{$masterRec->amountDeal}-{$masterRec->amountVat}-{$masterRec->amountDiscount}", 'error');
        }
        
        return $discount;
    }
}