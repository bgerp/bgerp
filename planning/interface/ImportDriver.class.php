<?php


/**
 * Баща за импортиране на драйверите за производветните документи
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class planning_interface_ImportDriver extends import2_AbstractDriver
{
    /**
     * Кой може да избира драйвъра
     */
    protected $canSelectDriver = 'ceo,planning,store';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'planning_interface_ImportDetailIntf';
    
    
    /**
     * Импортиране на детайла (@see import2_DriverIntf)
     *
     * @param object $rec
     *
     * @return void
     */
    public function doImport(core_Manager $mvc, $rec)
    {
        if (!is_array($rec->importRecs)) {
            
            return;
        }
        
        foreach ($rec->importRecs as $rec) {
            expect($rec->productId, 'Липсва продукт ид');
            expect(cat_Products::fetchField($rec->productId), 'Няма такъв артикул');
            expect($rec->packagingId, 'Няма опаковка');
            expect(cat_UoM::fetchField($rec->packagingId), 'Несъществуваща опаковка');
            expect($rec->{$mvc->masterKey}, 'Няма мастър кей');
            expect($mvc->Master->fetch($rec->{$mvc->masterKey}), 'Няма такъв запис на мастъра');
            expect($mvc->haveRightFor('add', (object) array($mvc->masterKey => $rec->{$mvc->masterKey})), 'Към този мастър не може да се добавя артикул');
            
            $fields = array();
            $exRec = null;
            if (!$mvc->isUnique($rec, $fields, $exRec)) {
                core_Statuses::newStatus('Записът не е импортиран, защото има дублиране');
                continue;
            }
            
            $mvc->save($rec);
        }
    }


    /**
     * Помощна ф-я за добавяне на партидности към резултатите от предходните ПО
     *
     * @param core_Query $bQuery
     * @param array $producedProducts
     * @param bool $zeroQuantity
     * @return void
     */
    protected static function addBatchDataToArray($bQuery, &$producedProducts, $zeroQuantity = false)
    {
        while($bRec = $bQuery->fetch()){
            if($batchDef = batch_Defs::getBatchDef($bRec->productId)){
                $bArr = array_keys($batchDef->makeArray($bRec->batch));
                foreach ($bArr as $b){
                    $bKey = md5($b);
                    if(!array_key_exists($bKey, $producedProducts[$bRec->productId]['batches'])){
                        $producedProducts[$bRec->productId]['batches'][$bKey] = array("batch" => $b, 'quantity' => 0);
                    }
                    if(!$zeroQuantity){
                        $producedProducts[$bRec->productId]['batches'][$bKey]['quantity'] += $bRec->quantity;
                    }
                }
            }
        }
    }
}
