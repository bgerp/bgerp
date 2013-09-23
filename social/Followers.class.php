<?php 


/**
 * Следене в социалните мрежи
 *
 *
 * @category  bgerp
 * @package   social
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class social_Followers extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Следени";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Следене";
        
    
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
		$this->FLD('title', 'varchar(32)', 'caption=Услуга');
		$this->FLD('url', 'varchar(128)', 'caption=URL за последване');
		$this->FLD('icon', 'fileman_FileType(bucket=social)', 'caption=Икона');
		$this->FLD('followersCnt', 'int', 'caption=Брой последователи');
    }
    
 
    /**
     * Създаване на бутони за споделяне
     */
    static function getButtons()
    {
    	// Взимаме всяко tpl, в което сме 
    	// сложили прейсхолдер [#social_Followers::getButtons#]
    	$tpl = new ET('');
    	
    	// Правим заявка към базата
    	$query = static::getQuery();
		$socialNetworks = $query->fetchAll();

		// За всеки един запис от базата
		foreach($socialNetworks as $socialNetwork){
			
			// ако услугата е "видима"
			if($socialNetwork->state == 'active'){
				
				// Вземаме качената икона
				if($socialNetwork->icon){
					$icon = $socialNetwork->icon;
					
					// Ако тя липсва
				} else {
					
					// Вземаме URL от базата
					$socUrl = $socialNetwork->url;
					
					// Намираме името на функцията
					$name = social_Sharings::getServiceNameByUrl($socUrl);
					
					// Намираме иконата в sbf папката
					$icon = sbf("cms/img/24/{$name}.png",'');
				}
				
				// Създаваме иконата за бутона
				$img = ht::createElement('img', array('src' => $icon));
				
				// Генерираме URL-то на бутона
				$url = array('social_Followers', 'Redirect', $socialNetwork->id);
				
				// Създаваме линка на бутона
				$link = ht::createLink("{$img}" . $socialNetwork->sharedCnt, $url, NULL, array("class" => "soc-following", "target"=>"_blank"));
				
				// Връщаме ескейпването, за да може да заменим
				// по-късно плейсхолдерите
				$link = str_replace('%5B%23', '[#', $link);
				$link = str_replace('%23%5D', '#]', $link);
				$link = str_replace('&amp;', '&', $link);
			
				$link = new ET(ET::unEscape($link));
		        
				// Добавямего към шаблона
				$tpl->append($link);  
				//$tpl->replace(toUrl(getCurrentUrl()), 'SOC_URL');
				//$tpl->replace('aaaaa', 'SOC_TITLE');
				//$tpl->replace('aaaa', 'SOC_SUMMARY');
			}
		}

		// Връщаме тулбар за споделяне в социалните мреци
		return $tpl;
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
    	    	    	
    	// Увеличаване на брояча на споделянията
    	$rec->sharedCnt += 1;
    	self::save($rec);
    	// Записваме в историята, че сме направели споделяне
    	if($rec) {
            if(core_Packs::fetch("#name = 'vislog'")) {
               vislog_History::add("Последване в " . $rec->title);
            }
        }
    	self::save($rec);
    	
    	// Връщаме URL-то
    	return new Redirect ($rec->url);
    }
       
}
