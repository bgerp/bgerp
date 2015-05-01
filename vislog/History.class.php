<?php



/**
 * Клас 'vislog_History' -
 *
 *
 * @category  vendors
 * @package   vislog
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class vislog_History extends core_Manager {
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = 'Система';
    
    
    /**
     * Заглавие
     */
    var $title = 'История на хитовете';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 40;
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = "Countries=drdata_Countries,IpToCountry=drdata_IpToCountry,plg_Created,vislog_Wrapper,HistoryResources=vislog_HistoryResources,plg_RefreshRows";
    
    
    /**
     * На колко време да обновява списъка на екрана
     */
    var $refreshRowsTime = 60000;


    /**
     * Кой  може да пише?
     */
    var $canWrite = "no_one";
    
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'cms, ceo, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, admin, cms';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo, admin, cms';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('ip', 'varchar(15)', 'caption=Ip,tdClass=aright');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър');
        
        $this->FLD('HistoryResourceId', 'key(mvc=vislog_HistoryResources,select=query,allowEmpty)', 'caption=Ресурс');
        
        $this->setDbIndex('ip');
    }
    
    
    /**
     * Добавя нов запис в лога
     * @param string $query
     * @param boolean $returnCnt
     */
    static function add($query, $returnCnt = FALSE)
    {   
        vislog_Adwords::add();

        $rec = new stdClass();
        
        $rec->query = $query;
        
        $History = cls::get('vislog_History');
        
        $History->save($rec);
        
        if($returnCnt) {
            if($rec->id) {
                
                // Преброяваме и връщаме броя посещения на ресурса
                $historyQuery = $History->getQuery();
                $historyQuery->where("#HistoryResourceId = {$rec->HistoryResourceId}");
                
                return $historyQuery->count();
            }
        } else {
            
            return $rec->id;
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     * Форма за търсене по дадена ключова дума
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->showFields = 'ip, brid';  //, HistoryResourceId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input($data->listFilter->showFields, 'silent');
        
        if($ip = $data->listFilter->rec->ip){
            $ip = str_replace('*', '%', $ip);
            $data->query->where(array("#ip LIKE '[#1#]'", $ip));
        }
        
        if($brid = $data->listFilter->rec->brid){
            $data->query->where(array("#brid LIKE '[#1#]'", $brid));
        }
        
        if($HistoryResourceId = $data->listFilter->rec->HistoryResourceId){
            // $data->query->where("#HistoryResourceId = {$HistoryResourceId}");
        }
        
        $data->query->orderBy("#createdOn=DESC");
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, $id, &$rec)
    {
        // Поставяме IP ако липсва
        if(!$rec->ip) $rec->ip = $_SERVER['REMOTE_ADDR'];
        
        if(!$rec->brid) {
            $rec->brid = core_Browser::getBrid();
        }
        
        // Съкращаваме заявката, ако е необходимо
        if(strlen($rec->query) > 255) {
            $i = 0; $q = '';
            
            while(strlen($q) <= 255) {
                $q .= mb_substr($rec->query, $i++, 1);
            }
            $rec->query = $q;
        }
        
        $rec->HistoryResourceId = $mvc->HistoryResources->fetchField(array("#query = '[#1#]'", $rec->query), 'id');
        
        if(!$rec->HistoryResourceId) {
            $sRec = new stdClass();
            $sRec->query = $rec->query;
            $rec->HistoryResourceId = $mvc->HistoryResources->save($sRec);
        }
        
        // Ако имаме такъв запис в последните 5 минути - връщаме FALSE, за да не продължи обработката
        $conf = core_Packs::getConfig('vislog');
        $last5 = dt::addSecs(0 - $conf->VISLOG_ALLOW_SAME_IP);
        
        if($mvc->fetch("#ip = '{$rec->ip}' AND #HistoryResourceId = {$rec->HistoryResourceId} AND #createdOn > '{$last5}'")) {
            
            return FALSE;
        }
        
        // Записваме данните за Referer-а
        $Referer = cls::get('vislog_Referer');
        $Referer->add($rec->HistoryResourceId);
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, TRUE, TRUE);
        
        $ref = vislog_Referer::getReferer($rec->ip, $rec->createdOn);
        
        if($ref) {
            $row->HistoryResourceId .= "<br><span style='font-size:0.6em;'>{$ref}</span>";
        }

        $row->brid = core_Browser::getLink($rec->brid);
    }
}

