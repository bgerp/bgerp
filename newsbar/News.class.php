<?php 


/**
 * Лента с топ новини
 *
 *
 * @category  bgerp
 * @package   newsbar
 * @author    Gabriela Petrova <gpetrova@experta.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class newsbar_News extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Новини";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Новина";

    
    /**
     * Разглеждане на листов изглед
     */
    var $canSingle = 'cms, newsbar, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'newsbar_Wrapper, plg_Created, plg_State2, plg_RowTools';
    
    
   
    /**
     * Полета за листовия изглед
     */
    var $listFields = '✍,news,startTime,endTime,state';


    /**
     * Поле за инструментите на реда
     */
    var $rowToolsField = '✍';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms, newsbar, admin, ceo';
        
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cms, newsbar, admin, ceo';

    
    /**
     * Описание на модела
     */
    function description()
    {
		$this->FLD('news', 'richtext(rows=2)', 'caption=Новина,mandatory');
		$this->FLD('startTime', 'datetime', 'caption=Показване на новината->Начало, mandatory');
		$this->FLD('endTime', 'datetime', 'caption=Показване на новината->Край,mandatory');
		
    }
    
    
    /**
     * Създаване на лентата за новини
     */
    static function getTopNews()
    {
    	// Правим заявка към базата
    	$query = static::getQuery();
    	$query->orderBy('startTime'); 
    	$nowTime = dt::now();
		$topNews = $query->fetchAll("#state = 'active' AND #startTime <= '{$nowTime}' AND  #endTime >= '{$nowTime}'");

        if(!count($topNews)) return;
       
		foreach($topNews as $news){
		
			$link .= $news->news . " | ";

		}
		
		$newLink = substr($link, 0, strlen($link)-2);
       
		// Връщаме стринг от всички новини
		return $newLink;
    }

    
 	/**
     * Пренасочва URL за връщане след запис към лист изгледа
     */
    function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако е субмитната формата 
        if ($data->form && $data->form->isSubmitted()) {

            // Променяма да сочи към list'a
            $data->retUrl = toUrl(array($mvc, 'list'));
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
    	if ($form->isSubmitted()) {
	    	if (empty($form->rec->news)) {
	    		
	            // Сетваме грешката
	            $form->setError('news', 'Непопълнен текст за новина');
	        }
	        
	        if(empty($form->rec->startTime)){
	        	
	        	// Сетваме грешката
	            $form->setError('startTime', 'Непопълнено начално време за видимост на новината');
	        }
	        
    		if(empty($form->rec->endTime)){
	        	
	        	// Сетваме грешката
	            $form->setError('endTime', 'Непопълнено крайно време за видимост на новината');
	        }
    	}
    }
}