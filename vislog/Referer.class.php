<?php



/**
 * Клас 'vislog_Referer'
 *
 * Клас-мениджър, който логва от къде идват посетителите
 *
 *
 * @category  bgerp
 * @package   vislog
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
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
    public $loadList = 'plg_RowTools,plg_Created,vislog_Wrapper';
    

    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('referer', 'varchar(255)', 'caption=Referer,oldFieldName=refferer');
        $this->FLD('query', 'varchar(255)', 'caption=Query');
        $this->FLD('searchLogResourceId', 'key(mvc=vislog_HistoryResources,title=query)', 'caption=Ресурс');
        $this->FLD('ip', 'ip(15,showNames)', 'caption=Ip');

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
                parse_str($parts['query'], $query);
                
                $search_engines = array(
                    'bing' => 'q',
                    'google' => 'q',
                    'yahoo' => 'p'
                );
                
                preg_match('/(' . implode('|', array_keys($search_engines)) . ')\./', $parts['host'], $matches);
                
                $rec->query = isset($matches[1], $query[$search_engines[$matches[1]]])   ? $query[$search_engines[$matches[1]]] : '';
                
                $rec->searchLogResourceId = $resource;
                
                // Поставяме IP ако липсва
                if (!$rec->ip) {
                    $rec->ip = $_SERVER['REMOTE_ADDR'];
                }
                
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
    }


    /**
     * Вербализиране на row
     * Поставя хипервръзка на ip-то
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, true, true);
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
