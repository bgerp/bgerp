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
	var $loadList = 'plg_RowTools, plg_Created, plg_Modified, feed_Wrapper';
	
	
	/**
	 * Поле за лентата с инструменти
	 */
	var $rowToolsField = 'tools';
	
	
	/**
	 * Полета за листов изглед 
	 */
	var $listFields = 'tools=Пулт, title, description, type, url, source, logo, lg, maxItems, createdOn, createdBy, modifiedOn, modifiedBy';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar(50)', 'caption=Наименование, mandatory');
		$this->FLD('description', 'text', 'caption=Oписание, mandatory');
		$this->FLD('logo', 'fileman_FileType(bucket=feedImages)', 'caption=Лого');
		$this->FLD('source', 'class(interface=feed_SourceIntf)', 'caption=Източник, mandatory');
		$this->FLD('type', 'enum(rss=RSS,rss2=RSS 2.0,atom=ATOM)', 'caption=Тип, notNull, mandatory');
		$this->FLD('lg', 'enum(bg=Български,en=Английски)', 'caption=Език, notNull, value=bg');
		$this->FLD('maxItems', 'int', 'caption=Максимално, mandatory, notNull');
	
		// Определяме уникален индекс
		$this->setDbUnique('title,type');
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
				 $feed->setChannelAbout('http://bgerp.com/blogm_Articles/');
				 break;
        	case 'rss2' : 
        		
        		 // Инстанцираме нова хранилка от тип RSS 2.0
        		 $feed = new RSS2FeedWriter();
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
        
        // Заглавие, Адрес и Описание на хранилката
		$feed->setTitle($rec->title);
		$feed->setLink(toUrl(array($this, 'get', $rec->id), 'absolute'));
        $feed->setDescription($rec->description);
        
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
		
        shutdown();
	}
	
	
	/**
	 *  Екшън за показване на всички Хранилки за външен достъп
	 */
	function act_Feeds()
	{
		$data = new stdClass();
		$data->action = 'feeds';
		$data->query = $this->getQuery();
		
		// Подготвяме хранилките
		$this->prepareFeeds($data);
		
		// Рендираме екшъна
		$layout = $this->renderFeeds($data);
		
		// Поставяме обвивката за външен достъп
		Mode::set('wrapper', 'cms_tpl_Page');
		
		return $layout;
	}
	
	
	/**
	 * Подготвяме хранилката
	 */
	function prepareFeeds($data)
	{
		$fields = $this->selectFields("");
		$fields['-feeds'] = TRUE;
		
		// Попълваме вътрешните и вербалните записи
		while($rec = $data->query->fetch()) {
			$data->recs[$rec->id] = $rec;
			$data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
		}
	}
	
	
	/**
	 * Рендираме списъка от хранилки за външен изглед
	 * @return core_ET
	 */
	function renderFeeds($data)
	{
		$layout = new ET(getFileContent('feed/tpl/Feeds.shtml'));
		
		// Поставяме иконка и заглавие
		$layout->append(tr('Нашите емисии'), 'HEADER');
		$icon = ht::createElement('img', array('src' => sbf("feed/img/rss_icon_glass32.PNG", ""), 'style' => 'float:left;;'));
		$layout->append($icon, 'ICON');
		
		foreach($data->rows as $row) {
			$feedTpl = $layout->getBlock('ROW');
			$feedTpl->placeObject($row);
			$feedTpl->removeBlocks();
			$feedTpl->append2master();
		}
		
		return $layout;
	}
	
	
	/**
	 * Модификация по вербалните записи
	 */
	static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
	{
		// Подготвяме адреса на хранилката
		$rssLink = array($this, 'get', $rec->id);
        $typeUrl = cls::get('type_Url');
		$row->url = $typeUrl->toVerbal(toUrl($rssLink, 'absolute'));
		
		if($fields['-feeds']) {
			// Преобразуваме логото на фийда да е  img
			$imgUrl = sbf('feed/img/' . $rec->type . '.png', '\'');
			
			$row->title = ht::createLink($row->title, $rssLink, NULL, 
                array('class' => 'linkWithIcon', 'style' => "padding-left:45px !important; background-image:url({$imgUrl})"));
			
		}
	}
	
	
	/**
	 * Генерира хедърите за обвивката
	 * @return core_ET
	 */
	static function generateHeaders()
	{
		// Шаблона който ще се връща
		$tpl = new ET('');
		
		// Заявка за работа с модела 
        $feedQuery = static::getQuery();
        
        while($feed = $feedQuery->fetch()) {
       		
       		// Адрес на хранилката
       		$url = toUrl(array('feed_Generator', 'get', $feed->id), 'absolute');
       		
       		// Взависимост от типа на хранилката определяме типа на хедъра
       		if($feed->type != 'atom') {
       			$type = 'application/rss+xml';
       		} else {
       			$type = 'application/atom+xml';
       		}
       		
       		// Натрупваме генерираният хедър в шаблона
       		$tpl->append("\n<link rel='alternate' type='{$type}' title='{$feed->title}' href='{$url}' />");
       	}

       	return $tpl;
	}
	
	
	/**
	 * Генерира икона с линк за екшъна с хранилките
	 * @return core_ET
	 */
	static function generateFeedLink()
	{
		// Шаблон в който ще се добави линка
		$tpl = new ET('');
		
		// Подготвяме иконка с линк към публичния лист на хранилката
		$url = array('feed_Generator', 'feeds');
		
        if( Mode::is('screenMode', 'narrow')) {
            $src = sbf("feed/img/rss_icon_glass_gray24.PNG", "");
        } else {
            $src = sbf("feed/img/rss_icon_glass_gray32.PNG", "");
        }
        
        $img = ht::createElement('img', array('src' => $src, 'style' => 'margin:0px;padding:0px;'));

		$link = ht::createLink($img, $url, NULL, array('style' => 'margin:0px;padding:0px;margin-left:7px;float:left;'));
		
		// Добавяме линка към шаблона
		$tpl->append($link);
		
		// Връщаме шаблона
		return $tpl;
	}
}