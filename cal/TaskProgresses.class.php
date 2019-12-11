<?php


/**
 * Клас 'cal_TaskProgresses'
 *
 * @title Отчитане изпълнението на задачите
 *
 * @category  bgerp
 * @package   cal
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @deprecated
 */
class cal_TaskProgresses extends core_Detail
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, cal_Wrapper, plg_Rejected';
    
    
    public $canEdit = 'no_one';
    
    
    public $canDelete = 'no_one';
    
    
    public $canReject = 'powerUser';
    
    
    /**
     * Заглавие
     */
    public $title = 'Прогрес по задачите';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'createdOn,createdBy,message,progress,workingTime,expectationTime';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/task.png';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleTitle = 'прогрес';
    
    
    public $canAdd = 'no_one';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Задачи';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // id на задачата
        $this->FLD('taskId', 'key(mvc=cal_Tasks,select=title)', 'caption=Задача,input=hidden,silent,column=none');
        
        // Каква част от задачата е изпълнена?
        $this->FLD('progress', 'percent(min=0,max=1,decimals=0)', 'caption=Прогрес');
        
        // Колко време е отнело изпълнението?
        $this->FLD('workingTime', 'time(suggestions=10 мин.|30 мин.|60 мин.|2 часа|3 часа|5 часа|10 часа)', 'caption=Отработено време');
        
        // Статус съобщение
        $this->FLD('message', 'richtext(rows=5, bucket=calTasks)', 'caption=Съобщение');
        
        $this->FLD('state', 'enum(active=Активирано,rejected=Оттеглено)', 'caption=Състояние,column=none,input=none,notNull,forceField');
        
        $this->setDbIndex('createdOn');
    }
    
    
    /**
     * Проверява дали има модел в БД
     *
     * @return bool
     */
    public static function isInstalled()
    {
        $Progresses = cls::get(get_called_class());
        
        if ($Progresses->db->tableExists($Progresses->dbTableName)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Връща създателя на последния прогрес
     *
     * @param int  $taskId
     * @param bool $removeRejected
     *
     * @return FALSE|stdClass
     */
    public static function getLastProgressAuthor($taskId, $removeRejected = true)
    {
        $query = self::getQuery();
        $query->where(array("#taskId = '[#1#]'", $taskId));
        
        if ($removeRejected) {
            $query->where("#state != 'rejected'");
        }
        
        $query->orderBy('createdOn', 'DESC');
        
        $query->limit(1);
        
        $query->show('createdBy');
        
        $lProggress = $query->fetch();
        
        $author = '';
        if ($lProggress) {
            $author = self::getVerbal($lProggress, 'createdBy');
        }
        
        return $author;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->FNC('notifyUsers', 'type_Keylist(mvc=core_Users, select=nick, where=#state !\\= \\\'rejected\\\', allowEmpty)', 'caption=Нотификация, input');
        
        if ($taskId = $data->form->rec->taskId) {
            $tRec = cal_Tasks::fetch($taskId);
            
            $notifyUsersArr = type_Users::toArray($tRec->assign);
            
            if ($tRec->createdBy > 0) {
                $notifyUsersArr[$tRec->createdBy] = $tRec->createdBy;
            }
            
            $interestedUsersArr = $notifyUsersArr;
            
            // Добавяме отговорника и споделените на папката
            if ($tRec->folderId) {
                $fRec = doc_Folders::fetch($tRec->folderId);
                $interestedUsersArr[$fRec->inCharge] = $fRec->inCharge;
                
                if ($fRec->shared) {
                    $interestedUsersArr += type_Keylist::toArray($fRec->shared);
                }
            }
            
            if ($tRec->sharedUsers) {
                $interestedUsersArr += type_Keylist::toArray($tRec->sharedUsers);
            }
            
            $cu = core_Users::getCurrent();
            unset($notifyUsersArr[$cu]);
            unset($interestedUsersArr[$cu]);
            
            $suggArr = $data->form->fields['notifyUsers']->type->prepareSuggestions();
            
            // Показваме само хората, които имат връзка със задачата или папката
            foreach ($interestedUsersArr as &$nick) {
                if ($suggArr[$nick]) {
                    $nick = $suggArr[$nick];
                } else {
                    unset($interestedUsersArr[$nick]);
                }
            }
            
            if (empty($interestedUsersArr)) {
                $data->form->setField('notifyUsers', 'input=none');
            }
            $data->form->setSuggestions('notifyUsers', $interestedUsersArr);
            
            if (!empty($interestedUsersArr) && !empty($notifyUsersArr)) {
                $data->form->setDefault('notifyUsers', $notifyUsersArr);
            }
        }
        
        expect($data->form->rec->taskId);
        
        $Driver = $mvc->Master->getDriver($data->form->rec->taskId);
        
        $mRec = $mvc->Master->fetch($data->form->rec->{$mvc->masterKey});
        
        $progressArr = $Driver->getProgressSuggestions($mRec);
        
        if ($mRec->progress) {
            $pVal = $mRec->progress * 100;
            Mode::push('text', 'plain');
            $pVal = $mvc->fields['progress']->type->toVerbal($mRec->progress);
            Mode::pop('text');
            if (!isset($progressArr[$pVal])) {
                $progressArr[$pVal] = $pVal;
                ksort($progressArr, SORT_NUMERIC);
            }
            $data->form->setDefault('progress', $mRec->progress);
        }
        
        $data->form->setSuggestions('progress', $progressArr);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        expect($form->rec->taskId);
        
        $masterRec = cal_Tasks::fetch($form->rec->taskId);
        
        // ако формата е събмитната
        if ($form->isSubmitted() && isset($masterRec->progress)) {
            if ($masterRec->progress > $form->rec->progress) {
                $form->setWarning('progress', '|Въвели сте по-малък прогрес от предишния. Сигурни ли сте, че искате да продължите?');
            } elseif ($masterRec->progress == $form->rec->progress) {
                $form->setWarning('progress', '|Въвели сте прогрес равен на предишния. Сигурни ли сте, че искате да продължите?');
            }
        }
    }
    
    
    /**
     * Изпълнява се след опаковане на детайла от мениджъра
     *
     * @param stdClass $data
     */
    public function renderDetail($data)
    {
        if (!countR($data->recs)) {
            
            return;
        }
        
        $tpl = new ET('<div class="clearfix21 portal" style="margin-top:20px;background-color:transparent;">
                            <div class="legend" style="background-color:#ffc;font-size:0.9em;padding:2px;color:black">Прогрес</div>
                            <div class="listRows">
                            [#TABLE#]
                            </div>
	                   </div>
	                ');
        $tpl->replace($this->renderListTable($data), 'TABLE');
        
        return $tpl;
    }
    
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    public static function on_AfterRenderListTable($mvc, &$res, $data)
    {
        if (!countR($data->recs)) {
            
            return;
        }
        
        if (Mode::is('screenMode', 'narrow')) {
            $res = new ET(' <table class="listTable progress-table"> 
									<!--ET_BEGIN COMMENT_LI-->
										<tr>
	                               			<td>
												<span class="nowrap">[#createdOn#]</span>&nbsp;	
												[#createdBy#]&nbsp; 
												[#progress#]&nbsp;
												<span class="nowrap">[#workingTime#]</span> 
												
											</td>
											<td>
												[#message#]
											</td>
										</tr>
										
									<!--ET_END COMMENT_LI-->
								</table>
                ');
            
            foreach ($data->recs as $rec) {
                $row = $mvc->recToVerbal($rec);
                
                $cTpl = $res->getBlock('COMMENT_LI');
                $cTpl->placeObject($row);
                $cTpl->removeBlocks();
                $cTpl->append2master();
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = null)
    {
        $tRec = cal_Tasks::fetch($rec->taskId);
        $now = dt::now();
        
        $msg = 'Добавен прогрес към задачата';
        
        $removeOldNotify = false;
        
        // Определяне на прогреса
        if (isset($rec->progress)) {
            if (($tRec->progress != $rec->progress) || ($rec->state == 'rejected')) {
                $oldProgress = $tRec->progress;
                $tRec->progress = $rec->progress;
                
                if ($rec->state == 'rejected') {
                    $lGoodProgress = self::getLastGoodProgress($tRec->id);
                }
                
                $tRec->progress = ($rec->state != 'rejected') ? $rec->progress : $lGoodProgress;
                
                // При прогрес на 100% нотифицираме и създателя на задачата
                if (($rec->progress == 1 && $rec->state != 'rejected') || ($rec->state == 'rejected' && $lGoodProgress == 1)) {
                    if ($tRec->state != 'closed' && $tRec->state != 'stopped') {
                        $cu = core_Users::getCurrent();
                        
                        if ($tRec->createdBy > 0 && $tRec->createdBy != $cu) {
                            if (!type_Keylist::isIn($cu, $rec->notifyUsers)) {
                                $rec->notifyUsers = type_Keylist::addKey($rec->notifyUsers, $tRec->createdBy);
                            }
                        }
                        
                        $tRec->brState = $tRec->state;
                        $tRec->state = 'closed';
                        $tRec->timeClosed = $now;
                        
                        $msg = 'Приключена е задачата';
                        
                        $removeOldNotify = true;
                    }
                }
                
                // Ако е в затворено състояно, връщаме предишното състояние
                if (($tRec->progress != 1) && (($rec->progress != $oldProgress) || $rec->state == 'rejected')) {
                    if (($tRec->state == 'closed') || ($tRec->state == 'stopped')) {
                        if ($tRec->state != $tRec->brState) {
                            $tState = $tRec->state;
                            $tRec->state = $tRec->brState;
                            $tRec->brState = $tState;
                            
                            if (!$tRec->state) {
                                $tRec->state = 'active';
                            }
                            
                            cal_Tasks::save($tRec, 'brState, state');
                        }
                    }
                }
            }
        }
        
        $notifyUsersArr = type_Keylist::toArray($rec->notifyUsers);
        
        cal_Tasks::notifyForChanges($tRec, $msg, $notifyUsersArr, $removeOldNotify);
        
        // Определяне на отработеното време
        if (isset($rec->workingTime) || ($rec->state == 'rejected')) {
            $query = self::getQuery();
            $query->where("#taskId = {$tRec->id}");
            $query->where("#state != 'rejected'");
            $query->XPR('workingTimeSum', 'int', 'sum(#workingTime)');
            $rec = $query->fetch();
            $tRec->workingTime = $rec->workingTimeSum;
        }
        
        $sharedUsersArr = rtac_Plugin::getNicksArr($rec->message);
        
        // Ако има споделяния
        if ($sharedUsersArr && !empty($sharedUsersArr)) {
            
            // Добавяме id-тата на споделените потребители
            foreach ((array) $sharedUsersArr as $nick) {
                $nick = strtolower($nick);
                $id = core_Users::fetchField(array("LOWER(#nick) = '[#1#]'", $nick), 'id');
                $tRec->sharedUsers = type_Keylist::addKey($tRec->sharedUsers, $id);
            }
            
            doc_Containers::changeNotifications($tRec, null, $tRec->sharedUsers);
        }
        
        cal_Tasks::save($tRec);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec->taskId)) {
            if ($requiredRoles == 'no_one') {
                
                return;
            }
            
            if (!$mvc->Master->haveRightFor('single', $rec->taskId)) {
                $requiredRoles = 'no_one';
            } else {
                $mRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     *
     *
     * @param stdClass $data
     */
    public function prepareDetail_($data)
    {
        $data->TabCaption = 'Прогрес';
        $data->Tab = 'top';
        
        $res = parent::prepareDetail_($data);
        
        if (empty($data->recs)) {
            $data->disabled = true;
        }
        
        return $res;
    }
    
    
    /**
     * Реализация по подразбиране на метода $mvc->reject($id)
     *
     * @param core_Mvc     $mvc
     * @param mixed        $res
     * @param int|stdClass $id
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        $mvc->updateTaskProgress($rec->taskId, 'reject');
    }
    
    
    /**
     * Възстановяване на оттеглен обект
     *
     * Реализация по подразбиране на метода $mvc->restore($id)
     *
     * @param core_Mvc     $mvc
     * @param mixed        $res
     * @param int|stdClass $id
     */
    public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = cal_Tasks::fetchRec($id);
        $tRec = cal_Tasks::fetch($rec->taskId);
        
        $rec->notifyUsers = $tRec->assign;
        
        $rec->notifyUsers = type_Keylist::addKey($rec->notifyUsers, $tRec->createdBy);
    }
    
    
    /**
     * Възстановяване на оттеглен обект
     *
     * Реализация по подразбиране на метода $mvc->restore($id)
     *
     * @param core_Mvc     $mvc
     * @param mixed        $res
     * @param int|stdClass $id
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        $mvc->updateTaskProgress($rec->taskId, 'restore');
    }
    
    
    /**
     * Обновява прогреса на задачата
     *
     * @param int         $taskId
     * @param NULL|string $state
     *
     * @return NULL|bool
     */
    protected static function updateTaskProgress($taskId, $state = null)
    {
        if (!$taskId) {
            
            return ;
        }
        
        $progress = self::getLastGoodProgress($taskId);
        
        $tRec = cal_Tasks::fetch($taskId);
        
        if (isset($progress) && ($tRec->progress != $progress)) {
            $tRec->progress = $progress;
            cal_Tasks::save($tRec, 'progress');
            
            $removeOldNotify = false;
            $msg = '';
            
            // При оттегляне, добавяме нотификация и върщаме предишното състояние
            if ($state == 'reject') {
                $notifyUsersArr = type_Keylist::toArray($tRec->assign);
                
                if ($rec->createdBy > 0) {
                    $notifyUsersArr[$tRec->createdBy] = $tRec->createdBy;
                }
                
                $cu = core_Users::getCurrent();
                unset($notifyUsersArr[$cu]);
                
                if (!empty($notifyUsersArr)) {
                    cal_Tasks::notifyForChanges($tRec, 'Оттеглен прогрес', $notifyUsersArr);
                }
            }
            
            return true;
        }
    }
    
    
    /**
     * Връща последно добавения неоттеглен прогрес
     *
     * @param int $taskId
     *
     * @return NULL|float
     */
    protected static function getLastGoodProgress($taskId)
    {
        if (!$taskId) {
            
            return ;
        }
        
        $query = self::getQuery();
        $query->where(array("#taskId = '[#1#]'", $taskId));
        $query->where("#state != 'rejected'");
        $query->limit(1);
        $query->show('progress');
        $query->orderBy('createdOn', 'DESC');
        
        $rec = $query->fetch();
        
        if (!$rec) {
            
            return 0;
        }
        
        return $rec->progress;
    }
    
    
    /**
     * Извлича редовете, които ще се покажат на текущата страница
     * За да покажем и оттеглените задачи
     *
     * @param stdClass $data
     *
     * @return stdClass
     */
    public function prepareListRecs(&$data)
    {
        $data = parent::prepareListRecs_($data);
        
        $data->rejQuery = clone($data->query);
        $data->rejQuery->where("#state = 'rejected'");
        
        return $res;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Добавяме стил за състоянието на оттеглените задачи
        if ($rec->state == 'rejected') {
            $row->ROW_ATTR['class'] .= ' state-' . $rec->state;
        }
        
        $Driver = $mvc->Master->getDriver($rec->taskId);
        
        $mRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        
        if ($Driver) {
            $progressArr = $Driver->getProgressSuggestions($mRec);
        } else {
            $progressArr = array();
        }
        
        Mode::push('text', 'plain');
        $pVal = $mvc->fields['progress']->type->toVerbal($rec->progress);
        Mode::pop('text');
        
        $pValStr = $progressArr[$pVal];
        
        if ($pValStr && ($pValStr != $pVal)) {
            $row->progress .= ' (' . $pValStr . ')';
        }
    }
}
