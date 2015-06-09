<?php 


/**
 * Абониране за бюлетина
 *
 * @category  bgerp
 * @package   marketing
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class marketing_Bulletin extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Абонати за бюлетина";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'ceo, marketing';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'ceo, marketing';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, marketing';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'ceo, marketing';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, marketing';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'ceo, marketing';
    

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'marketing_Wrapper,  plg_RowTools, plg_Created, plg_Sorting, plg_Search';
    
    
    /**
     * 
     */
    public $searchFields = 'email, name, company';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('email', 'email', 'caption=Имейл, mandatory');
        $this->FLD('name', 'varchar(128)', 'caption=Имена, oldFieldName=names');
        $this->FLD('company', 'varchar(128)', 'caption=Фирма');
        $this->FLD('ip', 'ip', 'caption=IP, input=none');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър, input=none');
        
        $this->setDbUnique('email');
    }
    
    
    /**
     * 
     * 
     * @param core_LoginLog $mvc
     * @param object $row
     * @param object $rec
     * @param array $fields
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	// Оцветяваме BRID
    	$row->brid = logs_Browsers::getLink($rec->brid);
    	
        if ($rec->ip) {
        	// Декорираме IP-то
            $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, TRUE);
    	}
    }
}
