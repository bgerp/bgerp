<?php


/**
 * Клас 'store_plg_RequestDetail' за записване на поръчаните количества в детайл на складов документ
 *
 * @see store_plg_Request
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_plg_RequestDetail extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->requestQuantityFieldName, 'requestedQuantity');
        setIfNot($mvc->quantityFieldName, 'quantity');
        setIfNot($mvc->quantityInPackName, 'quantityInPack');
        setIfNot($mvc->packQuantityFld, 'packQuantity');
        
        // Добавяне на поле за заявено количество
        $mvc->FLD($mvc->requestQuantityFieldName, 'double', 'caption=Заявено,input=none,forceField,smartCenter');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if (!countR($data->recs)) {
            
            return;
        }
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')) {
            
            return;
        }
        $showRequested = false;
        
        foreach ($data->rows as $id => &$row) {
            $rec = $data->recs[$id];
            $requested = $rec->{$mvc->requestQuantityFieldName};
            
            if (isset($requested)) {
                if ($requested != $rec->{$mvc->packQuantityFld}) {
                    if ($requested <= $rec->{$mvc->packQuantityFld}) {
                        $row->{$mvc->requestQuantityFieldName} = "<span class='red'>{$row->{$mvc->requestQuantityFieldName}}</span>";
                    }
                    $showRequested = true;
                } else {
                    unset($row->{$mvc->requestQuantityFieldName});
                }
            }
        }
        
        if ($showRequested === true) {
            $data->listTableMvc->setField("{$mvc->requestQuantityFieldName}", 'tdClass=lighterColor');
            arr::placeInAssocArray($data->listFields, array("{$mvc->requestQuantityFieldName}" => 'Поръчано'), null, 'packQuantity');
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($rec->{$mvc->requestQuantityFieldName})) {
            $rec->{$mvc->requestQuantityFieldName} /= $rec->{$mvc->quantityInPackName};
            $row->{$mvc->requestQuantityFieldName} = $mvc->getFieldType($mvc->requestQuantityFieldName)->toVerbal($rec->{$mvc->requestQuantityFieldName});
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($requiredRoles == 'no_one') {
            
            return;
        }
        
        if ($action == 'delete' && isset($rec->{$mvc->requestQuantityFieldName})) {
            $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
            if (!self::isApplicant($mvc->Master, $masterRec, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Дали потребителя е 'Заявител' на складовия документ и
     * може да променя заявените количества
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param int|NULL $userId
     *
     * @return bool
     */
    private static function isApplicant($masterMvc, $masterRec, $userId = null)
    {
        $masterRec = $masterMvc->fetchRec($masterRec);
        
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        // Създателя на документа и ceo-то са 'Заявители'
        if (haveRole('ceo', $userId)) {
            
            return true;
        }
        if ($masterRec->createdBy == $userId) {
            
            return true;
        }
        
        // Ако потребителя може да контира в склада той НЕ е 'заявител'
        if (bgerp_plg_FLB::canUse('store_Stores', $masterRec->{$masterMvc->storeFieldName}, $userId)) {
            
            return false;
        }
        
        // Ако не може да контира в склада, но може да избира е 'заявител'
        if (bgerp_plg_FLB::canUse('store_Stores', $masterRec->{$masterMvc->storeFieldName}, $userId, 'select')) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        // Ако е заявител
        if (self::isApplicant($mvc->Master, $rec->{$mvc->masterKey})) {
            
            // И няма заявено количество: попълва се
            if (!isset($rec->{$mvc->requestQuantityFieldName})) {
                $rec->{$mvc->requestQuantityFieldName} = $rec->{$mvc->quantityFieldName};
                $mvc->save_($rec, $mvc->requestQuantityFieldName);
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        if (self::isApplicant($mvc->Master, $data->masterRec) && isset($data->form->rec->id)) {
            $data->form->toolbar->addSbBtn('Поръчано', 'requested', 'id=btnReq,order=9.99981', 'ef_icon = img/16/save_and_new.png');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            if ($form->cmd == 'requested') {
                
                // Ако е натиснат бутона за 'Поръчано', дига се флаг
                $form->rec->updateRequested = true;
            }
        }
    }
    
    
    /**
     * Преди запис
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        // Е записа е от натискане на бутона 'Заявено', обновява се заявеното
        if ($rec->updateRequested === true) {
            $rec->{$mvc->requestQuantityFieldName} = $rec->{$mvc->quantityFieldName};
            $mvc->save_($rec, $mvc->requestQuantityFieldName);
        }
    }
    
    
    /**
     * Масив връщащ детайлите с недоставени к-ва
     */
    public static function on_AfterGetUndeliveredDetails($mvc, &$res, $masterId)
    {
        if (isset($res)) {
            
            return $res;
        }
        $res = array();
        
        $dQuery = $mvc->getQuery();
        $dQuery->where("#{$mvc->masterKey} = {$masterId} AND #{$mvc->requestQuantityFieldName} IS NOT NULL");
        while ($dRec = $dQuery->fetch()) {
            $dRec->quantity = $dRec->{$mvc->requestQuantityFieldName} - $dRec->quantity;
            $dRec->packQuantity = $dRec->quantity / $dRec->{$mvc->quantityInPackName};
            $dRec->autoBatches = true;
            
            $fieldsNotToClone = arr::make($mvc->fieldsNotToClone, true);
            foreach ($fieldsNotToClone as $fld) {
                unset($dRec->{$fld});
            }
            
            if ($dRec->quantity > 0) {
                $res[] = $dRec;
            }
        }
    }
}
