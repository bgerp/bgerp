<?php



/**
 * Хронология на посещенията от Google Adwords
 *
 *
 * @category  bgerp
 * @package   vislog
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Последни документи и папки
 */
class vislog_Adwords extends core_Manager
{
    
    
    
    /**
     * @see bgerp_RefreshRowsPlg
     */
    var $bgerpRefreshRowsTime = 15000;
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'vislog_Wrapper, plg_RowTools, plg_Search, bgerp_RefreshRowsPlg, plg_Created';
    
    
    /**
     * Заглавие
     */
    var $title = 'Хитове от Adwords';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,cms,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin,cms,ceo';
    
    public $listFields = 'id,ip,match,keywords,ad,createdOn,createdBy';
     
    /**
     * Описание на модела
     */
    function description()
    {   
        $this->FLD('ip', 'ip(15,showNames)', 'caption=Ip');

        $this->FLD('match', 'enum(b=broad,p=phrase,e=exact)', 'caption=Тип, mandatory');
        $this->FLD('keywords', 'varchar', 'caption=Ключови думи');
        $this->FLD('ad', 'int', 'caption=Реклама');
         
        $this->setDbUnique('ip, match, keywords, ad');
    }
    

    /**
     * Добавя в регистъра
     */
    static function add()
    {
        $rec = new stdClass();
        $rec->ip = $_SERVER['REMOTE_ADDR'];
        $rec->match = Request::get('awMatch');
        $rec->keywords = Request::get('awKeywords');
        $rec->ad = Request::get('awAd');

        if($rec->match ||  $rec->keywords || $rec->ad) {
            self::save($rec, NULL, 'IGNORE');
        }

    }
    
 
}
