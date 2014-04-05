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
     * @todo Чака за документация...
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
    var $loadList = "Countries=drdata_Countries,IpToCountry=drdata_IpToCountry,plg_Created,vislog_Wrapper,HistoryResources=vislog_HistoryResources";
    
    
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
        $this->FLD('ip', 'varchar(15)', 'caption=Ip,tdClass=rightAlign');
        
        $this->FLD('HistoryResourceId', 'key(mvc=vislog_HistoryResources,select=query,allowEmpty)', 'caption=Query');
                
        $this->setDbUnique('ip,HistoryResourceId,createdBy');
    }
    
    
    /**
     * Добавя нов запис в лога
     * @param string $query
     * @param boolean $returnCnt
     */
    static function add($query, $returnCnt = FALSE)
    {
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
    static function on_AfterPrepareListFilter($mvs, &$res, $data)
    {
        $data->listFilter->showFields = 'ip'; //, HistoryResourceId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input('ip', 'silent');
        
        if($ip = $data->listFilter->rec->ip){
            $data->query->where("#ip LIKE '{$ip}%'");
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
        
        
        // Ако имаме такъв запис - връщаме ИСТИНА, за да не продължи обработката
        if($mvc->fetch("#ip = '{$rec->ip}' AND #HistoryResourceId = {$rec->HistoryResourceId}")) {
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
       // $row->ip = ht::createLink($row->ip, "http://bgwhois.com/?query=" . $rec->ip, NULL, array('target' => '_blank'));
        
        // Ако имаме име на това ip - слагаме го като префикс, ако не - държавата
       // if($ipName = vislog_IpNames::fetchField(array("#ip = '[#1#]'", $rec->ip), 'name')) {
       //     $name = $ipName;
       // } else {
        //    $name = $mvc->IpToCountry->get($rec->ip);
       // }

        $row->ip = self::decorateIp($rec->ip, $rec->createdOn);
        
        $ref = vislog_Referer::getReferer($rec->ip, $rec->createdOn);

        if($ref) {
            $row->HistoryResourceId .= "<br><span style='font-size:0.6em;'>{$ref}</span>";
        }
    }



    /**
     * Декорира ip адреса
     */
    static function decorateIp($ip, $time)
    {   
        $cnt = self::count(array("#ip = '[#1#]'", $ip));
        $old = self::count(array("#ip = '[#1#]' AND #createdOn <= '[#2#]'", $ip, $time));
        
        $color = sprintf("%02X%02X%02X", min(($old / $cnt) * ($old / $cnt) * ($old / $cnt) * 255, 255),0,0); 

        $count = ht::createLink("{$old}/{$cnt}", 
                    array('vislog_History', 'ip' => $ip),
                    NULL,
                    array('class' => 'weblog-cnt', 'style' => "color:#{$color};"));
        

        $country2 = 'BG'; //= drdata_IpToCountry::get($ip);
        $countryName = drdata_Countries::fetchField("#letterCode2 = '" . strtoupper($country2) . "'", 'commonName' . (core_Lg::getCurrent() == 'bg' ? 'Bg' : ''));

        $country = ht::createLink($country2, "http://bgwhois.com/?query=" . $ip, NULL, array('target' => '_blank', 'class' => 'weblog-ip', 'title' => $countryName));
        

        $ipRec = vislog_IpNames::fetch(array("#ip = '[#1#]'", $ip));

        if($ipRec) {
            $name = vislog_IpNames::getVerbal($ipRec, 'name');
        }

        if(!$name) {
            $name = $ip;
        }
 
        $res = new ET("<div class='weblog'>[#1#]&nbsp;<span class='weblog-ip'>{$name}</span>&nbsp;[#2#]</div>", $country, $count);

        return $res;
    }
}
