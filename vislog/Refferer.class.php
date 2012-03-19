<?php



/**
 * Клас 'vislog_Refferer' (кратко име 'URL')
 *
 * Клас-мениджър, който логва от къде идват посетителите
 *
 *
 * @category  all
 * @package   vislog
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class vislog_Refferer extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = "Рефериране";
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = "no_one";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("refferer", 'varchar(255)', 'caption=Refferer');
        $this->FLD("query", 'varchar(255)', 'caption=Query');
        $this->FLD('searchLogResourceId', 'key(mvc=vislog_HistoryResources,title=query)', 'caption=Resource');
        
        $this->load("plg_RowTools,plg_Created,vislog_Wrapper");
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function add($resource)
    {
        
        $rec->refferer = $_SERVER['HTTP_REFERER'];
        
        if($rec->refferer) {
            
            $parts = parse_url($rec->refferer);
            
            $localHost = $_SERVER['SERVER_NAME'];
            
            if(stripos($parts['host'], $localHost) === FALSE) {
                
                parse_str($parts['query'], $query);
                
                $search_engines = array(
                    'bing' => 'q',
                    'google' => 'q',
                    'yahoo' => 'p'
                );
                
                preg_match('/(' . implode('|', array_keys($search_engines)) . ')\./', $parts['host'], $matches);
                
                $rec->query = isset($matches[1]) && isset($query[$search_engines[$matches[1]]]) ? $query[$search_engines[$matches[1]]] : '';
                
                $rec->searchLogResourceId = $resource;
                
                $this->save($rec);
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
    }
} 