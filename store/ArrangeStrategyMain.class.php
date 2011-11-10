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
    function getAutoPalletPlace($palletId) 
    {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        // id-то на продукта
        $productId = store_Pallets::fetchField($palletId, 'productId');
        
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
            $racksParamsArr[$recRacks->id] = $recRacks->rows;  
            $racksParamsArr[$recRacks->id]['columns'] = $recRacks->columns;
        }
        /* ENDOF Създава масива $storeRacksMatrix, в който п. ключ са палет местата в целия склад */
        
        // За всеки стелаж
        foreach ($racksParamsArr as $rackId => $v) {
        	// За всеки ред на стелаж
        	for ($r = 1; $r <= $v['rows']; $r++) {
        		// За всяка колона на стелажа
        	    for ($c = 1; $c <= $v['columns']; $c++) {
                    $palletPlace = $rackId . "-" . $rackRowsArrRev[$r] . "-" . $c;
                    $storeRacksMatrix[$palletPlace]['rating'] = 0;
                    
                    $storeRacksMatrix[$palletPlace]['isSuitable'] = store_Racks::isSuitable($rackId, $palletId, $palletPlace);
                    
                    if (store_Racks::isSuitable($rackId, $palletId, $palletPlace) === FALSE ) {
                        $storeRacksMatrix[$palletPlace]['rating'] = -1000;	
                    } else {
	                    /* Изчислява рейтинга на палет мястото */
	                    
                        // Ако под инспектираното място има същия продукт +100 т.
                        if ($rackRowsArrRev[$r] != 'A') {
                            $palletPlaceForTest = $rackId . "-" . $rackRowsArrRev[$r -1] . "-" . $c;
                            
                            if ($productIdForTest = store_Pallets::fetchField("#position = '{$palletPlaceForTest}'", 'productId')) {
                                if ($productId == $productIdForTest) {
                                    $storeRacksMatrix[$palletPlace]['rating'] += 100;   	
                                }
                            };
                        };
                        
                        // Ако над инспектираното място има празно място +10 т.
                        if ($rackRowsArrRev[$r] != $racksParamsArr[$v['rows']]) {
                        	for ($vertical == ($r + 1); $vertical <= $racksParamsArr[$v['rows']]; $vertical++) {
                        	    $palletPlaceForTest = $rackId . "-" . $rackRowsArrRev[$vertical] . "-" . $c;

                                if (store_Racks::isSuitable($rackId, $palletId, $palletPlaceForTest)) {
                                    $storeRacksMatrix[$palletPlace]['rating'] += 10;       
                                };
                        	}
                        }
                        
	                    // Ако в ляво имаме същия продукт +5 т.
	                    if ($c != 1) {
                            $palletPlaceForTest = $rackId . "-" . $rackRowsArrRev[$r] . "-" . ($c - 1);

                            if ($productIdForTest = store_Pallets::fetchField("#position = '{$palletPlaceForTest}'", 'productId')) {
                                if ($productId == $productIdForTest) {
                                    $storeRacksMatrix[$palletPlace]['rating'] += 5;       
                                }
                            };                            
	                    }
	                    /* ENDOF Изчислява рейтинга на палет мястото */                        
                    }
                }        		
        	}
        }
        /* ENDOF Създава масива $storeRacksMatrix, в който п. ключ са палет местата в целия склад */
        
        $maxRatingArr = array();
        
        foreach($storeRacksMatrix as $k => $v) {
            if ($v['rating'] > $maxRatingArr['rating']) {
                $maxRatingArr = array();
                
                $maxRatingArr['rating']      = $v['rating'];
                $maxRatingArr['palletPlace'] = $k;
            }
        }
        
		return $maxRatingArr['palletPlace'];
    }
}