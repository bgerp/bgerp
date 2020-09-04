<?php


/**
 * Контролер на терминала за отчитане на производство
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
class planning_Terminal extends peripheral_Terminal
{
    
    /**
     * Заглавие
     */
    public $title = 'Производствен терминал';
    
    
    /**
     * Име на източника
     */
    protected $clsName = 'planning_Points';
    
    
    /**
     * Полета
     */
    protected $fieldArr = array('centerId', 'fixedAssets', 'employees');

    
    /**
     * Информация за табовете
     */
    public static $tabData = array('taskList'     => array('placeholder' => 'TASK_LIST', 'fnc' => 'getTaskListTable', 'tab-id' => 'task-list', 'id' => 'task-list-content'),
                           'taskProgress' => array('placeholder' => 'TASK_PROGRESS', 'fnc' => 'getProgressTable', 'tab-id' => 'tab-progress', 'id' => 'task-progress-content'),
                           'taskSingle'   => array('placeholder' => 'TASK_SINGLE', 'fnc' => 'getTaskHtml', 'tab-id' => 'tab-single-task', 'id' => 'task-single-content'),
                           'taskJob'      => array('placeholder' => 'TASK_JOB', 'fnc' => 'getJobHtml', 'tab-id' => 'tab-job', 'id' => 'task-job-content'),
                           'taskSupport'  => array('placeholder' => 'SUPPORT', 'fnc' => 'getSupportHtml', 'tab-id' => 'tab-support', 'id' => 'task-support-content'));
    
    
    /**
     * Кой има право да чете?
     */
    public $canOpenterminal = 'debug';

    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('centerId', 'key(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Център, mandatory,removeAndRefreshForm=fixedAssets|employees,silent');
        $fieldset->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=name,makeLinks,allowEmpty)', 'caption=Оборудване, input=none');
        $fieldset->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks,allowEmpty)', 'caption=Оператори, input=none');
    }
    
    
    /**
     * След подготовка на формата за добавяне
     *
     * @param core_Fieldset $fieldset
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        cls::get('planning_Points')->invoke('AfterPrepareEditForm', array($data));
    }
    
    
    /**
     * Редиректва към посочения терминал в посочената точка и за посочения потребител
     *
     * @return Redirect
     *
     * @see peripheral_TerminalIntf
     */
    public function getTerminalUrl($pointId)
    {
        return array('planning_Terminal', 'open', $pointId);
    }
    
    
    /**
     * Кой е активния таб
     *
     * @param stdClass $rec
     * @return string
     */
    private function getActiveTab($rec)
    {
        if($activeTab = Mode::get("activeTab{$rec->id}")){
            
            return $activeTab;
        }
        
        $activeTab = Mode::get("currentTaskId{$rec->id}") ? 'taskProgress' : 'taskList';
        
        return $activeTab;
    }
    
    
    /**
     * УРЛ към, което да бъде редиректнат потребителя, ако има проблем
     *
     * @param stdClass $rec
     * @param string|null $msg
     *
     * @return array $url
     */
    private function getRedirectUrlAfterProblemIsFound($rec, &$msg)
    {
        $url = (planning_Centers::haveRightFor('single', $rec->centerId)) ? array('planning_Centers', 'single', $rec->centerId) : array('bgerp_Portal', 'show');
        $msg = 'Нямате достъп до терминала|*!';
        if(!core_Users::getCurrent('id', false)){
            $url = array('core_Users', 'login', 'ret_url' => toUrl(array($this, 'open', $rec->id), 'local'));
            $msg = 'Трябва да сте логнат за достъп до терминала|*!';
        }
        
        $object = ht::mixedToHtml($rec);
        planning_Points::logDebug($object, $rec->id);
        
        return $url;
    }
    
    
    /**
     * Рендиране на таба за поддръжка
     *
     * @param mixed $id
     * @return core_ET
     */
    private function getSupportHtml($id)
    {
        $rec = planning_Points::fetchRec($id);
        $tpl = new core_ET(tr("|*<h3 class='title'>|Сигнал за повреда|*</h3><div class='formHolder'>[#FORM#]</div>"));
        $form = cls::get('core_Form');
        $form->FLD('asset', 'key(mvc=planning_AssetResources,select=name,select2MinItems=100)', 'class=w100,placeholder=Оборудване,caption=Оборудване,mandatory');
        $form->FLD('body', 'richtext(rows=4,bucket=calTasks)', 'caption=Описание на проблема,mandatory,placeholder=Описание на проблема');
        
        $options = planning_AssetResources::getByFolderId(planning_Centers::fetchField($rec->centerId, 'folderId'));
        $form->setOptions('asset', array('' => '') + $options);
        $pointAsset = keylist::toArray($rec->fixedAssets);
        $form->setDefault('asset', key($pointAsset));
        $form->input();
        
        if($form->isSubmitted()){
            if(isset($form->rec->asset)){
                $assetRec = planning_AssetResources::fetch($form->rec->asset);
                $supportFolders = keylist::toArray($assetRec->systemFolderId);
                if(!countR($supportFolders)){
                    $form->setError('assetFolderId', 'Оборудването няма избрана папка за поддръжка');
                } else {
                    $newTask = (object)array('folderId' => key($supportFolders),
                        'driverClass' => support_TaskType::getClassId(),
                        'description' => $form->rec->body,
                        'typeId' => support_IssueTypes::fetchField("#type = 'Повреда'"),
                        'assetResourceId' => $form->rec->asset,
                        'state' => 'pending',
                        'title' => str::limitLen(strip_tags($form->getFieldType('body')->toVerbal($form->rec->body)), 64),
                    );
                    
                    cal_Tasks::save($newTask);
                    doc_ThreadUsers::addShared($newTask->threadId, $newTask->containerId, core_Users::getCurrent());
                    planning_Points::addSentTasks($rec, $newTask->id);
                    
                    redirect(array($this, 'open', $rec->id), false, "Успешно пуснат сигнал|* #Tsk{$newTask->id}");
                }
            }
        }
        
        $form->toolbar->addSbBtn('Изпрати', 'default', 'id=filter', 'title=Изпращане на сигнал за повреда на оборудването');
        $form->class = 'simpleForm';
        $form->fieldsLayout = getTplFromFile('planning/tpl/terminal/SupportFormLayout.shtml');
        $tpl->append($form->renderHtml(), 'FORM');
        $tpl->removeBlocksAndPlaces();
        $tpl->append($this->getTasks4SupportTable($rec->tasks));
        $tpl->append("<div class='clearfix21'></div>");
        
        return $tpl;
    }
    
    
    /**
     * Показване на активните сигнали, пуснати от терминала
     *
     * @param mixed $id
     * @return core_ET $tpl
     */
    private function getTasks4SupportTable($tasks)
    {
        $tpl = new core_ET("");
        $tasks = keylist::toArray($tasks);
        if(!empty($tasks)){
            arsort($tasks);
            
            $tpl->append("<div class='fleft taskHolder'><table class='listTable'>");
            foreach ($tasks as $taskId){
                $taskRec = cal_Tasks::fetch($taskId, 'state,progress,createdOn');
                $taskRow = cal_Tasks::recToVerbal($taskRec, 'progressBar,progress,createdOn');
                $taskRow->title = cal_Tasks::getTitleById($taskRec->id, true);
                $tpl->append("<tr class='state-{$taskRec->state}'><td class='nowrap'>{$taskRow->createdOn}</td><td>{$taskRow->title}</td><td>{$taskRow->progressBar} {$taskRow->progress}</td></tr>");
            }
            $tpl->append("</table></div>");
        }
        
        return  $tpl;
    }
    
    
    /**
     * Рендиране на изгледа на избраната активна операция
     *
     * @param mixed $id
     * @return core_ET $tpl
     */
    private function getTaskHtml($id)
    {
        $rec = planning_Points::fetchRec($id);
        
        $tpl = new core_ET(" ");
        if($taskId = Mode::get("currentTaskId{$rec->id}")){
            Mode::push('taskInTerminal', true);
            Mode::push('hideToolbar', true);
            $taskContainerId = planning_Tasks::fetchField($taskId, 'containerId');
            $taskObject = doc_Containers::getDocument($taskContainerId);
            
            Mode::push('noBlank', true);
            $tpl = $taskObject->getInlineDocumentBody('xhtml');
            Mode::pop('noBlank');
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
        $rec = planning_Points::fetchRec($id);
        
        $tpl = new core_ET(" ");
        if($taskId = Mode::get("currentTaskId{$rec->id}")){
            $jobContainerId = planning_Tasks::fetchField($taskId, 'originId');
            $jobObject = doc_Containers::getDocument($jobContainerId);
            
            Mode::push('noBlank', true);
            $tpl = $jobObject->getInlineDocumentBody('xhtml');
            Mode::pop('noBlank', true);
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
        $rec = planning_Points::fetchRec($id);
        $folderId = planning_Centers::fetchField($rec->centerId, 'folderId');
        $taskId = Mode::get("currentTaskId{$rec->id}");
        
        // Всички активни операции, в избрания център отговарящи на избраното оборудване ако има
        $Tasks = cls::get('planning_Tasks');
        $data = (object)array('action' => 'list', 'query' => $Tasks->getQuery(), 'listClass' => 'planning-task-table');
        $data->query->where("#folderId = {$folderId} AND #state != 'rejected' AND #state != 'closed' AND #state != 'stopped' AND #state != 'draft'");
        $data->query->orderBy('id', "DESC");
        if(!empty($rec->fixedAssets)){
            $data->query->likeKeylist('fixedAssets', $rec->fixedAssets);
        }
        
        Mode::push('text', 'xhtml');
        
        // Подготовка на табличните данни
        $Tasks->prepareListFields($data);
        $Tasks->prepareListRecs($data);
        $Tasks->prepareListRows($data);
        if(countR($data->recs)){
            foreach ($data->rows as $id => &$row){
                $title = planning_Tasks::getRecTitle($data->recs[$id]);
                $selectUrl = toUrl(array($this, 'selectTask', $rec->id, 'taskId' => $id));
                $row->title = ht::createLink($title, $selectUrl, false, "title=Избиране на операцията за текуща,class=changeTab");
                $row->title .= "<br><small>{$row->originShortLink}</small>";
                if($id == $taskId){
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
        $data->listFields = $data->listFields;
        $data->listFields['title'] = 'Операция';
        
        setIfNot($data->listTableMvc, clone $Tasks);
        $data->listTableMvc->FLD('selectBtn', 'varchar', 'tdClass=small-field centered');
        $tpl = $Tasks->renderList($data);
        Mode::pop('text', 'xhtml');
        
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
        Mode::push('text', 'xhtml');
        $rec = planning_Points::fetchRec($id);
        Mode::push('taskProgressInTerminal', $rec->id);
        Mode::push('hideToolbar', true);
        
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
            $data->groupByField = '_createdDate';
            $data->listFields = array('_createdDate' => '@', 'typeExtended' => '@', 'serial' => '№', 'quantityExtended' => 'К-во', 'additional' => ' ');
        }
        
        unset($data->toolbar);
        $tpl = $Details->renderDetail($data);
        Mode::pop('hideToolbar');
        Mode::pop('taskProgressInTerminal');
        Mode::pop('text');
        
        $formTpl = $this->getFormHtml($rec);
        $formTpl->prepend("<div class='formHolder fright'>");
        $formTpl->append("</div> ");
        $tpl->prepend($formTpl);
        $tpl->append("<div class='clearfix21'></div>");
        Mode::setPermanent("terminalLastRec{$rec->id}", null);
        
        return $tpl;
    }
    
    
    /**
     * Рендира шаблона за търсене
     *
     * @param mixed $id
     * @return core_ET $tpl
     */
    private function getSearchTpl($id)
    {
        $rec = $this->fetchRec($id);
        $tpl = new core_ET("[#searchInput#][#searchBtn#]");
        
        // Ако се гледа през андроидски телефон да се активира полето за търсене
        $attr = array('name' => 'searchBarcode', 'class' => 'searchBarcode scanElement', 'title' => 'Търсене');
        if($search = Request::get('search', 'varchar')){
            $attr['value'] = $search;
        }
        $searchInput = ht::createElement('input', $attr);
        $tpl->append($searchInput, 'searchInput');
        
        $userAgent = log_Browsers::getUserAgentOsName();
        $url = ($userAgent == 'Android') ? barcode_Search::getScannerActivateUrl(toUrl(array($this, 'open', $rec->id, 'search' => '__CODE__'), true)) : array();
        
        // Бутон за търсене
        $searchUrl = toUrl(array($this, 'search', $rec->id), 'local');
        $searchBtn = ht::createFnBtn('', null, null, array('ef_icon' => 'img/24/search-white.png', 'id' => 'searchBtn',  'data-url' => $searchUrl, 'class' => 'formBtn search',  'title' => 'Търсене'));
        $tpl->append($searchBtn, 'searchBtn');
        
        // Бутон за сканиране
        $scanBtn = ht::createBtn('', $url, false, false, array('ef_icon' => 'img/24/qr.png','class' => 'formBtn qrBtn'));
        $tpl->append($scanBtn, 'scanBtn');
        
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
        $pointRec = planning_Points::fetchRec($id);
        
        // Коя е текущата задача, ако има
        $currentTaskId = Mode::get("currentTaskId{$pointRec->id}");
        expect($taskRec = planning_Tasks::fetch($currentTaskId));
        $Details = cls::get('planning_ProductionTaskDetails');
        Mode::push('terminalProgressForm', $currentTaskId);
        $mandatoryClass = ($taskRec->showadditionalUom == 'mandatory') ? ' mandatory' : '';
        
        $form = cls::get('core_Form');
        $form->formAttr['id'] = $Details->className . '-EditForm';
        $form->formAttr['class'] = 'simpleForm';
        $form->FLD('taskId', 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Операция');
        $form->FLD('action', 'varchar(select2MinItems=100)', 'elementId=actionIdSelect,placeholder=Действие,mandatory,silent,removeAndRefreshForm=productId|type');
        $form->FLD('productId', 'key(mvc=cat_Products,select=name)', 'class=w100,input=hidden,silent');
        $form->FLD('type', 'enum(input=Влагане,production=Произв.,waste=Отпадък)', 'elementId=typeSelect,input=hidden,silent,removeAndRefreshForm=productId|weight|serial,caption=Действие,class=w100');
        $form->FLD('serial', 'varchar(32)', 'autocomplete=off,placeholder=№,class=w100 serialField');
        $form->FLD('quantity', 'double(Min=0)', 'class=w100 quantityField,placeholder=К-во', array('attr' => array('value' => Mode::get("lastQuantity"))));
        $form->FLD('scrappedQuantity', 'double(Min=0)', 'caption=Брак,input=none');
        $form->FLD('weight', 'double(Min=0)', "class=w100 weightField{$mandatoryClass},placeholder=Тегло|* (|кг|*)");
        $form->FLD('employees', 'keylist(mvc=crm_Persons,select=id,select2MinItems=100,columns=3)', 'elementId=employeeSelect,placeholder=Оператори,class=w100');
        $form->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=id,select2MinItems=100)', 'elementId=fixedAssetSelect,placeholder=Оборудване,class=w100');
        $form->FLD('recId', 'int', 'input=hidden,silent');
        $form->rec->taskId = $currentTaskId;
        $form->input(null, 'silent');
        
        if($form->rec->recId){
            $exRec = planning_ProductionTaskDetails::fetch($form->rec->recId);
            $fields = array_keys($form->selectFields("#name != 'recId' AND #name != 'taskId'"));
            foreach ($fields as $name){
                $form->rec->{$name} = $exRec->{$name};
            }
        }
        
        $userAgent = log_Browsers::getUserAgentOsName();
        if ($userAgent == 'Android') {
            $url = toUrl(array($this, 'open', $pointRec->id, 'serial' => '__CODE__'), true);
            $scannerUrl = barcode_Search::getScannerActivateUrl($url);
            $form->setFieldAttr('serial', array('data-url' => $scannerUrl));
        }
        
        // Зареждане на опциите
        $typeOptions = array();
        foreach (array('production' => 'Произв.', 'input' => 'Влагане', 'waste' => 'Отпадък') as $type => $typeCaption){
            $options = planning_ProductionTaskProducts::getOptionsByType($currentTaskId, $type);
            foreach ($options as $pId => $pName){
                if(is_object($pName)) continue;
                $typeOptions["{$type}|{$pId}"] = "[{$typeCaption}] {$pName}";
            }
        }
        
        $form->setOptions('action', $typeOptions);
        $form->setDefault('action', "production|{$taskRec->productId}");
        if(isset($form->rec->action)){
            list($type, $productId) = explode('|', $form->rec->action);
            $form->rec->productId = $productId;
            $form->rec->type = $type;
            
            if($taskRec->labelType == 'print' || $form->rec->type == 'waste'){
                $form->setField('serial', 'input=none');
            }
        }
        
        $data = (object) array('form' => $form, 'masterRec' => planning_Tasks::fetch($currentTaskId), 'action' => 'add');
        $Details->invoke('AfterPrepareEditForm', array($data, $data));
        
        // Кустом рендиране на полетата
        $form->fieldsLayout = getTplFromFile('planning/tpl/terminal/FormFields.shtml');
        $currentTaskHtml = ($currentTaskId)  ? planning_Tasks::getTitleById($currentTaskId, true) : tr('Няма текуща задача');
        $form->fieldsLayout->append($currentTaskHtml, 'currentTaskId');
        
        // Бутони за добавяне
        $sendAttr = array('class' => "planning-terminal-form-btn", 'id' => 'sendBtn', 'title' => 'Изпълнение по задачата');
        if(planning_Points::haveRightFor('openterminal')){
            $sendUrl = toUrl(array($this, 'doAction', $pointRec->id), 'local');
            $sendAttr['data-url'] = $sendUrl;
        } else {
            $sendAttr['class'] .= ' disabled';
        }
        $sendBtn = ht::createFnBtn("Въвеждане|* " . html_entity_decode('&#x23CE;'), null, null, $sendAttr);
        
        $form->fieldsLayout->append($sendBtn, 'SEND_BTN');
        $numpadBtn = ht::createFnBtn('', null, null, array('class' => "planning-terminal-numpad", 'id' => 'numPadBtn', 'title' => 'Отваряне на клавиатура', 'ef_icon' =>'img/16/numpad.png'));
        $serialPadBtn = ht::createFnBtn('', null, null, array('class' => "planning-terminal-numpad", 'id' => 'serialPadBtn', 'title' => 'Отваряне на клавиатура', 'ef_icon' =>'img/16/numpad.png'));
        $form->fieldsLayout->append($numpadBtn, 'NUM_PAD_BTN');
        $form->fieldsLayout->append($serialPadBtn, 'SERIAL_PAD_BTN');
        
        // Показване на прогреса, само ако е
        if($form->rec->productId == $data->masterRec->productId){
            $taskRow = planning_Tasks::recToVerbal(planning_Tasks::fetch($currentTaskId), 'progressBar,progress');
            $form->fieldsLayout->append($taskRow->progressBar, 'PROGRESS');
            $form->fieldsLayout->append(" " . $taskRow->progress, 'PROGRESS');
        }
        
        if($form->fields['weight']->input != 'none'){
            $weightPadBtn = ht::createFnBtn('', null, null, array('class' => "planning-terminal-numpad", 'id' => 'weightPadBtn', 'title' => 'Отваряне на клавиатура', 'ef_icon' =>'img/16/numpad.png'));
            $form->fieldsLayout->append($weightPadBtn, 'WEIGHT_PAD_BTN');
        }
        
        $tpl = $form->renderHtml();
        $Details->invoke('AfterRenderInTerminal', array(&$tpl, $form));
        Mode::pop('terminalProgressForm');
        
        return $tpl;
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
        
        foreach (self::$tabData as $tabName => $tabArr){
            $contentHtml = ($tabName == $name) ? $this->{$tabArr['fnc']}($rec)->getContent() : ' ';
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => $tabArr['id'], 'html' => $contentHtml, 'replace' => true);
            $objectArr[] = $resObj;
        }
        
        // Активиране на нужния таб
        $resObj = new stdClass();
        $resObj->func = 'activateTab';
        $resObj->arg = array('tabId' => self::$tabData[$name]['tab-id']);
        $objectArr[] = $resObj;
        
        // Реплейсване на текущата дата
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => 'dateHolder', 'html' => dt::mysql2verbal(dt::now(), 'd/m/y'), 'replace' => true);
        $objectArr[] = $resObj;
        
        // Подготовка на клавиатурата
        $resObj = new stdClass();
        $resObj->func = 'prepareKeyboard';
        $objectArr[] = $resObj;

        // Подготовка на селекта
        $resObj = new stdClass();
        $resObj->func = 'prepareSelect';
        $objectArr[] = $resObj;
        
        // Задаване на фокус на нужното поле според таба
        $resObj = new stdClass();
        $resObj->func = 'setFocus';
        $resObj->arg = array('tabId' => self::$tabData[$name]['tab-id']);
        $objectArr[] = $resObj;
        
        // Показване на чакащите статуси
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        
        // Скриване на грешките
        $objectArr1 = array();
        $resObj = new stdClass();
        $resObj->func = 'clearStatuses';
        $resObj->arg = array('type' => 'error');
        $objectArr1[] = $resObj;
        
        $res = array_merge($objectArr, (array) $statusData, $objectArr1);
        
        return $res;
    }
    
    
    /**
     * Връща резултат за грешла
     *
     * @param mixed $rec
     * @param core_exception_Expect $e
     * @return array|null
     */
    private function getErrorResponse($rec, core_exception_Expect $e)
    {
        $dump = $e->getDump();
        $dump = $dump[0];
        $errorMsg = $dump;
        reportException($e);
        
        if (Request::get('ajax_mode')) {
            core_Statuses::newStatus($errorMsg, 'error');
            
            // Задаване на фокуса на нужното поле
            $name = Mode::get("activeTab{$rec->id}");
            $objectArr = array();
            $resObj = new stdClass();
            $resObj->func = 'setFocus';
            $resObj->arg = array('tabId' => self::$tabData[$name]['tab-id']);
            $objectArr[] = $resObj;
            
            // Скриване на грешките
            $objectArr1 = array();
            $resObj = new stdClass();
            $resObj->func = 'clearStatuses';
            $resObj->arg = array('type' => 'error');
            $objectArr1[] = $resObj;
            
            // Показваме веднага и чакащите статуси
            $hitTime = Request::get('hitTime', 'int');
            $idleTime = Request::get('idleTime', 'int');
            $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
            $res = array_merge($objectArr, (array) $statusData, $objectArr1);
            
            return $res;
        }
    }
    
    
    /**
     * Добавя контролна сума към ID параметър
     */
    public function protectId($id)
    {
        if (!$this->protectId) {
            
            return $id;
        }
        
        $id = (int)$id;
        $hash = substr(base64_encode(md5(EF_SALT . $this->className . $id)), 0, $this->idChecksumLen);
        
        return $id . $hash;
    }
    
    
    /**
     * Екшън за избиране на текуща производствена операция
     */
    public function act_selectTask()
    {
        planning_Points::requireRightFor('selecttask');
        expect($id = Request::get('id', 'int'));
        expect($rec = planning_Points::fetch($id));
        expect($rec->taskId = Request::get('taskId', 'int'));
        planning_Points::requireRightFor('selecttask', $rec);
        Mode::setPermanent("currentTaskId{$rec->id}", $rec->taskId);
        Mode::setPermanent("activeTab{$rec->id}", 'taskProgress');
        $res = array($this, 'open', $rec->id);
        if (Request::get('ajax_mode')) {
            $res = $this->getSuccessfullResponce($rec, 'taskProgress', true);
            
            return $res;
        }
        
        // Ако не сме в Ajax режим пренасочваме към терминала
        redirect(array($this, 'open', $rec->id, 'recId' => Request::get('recId', 'int')));
    }
    
    
    /**
     * Екшън извършващ посоченото действие
     *
     * @return Redirect|array
     */
    public function act_Search()
    {
        $id = Request::get('id', 'int');
        expect($rec = planning_Points::fetch($id), 'Неразпознат ресурс');
        if(!planning_Points::haveRightFor('openterminal') || !planning_Points::haveRightFor('openterminal', $rec)){
            $msg = null;
            $url = $this->getRedirectUrlAfterProblemIsFound($rec, $msg);
            
            return new Redirect($url, $msg, 'warning');
        }
        
        $this->logRead('Търсене в терминала', $rec->id);
        
        try {
            expect($search = Request::get('search', 'varchar'), 'Не е избрано по какво да се търси');
            
            $folderId = planning_Centers::fetchField($rec->centerId, 'folderId');
            $reference = null;
            
            // Ако има въведен сериен номер
            if(!empty($search)){
                
                // ...и той е към сингъл на документ
                if(core_Url::isUrlToSingle($search, $reference)){
                    
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
                
                expect(false, 'Не е разпозната операция');
            }
        } catch(core_exception_Expect $e){
            
            return $this->getErrorResponse($rec, $e);
        }
        
        // Ако не сме в Ajax режим пренасочваме към терминала
        redirect(array($this, 'open', $rec->id));
    }
    
    
    /**
     * Екшън извършващ посоченото действие
     *
     * @return Redirect|array
     */
    public function act_doAction()
    {
        $id = Request::get('id', 'int');
        expect($rec = planning_Points::fetch($id), 'Неразпознат ресурс');
        if(!planning_Points::haveRightFor('openterminal') || !planning_Points::haveRightFor('openterminal', $rec)){
            $msg = null;
            $url = $this->getRedirectUrlAfterProblemIsFound($rec, $msg);
            
            return new Redirect($url, $msg, 'error');
        }
        
        try{
            // Ако се е стигнало до тук, значи се въвежда прогрес по вече избрана ПО
            $serial = Request::get('serial', 'varchar');
            expect($taskId = Request::get('taskId', 'int'), 'Не е избрана операция');
            $params = array('taskId' => $taskId,
                'productId' => Request::get('productId'),
                'type'     => Request::get('type'),
                'quantity' => Request::get('quantity'),
                'employees' => Request::get('employees'),
                'fixedAsset' => Request::get('fixedAsset'),
                'weight' => Request::get('weight'),
                'serial' => $serial,
            );
            
            // Опит за добавяне на запис в прогреса
            $Details = cls::get('planning_ProductionTaskDetails');
            $dRec = $Details::add($params['taskId'], $params);
            $Details->logInAct('Създаване на детайл от терминала', $dRec);
            Mode::setPermanent("terminalLastRec{$rec->id}", $dRec->id);
            Mode::set("lastQuantity", $params['quantity']); file_put_contents('debug.txt', $params['quantity'] . "\n");
            
            if(isset($dRec->_rejectId) || !Request::get('ajax_mode')){
                
                // Ако не сме в Ajax режим или е редактиран ред се рефрешва страницата
                redirect(array($this, 'open', $rec->id));
            }
            
            Mode::setPermanent("activeTab{$rec->id}", 'taskProgress');
            $res = $this->getSuccessfullResponce($rec, 'taskProgress');
            
            return $res;
        } catch (core_exception_Expect $e){
            
            return $this->getErrorResponse($rec, $e);
        }
    }
    
    
    /**
     * Терминал за отчитане на прогреса
     * @return Redirect|core_Et
     */
    public function act_Open()
    {
        expect($id = Request::get('id', 'int'));
        expect($rec = planning_Points::fetch($id));

        if(!planning_Points::haveRightFor('openterminal') || !planning_Points::haveRightFor('openterminal', $rec)){
            $msg = null;
            $url = $this->getRedirectUrlAfterProblemIsFound($rec, $msg);
            
            return new Redirect($url, $msg, 'error');
        }
        
        Mode::setPermanent('currentPlanningPoint', $id);
        Mode::set('wrapper', 'page_Empty');
        $verbalAsset = strip_tags(core_Type::getByName('keylist(mvc=planning_AssetResources,makeLinks=hyperlink)')->toVerbal($rec->fixedAssets));
        
        $tpl = getTplFromFile('planning/tpl/terminal/Point.shtml');
        $tpl->replace($rec->name, 'name');
        $tpl->replace($rec->id, 'id');
        $tpl->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf('img/16/monitor.png', '"', true) . '>', 'HEAD');
        
        $tpl->replace(planning_Centers::getTitleById($rec->centerId), 'centerId');
        $tpl->replace($verbalAsset, 'fixedAssets');
        $tpl->replace(dt::mysql2verbal(dt::now(), 'd/m/y'), 'date');
        $tpl->replace(strip_tags(crm_Profiles::createLink()), 'userId');
        $img = ht::createImg(array('path' => 'img/16/logout-white.png'));
        
        $tpl->replace(ht::createLink($img, array('core_Users', 'logout', 'ret_url' => array('core_Users', 'login')), false, 'title=Излизане от системата'), 'EXIT_TERMINAL');
        
        // Подготовка на урл-тата на табовете
        $taskListUrl = toUrl(array($this, 'renderTab', $rec->id, 'name' => 'taskList'), 'local');
        $taskProgressUrl = toUrl(array($this, 'renderTab', $rec->id, 'name' => 'taskProgress'), 'local');
        $taskSingleUrl = toUrl(array($this, 'renderTab', $rec->id, 'name' => 'taskSingle'), 'local');
        $taskJobUrl = toUrl(array($this, 'renderTab', $rec->id, 'name' => 'taskJob'), 'local');
        $taskSupportUrl = toUrl(array($this, 'renderTab', $rec->id, 'name' => 'taskSupport'), 'local');
        $tpl->replace($taskListUrl, 'taskListUrl');
        $tpl->replace($taskProgressUrl, 'taskProgressUrl');
        $tpl->replace($taskSingleUrl, 'taskSingleUrl');
        $tpl->replace($taskJobUrl, 'taskJobUrl');
        $tpl->replace($taskSupportUrl, 'taskSupportUrl');
        
        // Какъв да е тайтъла на страницата
        $pageTitle = $rec->name . ((!empty($verbalAsset) ? " « " . strip_tags($verbalAsset) : ""));
        $tpl->replace($pageTitle, 'PAGE_TITLE');
        Mode::setPermanent("activeTab{$rec->id}", $this->getActiveTab($rec));
        $activeTab = Mode::get("activeTab{$rec->id}");
        
        // Ако няма избрана операция, забраняват се определени бутони
        if(!Mode::get("currentTaskId{$rec->id}")){
            $tpl->replace('disabled', 'activeSingle');
            $tpl->replace('disabled', 'activeJob');
            $tpl->replace('disabled', 'activeTask');
            
            if($activeTab == 'taskList'){
                $tpl->replace('active', 'activeAll');
            }
        }
        
        // Кой е активния таб ? Показване на формата за търсене по баркод
        $tpl->replace($this->getSearchTpl($rec), "SEARCH_FORM");
        
        // Рендиране на активния таб
        expect($aciveTabData = self::$tabData[$activeTab]);
        $tableTpl = $this->{$aciveTabData['fnc']}($rec);
        $tpl->replace($tableTpl, $aciveTabData['placeholder']);
        
        jquery_Jquery::enable($tpl);
        $tpl->push('css/Application.css', 'CSS');
        $tpl->push('js/efCommon.js', 'JS');
        $tpl->push('planning/tpl/terminal/styles.css', 'CSS');
        $tpl->push('planning/tpl/terminal/jquery.numpad.css', 'CSS');
        $tpl->push('planning/tpl/terminal/scripts.js', 'JS');
        $tpl->push('planning/tpl/terminal/jquery.numpad.js', 'JS');
        
        $cookieId = "terminalTab{$rec->id}";
        jquery_Jquery::run($tpl, "setCookie('{$cookieId}', '{$aciveTabData['tab-id']}');");
        
        jquery_Jquery::run($tpl, 'planningActions();');
        jquery_Jquery::run($tpl, "setFocus('{$aciveTabData['tab-id']}')");
        $this->logRead('Отваряне на точка за производство', $rec->id);
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на таб
     */
    function act_renderTab()
    {
        // Кой е таба
        expect($id = Request::get('id', 'int'));
        expect($name = Request::get('name', 'varchar'));
        expect($rec = planning_Points::fetch($id));
        Mode::setPermanent("activeTab{$rec->id}", $name);
        
        if(!planning_Points::haveRightFor('openterminal') || !planning_Points::haveRightFor('openterminal', $rec)){
            $msg = null;
            $url = $this->getRedirectUrlAfterProblemIsFound($rec, $msg);
            
            return new Redirect($url, $msg, 'error');
        }
        
        if (Request::get('ajax_mode')) {
            $res = $this->getSuccessfullResponce($rec, $name);
            
            return $res;
        }
        
        // Ако не сме в Ajax режим пренасочваме към терминала
        redirect(array($this, 'open', $rec->id));
    }
}