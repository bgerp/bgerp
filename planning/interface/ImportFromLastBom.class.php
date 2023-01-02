<?php


/**
 * Помощен клас-имплементация на интерфейса import_DriverIntf
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Импорт на артикули от последната активна рецепта
 */
class planning_interface_ImportFromLastBom extends planning_interface_ImportDriver
{
    /**
     * Заглавие
     */
    public $title = 'Импорт на артикули от последната работна рецепта';
    
    
    /**
     * Добавя специфични полета към формата за импорт на драйвера
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function addImportFields($mvc, core_FieldSet $form)
    {
        $rec = &$form->rec;
        $rec->detailsDef = array();
        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        $bomId = null;

        $firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);
        if($firstDoc->isInstanceOf('planning_Jobs')) {

            // Ако Протокола за влагане е към задание и за артикула има активна рецепта - нея
            $productId = $firstDoc->fetchField('productId');
            expect($bomId = cat_Products::getLastActiveBom($productId, 'production,sales'));
            $form->info = tr('По рецепта') . ': ' . cat_Boms::getHyperlink($bomId, true);
            $defaultQuantity = $firstDoc->fetchField('quantity');
        } else {
            $form->info = tr('От планираното по') . ': ' . $firstDoc->getHyperlink(true);
            $defaultQuantity = $firstDoc->fetchField('plannedQuantity');
        }

        $form->FLD("forQuantity", 'int', "input,caption=За количество,silent");
        $form->setDefault('forQuantity', $defaultQuantity);
        $form->input('forQuantity', 'silent');

        if($firstDoc->isInstanceOf('planning_Jobs')) {

            // Ако е към задание ще се импортират материалите от заданието
            $details = cat_Boms::getBomMaterials($bomId, $rec->forQuantity, $masterRec->storeId);
        } else {
            $details = array();
            $dQuery = planning_ProductionTaskProducts::getQuery();
            $dQuery->where("#taskId = {$firstDoc->that} AND #type = 'input'");
            if(empty($masterRec->storeId)){
                $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
                $dQuery->where("#canStore = 'no'");
            }
            $plannedQuantity = $firstDoc->fetchField('plannedQuantity');
            while($dRec = $dQuery->fetch()){
                $ratio = $plannedQuantity / $dRec->plannedQuantity;
                $round = cat_UoM::fetchField($dRec->packagingId, 'round');
                $newQuantity = round($rec->forQuantity / $ratio, $round);
                $details[$dRec->id] = (object)array('productId' => $dRec->productId, 'quantity' => $newQuantity, 'quantityInPack' => $dRec->quantityInPack, 'packagingId' => $dRec->packagingId);
            }
        }

        foreach ($details as $dRec) {
            $dRec->caption = cat_Products::getTitleById($dRec->productId);
            $dRec->caption = str_replace(',', ' ', $dRec->caption);
            
            // Подготовка на полетата
            $key = "{$dRec->productId}_{$dRec->packagingId}";
            $shortUom = cat_UoM::getShortName($dRec->packagingId);

            $equivalentArr = planning_GenericMapper::getEquivalentProducts($dRec->productId);
            if(countR($equivalentArr) > 1){
                unset($equivalentArr[$dRec->productId]);
                $form->FLD("{$key}_replaceId", 'int', "input,caption={$dRec->caption}->Заместител");
                $form->setOptions("{$key}_replaceId", array('' => '') + $equivalentArr);
            }

            $form->FLD($key, 'double(Min=0)', "input,caption={$dRec->caption}->К-во,unit={$shortUom}");
            $form->setDefault($key, $dRec->quantity / $dRec->quantityInPack);
            $rec->detailsDef[$key] = $dRec;
        }

        $refreshFields = implode('|', array_keys($rec->detailsDef));
        $form->setField('forQuantity', "removeAndRefreshForm={$refreshFields}");
    }
    
    
    /**
     * Връща записите, подходящи за импорт в детайла
     *
     * @param array $recs
     *                    o productId        - ид на артикула
     *                    o quantity         - к-во в основна мярка
     *                    o quantityInPack   - к-во в опаковка
     *                    o packagingId      - ид на опаковка
     *                    o batch            - дефолтна партида, ако може
     *                    o notes            - забележки
     *                    o $this->masterKey - ид на мастър ключа
     *
     * @return void
     */
    private function getImportRecs(core_Manager $mvc, $rec)
    {
        $recs = array();
        if (!is_array($rec->detailsDef)) return $recs;

        foreach ($rec->detailsDef as $key => $dRec) {

            // Ако има въведено количество записва се
            if (!empty($rec->{$key})) {
                unset($dRec->id);
                $dRec->quantity = $rec->{$key} * $dRec->quantityInPack;

                if(!empty($rec->{"{$key}_replaceId"})){
                    $originalMeasureId = cat_Products::fetchField($dRec->productId, 'measureId');
                    $newMeasureId = cat_Products::fetchField($rec->{"{$key}_replaceId"}, 'measureId');
                    $dRec->packagingId = $newMeasureId;

                    $dRec->quantity = cat_Uom::convertValue($dRec->quantity, $originalMeasureId, $newMeasureId);
                    $dRec->quantityInPack = 1;
                    $dRec->productId = $rec->{"{$key}_replaceId"};
                }

                $dRec->noteId = $rec->{$mvc->masterKey};
                $dRec->isEdited = true;
                $recs[] = $dRec;
            }
        }

        return $recs;
    }
    
    
    /**
     * Проверява събмитнатата форма
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function checkImportForm($mvc, core_FieldSet $form)
    {
        if ($form->isSubmitted()) {
            $form->rec->importRecs = $this->getImportRecs($mvc, $form->rec);
        }
    }
    
    
    /**
     * Може ли драйвера за импорт да бъде избран
     *
     * @param core_Manager $mvc      - клас в който ще се импортира
     * @param int|NULL     $masterId - ако импортираме в детайл, id на записа на мастъра му
     * @param int|NULL     $userId   - ид на потребител
     *
     * @return bool - може ли драйвера да бъде избран
     */
    public function canSelectDriver(core_Manager $mvc, $masterId = null, $userId = null)
    {
        if (!($mvc instanceof planning_ConsumptionNoteDetails)) return false;

        if (isset($masterId)) {
            $masterRec = $mvc->Master->fetchRec($masterId);

            $firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);
            if($firstDoc->isInstanceOf('planning_Jobs')){
                if(empty($masterRec->storeId)) return false;

                // Ако Протокола за влагане е към задание и за артикула има активна рецепта - нея
                $productId = $firstDoc->fetchField('productId');
                $bomId = cat_Products::getLastActiveBom($productId, 'production,sales');
                $details = cat_Boms::getBomMaterials($bomId, $firstDoc->fetchField('quantity'), $masterRec->storeId);
                if (!countR($details)) return false;

            } elseif($firstDoc->isInstanceOf('planning_Tasks')){
                // Ако е към ПО само ако има посочени в таба планиране артикули за влагане
                $dQuery = planning_ProductionTaskProducts::getQuery();
                $dQuery->where("#type = 'input' && #taskId = {$firstDoc->that} ");
                if(empty($masterRec->storeId)){
                    $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
                    $dQuery->where("#canStore = 'no'");
                }
                $countPlanned = $dQuery->count();

                if(!$countPlanned) return false;
            } else {
                return false;
            }
        }
        
        return true;
    }
}
