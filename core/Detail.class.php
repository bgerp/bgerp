<?php


/**
 * Клас 'core_Detail' - Мениджър за детайлите на бизнес обектите
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Detail extends core_Manager
{
    /**
     * Полето-ключ към мастъра
     */
    public $masterKey;
    
    
    /**
     * Инстанция към мастера
     */
    public $Master;
    
    
    /**
     * По колко реда от резултата да показва на страница в детайла на документа
     * Стойност '0' означава, че детайла няма да се странира
     */
    public $listItemsPerPage = 1000;
    

    /**
     * Дали да се рендира мастъра под формата за добавяне/редакция на детайла
     */
    public $renderMasterBellowForm = false;
    
    
    /**
     * Време за кеширане на правата към обекта
     */
    public $cacheRightsDuration = 0;
    
    
    /**
     * Изпълнява се след началното установяване на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
        expect($mvc->masterKey);
        
        $mvc->fields[$mvc->masterKey]->silent = 'silent';
        
        setIfNot($mvc->fetchFieldsBeforeDelete, $mvc->masterKey);
        
        if ($mvc->masterClass = $mvc->fields[$mvc->masterKey]->type->params['mvc']) {
            $mvc->Master = cls::get($mvc->masterClass);
        }
        
        // Проверяваме дали мастър ключа има индекс за търсене
        $indexName = str::convertToFixedKey(str::phpToMysqlName(implode('_', arr::make($mvc->masterKey))));
        if (!isset($mvc->dbIndexes[$indexName])) {
            
            // Ако мастър ключа не е индексиран, добавяме го като индекс
            $mvc->setDbIndex($mvc->masterKey);
        }

        setIfNot($mvc->requireMasterBeInstanceOfCoreMaster, true);
        setIfNot($mvc->addDeleteSelectRows, true);
    }
    
    
    /**
     * Подготвяме  общия изглед за 'List'
     */
    public function prepareDetail_($data)
    {
        setIfNot($data->masterKey, $this->masterKey);
        setIfNot($data->masterMvc, $this->Master);
        
        // Очакваме да masterKey да е зададен
        expect($data->masterKey);
        expect($data->masterMvc instanceof core_Master);
        
        // Подготвяме заявката за детайла
        $this->prepareDetailQuery($data);
        
        // Подготвяме полетата за показване
        $this->prepareListFields($data);

        // Махане на мастър ключа ако се рендира като детайл по-време на сингъл
        unset($data->listFields[$data->masterKey]);

        // Подготвяме филтъра
        $this->prepareListFilter($data);
        
        // Подготвяме заявката за резюме/обощение
        $this->prepareListSummary($data);
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);
        
        // Името на променливата за страниране на детайл
        if (is_object($data->pager)) {
            $data->pager->setPageVar($data->masterMvc->className, $data->masterId, $this->className);
            if (cls::existsMethod($data->masterMvc, 'getHandle')) {
                $data->pager->addToUrl = array('#' => $data->masterMvc->getHandle($data->masterId));
            }
        }
        
        // Подготвяме редовете от таблицата
        $this->prepareListRecs($data);
        
        // Подготвяме вербалните стойности за редовете
        $this->prepareListRows($data);
        
        // Подготвяме лентата с инструменти
        $this->prepareListToolbar($data);
        
        return $data;
    }
    
    
    /**
     * Създаване на шаблона за общия List-изглед
     */
    public function renderDetailLayout_($data)
    {
        $className = cls::getClassName($this);
        
        // Шаблон за листовия изглед
        $listLayout = new ET("
            <div class='clearfix21 {$className}'>
            	<div class='listTopContainer clearfix21'>
                    [#ListFilter#]
                </div>
                [#ListPagerTop#]
                [#ListTable#]
                [#ListSummary#]
                [#ListPagerBottom#]
                [#ListToolbar#]
                
            </div>
        ");
        
        return $listLayout;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    public function renderDetail_($data)
    {
        if (!isset($data->listClass)) {
            $data->listClass = 'listRowsDetail';
        }
        
        if (empty($this->currentTab)) {
            $this->currentTab = $data->masterMvc;
        }

        // Рендираме общия лейаут
        $tpl = $this->renderDetailLayout($data);
        
        // Попълваме формата-филтър
        $tpl->append($this->renderListFilter($data), 'ListFilter');
        
        // Попълваме обобщената информация
        $tpl->append($this->renderListSummary($data), 'ListSummary');
        
        // Попълваме таблицата с редовете
        setIfNot($data->listTableMvc, clone $this);
        $data->hideListFieldsIfEmpty = arr::make($this->hideListFieldsIfEmpty, true);
        $tpl->append($this->renderListTable($data), 'ListTable');
        
        // Попълваме таблицата с редовете
        $pagerHtml = $this->renderListPager($data);
        $tpl->append($pagerHtml, 'ListPagerTop');
        $tpl->append($pagerHtml, 'ListPagerBottom');
        
        // Попълваме долния тулбар
        $tpl->append($this->renderListToolbar($data), 'ListToolbar');
        
        return $tpl;
    }
    
    
    /**
     * Подготвя заявката за данните на детайла
     */
    public function prepareDetailQuery_($data)
    {
        // Създаваме заявката
        $data->query = $this->getQuery();
        
        // Добавяме връзката с мастер-обекта
        $data->query->where("#{$data->masterKey} = {$data->masterId}");
        
        return $data;
    }
    
    
    /**
     * Подготвя лентата с инструменти за табличния изглед
     */
    public function prepareListToolbar_(&$data)
    {
        $data->toolbar = cls::get('core_Toolbar');
        
        $masterKey = $data->masterKey;
        
        if ($data->masterId) {
            $rec = new stdClass();
            $rec->{$masterKey} = $data->masterId;

            if ($this->haveRightFor('add', $rec) && $data->masterId && $this->listAddBtn !== false) {
                $data->toolbar->addBtn('Нов запис', array($this, 'add', $masterKey => $data->masterId, 'ret_url' => true),  'id=btnAdd', 'ef_icon = img/16/star_2.png,title=Създаване на нов запис');
            }
        }

        if($this->haveRightFor('selectrowstodelete', (object)array($masterKey => $data->masterId))){
            $data->toolbar->addBtn('Изтриване', array($this, 'selectRowsToDelete', $masterKey => $data->masterId, 'ret_url' => true,), 'id=btnDellAll', 'ef_icon = img/16/deletered.png,title=Форма за избор на редове за изтриване,order=500,class=selectDeleteRowsBtn');
        }

        return $data;
    }
    
    
    /**
     * Подготвя формата за редактиране
     */
    public function prepareEditForm_($data)
    {
        setIfNot($data->singleTitle, $this->singleTitle);
        
        parent::prepareEditForm_($data);
        
        $form = $data->form;
        
        if (!$data->masterMvc) {
            $data->masterMvc = $this->getMasterMvc($data->form->rec);
        }

        if (empty($this->currentTab)) {
            $this->currentTab = $data->masterMvc;
        }

        if (!$data->masterKey) {
            $data->masterKey = $this->getMasterKey($data->form->rec);
        }
        
        // Очакваме да masterKey да е зададен
        expect($data->masterKey, $data);
        if($this->requireMasterBeInstanceOfCoreMaster){
            expect($data->masterMvc instanceof core_Master, $data);
        }
        
        $masterKey = $data->masterKey;
        
        if (!isset($form->fields[$masterKey]->input) || $form->fields[$masterKey]->input == 'none') {
            $form->fields[$masterKey]->input = 'hidden';
        }
        
        expect($data->masterId = $data->form->rec->{$masterKey}, $data->form->rec);
        expect($data->masterRec = $data->masterMvc->fetch($data->masterId), $data);
        
        return $data;
    }
    
    
    /**
     * Подготвя заглавието на формата
     *
     * @param stdClass $data
     */
    public function prepareEditTitle_($data)
    {
        $data->form->title = static::getEditTitle($data->masterMvc, $data->masterId, $data->singleTitle, $data->form->rec->id, $this->formTitlePreposition);
    }
    
    
    /**
     * Помощна ф-я, която връща заглавие за формата при добавяне на детайл към клас
     * Изнесена е статично за да може да се използва и от класове, които не наследяват core_Detail,
     * Но реално се добавят като детайли към друг клас
     *
     * @param mixed    $master      - ид на класа на мастъра
     * @param int      $masterId    - ид на мастъра
     * @param string   $singleTitle - еденично заглавие
     * @param int|NULL $recId       - ид на записа, ако има
     * @param string   $preposition - предлог
     * @param int|NULL $len         - максимална дължина на стринга
     *
     * @return string $title      - заглавието на формата на 'Детайла'
     */
    public static function getEditTitle($master, $masterId, $singleTitle, $recId, $preposition = null, $len = null)
    {
        if (!$preposition) {
            $preposition = tr('към');
        }

        if ($singleTitle) {
            $single = ' на|* ' . tr(mb_strtolower($singleTitle));
        }

        $title = ($recId) ? "Редактиране{$single} {$preposition}" : "Добавяне{$single} {$preposition}";
        $title .= ' ' . cls::get($master)->getFormTitleLink($masterId);

        return $title;
    }
    
    
    /**
     * Дефолт функция за определяне мастера, спрямо дадения запис
     */
    public function getMasterMvc_($rec)
    {
        return $this->Master;
    }
    
    
    /**
     * Дефолт функция за определяне полето-ключ към мастера, спрямо дадения запис
     */
    public function getMasterKey_($rec)
    {
        return $this->masterKey;
    }
    
    
    /**
     * Връща ролите, които могат да изпълняват посоченото действие
     */
    public function getRequiredRoles_(&$action, $rec = null, $userId = null)
    {
        if ($action == 'read') {
            // return 'no_one';
        }
        
        if ($action == 'write' && isset($rec) && $this->Master instanceof core_Master) {
            expect($masterKey = $this->masterKey);
            
            if ($rec->{$masterKey}) {
                $masterRec = $this->Master->fetch($rec->{$masterKey});
            }
            
            if ($masterRec) {
                
                return $this->Master->getRequiredRoles('edit', $masterRec, $userId);
            }
        }

        // За екшъна за изтриване на избрани редове, се изисква да има поне един запис, който може да се изтрива
        if($action == 'selectrowstodelete'){
            if(!$this->addDeleteSelectRows || (!$this->hasPlugin('plg_RowTools') && !$this->hasPlugin('plg_RowTools2')) || $this->hasPlugin('plg_Select')) return 'no_one';

            $actionCast = 'delete';
            $res = parent::getRequiredRoles_($actionCast, $rec, $userId);

            if($res != 'no_one'){

                if(isset($rec->{$this->masterKey})){
                    $query = static::getQuery();
                    $query->where("#{$this->masterKey} = {$rec->{$this->masterKey}}");

                    // Ако има указани допълнителни полета за филтриране на детайлите
                    if(isset($rec->_filterFld)){
                        $sign = ($rec->_filterFldNot) ? '!=' : '=';
                        $query->where("#{$rec->_filterFld} {$sign} '{$rec->_filterFldVal}'");
                    }

                    $canDeleteCount = 0;
                    $haveDeletableMoreThanOneRec = false;
                    while ($dRec = $query->fetch()){
                        if(static::haveRightFor('delete', $dRec)){
                            $canDeleteCount++;
                            if($canDeleteCount >= 2) {
                                $haveDeletableMoreThanOneRec = true;
                                break;
                            }
                        }
                    }

                    if(!$haveDeletableMoreThanOneRec){
                        $res = 'no_one';
                    }
                } else {
                    $res = 'no_one';
                }
            }

            return $res;
        }


        return parent::getRequiredRoles_($action, $rec, $userId);
    }
    
    
    /**
     * След запис в детайла извиква събитието 'AfterUpdateDetail' в мастъра
     */
    public function save_(&$rec, $fieldsList = null, $mode = null)
    {
        if (!$id = parent::save_($rec, $fieldsList, $mode)) {
            
            return false;
        }
        
        $masterKey = $this->masterKey;
        
        $masters = $this->getMasters($rec);
        
        foreach ($masters as $masterKey => $masterInstance) {
            if ($rec->{$masterKey}) {
                $masterId = $rec->{$masterKey};
            } elseif ($rec->id) {
                $masterId = $this->fetchField($rec->id, $masterKey);
            }
            
            // Ако в сесията е спряно обновяването на мастъра, спира се
            $stopMasterUpdate = Mode::get("stopMasterUpdate{$rec->{$masterKey}}");
            if ($stopMasterUpdate === true) {
                break;
            }
            
            $masterInstance->invoke('AfterUpdateDetail', array($masterId, $this));
        }
        
        return $id;
    }
    
    
    /**
     * Логва действието
     *
     * @param string            $msg
     * @param NULL|stdClass|int $rec
     * @param string            $type
     */
    public function logInAct($msg, $rec = null, $type = 'write')
    {
        if (is_numeric($rec)) {
            $rec = $this->fetch($rec);
        }
        
        $masterKey = $this->masterKey;
        $masters = $this->getMasters($rec);
        
        $newMsg = $msg . ' на детайл';

        if(countR($masters)){
            foreach ($masters as $masterKey => $masterInstance) {
                if ($rec->{$masterKey}) {
                    $masterId = $rec->{$masterKey};
                } elseif ($rec->id) {
                    $masterId = $this->fetchField($rec->id, $masterKey);
                }

                if ($type == 'write') {
                    $masterInstance->logWrite($newMsg, $masterId);
                } else {
                    $masterInstance->logRead($newMsg, $masterId);
                }
            }
        }
        
        parent::logInAct($newMsg, $rec, $type);
    }
    
    
    /**
     * След изтриване в детайла извиква събитието 'AfterUpdateDetail' в мастъра
     */
    public static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        if ($numRows) {
            foreach ($query->getDeletedRecs() as $rec) {
                $masters = $mvc->getMasters($rec);
                
                foreach ($masters as $masterKey => $masterInstance) {
                    $masterId = $rec->{$masterKey};
                    $masterInstance->invoke('AfterUpdateDetail', array($masterId, $mvc));
                }
            }
        }
    }
    
    
    /**
     *
     *
     * @see core_Manager::act_Delete()
     */
    public function act_Delete()
    {
        $id = Request::get('id', 'int');
        
        $rec = $this->fetch($id);
        
        $masterKey = $this->masterKey;
        
        $masters = $this->getMasters($rec);
        
        foreach ($masters as $masterKey => $masterInstance) {
            if ($rec->{$masterKey}) {
                $masterId = $rec->{$masterKey};
            } elseif ($rec->id) {
                $masterId = $this->fetchField($rec->id, $masterKey);
            }
            
            $masterInstance->logInfo('Изтриване на детайл', $masterId);
        }
        
        return parent::act_Delete();
    }
    
    
    /**
     * Връща списъка от мастър-мениджъри на зададен детайл-запис.
     *
     * Обикновено детайлите имат точно един мастър. Използваме този метод в случаите на детайли
     * с повече от един мастър, който евентуално зависи и от данните в детайл-записа $rec.
     *
     * @param stdClass $rec
     *
     * @return array масив от core_Master-и. Ключа е името на полето на $rec, където се
     *               съхранява външния ключ към съотв. мастър
     */
    public function getMasters_($rec)
    {
        return isset($this->Master) ? array($this->masterKey => $this->Master) : array();
    }
    
    
    /**
     * Връща линк към подадения обект
     *
     * @param int $objId
     *
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        $me = get_called_class();
        $inst = cls::get($me);
        
        if (isset($objId) && ($masterKey = $inst->masterKey) && is_object($inst->Master) && ($inst->Master instanceof core_Master)) {
            $rec = $inst->fetch($objId);
            
            $masterId = $rec->{$masterKey};
            
            return $inst->Master->getLinkForObject($masterId);
        }
        
        return parent::getLinkForObject($objId);
    }
    
    
    /**
     * Подготвя формата за филтриране
     */
    public function prepareListFilter_($data)
    {
        parent::prepareListFilter_($data);
        
        // Ако детайла се подготвя за показване в мастъра
        if (is_object($data->listFilter) && isset($data->masterMvc)) {
            
            // Кои са хидън полетата на мастъра?
            $masterFields = $data->masterMvc->selectFields("#input == 'hidden'");
            
            // Ако има те се махат от лист фийлда на детайла да няма засечка между полетата
            foreach ($masterFields as $name => $fld) {
                unset($data->listFilter->fields[$name]);
            }
        }
        
        return $data;
    }


    /**
     * Екшън за групово изтриване на детайлите
     */
    public function act_selectRowsToDelete()
    {
        expect($masterId = Request::get($this->masterKey, 'int'));
        $this->requireRightFor('selectrowstodelete', (object)array($this->masterKey => $masterId));
        $deleteAllUrl = toUrl(array($this, 'selectrowstodelete', "{$this->masterKey}" => $masterId));

        $form = cls::get('core_Form');
        $form->title = 'Изтриване на редове от|* <b>' . cls::get($this->Master)->getFormTitleLink($masterId) . '</b>';
        $form->info = new core_ET("");
        $form->FLD('selected', 'varchar', 'caption=Избрани', 'silent,input=hidden');
        $form->input('selected', 'silent');

        $query = $this->getQuery();
        $query->where("#{$this->masterKey} = {$masterId}");
        $filterFld = Request::get('_filterFld', 'varchar');
        $filterNot = Request::get('_filterFldNot', 'varchar');
        $filterFldVal = Request::get('_filterFldVal', 'varchar');
        if(!empty($filterFld)){
            $sign = ($filterNot) ? '!=' : '=';
            $query->where("#{$filterFld} {$sign} '{$filterFldVal}'");
        }

        // Визуализиране на редовете за изтриване
        $data = (object)array('masterMvc' => $this->Master, 'masterData' => (object)array('rec' => $this->Master->fetch($masterId)), 'recs' => array(), 'rows' => array(), 'masterId' => $masterId, 'query' => $query);

        // Ако са повече от 500 да се показват първите 500
        $count = $query->count();
        if($count > 500){
            $data->query->limit(500);
            $count = core_Type::getByName('int')->toVerbal(500);
            $form->info->append(tr("|*<div style='font-size:1.2em;margin-bottom:5px;'>|Показване на първите|*: <b>{$count}</b></div>"));
        }

        Mode::push('selectRows2Delete', true);
        $this->prepareListFields($data);
        $this->prepareListRecs($data);
        $this->prepareListRows($data);
        $data->listTableMvc = clone $this;
        foreach ($data->rows as $id => $row){
            unset($row->_rowTools);
            if(!static::haveRightFor('delete', $data->recs[$id])) continue;
            $row->btn = "<input type='checkbox' name='C[{$id}]' id='cb_{$id}' class='inline-checkbox defaultDeleteRowCheckbox' data-selectedId='{$id}' checked title='Маркиране на реда за изтриване'>";
        }

        $btnAll = "<input type='checkbox' name='checkAllRows' checked class='inline-checkbox' title='Маркиране/размаркирване на всички редове за изтриване'>";
        $data->listFields = array('btn' => "|* {$btnAll}") + $data->listFields;
        $data->hideListFieldsIfEmpty = arr::make($this->hideListFieldsIfEmpty, true);
        $data->listTableMvc->FLD('btn', 'varchar', 'tdClass=centered vtop');
        $docTableTpl = $this->renderListTable($data);
        Mode::pop('selectRows2Delete');

        $form->info->append($docTableTpl);
        $form->input();

        // Ако има събмитнати редове за изтриване - да се изтрият
        if($form->rec->selected){
            $selectedArr = explode('|', $form->rec->selected);
            if(countR($selectedArr)){
                $str = implode(',', $selectedArr);
                static::delete("#{$this->masterKey} = {$masterId} AND #id IN ({$str})");

                $this->Master->logWrite('Изтриване на избрани редове', $masterId);
                redirect($this->Master->getSingleUrlArray($masterId), 'Успешно са изтрити избраните редове|*!');
            }
        }

        $form->toolbar->addFnBtn('Изтриване', '', array('class' => 'deleteAllCheckedRows', 'ef_icon' => 'img/16/deletered.png', 'data-url' => $deleteAllUrl, 'data-errorMsg' => tr('Моля изберете редове за изтриване|*!')));
        $form->toolbar->addBtn('Назад', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Назад към заданието');

        $tpl = $this->renderWrapping($form->renderHtml());
        jquery_Jquery::run($tpl, 'detailDeleteRowsAct();');

        return $tpl;
    }

    /**
     * След взимане на полетата, които да не се клонират
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $rec
     */
    public static function on_AfterGetFieldsNotToClone($mvc, &$res, $rec)
    {
        $fieldsNotToClone = arr::make($mvc->fieldsNotToClone, true);

        if (!is_array($res)) {
            $res = $fieldsNotToClone;
        } else {
            $res += $fieldsNotToClone;
        }
    }
}
