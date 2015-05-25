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
         $this->FLD('brId', 'key(mvc=logs_Browsers, select=ip)', 'caption=Браузър');
         $this->FLD('time', 'int', 'caption=Време');
         $this->FLD('ref', 'varchar', 'caption=Реферер');
    }
}
