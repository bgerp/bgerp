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
     * Полета за листовия изглед
     */
    var $listFields = '✍,title,url,icon,followersCnt,state';


    /**
     * Поле за инструментите на реда
     */
    var $rowToolsField = '✍';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, social';
            
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo, social';

    /**
     * Кои може да гледа сингъла
     */
    var $canSingle = 'no_one';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('title', 'varchar(32)', 'caption=Услуга');
		$this->FLD('url', 'varchar(128)', 'caption=URL, hint=URL на вашата страница');
		$this->FLD('icon', 'fileman_FileType(bucket=social)', 'caption=Икона');
		$this->FLD('followersCnt', 'int', 'caption=Последователи, input=none');
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
				$name = social_Sharings::getServiceNameByUrl($socUrl);
					
				// Намираме иконата в sbf папката
				$icon = sbf("cms/img/24/{$name}.png",'');
			}
				
			// Създаваме иконата за бутона
			$img = ht::createElement('img', array('src' => $icon));
				
			// Генерираме URL-то на бутона
			$url = array('social_Followers', 'Redirect', $socialNetwork->id);
				
			// Създаваме линка на бутона
			$link = ht::createLink("{$img}" . $socialNetwork->sharedCnt, $url, NULL, array("class" => "soc-following", "target"=>"_blank", "title" => tr('Последвайте ни в '). $socialNetwork->title));
        
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
    	// Взимаме $ид-то на услугата
    	$id = core_Request::get('id', 'key(mvc='.get_class($mvc).')');
    	
    	// Намираме нейния запис
    	$rec = self::fetch("#id = '{$id}'"); 
    	    	    	
    	// Увеличаване на брояча на споделянията
    	$rec->followersCnt += 1;
    	
    	
    	// Записваме в историята, че сме направели споделяне
    	if($rec) {
            if(core_Packs::fetch("#name = 'vislog'")) {
               vislog_History::add("Последване в " . $rec->title);
            }
        }
    	self::save($rec, 'followersCnt');
    	
    	// Връщаме URL-то
    	return new Redirect ($rec->url);
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
	    	if (empty($form->rec->title)) {
	    		
	            // Сетваме грешката
	            $form->setError('title', 'Непопълнено име на социалната мрежа');
	        }
	        
	        if(empty($form->rec->url)){
	        	
	        	// Сетваме грешката
	            $form->setError('url', 'Непопълнено URL за споделяне');
	        }
    	}
    }
    
    
    /**
     * Преди да вкараме записа в базата
     */
    static function on_BeforeSave($mvc,$res,$rec)
    {
    	$rec->followersCnt = 0;
    }
}
