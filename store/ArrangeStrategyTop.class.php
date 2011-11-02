<?php
/**
 * 
 * Движения
 */
class store_ArrangeStrategyTop  
{
 
    /**
     * Какви интерфайси поддържа този мениджър
     */
    var $interfaces = 'store_ArrangeStrategyIntf';
        
    public static function getAutoPalletPlace($palletId) {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        $strategyId = store_Stores::fetchField("#id = {$selectedStoreId}", 'strategyName');
        $strategyName = store_ArrangeStrategy::fetchField("#id = {$strategyId}", 'strategyName');
    	
    	switch ($strategyName) {
    		case "Random":
    			$palletPlaceAuto = "6-A-1";
    			break;
    		case "Top":
                $palletPlaceAuto = "6-G-22";
                break;    				
    	}
    	
    	return $palletPlaceAuto;
    }
    
}