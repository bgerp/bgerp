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
    public $title = 'Импорт на артикули от последната активна рецепта';
    
    
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

        $form->FLD("forQuantity", 'double', "input,caption=За количество,silent");
        $form->FLD("onlyInStock", 'enum(yes=Само наличните в склада Артикули,no=Всички Артикули от Рецептата)', "input,caption=Избор,silent,removeAndRefreshForm");
        $form->setDefault('forQuantity', $defaultQuantity);
        $form->input('forQuantity,onlyInStock', 'silent');

        $defaultInStock = core_Permanent::get('onlyInStockBomImport');
        $defaultInStock = $defaultInStock ?? 'yes';
        $form->setDefault('onlyInStock', $defaultInStock);

        if($firstDoc->isInstanceOf('planning_Jobs')) {
            $storeId = ($form->rec->onlyInStock == 'yes') ? $masterRec->storeId : null;
            $ignoreArr = array(array($firstDoc->getClassId(), $firstDoc->that));

            // Ако е към задание ще се импортират материалите от заданието
            $details = cat_Boms::getBomMaterials($bomId, $rec->forQuantity, $storeId, false, $ignoreArr);
            foreach ($details as &$d1){
                $uomRec = cat_UoM::fetch($d1->packagingId, 'roundSignificant,round');
                $d1->quantity = core_Math::roundNumber($d1->quantity, $uomRec->round, $uomRec->roundSignificant);
            }
        } else {
            $details = array();
            $dQuery = planning_ProductionTaskProducts::getQuery();
            $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $dQuery->where("#taskId = {$firstDoc->that} AND #type = 'input' AND #canStore = 'yes'");

            $plannedQuantity = $firstDoc->fetchField('plannedQuantity');
            $ratio = $rec->forQuantity / $plannedQuantity;

            while($dRec = $dQuery->fetch()){
                $round = cat_UoM::fetchField($dRec->packagingId, 'round');
                $newQuantity = round($dRec->plannedQuantity * $ratio, $round);
                $details[$dRec->id] = (object)array('productId' => $dRec->productId, 'quantity' => $newQuantity, 'quantityInPack' => $dRec->quantityInPack, 'packagingId' => $dRec->packagingId);
            }
        }

        foreach ($details as $dRec) {
            $dRec->caption = cat_Products::getTitleById($dRec->productId);
            $dRec->caption = str_replace(',', ' ', $dRec->caption);
            
            // Подготовка на полетата
            $key = "{$dRec->productId}_{$dRec->packagingId}";
            $shortUom = cat_UoM::getShortName($dRec->packagingId);

            $equivalentArr = planning_GenericMapper::getEquivalentProducts($dRec->productId, $dRec->genericProductId, false, true);
            if(countR($equivalentArr) > 1){
                unset($equivalentArr[$dRec->productId]);
                $form->FLD("{$key}_replaceId", 'int', "input,caption={$dRec->caption}->Заместител");
                $form->setOptions("{$key}_replaceId", array('' => '') + $equivalentArr);
            }

            $form->FLD($key, 'double(Min=0)', "input,caption={$dRec->caption}->К-во,unit={$shortUom}");

            $form->setDefault($key, $dRec->quantity);
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
        $rec = &$form->rec;
        if ($form->isSubmitted()) {
            $rec->importRecs = $this->getImportRecs($mvc, $rec);
            if(!countR($rec->importRecs)){
                $form->setError('onlyInStock', 'Няма налични артикули за импортиране');
            }
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
                if(empty($bomId)) return false;

                $details = cat_Boms::getBomMaterials($bomId, $firstDoc->fetchField('quantity'));
                if (!countR($details)) return false;

            } elseif($firstDoc->isInstanceOf('planning_Tasks')){
                // Ако е към ПО само ако има посочени в таба планиране артикули за влагане
                $dQuery = planning_ProductionTaskProducts::getQuery();
                $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
                $dQuery->where("#type = 'input' && #taskId = {$firstDoc->that} AND #canStore = 'yes'");
                $countPlanned = $dQuery->count();

                if(!$countPlanned) return false;
            } else {
                return false;
            }
        }
        
        return true;
    }


    /**
     * Импортиране на детайла (@see import2_DriverIntf)
     *
     * @param object $rec
     * @return void
     */
    public function doImport(core_Manager $mvc, $rec)
    {
        $res = parent::doImport($mvc, $rec);
        core_Permanent::set('onlyInStockBomImport', $rec->onlyInStock, core_Permanent::FOREVER_VALUE);

        return $res;
    }
}
