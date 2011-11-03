<?php
/**
 * Стратегия за подреждане на склада 'ArrangeStrategyBottom'
 */
class store_ArrangeStrategyBottom  
{
 
    /**
     * Какви интерфeйси поддържа този мениджър
     */
    var $interfaces = 'store_ArrangeStrategyIntf';
        
    function getAutoPalletPlace($palletId) {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
		$palletPlaceAuto = "6-A-1";
    	
    	return $palletPlaceAuto;
    }
    
}