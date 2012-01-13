<?php

/**
 * Стратегия за подреждане на склада 'ArrangeStrategyBottom'
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ArrangeStrategyBottom
{
    
    
    
    /**
     * Какви интерфeйси поддържа този мениджър
     */
    var $interfaces = 'store_ArrangeStrategyIntf';
    
    function getAutoPalletPlace($productId) {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        $palletPlaceAuto = "6-A-1";
        
        return $palletPlaceAuto;
    }
}