<?php

/**
 * Хранилка
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Feeds extends core_Manager {

	
	/**
	 * Заглавие на страницата
	 */
	public $title = 'Хранилки';
	
	
	/**
	 * Заглавие в единично число
	 */
	public $singleTitle = "Хранилка";
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	public $loadList = 'plg_RowTools2, plg_Created, plg_Modified, cms_Wrapper';
	

    /**
     * Да не се кодират id-тата
     */
    public $protectId = FALSE;
	
    
	/**
	 * Поле за лентата с инструменти
	 */
	public $rowToolsField = 'tools';
	
	
	/**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'feed_Generator';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,admin,cms';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,admin,cms';
    
    
	/**
	 * Полета за листов изглед 
	 */
	public $listFields = 'title, description, type, source, logo, maxItems';
	
	
	/**
	 * Описание на модела
	 */
	public function description()
	{
		$this->FLD('source', 'class(interface=cms_FeedsSourceIntf,allowEmpty,select=title)', 'caption=Източник, mandatory,silent');
		$this->FLD('title', 'varchar(50)', 'caption=Наименование, mandatory');
		$this->FLD('description', 'text', 'caption=Oписание, mandatory');
		$this->FLD('logo', 'fileman_FileType(bucket=feedImages)', 'caption=Лого');
		$this->FLD('type', 'enum(rss=RSS,rss2=RSS 2.0,atom=ATOM)', 'caption=Тип, notNull, mandatory');
		$this->FLD('domainId',    'key(mvc=cms_Domains, select=*)', 'caption=Домейн,notNull,defValue=bg,mandatory,autoFilter');
		$this->FLD('maxItems', 'int', 'caption=Максимално, mandatory, notNull');
		$this->FLD('data', 'blob(serialize,compress)', 'caption=Информация за продукта,input=none');
		
		// Определяме уникален индекс
		$this->setDbUnique('title, type');
	}
	
	
	/**
	 *  Създаваме нова кофа за логото
	 */
	public static function on_AfterSetupMvc($mvc, &$res)
    {
        // Кофа за логото
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('feedImages', 'Лого на хранилката', 'jpg,jpeg,png,bmp,gif,image/*', '3MB', 'user', 'every_one');
    }
    
    
    /**
     *  Генерира хранилка 
     */
	public function act_Get()
	{
		// Извличаме записа на хранилката
		expect($id = Request::get('id', 'int'));
		expect($rec = $this->fetch($id));
		
		// Инстанцираме източника
		expect($source = cls::get($rec->source));
		
		// Генерираме масив от елементи за хранилката
		$items = $source->getItems($rec->maxItems, $rec->domainId, $rec->data);
		
		// Вкарваме компонента FeedWriter
		$path = "cms/feedWriter/FeedTypes.php";
        require_once getFullPath($path);
		
        // Взависимост от посоченият вид, инстанцираме определения клас хранилка
        switch ($rec->type) {
        	case 'rss' : 
        		 // Инстанцираме нова хранилка от тип RSS 1
        		 $feed = new RSS1FeedWriter();
				 $feed->setChannelAbout(toUrl(array($this, 'get', $rec->id), 'absolute'));
				 break;

        	case 'rss2' : 
        		 // Инстанцираме нова хранилка от тип RSS 2.0
        		 $feed = new RSS2FeedWriter();
                 $lang = cms_Domains::fetch($rec->domainId)->lang;
  				 $feed->setChannelElement('language', $lang);
  				 $feed->setChannelElement('pubDate', date(DATE_RSS, time()));
  				 if($rec->logo){
  				 	$img = new thumb_Img(array($rec->logo, 120, 120, 'fileman', 'isAbsolute' => TRUE));
  				 	
  				 	$feed->setImage($rec->title, toUrl(array($this, 'get', $rec->id), 'absolute'), $img->getUrl());
  				 }
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
		$feed->setLink(toUrl(array('blogm_Articles'), 'absolute'));
        $feed->setDescription($rec->description);
        
        // Попълваме хранилката от източника
		foreach($items as $item) {
			
        	$newFeed = $feed->createNewItem();
		    $newFeed->setTitle($item->title);
		    $newFeed->setlink($item->link);
		    if($rec->type == 'rss2'){
		    	$newFeed->setGuid($item->link);
		    }
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
	 * Връща датата на последния елемент във фийда
	 */
	private function getPubDate($items)
	{
		if($items){
			foreach ($items as $i => $item){
				$dates[] = $item->date;
			}
			
			rsort($dates);
		
			return reset($dates);
		}
	}
	
	
	/**
	 *  Екшън за показване на всички Хранилки за външен достъп
	 */
	public function act_Feeds()
	{
        cms_Content::setCurrent();

		$data = new stdClass();
		$data->action = 'feeds';
		$data->query = $this->getQuery();
		
		// Подготвяме данните за RSS хранилките
		$this->prepareFeeds($data);
		
		// Рендираме списък на RSS хранилките
		$layout = $this->renderFeeds($data);
		
		// Поставяме обвивката за външен достъп
		Mode::set('wrapper', 'cms_page_External');
		
		return $layout;
	}
	
	
	/**
	 * Подготвяме хранилката
	 */
	public function prepareFeeds($data)
	{
		$fields = $this->selectFields("");
		$fields['-feeds'] = TRUE;
		$tableName = static::instance()->dbTableName;
		
		// Проверка дали съществува таблица на модела
		if(static::instance()->db->tableExists($tableName)) {
			
			// Попълваме вътрешните и вербалните записи
			while($rec = $data->query->fetch(array("#domainId = '[#1#]'", cms_Domains::getPublicDomain('id')))) {
				$data->recs[$rec->id] = $rec;
				$data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
			}
		} else {
			$msg = new stdClass();
			$msg->title = tr('Има проблем при генерирането на емисиите');
			$data->rows[] = $msg;
		} 
	}
	
	
	/**
	 * Рендираме списъка от хранилки за външен изглед
	 * @return core_ET
	 */
	public function renderFeeds($data)
	{
		$layout = getTplFromFile('cms/tpl/Feeds.shtml');
		
		// Поставяме иконка и заглавие
		$layout->append(tr('Нашите емисии'), 'HEADER');
 
		if(count($data->rows) > 0) {
			foreach($data->rows as $row) {
				$feedTpl = $layout->getBlock('ROW');
				$feedTpl->placeObject($row);
				$feedTpl->removeBlocks();
				$feedTpl->append2master();
			}
		}
		
		return $layout;
	}
	
	
	/**
	 * Модификация по вербалните записи
	 */
	public static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
	{
		// Подготвяме адреса на хранилката
		$rssLink = array($mvc, 'get', $rec->id);
        $typeUrl = cls::get('type_Url');
		$row->url = $typeUrl->toVerbal(toUrl($rssLink, 'absolute'));
		
		// Преобразуваме логото на фийда да е  img
		$icon = 'cms/img/' . $rec->type . '.png';
		$row->title = ht::createLink($row->title, $rssLink, NULL, "ef_icon={$icon}");
	}
	
	
	/**
	 * Генерира хедърите за обвивката
	 * @return core_ET
	 */
	public static function generateHeaders()
	{
		// Шаблона който ще се връща
		$tpl = new ET('');
        
        $tableName = static::instance()->dbTableName;
        if(static::instance()->db->tableExists($tableName)) {
            $feedQuery = static::getQuery();
	        while($feed = $feedQuery->fetch("#domainId = " . cms_domains::getPublicDomain('id'))) {
	       		
	       		// Адрес на хранилката
	       		$url = toUrl(array('cms_Feeds', 'get', $feed->id), 'absolute');
	       		
	       		// Взависимост от типа на хранилката определяме типа на хедъра
	       		if($feed->type != 'atom') {
	       			$type = 'application/rss+xml';
	       		} else {
	       			$type = 'application/atom+xml';
	       		}
	       		
       			// Натрупваме генерираният хедър в шаблона, ако хранилката е от същия език, като на външната част
       			$tpl->append("\n<link rel='alternate' type='{$type}' title='{$feed->title}' href='{$url}'>");
	       	}
        }
		
       	return $tpl;
	}
	
	
	/**
	 * Генерира икона с линк за екшъна с хранилките
	 * @return core_ET
	 */
	public static function generateFeedLink()
	{
		// Шаблон в който ще се добави линка
		$tpl = new ET('');
		
		$query = static::getQuery();
        $domainId = cms_Domains::getPublicDomain('id');
		$feeds = $query->fetchAll("#domainId = $domainId");
		if(!count($feeds)) return NULL;
		
		// Подготвяме иконка с линк към публичния лист на хранилката
		$url = array('cms_Feeds', 'feeds');

		if(log_Browsers::isRetina()) {
			$size = 48;
		} else {
			$size = 24;
		}

        $src = sbf("cms/img/{$size}/rss.png", "");

        $img = ht::createElement('img', array('src' => $src, 'alt' => 'RSS Feeds', 'width' => 24, 'height' =>24));

		$link = ht::createLink($img, $url, NULL, array('class' => 'soc-following noSelect'));
		
		// Добавяме линка към шаблона
		$tpl->append($link);
		
		// Връщаме шаблона
		return $tpl;
	}
	
	
	/**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$form = &$data->form;
    	
    	$form->setField('source', array('removeAndRefreshForm' => "title|description|logo|maxItems|data"));
    	
	    if($form->rec->source){
	    	$Source = cls::get($form->rec->source);
	    	if($Source->feedFilterField){
	    		$sourceField = $Source->fields[$Source->feedFilterField];
	    		$form->FNC($Source->feedFilterField, $sourceField->type, "input,fromSource,caption={$sourceField->caption},after=type");
	    			
		    	if($form->rec->data){
		    		$form->setDefault($Source->feedFilterField, $form->rec->data);
		    	}
	    	}
	    }
    }
    
    
    /**
     * След инпут на формата
     */
	public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$fld = $form->selectFields('#fromSource');
    		if(!count($fld)) return;
    		$fld = reset($fld);
    		
    		$form->rec->data = $form->rec->{$fld->name};
    	}
    }
}