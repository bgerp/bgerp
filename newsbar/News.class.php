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
    var $loadList = 'newsbar_Wrapper, plg_Created, plg_State2, plg_RowTools2, newsbar_Plugin';
    
    
   
    /**
     * Полета за листовия изглед
     */
    var $listFields = 'news,startTime,endTime,lang,color,transparency,state';


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
		$this->FLD('news', 'richtext(rows=2,bucket=Notes)', 'caption=Новина,mandatory');
		$this->FLD('startTime', 'datetime(format=smartTime)', 'caption=Показване на новината->Начало, mandatory');
		$this->FLD('endTime', 'datetime(defaultTime=23:59:59,format=smartTime)', 'caption=Показване на новината->Край,mandatory');
        $this->FLD('domainId',    'key(mvc=cms_Domains, select=*)', 'caption=Домейн,notNull,defValue=bg,mandatory,autoFilter');
		$this->FLD('color', 'color_Type', 'caption=Фон->Цвят,unit=rgb');
		$this->FLD('transparency', 'percent(min=0,max=1,decimals=0)', 'caption=Фон->Непрозрачност');
    }
    


    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $domainId = cms_Domains::getCurrent();
        $data->query->where("#domainId = {$domainId}");
        $data->query->orderBy('#createdOn', 'DESC');
    }

    
    /**
     * Създаване на лентата за новини
     */
    static function getTopNews()
    {
    	// Правим заявка към базата
    	$query = static::getQuery();
    	
    	$nowTime = dt::now();

        $query->groupBy('RAND()');
    	$query->limit(1);
        
        $domainId = cms_Domains::getPublicDomain('id');
    	$query->where("#state = 'active'");
    	$query->where("#startTime <= '{$nowTime}' AND  #endTime >= '{$nowTime}'");
		$query->where("#domainId = '{$domainId}'");
		
        $news = $query->fetch();
        		       
		// Връщаме стринг от всички новини
		return (object) array('news' => $news->news, 'color' => $news->color, 'transparency'=> $news->transparency);
    }
    
    
    /**
     * Превръщане на цвят от 16-тичен към RGB
     */
	static function hex2rgb($hex) {
	   $hex = str_replace("#", "", $hex);
	
	   if(strlen($hex) == 3) {
	      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
	      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
	      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
	   } else {
	      $r = hexdec(substr($hex,0,2));
	      $g = hexdec(substr($hex,2,2));
	      $b = hexdec(substr($hex,4,2));
	   }
	   $rgb = array($r, $g, $b);
	   
	   return $rgb; 
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
        
        $form->rec->domainId = cms_Domains::getCurrent();
        $form->setReadOnly('domainId');
            

        $progressArr[''] = '';

        for($i = 0; $i <= 100; $i += 10) {
            if($rec->transparency > ($i/100)) continue;
            $p = $i . ' %';
            $progressArr[$p] = $p;
        }
        $form->setSuggestions('transparency', $progressArr);
        
        if (!$rec->color) {
        	$form->setDefault('color', '#000000');
        }
       
        if (!$rec->transparency) { 
        	$form->setDefault('transparency', 0.5);
        }
    }
    
    
        

    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {

    	$row->news = self::generateHTML($rec);
    }
    
    
    /**
     * Генериране на HTML код за лентата на нюзбара
     * ще я използваме както вътре, така и вънка
     *  
     * @param stdClass $rec
     * @return ET
     */
    public static function generateHTML ($rec)
    {
    	$rgb = static::hex2rgb($rec->color);
    	$hexTransparency = dechex($rec->transparency * 255);
    	$forIE = "#". $hexTransparency. str_replace("#", "", $rec->color);

        $rt = cls::get('type_Richtext');
    	
    	$html =  new ET ("<div class=\"[#class#]\" style=\"background-color: rgb([#r#], [#g#], [#b#]); 
            										   background-color: rgba([#r#], [#g#], [#b#], [#transparency#]);
           											   background:transparent\0; 
                          filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=[#ie#], endColorstr=[#ie#]);
                          -ms-filter: 'progid:DXImageTransform.Microsoft.gradient(startColorstr=[#ie#], endColorstr=[#ie#])';
                          zoom: 1;\">
            [#marquee#]<b>[#1#]</b>[#marquee2#]
            </div><div class='clearfix21'></div>", $rt->toHtml('[color=white]' . $rec->news . '[/color]'));
    	
    	$html->replace($rgb[0], 'r');
        $html->replace($rgb[1], 'g');
        $html->replace($rgb[2], 'b');
        $html->replace($rec->transparency, 'transparency');
        $html->replace($forIE, 'ie');
    
        return $html;
    }
}