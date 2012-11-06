<?php

/**
 * Хранилка
 *
 *
 * @category  vendors
 * @package   feed
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class feed_Generator extends core_Manager {

	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Хранилка';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Modified';
	
	
	/**
	 * Поле за лентата с инструменти
	 */
	var $rowToolsField = 'tools';
	
	
	/**
	 * Полета за листов изглед 
	 */
	var $listFields = 'tools=Пулт, title, description, type, source, url, logo, lg, maxItems, createdOn, createdBy, modifiedOn, modifiedBy';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar(50)', 'caption=Наименование, mandatory, notNull');
		$this->FLD('description', 'varchar(100)', 'caption=Oписание, mandatory, notNull');
		$this->FLD('logo', 'fileman_FileType(bucket=feedImages)', 'caption=Лого, mandatory, notNull');
		$this->FLD('source', 'class(interface=feed_SourceIntf)', 'caption=Източник, mandatory, notNull');
		$this->FLD('url', 'url', 'caption=Адрес, mandatory, notNull');
		$this->FLD('type', 'enum(rss=RSS,rss2=RSS 2.0,atom=ATOM)', 'caption=Тип, mandatory, notNull');
		$this->FLD('lg', 'varchar(2)', 'caption=Език, mandatory, notNull');
		$this->FLD('maxItems', 'int', 'caption=Максимално, mandatory, notNull');
	}
	
	
	/**
	 *  Създаваме нова кофа за логото
	 */
	static function on_AfterSetupMvc($mvc, &$res)
    {
        // Кофа за логото
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('feedImages', 'Лого на хранилката', 'jpg,jpeg,png,bmp,gif,image/*', '3MB', 'user', 'every_one');
    }
    
    
    /**
     *  Генерира хранилка 
     */
	function act_Get()
	{
		// Извличаме записа на хранилката
		expect($id = Request::get('id'));
		expect($rec = $this->fetch($id));
		
		// Инстанцираме източника
		$source = cls::get($rec->source);
		
		// Генерираме масив от елементи за хранилката
		$items = $source->getItems($rec->maxItems, $rec->lg);
		
		// Вкарваме компонента FeedWriter
		$path = "feed/feedWriter/FeedTypes.php";
        require_once getFullPath($path);
		
        // Взависимост от посоченият вид, инстанцираме определения клас хранилка
        switch ($rec->type) {
        	case 'rss' : 
        		
        		 // Инстанцираме нова хранилка от тип RSS 1
        		 $feed = new RSS1FeedWriter();
				 $feed->setDescription($rec->description);
				 $feed->setChannelAbout('http://bgerp.com/blogm_Articles/');
				 break;
        	case 'rss2' : 
        		
        		 // Инстанцираме нова хранилка от тип RSS 2.0
        		 $feed = new RSS2FeedWriter();
  				 $feed->setDescription($rec->description);
				 $feed->setChannelElement('language', $rec->lg);
  				 $feed->setChannelElement('pubDate', date(DATE_RSS, time()));
  				 $feed->setImage('feed', null, fileman_Download::getDownloadUrl($rec->logo));
  				 break;
        	case 'atom' : 
        		
        		// Инстанцираме нова хранилка от тип ATOM
        		$feed = new ATOMFeedWriter();
        		$feed->setChannelElement('updated', date(DATE_ATOM, time()));
				$feed->setChannelElement('author', array('name'=>'bgerp'));
        		break;
        }
        
        // Заглавие и адрес на хранилката
		$feed->setTitle($rec->title);
		$feed->setLink($rec->url);
        
        // Попълваме хранилката от източника
		foreach($items as $item) {
			
        	$newFeed = $feed->createNewItem();
		    $newFeed->setTitle($item->title);
		    $newFeed->setlink($item->link);
		    $newFeed->setDate($item->date);
		    $newFeed->setDescription($item->description);
		    
		    // Добавяме новия елемент на хранилката
		    $feed->addItem($newFeed);
        }
        
        // Генерираме хранилката
		$feed->generateFeed();
		
		//@TODO външен изглед на фийдовете
		//@TODO  да добавя хедъри на рсс-те в cms_Page
	}
}