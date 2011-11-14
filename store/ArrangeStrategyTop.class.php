<?php
/**
 * Стратегия за подреждане на склада 'ArrangeStrategyTop'
 */
class store_ArrangeStrategyTop  
{
 
    /**
     * Какви интерфeйси поддържа този мениджър
     */
    var $interfaces = 'store_ArrangeStrategyIntf';
        
    function getAutoPalletPlace($productId) {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
		$palletPlaceAuto = "6-G-22";
    	
    	return $palletPlaceAuto;
    }
    
}