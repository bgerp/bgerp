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
    var $title = "Бутони за споделяне в социални мрежи";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Споделяне";

    
    /**
     * Разглеждане на листов изглед
     */
    var $canSingle = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'social_Wrapper, plg_Created, plg_State2, plg_RowTools2';

   
    /**
     * Полета за листовия изглед
     */
    var $listFields = 'name,url,icon,sharedCnt,state,order';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms, social, admin, ceo';
        
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cms, social, admin, ceo';

    
    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('name', 'varchar(32)', 'caption=Услуга,mandatory');
		$this->FLD('url', 'varchar(128)', 'caption=URL, hint=URL за споделяне,mandatory');
		$this->FLD('icon', 'fileman_FileType(bucket=social)', 'caption=Икона');
		$this->FLD('sharedCnt', 'int', 'caption=Споделяния, input=none,notNull');
		$this->FLD('order', 'int(3)', 'caption=Подредба');
        
        // Уникално име на бутона
        $this->setDbUnique('name');
    }
    
    
    /**
     * Създаване на бутони за споделяне
     */
    static function getButtons()
    {
    	// Правим заявка към базата
    	$query = static::getQuery();
    	$query->orderBy("#order");
		$socialNetworks = $query->fetchAll("#state = 'active'");

        if(!count($socialNetworks)) return;
        
        $cUrl    = cms_Content::getShortUrl(); 
        $cntUrl  = toUrl($cUrl, 'absolute');
        $selfUrl = substr(rawurlencode($cntUrl), 4);

        $selfTitle   = rawurlencode(html_entity_decode(Mode::get('SOC_TITLE')));
        $selfSummary = rawurlencode(str::truncate(html_entity_decode(Mode::get('SOC_SUMMARY')), 200));
    	
        // Взимаме всяко tpl, в което сме 
    	// сложили прейсхолдер [#social_Sharings::getButtons#]
    	$tpl = new ET('');

		// За всеки един запис от базата
		foreach($socialNetworks as $socialNetwork){
				
			// Вземаме качената икона
			if($socialNetwork->icon){
				
	            $imgInst = new thumb_Img(array($socialNetwork->icon, 16, 16, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'small-no-change', 'verbalName' => $socialNetwork->title));
	            $icon = $imgInst->getUrl('forced');
	            
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
			$img = ht::createElement('img', array('src' => $icon, 'alt' => "{$name}"));
 
			// Генерираме URL-то на бутона
			$url =  substr(toUrl(array(  'social_Sharings',
                                        'Redirect', 
                                        $socialNetwork->id, 
									    'socUrl' => $selfUrl, 
									    'socTitle' => $selfTitle, 
									    'socSummary' => $selfSummary
                                     ), 'absolute'
                ), 4) ;	
			
			// Търсим, дали има запис в модела, който отброява споделянията
			$socCnt = social_SharingCnts::fetch(array("#networkId = '{$socialNetwork->id}' AND LOWER(#url) LIKE '%[#1#]'", self::getCanonicUrlPart($cntUrl)));

			if($socCnt){
				// Ако е намерен такъв запис, 
				// взимаме броя на споделянията
				$socCntP = $socCnt->cnt;
			} else {
				// за сега нямаме споделяне
				$socCntP = 0;
			}
			
			// Създаваме линка на бутона
			$link = ht::createLink("{$img}  <sup>+</sup>" . $socCntP, 
									'#', 
                                    NULL, 
                                    array(
                                        "class"   => "soc-sharing", 
                                        "title"   => 'Споделете в|* ' . $socialNetwork->name,
                                        "rel"     => "nofollow",
                                        "onclick" => "window.open('http' + '{$url}')"));
				
			$link = (string) $link;

			// Добавяме го към шаблона
			$tpl->append($link);
		}
		
        $str = $tpl->getContent();
       
		// Връщаме тулбар за споделяне в социалните мреци
		return "<div class='soc-sharing-holder noSelect'>" . $str . "</div>";
    }
    
    
    /**
     * Функция за споделяне
     */
    public function act_Redirect()
    {
    	$id = core_Request::get('id', 'int');
    	
    	// Намираме нейния запис
    	expect($rec = self::fetch($id)); 
    	    	
    	// URL към обекта който ще споделяме
    	expect($url = Request::get('socUrl'));
    	$url = 'http' . $url;
        $urlDecoded = urldecode($url);

        // Очакваме в началото на url-то за споделяне да има валиден протокол
        expect(strpos($urlDecoded, 'http://') === 0 || strpos($urlDecoded, 'https://') === 0, $urlDecoded, $rec);
        
    	// Заглавието на обекта
    	$title = Request::get('socTitle');
    	
    	// Описание на обекта
    	$summary = Request::get('socSummary');
    	
    	// Заместваме данните в URL за редиректване
    	$redUrl = str_replace("[#URL#]", $url, $rec->url);
        $redUrl = str_replace("[#TITLE#]", $title, $redUrl);
        $redUrl = str_replace("[#SUMMARY#]", $summary, $redUrl);
    	// Записваме в историята, че сме направели споделяне
        if(core_Packs::fetch("#name = 'vislog'") &&
            vislog_History::add("Споделяне в " . $rec->name . " на " . $urlDecoded)) {
 
            if (Mode::is('javascript', 'yes') && !log_Browsers::detectBot()){
 
                // Увеличаване на брояча на споделянията
	    	    $rec->sharedCnt++;
	            self::save($rec, 'sharedCnt');             
                  
                // Увеличаваме брояча на споделянията за конкретната страница
                social_SharingCnts::addHit($rec->id, $urlDecoded);
            }
        }

    	// Връщаме URL-то
    	return new Redirect($redUrl);
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
					    	"delicious.com"=>"delicious",
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
							"youtube.com"=>"youtube",
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
     * Преди запис
     */
    static function on_BeforeSave($mvc, $res, $rec)
    {   
    	if($rec->csv_order == 0){
    		static $i;
    		
    		if (!$i) {
    			$i = 1;
    		} else {
    			$i = $i+1;
    		}
    		
    		$rec->order = $i;
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
    		5 => "csv_order",
    	);
    	    	
    	// Импортираме данните от CSV файла. 
    	// Ако той не е променян - няма да се импортират повторно 
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields, NULL, NULL); 
     	
    	// Записваме в лога вербалното представяне на резултата от импортирането 
    	$res .= $cntObj->html;
    }

    
 	/**
     * Пренасочва URL за връщане след запис към лист изгледа
     */
    function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако е субмитната формата 
        if ($data->form && $data->form->isSubmitted()) {

            // Променяма да сочи към single'a
            $data->retUrl = toUrl(array($mvc, 'list'));
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
    	if ($form->isSubmitted()) {
	    	if (empty($form->rec->name)) {
	    		
	            // Сетваме грешката
	            $form->setError('name', 'Непопълнено име на социалната мрежа');
	        }
	        
	        if(empty($form->rec->url)){
	        	
	        	// Сетваме грешката
	            $form->setError('url', 'Непопълнено URL за споделяне');
	        }
    	}
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {   
    	$data->query->orderBy("#order");
    }


    /**
     * Връща канонична част от URL-to
     */
    static function getCanonicUrlPart($url)
    {
        $url = strtolower(trim($url));
        $url = str_replace('://www.', '://', $url);
        $url = str_replace('http://', '', $url);
        $url = str_replace('https://', '', $url);
        
        return $url;
    }

}
