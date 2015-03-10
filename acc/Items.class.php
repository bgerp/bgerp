<?php



/**
 * Мениджър регистър на счетоводните пера
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Items extends core_Manager
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State2, plg_RowTools, editwatch_Plugin, plg_Search,
                     plg_SaveAndNew, acc_WrapperSettings, Lists=acc_Lists, plg_Sorting';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, num';
    
    
    /**
     * Заглавие
     */
    var $title = 'Пера';
    
    
    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Перо';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,acc';
    
    
    /**
     * Кой е може да го администрира?
     */
    var $canAdmin = 'ceo,acc';
    
    
    /**
     * @var acc_Lists
     */
    var $Lists;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'num,titleLink=Наименование,uomId,lastUseOn,tools=Пулт,createdBy,state';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Опашка от записи за записване в on_Shutdown
     *
     * @var array Масив от записи на acc_Items (с ключове - ид-та на записи)
     * @see acc_Items::touch()
     */
    protected $touched = array();
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();
    
    
    /**
     * Шаблон (ET) за заглавие на перо
     *
     * @var string
     */
    public $recTitleTpl = '[#title#] ( [#num#] )';
    
    
    /**
     * Кеш на уникален индекс
     */
    protected $unique = 0;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Разпознаваем от човек код на перото.
        $this->FLD('num', 'varchar(64)', "caption=Код,mandatory,remember=info,notNull");
        
        // Заглавие
        $this->FLD('title', 'varchar', 'caption=Наименование,mandatory,remember=info');
        
        // Външен ключ към номенклатурата на това перо.
        $this->FLD('lists', 'keylist(mvc=acc_Lists,select=nameLink)', 'caption=Номенклатури,input');
        
        // Външен ключ към модела (класа), генерирал това перо. Този клас трябва да реализира
        // интерфейса, посочен в полето `interfaceId` на мастъра @link acc_Lists 
        $this->FLD('classId', 'class(interface=acc_RegisterIntf,select=title,allowEmpty)',
            'caption=Регистър,input=hidden,silent');
        
        // Външен ключ към обекта, чиято сянка е това перо. Този обект е от класа, посочен в
        // полето `classId` 
        $this->FLD('objectId', 'int', "input=hidden,silent,column=none,caption=Обект");
        
        // Мярка на перото. Има смисъл само ако мастър номенклатурата е отбелязана като 
        // "оразмерима" (acc_Lists::isDimensional == true). Мярката се показва и въвежда само 
        // ако има смисъл.
        $this->FLD('uomId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,remember');
        
        // Състояние на перото
        $this->FLD('state', 'enum(active=Активно,closed=Затворено)', 'caption=Състояние,input=none');
        
        // Кога за последно е използвано
        $this->FLD('lastUseOn', 'datetime(format=smartTime)', 'caption=Последно,input=none');
        
        // Титла - хипервръзка
        $this->FNC('titleLink', 'html', 'column=none');
        $this->FNC('titleNum', 'varchar', 'column=none');
        
        $this->setDbUnique('objectId,classId');
    }
    
    
    /**
     * За полето titleNum създава линк към обекта от регистъра
     *
     * @internal: Това не е добро решение, защото това функционално поле ще се изчислява в много случаи без нужда.
     */
    static function on_CalcTitleNum($mvc, $rec)
    {
    	$rec->titleNum = $rec->title . " ({$rec->num})";
    }
    
    
    /**
     * За полето titleLink създава линк към обекта от регистъра
     *
     * @internal: Това не е добро решение, защото това функционално поле ще се изчислява в много случаи без нужда.
     */
    static function on_CalcTitleLink($mvc, $rec)
    {
        $title = $mvc->getVerbal($rec, 'title');
        $num = $mvc->getVerbal($rec, 'num');
        $rec->titleLink = $title . " ($num)";
    }
    
    
    /**
     * След като е готово вербалното представяне
     */
    static function on_AfterGetVerbal($mvc, &$num, $rec, $part)
    {
        if($part == 'titleLink'){
            
            // Задаваме уникален номер на контейнера в който ще се реплейсва туултипа
            $mvc->unique ++;
            $unique = $mvc->unique;
            
            $id = (is_object($rec)) ? $rec->id : $rec;
            $tooltipUrl = toUrl(array('acc_Items', 'showItemInfo', $id, 'unique' => $unique), 'local');
            
            $arrow = ht::createElement("span", array('class' => 'anchor-arrow tooltip-arrow-link', 'data-url' => $tooltipUrl));
            $arrow = "<span class='additionalInfo-holder'><div class='additionalInfo' id='info{$unique}'></div>{$arrow}</span>";
            $num .= "&nbsp;{$arrow}";
        }
    }
    
    
    /**
     * Преди запис на перо
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if($rec->id){
            // Запомняне на старите номенклатури
            $rec->oldLists = $mvc->fetchField($rec->id, 'lists');
        }
    }
    
    
    /**
     * Изпълнява се след запис на перо
     * Предизвиква обновяване на обобщената информация за перата
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
        // Информацията на кои номенклатури трябва да се обнови
        $lists = keylist::toArray($rec->lists) + keylist::toArray($rec->oldLists);
        
        foreach ($lists as $listId) {
            $mvc->Lists->updateSummary($listId);
        }
        
        // Ако няма информация за мениджър, acc_Items става мениджър
        if(empty($rec->classId) && empty($rec->objectId)){
            $rec->classId = $mvc->getClassId();
            $rec->objectId = $rec->id;
            $mvc->save($rec);
        }
        
        // Ако няма номенклатури, и перото е активно - затваряме го
        if(empty($rec->lists) && $rec->state == 'active'){
            $rec->state = 'closed';
            $mvc->save($rec);
        }
        
        // Синхронизира свойствата на перото
        acc_Features::syncItem($id);
    }
    
    
    /**
     * Изпълнява се преди изтриване на пера
     * Събира информация, на кои номенклатури трябва да си обновят информацията
     */
    static function on_BeforeDelete($mvc, &$numRows, $query, $cond)
    {
        $tmpQuery = clone($query);
        $query->_listsForUpdate = array();
        
        while($rec = $tmpQuery->fetch($cond)) {
            $query->_listsForUpdate += keylist::toArray($rec->lists);
        }
    }
    
    
    /**
     * Изпълнява се след изтриване на пера
     * Предизвиква обновяване на информацията на подбрани преди изтриване номенклатури
     */
    static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        if(count($query->_listsForUpdate)) {
            foreach($query->_listsForUpdate as $listId) {
                $mvc->Lists->updateSummary($listId);
            }
        }
    }
    
    
    /**
     * Извиква се преди подготовката на титлата в списъчния изглед
     */
    static function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        $listId = $mvc->getCurrentListId();
        $listRec = $mvc->Lists->fetch($listId);
        
        $data->title = "Пера в номенклатурата|* <span class=\"green\"> {$listRec->caption} </span>";
        
        return FALSE;
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec  = &$form->rec;
        
        if (!$rec->id && $rec->classId && $rec->objectId) {
            if ($_rec = $mvc::fetchItem($rec->classId, $rec->objectId)) {
                $rec = $_rec;
            }
        }
        
        if ($rec->classId && $rec->objectId) {
            
            expect($register = core_Cls::getInterface('acc_RegisterIntf', $rec->classId));
            
            $form->setField('num', 'input=none');
            $form->setField('title', 'input=none');
            
            if (!$rec->id) {
                // Попълва полетата на $rec с данни извлечени от съотв. регистър
                static::syncItemRec($rec, $register, $rec->objectId);
                
                $mvc::on_CalcTitleLink($mvc, $rec);
            }
            
            $form->info = $mvc->getVerbal($rec, 'titleLink');
        }
        
        $form->setSuggestions('lists', acc_Lists::getPossibleLists($rec->classId));
        $form->setDefault('ret_url', Request::get('ret_url'));
        
        if($listId = Request::get('listId', 'int')){
            $form->setDefault('lists', array($listId => $listId));
            $form->title = "|Добавяне на перо в|* " . acc_Lists::getVerbal($listId, 'name');
        }
        
        if ($rec->id) {
            $form->title = "|Редактиране на перо|*";
        }
        
        $listId = $mvc->getCurrentListId();
        $listRec = $mvc->Lists->fetch($listId);
        if($listRec->isDimensional == 'no') {
        	$form->setField('uomId', 'input=none');
        }
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от заявката във формата
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        if(!$form->rec->id) {
            $listId = $mvc->getCurrentListId();
            Mode::setPermanent('lastEnterItemNumIn' . $listId, $form->rec->num);
        }
        
        if(!empty($form->rec->lists)){
        	
        	// Ако има избрани номенклатури: перото винаги става активно
        	$form->rec->state = 'active';
        }
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        $listId = $mvc->getCurrentListId();
        $listRec = $mvc->Lists->fetch($listId);
        
        if($listRec->isDimensional == 'no') {
            unset($data->listFields['uomId']);
        }
        
        if($listRec->regInterfaceId) {
            unset($data->listFields['tools']);
        }
    }
    
    
    /**
     * Добавя филтър към перата
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('listId', 'key(mvc=acc_Lists,select=name)', 'input,caption=Номенклатура,refreshForm');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'listId, search';
        
        $data->listFilter->setDefault('listId', $listId = $mvc->getCurrentListId());
        
        $filter = $data->listFilter->input();
        
        expect($filter->listId);
        
        $data->query->where("#lists LIKE '%|{$filter->listId}|%'");
        
        $data->query->orderBy('#num');
    }
    
    
    /**
     * След подготовка на ролите
     */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        if(($action == 'add' || $action == 'edit') && isset($rec->classId)){
            if(cls::load($rec->classId, TRUE)){
                $Class = cls::get($rec->classId);
                
                if(!$Class->haveRightFor('edit', (object)array('id' => $rec->objectId))){
                    $res = 'no_one';
                } else {
                	
                	// Ако перото е документ, то то не трябва да е чернова
                	if(cls::haveInterface('doc_DocumentIntf', $rec->classId)){
                		$state = cls::get($rec->classId)->fetchField($rec->objectId, 'state');
                		if($state == 'draft'){
                			$res = 'no_one';
                		}
                	}
                }
            }
        }
        
        if($action == 'add' && isset($rec->lists)){
            if(!is_array($rec->lists)) return;
            
            // Ако избраната номенклатура има изискване за интерфейси
            $listRec = acc_Lists::fetch(reset($rec->lists));
            
            if($listRec->regInterfaceId){
                $intName = core_Interfaces::fetchField($listRec->regInterfaceId, 'name');
                $options = core_Classes::getOptionsByInterface($intName);
                
                // Ако е само един наличния мениджър и той има 'autoList' с тази
                // номенклатура, не може да се добавя перо от тук.
                if(count($options) == 1){
                    $Class = cls::get(reset($options));
                    
                    if(isset($Class->autoList) && $Class->autoList == $listRec->systemId){
                        $res = 'no_one';
                    }
                }
            } else {
                if(!empty($listRec->systemId)){
                    
                    // Ако няма интерфейс и има систем ид, не може да се добавя от интерфейса
                    $res = 'no_one';
                }
            }
        }
        
        // Дали може да се импортират данни от мениджъри отговарящи на наличния интерфейс
        if($action == 'insert' && isset($rec->listId)){
            $res = $mvc->getRequiredRoles('add', (object)array('lists' => arr::make($rec->listId, TRUE)));
            $listRec = acc_Lists::fetch($rec->listId);
            
            // Ако избраната номенклатура, няма интерфейс - не може
            if(empty($listRec->regInterfaceId)){
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Тази функция връща текущата номенклатура, като я открива по първия възможен начин:
     *
     * 1. От Заявката (Request)
     * 2. От Сесията (Mode)
     * 3. Първата активна номенклатура от таблицата
     */
    function getCurrentListId()
    {
        $listId = Request::get('listId', 'key(mvc=acc_Lists,select=name)');
        
        if(!$listId) {
            $listId = Mode::get('currentListId');
        }
        
        if(!$listId) {
            $listQuery = $this->Lists->getQuery();
            $listQuery->orderBy('num');
            $listRec = $listQuery->fetch('1=1');
            $listId = $listRec->id;
        }
        
        if($listId) {
            Mode::setPermanent('currentListId', $listId);
        } else {
            redirect(array('acc_Lists'));
        }
        
        return $listId;
    }
    
    
    /**
     * Предефиниране на подготовката на лентата с инструменти за табличния изглед
     */
    function prepareListToolbar_(&$data)
    {
        $data->toolbar = cls::get('core_Toolbar');
        
        $listId = $this->getCurrentListId();
        
        if($listId){
            // Проверка можели да добавяме записи пък това перо
            if ($this->haveRightFor('add', (object)array('lists' => arr::make($listId, TRUE)))) {
                $data->toolbar->addBtn('Нов запис', array($this, 'add', 'listId' => $listId), 'id=btnAdd', 'ef_icon = img/16/star_2.png,title=Създаване на нов запис');
            }
            
            // Можели да импортираме от модел, ако да махаме бутона за нормално добавяне
            if($this->haveRightFor('insert', (object)array('listId' => $listId))){
                $data->toolbar->removeBtn('btnAdd');
                $data->toolbar->addBtn("Избор", array($this, 'Insert', 'listId' => $listId, 'ret_url' => TRUE), 'ef_icon=img/16/table-import-icon.png,title=Бърз избор на кои записи да станат пера');
            }
        }
        
        return $data;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        if (!empty($data->form->toolbar->buttons['saveAndNew'])) {
            if($data->form->rec->classId && $data->form->rec->objectId){
                $data->form->toolbar->removeBtn('saveAndNew');
            }
        }
    }
    
    
    /**
     * Помощен метод за извличане на перо със зададени регистър и ключ в регистъра
     *
     * @param int $class
     * @param int $objectId
     * @param boolean $useCachedItems - дали да се използва кеширане на информацията за перата
     */
    public static function fetchItem($class, $objectId, $useCachedItems = FALSE)
    {
        $Class = cls::get($class);
        $self = cls::get(get_called_class());
        
        if($useCachedItems === TRUE){
        	$index = $Class->getClassId() . "|" . $objectId;
        	$cache = $self->getCachedItems();
        	
        	return $cache['indexedItems'][$index];
        } else {
        	return static::fetch("#classId = '{$Class->getClassId()}' AND #objectId = '{$objectId}'");
        }
    }
    
    
    /**
     * След промяна на запис на мениджър, на който acc_Items е екстендер
     *
     * Това събитие се генерира от @see groups_Extendable
     *
     * @param acc_Items $mvc
     * @param stdClass $regRec
     * @param core_Mvc $master
     */
    public static function on_AfterMasterSave(acc_Items $mvc, stdClass $regRec, core_Mvc $master)
    {
        $mvc::syncItemWith($master, $regRec->id);
    }
    
    
    /**
     * Синхронизира запис от регистър на пера със съответното му номенклатурно перо.
     *
     * @param core_Mvc $master
     * @param int $objectId;
     */
    public static function syncItemWith(core_Mvc $master, $objectId)
    {
        // Синхронизирането е възможно само с мениджъри поддържащи acc_RegisterIntf
        if (!core_Cls::haveInterface('acc_RegisterIntf', $master)) {
            return;
        }
        
        $classId  = $master::getClassId();
        
        if ($itemRec = static::fetchItem($classId, $objectId)) {
            static::syncItemRec($itemRec, $master, $itemRec->objectId);
            static::save($itemRec);
        }
    }
    
    
    /**
     * Синхронизира запис-перо с автентични данни извлечени от регистъра.
     *
     * @param acc_RegisterIntf $register
     * @param int $objectId
     * @param stdClass $itemRec
     */
    public static function syncItemRec(&$itemRec, $register, $objectId)
    {
        if (is_scalar($register)) {
            $register = cls::get($register);
        }
        
        if (!$regRec = $register->getItemRec($objectId)) {
            return FALSE;
        }
        
        if ($regRec) {
            $itemRec->num      = $regRec->num;
            $itemRec->title    = $regRec->title;
            $itemRec->uomId    = $regRec->uomId;
            $itemRec->features = $regRec->features;
            
            if (!empty($register->autoList)) {
                // Автоматично добавяне към номенклатурата $autoList
                expect($autoListId = acc_Lists::fetchField(array("#systemId = '[#1#]'", $register->autoList), 'id'));
                $itemRec->lists = keylist::addKey($itemRec->lists, $autoListId);
            }
        }
        
        return TRUE;
    }
    
    
    /**
     * Създава (ако липсва) перо, добавя го в номенклатира (ако не е) и го маркира като използвано
     *
     * @param int $classId
     * @param int $objectId
     * @param int $listId
     * @return int ИД на перото
     */
    public static function force($classId, $objectId, $listId, $useCachedItems = FALSE)
    {
        $rec = self::fetchItem($classId, $objectId, $useCachedItems);
        
        if (empty($rec)) {
            // Няма такова перо - създаваме ново и го добавяме в номенклатурата $listId
            $rec = new stdClass();
            $register = core_Cls::getInterface('acc_RegisterIntf', $classId);
            self::syncItemRec($rec, $register, $objectId);
        }
        
        $rec->classId  = $classId;
        $rec->objectId = $objectId;
        
        if (!empty($rec->id) && keylist::isIn($listId, $rec->lists)) {
            // Идеята е да се буферира многократното обновяване на едно и също перо само за
            // да му се смени състоянието и датата на последно използване
            self::touch($rec);
        } else {
            
            // Ако перото не е в номенкл. $listId (независимо дали се създава за пръв път или
            // вече го има), добавяме го и записваме на момента.
            $rec->lists = keylist::addKey($itemRec->lists, $listId);
            $rec->state      = 'active';
            $rec->lastUseOn = dt::now();
            
            self::save($rec);
        }
        
        return $rec->id;
    }
    
    
    /**
     * Запомня запис на перо за по късно обновление.
     *
     * @param stdClass $rec
     */
    public static function touch($rec)
    {
        // Вземаме инстация на acc_Items за да подсигурим извикването на acc_Items::on_Shutdown()
        $Items = cls::get(__CLASS__);
        $rec->lastUseOn = dt::now();
        
        expect($rec->id);
        
        // Тук само запомняме какво е "пипнато" (използвано). Същинското обновяване се прави в on_Shutdown()
        $Items->touched[$rec->id] = $rec;
    }
    
    
    /**
     * Изчиства записите, заопашени за запис
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
        $mvc->flushTouched();
    }
    
    
    /**
     * Обновява последното използване на всички заопашени пера
     */
    public function flushTouched()
    {
        if(count($this->touched)){
        	$timeLimit = count($this->touched) * 2;
        	core_App::setTimeLimit($timeLimit);
        	
            foreach ($this->touched as $rec) {
                $this->save($rec, 'lastUseOn');
            }
        }
    }
    
    
    /**
     * Метод пораждащ събитие 'AfterJournalItemAffect'в мениджъра на перото
     *
     * @param mixed $id - обект или запис на перо
     * @return void
     */
    public static function notifyObject($id)
    {
        $rec = static::fetchRec($id);
        
        // Опитваме се да заредим класа на перото
        if($rec && cls::load($rec->classId, TRUE)){
            $Class = cls::get($rec->classId);
            $objectRec = $Class->fetch($rec->objectId);
            $Class->invoke('AfterJournalItemAffect', array($objectRec, $rec));
        }
    }
    
    
    /**
     * Екшън за бързо вкарване на пера в номенкатура
     */
    function act_Insert()
    {
        expect($listId = Request::get('listId', 'int'));
        $this->requireRightFor('insert', (object)array('listId' => $listId));
        expect($listRec = acc_Lists::fetch($listId));
        
        $intName = core_Interfaces::fetchField($listRec->regInterfaceId, 'name');
        $options = core_Classes::getOptionsByInterface($intName);
        $listTitle = acc_Lists::getVerbal($listId, 'name');
        
        $form = cls::get('core_Form');
        $form->title = "Добавяне на пера към номенклатура|* '{$listTitle}'";
        
        foreach ($options as $className){
            $this->prepareInsertForm($form, $className, $listId);
        }
        $form->input();
        
        $fields = $form->selectFields();
        
        // Ако няма налични пера редирект
        if(!count($fields)) return followRetUrl(NULL, tr('Няма налични пера за избор'));
        
        if($form->isSubmitted()){
            $areAdded = FALSE;
            $fieldNames = '';
            
            foreach ($fields as $name => $fld){
                $fieldNames .= "$name,";
                
                if($items = keylist::toArray($form->rec->{$name})){
                    foreach($items as $id){
                        
                        // Всеки избран запис, се добавя като перо към номенклатурата
                        acc_Lists::addItem($listId, $name, $id);
                        $areAdded = TRUE;
                    }
                }
            }
            
            // Трябва да има поне едно избрано перо да се добави
            if(empty($areAdded)){
                $form->setError($fieldNames, 'Не са избрани пера');
            }
            
            if(!$form->gotErrors()){
                return followRetUrl(NULL, tr('Перата са добавени успешно'));
            }
        }
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png, title=Прекратяване на действията');
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Помощен метод за намиране на всички записи от даден мениджър,
     * които са пера в определена номенклатура
     * @param mixed $class - име на клас
     * @param int $listId - ид на намонклатура
     * @return array $items - списък с ид-та на обектите, които са пера
     */
    public static function getClassItems($class, $listId)
    {
        $items = array();
        expect($Class = cls::get($class));
        
        $itemsQuery = static::getQuery();
        $itemsQuery->like('lists', "|{$listId}|");
        $itemsQuery->where("#classId = {$Class->getClassId()}");
        $itemsQuery->show('objectId');
        
        while($itemRec = $itemsQuery->fetch()){
            $items[] = $itemRec->objectId;
        }
        
        return $items;
    }
    
    
    /**
     * Подготовка на полетата на формата за избиране на записи от мениджър,
     * които ще стават пера
     * @param core_Form $form - форма
     * @param mixed $className - име на клас
     * @param int $listId - ид на наменклатура
     */
    private function prepareInsertForm(core_Form &$form, $className, $listId)
    {
        $options = array();
        core_Debug::$isLogging = FALSE;
        $Class = cls::get($className);
        
        // Намират се перата, които вече участват на този мениджър
        $items = static::getClassItems($Class, $listId);
        
        // Извличат се всички записи на мениджъра, които не са пера
        $query = $Class->getQuery();
        $query->where("#state != 'rejected'");
        
        if(count($items)){
            $query->notIn('id', $items);
        }
        $query->show('id,state');
        
        // Дали е документ
        $isDoc = cls::haveInterface('doc_DocumentIntf', $Class);
        
        while ($cRec = $query->fetch()){
            
            // Ако е документ и е чернова, не може да стане перо
            if($isDoc && $cRec->state == 'draft') continue;
            
            $options[$cRec->id] = $Class->getTitleById($cRec->id);
        }
        
        if(count($options)) {
            $form->FNC($className, "keylist(mvc={$className},maxSuggestions=1)", "caption={$Class->title},input,columns=1");
            $form->setSuggestions($className, $options);
        }
        
        core_Debug::$isLogging = TRUE;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = NULL;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->objectId,
                'title' => $rec->title,
            );
        }
        
        return $result;
    }
    
    
    /**
     * Форсира системно перо, такова което не идва от мениджър,
     * уникалноста на перото е името и номенклатурите му
     *
     * @param string $title - име на перото
     * @param string $num - номер на перото
     * @param string $listSysId - систем ид на номенклатура
     */
    public static function forceSystemItem($title, $num, $listSysId)
    {
        $lists = keylist::addKey('', acc_Lists::fetchBySystemId($listSysId)->id);
        
        // Имали от същата номенклатура перо с такова име
        $item = static::fetch("#title = '{$title}' AND #lists LIKE '%$lists%'");
        
        // Ако няма го създаваме
        if(empty($item)){
            $item = new stdClass();
            $item->title = $title;
            $item->num = $num;
            $item->lists = $lists;
            
            static::save($item);
        }
        
        return $item;
    }
    
    
    /**
     * Изтрива всички затворени и неизползвани пера
     */
    public function cron_DeleteUnusedItems()
    {
        $numRows = $this->delete("#state = 'closed' AND #lastUseOn IS NULL");
        
        if($numRows){
            $this->log("Изтрити са {$numRows} неизползвани, затворени пера");
        }
    }
    
    
    /**
     * Показва информация за перото по Айакс
     */
    public function act_ShowItemInfo()
    {
        $id = Request::get('id', 'int');
        $unique = Request::get('unique', 'int');
        
        $rec = $this->fetchRec($id);
        $row = $this->recToVerbal_($rec);
        
        $cantShow = FALSE;
        
        if ($rec->classId && cls::load($rec->classId, TRUE)) {
            $AccRegister = cls::get($rec->classId);
            
            // Ако го има интерфейсния метод
            if(method_exists($AccRegister, 'getLinkToObj')) {
                $row->link = $AccRegister->getLinkToObj($rec->objectId);
            } elseif(method_exists($AccRegister, 'act_Single')) {
                
                // По дефолт е линк към сингъла, ако имаме права
                if($AccRegister->haveRightFor('single', $rec->objectId)) {
                    if($AccRegister->fetchField($rec->objectId)){
                        $row->link = ht::createLink(tr('Връзка'), array($AccRegister, 'Single', $rec->objectId));
                    } else {
                        $cantShow = TRUE;
                    }
                } else {
                    $row->link = "<span style='color:red'>" . tr('Нямате права') . "</span>";
                }
            }
        } else {
            $cantShow = TRUE;
        }
        
        // Ако има проблем при извличането на записа показваме съобщение
        if($cantShow){
            $row = new stdClass();
            $row->link = "<span style='color:red'>" . tr('Проблем с показването') . "</span>";
        }
        
        $tpl = getTplFromFile('acc/tpl/ItemTooltip.shtml');
        $tpl->placeObject($row);
        
        if (Request::get('ajax_mode')) {
            $resObj = new stdClass();
            $resObj->func = "html";
            $resObj->arg = array('id' => "info{$unique}", 'html' => $tpl->getContent(), 'replace' => TRUE);
            
            return array($resObj);
        } else {
            return $tpl;
        }
    }
    
    
    /**
     * Кешира всички пера в модела в два масива, единия е с индекс ид-то на перото другия е с индекс класа и ид-то на обекта
     * 
     * @return array - масив с кешираните пера
     * 
     * 		['items'] - масив с записите на перата с индекс ид-то им
     * 		['indexedItems'] - масив с записите на перата с индекс classId им и objectId
     */
    public function getCachedItems()
    {
    	$cache = new stdClass();
    	if(!count($this->cache)){
    		$query = $this->getQuery();
    		$query->show('title,num,classId,objectId,lists,state');
    		while($rec = $query->fetch()){
    			$this->cache['items'][$rec->id] = $rec;
    			$this->cache['indexedItems'][$rec->classId . "|" . $rec->objectId] = $rec;
    		}
    	}
    	
    	return $this->cache;
    }
}