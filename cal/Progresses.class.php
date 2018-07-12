<?php


/**
 * Коментар от тип прогрес
 *
 * @category  bgerp
 * @package   cal
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cal_Progresses extends core_Mvc
{
    public $interfaces = 'doc_ExpandCommentsIntf';
    
    
    public $title = 'Прогрес';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('progress', 'percent(min=0,max=1,decimals=0)', 'caption=Прогрес,after=body, changable');
        $fieldset->FLD('workingTime', 'time(suggestions=10 мин.|30 мин.|60 мин.|2 часа|3 часа|5 часа|10 часа)', 'caption=Отработено време,after=progress, changable');
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     *
     * @param cal_Progresses $Driver
     * @param doc_Comments   $mvc
     * @param stdClass       $data
     */
    public static function on_AfterPrepareEditForm($Driver, $mvc, &$data)
    {
        $data->singleTitle = 'Прогрес';
        
        $rec = $data->form->rec;
        
        if ($originId = $rec->originId) {
            $doc = doc_Containers::getDocument($originId);
            $tRec = $doc->fetch();
            
            if (!haveRole('partner')) {
                $shareUsersArr = type_Users::toArray($tRec->assign);
                
                if ($tRec->createdBy > 0) {
                    $shareUsersArr[$tRec->createdBy] = $tRec->createdBy;
                }
                
                $cu = core_Users::getCurrent();
                unset($shareUsersArr[$cu]);
                
                if (!empty($shareUsersArr)) {
                    $data->form->setDefault('sharedUsers', $shareUsersArr);
                }
            }
            
            if ($doc->instance instanceof embed_Manager) {
                $TaskDriver = $doc->getDriver();
                
                $progressArr = $TaskDriver->getProgressSuggestions($tRec);
                
                if ($tRec->progress) {
                    $pVal = $tRec->progress * 100;
                    Mode::push('text', 'plain');
                    $pVal = $doc->fields['progress']->type->toVerbal($tRec->progress);
                    Mode::pop('text');
                    if (!isset($progressArr[$pVal])) {
                        $progressArr[$pVal] = $pVal;
                        ksort($progressArr, SORT_NUMERIC);
                    }
                    $data->form->setDefault('progress', $tRec->progress);
                }
                
                $data->form->setSuggestions('progress', $progressArr);
            }
        }
    }
    
    
    /**
     *
     *
     * @param cal_Progresses $Driver
     * @param doc_Comments   $mvc
     * @param int            $id
     * @param stdClass       $rec
     * @param NULL|array     $saveFileds
     */
    public static function on_AfterSave($Driver, $mvc, &$id, $rec, $saveFileds = null)
    {
        $touchRec = false;
        if ($rec->originId) {
            $tDoc = doc_Containers::getDocument($rec->originId);
            if ($tDoc->instance instanceof cal_Tasks) {
                $touchRec = true;
            }
        }
        
        // При промяна на прогрес
        if ($rec->__isBeingChanged) {
            $lGoodProgress = $Driver->getLastGoodProgress($rec->originId);
            $Driver->updateTaskProgress($rec, $lGoodProgress);
        }
        
        // Променяме общото отработено време на задачата
        if ($rec->state != 'draft' && $rec->originId && $rec->workingTime) {
            if ($tDoc->instance instanceof cal_Tasks) {
                $tRec = $tDoc->fetch();
                
                $tRec->workingTime = 0;
                
                // Това е за да се вземе времената от старите прогреси
                if (cal_TaskProgresses::isInstalled()) {
                    $query = cal_TaskProgresses::getQuery();
                    $query->where("#taskId = {$tRec->id}");
                    $query->where("#state != 'rejected'");
                    $query->XPR('workingTimeSum', 'int', 'sum(#workingTime)');
                    $query->show('workingTimeSum');
                    $tRec->workingTime = (int) $query->fetch()->workingTimeSum;
                }
                
                // Да се вземе времената на новите прогреси
                $query = doc_Comments::getQuery();
                $query->where(array("#originId = '[#1#]'", $rec->originId));
                $query->where("#state != 'rejected'");
                $query->where("#state != 'draft'");
                $query->show('driverRec');
                
                while ($dRec = $query->fetch()) {
                    if (!$dRec->driverRec['workingTime']) {
                        continue;
                    }
                    
                    $tRec->workingTime += $dRec->driverRec['workingTime'];
                }
                
                $tDoc->instance->save($tRec, 'workingTime');
            }
        }
        
        if ($touchRec) {
            $tDoc->touchRec();
        }
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param cal_Progresses $Driver
     * @param doc_Comments   $mvc
     * @param NULL|array     $res
     * @param object         $rec
     * @param object         $row
     */
    public static function on_AfterGetFieldForLetterHead($Driver, $mvc, &$resArr, $rec, $row)
    {
        $resArr = arr::make($resArr);
        
        if ($row->progressBar || $row->progress) {
            $resArr['progressBar'] = array('name' => tr('Прогрес'), 'val' => '[#progressBar#] [#progress#]');
        }
        
        if ($row->workingTime) {
            $resArr['workingTime'] = array('name' => tr('Отработено време'), 'val' => '[#workingTime#]');
        }
        
        if ($rec->originId) {
            $tDoc = doc_Containers::getDocument($rec->originId);
            if ($tDoc->instance instanceof cal_Tasks) {
                $resArr['originId'] = array('name' => tr('Задача'), 'val' => $tDoc->getLinkToSingle());
            }
        }
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     *
     * @param cal_Progresses $Driver
     * @param doc_Comments   $mvc
     * @param stdClass       $rec
     */
    public static function on_AfterActivation($Driver, $mvc, &$rec)
    {
        $Driver->updateTaskProgress($rec, $rec->progress);
        
        if ($rec->originId) {
            $tDoc = doc_Containers::getDocument($rec->originId);
            if ($tDoc->instance instanceof cal_Tasks) {
                $tDoc->touchRec();
            }
        }
    }
    
    
    /**
     * Възстановяване на оттеглен обект
     *
     * @param cal_Progresses $Driver
     * @param doc_Comments   $mvc
     * @param mixed          $res
     * @param int|stdClass   $id
     */
    public static function on_AfterRestore($Driver, $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        if ($rec->originId) {
            $lGoodProgress = $Driver->getLastGoodProgress($rec->originId);
            
            $Driver->updateTaskProgress($rec, $lGoodProgress);
        }
    }
    
    
    /**
     * След оттегляне на обект
     *
     * @param cal_Progresses $Driver
     * @param doc_Comments   $mvc
     * @param mixed          $res
     * @param int|stdClass   $id
     */
    public static function on_AfterReject($Driver, $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        if ($rec->originId) {
            $lGoodProgress = $Driver->getLastGoodProgress($rec->originId);
            
            $Driver->updateTaskProgress($rec, $lGoodProgress);
        }
    }
    
    
    /**
     * Обновява задачата след промяна на прогреса
     *
     * @param stdClass $rec
     * @param NULL|int $progress
     */
    public static function updateTaskProgress($rec, $progress = null)
    {
        if ($rec->originId) {
            $tDoc = doc_Containers::getDocument($rec->originId);
            
            $saveArr = array();
            
            if ($tDoc->instance instanceof cal_Tasks && isset($progress)) {
                $tRec = $tDoc->fetch();
                $oldProgress = $tRec->progress;
                $oldState = $tRec->state;
                
                // Ако има промяна в прогреса
                if ($oldProgress != $progress) {
                    $tRec->progress = $progress;
                    
                    $saveArr['progress'] = 'progress';
                    
                    if (isset($tRec->progress)) {
                        
                        // Ако прогреса е 100%, тогава затваряме задачата
                        if ($tRec->progress == 1) {
                            $tRec->brState = $tRec->state;
                            $tRec->state = 'closed';
                            
                            $saveArr['state'] = 'state';
                            $saveArr['brState'] = 'state';
                            
                            $tRec->timeClosed = dt::now();
                            $saveArr['timeClosed'] = 'timeClosed';
                        }
                        
                        // Ако връщаме прогреса - връщаме и предишното състояние
                        if ($oldProgress == 1) {
                            $tRec->brState = $tRec->state;
                            $tRec->state = 'wakeup';
                            $saveArr['state'] = 'state';
                            $saveArr['brState'] = 'state';
                        }
                    }
                    
                    if (!empty($saveArr)) {
                        $tDoc->instance->save($tRec, $saveArr);
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща най-новия активен прогрес
     *
     * @param int $originId
     *
     * @return float
     */
    protected static function getLastGoodProgress($originId)
    {
        if (!$originId) {
            
            return ;
        }
        
        $query = doc_Comments::getQuery();
        $query->where(array("#originId = '[#1#]'", $originId));
        $query->where("#state != 'rejected'");
        $query->where("#state != 'draft'");
        $query->limit(1);
        $query->show('driverRec');
        $query->orderBy('activatedOn', 'DESC');
        
        $rec = $query->fetch();
        
        if (!$rec) {
            
            return 0;
        }
        
        return $rec->driverRec['progress'];
    }
    
    
    /**
     * Подготвяне на вербалните стойности
     *
     * @param cal_Progresses $Driver
     * @param doc_Comments   $mvc
     * @param stdClass       $row
     * @param stdClass       $rec
     */
    public function on_AfterRecToVerbal($Driver, $mvc, $row, $rec)
    {
        $row->singleTitle = tr('Прогрес');
        
        // Показване на типа на прогреса
        if ($rec->originId) {
            $doc = doc_Containers::getDocument($rec->originId);
            $tRec = $doc->fetch();
            
            $tDriver = $doc->getDriver();
            
            if ($tDriver) {
                $progressArr = $tDriver->getProgressSuggestions($tRec);
            } else {
                $progressArr = array();
            }
            
            Mode::push('text', 'plain');
            $pVal = $doc->instance->fields['progress']->type->toVerbal($rec->progress);
            Mode::pop('text');
            
            $pValStr = $progressArr[$pVal];
            
            if ($pValStr && ($pValStr != $pVal)) {
                $row->progress .= ' (' . $pValStr . ')';
            }
        }
    }
    
    
    /**
     * Връща състоянието на нишката
     *
     * @param cal_Progresses $Driver
     * @param doc_Comments   $mvc
     * @param string|NULL    $res
     * @param int            $id
     *
     * @return string
     */
    public static function on_AfterGetThreadState($Driver, $mvc, &$res, $id)
    {
        $res = null;
        
        $rec = $mvc->fetchRec($id);
        
        // Когато задачата е на 100% и няма друга задача и друг имейл - тогава затваря нишката
        if ($rec->progress == 1) {
            if ($rec->threadId) {
                $tRec = doc_Threads::fetch($rec->threadId);
                
                if ($tRec->state != 'closed') {
                    // Да няма входящ имейл в нишката
                    if (!email_Incomings::fetch(array("#threadId = '[#1#]' AND #state != 'rejected'", $tRec->id))) {
                        // Ако няма други задачи
                        if (!cal_Tasks::fetch(array("#containerId != [#1#] AND #threadId = '[#2#]' AND #state != 'rejected' AND #state != 'closed' AND #state != 'stopped' AND #state != 'draft'", $rec->originId, $tRec->id))) {
                            $res = 'closed';
                        }
                    }
                }
            }
        }
    }
}
