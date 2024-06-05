<?php


/**
 * Абстрактен клас за наследяване от драйвери за импортиране в протокол за влагане
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
abstract class planning_interface_ConsumptionNoteImportProto extends planning_interface_ImportDriver
{
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
    protected function getImportRecs(core_Manager $mvc, $rec)
    {
        $recs = array();
        if (!is_array($rec->_details)) return $recs;

        foreach ($rec->_details as $key => $data) {

            // Ако има въведено количество групира се по к-во и партиди
            if (!empty($rec->{$key})) {
                $k = "{$data['productId']}|{$data['packagingId']}";
                if(!array_key_exists($k, $recs)){
                    $importRec = (object)array('productId' => $data['productId'],
                        'packagingId' => $data['packagingId'],
                        'quantityInPack' => $data['quantityInPack'],
                        'noteId' => $rec->{$mvc->masterKey},
                        'batches' => array(),
                        'quantity' => 0);
                    $recs[$k] = $importRec;
                }
                if($this instanceof planning_interface_ImportFromConsignmentProtocol){
                    $rec->{$key} *= $data['quantityInPack'];
                }

                $recs[$k]->quantity += $rec->{$key};
                if(!empty($data['batch'])){
                    $recs[$k]->batches[$data['batch']] = $rec->{$key};
                }
            }
        }

        return $recs;
    }


    /**
     * Импортиране на детайла (@see import2_DriverIntf)
     *
     * @param object $rec
     *
     * @return void
     */
    public function doImport(core_Manager $mvc, $rec)
    {
        if (!is_array($rec->importRecs)) return;

        foreach ($rec->importRecs as $rec) {
            $fields = array();
            $exRec = null;
            if (!$mvc->isUnique($rec, $fields, $exRec)) {
                core_Statuses::newStatus('Записът не е импортиран, защото има дублиране');
                continue;
            }

            $rec->_clonedWithBatches = true;
            $rec->autoAllocate = false;

            $mvc->save($rec);
            if(countR($rec->batches)){
                batch_BatchesInDocuments::saveBatches($mvc, $rec->id, $rec->batches);
            }
        }
    }
}