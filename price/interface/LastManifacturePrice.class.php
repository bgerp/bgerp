<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Последно производство"
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see price_CostPolicyIntf
 * @title Мениджърска себестойност "Последно производство"
 *
 */
class price_interface_LastManifacturePrice extends price_interface_BaseCostPolicy
{

    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'price_CostPolicyIntf';


    /**
     * Как се казва политиката
     *
     * @param bool $verbal - вербалното име или системното
     *
     * @return string
     */
    public function getName($verbal = false)
    {
        $res = ($verbal) ? tr('Последно производство') : 'lastManifacture';

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
        $query = cat_Products::getQuery();
        $query->where("#isPublic = 'yes' AND #canManifacture = 'yes' AND #state = 'active'");
        $query->show('id');

        return arr::extractValuesFromArray($query->fetchAll(), 'id');
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
        $res = array();
        if(!count($affectedTargetedProducts)) return $res;

        // Ако баланса се записва в момента, чака се докато свърши
        $maxTry = 7;
        while(core_Locks::isLocked(acc_Balances::saveLockKey)){
            sleep(1);
            $maxTry--;
            if($maxTry <= 0) break;
        }

        core_Debug::startTimer('CALC_LAST_MANIFACTURE_PRICE');

        // Намиране на последните ПП на въпросните артикули
        $pQuery = planning_DirectProductionNote::getQuery();
        $pQuery->XPR('lastId', 'int', 'MAX(#id)');
        $pQuery->where("#state = 'active'");
        $pQuery->in('productId', $affectedTargetedProducts);
        $pQuery->show('lastId,productId');
        $pQuery->groupBy('productId');

        $lastProduction = $productionIds = array();
        while($dRec = $pQuery->fetch()){
            $productionIds[$dRec->lastId] = $dRec->lastId;
            $lastProduction[$dRec->lastId] = array('id' => $dRec->lastId, 'productId' => $dRec->productId, 'journal' => array());
        }

        if(!count($lastProduction)) return $res;

        // Извличане на перата на афектираните артикули
        $productMap = array();
        $productIdsWithLastProduction = arr::extractValuesFromArray($lastProduction, 'productId');
        $iQuery = acc_Items::getQuery();
        $iQuery->where("#classId = " . cat_Products::getClassId());
        $iQuery->in('objectId', $productIdsWithLastProduction);
        $iQuery->show('id,objectId');
        while($iRec = $iQuery->fetch()){
            $productMap[$iRec->objectId] = $iRec->id;
        }
        if(!count($productMap)) return $res;

        // Извличане на записите от журнала на намерените ПП
        $productionClassId = planning_DirectProductionNote::getClassId();
        $jQuery = acc_JournalDetails::getQuery();
        $jQuery->EXT('docType', 'acc_Journal', 'externalKey=journalId');
        $jQuery->EXT('docId', 'acc_Journal', 'externalKey=journalId');
        $jQuery->EXT('valior', 'acc_Journal', 'externalKey=journalId');
        $jQuery->where("#docType = '{$productionClassId}'");
        if(count($productionIds)){
            $jQuery->in('docId', $productionIds);
        } else {
            $jQuery->where("1=2");
        }

        while($jRec = $jQuery->fetch()){
            $lastProduction[$jRec->docId]['journal'][$jRec->id] = $jRec;
        }

        $debitAccArr = array(acc_Accounts::getRecBySystemId(321)->id, acc_Accounts::getRecBySystemId(60201)->id);

        $today = dt::today();
        foreach ($lastProduction as $lastData){
            if(!countR($lastData['journal'])) continue;
            $productItemId = $productMap[$lastData['productId']];

            // Смятане на сумата, която ще се натрупва към сб-ст на произведения артикул
            $producedAmount = $producedQuantity = 0;
            foreach ($lastData['journal'] as $jRec){
                if(in_array($jRec->debitAccId, $debitAccArr) && $jRec->debitItem2 == $productItemId){
                    $producedQuantity += $jRec->debitQuantity;
                    $producedAmount += $jRec->amount;
                }
            }

            $producedPrice = !empty($producedQuantity) ? $producedAmount / $producedQuantity : 0;
            $producedPriceInBaseCurrency = deals_Helper::getSmartBaseCurrency($producedPrice, $jRec->valior, $today);
            $res[$lastData['productId']] = (object)array('productId'     => $lastData['productId'],
                                                         'classId'       => $this->getClassId(),
                                                         'sourceClassId' => $productionClassId,
                                                         'sourceId'      => $lastData['id'],
                                                         'valior'        => null,
                                                         'quantity'      => $producedQuantity,
                                                         'price'         => round($producedPriceInBaseCurrency, 5));
        }

        core_Debug::stopTimer('CALC_LAST_MANIFACTURE_PRICE');
        core_Debug::log("END CALC_LAST_MANIFACTURE_PRICE " . round(core_Debug::$timers["CALC_LAST_MANIFACTURE_PRICE"]->workingTime, 6));

        return $res;
    }
}