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
class log_Referer extends core_Master
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'logs_Referer';
    
    
    /**
     * Заглавие
     */
    public $title = 'Реферери';
    
    
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
     * Кой има право да изтрива?
     */
    public $canSingle = 'admin';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SystemWrapper, log_Wrapper';
    
    
    /**
     * Полета на модела
     */
    public function description()
    {
        $this->FLD('ipId', 'key(mvc=log_Ips, select=ip)', 'caption=IP');
        $this->FLD('brId', 'key(mvc=log_Browsers, select=brid)', 'caption=Браузър');
        $this->FLD('time', 'int', 'caption=Време');
        $this->FLD('ref', 'text', 'caption=Реферер');
        
        $this->setDbUnique('ipId, brId, time');
    }
    
    
    /**
     * Добавя запис за реферер
     *
     * @param int $ipId
     * @param int $bridId
     * @param int $time
     *
     * @return NULL|int
     */
    public static function addReferer($ipId = null, $bridId = null, $time = null)
    {
        $referer = $_SERVER['HTTP_REFERER'];
        
        if (!$referer) {
            
            return ;
        }
        
        if (core_Url::isLocal($referer)) {
            
            return ;
        }
        
        if (!isset($ipId)) {
            $ipId = log_Ips::getIpId();
        }
        
        if (!isset($bridId)) {
            $bridId = log_Browsers::getBridId();
        }
        
        if (!isset($time)) {
            $time = dt::mysql2timestamp();
        }
        
        $rec = new stdClass();
        $rec->ipId = $ipId;
        $rec->brId = $bridId;
        $rec->time = $time;
        $rec->ref = $referer;
        
        return self::save($rec, null, 'IGNORE');
    }
    
    
    /**
     * Връща записа за реферера
     *
     * @param int $ipId
     * @param int $bridId
     * @param int $time
     *
     * @return object|FALSE
     */
    public static function getRefRec($ipId, $bridId, $time)
    {
        $rec = self::fetch(array("#ipId = '[#1#]' AND #brId = '[#2#]' AND #time = '[#3#]'", $ipId, $bridId, $time));
        
        return $rec;
    }
    
    
    /**
     * Изтрива записа за реферера
     *
     * @param int  $ipId
     * @param int  $bridId
     * @param int  $time
     * @param bool $check
     *
     * @return int
     */
    public static function delRefRec($ipId, $bridId, $time, $check = true)
    {
        if ($check) {
            if (log_Data::fetch(array("#ipId = '[#1#]' AND #brId = '[#2#]' AND #time = '[#3#]'", $ipId, $bridId, $time))) {
                
                return 0;
            }
        }
        
        $delCnt = self::delete(array("#ipId = '[#1#]' AND #brId = '[#2#]' AND #time = '[#3#]'", $ipId, $bridId, $time));
        
        return $delCnt;
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
        $row->brId = log_Browsers::getLinkFromId($rec->brId);
        
        if ($rec->time) {
            $time = dt::timestamp2Mysql($rec->time);
            $row->time = dt::mysql2verbal($time, 'smartTime');
        }
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('time', 'DESC');
    }
}
