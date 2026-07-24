<?php


/**
 * Клас 'planning_ProductionDocument' - базов клас за наследяване
 * на документи за засклаждане на произведен артикул
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class planning_ProductionDocument extends deals_ManifactureMaster
{
    /**
     * Работен кеш
     */
    protected $arr = array();


    /**
     * Дали да се проверява за по-нов производствен документ при оттегляне
     */
    protected $checkNewerProductionDocument = true;
    
    
    /**
     * Рендиране на документа
     */
    public function renderSingle_($data)
    {
        $tpl = parent::renderSingle_($data);
        $tpl->push('planning/tpl/styles.css', 'CSS');

        return $tpl;
    }


    /**
     * Връща грешка при опит за контиране/възстановяване, или null ако няма.
     * Наследниците могат да предефинират за специфични проверки.
     *
     * @param stdClass|int $rec
     * @param string       $action  'conto' или 'restore'
     * @return string|null
     */
    protected function getErrorWhenTryingToConto($rec, $action = 'conto')
    {
        return null;
    }


    /**
     * Изпълнява се преди контиране на документа
     */
    public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $errorMsg = $mvc->getErrorWhenTryingToConto($id);
        if (!empty($errorMsg)) {
            core_Statuses::newStatus($errorMsg, 'error');

            return false;
        }
    }


    /**
     * Проверка имали по нов производствен документ
     *
     * @param stdClass $rec
     */
    protected function getNewerProductionDocumentHandle($rec)
    {
        if (isset($this->arr[$rec->id])) {
            
            return $this->arr[$rec->id];
        }
        
        $res = false;
        
        // Ако е протокол за производство
        if ($this instanceof planning_ProductionNotes) {
            $query = planning_ProductionNoteDetails::getQuery();
            $query->where("#noteId = {$rec->id}");
            $query->show('jobId');
            while ($dRec = $query->fetch()) {
                
                // Ако за заданието на някой от детайлите му има по нов документ
                if ($handle = $this->hasNewerProductionDocument($this, $rec, $dRec->jobId)) {
                    $res = $handle;
                }
            }
            
            // Ако е протокол за бързо производство
        } elseif ($this instanceof planning_DirectProductionNote) {
            if ($handle = $this->hasNewerProductionDocument($this, $rec)) {
                $res = $handle;
            }
        }
        
        $this->arr[$rec->id] = $res;
        
        return $res;
    }
    
    
    /**
     * Имали по нов производсвтен документ по заданието
     *
     * @param core_Mvc $mvc
     * @param int      $id
     * @param int      $jobId
     *
     * @return string|FALSE - хендлъра на по-новия документ
     */
    private function hasNewerProductionDocument(core_Mvc $mvc, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        // Проверяваме в протколите за бързо производство
        $dQuery = planning_DirectProductionNote::getQuery();
        $dQuery->EXT('containerCreatedOn', 'doc_Containers', 'externalName=createdOn,externalKey=containerId');
        $dQuery->where("#state = 'active' AND #containerCreatedOn > '{$rec->createdOn}'");
        
        // Имали такъв с по-нова дата към същото задание
        $dQuery->where("#originId = '{$rec->originId}'");
        if ($mvc instanceof planning_DirectProductionNote) {
            $dQuery->where("#id != {$rec->id}");
        }
        $dQuery->show('id');
        $dQuery->orderBy('id', 'DESC');
        $dQuery->limit(1);
        
        // Ако има намерен документ
        if ($fRec = $dQuery->fetch()) {
            
            return planning_DirectProductionNote::getHandle($fRec->id);
        }
        
        $db = new core_Db();
        if ($db->tableExists('planning_production_note_details') && ($db->tableExists('planning_production_note'))) {
            $origin = doc_Containers::getDocument($rec->originId);
            
            if($origin->isInstanceOf('planning_Jobs')){
                // Проверяваме към протоколите за производство
                $dQuery = planning_ProductionNoteDetails::getQuery();
                $dQuery->EXT('state', 'planning_ProductionNotes', 'externalName=state,externalKey=noteId');
                $dQuery->EXT('containerId', 'planning_ProductionNotes', 'externalName=containerId,externalKey=noteId');
                $dQuery->where("#state = 'active'");
                 $dQuery->where("#jobId = '{$origin->that}'");
                if ($mvc instanceof planning_ProductionNotes) {
                    $dQuery->where("#id != {$rec->id}");
                }
                
                // Ако протокола е по-нов и има детайл към същото задание
                $dQuery->orderBy('id', 'DESC');
                while ($dRec = $dQuery->fetch()) {
                    $cCreatedOn = doc_Containers::fetchField($dRec->containerId, 'createdOn');
                    if ($cCreatedOn > $rec->createdOn) {
                        
                        return planning_ProductionNotes::getHandle($dRec->noteId);
                    }
                }
            }
        }
        
        return false;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if (!$mvc->checkNewerProductionDocument) {

            return;
        }

        $rec = $data->rec;
        if (planning_Setup::get('PRODUCTION_NOTE_REJECTION') != 'no') {

            return;
        }

        if ($rec->state == 'active') {

            // Ако има по нов документ не може да се оттегля документа
            if ($data->toolbar->haveButton("btnDelete{$rec->containerId}")) {
                if ($handle = $mvc->getNewerProductionDocumentHandle($rec)) {
                    $data->toolbar->setError(array("btnDelete{$rec->containerId}"), "Не може да бъде оттеглен, докато има по-нов производствен документ|* #{$handle}");
                }
            }

            // Ако има по нов документ не може да се възстановява документа
        } elseif ($rec->state == 'rejected' && $rec->brState == 'active') {
            if ($data->toolbar->haveButton("btnRestore{$rec->containerId}")) {
                if ($handle = $mvc->getNewerProductionDocumentHandle($rec)) {
                    $data->toolbar->setError(array("btnRestore{$rec->containerId}"), "Не може да бъде възстановен, докато има по-нов производствен документ|* #{$handle}");
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се преди оттеглянето на документа
     */
    public static function on_BeforeReject($mvc, &$res, $id)
    {
        if (!$mvc->checkNewerProductionDocument) {

            return;
        }

        $rec = $mvc->fetchRec($id);
        if ($rec->state == 'active' && planning_Setup::get('PRODUCTION_NOTE_REJECTION') != 'yes') {
            expect(!$mvc->getNewerProductionDocumentHandle($rec));
        }
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }


    /**
     * Изчислява dateIn и dateOut за планирани наличности по протокол
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return array [$dateIn, $dateOut]
     */
    protected static function calcPlannedDates($mvc, $rec)
    {
        $date = !empty($rec->{$mvc->termDateFld}) ? $rec->{$mvc->termDateFld} : (!empty($rec->{$mvc->valiorFld}) ? $rec->{$mvc->valiorFld} : null);
        $horizonAdd = store_Setup::get('PLANNED_DATE_ADDITIVE_IF_IN_THE_PAST');
        $dateIn = $date;
        if (empty($date) || $date < dt::today()) {
            $dateIn = dt::addSecs($horizonAdd, dt::now());
        }
        $dateOut = empty($date) ? $rec->createdOn : $date;

        return array($dateIn, $dateOut);
    }


    /**
     * Изгражда обект за планирана наличност от ред на детайл с тип (input/production)
     *
     * @param stdClass $dRec  - ред от детайл (трябва да има: storeId, productId, totalQuantity, type, generic, canConvert)
     * @param string   $dateIn
     * @param string   $dateOut
     * @return stdClass
     */
    protected static function buildPlannedStockEntry($dRec, $dateIn, $dateOut)
    {
        $genericProductId = null;
        if ($dRec->generic == 'yes') {
            $genericProductId = $dRec->productId;
        } elseif ($dRec->canConvert == 'yes') {
            $genericProductId = planning_GenericMapper::fetchField("#productId = {$dRec->productId}", 'genericProductId');
        }

        $quantityIn = $quantityOut = null;
        if ($dRec->type == 'input') {
            $detailDate = $dateOut;
            $quantityOut = $dRec->totalQuantity;
        } else {
            $detailDate = $dateIn;
            $quantityIn = $dRec->totalQuantity;
        }

        return (object) array(
            'storeId'          => $dRec->storeId,
            'productId'        => $dRec->productId,
            'date'             => $detailDate,
            'quantityIn'       => $quantityIn,
            'quantityOut'      => $quantityOut,
            'genericProductId' => $genericProductId,
        );
    }
}
