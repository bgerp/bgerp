<?php
/**
 * Стратегия за подреждане на склада 'ArrangeStrategyMain'
 * 
 * Започва се от 0 т.
 * Ако store_Racks::isSuitable() върне FALSE това палет място не може да се използва -1000 т.
 * Ако под инспектираното палет място има палет (или наредено движение) със същия продукт +100 т.  
 * Ако в ляво от инспектираното палет място има палет (или наредено движение) със същия продукт + 5 т.
 * За всяко свободно място над инспектираното се добавят +10 т.
 */
class store_ArrangeStrategyMain  
{
    /**
     * Какви интерфeйси поддържа този мениджър
     */
    var $interfaces = 'store_ArrangeStrategyIntf';

    /**
     * По productId за палет връща предложение за неговото място
     * 
     * @param int $productId
     * @return string $maxRatingArr['palletPlace']
     */
    function getAutoPalletPlace($productId) 
    {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        $palletsInStoreArr = store_Pallets::getPalletsInStore(); 
        
        // array letter to digit
        $rackRowsArr = array('A' => 1, 'B' => 2,
                             'C' => 3, 'D' => 4,
                             'E' => 5, 'F' => 6,
                             'G' => 7, 'H' => 8);
        
        // array digit to letter
        $rackRowsArrRev = array(1 => 'A', 2 => 'B',
                                3 => 'C', 4 => 'D',
                                5 => 'E', 6 => 'F',
                                7 => 'G', 8 => 'H');
        
        /* $rackParamsArr носи информация за броя на редовете и колоните за всеки стелаж */ 
        $queryRacks = store_Racks::getQuery();
        $where = "#storeId = {$selectedStoreId}";
        
        while($recRacks = $queryRacks->fetch($where)) {
            $racksParamsArr[$recRacks->id]['rows']    = $recRacks->rows;  
            $racksParamsArr[$recRacks->id]['columns'] = $recRacks->columns;
        }
        /* ENDOF $rackParamsArr носи информация за броя на редовете и колоните за всеки стелаж */
        
        /* Създава масива $storeRacksMatrix с рейтинг (оценка) за всяко палет място от склада */
        
        // За всеки стелаж
        foreach ($racksParamsArr as $rackId => $v) {
        	// За всеки ред на стелаж
        	for ($r = 1; $r <= $v['rows']; $r++) {
        		// За всяка колона на стелажа
        	    for ($c = 1; $c <= $v['columns']; $c++) {
                    $palletPlace = $rackId . "-" . $rackRowsArrRev[$r] . "-" . $c;
                    
                    // Старт rating
                    $storeRacksMatrix[$palletPlace]['rating'] = 0;
                    
                    // Проверява isSuitable() за палет мястото
                    $isSuitableResult = store_Racks::isSuitable($rackId, $productId, $palletPlace); 
                    
                    if ($isSuitableResult[0] === FALSE ) {
                    	// На това палет място не може да се сложи новия палет
                        $storeRacksMatrix[$palletPlace]['rating'] = -1000;
                    } else {
                    	// На това палет място може да се сложи новия палет
                    	
	                    /* Изчислява рейтинга на палет мястото */
	                    
                        // Ако под инспектираното място има палет (или има наредено движение) със същия продукт +100 т.
                        if ($r != 1) {
                        	if (isset($palletsInStoreArr[$rackId][$rackRowsArrRev[$r -1]][$c])) {
                        		if ($palletsInStoreArr[$rackId][$rackRowsArrRev[$r -1]][$c]['productId'] == $productId) {
                        		    $storeRacksMatrix[$palletPlace]['rating'] += 100;
                        		}
                        	}
                        }
                        
                        // Ако над инспектираното място има празно място +10 т. (isSuitable() анализира и наредените движения)
                        if ($rackRowsArrRev[$r] != $racksParamsArr[$v['rows']]) {
                        	for ($vertical = ($r + 1); $vertical <= $v['rows']; $vertical++) {
                        	    $palletPlaceForTest = $rackId . "-" . $rackRowsArrRev[$vertical] . "-" . $c;

                        	    $isSuitableResultPalletPlaceForTest = store_Racks::isSuitable($rackId, $productId, $palletPlaceForTest);
                        	    
                                if ($isSuitableResultPalletPlaceForTest[0]) {
                                	$storeRacksMatrix[$palletPlace]['rating'] += 10;
                                }
                        	}
                        }
                        
	                    // Ако в ляво има палет със същия продукт (или има наредено движение) +5 т.
	                    if ($c != 1) {
	                    	if (isset($palletsInStoreArr[$rackId][$rackRowsArrRev[$r]][$c - 1])) {
                                if ($palletsInStoreArr[$rackId][$rackRowsArrRev[$r]][$c - 1]['productId'] == $productId) {
                                    $storeRacksMatrix[$palletPlace]['rating'] += 5;
                                }	                    		
	                    	}
	                    }
	                    /* ENDOF Изчислява рейтинга на палет мястото */
                    }
                }
        	}
        }
        /* ENDOF Създава масива $storeRacksMatrix с рейтинг (оценка) за всяко палет място от склада */
        
        // Вземаме палет мястото с най-голям рейтинг.
        // Ако са няколко места с равен рейтинг, $maxRatingArr държи разположеното най- в ляво 
        $maxRatingArr['rating']      = -1000;
        $maxRatingArr['palletPlace'] = NULL;
        
        foreach($storeRacksMatrix as $k => $v) {
            if ($v['rating'] > $maxRatingArr['rating']) {
                $maxRatingArr['rating']      = $v['rating'];
                $maxRatingArr['palletPlace'] = $k;
            }
        }
        
        /*
        // резултат
        if ($maxRatingArr['rating'] < 0) {
            core_Message::redirect("Всички палет места са заети в склада !", 
                                    'tpl_Error', 
                                    NULL, 
                                    array('store_Products', 'list'));        
        }
        */
        
		return $maxRatingArr['palletPlace'];
    }
}