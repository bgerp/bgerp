<?php


/**
 * Клас 'batch_plg_PosReports' - За генериране на партидни движения от пос отчета
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class batch_plg_PosReports extends core_Plugin
{
    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if (batch_Movements::haveRightFor('list') && $data->rec->state == 'active') {
            $data->toolbar->addBtn('Партиди', array('batch_Movements', 'list', 'document' => $mvc->getHandle($data->rec->id)), 'ef_icon = img/16/wooden-box.png,title=Добавяне като ресурс,row=2');
        }
    }
    
    
    /**
     * Записва заопашените движения
     */
    public static function on_Shutdown($mvc)
    {
        if (is_array($mvc->saveMovementsOnShutdown)) {
            foreach ($mvc->saveMovementsOnShutdown as $rec) {
                self::saveMovement($rec);
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $saveFileds = null)
    {
        if ($rec->state == 'active') {
            $mvc->saveMovementsOnShutdown[$rec->id] = $rec;
        } elseif ($rec->state == 'rejected') {
            batch_Movements::removeMovement($mvc, $rec->id);
        }
    }
    
    
    /**
     * Записва движението на партидите
     * 
     * @param stdClass $rec
     */
    private static function saveMovement($rec)
    {
        $details = $rec->details['receiptDetails'];
        $pointRec = pos_Points::fetch($rec->pointId);
        $date = dt::verbal2mysql($rec->createdOn, false);
        $docType = pos_Reports::getClassId();
        $toSave = array();
        
        if(is_array($details)){
            foreach ($details as $detRec){
                if($detRec->action != 'sale' || empty($detRec->batch)) continue;
                $result = true;
                
                try {
                    $itemId = batch_Items::forceItem($detRec->value, $detRec->batch, $pointRec->storeId);
                    $quantity = round($detRec->quantity * $detRec->quantityInPack, 2);
                    
                    // Движението, което ще запишем
                    $mRec = (object) array('itemId' => $itemId,
                        'quantity' => $quantity,
                        'operation' => 'out',
                        'docType' => $docType,
                        'docId' => $rec->id,
                        'date' => $date,
                    );
                    
                    $toSave[] = $mRec;
                } catch (core_exception_Expect $e) {
                    reportException($e);
                    
                    // Ако е изникнала грешка
                    $result = false;
                }
            }
            
            // При грешка изтриваме всички записи до сега
            if ($result === false) {
                batch_Movements::removeMovement('pos_Reports', $rec->id);
            } elseif(countR($toSave)){
                cls::get('batch_Movements')->saveArray($toSave);
            }
        }
    }
}
