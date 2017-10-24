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
            $mvc->FLD('assign', 'user(roles=user)', 'caption=Възложен на,input=none, changable');
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
        // На кого е била възложена задачата преди това
        $oldAssigned = $oldRec->assign;
        
        // На кого е възложено сега
        $newAssigned = $newRec->assign;

        // Вземаме всички записи
        $rec = $mvc->fetch($oldRec->id);
    
        // Ако няма промяне, връщаме
        if (($oldAssigned == $newAssigned)) return ;
        
        // URL' то което ще се премахва или показва от нотификациите
        $keyUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
        
        // Ако е била възложена на някой друг преди това
        if ($oldAssigned) {
            
            // Премахваме този документ от нотификациите за стария потребител
            bgerp_Notifications::setHidden($keyUrl, 'yes', $oldAssigned);
            
            // Премахваме документа от "Последно" за стария потребител
            bgerp_Recently::setHidden('document', $rec->containerId, 'yes', $oldAssigned);
            
            // Премахваме контейнера от достъпните
            doc_ThreadUsers::removeContainer($rec->containerId);
        }
        
        // Ако има нов възложен
        if ($newAssigned) {

            // Премахваме контейнера от достъпните
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
            $newRec->assign = $newAssigned;
            
            // Нотифицираме възложения потребител
            $mvc->notificateAssigned($newRec);
            
            // Името на документа
//            $docSingleTitle = mb_strtolower($mvc->singleTitle); 
            
            // Добавяме съобщение
//            status_Messages::newStatus("|Успешно възложихте|* {$docSingleTitle} |на|*: " . $mvc->getVerbal($newRec, 'assign'));
        }
    }
    
    
    /**
     * Дефолт имплементацията на notificateAssigned($id)
     * Изпраща нотификация до възложения потребител
     */
    static function on_AfterNotificateAssigned($mvc, $res, $iRec)
    {
        // id на записа
        $id = $iRec->id;
        
        // Нишката
        $threadId = $iRec->threadId;
        
        // Документа
        $containerId = $iRec->containerId;
        
        // Потребителя, на който е възложен
        $assignUserId = $iRec->assign;
        
        // id' то на потребителя, който възлага задачата
        $currUserId = core_Users::getCurrent('id');
        
        // Ако, възлагащия също е отговорник
        if ($assignUserId == $currUserId) return ;
        
        // Вербалния ник на потребителя
        $nick = core_Users::getVerbal($currUserId, 'nick');
        
        // Манипулатора на документа
        $docHnd = $mvc->getHandle($id);
        
        // Титлата на документа в долния регистър
        $docSingleTitleLower = mb_strtolower($mvc->singleTitle); 

        // Заглавието на сигнала във НЕвербален вид
        $title = str::limitLen($mvc->getDocumentRow($id)->recTitle, 90);
        
        // Съобщението, което ще се показва и URL' то
        $message = "{$nick} |възложи|* {$docSingleTitleLower}: \"{$title}\"";
        $url = array('doc_Containers', 'list', 'threadId' => $threadId);
        $customUrl = array('doc_Containers', 'list', 'threadId' => $threadId, 'docId' => $docHnd, '#' => $docHnd);
        
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
        bgerp_Notifications::add($message, $url, $assignUserId, $priority, $customUrl);
    }
    
    
	/**
     * Вербалните стойности на датата и възложителя
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако има assignedBy
        if ($rec->assignedBy) {
            
            // Вербална стойност
            $row->assignedBy = crm_Profiles::createLink($rec->assignedBy);
        }
        
        // Ако има assign
        if ($rec->assign) {
            
            // Вербална стойност
            $row->assign = crm_Profiles::createLink($rec->assign);
        }

        // Ако има данни
        if ($rec->assignedDate) {
            
            // Вербалната стойност
            $row->assignedDate = dt::mysql2verbal($rec->assignedDate, 'd-m-Y');    
        }
    }
    
    
    /**
     * Потребителя, на когото е възложена задачата
     */
    function on_AfterGetShared($mvc, &$shared, $id)
    {
        // Вземаме записите
        $assignedRec = $mvc->fetch($id, 'assign, assignedBy', FALSE);
        
        // Възложен на
        $assignedUser = $assignedRec->assign;
        
        // Възложен от
        $assignedBy = $assignedRec->assignedBy;

        // Ако възложителят е различен от възложения
        if ($assignedUser != $assignedBy) {
            
            // Обединява с другите шерната потребители
            $shared = keylist::merge($assignedUser, $shared);   
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
}
