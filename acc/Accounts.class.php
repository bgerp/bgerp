<?php



/**
 * Мениджър на счетоводни сметки
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Accounts extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Сметкоплан';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_State2, plg_SaveAndNew, plg_Search, acc_WrapperSettings';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'num,title,type,systemId,groupId1,groupId2,groupId3';
    
    
    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Сметкоплан';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,acc';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,accMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,accMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,accMaster';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'no_one';
    
    
    /**
     * Кой може да променя състоянието и ...;
     */
    var $canAdmin = 'ceo,accMaster';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 300;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'num,title,type,lists=Номенклатури,systemId,state,tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
    /**
     * 
     */
    private static $idToNumMap;
    
    
    /**
     *
     */
    private static $numToIdMap;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('num', 'varchar(5)', "caption=№,mandatory,remember=info, export");
        $this->FLD('title', 'varchar', 'caption=Сметка,mandatory,remember=info, export');
        $this->FLD('type', 'enum(,dynamic=Смесена,active=Активна,passive=Пасивна,transit=Корекционна)',
            'caption=Тип,remember, export');
        $this->FLD('strategy', 'enum(,WAC)',
            'caption=Стратегия, export');
        $this->FLD('groupId1', 'key(mvc=acc_Lists,select=caption,allowEmpty=true)',
            'caption=Разбивка по номенклатури->Ном. 1,remember, export');
        $this->FLD('groupId2', 'key(mvc=acc_Lists,select=caption,allowEmpty=true)',
            'caption=Разбивка по номенклатури->Ном. 2,remember, export');
        $this->FLD('groupId3', 'key(mvc=acc_Lists,select=caption,allowEmpty=true)',
            'caption=Разбивка по номенклатури->Ном. 3,remember, export');
        $this->FLD('lastUseOn', 'datetime', 'caption=Последно,input=hidden');
        $this->FLD('systemId', 'varchar(5)', 'caption=System ID, export, mandatory');
        
        $this->XPR('isSynthetic', 'int', 'CHAR_LENGTH(#num) < 3', 'column=none');
        
        $this->setDbUnique('num');
        $this->setDbUnique('systemId');
    }
    
    
    /**
     * Изчисление на "синтетичните" (1 и 2 разрядни) сметки
     */
    protected static function on_CalcIsSynthetic($mvc, &$rec)
    {
        $rec->isSynthetic = (strlen($rec->num) < 3);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec->id && $action == 'delete') {
            $rec = $mvc->fetch($rec->id);
            
            if ($rec->lastUseOn) {
                // Използвана сметка - забранено изтриване
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Сортиране на записите по num
        $data->query->orderBy('num');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        if (!empty($form->rec->id)) {
            $rec = $form->rec;
            expect($rec &&
                is_object($rec) &&
                array_key_exists('lastUseOn', (array)$rec)
            );
            
            if ($rec->lastUseOn) {
                $form->setReadOnly('groupId1');
                $form->setReadOnly('groupId2');
                $form->setReadOnly('groupId3');
            }
        }
    }
    
    
    /**
     * Проверка уникално ли е числото
     */
    public function isUniquenum($rec)
    {
        $preCond = '1 = 1';
        
        if (!empty($rec->id)) {
            $preCond = "#id != {$rec->id}";
        }
        $result = !($this->fetch(array("#num = '[#1#]' AND {$preCond}", $rec->num)));
        
        return $result;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if (empty($form->rec->num)) {
            return;
        }
        
        if ($form->isSubmitted()) {
            
            // Ако не е цяло число
            if (!ctype_digit($form->rec->num)) {
                
                // Сетваме грешката
                $form->setError('num', 'Недопустими символи в число/израз');
            }
            
            // Ако не е цяло число
            if (!ctype_digit($form->rec->systemId)) {
                
                // Сетваме грешката
                $form->setError('systemId', 'Недопустими символи в число/израз');
            }
        }
        
        // Изчисление на FNC поле "isSynthetic"
        $mvc->on_CalcIsSynthetic($mvc, $form->rec);
        
        if (!$mvc->isUniquenum($form->rec)) {
            $form->setError('num', 'Съществува сметка с този номер');
        }
        
        // Валидация: "синтетичните" (1 и 2 разрядни) сметки
        // (т.е. разделите и групите на Сметкоплана) НЕ допускат избор на "Тип";
        
        if ($form->rec->isSynthetic) {
            
            // ако сметката е "Синтетична"
            
            if (!empty($form->rec->type))
            
            // и полето "Тип" НЕ е празно
            
            $form->setError('type', "Разделите и Групите на Сметкоплана нямат|* <b>|Тип|*</b> | !");
        }
        
        // Валидация: всички останали (>=3 разряздните - "аналитични") сметки
        // изискват задаване на "Тип"
        else {
            
            // ако сметката НЕ е "Синтетична"
            
            if (empty($form->rec->type))
            
            // и полето "Тип" е празно
            
            $form->setError('type', "Изберете|* <b>|Тип|*</b> |на сметката!");
        }
        
        // Определяне на избраните номенклатури.
        $groupFields = array();
        
        foreach (range(1, 3) as $i) {
            if (!empty($form->rec->{"groupId{$i}"})) {
                $groupFields[] = "groupId{$i}";
            }
        }
        
        if ($form->rec->isSynthetic) {
            //
            // Синтетична сметка
            //
            
            // Валидация: сметките с тип "синтетична" НЕ допускат задаване на номенклатури;
            // всички останали сметки допускат задаване на номенклатури
            
            if (!empty($groupFields)) {
                $form->setError(implode(',', $groupFields),
                    "Не се допуска задаването на номенклатури за синтетични сметки");
            }
        } else {
            //
            // Аналитична сметка
            //
            
            // Колко от избраните номенклатури имат размерност?
            $nDimensions = 0;
            
            foreach ($groupFields as $groupId) {
                if (acc_Lists::isDimensional($form->rec->{$groupId})) {
                    $nDimensions++;
                }
                
                if ($nDimensions > 1) {
                    break;
                }
            }
            
            // Валидация: Аналитична сметка може да има най-много една оразмерима номенклатура.
            //            Ако има такава, с/ката е "оразмерима"; ако няма - "неоразмерима"
            
            if ($nDimensions > 1) {
                $form->setError(implode(',', $groupFields),
                    "Допуска се най-много една номенклатура с размерност");
            }
        }
        
        // Валидация: Стратегия (LIFO, FIFO, WAC) не се допуска за "неоразмерими" сметки.
        
        if (!empty($form->rec->strategy) && empty($nDimensions)) {
            $form->setError('strategy',
                "Стратегия се допуска само ако поне една от номенклатурите има размерност");
        }
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
        if($rec->state == 'active') {
            $row->ROW_ATTR['class'] .= ' level-' . strlen($rec->num);
        }
        
        if($rec->groupId1) {
            $listRec = acc_Lists::fetch($rec->groupId1);
            $row->lists .= "<div class='acc-detail'><a href='" .
            toUrl(array('acc_Items', 'listId' => $rec->groupId1)) .
            "'>{$listRec->caption}</a></div>";
        }
        
        if($rec->groupId2) {
            $listRec = acc_Lists::fetch($rec->groupId2);
            $row->lists .= "<div class='acc-detail'><a href='" .
            toUrl(array('acc_Items', 'listId' => $rec->groupId2)) .
            "'>{$listRec->caption}</a></div>";
        }
        
        if($rec->groupId3) {
            $listRec = acc_Lists::fetch($rec->groupId3);
            $row->lists .= "<div class='acc-detail'><a href='" .
            toUrl(array('acc_Items', 'listId' => $rec->groupId3)) .
            "'>{$listRec->caption}</a></div>";
        }
        
        if($rec->type) {
            $row->type = "<div class='acc-detail'>" .
            $row->type . "</div>";
        }
        
        if($rec->strategy) {
            $row->type .= "<div class='acc-detail'>" .
            $mvc->getVerbal($rec, 'strategy') . "</div>";
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
        // Подготвяме пътя до файла с данните 
        $file = "acc/csv/Accounts.csv";
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => "num",
            1 => "title",
            2 => "type",
            3 => "strategy",
            4 => "csv_groupId1",
            5 => "csv_groupId2",
            6 => "csv_groupId3",
            7 => "systemId",
            8 => "state",
            9 => "csv_createdBy",
        );
        
        // Импортираме данните от CSV файла. 
        // Ако той не е променян - няма да се импортират повторно 
        $cntObj = csv_Lib::importOnce($this, $file, $fields, NULL, NULL);
        
        // Записваме в лога вербалното представяне на резултата от импортирането 
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
        if (isset($rec->csv_groupId1) || isset($rec->csv_groupId2) || isset($rec->csv_groupId3) || isset($rec->csv_createdBy)) {
            
            $rec->groupId1 = self::getListsId($rec->csv_groupId1);
            $rec->groupId2 = self::getListsId($rec->csv_groupId2);
            $rec->groupId3 = self::getListsId($rec->csv_groupId3);
            $rec->createdBy = -1;
        }
    }
    
    
    /**
     * Връща 'id' от acc_Lists по подаден стринг, от който се взема 'num'
     *
     * @param string стринг от вида `име на номенклатура (код)`
     * @return int ид на номенклатура
     */
    private static function getListsId($string)
    {
        $string = strip_tags($string);
        $string = trim($string);
        
        if (empty($string)) {
            // Няма разбивка
            return NULL;
        }
        
        expect(preg_match('/\((\d+)\)\s*$/', $string, $matches),
            'Некоректно форматирано име на номенклатура, очаква се `Име (код)`',
            $string);
        
        // Проблем: парсиран е код, но не е намерена номенклатура с този код
        $num = (int)$matches[1];
        expect(($listId = acc_Lists::fetchField("#num={$num}", 'id')),
            'В ' . "acc/csv/Accounts.csv" . ' има номер на номенклатура, която не е открита в acc_Lists',
            $num, $string);
        
        return $listId;
    }
    
    
    /**
     * Функция, която връща подготвен масив за СЕЛЕКТ от елементи (ид, поле) на $class отговарящи на условието where
     */
    function makeArray4Select($fields = NULL, $where = "", $index = 'id', $tpl = NULL)
    {
        $query = $this->getQuery();
        
        $res = array();
        
        if (!$where) {
            $fields = 'id, num, title, isSynthetic';
            $query->show($fields);
            $query->show($index);
            $query->show('id');
        }
        
        $query->orderBy('#num');
        
        /**
         * Структура за преброяване на листата на синтетичните с/ки. Използва се за премахване
         * на синтетичните сметки, под които няма аналитични сметки.
         */
        $leafCount = array();
        
        while ($rec = $query->fetch($where)) {
            $title = $this->getRecTitle($rec, FALSE);
            
            if ($rec->isSynthetic) {
                $res[$rec->{$index}] = (object)array(
                    'title' => $title,
                    'group' => TRUE
                );
                $leafCount[$rec->num] = array(0, $rec->{$index});
            } else {
                $res[$rec->{$index}] = $title;
                
                for ($i = 0; $i < strlen($rec->num)-1; $i++) {
                    $leafCount[substr($rec->num, 0, $i + 1)][0]++;
                }
            }
        }
        
        /**
         * Окастряне на сухите клони на дървото - клоните, които нямат листа.
         */
        foreach ($leafCount as $num=>$d) {
            if ($d[0] == 0) {
                unset($res[$d[1]]);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
        $title = $rec->num . '. ' . $rec->title;
        
        if($escaped) {
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    
    /**
     * Извлича масив с индекс ид на сметка и стойност - номер на съответната сметка, както и обратния му
     */
    private static function fetchIdToNumMap()
    {
        self::$idToNumMap = array();
        
        $query = self::getQuery();
        
        while ($r = $query->fetch()) {
            self::$idToNumMap[$r->id] = $r->num;
        }
        
        self::$numToIdMap = array_flip(self::$idToNumMap);
    }
    
    
    /**
     * Връща номер на сметка по ид на сметка.
     *
     * @param int $id ид на сметка
     * @return string|FALSE номер на сметка
     */
    public static function getNumById($id)
    {
        if (!isset(self::$idToNumMap)) {
            self::fetchIdToNumMap();
        }
        
        if (!isset(self::$idToNumMap[$id])) {

            return FALSE;
        }
        
        return self::$idToNumMap[$id];
    }
    
    
    /**
     * Връща ид на сметка по номер на сметка
     *
     * @param string $num номер на сметка
     * @return int|FALSE ид на сметка
     */
    function getIdByNum($num)
    {
        if (!isset(self::$numToIdMap)) {
            $this->fetchIdToNumMap();
        }
        
        if (!isset(self::$numToIdMap[$num])) {
            return FALSE;
        }
        
        return self::$numToIdMap[$num];
    }
    
    
    /**
     * Factory метод - създава обект стратегия (наследник на @link acc_Strategy) според
     * стратегията на зададената сметка.
     *
     * @param int $accountId ид на аналитична сметка
     * @return acc_Strategy
     */
    function createStrategyObject($accountId)
    {
        $strategyType = $this->fetch($accountId, 'strategy');
        $strategy = FALSE;
        
        if ($accountId == 37) {
            time();
        }
        
        switch ($strategyType->strategy) {
            case 'LIFO' :
                $strategy = new acc_strategy_LIFO($accountId);
                break;
            case 'FIFO' :
                $strategy = new acc_strategy_FIFO($accountId);
                break;
            case 'WAC' :
                $strategy = new acc_strategy_WAC($accountId);
                break;
        }
        
        return $strategy;
    }
    
    
    /**
     * Връща типа (активна, пасивна) на зададената сметка.
     *
     * @param int $accountId ид на аналитична сметка
     * @return string
     */
    public function getType($accountId)
    {
        return $this->fetchField($accountId, 'type');
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    protected static function on_BeforeAction($mvc, &$res, $action)
    {
        $mvc->setField('state', 'export');
    }
    
    
    /**
     * Вземане на запис от базата, чрес системното му ид
     */
    public static function getRecBySystemId($systemId)
    {
        expect($rec = static::fetch(array("#systemId = '[#1#]'", $systemId)), "Липсва сметка със `systemId`={$systemId}");
        
        return $rec;
    }
    
    
    /**
     * Информация за сч. сметка
     */
    public static function getAccountInfo($accountId)
    {
        $acc = (object)array(
            'rec' => acc_Accounts::fetch($accountId),
            'groups' => array(),
            'isDimensional' => FALSE
        );
        
        foreach (range(1, 3) as $i) {
            $listPart = "groupId{$i}";
            
            if (!empty($acc->rec->{$listPart})) {
                $listId = $acc->rec->{$listPart};
                $acc->groups[$i] = new stdClass();
                $acc->groups[$i]->rec = acc_Lists::fetch($listId);
                $acc->isDimensional = $acc->isDimensional || acc_Lists::isDimensional($listId);
            }
        }
        
        return $acc;
    }
    
    
    /**
     * Връща дали сметката има стратегия
     * Кешира резултатите за бързодействие
     * 
     * @param int $id - ид  на сметка
     * @return boolean
     */
    public static function hasStrategy($id)
    {
    	expect(is_numeric($id));
    	
    	if(!isset(self::$cache[$id])){
    	    
    	    $strategy = self::fetchField($id, 'strategy');
    	    
    		self::$cache[$id] = !empty($strategy);
    	}
    	
    	return self::$cache[$id];
    }
    
    
    /**
     * Връща опции за избор на сметки, чиито пера имат подадените интерфейси
     * 
     * @param mixed $interfaces - имената на интерфейсите като масив или стринг
     * @return array $options - готовите опции
     */
    public static function getOptionsByListInterfaces($interfaces)
    {
    	$options = cls::get('acc_Accounts')->makeArray4Select('title', array("#num LIKE '[#1#]%' AND state NOT IN ('closed')", ''));
    	
    	$interfaces = arr::make($interfaces);
    	$interfaces = implode('|', $interfaces);
    	acc_type_Account::filterSuggestions($interfaces, $options);
    	
    	return $options;
    }
}
