<?php
/**
 * Стратегия за подреждане на склада 'ArrangeStrategyMain'
 */
class store_ArrangeStrategyMain  
{
    /**
     * Какви интерфeйси поддържа този мениджър
     */
    var $interfaces = 'store_ArrangeStrategyIntf';

    /**
     * По id на палет връща предложение за неговото място
     * 
     * @param $palletId
     * @return string $palletPlaceAuto
     */
    function getAutoPalletPlace($palletId) {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        // array letter to digit
        $rackRowsArr = array('A' => 1,
                             'B' => 2,
                             'C' => 3,
                             'D' => 4,
                             'E' => 5,
                             'F' => 6,
                             'G' => 7,
                             'H' => 8);
        
        // array digit to letter
        $rackRowsArrRev = array('1' => A,
                                '2' => B,
                                '3' => C,
                                '4' => D,
                                '5' => E,
                                '6' => F,
                                '7' => G,
                                '8' => H);
        
        /* Създава масива $storeRacksMatrix, в който п. ключ са палет местата в целия склад */
        $queryRacks = store_Racks::getQuery();
        $where = "#storeId = {$selectedStoreId}";
        
        while($recRacks = $queryRacks->fetch($where)) {
            $racksParamsArr[$recRacks->id]['rows']    = $recRacks->rows;  
            $racksParamsArr[$recRacks->id]['columns'] = $recRacks->columns;
        }
        
        // За всеки стелаж
        foreach ($racksParamsArr as $rackId => $v) {
        	// За всеки ред на стелаж
        	for ($r = 1; $r <= $v['rows']; $r++) {
        		// За всяка колона на стелажа
        	    for ($c = 1; $c <= $v['columns']; $c++) {
                    $palletPlace = $rackId . "-" . $rackRowsArrRev[$r] . "-" . $c;
                    
                    $storeRacksMatrix[$palletPlace]['isSuitable'] = store_Racks::isSuitable($rackId, $palletId, $palletPlace);
        	    	
                    /* Изчислява рейтинга на палет мястото */
                    
			        // Ако под това място има същия продукт + 100 т.
			        
			        // За всяко свободно място над този палет + 10 т.
			        
			        // Ако в ляво имаме същия продукт + 5 т.
                    
                    $storeRacksMatrix[$palletPlace]['rating'] = 0; 
                    /* ENDOF Изчислява рейтинга на палет мястото */
                }        		
        	}
        }
        
        // bp($storeRacksMatrix);
        
        /* ENDOF Създава масива $storeRacksMatrix, в който п. ключ са палет местата в целия склад */
        
		$palletPlaceAuto = "X-X-X";
    	
    	return $palletPlaceAuto;
    }
}