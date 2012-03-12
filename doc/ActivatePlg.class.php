<?php



/**
 * Клас 'doc_DocumentPlg'
 *
 * Плъгин за мениджърите на документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_ActivatePlg extends core_Plugin
{
    
    
    /**
     * Подготвя полетата threadId и folderId, ако има originId и threadId
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        // В записа на формата "тихо" трябва да са въведени от Request originId, threadId или folderId
        $rec = $data->form->rec;
        
        if($rec->id) {
            $exRec = $mvc->fetch($rec->id);
            $mvc->threadId = $exRec->threadId;
        }
        
        if($exRec) {
            $state = $exRec->state;
        } else {
            $state = 'draft';
        }
        
        if (($state == 'draft') && ($mvc->haveRightFor('activation', $exRec))) {
            $data->form->toolbar->addSbBtn('Активиране', 'active', 'class=btn-activation,order=9');
        }
    }
    
    
    /**
     * Ако е натиснат бутона 'Активиране" добавя състоянието 'active' в $form->rec
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if($form->isSubmitted()) {
            if($form->cmd == 'active') {
                $form->rec->state = 'active';
                $mvc->invoke('Activation', array($form->rec));
            }
        }
    }
    
    
    /**
     * Добавяме бутон за активиране на всички документи, които са в състояние чернова
     */
    function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if (($data->rec->state == 'draft') && ($mvc->haveRightFor('activation', $data->rec))) {
            $data->toolbar->addBtn('Активиране', array('doc_Containers', 'activate', 'containerId' => $data->rec->containerId), 'class=btn-activation');
        }
    }
}