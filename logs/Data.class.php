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
class logs_Data extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Логове";
    
    
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
    protected static $toAdd = array();
    
    
    
    /**
     * Полета на модела
     */
    public function description()
    {    
         $this->FLD('ipId', 'key(mvc=logs_Ips, select=ip)', 'caption=Идентификация->IP адрес на потребителя');
         $this->FLD('brId', 'key(mvc=logs_Browsers, select=brid)', 'caption=Идентификация->Идентификатор на браузъра на потребителя');
         $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Идентификация->Потребител');
         $this->FLD('time', 'int', 'caption=Време на записа');
         $this->FLD('type', 'enum(emerg,alert,crit,err,warning,notice,info,debug)', 'caption=Данни->Тип на събитието');
         $this->FLD('actionCrc', 'int', 'caption=Данни->Действие');
         $this->FLD('classCrc', 'int', 'caption=Данни->Клас');
         $this->FLD('objectId', 'int', 'caption=Данни->Обект');
         
         $this->setDbIndex('ipId');
         $this->setDbIndex('brId');
         $this->setDbIndex('userId');
         $this->setDbIndex('time');
         $this->setDbIndex('type');
         $this->setDbIndex('actionCrc');
         $this->setDbIndex('classCrc,objectId');
    }
    
    
    /**
     * 
     * 
     * @param string $type
     * @param string $message
     * @param string|object|NULL $className
     * @param integer|NULL $objectId
     * @param integer|NULL $time
     * @param boolean $returnCnt
     */
    public static function add($type, $message, $className = NULL, $objectId = NULL, $time = NULL, $returnCnt = FALSE)
    {
        // Инстанцираме класа, за да може да се изпълни on_Shutdown
        cls::get(get_called_class());
        
        if (is_object($className)) {
            $className = cls::getClassName($className);
        }
        
        $toAdd = array();
        $toAdd['type'] = $type;
        $toAdd['message'] = $message;
        $toAdd['className'] = $className;
        $toAdd['objectId'] = $objectId;
        $toAdd['time'] = $time;
        
        if ($returnCnt) {
            //TODO ???
            // flush ???
        }
        
        self::$toAdd[] = $toAdd;
    }
    
    
    /**
     * При приключване на изпълнените на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        // Форсираме стартирането на сесията
        core_Session::forcedStart();
        
        // Записва в БД всички действия от стека
        self::flush();
    }
    
    
    /**
     * Записва в БД всички действия от стека
     */
    public static function flush()
    {
        // Ако няма данни за добавяне, няма нужда да се изпълнява
        if (!self::$toAdd) return ;
        
        $ipId = logs_Ips::getIpId();
        $bridId = logs_Browsers::getBridId();
        
        foreach (self::$toAdd as $toAdd) {
            
            $rec = new stdClass();
            $rec->ipId = $ipId;
            $rec->brId = $bridId;
            $rec->userId = core_Users::getCurrent();
            $rec->actionCrc = logs_Actions::getActionCrc($toAdd['message']);
            $rec->classCrc = logs_Classes::getClassCrc($toAdd['className']);
            $rec->objectId = $toAdd['objectId'];
            $rec->time = $toAdd['time'];
            $rec->type = $toAdd['type'];
            
            self::save($rec);
            
            logs_Referer::addReferer($ipId, $bridId, $toAdd['time']);
        }
        
        // Записваме crc32 стойностите на стринговете
        logs_Actions::saveActions();
        logs_Classes::saveActions();
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->brId = logs_Browsers::getLinkFromId($rec->brId);
        
        if ($rec->time) {
            $time = dt::timestamp2Mysql($rec->time);
            $row->time = dt::mysql2verbal($time, 'smartTime');
        }
    }
}
