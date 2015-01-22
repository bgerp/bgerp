<?php


/**
 * Лог за затварянията на статуса за помощ
 *
 * @category  bgerp
 * @package   needhelp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class needhelp_Log extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Лог на затварянията';
    
    
    /**
     * Кой има право да чете?
     */
    protected $canRead = 'admin, debug';
    
    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'admin, debug';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'admin, debug';
    
    
    /**
     * Кой има право да го види?
     */
    protected $canView = 'admin, debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'admin, debug';
    
    
    /**
     * Кой има право да го изтрие?
     */
    protected $canDelete = 'admin, debug';
    
    
    /**
     * Кой има право да добавя записи в лога
     */
    protected $canAddtolog = 'user';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Sorting, plg_Created';
    
    
    /**
     * 
     */
    public function description()
    {
        $this->FLD('userId', 'user', 'caption=Потрбител');
    }
    
    
    /**
     * Добавя запис в лога за съответния потребител
     */
    public function act_AddToLog()
    {
        $currUserId = core_Users::getCurrent();
        
        if ($currUserId <= 0) return ;
        
        if (!Request::get('ajax_mode')) return ;
        
        if (!$this->haveRightFor('addtolog', NULL, $currUserId)) return FALSE;
        
        $nRec = new stdClass();
        
        $nRec->userId = $currUserId;
        
        self::save($nRec);
        
        return array();
    }
    
    
    /**
     * Проверява дали потребителя е достигнал лимита за показвания
     */
    public static function isShowLimitReached($currUserId = NULL)
    {
        $conf = core_Packs::getConfig('needhelp');
        
        if (!isset($currUserId)) {
            $currUserId = core_Users::getCurrent();
        }
        
        if ($currUserId <= 0) return ;
        
        $createdOn = dt::subtractSecs($conf->NEEDHELP_SHOW_LIMIT_DATE);
        
        // Проверява всички записи за съответния потребител от подадения срок
        $query = self::getQuery();
        $query->where("#userId = '{$currUserId}'");
        $query->where("#createdOn > '{$createdOn}'");
        $cnt = $query->count();
        
        if ($conf->NEEDHELP_SHOW_LIMIT <= $cnt) return TRUE; 
        
        return FALSE;
    }
}
