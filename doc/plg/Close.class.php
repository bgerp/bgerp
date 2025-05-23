<?php


/**
 * Клас 'doc_plg_Close' - Плъгин за затваряне на мениджъри
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_plg_Close extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
        // Ако липсва, добавяме поле за състояние
        if (!$mvc->getField('state', false)) {
            plg_State::setStateField($mvc);
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('close', $data->rec)) {
            $singleTitle = mb_strtolower($mvc->singleTitle);

            $closeBtnId = "btnClose";
            $openBtnId = "btnOpen";
            if ($mvc->hasPlugin('doc_FolderPlg')) {
                $closeBtn = 'Закриване||Close';
                $titleCloseBtn = "Закриване на|* |{$singleTitle}|*";
            } else {
                $closeBtn = 'Затваряне||Close';
                $titleCloseBtn = "Затваряне на|* |{$singleTitle}|*";
            }
            if(cls::haveInterface('doc_DocumentIntf', $mvc)){
                $closeBtnId = "btnClose_{$data->rec->containerId}";
                $openBtnId = "btnOpen_{$data->rec->containerId}";
            }

            if ($data->rec->state == 'closed') {
                $warning = $mvc->getChangeStateWarning($data->rec, $data->rec->brState);
                $data->toolbar->addBtn('Активиране', array($mvc, 'changeState', $data->rec->id, 'ret_url' => true), "order=39,id={$openBtnId},row=2,ef_icon = img/16/lock_unlock.png,title=Активиране на {$singleTitle}");
                $data->toolbar->setWarning($openBtnId, $warning);
            } elseif (in_array($data->rec->state, array('active', 'pending', 'template', 'draft'))) {
                $warning = $mvc->getChangeStateWarning($data->rec, 'closed');
                $closeBtnRow = $mvc->closeBtnRow ?? 2;
                $data->toolbar->addBtn($closeBtn, array($mvc, 'changeState', $data->rec->id, 'ret_url' => true), "order=39,id={$closeBtnId},row={$closeBtnRow},ef_icon = img/16/gray-close.png,title={$titleCloseBtn}");
                $data->toolbar->setWarning($closeBtnId, $warning);
            }
        }
    }
    
    
    /**
     * Ще има ли предупреждение при смяна на състоянието
     */
    public static function on_AfterGetChangeStateWarning($mvc, &$res, $rec, $newState)
    {
        if (empty($res)) {
            $firstContainerId = ($rec->threadId) ? doc_Threads::getFirstContainerId($rec->threadId) : null;

            if ($rec->state == 'closed') {
                if($firstContainerId == $rec->containerId){
                    $res = ($mvc->hasPlugin('doc_FolderPlg')) ? 'Сигурни ли сте, че искате да активирате тази папка и да може да се добавят документи в нея|*?' : 'Сигурни ли сте, че искате да активирате тази нишка и да може да се добавят документи в нея|*?';
                } else {
                    $res = 'Сигурни ли сте, че искате да активирате този документ|*?';
                }
            } elseif (in_array($rec->state, array('active', 'pending', 'template', 'draft'))) {
                if($firstContainerId == $rec->containerId){
                    $res = ($mvc->hasPlugin('doc_FolderPlg')) ? 'Сигурни ли сте, че искате да закриете тази папка и да не може да се добавят документи в нея|*?' : 'Сигурни ли сте, че искате да закриете тази нишка и да не може да се добавят документи в нея|*?';
                } else {
                    $res = 'Сигурни ли сте, че искате да закриете този документ|*?';
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'close' && isset($rec)) {
            if ($rec->threadId) {
                if (!doc_Threads::haveRightFor('single', $rec->threadId) && !$mvc->haveRightFor('single', $rec)) {
                    $res = 'no_one';
                }
            } else {
                if (!$mvc->haveRightFor('single', $rec)) {
                    $res = 'no_one';
                }
            }
            
            if ($rec->state == 'rejected') {
                $res = 'no_one';
            }
            
            if ($res != 'no_one') {
                
                // Ако мениджъра е корица
                if (cls::haveInterface('doc_FolderIntf', $mvc) && !cls::haveInterface('acc_RegisterIntf', $mvc)) {
                   
                    // И има папка без документи или няма папка, няма смисъл да се затваря (защото може да се оттегли)
                    if (isset($rec->folderId)) {
                        $threadsCount = doc_Folders::fetchField($rec->folderId, 'allThreadsCnt');
                        if ($threadsCount == 0) {
                            $res = 'no_one';
                        }
                    } else {
                        $res = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param string   $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action != 'changestate') {
            return;
        }
        
        $mvc->requireRightFor('close');
        expect($id = Request::get('id', 'int'));
        expect($rec = $mvc->fetch($id));
        $mvc->requireRightFor('close', $rec);
        
        $state = ($rec->state == 'closed') ? (($rec->brState) ? $rec->brState : (($rec->exState) ? $rec->exState : 'active')) : 'closed';
        $action = ($state == 'closed') ? 'Приключване' : 'Активиране';
        
        if ($mvc->invoke('BeforeChangeState', array(&$rec, &$state))) {
            $rec->brState = $rec->state;
            $rec->exState = $rec->state;
            $rec->state = $state;
            
            $mvc->save($rec);
            $mvc->invoke('AfterChangeState', array(&$rec, $state));
            
            if (cls::haveInterface('doc_DocumentIntf', $mvc)) {
                doc_Prototypes::sync($rec->containerId);
            }
            $mvc->logWrite($action, $rec->id);
        }
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array($mvc, 'single', $rec->id);
        }
        
        redirect($retUrl);
    }
    
    
    /**
     * Преди затваряне/отваряне на записа
     *
     * @param core_Mvc $mvc      - мениджър
     * @param stdClass $rec      - запис
     * @param string   $newState - ново състояние
     *
     * @return mixed
     */
    protected static function on_BeforeChangeState(core_Mvc $mvc, &$rec, &$newState)
    {
        $castArr = array('stopped' => 'active', 'closed' => 'active', 'rejected' => 'active');
        
        if ($castArr[$rec->state] && $castArr[$newState]) {
            $newState = $castArr[$newState];
        }
    }
}
