<?php 


/**
 * Декларации за съответствия
 *
 *
 * @category  bgerp
 * @package   dec
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class social_Sharings extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Споделяния";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Споделяне";

    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'dec_Wrapper, plg_Created, plg_State2, plg_RowTools';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, social';
    
    
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo, social';

    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('name', 'varchar(32)', 'caption=Услуга');
		$this->FLD('url', 'varchar(128)', 'caption=URL за споделяне');
		$this->FLD('icon', 'fileman_FileType(bucket=social)', 'caption=Икона');
		$this->FLD('sharedCnt', 'int', 'caption=Брой споделяния');
    }
    
    
    /**
     * 
     */
    static function getButtons()
    {
    	$tpl = new ET('');
    	
    	$query = static::getQuery();
		$socialNetworks = $query->fetchAll();

		foreach($socialNetworks as $socialNetwork){
			if($socialNetwork->state == 'active'){
				if($socialNetwork->icon){
					$icon = $socialNetwork->icon;
				} else {
					$socUrl = $socialNetwork->url;
					$name = self::getServiceNameByUrl($socUrl);
					$icon = sbf("cms/img/16/{$name}.png",'');
					
				}
				$img = ht::createElement('img', array('src' => $icon));
				
				$url = array('social_Sharings', 'Redirect', $socialNetwork->id, 'socUrl' => '[#SOC_URL#]', 'socTitle' => '[#SOC_TITLE#]', 'socSummary' => '[#SOC_SUMMARY#]');
				//$url = array('social_Sharings', 'Redirect', $socialNetwork->id, 'socUrl' => $socialNetwork->url, 'socTitle' => $socialNetwork->name, 'socSummary' => "Shared '{$socialNetwork->name}'");
				$link = ht::createLink("{$img} « " . $socialNetwork->sharedCnt, $url, NULL, array("class"=>"soc-sharing", "target"=>"_blank"));
				
				$link = str_replace('%5B%23', '[#', $link);
				$link = str_replace('%23%5D', '#]', $link);
				$link = str_replace('&amp;', '&', $link);
			
				$link = new ET(ET::unEscape($link));
		        
				$tpl->append($link);
			}

		}
		return $tpl;
    }
    
    
    public function act_Redirect()
    {
    	$id = core_Request::get('id', 'key(mvc='.get_class($mvc).')');
    	$rec = self::fetch("#id = '{$id}'"); 
    	$curUrl = toUrl(getCurrentUrl());
    	$arrayUrl = core_Url::parseUrl($curUrl); 
    	$domain = $arrayUrl['query_params']['domain'];
    	$url = $arrayUrl['query_params']['socUrl'];
    	$title = $arrayUrl['query_params']['socTitle'];
    	$summary = $arrayUrl['query_params']['socSummary'];
    	$redUrl = str_replace("[#URL#]", $url, $rec->url);
    	if(strpos($rec->url, "[#TITLE#]") || strpos($rec->url, "[#SUMMERY#]"))
    	{
	    	$redUrl = str_replace("[#TITLE#]", $title, $redUrl);
	    	$redUrl = str_replace("[#SUMMERY#]", $summary, $redUrl);
    	}
    	$redUrl = $domain.$redUrl;
    	$rec->sharedCnt += 1;
    	self::save($rec);
    	
    	if($rec) {
            if(core_Packs::fetch("#name = 'vislog'")) {
               vislog_History::add("Споделяне " . $rec->name);
            }
        }
    	self::save($rec);
    	
    	return new Redirect ($redUrl);
    }
    
    
    function act_Test()
    {
    	$url = "https://plus.google.com/101118968403881827448/posts";
    	bp(self::getButtons());
    }
    
    
    /**
     * Функцията сравнява подаденото URL с масив
     * от начално заредените URL в пакета
     * и връща като резултат името на услугата
     *
     */
    static function getServiceNameByUrl($url)
    {
    	$services = array ( "plus.google.com"=>"google-plus",
    					    "svejo.net"=>"svejo",
					    	"twitter.com"=>"twitter",
					    	"digg.com"=>"digg",
					    	"facebook.com"=>"facebook",
					    	"stumbleupon.com"=>"stumbleupon",
					    	"del.icio.us"=>"delicious",
					    	"google.com"=>"google-buzz",
					    	"linkedin.com"=>"linkedin",
					    	"slashdot.org"=>"slashdot",
					    	"technorati.com"=>"technorati",
					    	"posterous.com"=>"posterous",
					    	"tumblr.com"=>"tumblr",
					    	"reddit.com"=>"reddit",
					    	"google.com/bookmarks"=>"google-bookmarks",
					    	"newsvine.com"=>"newsvine",
					    	"ping.fm"=>"pingfm",
					    	"evernote.com"=>"evernote",
					    	"friendfeed.com"=>"friendfeed");
    	
    	foreach($services as $servic=>$nameServic){
    		if(strpos($url, $servic)){
    			return $nameServic;
    		}
    	}
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	
    	// Подготвяме пътя до файла с данните 
    	$file = "social/data/Sharings.csv";
    	
    	// Кои колонки ще вкарваме
    	$fields = array( 
    		0 => "name", 
    		1 => "url",
    		2 => "icon",
    		3 => "sharedCnt",
    	
    		
    	);
    	
    	
    	// Импортираме данните от CSV файла. 
    	// Ако той не е променян - няма да се импортират повторно 
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields, NULL, NULL, TRUE); 
     	
    	// Записваме в лога вербалното представяне на резултата от импортирането 
    	$res .= $cntObj->html;
 		
    }
}