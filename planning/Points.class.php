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
     * На колко време автоматично да се рефрешва страницата
     */
    const AUTO_REFRESH_TIME = 30000;
    
    
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
            
            // Добавяне на избор само на достъпните служители/оборудване към ПО
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
    
    
    /**
     * Терминал за отчитане на прогреса
     * @return Redirect|core_Et
     */
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
        
        $centerName = (Mode::get('terminalId')) ? planning_Centers::getTitleById($rec->centerId) : planning_Centers::getHyperlink($rec->centerId, true);
        $tpl->replace($centerName, 'centerId');
        $tpl->replace(self::getVerbal($rec, 'fixedAssets'), 'fixedAssets');
        $tpl->replace(dt::mysql2verbal(dt::now(), 'd.m.Y H:i'), 'date');
        $tpl->replace(crm_Profiles::createLink(), 'userId');
        if (Mode::get('terminalId')) {
            $tpl->replace(ht::createLink('', array('peripheral_Terminal', 'exitTerminal'), false, 'title=Изход от терминала,ef_icon=img/16/logout.png'), 'EXIT_TERMINAL');
        } else {
            $tpl->replace(ht::createLink('', array('core_Users', 'logout', 'ret_url' => true), false, 'title=Излизане от системата,ef_icon=img/16/logout.png'), 'EXIT_TERMINAL');
        }

        // Подготовка на урл-тата на табовете
        $taskListUrl = toUrl(array($this, 'renderTab', 'tId' => $rec->id, 'name' => 'taskList'), 'local');
        $taskProgressUrl = toUrl(array($this, 'renderTab', 'tId' => $rec->id, 'name' => 'taskProgress'), 'local');
        $taskSingleUrl = toUrl(array($this, 'renderTab', 'tId' => $rec->id, 'name' => 'taskSingle'), 'local');
        $taskJobUrl = toUrl(array($this, 'renderTab', 'tId' => $rec->id, 'name' => 'taskJob'), 'local');
        $taskSupportUrl = toUrl(array($this, 'renderTab', 'tId' => $rec->id, 'name' => 'taskSupport'), 'local');
        
        $tpl->replace($taskListUrl, 'taskListUrl');
        $tpl->replace($taskProgressUrl, 'taskProgressUrl');
        $tpl->replace($taskSingleUrl, 'taskSingleUrl');
        $tpl->replace($taskJobUrl, 'taskJobUrl');
        $tpl->replace($taskSupportUrl, 'taskSupportUrl');
        
        if(Mode::get("currentTaskId{$rec->id}")){
            $tableTpl = $this->getProgressTable($rec);
            $tpl->replace($tableTpl, 'TASK_PROGRESS');
        } else {
            $tpl->replace('disabled', 'activeSingle');
            $tpl->replace('disabled', 'activeJob');
            $tpl->replace('disabled', 'activeTask');
            $tpl->replace('active', 'activeAll');
        }
        
        Mode::setPermanent('activeTab', $this->getActiveTab($rec));
        
        if(haveRole('debug')){
            core_Statuses::newStatus("TAB " . Mode::get('activeTab'));
        }
        
        $formTpl = $this->getFormHtml($rec);
        $tpl->replace($formTpl, 'FORM');
        
        $activeTab = Mode::get('activeTab');
        $arr = array('taskList'     => array('placeholder' => 'TASK_LIST', 'fnc' => 'getTaskListTable', 'id' => 'task-list'),
                     'taskProgress' => array('placeholder' => 'TASK_PROGRESS', 'fnc' => 'getProgressTable', 'id' => 'tab-progress'),
                     'taskSingle'   => array('placeholder' => 'TASK_SINGLE', 'fnc' => 'getTaskHtml', 'id' => 'tab-single-task'),
                     'taskJob'      => array('placeholder' => 'TASK_JOB', 'fnc' => 'getJobHtml', 'id' => 'tab-job'),
                     'taskSupport'  => array('placeholder' => 'SUPPORT', 'fnc' => 'getSupportHtml', 'id' => 'tab-support'));
        
        expect($aciveTabData = $arr[$activeTab]);
        $tableTpl = $this->{$aciveTabData['fnc']}($rec);
        $tpl->replace($tableTpl, $aciveTabData['placeholder']);
        
        jquery_Jquery::enable($tpl);

        $tpl->push('css/Application.css', 'CSS');
        $tpl->push('js/efCommon.js', 'JS');
        $tpl->push('planning/tpl/terminal/styles.css', 'CSS');
        $tpl->push('planning/tpl/terminal/jquery.numpad.css', 'CSS');
        $tpl->push('planning/tpl/terminal/scripts.js', 'JS');
        $tpl->push('planning/tpl/terminal/jquery.numpad.js', 'JS');
        
        jquery_Jquery::run($tpl, "setCookie('terminalTab', '{$aciveTabData['id']}');");
        jquery_Jquery::run($tpl, 'planningActions();');
        jquery_Jquery::run($tpl, 'prepareKeyboard();');
        jquery_Jquery::runAfterAjax($tpl, 'prepareKeyboard');
        $refreshUrlLocal = toUrl(array($this, 'updateTerminal', 'tId' => $rec->id), 'local');
        core_Ajax::subscribe($tpl, $refreshUrlLocal, 'refreshPlanningTerminal', self::AUTO_REFRESH_TIME);
        
        return $tpl;
    }
    
    
    /**
     * Кой е активния таб
     * 
     * @param stdClass $rec
     * @return string
     */
    private function getActiveTab($rec)
    {
        if($activeTab = Mode::get('activeTab')){
            
            return $activeTab;
        }
        
        $activeTab = (Mode::get('activeTab')) ? Mode::get('activeTab') : (Mode::get("currentTaskId{$rec->id}") ? 'taskProgress' : 'taskList');
       
        return $activeTab;
    }
    
    
    /**
     * Рендиране на таб
     */
    function act_renderTab()
    {
        // Кой е таба
        peripheral_Terminal::setSessionPrefix();
        expect($id = Request::get('tId', 'int'));
        expect($name = Request::get('name', 'varchar'));
        expect($rec = self::fetch($id));
        Mode::setPermanent('activeTab', $name);
        
        if(!$this->haveRightFor('terminal') || !$this->haveRightFor('terminal', $rec)){
            $url = $this->getRedirectUrlAfterProblemIsFound($rec);
            
            return new Redirect($url);
        }
        
        if (Request::get('ajax_mode')) {
            $res = $this->getSuccessfullResponce($rec, $name);
            return $res;
        }
        
        // Ако не сме в Ajax режим пренасочваме към терминала
        redirect(array($this, 'terminal', 'tId' => $rec->id));
    }
    
    
    /**
     * УРЛ към, което да бъде редиректнат потребителя, ако има проблем
     * 
     * @param stdClass $rec
     * 
     * @return array $url
     */
    private function getRedirectUrlAfterProblemIsFound($rec)
    {
        $url = ($this->haveRightFor('list')) ? array($this, 'list') : array('bgerp_Portal', 'show');
        if(!core_Users::getCurrent('id', false)){
            $url = (Mode::get('terminalId')) ? array('peripheral_Terminal', 'default', 'afterExit' => true) : array('core_Users', 'login', 'ret_url' => toUrl(array($this, 'terminal', 'tId' => $rec->id), 'local'));
        }
        
        $object = ht::mixedToHtml($rec);
        planning_Points::logDebug($object, $rec->id);
        
        return $url;
    }
    
    
    /**
     * Екшън опресняващ терминала периодично
     * @return Redirect|array
     */
    public function act_updateTerminal()
    {
        peripheral_Terminal::setSessionPrefix();
        expect($id = Request::get('tId', 'int'));
        expect($rec = self::fetch($id));
        
        if(!$this->haveRightFor('terminal') || !$this->haveRightFor('terminal', $rec)){
            $url = $this->getRedirectUrlAfterProblemIsFound($rec);
            
            return new Redirect($url);
        }
        
        $activeTab = $this->getActiveTab($rec);
        
        return $this->getSuccessfullResponce($rec, $activeTab, false);
    }
    
    
    /**
     * Рендиране на таба за поддръжка
     * 
     * @param mixed $id
     * @return core_ET
     */
    private function getSupportHtml($id)
    {
        $rec = self::fetchRec($id);
        $tpl = new core_ET();
        
        // Полетата на формата
        $form = cls::get('core_Form');
        
        $form->formAttr['submitFormOnRefresh'] = true;
        $form->formAttr['id'] = 'supportForm';
        $form->FLD('asset', 'key(mvc=planning_AssetResources,select=name)', 'class=w100,caption=Оборудване,silent,removeAndRefreshForm=assetFolderId,mandatory');
        $form->FLD('assetFolderId', 'int', 'input=hidden');
        $form->FLD('tId', 'int', 'input=hidden,silent');
        
        // Наличните центрове на дейност
        $options = planning_AssetResources::getByFolderId(planning_Centers::fetchField($rec->centerId, 'folderId'));
        $form->setOptions('asset', array('' => '') + $options);
        $form->input(null, 'silent');
        
        // Ако има избран център, да се заредят неговите папки за поддръжка
        if(isset($form->rec->asset)){
            $assetRec = planning_AssetResources::fetch($form->rec->asset);
            $supportFolders = keylist::toArray($assetRec->systemFolderId);
            if(!empty($supportFolders)){
                $options = array();
                foreach ($supportFolders as $supportFolderId){
                    $options[$supportFolderId] = doc_Folders::getTitleById($supportFolderId, false);
                }
                $form->setField('assetFolderId', 'input');
                $form->setOptions('assetFolderId', $options);
            } else {
                $form->setError('assetFolderId', 'Оборудването няма избрана папка за поддръжка');
                $form->setReadOnly('assetFolderId');
            }
        }
        
        // Събмит на формата
        $form->input();
        if($form->isSubmitted()){
            if(empty($form->rec->assetFolderId)){
                $form->setError('assetFolderId', 'Не е избрана папка');
            }
            
            return redirect(array('cal_Tasks', 'add', 'folderId' => $form->rec->assetFolderId, 'assetResourceId' => $form->rec->asset, 'ret_url' => true));
        }
        
        $sbBtn = ht::createSbBtn('', 'save', null, null, "ef_icon = img/16/settings.png,id='support-submit'");
        $form->fieldsLayout = getTplFromFile('planning/tpl/terminal/SupportFormLayout.shtml');
        $form->class = 'simpleForm';
        
        $tpl->append($form->renderHtml());
        $tpl->append($sbBtn, 'SB_BTN');
        $tpl->removeBlocksAndPlaces();
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на изгледа на избраната активна операция
     *
     * @param mixed $id
     * @return core_ET $tpl
     */
    private function getTaskHtml($id)
    {
        $rec = self::fetchRec($id);
        
        $tpl = new core_ET(" ");
        if($taskId = Mode::get("currentTaskId{$rec->id}")){
            Mode::push('taskInTerminal', true);
            Mode::push('hideToolbar', true);
            $taskContainerId = planning_Tasks::fetchField($taskId, 'containerId');
            $taskObject = doc_Containers::getDocument($taskContainerId);
            
            $mode = (Mode::get('terminalId')) ? 'xhtml' : 'html';
            $tpl = $taskObject->getInlineDocumentBody($mode);
            Mode::pop('hideToolbar');
            Mode::pop('taskInTerminal');
        }
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на таба с избраното задание
     * 
     * @param mixed $id
     * @return core_ET $tpl
     */
    private function getJobHtml($id)
    {
        $rec = self::fetchRec($id);
        
        $tpl = new core_ET(" ");
        if($taskId = Mode::get("currentTaskId{$rec->id}")){
            $jobContainerId = planning_Tasks::fetchField($taskId, 'originId');
            $jobObject = doc_Containers::getDocument($jobContainerId);

            $mode = (Mode::get('terminalId')) ? 'xhtml' : 'html';
            $tpl = $jobObject->getInlineDocumentBody($mode);
        }
        
        return $tpl;
    }
    
    
    /**
     * Реднира таблица със всички операции в терминала
     * 
     * @param mixed $id
     * @return core_ET $tpl
     */
    private function getTaskListTable($id)
    {
        $rec = self::fetchRec($id);
        $folderId = planning_Centers::fetchField($rec->centerId, 'folderId');
        $taskId = Mode::get("currentTaskId{$rec->id}");
        
        // Всички активни операции, в избрания център отговарящи на избраното оборудване ако има
        $Tasks = cls::get('planning_Tasks');
        $data = (object)array('action' => 'list', 'query' => $Tasks->getQuery(), 'listClass' => 'planning-task-table');
        $data->query->where("#folderId = {$folderId} AND #state != 'rejected' AND #state != 'closed' AND #state != 'stopped' AND #state != 'draft'");
        if(!empty($rec->fixedAssets)){
            $data->query->likeKeylist('fixedAssets', $rec->fixedAssets);
        }
        
        if(Mode::get('terminalId')) {
            Mode::push('text', 'xhtml');
        }
        
        // Подготовка на табличните данни
        $Tasks->prepareListFields($data);
        $Tasks->prepareListRecs($data);
        $Tasks->prepareListRows($data);
        if(count($data->recs)){
            foreach ($data->rows as $id => &$row){
                if($id != $taskId){
                    $selectUrl = toUrl(array($this, 'selectTask', $rec->id, 'taskId' => $id));
                    $img = ht::createImg(array('path' => 'img/32/right.png'));
                    $row->selectBtn = ht::createLink($img, $selectUrl, false, 'title=Избиране на операцията за текуща,class=imgNext changeTab');
                } else {
                    $img =  ht::createImg(array('path' =>'img/32/dialog_ok.png'));
                    $row->selectBtn = ht::createLink($img, "", false, 'title=Tекуща операция,class=imgNext');
                    $row->ROW_ATTR['class'] .= ' task-selected';
                }
                unset($row->_rowTools);
            }
        }
        
        // Рендиране на табличните данни
        unset($data->listFields['modifiedOn']);
        unset($data->listFields['modifiedBy']);
        unset($data->listFields['folderId']);
        unset($data->listFields['state']);
        $data->listFields = array('selectBtn' => 'Избор') + $data->listFields;
        $data->listFields['title'] = 'Операция';
        
        setIfNot($data->listTableMvc, clone $Tasks);
        $data->listTableMvc->FLD('selectBtn', 'varchar', 'tdClass=small-field centered');
        $tpl = $Tasks->renderList($data);
        
        if(Mode::get('terminalId')) {
            Mode::pop('text', 'xhtml');
        }
        
        return $tpl;
    }
    
    
    /**
     * Реднира таблица с прогреса към избраната операция
     *
     * @param mixed $id
     * @return core_ET $tpl
     */
    private function getProgressTable($id)
    {
        if(Mode::get('terminalId')) {
            Mode::push('text', 'xhtml');
        }
        
        Mode::push('taskProgressInTerminal', true);
        Mode::push('hideToolbar', true);
        $rec = self::fetchRec($id);
        
        // Подготовка на прогреса на избраната операция, ако има
        $Details = cls::get('planning_ProductionTaskDetails');
        $data = (object)array('action' => 'list', 'query' => $Details->getQuery(), 'listClass' => 'planning-task-progress');
        $taskId = Mode::get("currentTaskId{$rec->id}");
        $data->query->where("#taskId = '{$taskId}'");
        $data->query->orderBy("taskId,id", 'DESC');
        if(isset($taskId)){
            $data->masterMvc = clone cls::get('planning_Tasks');
            $data->masterId = $taskId;
            $data->masterData = (object)array('rec' => planning_Tasks::fetch($taskId));
            $Details->listItemsPerPage = false;
            $Details->prepareDetail_($data);
            unset($data->listFields['productId']);
            unset($data->listFields['taskId']);
            unset($data->listFields['modified']);
            $data->listFields['serial'] = '№';
            $data->listFields['_createdDate'] = 'Създаване';
            $data->groupByField = '_createdDate';
        }
        
        unset($data->toolbar);
        $tpl = $Details->renderDetail($data);
        Mode::pop('hideToolbar');
        Mode::pop('taskProgressInTerminal');
       
        if(Mode::get('terminalId')) {
            Mode::pop('text');
        }
        
        return $tpl;
    }
    
    
    /**
     * Рендира формата за въвеждане на прогреса
     *
     * @param mixed $id
     * @return core_ET $tpl
     */
    private function getFormHtml($id)
    {
        $rec = self::fetchRec($id);
        
        // Коя е текущата задача, ако има
        $currentTaskId = Mode::get("currentTaskId{$rec->id}");
        $Details = cls::get('planning_ProductionTaskDetails');
        
        // Подготовка на формата
        $form = $Details->getForm();
        $form->setField('serial', 'placeholder=№,class=w100 serialField');
        $form->setField('productId', 'class=w100');
        $form->setField('weight', 'class=w100 weightField');
        $form->setField('quantity', 'class=w100 quantityField');
        $form->setField('employees', 'placeholder=Служители,class=w100');
        $form->setField('fixedAsset', 'placeholder=Оборудване,class=w100');
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
        
        // Зареждане на опциите
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
            
            // Ако няма избрана операция, забраняват се другите полета
            $form->setOptions('type', $typeOptions);
            $form->rec->productId = null;
            foreach (array('employees', 'fixedAsset', 'type', 'productId', 'weight', 'quantity') as $fld){
               $form->setReadOnly($fld);
            }
        }
        
        // Кустом рендиране на полетата
        $form->fieldsLayout = getTplFromFile('planning/tpl/terminal/FormFields.shtml');
        
        $taskName = (Mode::get('terminalId')) ? planning_Tasks::getTitleById($currentTaskId, true) : planning_Tasks::getHyperlink($currentTaskId, true);
        $currentTaskHtml = ($currentTaskId)  ? $taskName : tr('Няма текуща задача');
        $form->fieldsLayout->append($currentTaskHtml, 'currentTaskId');
        
        // Бутони за добавяне
        $sendUrl = ($this->haveRightFor('terminal')) ?  toUrl(array($this, 'doAction', 'tId' => $rec->id), 'local') : array();
        $sendBtn = ht::createFnBtn('Изпращане', null, null, array('class' => "planning-terminal-form-btn", 'id' => 'sendBtn', 'data-url' => $sendUrl, 'title' => 'Изпращане на формата'));
        $form->fieldsLayout->append($sendBtn, 'SEND_BTN');
        
        $numpadBtn = ht::createFnBtn('', null, null, array('class' => "planning-terminal-numpad", 'id' => 'numPadBtn', 'title' => 'Отваряне на клавиатура', 'ef_icon' =>'img/16/numpad.png'));
        $form->fieldsLayout->append($numpadBtn, 'NUM_PAD_BTN');

        $weightPadBtn = ht::createFnBtn('', null, null, array('class' => "planning-terminal-numpad", 'id' => 'weightPadBtn', 'title' => 'Отваряне на клавиатура', 'ef_icon' =>'img/16/numpad.png'));
        $form->fieldsLayout->append($weightPadBtn, 'WEIGHT_PAD_BTN');
        
        // Показване на прогреса, само ако е 
        if($currentTaskId && $form->rec->productId == $data->masterRec->productId){
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
    
    
    /**
     * Връща масив за успешен резултат по AJAX
     * 
     * @param mixed $rec
     * @param boolean $replaceForm
     * @param boolean $autoSelectProgress
     * @return array
     */
    private function getSuccessfullResponce($rec, $name, $replaceForm = true)
    {   
        $rec = $this->fetchRec($rec);
        $objectArr = array();
        $arr = array('taskList'     => array('id' => 'task-list-content', 'fnc' => 'getTaskListTable', 'tab-id' => 'task-list'), 
            'taskProgress' => array('id' => 'task-progress-content', 'fnc' => 'getProgressTable', 'tab-id' => 'tab-progress'), 
            'taskSingle'   => array('id' => 'task-single-content', 'fnc' => 'getTaskHtml', 'tab-id' => 'tab-single-task'), 
            'taskJob'      => array('id' => 'task-job-content', 'fnc' => 'getJobHtml', 'tab-id' => 'tab-job'),
            'taskSupport'  => array('id' => 'task-support-content', 'fnc' => 'getSupportHtml', 'tab-id' => 'tab-support'));
        
        foreach ($arr as $tabName => $tabArr){
            $contentHtml = ($tabName == $name) ? $this->{$tabArr['fnc']}($rec)->getContent() : ' ';
            
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => $tabArr['id'], 'html' => $contentHtml, 'replace' => true);
            $objectArr[] = $resObj;
        }
        
        $resObj = new stdClass();
        $resObj->func = 'activateTab';
        $resObj->arg = array('tabId' => $arr[$name]['tab-id']);
        $objectArr[] = $resObj;
        
        // Реплейсване на текущата дата
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => 'dateHolder', 'html' => dt::mysql2verbal(dt::now(), 'd.m.Y H:i'), 'replace' => true);
        $objectArr[] = $resObj;

        $resObj = new stdClass();
        $resObj->func = 'prepareKeyboard';
        $objectArr[] = $resObj;
        
        if($replaceForm === true){
            $formHtml = $this->getFormHtml($rec)->getContent();
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => 'planning-terminal-form', 'html' => $formHtml, 'replace' => true);
            $objectArr[] = $resObj;
        }
        
        // Показване на чакащите статуси
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        
        $res = array_merge($objectArr, (array) $statusData);
        
        return $res;
    }
    
    
    /**
     * Екшън извършващ посоченото действие
     * 
     * @return Redirect|array
     */
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
            
            // Ако има въведен сериен номер
            if(!empty($serial)){
                
                // ...и той е към сингъл на документ
                if(core_Url::isUrlToSingle($serial, $reference)){
                    
                    // ...и той сочи към производствена операция
                    if($reference->isInstanceOf('planning_Tasks')){
                        
                        // ...тогава се избира операцията за текуща
                        $taskRec = $reference->fetch('folderId,state');
                        expect($taskRec->folderId == $folderId, 'Производствената операция е в|* ' . doc_Folders::getTitleById($taskRec->folderId));
                        expect(!in_array($taskRec->state, array('closed', 'rejected', 'stopped')), 'Производствената операция не е активна');
                        redirect(array($this, 'selectTask', $rec->id, 'taskId' => $reference->that));
                    } else {
                        expect(false, 'Не е разпозната операция');
                    }
                }
            }
            
            // Ако се е стигнало до тук, значи се въвежда прогрес по вече избрана ПО
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
            
            // Опит за добавяне на запис в прогреса
            planning_ProductionTaskDetails::add($params['taskId'], $params);
            
            if (Request::get('ajax_mode')) {
                Mode::setPermanent('activeTab', 'taskProgress');
                $res = $this->getSuccessfullResponce($rec, 'taskProgress');
               
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
    
    
    /**
     * Екшън за избиране на текуща производствена операция
     */
    public function act_selectTask()
    {
        peripheral_Terminal::setSessionPrefix();
        $this->requireRightFor('selecttask');
        expect($id = Request::get('id', 'int'));
        expect($rec = self::fetch($id));
        expect($rec->taskId = Request::get('taskId', 'int'));
        $this->requireRightFor('selecttask', $rec);
        Mode::setPermanent("currentTaskId{$rec->id}", $rec->taskId);
        Mode::setPermanent('activeTab', 'taskProgress');
        
        if (Request::get('ajax_mode')) {
            $res = $this->getSuccessfullResponce($rec, 'taskProgress', true);
            
            return $res;
        }
        
        // Ако не сме в Ajax режим пренасочваме към терминала
        redirect(array($this, 'terminal', 'tId' => $rec->id));
    }
}