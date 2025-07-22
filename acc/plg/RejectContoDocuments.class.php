<?php


/**
 * Плъгин който преди оттегляне/възстановяване/контиране на контиращи документи, провеврява имали в тях приключени пера
 * и ако има забранвява съответното действие, показвайки съобщение, кои пера къде са затворени.
 *
 * Ако искаме документа да не подлежи на тази проверка, трябва да му зададем атрибут `canUseClosedItems = TRUE`
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_plg_RejectContoDocuments extends core_Plugin
{
    /**
     * Кои са затворените пера в транзакцията на документа
     */
    public static function on_AfterGetClosedItemsInTransaction($mvc, &$res, $id)
    {
        // Ако е мениджъра е казано, че може да се контира/възстановява/оттегля ако има затворени права, премахваме изискването
        if ($mvc->canUseClosedItems($id)) {
            $res = array();
            
            return;
        }
        
        // Ако няма пера
        if (!is_array($res)) {
            
            // Взима всички от текущата транзакция
            $transaction = $mvc->getValidatedTransaction($id);
            
            if ($transaction) {
                $res = $transaction->getClosedItems();
            }
        }
    }
    
    
    /**
     * Дали документа може да бъде възстановен/оттеглен/контиран, ако в транзакцията му има
     * поне едно затворено перо връща FALSE
     */
    public static function on_AfterCanRejectOrRestore($mvc, &$res, $id, $action, $ignoreArr = array())
    {
        try {
            $closedItems = $mvc->getClosedItemsInTransaction($id);
        } catch (acc_journal_Exception $e) {
            
            return;
        }
        
        // Ако има пера за игнориране, игнорираме ги
        if (countR($ignoreArr)) {
            foreach ($ignoreArr as $ignore) {
                unset($closedItems[$ignore]);
            }
        }
        
        // Ако има затворено перо в транзакциите или документа е използван като перо в документ от друг тред
        if (countR($closedItems) || (isset($mvc->usedIn) && is_array($mvc->usedIn))) {
            
            // Ако има затворени пера, показваме съобщение и връщаме FALSE
            if (countR($closedItems)) {
                $itemByClass = array();
                $iQuery = acc_Items::getQuery();
                $iQuery->in('id', $closedItems);
                while($iRec = $iQuery->fetch()) {
                    $itemByClass[$iRec->classId][$iRec->id] = acc_Items::getVerbal($iRec->id, 'title');
                }
                $msg = tr("|*#{$mvc->getHandle($id)} |не може да бъде оттеглен/възстановен докато|* ");
                $subStr = '';

                $c = 0;
                foreach ($itemByClass as $classId => $items) {
                    $sArr = array();
                    $sTitle = mb_strtolower(tr(cls::get($classId)->singleTitle));
                    foreach ($items as $itemName){
                        $itemName = str_replace('"', '', $itemName);
                        $sArr[] = "{$sTitle} \"{$itemName}\"";
                        $c++;
                    }
                    $subStr .= (!empty($subStr) ?  tr('|* |и|* ') : '') . implode(", ", $sArr);
                }
                
                $msg .= "{$subStr} " . ($c == 1 ? tr('е затворен') : tr('е/са затворен/и'));

                core_Statuses::newStatus($msg, 'error');
                if(core_Users::isSystemUser()){
                    $mvc->logWrite("Неуспешно контиране от системата, поради затворени пера", $id);
                }
            }
            
            // Ако документа е използван в контировката на документ от друг тред, показваме съобщение и връщаме FALSE
            if (countR($mvc->usedIn)) {
                if($action != 'conto'){
                    foreach ($mvc->usedIn as $itemId => $used) {
                        $itemName = acc_Items::getVerbal($itemId, 'title');
                        $msg = tr("|Документът|* \"{$itemName}\" |не може да бъде оттеглен/възстановен докато е контиран от следните документи извън нишката|*:");

                        foreach ($used as $doc) {
                            $msg .= '#' . $doc . ', ';
                        }
                    }

                    $msg = trim($msg, ', ');
                    core_Statuses::newStatus($msg, 'error');
                }
            }

            if(!empty($msg)){
                $res = false;
            }
        } else {
            $res = true;
        }
    }
    
    
    /**
     * Преди оттегляне, ако има затворени пера в транзакцията, не може да се оттегля
     */
    public static function on_BeforeConto($mvc, &$res, $id)
    {
        // Ако не може да се оттегля, връща FALSE за да се стопира оттеглянето
        return $mvc->canRejectOrRestore($id, 'conto');
    }
    
    
    /**
     * Преди оттегляне, ако има затворени пера в транзакцията, не може да се оттегля
     */
    public static function on_BeforeReject($mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        if ($rec->state != 'draft' && $rec->state != 'stopped' && $rec->state != 'pending') {
            
            // Ако не може да се оттегля, връща FALSE за да се стопира оттеглянето
            return $mvc->canRejectOrRestore($id, 'reject');
        }
    }
    
    
    /**
     * Преди възстановяване, ако има затворени пера в транзакцията, не може да се възстановява
     */
    public static function on_BeforeRestore($mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        $ignore = array();
        
        // Ако не може да се възстановява, връща FALSE за да се стопира възстановяването
        if ($rec->brState != 'draft' && $rec->brState != 'stopped' && $rec->brState != 'pending') {
            
            // Ако документа не е сделка
            if (!cls::haveInterface('deals_DealsAccRegIntf', $mvc)) {
                $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
                
                // и състоянието и е отворено, игнорираме перото и
                $documentItemRec = acc_Items::fetchItem($mvc->getClassId(), $rec->id);
                if(is_object($documentItemRec)){
                    $ignore[] = $documentItemRec->id;
                } else {
                    if (is_object($firstDoc) && $firstDoc->fetchField('state') == 'active') {
                        $ignore[] = acc_Items::fetchItem($firstDoc->getClassId(), $firstDoc->that)->id;
                    }
                }
            } else {
                
                // Ако класа е пос отчет винаги му игнорираме перото
                if ($mvc instanceof pos_Reports) {
                    $ignore[] = acc_items::fetchItem($mvc->getClassId(), $rec->id)->id;
                }
            }
            
            return $mvc->canRejectOrRestore($id, 'restore', $ignore);
        }
    }
}
