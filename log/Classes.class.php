<?php 

/**
 *
 *
 * @category  bgerp
 * @package   logs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class log_Classes extends core_Manager
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'logs_Classes';
    
    
    /**
     * Заглавие
     */
    public $title = 'Класове';
    
    
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
    public $loadList = 'plg_SystemWrapper, log_Wrapper';
    
    
    public static $classArr = array();
    
    
    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('crc', 'bigint', 'caption=crc32 на класа');
        $this->FLD('class', 'varchar', 'caption=Име на класа');
        
        $this->setDbUnique('crc');
    }
    
    
    /**
     * Връща crc32 стойността на стринга
     *
     * @param string $action
     * @param bool   $autoSave
     *
     * @return int|NULL
     */
    public static function getClassCrc($className, $autoSave = true)
    {
        if (!$className) {
            
            return ;
        }
        
        $classCrc = crc32($className);
        
        if (!self::$classArr[$classCrc] && $autoSave) {
            self::$classArr[$classCrc] = $className;
        }
        
        return $classCrc;
    }
    
    
    /**
     * Записва масива със crc32 и класа
     */
    public static function saveActions()
    {
        foreach (self::$classArr as $crc => $class) {
            $rec = new stdClass();
            $rec->crc = $crc;
            $rec->class = $class;
            
            self::save($rec, null, 'IGNORE');
        }
    }
    
    
    /**
     * Връща името на класа от подадената crc стойност
     *
     * @param int $crc
     *
     * @return string
     */
    public static function getClassFromCrc($crc)
    {
        static $crcClassArr = array();
        
        if (!isset($crcClassArr[$crc])) {
            $rec = self::fetch(array("#crc = '[#1#]'", $crc));
            $crcClassArr[$crc] = $rec->class;
        }
        
        return $crcClassArr[$crc];
    }
}
