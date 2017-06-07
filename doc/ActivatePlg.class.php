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
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        // В записа на формата "тихо" трябва да са въведени от Request originId, threadId или folderId
        $rec = $data->form->rec;
        
        if($rec->id) {
            $exRec = $mvc->fetch($rec->id);
            $mvc->threadId = $exRec->threadId;
        }
        
        if ($mvc->haveRightFor('activate', $exRec)) {
            $data->form->toolbar->addSbBtn('Активиране', 'active', 'id=activate, order=10.00019', 'ef_icon = img/16/lightning.png,title=Активиране на документа');
        }
    }
    
    
    /**
     * Ако е натиснат бутона 'Активиране" добавя състоянието 'active' в $form->rec
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if($form->isSubmitted()) {
            if($form->cmd == 'active') {
                $form->rec->state = 'active';
                $mvc->invoke('BeforeActivation', array($form->rec));
                $form->rec->_isActivated = TRUE;
            }
        }
    }
    
    
 	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
    	if($rec->_isActivated) {
    		unset($rec->_isActivated);
    		$mvc->invoke('AfterActivation', array($rec));
    		$mvc->logWrite('Активиране', $rec->id);
    	}
    }
    
    
    /**
     * Добавяме бутон за активиране на всички документи, които са в състояние чернова
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	// Бутон за заявка
    	if($mvc->haveRightFor('pending', $rec)){
    		if($rec->state != 'pending'){
    			$data->toolbar->addBtn('Заявка', array($mvc, 'changePending', $rec->id), "id=btnRequest,warning=Наистина ли желаете документът да стане заявка?", 'ef_icon = img/16/tick-circle-frame.png,title=Превръщане на документа в заявка');
    		} else{
    			$data->toolbar->addBtn('Чернова', array($mvc, 'changePending', $rec->id), "id=btnDraft,warning=Наистина ли желаете да върнете възможността за редакция?", 'ef_icon = img/16/arrow-undo.png,title=Връщане на възможността за редакция');
    		}
    	}
    	
    	if ($mvc->haveRightFor('activate', $data->rec)) {
            $data->toolbar->addBtn('Активиране', array('doc_Containers', 'activate', 'containerId' => $data->rec->containerId), 'warning=Наистина ли искате да активирате документа?', 'id=btnActivate,ef_icon = img/16/lightning.png,title=Активиране на документа');
        }
    }
    
    
    /**
     * След подготовка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'activate') {
            if (!empty($rec->id) && ($rec->state != 'draft' || !$mvc->haveRightFor('edit', $rec))) {
                if($rec->state != 'pending'){
                	$requiredRoles = 'no_one';
                }
            } else {
                
                $canAction = $action;
                $canAction{0} = strtoupper($canAction{0});
                $canAction = 'can' . $canAction;
                
                // Ако не е зададено кой може да активира, тогава използваме правата за добавяне
                if(isset($mvc->{$canAction})) {
                    $requiredRoles = $mvc->{$canAction};
                } else {
                    $requiredRoles = $mvc->getRequiredRoles('edit', $rec, $userId);
                }
            }
        }
    }
}
