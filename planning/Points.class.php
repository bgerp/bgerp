<?php


/**
 * Tерминал за въвеждане на продукция
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_Points extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Терминали за въвеждане на продукция';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'planning_Wrapper,plg_Rejected,plg_RowTools2';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'peripheral_TerminalIntf';
    
    
    /**
     * Кой има право да чете?
     */
    public $canTerminal = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Полета, които се виждат
     */
    public $listFields = 'name, centerId, fixedAssets, employees, terminal=Вход';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(16)', 'caption=Наименование, mandatory');
        $this->FLD('centerId', 'key(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Център, mandatory,removeAndRefreshForm=fixedAssets|employees,silent');
        $this->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=name,makeLinks,allowEmpty)', 'caption=Оборудване, input=none');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks,allowEmpty)', 'caption=Служители, input=none');
        $this->FLD('state', 'enum(active=Контиран,rejected=Оттеглен)', 'caption=Състояние,notNull,value=active,input=none');
        
        $this->setDbIndex('centerId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        if(isset($form->rec->centerId)){
            $folderId = planning_Centers::fetchField($form->rec->centerId, 'folderId');
            
            foreach (array('fixedAssets' => 'planning_AssetResources', 'employees' => 'planning_Hr') as $field => $Det) {
                $arr = $Det::getByFolderId($folderId);
                if (!empty($form->rec->{$field})) {
                    $alreadyIn = keylist::toArray($form->rec->{$field});
                    foreach ($alreadyIn as $fId) {
                        if (!array_key_exists($fId, $arr)) {
                            $arr[$fId] = $Det::getTitleById($fId, false);
                        }
                    }
                }
                
                if (count($arr)) {
                    $form->setSuggestions($field, array('' => '') + $arr);
                    $form->setField($field, 'input');
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->ROW_ATTR['class'] = "state-{$rec->state}";
        $row->centerId = planning_Centers::getHyperlink($rec->centerId, true);
        
        if(planning_Points::haveRightFor('terminal', $rec)){
            $row->terminal = ht::createBtn('Отвори', array('planning_Points', 'terminal', 'tId' => $rec->id), false, true, 'title=Отваряне на терминала за отчитане на производството,ef_icon=img/16/forward16.png');
        }
    }
    
    
    /**
     * Връща всички достъпни за текущия потребител id-та на обекти, отговарящи на записи
     *
     * @return array
     *
     * @see peripheral_TerminalIntf
     */
    public function getTerminalOptions()
    {
        $options = array();
        $cQuery = self::getQuery();
        $cQuery->where("#state != 'rejected' AND #state != 'closed'");
        while ($cRec = $cQuery->fetch()) {
            $options[$cRec->id] = self::getRecTitle($cRec, false) . " ({$cRec->id})";
        }
        
        return $options;
    }
    
    
    /**
     * Редиректва към посочения терминал в посочената точка и за посочения потребител
     *
     * @return Redirect
     *
     * @see peripheral_TerminalIntf
     */
    public function openTerminal($objectId, $userId)
    {
        return new Redirect(array($this, 'openTerminal', $objectId));
    }
    
    
    /**
     * Екшън форсиращ избирането на точката и отваряне на терминала
     */
    public function act_OpenTerminal()
    {
        expect($objectId = Request::get('id', 'int'));
        
        return new Redirect(array(get_called_class(), 'terminal', 'tId' => $objectId));
    }
    
    
    public function act_Terminal()
    {
        peripheral_Terminal::setSessionPrefix();
        expect($id = Request::get('tId', 'int'));
        expect($rec = self::fetch($id));
        
        if(!$this->haveRightFor('terminal') || !$this->haveRightFor('terminal', $rec)){
            $url = $this->getRedirectUrlAfterProblemIsFound($rec);
            
            return new Redirect($url);
        }
        
        Mode::setPermanent('currentPlanningPoint', $id);
        Mode::set('wrapper', 'page_Empty');
        
        $tpl = getTplFromFile('planning/tpl/terminal/Point.shtml');
        $tpl->replace($rec->name, 'PAGE_TITLE');
        $tpl->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf('img/16/big_house.png', '"', true) . '>', 'HEAD');
        
        $img = ht::createElement('img', array('src' => sbf('pos/img/bgerp.png', '')));
        $logo = ht::createLink($img, array('bgerp_Portal', 'Show'), null, array('target' => '_blank', 'class' => 'portalLink', 'title' => 'Към портала'));
        $tpl->append($logo, 'LOGO');
        
        $tpl->replace(planning_Centers::getHyperlink($rec->centerId, true), 'centerId');
        $tpl->replace(self::getVerbal($rec, 'fixedAssets'), 'fixedAssets');
        $tpl->replace(dt::mysql2verbal(dt::now(), 'd.m.Y H:i'), 'date');
        $tpl->replace(crm_Profiles::createLink(), 'userId');
        if (Mode::get('terminalId')) {
            $tpl->replace(ht::createLink('', array('peripheral_Terminal', 'exitTerminal'), false, 'title=Изход от терминала,ef_icon=img/16/logout.png'), 'EXIT_TERMINAL');
        }

        $taskId = Mode::get("currentTaskId{$rec->id}");
        if($taskId) {
            $tpl->replace('active', 'activeSingle');
        }
        else {
            $tpl->replace('active', 'activeAll');
        }

        $tableTpl = $this->getTasksTable($rec);
        $tpl->replace($tableTpl, 'PROGRESS_TASK_TABLE');
        
        $tableTpl = $this->getProgressTable($rec);
        $tpl->replace($tableTpl, 'PROGRESS_TABLE');
       
        $formTpl = $this->getFormHtml($rec);
        $tpl->replace($formTpl, 'FORM');
        
        jquery_Jquery::enable($tpl);
        $tpl->push('css/Application.css', 'CSS');
        $tpl->push('js/efCommon.js', 'JS');
        $tpl->push('planning/tpl/terminal/styles.css', 'CSS');
        $tpl->push('planning/tpl/terminal/scripts.js', 'JS');
        jquery_Jquery::run($tpl, 'planningActions();');
        
        $refreshUrlLocal = toUrl(array($this, 'updateTerminal', 'tId' => $rec->id), 'local');
        core_Ajax::subscribe($tpl, $refreshUrlLocal, 'refreshPlanningTerminal', 2000);
        
        return $tpl;
    }
    
    
    private function getRedirectUrlAfterProblemIsFound($rec)
    {
        $url = ($this->haveRightFor('list')) ? array($this, 'list') : array('bgerp_Portal', 'show');
        if(!core_Users::getCurrent('id', false)){
            $url = (Mode::get('terminalId')) ? array('peripheral_Terminal', 'default', 'afterExit' => true) : array('core_Users', 'login', 'ret_url' => toUrl(array($this, 'terminal', 'tId' => $rec->id), 'local'));
        }
        
        return $url;
    }
    
    
    public function act_updateTerminal()
    {
        peripheral_Terminal::setSessionPrefix();
        expect($id = Request::get('tId', 'int'));
        expect($rec = self::fetch($id));
        
        if(!$this->haveRightFor('terminal') || !$this->haveRightFor('terminal', $rec)){
            $url = $this->getRedirectUrlAfterProblemIsFound($rec);
            
            return new Redirect($url);
        }
        
        return $this->getSuccessfullResponce($rec, false);
    }
    
    
    
    
    private function getTasksTable($id)
    {
        $rec = self::fetchRec($id);
        $folderId = planning_Centers::fetchField($rec->centerId, 'folderId');
        
        $Tasks = cls::get('planning_Tasks');
        $data = (object)array('action' => 'list', 'query' => $Tasks->getQuery(), 'listClass' => 'planning-task-table');
        $data->query->where("#folderId = {$folderId} AND #state != 'rejected' AND #state != 'closed' AND #state != 'stopped' AND #state != 'draft'");
        if(!empty($rec->fixedAssets)){
            $data->query->likeKeylist('fixedAssets', $rec->fixedAssets);
        }
        
        $Tasks->prepareListFields($data);
        $Tasks->prepareListRecs($data);
        $Tasks->prepareListRows($data);
        
        if(count($data->recs)){
            foreach ($data->rows as $id => &$row){
                $row->ROW_ATTR['data-url'] = toUrl(array($this, 'selectTask', $rec->id, 'taskId' => $id), 'local');
                $row->ROW_ATTR['class'] .= " terminal-task-row";
                unset($row->_rowTools);
            }
        }
        
        unset($data->listFields['modifiedOn']);
        unset($data->listFields['modifiedBy']);
        unset($data->listFields['folderId']);
        unset($data->listFields['_rowTools']);
        
        setIfNot($data->listTableMvc, clone $Tasks);
        $data->listTableMvc->setField('progress', 'smartCenter');
        
        $tpl = $Tasks->renderList($data);
        
        return $tpl;
    }
    
    
    
    
    private function getProgressTable($id)
    {
        Mode::push('centerTerminal', true);
        $rec = self::fetchRec($id);
        
        $Details = cls::get('planning_ProductionTaskDetails');
        $data = (object)array('action' => 'list', 'query' => $Details->getQuery(), 'listClass' => 'planning-task-progress');
        $taskId = Mode::get("currentTaskId{$rec->id}");

        $data->query->where("#taskId = '{$taskId}'");
        $data->query->orderBy("taskId,id", 'DESC');
        
        $Details->prepareListFields($data);
        $Details->prepareListRecs($data);
        $Details->prepareListRows($data);
        
        unset($data->listFields['taskId']);
        unset($data->listFields['modified']);
        unset($data->listFields['productId']);
        
        setIfNot($data->listTableMvc, clone $Details);
        $data->listTableMvc->setField('quantity', 'smartCenter,tdClass=leftCol');
        
        $tpl = $Details->renderList($data);
        Mode::pop('centerTerminal');
        
        return $tpl;
    }
    
    private function getFormHtml($id)
    {
        $rec = self::fetchRec($id);
        
        $currentTaskId = Mode::get("currentTaskId{$rec->id}");
        $Details = cls::get('planning_ProductionTaskDetails');
        
        $form = $Details->getForm();
        $form->setField('serial', 'placeholder=№,class=w100');
        $form->setField('productId', 'class=w100');
        $form->setField('quantity', 'class=w100');
        $form->setField('weight', 'placeholder=Тегло,class=w100');
        $form->setField('employees', 'placeholder=Служителио,class=w100');
        $form->setField('fixedAsset', 'placeholder=Оборудванео,class=w100');
        
        $form->setDefault('type', 'production');
        $form->setField('type', 'input,removeAndRefreshForm=productId|weight|serial,caption=Действие,class=w100');
        $form->input(null, 'silent');
        $form->formAttr['id'] = 'planning-terminal-form';
        $form->formAttr['class'] = 'simpleForm';
        unset($form->rec->id);
        unset($form->fields['id']);
        $form->fields['employees']->attr = array('id' => 'employeeSelect');
        $form->fields['fixedAsset']->attr = array('id' => 'fixedAssetSelect');
        $form->fields['productId']->attr = array('id' => 'productIdSelect');
        $form->fields['type']->attr = array('id' => 'typeSelect');
        $form->rec->taskId = $currentTaskId;
        
        $typeOptions = array('production' => 'Произвеждане');
        if($currentTaskId){
            if($inputOptions = planning_ProductionTaskProducts::getOptionsByType($currentTaskId, 'input')){
                if(count($inputOptions)){
                    $typeOptions['input'] = 'Влагане';
                }
            }
            if($wasteOptions = planning_ProductionTaskProducts::getOptionsByType($currentTaskId, 'waste')){
                if(count($wasteOptions)){
                    $typeOptions['waste'] = 'Отпадък';
                }
            }
            $form->setOptions('type', $typeOptions);
            $data = (object) array('form' => $form, 'masterRec' => planning_Tasks::fetch($currentTaskId), 'action' => 'add');
            $Details->invoke('AfterPrepareEditForm', array($data, $data));
        } else {
            $form->setOptions('type', $typeOptions);
            $form->rec->productId = null;
            foreach (array('employees', 'fixedAsset', 'type', 'productId', 'weight', 'quantity') as $fld){
               $form->setReadOnly($fld);
            }
        }
        
        $form->fieldsLayout = getTplFromFile('planning/tpl/terminal/FormFields.shtml');
        $currentTaskHtml = ($currentTaskId)  ? planning_Tasks::getHyperlink($currentTaskId, true) : "<span>" . tr('Няма текуща задача') . "</span>";
        $form->fieldsLayout->append($currentTaskHtml, 'currentTaskId');
        
        $sendUrl = ($this->haveRightFor('terminal')) ?  toUrl(array($this, 'doAction', 'tId' => $rec->id), 'local') : array();
        $sendBtn = ht::createFnBtn('Изпращане', null, null, array('class' => "planning-terminal-form-btn", 'id' => 'sendBtn', 'data-url' => $sendUrl, 'title' => 'Изпращане на формата'));
        $form->fieldsLayout->append($sendBtn, 'SEND_BTN');
        
        if($currentTaskId){
            $taskRow = planning_Tasks::recToVerbal(planning_Tasks::fetch($currentTaskId), 'progressBar,progress');
            $form->fieldsLayout->append($taskRow->progressBar, 'PROGRESS');
            $form->fieldsLayout->append(" " . $taskRow->progress, 'PROGRESS');
        }
        
        $tpl = $form->renderHtml();
        
        return $tpl;
    }
    
    
    /**
     * Модификация на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if($action == 'terminal' && isset($rec)){
            if(in_array($rec->state, array('closed', 'rejected'))){
                $res = 'no_one';
            }
        }
        
        if($action == 'selecttask'){
            $res = $mvc->getRequiredRoles('terminal', $rec, $userId);
            
            if(isset($rec)){
                if(empty($rec->taskId)){
                    $res = 'no_one';
                } else {
                    $folderId = planning_Centers::fetchField($rec->centerId, 'folderId');
                    $taskRec = planning_Tasks::fetch($rec->taskId, 'state,folderId');
                    if(!in_array($rec->state, array('active', 'wakeup')) || $folderId != $taskRec->folderId){
                        $res = 'no_one';
                    }
                }
            }
        }
    }
    
    
    private function getSuccessfullResponce($rec, $replaceForm = true)
    {
        $rec = $this->fetchRec($rec);
        $objectArr = array();
        
        // Ще реплейснем само таба с прогреса
        $progressHtml = $this->getProgressTable($rec)->getContent();
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => 'progress-holder', 'html' => $progressHtml, 'replace' => true);
        $objectArr[] = $resObj;
        
        // Ще реплейснем само таба с прогреса
        $tableHtml = $this->getTasksTable($rec)->getContent();
        $resObj1 = new stdClass();
        $resObj1->func = 'html';
        $resObj1->arg = array('id' => 'progress-task', 'html' => $tableHtml, 'replace' => true);
        $objectArr[] = $resObj1;
        
        $resObj2 = new stdClass();
        $resObj2->func = 'html';
        $resObj2->arg = array('id' => 'dateHolder', 'html' => dt::mysql2verbal(dt::now(), 'd.m.Y H:i'), 'replace' => true);
        $objectArr[] = $resObj2;
        
        
        if($replaceForm === true){
            $formHtml = $this->getFormHtml($rec)->getContent();
            
            // Ще реплесйнем и таба за плащанията
            $resObj3 = new stdClass();
            $resObj3->func = 'html';
            $resObj3->arg = array('id' => 'planning-terminal-form', 'html' => $formHtml, 'replace' => true);
            $objectArr[] = $resObj3;
        }
        
        // Показваме веднага и чакащите статуси
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        
        $res = array_merge($objectArr, (array) $statusData);
        
        return $res;
    }
    
    
    
    
    public function act_doAction()
    {
        peripheral_Terminal::setSessionPrefix();
        $id = Request::get('tId', 'int');
        expect($rec = self::fetch($id), 'Неразпознат ресурс');
        
        if(!$this->haveRightFor('terminal') || !$this->haveRightFor('terminal', $rec)){
            $url = $this->getRedirectUrlAfterProblemIsFound($rec);
            
            return new Redirect($url);
        }
        
        try{
            $folderId = planning_Centers::fetchField($rec->centerId, 'folderId');
            
            $reference = null;
            $serial = Request::get('serial', 'varchar');
            
            if(!empty($serial)){
                if(core_Url::isUrlToSingle($serial, $reference)){
                    if($reference->isInstanceOf('planning_Tasks')){
                        $taskRec = $reference->fetch('folderId,state');
                        expect($taskRec->folderId == $folderId, 'Производствената операция е в|* ' . doc_Folders::getTitleById($taskRec->folderId));
                        expect(!in_array($taskRec->state, array('closed', 'rejected', 'stopped')), 'Производствената операция не е активна');
                        redirect(array($this, 'selectTask', $rec->id, 'taskId' => $reference->that));
                    } else {
                        expect(false, 'Не е разпозната операция');
                    }
                }
            }
            
            expect($taskId = Request::get('taskId', 'int'), 'Не е избрана операция');
            
            $params = array('taskId' => $taskId, 
                            'productId' => Request::get('productId'),
                            'type'     => Request::get('type'),
                            'quantity' => Request::get('quantity'),
                            'employees' => Request::get('employees'),
                            'fixedAsset' => Request::get('fixedAsset'),
                            'weight' => Request::get('weight'),
                            'weight' => Request::get('weight'),
                            'serial' => $serial,
            );
            
            planning_ProductionTaskDetails::add($params['taskId'], $params);
            
            // Ако заявката е по ajax
            if (Request::get('ajax_mode')) {
                $res = $this->getSuccessfullResponce($rec);
               
                return $res;
            }
            
            // Ако не сме в Ajax режим пренасочваме към терминала
            redirect(array($this, 'terminal', 'tId' => $rec->id));
            
        } catch (core_exception_Expect $e){
            $dump = $e->getDump();
            $dump = $dump[0];
            
            $errorMsg = (haveRole('debug')) ? $dump : 'Възникна проблем при отчитане на прогреса|*!';
            reportException($e);
            
            if (Request::get('ajax_mode')) {
                core_Statuses::newStatus($errorMsg, 'error');
                
                // Показваме веднага и чакащите статуси
                $hitTime = Request::get('hitTime', 'int');
                $idleTime = Request::get('idleTime', 'int');
                $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
                
                return array_merge($statusData);
            }
        }
    }
    
    
    public function act_selectTask()
    {
        peripheral_Terminal::setSessionPrefix();
        $this->requireRightFor('selecttask');
        expect($id = Request::get('id', 'int'));
        expect($rec = self::fetch($id));
        expect($rec->taskId = Request::get('taskId', 'int'));
        $this->requireRightFor('selecttask', $rec);
        
        Mode::setPermanent("currentTaskId{$rec->id}", $rec->taskId);
        
        if (Request::get('ajax_mode')) {
            $res = $this->getSuccessfullResponce($rec);
            
            return $res;
        }
        
        // Ако не сме в Ajax режим пренасочваме към терминала
        redirect(array($this, 'terminal', $rec->id));
    }
    
}