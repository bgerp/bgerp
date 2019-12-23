<?php


/**
 * Клас 'docarch_plg_Archiving' -Плъгин за архивиране на документи
 *
 *
 * @category  bgerp
 * @package   docarch
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class docarch_plg_Archiving extends core_Plugin
{
    /**
     * Добавя бутон за архивиране към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $intfCond = in_array('doc_DocumentIntf',cls::get($mvc)->interfaces);
        
        if ((!core_Packs::isInstalled('docarch'))  || !$intfCond) {
            
            return;
        }
        $rec = &$data->rec;
        $arcivesArr = array();
        $archArr = array();
        
        
        // има ли архиви дефинирани за документи от този клас , или за всякакви документи
        $docClassId = $mvc->getClassId();
        
        $documentContainerId = ($rec->containerId);
        
        $archQuery = docarch_Archives::getQuery();
        
        $archQuery->show('documents');
        
        $archQuery->likeKeylist('documents', $docClassId);
        
        $archQuery->orWhere('#documents IS NULL');
        
        if ($archQuery->count() > 0) {
            while ($arcives = $archQuery->fetch()) {
                $arcivesArr[$arcives->id] = $arcives->id;
            }
            
            // Има ли в тези архиви томове дефинирани да архивират документи, с отговорник текущия потребител
            $volQuery = docarch_Volumes::getQuery();
            
            //В кои томове е архивиран към настоящия момент този документ.
            $balanceDocMove = docarch_Movements::getBalanceOfDocumentMovies($documentContainerId);
            
            if (is_array($balanceDocMove)) {
                foreach ($balanceDocMove as $val) {
                    if ($val->isInArchive == 1) {
                        $archArr[$val->archive] = $val->archive;
                    }
                }
            }
            
            if (!empty($archArr)) {
                foreach ($archArr as $v) {
                    unset($arcivesArr[$v]);
                }
            }
            
            $volQuery->in('archive', $arcivesArr);
            
            $volQuery->where(array("#isForDocuments = 'yes' AND #inCharge = '[#1#]' AND #state = 'active'", core_Users::getCurrent()));
            
            //Дата на документа
            $documentDate = self::getDocumentDate($rec);
            
            //Състояния в които документ неможе да бъде архивиран
            $stateArr = array('draft','pending','stopped');
            
            $stateCond = !in_array($rec->state, $stateArr);
            
            //Ако има том който да отговатя на условията за него, показва бутон за архивиране
            if (($volQuery->count() > 0) && $stateCond) {
                $data->toolbar->addBtn(
                    'Архивиране',
                    array(
                        'docarch_Movements',
                        'Add',
                        'documentId' => $documentContainerId,
                        'documentDate' => $documentDate,
                        'ret_url' => true
                    ),
                                                             'ef_icon=img/16/archive.png,row=2'
                );
            }
        }
    }
    
    
    /**
     * Показва допълнителни действие в doclog история
     *
     * @param core_Master $mvc
     * @param string|null $html
     * @param int         $containerId
     * @param int         $threadId
     */
    public static function on_RenderOtherSummary($mvc, &$html, $containerId, $threadId)
    {
        if (!(core_Packs::isInstalled('docarch'))) {
            
            return;
        }
        $html .= docarch_Movements::getSummary($containerId);
    }
    
    
    /**
     * Определяне дата на документа
     *
     * @param object $rec
     *
     * @return string $docDate дата на документа
     */
    public static function getDocumentDate($rec)
    {
        $docDate = null;
        
        //Възможни дати
        $possibleDate = array('date','valior','closedOn','activatedOn','createdOn');
        
        foreach ($possibleDate as $val) {
            if (!is_null($rec-> {$val})) {
                $docDate = $rec->{$val};
                
                return $docDate;
            }
        }
        
        return $docDate ;
    }
}
