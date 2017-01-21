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
    var $title = "Бутони за проследяване в социалните мрежи";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Проследяване";
        
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'social_Wrapper, plg_Created, plg_State2, plg_RowTools2, cms_DomainPlg';
    
    
    /**
     * Полета за листовия изглед
     */
    var $listFields = 'title,url,icon,followersCnt,state,order';

    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms, social, admin, ceo';
            
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cms, social, admin, ceo';

    /**
     * Кои може да гледа сингъла
     */
    var $canSingle = 'no_one';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('title', 'varchar(32)', 'caption=Услуга, mandatory');
		$this->FLD('url', 'varchar(128)', 'caption=URL, hint=URL на вашата страница, mandatory');
		$this->FLD('icon', 'fileman_FileType(bucket=social)', 'caption=Икона');
		$this->FLD('followersCnt', 'int', 'caption=Последователи, input=none, notNull');
		$this->FLD('order', 'int', 'caption=Подредба, notNull');
        $this->FLD('domainId', 'key(mvc=cms_Domains, select=*)', 'caption=Домейн,notNull,defValue=bg,mandatory,autoFilter');
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
    	$query->orderBy("#order");
        $domainId = cms_Domains::getPublicDomain('id');
		$socialNetworks = $query->fetchAll("#state = 'active' AND #domainId = {$domainId}");
		
		// За всеки един запис от базата
		foreach($socialNetworks as $socialNetwork){
		    
			// Вземаме качената икона
			if($socialNetwork->icon){
				
	            $imgInst = new thumb_Img(array($socialNetwork->icon, 24, 24, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'small-no-change', 'verbalName' => $socialNetwork->title));
	            $icon = $imgInst->getUrl('forced');

                // Ако тя липсва
			} else {
					
				// Вземаме URL от базата
				$socUrl = $socialNetwork->url;
					
				// Намираме името на функцията
				$name = social_Sharings::getServiceNameByUrl($socUrl);



                if(log_Browsers::isRetina()) {
                    $size = 48;
                } else {
                    $size = 24;
                }

				// Намираме иконата в sbf папката
				$icon = sbf("cms/img/{$size}/{$name}.png",'');

			}
				
			// Създаваме иконата за бутона
			$img = ht::createElement('img', array('src' => $icon, 'alt' => $socialNetwork->title, 'width' => 24, 'height' => 24));
				
			// Генерираме URL-то на бутона
			$url = array('social_Followers', 'Redirect', $socialNetwork->id);
				
			// Създаваме линка на бутона
			$link = ht::createLink("{$img}" . $socialNetwork->sharedCnt, $url, NULL, array("class" => "soc-following noSelect", "target"=>"_blank", "rel"=>"nofollow", "title" => $socialNetwork->title));
       
			// Добавямего към шаблона
			$tpl->append($link);  
		}
		
		// Връщаме тулбар за споделяне в социалните мреци
		return $tpl;
    }


    /**
     * Функция за споделяне
     */
    public function act_Redirect()
    {
    	$id = core_Request::get('id', 'key(mvc=' . get_class($this) . ')');
    	
    	// Намираме нейния запис
    	$rec = self::fetch((int) $id);
    	
    	// Записваме в историята, че сме направели споделяне
    	if($rec) {
            if(core_Packs::fetch("#name = 'vislog'") && 
               vislog_History::add("Последване в " . $rec->title)) {
               
               if (Mode::is('javascript', 'yes')  && !log_Browsers::detectBot()){
               	    	    	
			       // Увеличаване на брояча на споделянията
			       $rec->followersCnt++;
			       self::save($rec, 'followersCnt'); 
               }
            }
        }
    	
    	// Връщаме URL-то
    	return new Redirect($rec->url);
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
}
