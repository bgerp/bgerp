<?php



/**
 * Хронология на посещенията от Google Adwords
 *
 *
 * @category  bgerp
 * @package   vislog
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Последни документи и папки
 */
class vislog_Adwords extends core_Manager
{
    
    
    
    /**
     * Време за опресняване информацията при лист на събитията
     */
    var $refreshRowsTime = 15000;
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'vislog_Wrapper, plg_RowTools2, plg_Search, plg_RefreshRows, plg_Created';
    
    
    /**
     * Заглавие
     */
    var $title = 'Хитове от Adwords';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'admin';
    
    
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
     * По кои полета ще се търси
     */
    public $searchFields = 'ip, keywords, ad';

    /**
     * Описание на модела
     */
    function description()
    {   
        $this->FLD('ip', 'ip(15,showNames)', 'caption=Ip');

        $this->FLD('match', 'enum(b=broad,p=phrase,e=exact)', 'caption=Тип, mandatory');
        $this->FLD('keywords', 'varchar', 'caption=Ключови думи');
        $this->FLD('ad', 'varchar(20)', 'caption=Реклама');
         
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

    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     * Форма за търсене по дадена ключова дума
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->showFields = 'search';  //, HistoryResourceId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        
        $data->query->orderBy("#createdOn=DESC");
    }


    /**
     * Преобразуване към вербален запис
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if($rec->keywords) {
            $dkw = urldecode($rec->keywords);
            if($dkw && mb_strlen($dkw) * 2 < mb_strlen($rec->keywords)) {
                $rec->keywords = $dkw;
                $row->keywords = $mvc->getVerbal($rec, 'keywords');
            }
        }
    }

 
}
