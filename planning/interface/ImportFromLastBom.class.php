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
        expect($bomId = self::getLastActiveBom($masterRec));
        $form->info = tr('По рецепта') . ' ' . cat_Boms::getHyperlink($bomId, true);
        $firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);

        $form->FLD("forQuantity", 'int', "input,caption=За количество,silent");
        $form->setDefault('forQuantity', $firstDoc->fetchField('quantity'));
        $form->input('forQuantity', 'silent');

        // Взимате се материалите за производството на к-то от заданието
        $details = cat_Boms::getBomMaterials($bomId, $rec->forQuantity, $masterRec->storeId);
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
     * Намира последната работна рецепта
     *
     * @param stdClass $masterRec
     *
     * @return mixed
     */
    private static function getLastActiveBom($masterRec)
    {
        // Опит за намиране на първата работна рецепта
        $firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);
        if (!$firstDoc->isInstanceOf('planning_Jobs')) {
            
            return false;
        }
        $productId = $firstDoc->fetchField('productId');
        $bomId = cat_Products::getLastActiveBom($productId, 'production,sales');
        
        // Ако има рецепта, проверява се има ли редове в нея
        if (!empty($bomId)) {
            $details = cat_Boms::getBomMaterials($bomId, $firstDoc->fetchField('quantity'), $masterRec->storeId);
            if (countR($details)) {

                return $bomId;
            }
        }
        
        return false;
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
            if(empty($masterRec->storeId)) return;

            $bomId = self::getLastActiveBom($masterRec);
            if (empty($bomId)) return false;
        }
        
        return true;
    }
}
