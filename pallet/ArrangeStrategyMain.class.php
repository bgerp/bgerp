<?php



/**
 * Стратегия за подреждане на склада 'pallet_ArrangeStrategyMain'
 *
 * Започва се от 0 т.
 * Ако pallet_Racks::isSuitable() върне FALSE това палет място не може да се използва -1000 т.
 * Ако под инспектираното палет място има палет (или наредено движение) със същия продукт +100 т.
 * Ако в ляво от инспектираното палет място има палет (или наредено движение) със същия продукт + 5 т.
 * За всяко свободно място над инспектираното се добавят +10 т.
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pallet_ArrangeStrategyMain
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'store_ArrangeStrategyMain';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'pallet_ArrangeStrategyIntf';
    
    
    /**
     * По productId за палет връща предложение за неговото място
     *
     * @param  int    $productId
     * @return string $maxRatingArr['palletPlace']
     */
    public function getAutoPalletPlace($productId)
    {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        $palletsInStoreArr = store_Pallets::getPalletsInStore();
        
        /* $rackParamsArr носи информация за броя на редовете и колоните за всеки стелаж */
        $queryRacks = store_Racks::getQuery();
        $where = "#storeId = {$selectedStoreId}";
        
        $racksParamsArr = array();
        while ($recRacks = $queryRacks->fetch($where)) {
            $racksParamsArr[$recRacks->id]['rows'] = $recRacks->rows;
            $racksParamsArr[$recRacks->id]['columns'] = $recRacks->columns;
        }
        
        /* ENDOF $rackParamsArr носи информация за броя на редовете и колоните за всеки стелаж */
        
        /* Създава масива $storeRacksMatrix с рейтинг (оценка) за всяко палет място от склада */
        
        if (count($racksParamsArr)) {
            
            // За всеки стелаж
            foreach ($racksParamsArr as $rackId => $v) {
                // За всеки ред на стелаж
                for ($r = 1; $r <= $v['rows']; $r++) {
                    // За всяка колона на стелажа
                    for ($c = 1; $c <= $v['columns']; $c++) {
                        $palletPlace = $rackId . '-' . store_Racks::rackRowConv($r) . '-' . $c;
            
                        // Старт rating
                        $storeRacksMatrix[$palletPlace]['rating'] = 0;
            
                        // Проверява isSuitable() за палет мястото
                        $isSuitableResult = store_Racks::isSuitable($rackId, $productId, $palletPlace);
            
                        if ($isSuitableResult[0] === false) {
                            // На това палет място не може да се сложи новия палет
                            $storeRacksMatrix[$palletPlace]['rating'] = -1000;
                        } else {
                            // На това палет място може да се сложи новия палет
            
                            /* Изчислява рейтинга на палет мястото */
            
                            // Ако под инспектираното място има палет (или има наредено движение) със същия продукт +100 т.
                            if ($r != 1) {
                                if (isset($palletsInStoreArr[$rackId][store_Racks::rackRowConv($r - 1)][$c])) {
                                    if ($palletsInStoreArr[$rackId][store_Racks::rackRowConv($r - 1)][$c]['productId'] == $productId) {
                                        $storeRacksMatrix[$palletPlace]['rating'] += 100;
                                    }
                                }
                            }
            
                            // Ако над инспектираното място има празно място +10 т. (isSuitable() анализира и наредените движения)
                            if (store_Racks::rackRowConv($r) != store_Racks::rackRowConv($v['rows'])) {
                                for ($vertical = ($r + 1); $vertical <= $v['rows']; $vertical++) {
                                    $palletPlaceForTest = $rackId . '-' . store_Racks::rackRowConv($vertical) . '-' . $c;
            
                                    $isSuitableResultPalletPlaceForTest = store_Racks::isSuitable($rackId, $productId, $palletPlaceForTest);
            
                                    if ($isSuitableResultPalletPlaceForTest[0]) {
                                        $storeRacksMatrix[$palletPlace]['rating'] += 10;
                                    }
                                }
                            }
            
                            // Ако в ляво има палет със същия продукт (или има наредено движение) +5 т.
                            if ($c != 1) {
                                if (isset($palletsInStoreArr[$rackId][store_Racks::rackRowConv($r)][$c - 1])) {
                                    if ($palletsInStoreArr[$rackId][store_Racks::rackRowConv($r)][$c - 1]['productId'] == $productId) {
                                        $storeRacksMatrix[$palletPlace]['rating'] += 20;
                                    }
                                }
                            }
            
                            /* ENDOF Изчислява рейтинга на палет мястото */
                        }
                    }
                }
            }
        }
        
        /* ENDOF Създава масива $storeRacksMatrix с рейтинг (оценка) за всяко палет място от склада */
        
        // Вземаме палет мястото с най-голям рейтинг.
        // Ако са няколко места с равен рейтинг, $maxRatingArr държи разположеното най- в ляво
        $maxRatingArr['rating'] = -1000;
        $maxRatingArr['palletPlace'] = null;
        
        if (count($storeRacksMatrix)) {
            foreach ($storeRacksMatrix as $k => $v) {
                if ($v['rating'] > $maxRatingArr['rating']) {
                    $maxRatingArr['rating'] = $v['rating'];
                    $maxRatingArr['palletPlace'] = $k;
                }
            }
        }
        
        return $maxRatingArr['palletPlace'];
    }
}
