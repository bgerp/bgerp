<?php


/**
 * Клас 'docarch_plg_Archiving' -Плъгин за архивиране на документи
 *
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
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
        $rec = &$data->rec;
        $arcivesArr = array();
        
        // има ли архиви дефинирани за документи от този клас , или за всякакви документи
        $docClassId = $mvc->getClassId();
        
        
        $documentContainerId = ($rec->containerId);
        
        
        $archQuery = docarch_Archives::getQuery();
        $archQuery->show('documents');
        $archQuery->likeKeylist('documents', $docClassId);
        $archQuery->orWhere('#documents IS NULL');
        
        if (! empty($archQuery->fetchAll())) {
            while ($arcives = $archQuery->fetch()) {
                
                $arcivesArr[] = $arcives->id;
            }
            
            // Има ли в тези архиви томове дефинирани да архивират документи, с отговорник текущия потребител
            $volQuery = docarch_Volumes::getQuery();
            $volQuery->in('archive', $arcivesArr);
            $currentUser = core_Users::getCurrent();
            $volQuery->where("#isForDocuments = 'yes' AND #inCharge = ${currentUser} AND #state = 'active'");
            
            //Архивиран ли е този документ
            $mQuery = docarch_Movements::getQuery();
            $mQuery->in('documentId', $documentContainerId);
            
            
            if ($volQuery->count() > 0) {
                if (($mCnt = $mQuery->count()) == 0) {
                    $data->toolbar->addBtn('Архивиране', array('docarch_Movements', 'Add', 'documentId' => $documentContainerId, 'ret_url' => true), 'ef_icon=img/16/archive.png,row=2');
                } else {  
                  //  $data->toolbar->addBtn('Архив|* (' . $mCnt . ')', array('docarch_Movements', 'document' => $documentContainerId, 'ret_url' => true), 'ef_icon=img/16/archive.png,row=2');
                }
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
        $html .= docarch_Movements::getSummary($containerId);
    }
}
