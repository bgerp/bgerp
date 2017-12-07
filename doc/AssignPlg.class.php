<?php


/**
 * Клас 'doc_AssignPlg' - Плъгин за възлагане на документи
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_AssignPlg extends core_Plugin
{
    
    
    /**
     * Кой може да възлага
     */
    var $canAssign = 'doc, admin, ceo';
    
    
    /**
     * Кой може да променя активирани записи
     */
    var $canChangerec = 'doc, admin, ceo';
    
    
    /**
     * 
     */
    var $loadList = 'change_Plugin';
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Ако няма такова поле
        if(!$mvc->fields['assign']) {
            
            // Добавяме в модела
            $mvc->FLD('assign', 'keylist(mvc=core_Users, select=nick, where=#state !\\= \\\'rejected\\\', allowEmpty)', 'caption=Възложен на, changable, before=sharedUsers');
            // TODO - да не се показват колабораторите
        }
        
        // Ако няма такова поле
        if(!$mvc->fields['assignedOn']) {
            
            // Добавяме в модела
            $mvc->FLD('assignedOn', 'datetime(format=smartTime)', 'caption=Възложено->На,input=none');
        }
        
        // Ако няма такова поле
        if(!$mvc->fields['assignedBy']) {
            
            // Добавяме в модела
            $mvc->FLD('assignedBy', 'user', 'caption=Възложено->От,input=none');
        }
    }
    
    
    /**
     * Прихваща извикването на AfterInputChanges в change_Plugin
     * 
     * @param core_MVc $mvc
     * @param object $oldRec - Стария запис
     * @param object $newRec - Новия запис
     */
    function on_AfterInputChanges($mvc, $oldRec, $newRec)
    {
        // Вземаме всички записи
        $rec = $mvc->fetch($oldRec->id, '*', FALSE);
        
        // Ако няма промяне, връщаме
        if (($oldRec->assign == $newRec->assign)) return ;
        
        $cu = core_Users::getCurrent();
        
        // URL' то което ще се премахва или показва от нотификациите
        $keyUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
        
        $oldAssignedArr = type_Keylist::toArray($oldRec->assign);
        
        $newAssignedArr = type_Keylist::toArray($newRec->assign);
        
        $removedUsersArr = array_diff($oldAssignedArr, $newAssignedArr);
        if (!empty($removedUsersArr)) {
            
            unset($removedUsersArr[$cu]);
            
            foreach ($removedUsersArr as $oldAssigned) {
                // Премахваме този документ от нотификациите за стария потребител
                bgerp_Notifications::setHidden($keyUrl, 'yes', $oldAssigned);
                
                // Премахваме документа от "Последно" за стария потребител
                bgerp_Recently::setHidden('document', $rec->containerId, 'yes', $oldAssigned);
                
                // Премахваме контейнера от достъпните
                doc_ThreadUsers::removeContainer($rec->containerId);
            }
        }
        
        $notifyUsersArr = array();
        $newUsersArr = array_diff($newAssignedArr, $oldAssignedArr);
        if (!empty($newUsersArr)) {
            foreach ($newUsersArr as $newAssigned) {
                // Премахва цялата информация за даден контейнер
                doc_ThreadUsers::removeContainer($rec->containerId);
                
                // Добавяме документа в нотификациите за новия потреибител
                bgerp_Notifications::setHidden($keyUrl, 'no', $newAssigned);
                
                // Добавяме документа в "Последно" за новия потребител
                bgerp_Recently::setHidden('document', $rec->containerId, 'no', $newAssigned);
                
                // Определяме кой е модифицирал записа
                $newRec->assignedBy = Users::getCurrent();
                
                // Записваме момента на създаването
                $newRec->assignedOn = dt::verbal2Mysql();
                
                // Променяме възложителя
                $newRec->assign = type_Keylist::addKey($newRec->assign, $newAssigned);
                
                $notifyUsersArr[$newAssigned] = $newAssigned;
            }
            
            $mvc->notificateAssigned($newRec, $notifyUsersArr);
        }
    }
    
    
    /**
     * Изпраща нотификация до възложения потребител
     */
    static function on_AfterNotificateAssigned($mvc, $res, $iRec, $notifyUsersArr)
    {
        $cu = core_Users::getCurrent();
        
        unset($notifyUsersArr[$cu]);
        
        if (empty($notifyUsersArr)) return ;
        
        // Вербалния ник на потребителя
        $nick = core_Users::getVerbal($cu, 'nick');
        
        // Манипулатора на документа
        $docHnd = $mvc->getHandle($iRec->id);
        
        // Титлата на документа в долния регистър
        $docSingleTitleLower = mb_strtolower($mvc->singleTitle); 

        // Заглавието на сигнала във НЕвербален вид
        $title = str::limitLen($mvc->getDocumentRow($iRec->id)->recTitle, 90);
        
        // Съобщението, което ще се показва и URL' то
        $message = "{$nick} |възложи|* {$docSingleTitleLower} \"{$title}\"";
        $url = array('doc_Containers', 'list', 'threadId' => $iRec->threadId);
        $customUrl = array('doc_Containers', 'list', 'threadId' => $iRec->threadId, 'docId' => $docHnd, '#' => $docHnd);
        
        // Определяме приоритете на нотификацията
        if ($iRec->priority) {
            
            // Приорите в долен регистър
            switch (strtolower($iRec->priority)) {
                
                case 'normal':
                case 'low':
                    $priority = 'normal';
                break;
                
                case 'warning':
                case 'high':
                    $priority = 'warning';
                break;
                
                case 'alert':
                case 'critical':
                    $priority = 'alert';
                break;
                
                default:
                    ;
                break;
            }
        }
        
        // Ако все още не сме определили приоритете по подразбиране да не нормален
        $priority = ($priority) ? $priority : 'normal';
        
        // Добавяме нотофикация
        foreach ($notifyUsersArr as $assignUserId) {
            bgerp_Notifications::add($message, $url, $assignUserId, $priority, $customUrl);
        }
    }
    
    
	/**
     * Вербалните стойности на датата и възложителя
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($rec->assignedBy) {
            $row->assignedBy = crm_Profiles::createLink($rec->assignedBy);
        }
        
        if ($rec->assign) {
            $row->assign = '';
            foreach (type_Keylist::toArray($rec->assign) as $aId) {
                $row->assign .= $row->assign ? ', ' : '';
                $row->assign .= crm_Profiles::createLink($aId);
            }
        }

        if ($rec->assignedDate) {
            $row->assignedDate = dt::mysql2verbal($rec->assignedDate, 'd-m-Y');    
        }
    }
    
    
    /**
     * Потребителя, на когото е възложена задачата
     */
    function on_AfterGetShared($mvc, &$shared, $id)
    {
        $assignedRec = $mvc->fetch($id, 'assign', FALSE);
        
        $assignedUsersArr = array();
        if ($assignedRec->assign) {
            $assignedUsersArr = type_Keylist::toArray($assignedRec->assign);
        }
        
        if (!empty($assignedUsersArr)) {
            // Обединява с другите шерната потребители
            $shared = keylist::merge($assignedUsersArr, $shared);
        }
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Определяме правата за възлагане
        if ($action == 'assign') {
            
            // Само активните документи могат да се възлат
            if ($rec && $rec->state != 'active') {
                
                // Никой няма такива права, ако не е активен
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        // Ако няма възложено на
        if (!$data->row->assign) {
            
            // Премахваме от и датата
            unset($data->row->assignedOn);
            unset($data->row->assignedBy);
        }
    }
    
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        
        $folderId = $data->form->rec->folderId;
        $threadId = $data->form->rec->threadId;
        
        $interestedUsersArr = array();
        if (!$folderId && $threadId) {
            $folderId = doc_Threads::fetchField($threadId, 'folderId');
        }
        
        if ($folderId) {
            $fRec = doc_Folders::fetch($folderId);
            $interestedUsersArr[$fRec->inCharge] = $fRec->inCharge;
            
            if ($fRec->shared) {
                $interestedUsersArr += type_Keylist::toArray($fRec->shared);
            }
        }
        
        if ($rec->id && isset($rec->sharedUsers)) {
            $interestedUsersArr += type_Keylist::toArray($rec->sharedUsers);
        }
        
        // Ако се създава нов и в папката няма споделени потребители - да се показват всички
        if ((!$rec->id && !$fRec->shared) || empty($interestedUsersArr)) {
            $interestedUsersArr = core_Users::getByRole('powerUser');
        }
        
        // Ако има възложени от предишния път - при редакция/промяна
        if ($rec->assign) {
            $interestedUsersArr += type_Keylist::toArray($rec->assign);
        }
         
        $suggArr = $data->form->fields['assign']->type->prepareSuggestions();
        foreach ($interestedUsersArr as &$nick) {
            if ($suggArr[$nick]) {
                $nick = $suggArr[$nick];
            } else {
                unset($interestedUsersArr[$nick]);
            }
        }
        
        $data->form->setSuggestions('assign', $interestedUsersArr);
    }
}
