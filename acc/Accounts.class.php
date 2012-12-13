<?php



/**
 * Мениджър на счетоводни сметки
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
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
    var $loadList = 'plg_RowTools, plg_Created, plg_State2, plg_SaveAndNew, acc_WrapperSettings, Lists=acc_Lists';
    
    
    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,acc,broker,designer';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,acc,broker,designer';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,acc,broker,designer';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 300;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'num,title,type,lists=Номенклатури,systemId,lastUseOn,state,tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * @var acc_Lists
     */
    var $Lists;
    
    private static $idToNumMap;
    private static $numToIdMap;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('num', 'varchar(5, size=5)', "caption=Номер,mandatory,remember=info, export");
        $this->FLD('title', 'varchar', 'caption=Сметка,mandatory,remember=info, export');
        $this->FLD('type', 'enum(,dynamic=Смесена,active=Активна,passive=Пасивна,transit=Корекционна)',
            'caption=Тип,remember,mandatory, export');
        $this->FLD('strategy', 'enum(,FIFO,LIFO,MAP)',
            'caption=Стратегия, export');
        $this->FLD('groupId1', 'key(mvc=acc_Lists,select=caption,allowEmpty=true)',
            'caption=Разбивка по номенклатури->Ном. 1,remember, export');
        $this->FLD('groupId2', 'key(mvc=acc_Lists,select=caption,allowEmpty=true)',
            'caption=Разбивка по номенклатури->Ном. 2,remember, export');
        $this->FLD('groupId3', 'key(mvc=acc_Lists,select=caption,allowEmpty=true)',
            'caption=Разбивка по номенклатури->Ном. 3,remember, export');
        $this->FLD('lastUseOn', 'datetime', 'caption=Последно,input=hidden');
        $this->FLD('systemId', 'varchar(5)', 'caption=System ID, export');
        
        $this->XPR('isSynthetic', 'int', 'CHAR_LENGTH(#num) < 3', 'column=none');
        
        $this->setDbUnique('num');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_CalcIsSynthetic($mvc, &$rec) {
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
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
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
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Сортиране на записите по num
        $data->query->orderBy('num');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
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
     * @todo Чака за документация...
     */
    function isUniquenum($rec)
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
    static function on_AfterInputEditForm($mvc, &$form)
    {
        if (empty($form->rec->num)) {
            return;
        }
        
        // Изчисление на FNC поле "isSynthetic"
        $mvc->on_CalcIsSynthetic($mvc, $form->rec);
        
        if (!$mvc->isUniquenum($form->rec)) {
            $form->setError('num', 'Съществува сметка с този номер');
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
        
        // Валидация: Стратегия (LIFO, FIFO, MAP) не се допуска за "неоразмерими" сметки.
        
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
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if($rec->state == 'active') {
            $row->ROW_ATTR['class'] .= ' level-' . strlen($rec->num);
        }
        
        if($rec->groupId1) {
            $listRec = $mvc->Lists->fetch($rec->groupId1);
            $row->lists .= "<div class='acc-detail'><a href='" .
            toUrl(array('acc_Items', 'listId' => $rec->groupId1)) .
            "'>{$listRec->caption}</a></div>";
        }
        
        if($rec->groupId2) {
            $listRec = $mvc->Lists->fetch($rec->groupId2);
            $row->lists .= "<div class='acc-detail'><a href='" .
            toUrl(array('acc_Items', 'listId' => $rec->groupId2)) .
            "'>{$listRec->caption}</a></div>";
        }
        
        if($rec->groupId3) {
            $listRec = $mvc->Lists->fetch($rec->groupId3);
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
     * @todo Чака за документация...
     */
    function makeArray4Select($fields = NULL, $where = "", $index = 'id', $tpl = NULL)
    {
        $query = $this->getQuery();
        
        $res = array();
        
        if (!$where) {
            $fields = 'id, num, title, isSynthetic';
            $query->show($fields);
        }
        
        $query->orderBy('#num');
        
        /**
         * Структура за преброяване на листата на синтетичните с/ки. Използва се за премахване
         * на синтетичните сметки, под които няма аналитични сметки.
         */
        $leafCount = array();
        
        while ($rec = $query->fetch($where)) {
            $title = $this->getRecTitle($rec);
            
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
    static function getRecTitle($rec, $escaped = TRUE)
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
    private function fetchIdToNumMap()
    {
        self::$idToNumMap = array();
        
        $query = $this->getQuery();
        
        while ($r = $query->fetch()) {
            self::$idToNumMap[$r->id] = $r->num;
        }
        
        self::$numToIdMap = array_flip(self::$idToNumMap);
    }
    
    
    /**
     * Връща номер на сметка по ид на сметка.
     *
     * @param int $id ид на сметка
     * @return string номер на сметка
     */
    function getNumById($id)
    {
        if (!isset(self::$idToNumMap)) {
            $this->fetchIdToNumMap();
        }
        
        if (!isset(self::$idToNumMap[$id])) {
            return false;
        }
        
        return self::$idToNumMap[$id];
    }
    
    
    /**
     * Връща ид на сметка по номер на сметка
     *
     * @param string $num номер на сметка
     * @return int ид на сметка
     */
    function getIdByNum($num)
    {
        if (!isset(self::$numToIdMap)) {
            $this->fetchIdToNumMap();
        }
        
        if (!isset(self::$numToIdMap[$num])) {
            return false;
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
            case 'MAP' :
                $strategy = new acc_strategy_MAP($accountId);
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
    function getType($accountId)
    {
        return $this->fetchField($accountId, 'type');
    }
    
    
    /**
     * Извлича запис на сметка по зададен id или systemId
     * 
     * @param string $systemId
     * @return stdClass
     */
    public static function fetchById($id = NULL, $systemId = NULL)
    {
        // Поне едно от id или systemId трябва да е зададено
        expect(!empty($id) || !empty($systemId));
        
        if (!empty($id)) {
            expect($rec = static::fetch($id));
            expect(empty($systemId) || $rec->systemId == $systemId);
        } else {
            expect($rec = static::fetch(array("#systemId = '[#1#]'", $systemId)));
        }
        
        return $rec;
    }
        
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    static function on_BeforeAction($mvc, &$res, $action)
    {
        $mvc->setField('state', 'export');
    }
}