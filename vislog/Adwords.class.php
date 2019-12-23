<?php


/**
 * Хронология на посещенията от Google Adwords
 *
 *
 * @category  bgerp
 * @package   vislog
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Последни документи и папки
 */
class vislog_Adwords extends core_Manager
{
    /**
     * Време за опресняване информацията при лист на събитията
     */
    public $refreshRowsTime = 15000;
    
    
    /**
     * Необходими мениджъри
     */
    public $loadList = 'vislog_Wrapper, plg_RowTools2, plg_Search, plg_RefreshRows, plg_Created';
    
    
    /**
     * Заглавие
     */
    public $title = 'Хитове от Adwords';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin,cms,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin,cms,ceo';
    
    
    public $listFields = 'id,ip,match,keywords,ad,domainId,createdOn,createdBy';
    
    
    /**
     * По кои полета ще се търси
     */
    public $searchFields = 'ip, keywords, ad';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('ip', 'ip(15,showNames)', 'caption=Ip');
        
        $this->FLD('match', 'enum(b=broad,p=phrase,e=exact)', 'caption=Тип, mandatory');
        $this->FLD('keywords', 'varchar', 'caption=Ключови думи');
        $this->FLD('ad', 'varchar(20)', 'caption=Реклама');
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt,allowEmpty)', 'caption=Домейн,notNull,autoFilter');

        $this->setDbUnique('ip, match, keywords, ad');
    }
    
    
    /**
     * Добавя в регистъра
     */
    public static function add()
    {
        $rec = new stdClass();
        $rec->ip = $_SERVER['REMOTE_ADDR'];
        $rec->match = Request::get('awMatch');
        $rec->keywords = Request::get('awKeywords');
        $rec->ad = Request::get('awAd');
        $rec->domainId = cms_Domains::getPublicDomain('id');

        if ($rec->match || $rec->keywords || $rec->ad) {
            self::save($rec, null, 'IGNORE');
        }
        
        if($rec->keywords) {
            Mode::set('adWordsQuery', $rec->keywords . ', ' . $rec->match);
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     * Форма за търсене по дадена ключова дума
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->showFields = 'search,domainId';  //, HistoryResourceId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search,domainId';
        $data->listFilter->input($data->listFilter->showFields, 'silent');
        
        if ($domainId = $data->listFilter->rec->domainId) {
            $data->query->where(array("#domainId = '[#1#]'", $domainId));
        }

        $data->listFilter->input();
        
        $data->query->orderBy('#createdOn=DESC');
    }
    
    
    /**
     * Преобразуване към вербален запис
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {   
        $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, true, true);
        if ($rec->keywords) {
            $dkw = urldecode($rec->keywords);
            if ($dkw && mb_strlen($dkw) * 2 < mb_strlen($rec->keywords)) {
                $rec->keywords = $dkw;
                $row->keywords = $mvc->getVerbal($rec, 'keywords');
            }
        }
    }
}
