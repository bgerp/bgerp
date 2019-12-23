<?php


/**
 * Клас 'vislog_Referer'
 *
 * Клас-мениджър, който логва от къде идват посетителите
 *
 *
 * @category  bgerp
 * @package   vislog
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class vislog_Referer extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Рефериране';
    
    
    /**
     * Старо име на модела
     */
    public $oldClassName = 'vislog_Refferer';
    
    
    /**
     * Кой  може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да чете?
     */
    public $canRead = 'cms, ceo, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin, cms';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, admin, cms';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools,plg_Created,plg_Search,vislog_Wrapper';
   
    
    /**
     * Полета, по които ще се търси
     */
    public $searchFields = "referer,query,ip";


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('referer', 'url(255)', 'caption=Referer,oldFieldName=refferer');
        $this->FLD('query', 'varchar(255)', 'caption=Query,column=none');
        $this->FLD('searchLogResourceId', 'key(mvc=vislog_HistoryResources,title=query)', 'caption=Ресурс');
        $this->FLD('ip', 'ip(15,showNames)', 'caption=Ip');
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=titleExt,allowEmpty)', 'caption=Домейн,notNull,autoFilter');

        $this->setDbIndex('ip');
    }
    
    
    /**
     * Добавя запис за страницата от която идва посетителя
     */
    public function add($resource)
    {
        $rec = new stdClass();
        
        $rec->referer = $_SERVER['HTTP_REFERER'];
        
        if ($rec->referer) {
            $parts = @parse_url($rec->referer);
            
            $localHost = $_SERVER['SERVER_NAME'];
            
            if (stripos($parts['host'], $localHost) === false) {
               
                if($query = Mode::get('adWordsQuery')) {
                    $rec->query = $query;
                } else { 
                    parse_str($parts['query'], $query);
                    $search_engines = array(
                        'bing' => 'q',
                        'google' => 'q',
                        'yahoo' => 'p'
                    );
                
                    preg_match('/(' . implode('|', array_keys($search_engines)) . ')\./', $parts['host'], $matches);
                    
                    $rec->query = isset($matches[1], $query[$search_engines[$matches[1]]]) ? $query[$search_engines[$matches[1]]] : '';
                }

                $rec->searchLogResourceId = $resource;
                
                // Поставяме IP ако липсва
                if (!$rec->ip) {
                    $rec->ip = $_SERVER['REMOTE_ADDR'];
                }
                $rec->domainId = cms_Domains::getPublicDomain('id');

                $this->save($rec);
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');

        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search,domainId';

        $data->listFilter->input($data->listFilter->showFields, 'silent');
        
        $domainsCnt = cms_Domains::count();
 
        // Ако е ясен домейна, махаме колонката
        if($data->listFilter->rec->domainId || $domainsCnt == 1) {
            unset($data->listFields['domainId']);
        }

        if($domainsCnt == 1) {
            $data->listFilter->showFields = 'search';   
        }

        if ($domainId = $data->listFilter->rec->domainId) {
            $data->query->where(array("#domainId = '[#1#]'", $domainId));
        }


    }
    
    
    /**
     * Вербализиране на row
     * Поставя хипервръзка на ip-то
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, true, true);

        $query = type_Varchar::escape($rec->query);

        if ($query) {
            $row->searchLogResourceId .= "<br><span style='font-size:0.6em;'>{$query}</span>";
        }
    }
    
    
    /**
     * Показва съкратена информация за реферера, ако има такъв
     */
    public static function getReferer($ip, $time)
    {
        $rec = self::fetch(array("#ip = '[#1#]' AND #createdOn = '[#2#]'", $ip, $time));
        
        if ($rec) {
            $parse = @parse_url($rec->referer);
            
            $res = str_replace('www.', '', strtolower($parse['host']));
            
            if ($rec->query) {
                $res .= ': ' . self::getVerbal($rec, 'query');
            }
            
            return $res;
        }
    }
}
