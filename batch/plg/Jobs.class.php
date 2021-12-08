<?php


/**
 * Клас 'batch_plg_Jobs' - За добавяне на партиди към заданията за производство
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class batch_plg_Jobs extends core_Plugin
{
    /**
     * Кои роли могат да променят групово партидите на изходящите документи
     */
    public static function on_AfterGetRolesToModifyBatches($mvc, &$res, $rec)
    {
        if(!$res){
            $res = $mvc->getRequiredRoles('edit', $rec);
        }
    }


    /**
     * Филтриране по подразбиране на наличните партиди
     */
    public static function on_AfterFilterBatches($mvc, &$res, $rec, &$batches)
    {

    }


    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        // Ако документа има сингъл добавя му се информацията за партидата
        $row = &$data->row;
        $rec = &$data->rec;

        if(isset($rec->storeId)){
            $canStore = cat_Products::fetchField($rec->productId, 'canStore');
            if($canStore == 'yes' && batch_Defs::getBatchDef($rec->productId)){
                if (batch_BatchesInDocuments::haveRightFor('modify', (object) array('detailClassId' => $mvc->getClassId(), 'detailRecId' => $rec->id, 'storeId' => $rec->storeId))) {
                    if (!core_Mode::isReadOnly()) {
                        core_Request::setProtected('detailClassId,detailRecId,storeId');
                        $url = array('batch_BatchesInDocuments', 'modify', 'detailClassId' => $mvc->getClassId(), 'detailRecId' => $rec->id, 'storeId' => $rec->storeId, 'ret_url' => true);
                        $row->addBatchBtn = ht::createLink('', $url, false, 'ef_icon=img/16/edit-icon.png,title=Промяна на партидите');
                    }
                }

                $row->BATCHES = batch_BatchesInDocuments::renderBatches($mvc, $rec->id, $rec->storeId);
            }
        }
    }


    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    public static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);

        $res = (object) array('productId'      => $rec->productId,
                              'packagingId'    => $rec->packagingId,
                              'quantity'       => $rec->quantity,
                              'quantityInPack' => $rec->quantityInPack,
                              'containerId'    => $rec->containerId,
                              'date'           => $rec->dueDate,
                              'state'          => $rec->state,
                              'operation'      => array('in' => $rec->storeId),
        );

        return $res;
    }


    /**
     * Модифициране на партидите към заданието
     *
     * @param $mvc
     * @param $rec
     * @param $action
     */
    private static function modifyBatches($mvc, $rec, $action)
    {
        if(isset($rec->storeId) && isset($rec->saleId)){
            $batchDef = batch_Defs::getBatchDef($rec->productId);
            $canStore = cat_Products::fetchField($rec->productId, 'canStore');
            if(is_object($batchDef) && $canStore == 'yes'){
                if($action == 'add'){
                    $threadId = sales_Sales::fetchField($rec->saleId, 'threadId');

                    $saveBatches = array();
                    $bQuery = batch_BatchesInDocuments::getQuery();
                    $bQuery->EXT('threadId', 'doc_Containers', "externalName=threadId,externalKey=containerId");
                    $bQuery->where("#threadId = {$threadId} AND #productId = {$rec->productId} AND #storeId = {$rec->storeId}");
                    $bQuery->show('batch,quantity');
                    while($bRec = $bQuery->fetch()){
                        $saveBatches["{$bRec->batch}"] = $bRec->quantity;
                    }

                    if(countR($saveBatches)){
                        batch_BatchesInDocuments::saveBatches($mvc, $rec->id, $saveBatches, true);
                    }
                } elseif($action == 'update'){
                    $bQuery = batch_BatchesInDocuments::getQuery();
                    $bQuery->where("#containerId = {$rec->containerId}");
                    while($bRec = $bQuery->fetch()){
                        $bRec->storeId = $rec->storeId;
                        batch_BatchesInDocuments::save($bRec, 'storeId');
                    }
                }
            }
        }
    }


    /**
     * Преди запис на документ
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if(isset($rec->id)){
            $rec->_oldStoreId = $mvc->fetchField($rec->id, 'storeId', '*');
        } else {
            $rec->_isCreated = true;
        }
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public function on_AfterSave($mvc, &$id, $rec, $fields = null)
    {
        if($rec->_isCreated){
            static::modifyBatches($mvc, $rec, 'add');
        } elseif(empty($rec->storeId) && isset($rec->_oldStoreId)){
            batch_BatchesInDocuments::delete("#containerId = {$rec->containerId}");
        } elseif($rec->storeId && $rec->storeId != $rec->_oldStoreId){
            static::modifyBatches($mvc, $rec, 'update');
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if($form->isSubmitted()){
            if(isset($rec->id)){
                if(empty($rec->storeId) && batch_BatchesInDocuments::fetchField("#containerId = {$rec->containerId}")){
                    $form->setWarning('storeId', 'Не е посочен склад, разпределените партиди ще бъдат изтритиЮ*!');
                }
            }
        }
    }


    /**
     * Метод по реализация на определянето на движението генерирано от реда
     *
     * @param core_Mvc $mvc
     * @param string   $res
     * @param stdClass $rec
     *
     * @return void
     */
    public static function on_AfterGetBatchMovementDocument($mvc, &$res, $rec)
    {
        if (!$res) {
            $res = 'in';
        }
    }


    /**
     * Метод по подразбиране за позволени партиди за заприхождаване
     */
    public static function on_AfterGetAllowedInBatches($mvc, &$res, $rec)
    {
        if(!$res){
            $rec = $mvc->fetchRec($rec);
            if($BatchDef = batch_Defs::getBatchDef($rec->productId)){
                $BatchType = $BatchDef->getBatchClassType();
                if($BatchType instanceof type_Enum){
                    $options = $BatchType->options;
                    $res = array_combine(array_values($options), array_values($options));
                }
            }
        }
    }
}