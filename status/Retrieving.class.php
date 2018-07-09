<?php 

/**
 * Клас 'status_Retrieving'
 *
 * @category  vendors
 * @package   status
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class status_Retrieving extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Изтегляния';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
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
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'status_Wrapper';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('messageId', 'key(mvc=status_Messages)', 'caption=Съобщение');
        $this->FLD('userId', 'user', 'caption=Потребител,notNull');
        $this->FLD('sid', 'varchar(32)', 'caption=Идентификатор,notNull');
        $this->FLD('retTime', 'datetime', 'caption=Изтегляне');
        $this->FLD('hitTime', 'datetime', 'caption=Заявка');
        $this->FLD('hitId', 'varchar(16)', 'caption=ID на хита');
        $this->FLD('idleTime', 'int', 'caption=Бездействие, notNull');
        
        $this->setDbUnique('messageId, hitTime, sid, userId');
        
        $this->dbEngine = 'InnoDB';
    }
    
    
    /**
     * Добавя запис за показване на съответното съобщение в даден таб
     *
     * @param int      $messageId
     * @param datetime $hitTime
     * @param int      $idleTime  - Време на бездействие на съответния таб
     * @param string   $sid
     * @param int      $userId
     * @param string   $hitId
     *
     * @return int - id на записа
     */
    public static function addRetrieving($messageId, $hitTime, $idleTime, $sid = null, $userId = null, $hitId = null)
    {
        // Записва
        $rec = new stdClass();
        $rec->messageId = $messageId;
        $rec->hitTime = $hitTime;
        $rec->retTime = dt::now();
        $rec->idleTime = $idleTime;
        $rec->hitId = $hitId;
        
        // Ако има потребител
        if ($userId) {
            $rec->userId = $userId;
        }
        
        // Ако има индентификатор
        if ($sid) {
            $rec->sid = $sid;
        }
        
        $id = static::save($rec, null, 'IGNORE');
        
        return $id;
    }
    
    
    /**
     * Изтрива информацията за изтеглянията за съответното статус събщение
     *
     * @param int $messageId - id на съобщението
     */
    public static function removeRetrieving($messageId)
    {
        $cnt = static::delete("#messageId = '{$messageId}'");
        
        return $cnt;
    }
    
    
    /**
     * Проверява дали съобщението и извлеченоо за даден потребител в съответния таб
     *
     * @param int      $messageId
     * @param datetime $hitTime
     * @param int      $idleTime  - Време на бездействие на съответния таб
     * @param string   $sid
     * @param int      $userId
     * @param string   $hitId
     *
     * @return bool
     */
    public static function isRetrived($messageId, $hitTime, $idleTime, $sid = null, $userId = null, $hitId = null)
    {
        // Конфигурация на пакета
        $conf = core_Packs::getConfig('status');
        
        // Време на бездействие на таба
        $maxIdleTime = $conf->STATUS_IDLE_TIME;
        
        // Вземаме всички съобщения, към даден потребител
        $query = static::getQuery();
        $query->where(array("#messageId = '[#1#]'", $messageId));
        
        $or = false;
        
        if (!empty($hitId)) {
            $query->where(array("#hitId = '[#1#]'", $hitId));
            $or = true;
        }
        
        // Които не са теглени от съответния таб или са теглени от таб с по прясно време на бездействие
        $query->where(array("#hitTime = '[#1#]'", $hitTime), $or);
        
        // Ако времето от браузра е по - голямо от максимално допустимото време
        if ($idleTime > $maxIdleTime) {
            $query->orWhere(array("#idleTime < '[#1#]'", $maxIdleTime));
        } else {
            $query->orWhere(array("#hitTime > '[#1#]'", $hitTime));
        }
        
        // Ако има идентификатор - когато не е логнат
        if ($sid) {
            $query->where(array("#sid = '[#1#]'", $sid));
        }
        
        // Ако има потребител - когато е логнат
        if ($userId) {
            $query->where(array("#userId = '[#1#]'", $userId));
        }
        
        // Ако има записи
        if ($query->count()) {
            
            return true;
        }
    }
}
