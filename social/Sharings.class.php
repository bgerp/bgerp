<?php 


/**
 * Споделяне в социалните мрежи
 *
 *
 * @category  bgerp
 * @package   social
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
    var $loadList = 'social_Wrapper, plg_Created, plg_State2, plg_RowTools';
    
    
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
     * Създаване на бутони за споделяне
     */
    static function getButtons()
    {
    	// Взимаме всяко tpl, в което сме 
    	// сложили прейсхолдер [#social_Sharings::getButtons#]
    	$tpl = new ET('');
    	
    	// Правим заявка към базата
    	$query = static::getQuery();
		$socialNetworks = $query->fetchAll("#state = 'active'");

		// За всеки един запис от базата
		foreach($socialNetworks as $socialNetwork){
				
			// Вземаме качената икона
			if($socialNetwork->icon){
				$icon = $socialNetwork->icon;
					
				// Ако тя липсва
			} else {
					
				// Вземаме URL от базата
				$socUrl = $socialNetwork->url;
					
				// Намираме името на функцията
				$name = self::getServiceNameByUrl($socUrl);
					
				// Намираме иконата в sbf папката
				$icon = sbf("cms/img/16/{$name}.png",'');
			}
				
			// Създаваме иконата за бутона
			$img = ht::createElement('img', array('src' => $icon));
				
			// Генерираме URL-то на бутона
			$url = array('social_Sharings', 'Redirect', $socialNetwork->id, 'socUrl' => 'SOC_URL', 'socTitle' => 'SOC_TITLE', 'socSummary' => 'SOC_SUMMARY');				
				
			// Създаваме линка на бутона
			$link = ht::createLink("{$img}  <sup>+</sup>" . $socialNetwork->sharedCnt, $url, NULL, array("class"=>"soc-sharing", "target"=>"_blank"));
				
			$link = (string) $link;
		    $from = array('SOC_URL', 'SOC_TITLE', 'SOC_SUMMARY');
		    $to = array (rawurlencode(toUrl(getCurrentUrl())), 
		    			 rawurlencode(Mode::get('SOC_TITLE')), 
		    			 core_String::truncate(rawurlencode((Mode::get('SOC_SUMMARY'))), 200));
					
		 	$link = str_replace($from, $to, $link);
				
			// Добавяме го към шаблона
			$tpl->append($link);
		}
		
		// Връщаме тулбар за споделяне в социалните мреци
		return "<div class='soc-sharing-holder'>".$tpl."</div>";
    }
    
    
    /**
     * Функция за споделяне
     */
    public function act_Redirect()
    {
    	// Взимаме $ид-то на услугата
    	$id = core_Request::get('id', 'key(mvc='.get_class($mvc).')');
    	
    	// Намираме нейния запис
    	$rec = self::fetch("#id = '{$id}'"); 
    	
    	// Текущото URL
    	$curUrl = toUrl(getCurrentUrl()); 
    	
    	// Парсираме го, за да извлечем параметрите от заявката
    	$arrayUrl = core_Url::parseUrl($curUrl);

    	// Домейна
    	$domain = $_SERVER['SERVER_NAME'];
    	
    	// URL към обекта който ще споделяме
    	$url = $domain.$arrayUrl['query_params']['socUrl'];
    	
    	// Заглавието на обекта
    	$title = $arrayUrl['query_params']['socTitle'];
    	
    	// Описание на обекта
    	$summary = $arrayUrl['query_params']['socSummary'];
    	
    	// Заместваме данните в URL за редиректване
    	$redUrl = str_replace("[#URL#]", $url, $rec->url);
    	if(strpos($rec->url, "[#TITLE#]") || strpos($rec->url, "[#SUMMARY#]"))
    	{
	    	$redUrl = str_replace("[#TITLE#]", $title, $redUrl);
	    	$redUrl = str_replace("[#SUMMARY#]", $summary, $redUrl);
    	}
    	
    	// Увеличаване на брояча на споделянията
    	$rec->sharedCnt += 1;
    	
    	// Записваме в историята, че сме направели споделяне
    	if($rec) {
            if(core_Packs::fetch("#name = 'vislog'")) {
               if(vislog_History::add("Споделяне " . $rec->name)){
               	 self::save($rec, 'sharedCnt');
               }
            }
        }
        
    	
    	
    	// Връщаме URL-то
    	return new Redirect ($redUrl);
    }
    
    
    /**
     * Тестова функция
     */
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
    	// Масив от домейни => имена на услуги
    	// заредени при началното инициализиране
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
    		// Проверява URL-to за първия срещнат домейн
    		if(strpos($url, $servic)){
    			// и връща името на услугата
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
    		4 => "state",
    	
    		
    	);
    	
    	
    	// Импортираме данните от CSV файла. 
    	// Ако той не е променян - няма да се импортират повторно 
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields, NULL, NULL, TRUE); 
     	
    	// Записваме в лога вербалното представяне на резултата от импортирането 
    	$res .= $cntObj->html;
 		
    }
}