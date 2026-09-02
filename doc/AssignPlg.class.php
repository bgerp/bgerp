<?php


/**
 * Клас 'doc_AssignPlg' - Плъгин за възлагане на документи
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_AssignPlg extends core_Plugin
{
    /**
     * Кой може да възлага
     */
    public $canAssign = 'doc, admin, ceo';
    
    
    /**
     * Кой може да променя активирани записи
     */
    public $canChangerec = 'doc, admin, ceo';
    
    
    public $loadList = 'change_Plugin';
    
    
    /**
     * Извиква се след описанието на модела
     */
    public function on_AfterDescription(&$mvc)
    {
        // Ако няма такова поле
        if (empty($mvc->fields['assign'])) {
            // Добавяме в модела
            $mvc->FLD('assign', 'keylist(mvc=core_Users, select=nick)', 'caption=Възлагане на, changable, before=sharedUsers, optionsFunc=doc_AssignPlg::getUsersForAssign');
        }
        
        // Ако няма такова поле
        if (empty($mvc->fields['assignedOn'])) {
            
            // Добавяме в модела
            $mvc->FLD('assignedOn', 'datetime(format=smartTime)', 'caption=Възложено->На,input=none');
        }
        
        // Ако няма такова поле
        if (empty($mvc->fields['assignedBy'])) {
            
            // Добавяме в модела
            $mvc->FLD('assignedBy', 'user', 'caption=Възложено->От,input=none');
        }
        
        $mvc->autoShareFields = arr::make($mvc->autoShareFields ?? null, true);
        $mvc->autoShareFields['assign'] = 'assign';
    }
    
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        // Към възложените потребители, добавяме споделените в ричтекста
        if ($form->isSubmitted()) {
            $assignedUsersArrAll = self::getRichtextNicksArr($mvc, $rec);

            if (!empty($assignedUsersArrAll)) {
                $oRec = null;
                $oldAssignedArr = $oldNicksArr = $removedUsersArr = array();

                // При редактиране - старите възложени, никовете, споменати преди редакцията,
                // и премахнатите в момента от възложените
                if (!empty($rec->id)) {
                    $oRec = $mvc->fetch($rec->id, '*', false);
                    if (is_object($oRec)) {
                        $oldAssignedArr = type_Keylist::toArray($oRec->assign);
                        $oldNicksArr = self::getRichtextNicksArr($mvc, $oRec);
                        $removedUsersArr = array_diff($oldAssignedArr, type_Keylist::toArray($rec->assign));
                    }
                }

                $toShareArr = array();
                foreach ((array) $assignedUsersArrAll as $nick) {
                    $nick = strtolower($nick);
                    $id = core_Users::fetchField(array("LOWER(#nick) = '[#1#]'", $nick), 'id');

                    // Партнюрите да не са споделение
                    if (core_Users::haveRole('partner', $id)) {
                        continue;
                    }

                    // Ако потребителя е премахнат в момента от възложените или е бил споменат и преди
                    // редакцията, без да е бил възложен - значи веднъж вече е премахнат нарочно.
                    // Не се добавя отново, а само се споделя документа с него
                    if (isset($removedUsersArr[$id]) || (isset($oldNicksArr[$nick]) && !isset($oldAssignedArr[$id]))) {
                        $toShareArr[$id] = $id;

                        continue;
                    }

                    $rec->assign = type_Keylist::addKey($rec->assign, $id);
                }

                // Премахнатите от възложените, ги мърджваме към споделените, ако има такова поле
                if (!empty($toShareArr)) {
                    foreach (array('sharedUsers') as $sName) {
                        if (!$mvc->getField($sName, false)) {
                            continue;
                        }

                        // Ако полето не е било във формата, взимаме стойността от записа в базата
                        $sharedUsers = $rec->$sName ?? null;
                        if (!isset($rec->$sName) && is_object($oRec)) {
                            $sharedUsers = $oRec->$sName ?? null;
                        }

                        $rec->$sName = type_Keylist::merge($sharedUsers, type_Keylist::fromArray($toShareArr));
                    }
                }
            }
        }
    }


    /**
     * Връща никовете на потребителите, споменати в ричтекст полетата на записа
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     *
     * @return array - масив с никове в долен регистър
     */
    protected static function getRichtextNicksArr($mvc, $rec)
    {
        $nicksArrAll = array();

        foreach ((array) $mvc->fields as $name => $field) {
            if ($field->type instanceof type_Richtext) {
                if (($field->type->params['nickToLink'] ?? null) == 'no') {
                    continue;
                }

                $usersArr = rtac_Plugin::getNicksArr($rec->$name ?? null);
                if (empty($usersArr)) {
                    continue;
                }

                $nicksArrAll = array_merge($nicksArrAll, $usersArr);
            }
        }

        return $nicksArrAll;
    }

    
    /**
     * Прихваща извикването на AfterInputChanges в change_Plugin
     *
     * @param core_MVc $mvc
     * @param object   $oldRec - Стария запис
     * @param object   $newRec - Новия запис
     */
    public function on_AfterInputChanges($mvc, $oldRec, $newRec)
    {
        // Вземаме всички записи
        $rec = $mvc->fetch($oldRec->id ?? null, '*', false);
        
        // Ако няма промяне, връщаме
        if (($oldRec->assign ?? null) == ($newRec->assign ?? null)) {
            
            return ;
        }
        
        $cu = core_Users::getCurrent();
        
        // URL' то което ще се премахва или показва от нотификациите
        $keyUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
        
        $oldAssignedArr = type_Keylist::toArray($oldRec->assign ?? null);
        
        $newAssignedArr = type_Keylist::toArray($newRec->assign ?? null);
        
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
    public static function on_AfterNotificateAssigned($mvc, $res, $iRec, $notifyUsersArr)
    {
        $cu = core_Users::getCurrent();
        
        unset($notifyUsersArr[$cu]);
        
        if (empty($notifyUsersArr)) {
            
            return ;
        }
        
        // Вербалния ник на потребителя
        $nick = core_Users::getVerbal($cu, 'nick');
        
        // Манипулатора на документа
        $docHnd = $mvc->getHandle($iRec->id);
        
        // Титлата на документа в долния регистър
        $docSingleTitleLower = mb_strtolower($mvc->singleTitle);
        
        // Заглавието на сигнала във НЕвербален вид
        Mode::push('getNotificationRecTitle', true);
        $title = str::limitLen($mvc->getDocumentRow($iRec->id)->recTitle, 90);
        Mode::pop('getNotificationRecTitle');

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
    public static function on_BeforeSave($mvc, &$id, $rec, $saveFields = null)
    {
        if (!empty($rec->assign)) {
            if (!isset($rec->assignedOn) && !isset($rec->assignedBy)) {
                $update = false;
                $oRec = null;
                if (!empty($rec->id)) {
                    $oRec = $mvc->fetch($rec->id, null, false);
                } else {
                    $update = true;
                }

                if (is_object($oRec) && $rec->assign != $oRec->assign) {
                    $update = true;
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
    public function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if (!empty($rec->assignedBy)) {
            $row->assignedBy = crm_Profiles::createLink($rec->assignedBy);
        }
        
        if (!empty($rec->assign)) {
            $row->assign = '';
            foreach (type_Keylist::toArray($rec->assign) as $aId) {
                $row->assign .= $row->assign ? ', ' : '';
                $row->assign .= crm_Profiles::createLink($aId);
            }
        }
        
        if (!empty($rec->assignedOn)) {
            $row->assignedOn = $mvc->getFieldType('assignedOn')->toVerbal($rec->assignedOn);
        }
    }
    
    
    /**
     * Потребителя, на когото е възложена задачата
     */
    public function on_AfterGetShared($mvc, &$shared, $id)
    {
        $assignedRec = $mvc->fetch($id, 'assign', false);
        
        $assignedUsersArr = array();
        if (!empty($assignedRec->assign)) {
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
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
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
    
    
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        // Ако няма възложено на
        if (empty($data->row->assign)) {
            
            // Премахваме от и датата
            unset($data->row->assignedOn);
            unset($data->row->assignedBy);
        }
    }
    
    
    /**
     * Преди записване на клонирания запис
     *
     * @param core_Mvc $mvc
     * @param object   $rec
     * @param object   $nRec
     *
     * @see plg_Clone
     */
    public function on_BeforeSaveCloneRec($mvc, $rec, $nRec)
    {
        unset($nRec->assignedOn);
        unset($nRec->assignedBy);
    }
    
    
    /**
     * Връща всички потребители, на които може да се възлага документа
     *
     * @param type_Keylist $type
     * @param NULL|array   $options
     */
    public static function getUsersForAssign($type, $options)
    {
        $type = 'users';
        $handle = 'assignUsers';
        $keepMinute = 1000;
        $depends = array('core_Users');
        
        $resArr = core_Cache::get($type, $handle, $keepMinute, $depends);
        
        if ($resArr === false) {
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

                if ($fRec) {
                    $interestedUsersArr = array();

                    if (!empty($fRec->shared)) {
                        $interestedUsersArr += type_Keylist::toArray($fRec->shared);
                    }

                    if (!empty($fRec->inCharge)) {
                        $interestedUsersArr[$fRec->inCharge] = $fRec->inCharge;
                    }

                    foreach ($interestedUsersArr as $uId) {
                        $uNames = $resArr[$uId] ?? null;
                        if (isset($uNames)) {
                            unset($resArr[$uId]);
                            $resArr = array($uId => $uNames) + $resArr;
                        }
                    }
                }
            }
            
            core_Cache::set($type, $handle, $resArr, $keepMinute, $depends);
        }
        
        // Текущият потребител да е най-отгоре
        if (!empty($resArr)) {
            $cu = core_Users::getCurrent();
            $cuNames = $resArr[$cu] ?? null;
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
        if (empty($data->form->rec->id)) {
            $defUsersArr = $mvc->getDefaultAssignUsers($data->form->rec);
            
            if ($defUsersArr) {
                $data->form->setDefault('assign', $defUsersArr);
            }
        }
    }
    
    
    /**
     * Връща потребителите по подразбиране за споделяне
     *
     * @param core_Mvc    $mvc
     * @param NULL|string $res
     * @param stdClass    $rec
     */
    public static function on_AfterGetDefaultAssignUsers($mvc, &$res, $rec)
    {
        $folderId = $rec->folderId ?? null;
        
        if (!$folderId && !empty($rec->threadId)) {
            $folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
        }
        
        $assignUsers = null;
        
        if ($folderId) {
            
            // Използваме последните 3 създадени документа в тази папка
            
            $cu = core_Users::getCurrent();
            
            $minLimit = 3;
            
            $mQuery = $mvc->getQuery();
            $mQuery->where(array("#folderId = '[#1#]'", $folderId));
            $mQuery->where(array("#createdBy = '[#1#]'", $cu));
            
            $mQuery->where("#state != 'rejected'");
            $mQuery->where("#state != 'draft'");
            
            $mQuery->orderBy('#createdOn', 'DESC');
            $mQuery->limit($minLimit);
            
            $mQuery->show('assign');
            
            if ($mQuery->count() >= $minLimit) {
                $aArr = array();
                while ($mRec = $mQuery->fetch()) {
                    if (!$mRec->assign) {
                        break;
                    }
                    
                    // Уеднакяваме полето за възложени
                    $assignArr = type_Keylist::toArray($mRec->assign);
                    asort($assignArr);
                    $aStr = type_Keylist::fromArray($assignArr);
                    
                    $aArr[$aStr] = ($aArr[$aStr] ?? 0) + 1;
                }
                
                if (countR($aArr) == 1) {
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
