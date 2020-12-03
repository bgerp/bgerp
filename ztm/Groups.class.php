<?php 

/**
 *
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ztm_Groups extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Групи';
    public $singleTitle = 'Устройсво';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ztm, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ztm, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ztm, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'ztm, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ztm, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    public $canReject = 'ztm, ceo';
    public $canRestore = 'ztm, ceo';
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'ztm, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper, plg_Rejected, plg_Created, plg_State2, plg_RowTools2, plg_Modified, plg_Sorting';
    
    
    /**
     * 
     */
    public function description()
    {
        $this->FLD('name', 'varchar(32)', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(access=Достъп, fire=Пожар)', 'caption=Тип, mandatory');
        
        $this->setDbUnique('type, name');
    }
    
    
    /**
     * Връща възможните опции за съответния тип
     * 
     * @param null|string $type
     * 
     * @return array
     */
    public static function getOptionsByType($type = null)
    {
        $query = self::getQuery();
        $query->where("#state = 'active'");
        
        if (isset($type)) {
            $query->where(array("#type = '[#1#]'", $type));
        }
        
        $resArr = array();
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec->name;
        }
        
        return $resArr;
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
        if ($rec->id && $action == 'edit') {
            if (ztm_Devices::fetch(array("#accessGroupId = '[#1#]'", $rec->id))) {
                
                $requiredRoles = 'no_one';
            }
        }
    }
}
