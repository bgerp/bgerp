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
    var $loadList = 'newsbar_Wrapper, plg_Created, plg_State2, plg_RowTools, newsbar_Plugin';
    
    
   
    /**
     * Полета за листовия изглед
     */
    var $listFields = '✍,news,startTime,endTime,lang,color,transparency,state';


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
		$this->FLD('lang', 'varchar(4), allowEmpty=true', 'caption=Показване на новината->Език,mandatory');
		$this->FLD('color', 'color_Type', 'caption=Фон->Цвят,mandatory,unit=rgb');
		$this->FLD('transparency', 'percent(min=0,max=1,decimals=0)', 'caption=Фон->Прозрачност,mandatory');
		
    }
    
    
    /**
     * Създаване на лентата за новини
     */
    static function getTopNews()
    {
    	// Правим заявка към базата
    	$query = static::getQuery();
    	
    	
    	$nowTime = dt::now();
    	$lg = cms_Content::getLang();

        $query->groupBy('RAND()');
    	$query->limit(1);

    	$query->where("#state = 'active'");
    	$query->where("#startTime <= '{$nowTime}' AND  #endTime >= '{$nowTime}'");
		$query->where("#lang = 'none' OR #lang = '{$lg}'");
		
        $news = $query->fetch();

		//$newLink = substr($news->news, 0, strlen($link)-2);
		       
		// Връщаме стринг от всички новини
		return (object) array('news' => $news->news, 'color' => $news->color, 'transparency'=> $news->transparency);
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
    
	
	/**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec  = &$form->rec;
        
        $arr = array ('none' => "");
        $form->setOptions('lang', $arr+cms_Content::getLangsArr());

        $progressArr[''] = '';

        for($i = 0; $i <= 100; $i += 10) {
            if($rec->transparency > ($i/100)) continue;
            $p = $i . ' %';
            $progressArr[$p] = $p;
        }
        $form->setSuggestions('transparency', $progressArr);
       
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    static function on_AfterPrepareListRows($mvc, &$res)
    {
        $rows = &$res->rows;
        $recs = &$res->recs;
        
        foreach ($recs as $id => $rec) {
        	if ($rec->lang == 'none') { 
        		$rows[$id]->lang = " ";
        	}
        }
    }

}