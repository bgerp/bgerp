<?php 

/**
 * Поддържани компоненти от сигналите
 *
 * @category  bgerp
 * @package   support
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @deprecated
 */
class support_Components extends core_Detail
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'issue_Components';
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Поддържани компоненти';
    
    
    public $singleTitle = 'Компонент';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin, support';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin, support';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin, support';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, support';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'admin, support';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'support_Wrapper, plg_RowTools2, plg_Sorting, plg_State';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'systemId';
    
    
    public $listItemsPerPage = 20;
    
    
    public $listFields = 'id, name, description, maintainers';
    
    
    public $currentTab = 'Системи';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('systemId', 'key(mvc=support_Systems, select=name)', 'caption=Система, mandatory');
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory, width=100%');
        $this->FLD('description', 'richtext(bucket=Support)', 'caption=Описание');
        $this->FLD('maintainers', 'userList(roles=support)', 'caption=Отговорници');
        $this->FLD('state', 'enum(draft=Чернова, active=Активно)', 'caption=Отговорници, input=none, notNull');
        
        $this->setDbUnique('systemId, name');
    }
    
    
    /**
     * Връща масив всички компоненти от системата
     *
     * @param support_Systems $systemId - id на системата
     *
     * @return array $componentArr - Масив с компонентите
     */
    public static function getSystemsArr($systemId = null)
    {
        // Масив с компонентите
        $componentArr = array();
        $query = static::getQuery();
        
        // Ако е зададено systemId
        if ($systemId) {
            
            // Всички системи
            $allSystemsArr = support_Systems::getSystems($systemId);
            
            $query->orWhereArr('systemId', $allSystemsArr);
        }
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Добавяме в масива
            $componentArr[$rec->id] = static::getVerbal($rec, 'name');
        }
        
        // Ако има окрити компоненти
        if (countR($componentArr)) {
            
            // Премахваме повтарящите се
            $componentArr = array_unique($componentArr);
        }
        
        return $componentArr;
    }
    
    
    /**
     * Масив с id' тата на еднаквите компоненти по име
     *
     * @param int $id - Id на компонента
     *
     * @return array $arr - Масив, с id'тата на компонентите със същото име
     */
    public static function getSame($id)
    {
        // Името на компонента
        $name = static::fetchField($id, 'name');
        
        // Името в долен регистър
        $name = mb_strtolower($name);
        
        // Запитване за да вземем всичките компоненти със съответното име
        $query = static::getQuery();
        $query->where(array("LOWER(#name) = '[#1#]'", $name));
        
        // Обхождаме записите
        while ($rec = $query->fetch()) {
            
            // Добавяме в масива
            $arr[$rec->id] = $rec->id;
        }
        
        return $arr;
    }
    
    
    /**
     * Маркира компонента като използван
     *
     * @param int $id
     */
    public static function markAsUsed($id)
    {
        if (!$id) {
            
            return ;
        }
        
        $rec = self::fetch($id);
        
        if ($rec->state == 'active') {
            
            return ;
        }
        
        $rec->state = 'active';
        
        self::save($rec, 'state');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($rec) {
            if ($action == 'delete') {
                if ($rec->state == 'active') {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
