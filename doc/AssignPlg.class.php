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
            $mvc->FLD('assign', 'keylist(mvc=core_Users, select=nick)', 'caption=Възлагане на, changable, before=sharedUsers, optionsFunc=doc_AssignPlg::getUsersForAssign');
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
     * Проверява и допълва въведените данни от 'edit' формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        // Към възложените потребители, добавяме споделените в ричтекста
        if ($form->isSubmitted()) {
            
            $assignedUsersArrAll = array();
            
            foreach ((array)$mvc->fields as $name => $field) {
                if ($field->type instanceof type_Richtext) {
                    
                    if ($field->type->params['nickToLink'] == 'no') continue;
                    
                    $usersArr = rtac_Plugin::getNicksArr($rec->$name);
                    if (empty($usersArr)) continue;
                    
                    $assignedUsersArrAll = array_merge($assignedUsersArrAll, $usersArr);
                }
            }
            
            if (!empty($assignedUsersArrAll)) {
                
                foreach ((array)$assignedUsersArrAll as $nick) {
                    $nick = strtolower($nick);
                    $id = core_Users::fetchField(array("LOWER(#nick) = '[#1#]'", $nick), 'id');
                    
                    // Партнюрите да не са споделение
                    if (core_Users::haveRole('partner', $id)) continue;
                    
                    $rec->assign = type_Keylist::addKey($rec->assign, $id);
                }
            }
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
        
        // Добавяме нотофикация
        foreach ($notifyUsersArr as $assignUserId) {
            bgerp_Notifications::add($message, $url, $assignUserId, $iRec->priority, $customUrl);
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if ($rec->assign) {
            if (!isset($rec->assignedOn) && !isset($rec->assignedBy)) {
                $update = FALSE;
                if ($rec->id) {
                    $oRec = $mvc->fetch($rec->id, NULL, FALSE);
                } else {
                    $update = TRUE;
                }
                
                if ($rec->assign != $oRec->assign) {
                    $update = TRUE;
                }
                
                if ($update) {
                    $rec->assignedBy = Users::getCurrent();
                    $rec->assignedOn = dt::verbal2Mysql();
                }
            }
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
     * Преди записване на клонирания запис
     *
     * @param core_Mvc $mvc
     * @param object $rec
     * @param object $nRec
     *
     * @see plg_Clone
     */
    function on_BeforeSaveCloneRec($mvc, $rec, $nRec)
    {
        unset($nRec->assignedOn);
        unset($nRec->assignedBy);
    }
    
    
    /**
     * Връща всички потребители, на които може да се възлага документа
     * 
     * @param type_Keylist $type
     * @param NULL|array $options
     */
    public static function getUsersForAssign($type, $options)
    {
        $type = 'users';
        $handle = 'assignUsers';
        $keepMinute = 1000;
        $depends = array('core_Users');
        
        $resArr = core_Cache::get($type, $handle, $keepMinute, $depends);
        
        if ($resArr === FALSE) {
            $uQuery = core_Users::getQuery();
            
            $uQuery->where("#state != 'rejected'");
            
            $powId = core_Roles::fetchByName('powerUser');
            
            if ($powId) {
                $uQuery->like('roles', "|{$powId}|");
            }
            
            $uQuery->orderBy('nick');
            
            // Текущия потребител да е най-отгоре
            
            $resArr = array();
            while ($uRec = $uQuery->fetch()) {
                $resArr[$uRec->id] = type_Nick::normalize($uRec->nick) . ' (' . core_Users::prepareUserNames($uRec->names) . ')';
            }
            
            // Собственика на папката и споделените да са най-отгоре
            if ($folderId = Request::get('folderId')) {
                $fRec = doc_Folders::fetch($folderId);
                
                $interestedUsersArr = array();
                
                if ($fRec->shared) {
                    $interestedUsersArr += type_Keylist::toArray($fRec->shared);
                }
                
                $interestedUsersArr[$fRec->inCharge] = $fRec->inCharge;
                
                foreach ($interestedUsersArr as $uId) {
                    $uNames = $resArr[$uId];
                    if (isset($uNames)) {
                        unset($resArr[$uId]);
                        $resArr = array($uId => $uNames) + $resArr;
                    }
                }
            }
            
            core_Cache::set($type, $handle, $resArr, $keepMinute, $depends);
        }
        
        // Текущият потребител да е най-отгоре
        if (!empty($resArr)) {
            $cu = core_Users::getCurrent();
            $cuNames = $resArr[$cu];
            if (isset($cuNames)) {
                unset($resArr[$cu]);
                $resArr = array($cu => $cuNames) + $resArr;
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Подготовка на формата за добавяне/редактиране
     * 
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        if (!$data->form->rec->id) {
            $defUsersArr = $mvc->getDefaultAssignUsers($data->form->rec);
            
            if ($defUsersArr) {
                $data->form->setDefault('assign', $defUsersArr);
            }
        }
    }
    
    
    /**
     * Връща потребителите по подразбиране за споделяне
     * 
     * @param core_Mvc $mvc
     * @param NULL|string $res
     * @param stdClass $rec
     */
    public static function on_AfterGetDefaultAssignUsers($mvc, &$res, $rec)
    {
        $folderId = $rec->folderId;
        
        if (!$folderId && $rec->threadId) {
            $folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
        }
        
        $assignUsers = NULL;
        
        if ($folderId) {
            
            // Използваме последните 3 създадени документа в тази папка
            
            $cu = core_Users::getCurrent();
            
            $minLimit = 3;
            
            $mQuery = $mvc->getQuery();
            $mQuery->where(array("#folderId = '[#1#]'", $folderId));
            $mQuery->where(array("#createdBy = '[#1#]'", $cu));
            
            $mQuery->where("#state != 'rejected'");
            $mQuery->where("#state != 'draft'");
            
            $mQuery->orderBy("#createdOn", 'DESC');
            $mQuery->limit($minLimit);
            
            $mQuery->show('assign');
            
            if ($mQuery->count() >= $minLimit) {
                while ($mRec = $mQuery->fetch()) {
                    
                    if (!$mRec->assign) break;
                    
                    // Уеднакяваме полето за възложени
                    $assignArr = type_Keylist::toArray($mRec->assign);
                    asort($assignArr);
                    $aStr = type_Keylist::fromArray($assignArr);
                    
                    $aArr[$aStr]++;
                }
                
                if (count($aArr) == 1) {
                    $assignUsers = key($aArr);
                }
            }
            
            // Ако няма други споделени и ако е в папка на текущия потребител
            if (!$assignUsers) {
                $fIncharge = doc_Folders::fetchField($folderId, 'inCharge');
                if ($fIncharge == $cu) {
                    $assignUsers = '|' . $fIncharge . '|';
                }
            }
        }
        
        if ($assignUsers) {
            $res = type_Keylist::merge($res, $assignUsers);
        }
    }
}
