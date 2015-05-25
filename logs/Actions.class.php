<?php 


/**
 * 
 *
 * @category  bgerp
 * @package   logs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class logs_Actions extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Действия";
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SystemWrapper, logs_Wrapper';
    
    
    /**
     * 
     */
    public static $actionsArr = array();
    
    
    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('crc', 'int', 'caption=crc32 на действието');
        $this->FLD('action', 'varchar', 'caption=Действие');
        
        $this->setDbUnique('crc');
    }
    
    
    /**
     * Връща crc32 стойността на стринга
     * 
     * @param string $action
     * 
     * @return integer
     */
    public static function getActionCrc($action)
    {
        if (!$action) return ;
        
        $actionCrc = crc32($action);
        
        if (!self::$actionsArr[$actionCrc]) {
            self::$actionsArr[$actionCrc] = $action;
        }
        
        return $actionCrc;
    }
    
    
    /**
     * Записва масива със crc32 и екшъна
     */
    public static function saveActions()
    {
       foreach (self::$actionsArr as $crc => $action) {
           $rec = new stdClass();
           $rec->crc = $crc;
           $rec->action = $action;
           
           self::save($rec, NULL, 'IGNORE');
       }
    }
}
