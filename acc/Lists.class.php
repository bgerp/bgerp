<?php



/**
 * Клас 'acc_Lists' - Счетоводни номенклатури
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Lists extends core_Manager {
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'acc_WrapperSettings, plg_RowTools2,plg_State2, plg_Sorting, plg_Created';
    
    
    /**
     * Кои роли имат пълни права за този мениджър?
     */
    var $canAdmin = 'ceo,acc';
    
    
    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Заглавие
     */
    var $title = 'Номенклатури';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Номенклатура';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'accMaster, ceo';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    //var $rowToolsField = 'tools';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'nameLink=Наименование,num=Код,itemsCnt=Пера,lastUseOn,isDimensional,featureList';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Трибуквен, уникален номер
        $this->FLD('num', 'int(3,size=3)', 'caption=Код,remember=info,mandatory,notNull,export');
        
        // Име на номенклатурата
        $this->FLD('name', 'varchar', 'caption=Номенклатура,mandatory,remember=info,mandatory,notNull,export');
        
        // Интерфейс, който трябва да поддържат класовете, генериращи пера в тази номенклатура
        $this->FLD('regInterfaceId', 'interface(suffix=AccRegIntf, allowEmpty, select=name)', 'caption=Интерфейс,export');
        
        // Колко пера има в тази номенклатура?
        $this->FLD('itemsCnt', 'int', 'caption=Пера->Брой,input=none');
        
        // Последно използване
        $this->FLD('lastUseOn', 'datetime(format=smartTime)', 'caption=Последно,input=none');
        
        // Състояние на номенклатурата
        $this->FLD('state', 'enum(active=Активна,closed=Затворена)', 'caption=Състояние,input=none');
        
        // System ID
        $this->FLD('systemId', 'varchar(32)', 'caption=System ID, export, input=hidden');
        
        // Заглавие 
        $this->FNC('caption', 'html', 'column=none');
        
        // Титла - хипервръзка
        $this->FNC('nameLink', 'html', 'column=none');
        
        // Титла - хипервръзка
        $this->FNC('title', 'html', 'column=none');
        
        // Дали елементите имат размерност
        $this->FLD('isDimensional', 'enum(no=Не,yes=Да)', 'caption=Размерност,smartCenter,export,maxRadio=2');
        
        // Списък със свойствата, които се поддържат от тази номенклатура
        $this->FLD('featureList', 'blob(serialize)', 'caption=Свойства,input=none,single=none');
      
        // Уникални индекси
        $this->setDbUnique('num');
        $this->setDbUnique('name');
    }
    
    
    /**
     * Изчислява полето 'caption', като конкатенира номера с името на номенклатурата
     */
    protected static function on_CalcCaption($mvc, $rec)
    {
        if (!$rec->name) {
            $rec->name = $mvc::fetchField($rec->id, 'name');
        }
        
        if (!$rec->num) {
            $rec->num = $mvc::fetchField($rec->id, 'num');
        }
        $rec->caption = $mvc->getVerbal($rec, 'name') . " (" . $mvc->getVerbal($rec, 'num') . ")";
    }
    
    
    /**
     * Изчислява полето 'nameLink', като име с хипервръзка към перата от тази номенклатура
     */
    protected static function on_CalcNameLink($mvc, $rec)
    {
        $name = $mvc->getVerbal($rec, 'name');
        $rec->nameLink = $name;
        
        if(acc_Lists::haveRightFor('list')){
            $rec->nameLink = ht::createLink($rec->nameLink, array ('acc_Items', 'list', 'listId' => $rec->id));
        }
    }
    
    
    /**
     * Изчислява полето 'title'
     */
    protected static function on_CalcTitle($mvc, $rec)
    {
        $name = $mvc->getVerbal($rec, 'name');
        $num = $mvc->getVerbal($rec, 'num');
        
        $rec->title = $num . '.&nbsp;' . $name;
    }
    
    
    /**
     * Извлича запис по име
     */
    public static function fetchByName($name)
    {
        $mvc = self::instance();

        return self::fetch(array ("#name = '[#1#]' COLLATE {$mvc->db->dbCharset}_general_ci", $name));
    }
    
    
    /**
     * Извлича запис на модела acc_Lists според системен идентификатор
     *
     * @param string $systemId
     * @return stdClass
     */
    public static function fetchBySystemId($systemId)
    {
        if(!isset(static::$cache[$systemId])){
        	static::$cache[$systemId] = self::fetch(array ("#systemId = '[#1#]'", $systemId));
        }
    	
    	return static::$cache[$systemId];
    }
    
    
    /**
     * Изпълнява се преди запис на номенклатурата
     */
    protected static function on_BeforeSave($mvc, $id, $rec)
    {
        if (!$rec->id) {
            $rec->itemCount = 0;
        }
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL)
    {
        if (($action == 'delete')) {
            
            //Позволява изтриването в дебъг режим от админ
            if (haveRole('admin') && isDebug()) return;
            
            if ($rec->id && ! isset($rec->itemsCnt)) {
                $rec = $mvc->fetch($rec->id);
            }
            
            if ($rec->itemsCnt || $rec->lastUseOn) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовка на формата за редактиране
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if (($data->form->rec->id && $data->form->rec->itemsCnt) || $data->form->rec->systemId) {
            
            // Забрана за промяна на интерфейса на непразните номенклатури
            $data->form->setReadonly('regInterfaceId');
        } else {
            $data->form->setField('regInterfaceId', 'allowEmpty');
        }
        
        $data->form->setDefault('isDimensional', 'no');
    }


    /**
     * Изпълнява се след конвертирането на вербалния запис
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = NULL)
    {
    	if(is_array($rec->featureList)){ 
    		$row->featureList = type_Varchar::escape(implode(', ', $rec->featureList));
    	}
    }
    
    
    /**
     * Предизвиква обновяване на обобщената информация за
     * номенклатура с посоченото id
     */
    public static function updateSummary($id)
    {
        expect($rec = self::fetch($id), $id);
        
        $itemsQuery = acc_Items::getQuery();
        $itemsQuery->where("#state = 'active'");
        $itemsQuery->where("#lists LIKE '%|{$id}|%'");
        
        // Обновяваме броя на перата в номенклатурата
        $rec->itemsCnt = $itemsQuery->count();
        
        // Намираме кога последно е използвано перо от номенклатурата
        $itemsQuery->XPR('lastused', 'datetime', 'max(#lastUseOn)');
        if($lastuse = $itemsQuery->fetch()->lastused){
        	$rec->lastUseOn = $lastuse;
        }
        
        // Обновяваме информацията за номенклатурата
        self::save($rec);
    }
    
    
    /**
     * Изпълнява се преди подготовката на показваните редове
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('num');
    }
    
    
    /**
     * Номенклатурите, в които е регистриран обект
     *
     * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
     * @param int $objectId
     * @return array ключове - ид-та на номенклатурите, в които е регистриран обекта,
     * стойности - наименования на номенклатурите.
     */
    public static function getItemLists($class, $objectId)
    {
        $result = array ();
        
        expect($classId = core_Classes::getId($class));
        
        $listIds = acc_Items::fetchField("#classId = {$classId} AND #objectId = {$objectId}", 'lists');
        
        if (count($listIds = keylist::toArray($listIds))) {
            foreach ($listIds as $listId) {
                $rec = self::fetch($listId);
                $result [$listId] = self::getVerbal($rec, 'title');
            }
        }
        
        return $result;
    }
    
    
    /**
     * Номенклатурите, в които могат да бъдат включвани като пера обектите от този клас
     *
     * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
     * @param int $objectId
     * @return array ключове - ид-та на номенклатурите, в които е регистриран обекта,
     * стойности - наименования на номенклатурите.
     */
    public static function getPossibleLists($class)
    {
        $result = array ();
        
        // Ако няма изискване за клас
        if (is_null($class)) {
            $query = static::getQuery();
            
            // Извличаме всички номенклатури без интерфейс и без systemId
            $query->where("(#regInterfaceId IS NULL OR #regInterfaceId = '') AND (#systemId IS NULL || #systemId = '')");
        } else {
            
            // Ако има клас проверяваме за тези номенклатури, чийто интерфейс е поддържан от класа
            $ifaceIds = array_keys(core_Interfaces::getInterfaceIds($class));
            
            if (count($ifaceIds)) {
                $query = static::getQuery();
                $query->where('#regInterfaceId IN (' . implode(',', $ifaceIds) . ')');
            }
        }
        
        if (isset($query)) {
            while ($rec = $query->fetch()) {
                $result [$rec->id] = self::getVerbal($rec, 'title');
            }
        }
        
        return $result;
    }
    
    
    /**
     * Добавя обект към номенклатура.
     *
     * Ако обекта не е в номенклатурата се добавя; ако е бил добавен преди - само състоянието
     * на перото става active.
     *
     * @param int $listId ид на номенклатура
     * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
     * @param int $objectId
     * @return int ид на перото, съответстващо на обекта в тази номенклатура
     */
    public static function addItem($listId, $class, $objectId)
    {
        if ($itemRec = self::fetchItem($class, $objectId)) {
            $lists = keylist::addKey($itemRec->lists, $listId);
        } else {
            $lists = $listId;
        }
        
        return self::updateItem($class, $objectId, $lists);
    }
    
    
    /**
     * Конвертира списък от номенклатури към масив.
     *
     * Списъкът може да бъде зададен по различни начини:
     *
     *  o като масив:          array(l1, l2, ...)
     *  o като keylist стринг: |l1|l2|...|
     *  o като стринг-масив:   l1, l2, ...
     *
     * Всеки елемент на списъка може да бъде стринг или цяло число. Те се интерпретират:
     *
     *  o стринг     - systemId на номенклатура
     *  o цяло число - първичен ключ на номенклатура
     *
     * @param array|string $lists списък от номенклатури
     * @return array масив от първични ключове на номенклатури (и по ключове, и по стойности)
     *
     */
    protected static function listsToArray($lists)
    {
        expect (is_null($lists) || is_array($lists) || is_string($lists) || is_int($lists));
        
        if (is_string($lists) && substr($lists, 0, 1) == '|' && substr($lists, -1, 1) == '|') {
            // Ако списъка е подаден като keylist, конвертираме го в масив
            $lists = keylist::toArray($lists);
        }
        
        $lists = arr::make($lists);  // NULL, стринг-масив или масив -> масив
        // Преобразуваме стринговите елементи към първични ключове (id-та)
        foreach ($lists as &$list) {
            if(!is_numeric($list)) {
                $list = static::fetchBySystemId($list)->id . "|";
            }
        }
        
        if (count($lists)) {
            $lists = array_combine($lists, $lists);
        }
        
        return $lists;
    }
    
    
    /**
     * Обновява информацията за перо или създава ново перо.
     *
     * Използва се за обновяване на данните на перо след промяна на съответната обект от регистър
     *
     * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
     * @param int $objectId
     * @param array|string|keylist $lists списък от номенклатури, към които да се добави перото
     * @param boolean $forced дали да обновяваме списъка към които е перото
     * Ако перото липсва - създава се
     * @return int ид на обновеното перо или null, ако няма такова перо
     */
    public static function updateItem($class, $objectId, $lists = NULL, $forced = TRUE)
    {
        // Нормализираме подадения списък от номенклатури
        $lists = self::listsToArray($lists);
        
        // Извличаме запис за перо (ако има)
        $itemRec = self::fetchItem($class, $objectId);
        
        if (!$itemRec && !$lists) {
            // Не може да се създава перо, което не е в нито една номенклатура
            return NULL;
        }
        
        $oldLists = array();
        
        if ($itemRec) {
            $oldLists = keylist::toArray(trim($itemRec->lists, '|'));
            
            // Ако $forced не е FALSE, сместваме досегашните номенклатури с новите
            if ($forced !== TRUE) {
                // Обединяваме текущия списък от номенклатури със зададения.
                $lists = array_merge($oldLists, $lists);
            }
        } else {
            $itemRec = new stdClass();
            $itemRec->classId = core_Classes::getId($class);
            $itemRec->objectId = $objectId;
        }
        
        // Номенклатурите, в които перото не е било, но сега ще бъде включено. Ще попитаме всяка
        // от тях дали ще приеме нашето перо.
        $addedToLists = array_diff($lists, $oldLists);
        
        if (!empty($addedToLists)) {
            // Перото трябва да поддържа интерфейса на всяка номенклатура, в която иска да бъде
            // добавено.
            $itemInterfaceIds = core_Interfaces::getInterfaceIds($itemRec->classId);
            
            foreach ($addedToLists as $listId) {
                $listIfaceId = static::fetchField($listId, 'regInterfaceId');
                expect(
                    empty($listIfaceId) || !empty($itemInterfaceIds[$listIfaceId]),
                    "Класът '" . core_Classes::fetchField($itemRec->classId, 'name') . "' не поддържа нужния интерфейс '" . core_Interfaces::fetchField($listIfaceId, 'name') . "'"
                );
            }
        }
        
        $itemRec->lists = keylist::fromArray($lists);
        
        // Извличаме от регистъра (през интерфейса `acc_RegisterIntf`), обновения запис за перо
        $AccRegister = cls::getInterface('acc_RegisterIntf', $class);
        
        acc_Items::syncItemRec($itemRec, $AccRegister, $objectId);
        
        $itemRec->state = empty($lists) ? 'closed' : 'active';
        
        if (($result = acc_Items::save($itemRec)) && $itemRec->state == 'active') {
            $AccRegister->itemInUse($objectId, TRUE);
            
            // Нотифициране на номенклатурите, от които перото е било премахнато
            $removedFromLists = array_diff($oldLists, $lists);
            
            foreach ($removedFromLists as $lid) {
                self::updateSummary($lid);
            }
        }
        
        return empty($result) ? NULL : $result;
    }
    
    
    /**
     * Изтрива перо, съответстващо на обект от регистър
     *
     * Затваря перо от регистъра, затворените и неизползваните пера се изтриват по крон
     *
     * @param mixed $class инстанция / име / ид (@see core_Classes::getId())
     * @param int $objectId
     * @return boolean true при успех, false при грешка, null при липсващо перо
     */
    public static function removeItem($class, $objectId)
    {
        $result = NULL;
        
        // Извличаме съществуващия запис за перо
        if ($itemRec = self::fetchItem($class, $objectId)) {
            
            // Перото е използвано - маркираме като 'closed', но не изтриваме
            $itemRec->state = 'closed';
            $result = acc_Items::save($itemRec);
        }
        
        $AccRegister = cls::getInterface('acc_RegisterIntf', $class);
        $AccRegister->itemInUse($objectId, FALSE);
        
        return $result;
    }
    
    
    /**
     * Взима записи от базата
     */
    private static function fetchItem($class, $objectId)
    {
        expect($Class = cls::get($class));
        $objectId = $Class->fetchRec($objectId)->id;
        
        $itemRec = acc_Items::fetch("#classId = {$Class->getClassId()} AND #objectId = {$objectId}");
        
        return $itemRec;
    }
    
    
    /**
     * Дали посочената номенклатура има размерност
     */
    public static function isDimensional($id)
    {
        $result =  ('yes' == self::fetchField($id, 'isDimensional'));
        
        return $result;
    }
    
    
    /**
     * Намира дали дадена номенклатура се намира в някоя от групуте на сметката
     * и на коя позиция
     * @param varchar $accSysId - systemId на сметката
     * @param mixed $iface - Име или Ид на Интефейса, който искаме да поддържа номенклатурата
     * @return mixed 1/2/3/NULL - Позицията на която е номенклатурата или
     * NULL ако не се среща
     */
    public static function getPosition($accSysId, $iface)
    {
        
        // Ако е подаден Ид на интерфейса очакваме да има такъв запис
        if (is_numeric($iface)) {
            expect($iface = core_Interfaces::fetch($iface), 'Няма такъв интерфейс');
        } else {
            
            expect($iface = core_Interfaces::fetch(array("#name='[#1#]'", $iface)), 'Няма такъв интерфейс');
        }
        $ifaceId = $iface->id;
        
        // Очакваме да има сметка с това systemId
        expect($acc = acc_Accounts::getRecBySystemId($accSysId), "Няма сметка със systemId {$accSysId}");
        
        // Извличаме информацията за номенклатурите на сметката
        $acc = acc_Accounts::getAccountInfo($acc->id);
        
        foreach ($acc->groups as $i => $list)  {
            
            // За всяка номенклатура проверяваме дали отговаря на този интерфейс
            if($list->rec->regInterfaceId == $ifaceId) {
                
                // Ако отговаря връщаме позицията на номенклатурата
                return $i;
            }
        }
        
        // Ако никоя номенклатура е поддържа интерфейса връщаме NULL
        return NULL;
    }
    
    
    /**
     * Обработка, преди импортиране на запис при начално зареждане
     */
    protected static function on_BeforeImportRec($mvc, $rec)
    {
        $rec->regInterfaceId = core_Interfaces::fetchField(array("#name = '[#1#]'", $rec->regInterfaceId), 'id');
        $rec->state = 'active';
    }


    /**
     * Обновява списъка с възможни свойства за дадена номенклатура
     * 
     * @param   int $listId id на номенклатура
     */
    public static function updateFeatureList($listId)
    {
        $rec = self::fetch($listId);
        $items = cls::get('acc_Items')->makeArray4Select('title', "#lists LIKE '%|{$listId}|%'", 'id');
        $features = array();
        if(is_array($items)) {
            $features = acc_Features::getFeatureOptions(array_keys($items));
        }
        $rec->featureList = $features;

        self::save($rec, 'featureList');
    }


    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        // Подготвяме пътя до файла с данните 
        $file = "acc/csv/Lists.csv";
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => "num",
            1 => "name",
            2 => "regInterfaceId",
            3 => "systemId",
            4 => "isDimensional"
        );
        
        // Импортираме данните от CSV файла. 
        // Ако той не е променян - няма да се импортират повторно 
        $cntObj = csv_Lib::importOnce($this, $file, $fields, NULL, NULL);
        
        // Записваме в лога вербалното представяне на резултата от импортирането 
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Връща бройката на перата в посочената номенклатура
     * 
     * @param mixed $listId    - ид или систем ид на номенклатура
     * @param string $systemId - дали $listId е систем ид или не
     * @return int             - брой пера в номенклатурата
     */
    public static function getItemsCountInList($listId, $systemId = TRUE)
    {
    	if($systemId === TRUE){
    		$listId = self::fetchBySystemId($listId)->id;
    	}
    	
    	// Опит за преброяване на перата в номенклатурата
    	$iQuery = acc_Items::getQuery();
    	$iQuery->like("lists", "|{$listId}|");
    	$iQuery->show('id');
    	
    	return $iQuery->count();
    }
}
