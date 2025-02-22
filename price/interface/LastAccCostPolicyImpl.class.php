<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Счетоводна (доставна) себестойност"
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see price_CostPolicyIntf
 * @title Мениджърска себестойност "Счетоводна (доставна)"
 *
 */
class price_interface_LastAccCostPolicyImpl extends price_interface_BaseCostPolicy
{
    
    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'price_CostPolicyIntf';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
    /**
     * Как се казва политиката
     *
     * @param bool $verbal - вербалното име или системното
     *
     * @return string
     */
    public function getName($verbal = false)
    {
        $res = ($verbal) ? tr('Счетоводна (доставна)') : 'accCost';
        
        return $res;
    }


    /**
     * Изчислява себестойностите на засегнатите артикули
     *
     * @param array $affectedTargetedProducts - засегнати артикули
     * @param array $params - параметри
     *
     * @return array
     *         ['classId']       - клас ид на политиката
     *         ['productId']     - ид на артикул
     *         ['quantity']      - количество
     *         ['price']         - ед. цена
     *         ['valior']        - вальор
     *         ['sourceClassId'] - ид на класа на източника
     *         ['sourceId']      - ид на източника
     */
    public function getCosts($affectedTargetedProducts, $params = array())
    {
        $tmpArr = $res = array();
        $balanceRec = acc_Balances::getLastBalance();

        // Ако няма баланс няма какво да подготвяме
        if (empty($balanceRec)) {
            
            return $res;
        } else {

            // Ако баланса се записва в момента, чака се докато свърши
            $maxTry = 5;
            while(core_Locks::isLocked(acc_Balances::saveLockKey)){
                sleep(1);
                $maxTry--;
                if($maxTry <= 0) break;
            }

            if(is_array($affectedTargetedProducts)){
                foreach ($affectedTargetedProducts as $key => $productId){
                    if(array_key_exists($productId, self::$cache)){
                        $res[$productId] = self::$cache[$productId];
                        unset($affectedTargetedProducts[$key]);
                    }
                }
            }
            
            if(!countR($affectedTargetedProducts)){
                
                return $res;
            }
        }

        $productClassId = cat_Products::getClassId();
        $productMap = array();
        $iQuery = acc_Items::getQuery();
        $iQuery->where("#classId = {$productClassId}");
        $iQuery->in('objectId', $affectedTargetedProducts);
        $iQuery->show('id,objectId');
        while($iRec = $iQuery->fetch()){
            $productMap[$iRec->objectId] = $iRec->id;
        }
       
        if(!countR($productMap)) {
            
            return $res;
        }

        // Филтриране да се показват само записите от зададените сметки за ТЕКУЩИЯ период
        $dQuery = acc_BalanceDetails::getQuery();
        acc_BalanceDetails::filterQuery($dQuery, $balanceRec->id, '321,60201', null, null, $productMap);
        $dRecs = $dQuery->fetchAll();

        // За всеки запис в баланса
        foreach ($dRecs as $dRec) {
            $itemId = $dRec->{"ent2Id"};
            if (!array_key_exists($itemId, $tmpArr)) {
                $tmpArr[$itemId] = new stdClass();
            }

            if(isset($dRec->blQuantity)){
                if(!empty($dRec->blQuantity)){
                    if ($dRec->blQuantity >= 0 && $dRec->blAmount >= 0) {
                        $tmpArr[$itemId]->quantity += $dRec->blQuantity;
                        $tmpArr[$itemId]->amount += $dRec->blAmount;
                    }
                } else {
                    if ($dRec->debitQuantity >= 0 && $dRec->debitAmount >= 0) {
                        $tmpArr[$itemId]->quantity += $dRec->debitQuantity;
                        $tmpArr[$itemId]->amount += $dRec->debitAmount;
                    }
                }
            }
        }

        // Гледа се дали в текущия период има артикули, които НЯМАТ записи
        $productsInCurrentBalance = arr::extractValuesFromArray($dRecs, 'ent2Id');
        $productsNotInBalance = array_diff($productMap, $productsInCurrentBalance);
        if(countR($productsNotInBalance)){

            // Ако има такива ще се извлекат от кешираните цени по периоди
            $productsInOtherBalances = array();
            $productsToDate = acc_ProductPricePerPeriods::getPricesToDate($balanceRec->fromDate, $productsNotInBalance, null, 'stores,costs');
            foreach ($productsToDate as $pRec){
                $productsInOtherBalances[$pRec->productItemId][] = $pRec->price;
            }

            // средно аритметично
            $tmpArr = array();
            foreach ($productsInOtherBalances as $pKey => $pGrouped) {
                if (array_key_exists($pKey, $tmpArr)) continue;
                $tmpArr[$pKey] = (object)array('amount' => array_sum($pGrouped), 'quantity' => countR($pGrouped));
            }
        }

        // Намиране на цената
        $productMap = array_flip($productMap);
        foreach ($tmpArr as $index => $r) {
            $pId = $productMap[$index];
            $amount = (!$r->quantity) ? 0 : round($r->amount / $r->quantity, 5);

            // Подсигуряване, че сумата няма да е отрицателна 0. -0 и 0 са равни при проверка с ==
            $amount = ($amount == 0) ? 0 : $amount;
            $amountToCheck = round($amount, 5);

            // Ако изчислената средна складова е 0 няма да се запише
            if(empty($amountToCheck)) continue;

            $r->quantity = (!$r->quantity) ? 0 : $r->quantity;
            
            $res[$pId] = (object)array('productId'     => $pId,
                                       'price'         => $amount,
                                       'classId'       => $this->getClassId(),
                                       'valior'        => null,
                                       'sourceClassId' => null,
                                       'sourceId'      => null,
                                       'quantity'      => $r->quantity);
           
            self::$cache[$pId] = $res[$pId];
        }

        // Връщане на резултатите
        return $res;
    }


    /**
     * Дали има самостоятелен крон процес за изчисление
     *
     * @param datetime $datetime
     *
     * @return array
     */
    public function getAffectedProducts($datetime)
    {
        // Всички артикули с движения
        $affected = $this->getAffectedProductWithMovement($datetime, 'all', array(), array(), false);

        return $affected;
    }
}
