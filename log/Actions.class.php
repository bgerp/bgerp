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
class log_Actions extends core_Manager
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'logs_Actions';
    
    
    /**
     * Заглавие
     */
    public $title = 'Действия';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'debug';
    
    
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
    public $canView = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SystemWrapper, log_Wrapper, plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'action';
    
    
    
    public static $actionsArr = array();
    
    
    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('crc', 'bigint', 'caption=crc32 на действието');
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
        if (!$action) {
            
            return ;
        }
        
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
            if (self::fetch("#crc = {$crc}")) {
                continue;
            }

            $rec = new stdClass();
            $rec->crc = $crc;
            $rec->action = $action;
           
            self::save($rec, null, 'IGNORE');
        }
    }
    
    
    /**
     * Връща действието от crc стойността
     *
     * @param integer $crc
     *
     * @return string
     */
    public static function getActionFromCrc($crc)
    {
        static $crcActArr = array();
        
        if (!isset($crcActArr[$crc])) {
            $rec = self::fetch(array("#crc = '[#1#]'", $crc));
            $crcActArr[$crc] = $rec->action;
        }
        
        return $crcActArr[$crc];
    }
}
