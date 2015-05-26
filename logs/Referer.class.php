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
class logs_Referer extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Реферери";
    
    
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
     * Полета на модела
     */
    public function description()
    {
         $this->FLD('ipId', 'key(mvc=logs_Ips, select=ip)', 'caption=IP');
         $this->FLD('brId', 'key(mvc=logs_Browsers, select=brid)', 'caption=Браузър');
         $this->FLD('time', 'int', 'caption=Време');
         $this->FLD('ref', 'varchar', 'caption=Реферер');
    }
    
    
    /**
     * Добавя запис за реферер
     * 
     * @param integer $ipId
     * @param integer $bridId
     * @param integer $time
     * 
     * @return NULL|integer
     */
    public static function addReferer($ipId = NULL, $bridId = NULL, $time = NULL)
    {
        $referer = $_SERVER['HTTP_REFERER'];
        
        if (!$referer) return ;
        
        if (!isset($ipId)) {
            $ipId = logs_Ips::getIpId();
        }
        
        if (!isset($bridId)) {
            $bridId = logs_Browsers::getBridId();
        }
        
        if (!isset($time)) {
            $time = dt::mysql2timestamp();
        }
        
        $rec = new stdClass();
        $rec->ipId = $ipId;
        $rec->brId = $bridId;
        $rec->time = $time;
        $rec->ref = $referer;
        
        return self::save($rec);
    }
}
