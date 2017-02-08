<?php

/**
 * Статии
 *
 *
 * @category  bgerp
 * @package   blogm
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class blogm_Articles extends core_Master {
	
	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Блог статии';
	
	
	/**
	 * Тип на разрешените файлове за качване
	 */
	const FILE_BUCKET = 'blogmFiles';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools2, plg_State, plg_Printing, blogm_Wrapper, 
        plg_Search, plg_Created, plg_Modified, cms_VerbalIdPlg, plg_Rejected';
	

    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'cms_SourceIntf, cms_FeedsSourceIntf';


	/**
	 * Полета за листов изглед
	 */
	var $listFields ='id, title, categories, author, createdOn=Създаване||Created->На, createdBy=Създаване||Created->От||By, modifiedOn=Модифицирано||Modified->На, modifiedBy=Модифицирано||Modified->От||By';
	
        
	
	/**
	 * Коментари на статията
	 */
	var $details = 'blogm_Comments';
	
	
	/** 
	 *  Полета по които ще се търси
	 */
	var $searchFields = 'title, author, body';
	
	
	/**
	 * Кой може да листва статии и да чете  статия
	 */
	var $canRead = 'cms, ceo, admin, blog';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, admin, cms, blog';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo, admin, cms, blog';
	
	
	/**
	 * Кой може да добявя,редактира или изтрива статия
	 */
	var $canWrite = 'cms, ceo, admin, blog';
	
	/**
	 * Кой може да вижда публичните статии
	 */
	var $canArticle = 'every_one';
	
	
	/**
	 * Единично заглавие на документа
	 */
	var $singleTitle = 'Статия';
	
	
	/**
	 * Поле за филтриране от фийдовете
	 */
	var $feedFilterField = 'categories';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('author', 'varchar(40)', 'caption=Автор, mandatory, notNull');
        $this->FLD('publishedOn', 'datetime', 'caption=Публикуване');
		$this->FLD('title', 'varchar(190)', 'caption=Заглавие, mandatory');
		$this->FLD('categories', 'keylist(mvc=blogm_Categories,select=title)', 'caption=Категории,mandatory');
		$this->FLD('body', 'richtext(bucket=' . self::FILE_BUCKET . ')', 'caption=Съдържание,mandatory');
 		$this->FLD('commentsMode', 
            'enum(enabled=Разрешени,confirmation=С потвърждение,disabled=Забранени,stopped=Спрени)',
            'caption=Коментари->Режим,mandatory,maxRadio=' . (Mode::is('screenMode', 'narrow') ? 2 : 4));
        $this->FLD('commentsCnt', 'int', 'caption=Коментари->Брой,value=0,notNul,input=none');
  		$this->FLD('state', 'enum(draft=Чернова,pending=Чакаща,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,mandatory');
  		
		$this->setDbUnique('title');
	}


    /**
     * Екшъна по подразбиране е разглеждане на статиите
     */
    function act_Default()
    {
        return $this->act_Browse();
    }
	
	
	/**
	 * Обработка на вербалното представяне на статиите
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{ 
        $rec->body = trim($rec->body);

        if($fields['-browse']) { 
            $txt = explode("\n", $rec->body, 2);
            if(count($txt) > 1) {
                $rec->body = trim($txt[0]); 
                $rec->body .=   " [link=" . toUrl(self::getUrl($rec), 'absolute') . "][" . tr('още') . "][/link]";
            }

            $row->body = $mvc->getVerbal($rec, 'body');
        }

        if($q = Request::get('q')) {
            $row->body = plg_Search::highlight($row->body, $q, 'searchContent');
        }

        if($fields['-browse'] || $fields['-article']) {
            if($row->commentsCnt == 1) {
                $row->commentsCnt .= '&nbsp;' . tr('коментар');
            } else {
                $row->commentsCnt .= '&nbsp;' . tr('коментара');
            }
        }

        if(!$rec->publishedOn) {
            $rec->publishedOn = $rec->createdOn;
        }
 
        $row->publishedOn = dt::mysql2verbal($rec->publishedOn, 'smartTime');  
 

        if($fields['-list']) { 
            $row->title = ht::createLink($row->title, self::getUrl($rec), NULL, 'ef_icon=img/16/monitor.png');
        }
	}


    /**
     * Изпълнява се преди всеки запис
     */
    static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL)
    {
        if(!$fields) {
            if($rec->state == 'active') {
                if(!$rec->publishedOn) {
                    $rec->publishedOn = dt::verbal2mysql();
                } elseif ($rec->publishedOn > dt::verbal2mysql()) {
                    $rec->state = 'pending';
                    core_Statuses::newStatus('|Статията ще бъде публикувана след|*' . ' ' . dt::mysql2verbal($rec->publishedOn), 'warning');
                }
            }
        }
    }


    /**
     * След обновяването на коментарите, обновяваме информацията в статията
     */
    protected static function on_AfterUpdateDetail(core_Master $mvc, $id, core_Manager $detailMvc)
    {  
        if($detailMvc->className == 'blogm_Comments') {
            $queryC = $detailMvc->getQuery();
            $queryC->where("#articleId = {$id} AND #state = 'active'");
            $rec = $mvc->fetch($id);
            if(is_object($rec)){
            	$rec->commentsCnt = $queryC->count();
            	$mvc->save($rec);
            }
        }
    }
	
	
	/**
	 * Обработка на заглавието
	 */
	function on_AfterPrepareListTitle($mvc, $data)
	{
		// Проверява имали избрана категория
		$category = Request::get('category', 'int');
		
		// Проверяваме имали избрана категория
		if(isset($category)) {
			
			// Ако е избрана се взима заглавието на категорията, което отговаря на посоченото id 
			if($catRec = blogm_Categories::fetch($category)) {
                $title = blogm_Categories::getVerbal($catRec, 'title');
                
                // В заглавието на list  изгледа се поставя името на избраната категория
                $data->title = 'Статии от категория: ' . $title;  
            }
		}
	}
	
	
	/**
	 * Подготовка на формата за добавяне/редактиране на статия 
	 */
	static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
		$form = $data->form;

        if(!$form->rec->id) {
            $form->setDefault('author', core_Users::getCurrent('nick'));
            $form->setDefault('commentsMode', 'confirmation');
        }
        
        $form->setSuggestions('categories', blogm_Categories::getCategoriesByDomain(cms_Domains::getCurrent()));
        
        // Ако сме в тесен режим
        if (Mode::is('screenMode', 'narrow')) {
        	// Да има само 2 колони
        	$data->form->setField('categories', array('maxColumns' => 2));
        	$data->form->setField('commentsMode', array('columns' => 2));
        }
 	}
	
	
	/**
	 *  Филтриране на статиите по ключови думи и категория
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{	
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->FNC('category', 'key(mvc=blogm_Categories,select=title,allowEmpty)', 'placeholder=Категория,silent,autoFilter');

        $data->listFilter->showFields = 'search,category';

        // Подреждаме статиите по датата им на публикуане в низходящ ред	
        $data->query->XPR('pubTime', 'datetime', "IF(#publishedOn,#publishedOn,#createdOn)");
		$data->query->orderBy('#pubTime', 'DESC');
		
        $categories = blogm_Categories::getCategoriesByDomain(cms_Domains::getCurrent());
 
        if(!count($categories)) {
            redirect(array('blogm_categories'), FALSE, "|Моля въведете категории за статиите в блога");
        }
        $data->listFilter->setOptions('category', $categories);
        
        // Активиране на филтъра
        $recFilter = $data->listFilter->input(NULL, 'silent');
        if(($cat = $recFilter->category) > 0) {
           $data->query->where("#categories LIKE '%|{$cat}|%'");
        } else {
		    $data->query->likeKeylist('categories', keylist::fromArray($categories));
        }
	
		// Ако метода е 'browse' показваме само активните статии
		if($data->action == 'browse'){
			
			// Показваме само статиите които са активни
			$data->query->where("#state = 'active'");
			
		}
     }


    /**
	 *  Екшън за публично преглеждане и коментиране на блог-статия
	 */
	function act_Article()
	{  
		// Имаме ли въобще права за Article екшън?			
		$this->requireRightFor('article');

		// Очакваме да има зададено "id" на статията
		$id = Request::get('id', 'int');

        if(!$id) {
            $id = Request::get('articleId', 'int');
        }
		
        if(!$id) {

            return $this->act_Browse();
        }

		// Създаваме празен $data обект
		$data = new stdClass();
		$data->query = $this->getQuery();
		$data->articleId = $id;

		// Трябва да има $rec за това $id
		$data->rec = $this->fetch($id);

        if(!$data->rec) {

            return $this->act_Browse();
        }


        // Определяме езика на статията от първата и категория
        $catArr = keylist::toArray($data->rec->categories);
        $firstCatId = key($catArr);
        
        $domainId = blogm_Categories::fetchField($firstCatId, 'domainId');
        if($domainId) {
            cms_Domains::setPublicDomain($domainId);
        }
		
        // Трябва да имаме права за да видим точно тази статия
		$this->requireRightFor('article', $data->rec);
 		
		// Подготвяме данните за единичния изглед
		$this->prepareArticle($data);
        
        // Обработка на формата за добавяне на коментари
        if($cForm = $data->commentForm) {
        
            // Зареждаме REQUEST данните във формата за коментар
            $cRec = $cForm->input();
            
            // Мениджърът на блог-коментарите
            $Comments = cls::get('blogm_Comments');

            // Генерираме събитие в $Comments, след въвеждането на формата
            $Comments->invoke('AfterInputEditForm', array($cForm));
            
            // Дали имаме права за това действие към този запис?
            $Comments->requireRightFor('add', $cRec, NULL);
            
            // Ако формата е успешно изпратена - запис, лог, редирект
            if ($cForm->isSubmitted()) {
                
                vislog_History::add('Нов коментар в блога');
                
                // Записваме данните
                if($id = $Comments->save($cRec)) {
                
                    // Правим запис в лога
                    $Comments->logWrite('Добавяне', $id);
                    
                    // Редиректваме към предварително установения адрес
                    return new Redirect(self::getUrl($data->rec), '|Благодарим за вашия коментар|*');
                } else {

                    // Връщане на СПАМ съобщение
                    return new Redirect(self::getUrl($data->rec), '|За съжаление не успяхме да запишем коментара ви|*');
                }
                
            }
        }
      
        Mode::set('SOC_TITLE', $data->ogp->siteInfo['Title']);
        Mode::set('SOC_SUMMARY', $data->ogp->siteInfo['Description']);
               

		// Подготвяме лейаута за статията
        $layout = $this->getArticleLayout($data);
       
		// Рендираме статията във вид за публично разглеждане
		$tpl = $this->renderArticle($data, $layout);
        
        $rec = clone($data->rec);
        setIfNot($rec->seoTitle, $data->ogp->siteInfo['Title']);
        cms_Content::setSeo($tpl, $rec);


		// Генерираме и заместваме OGP информацията в шаблона
        $ogpHtml = ograph_Factory::generateOgraph($data->ogp);
        
        
                
        $tpl->append($ogpHtml);

		// Записваме, че потребителя е разглеждал тази статия
		$this->logRead('Разгледана статия', $id);
		
        if(core_Packs::fetch("#name = 'vislog'")) {
            vislog_History::add($data->row->title);
        }

        // Добавя канонично URL
        $url = self::getUrl($data->rec, TRUE);
        $url = toUrl($url, 'absolute');
        
        cms_Content::addCanonicalUrl($url, $tpl);
        
		return $tpl;
	}
	

    /**
     * Моделен метод за подготовка на данните за публично показване на една статия
     */
    function prepareArticle_(&$data)
    {
        $data->rec = $this->fetch($data->articleId);

        $fields = $this->selectFields("");
        
        $fields['-article'] = TRUE;

        $data->row = $this->recToVerbal($data->rec, $fields);

        blogm_Comments::prepareComments($data);
		
        $data->selectedCategories = keylist::toArray($data->rec->categories);
		
       	$this->prepareNavigation($data);

        if($this->haveRightFor('single', $data->rec)) {
            $data->workshop = array('blogm_Articles', 'edit', $data->rec->id);
        }
        
        // Подготвяме информацията за Статията за Open Graph Protocol
        $this->prepareOgraph($data);
    }
	
	
    /**
     * Създава OpenGraphProtocol  обект
     */
    function prepareOgraph($data)
    {
    	// Създаваме OGP Image обект
    	$conf = core_Packs::getConfig('cms');
        $data->ogp = new stdClass();
    	
    	// Добавяме изображението за ографа ако то е дефинирано от потребителя
        
        
        if($data->rec->body) {
            $pattern = cms_GalleryRichTextPlg::IMG_PATTERN;
        
            preg_match($pattern, $data->rec->body, $matches);
 
            if($iHnd = $matches[1]) {
                $iRec = cms_GalleryImages::fetch(array("#title = '[#1#]'", $iHnd));
                $fileSrc = $iRec->src;
            }
        }
        
        if(!$fileSrc) {
            $fileSrc = $conf->CMS_OGRAPH_IMAGE;
        }
        
        if($fileSrc) {
	        $file = fileman_Files::fetchByFh($fileSrc);
	        $type = fileman_Files::getExt($file->name);
	        
	        $img = new thumb_Img(array($file->fileHnd, 200, 200, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
	        $imageURL = $img->getUrl('forced');
	        
	    	$data->ogp->imageInfo = array('url'=> $imageURL,
	    						    	  'type'=> "image/{$type}",
	    						 		);
        }
        
    	if(!$data->rec){
    		
    		// Създаваме Ограф (Open Graph Protocol) Обект
	        $data->ogp->siteInfo = array('Locale' =>'bg_BG',
	    				  'SiteName' => $_SERVER['HTTP_HOST'],
	    	              'Title' => $_SERVER['HTTP_HOST'],
	    	              'Description' => $this->title,
	    	              'Type' =>'blog',
	    				  'Url' => toUrl(getCurrentUrl(), 'absolute'),
	    				  'Determiner' =>'the',);
    	} else {
    		$richText = cls::get('type_Richtext');
    		$desc = ht::extractText($richText->toHtml($data->rec->body));
    		
    		// Ако преглеждаме единична статия зареждаме и нейния Ograph
	        $data->ogp->siteInfo = array('Locale' =>'bg_BG',
	    				  'SiteName' => $_SERVER['HTTP_HOST'],
	    	              'Title' => $data->row->title,
	    	              'Description' => $desc,
	    	              'Type' =>'article',
	    				  'Url' => toUrl(getCurrentUrl(), 'absolute'),
	    				  'Determiner' =>'the',);
	        
	        // Създаваме Open Graph Article  обект
	    	$data->ogp->recInfo = array('published' => $data->rec->createdOn,
	    				  'modified' => $data->rec->modifiedOn,
	    				  'expiration' => '',);
    	}
    }
    
    
	/**
     * Рендиране на статия за публичната част на блога
	 */
	function renderArticle_($data, $layout)
	{ 
		// Поставяме данните от реда
		$layout->placeObject($data->row);
		$layout = blogm_Comments::renderComments($data, $layout);
        
        // Рендираме тулбара за споделяне
        $conf = core_Packs::getConfig('cms');
        $sharing = social_Sharings::getButtons(); 
        $layout->replace($sharing, 'SHARE_TOOLBAR');

        // Рендираме навигацията
        $layout->replace($this->renderNavigation($data), 'NAVIGATION');
        		
		return $layout;
	}


    /**
     * Връща лейаута на статия за публично разглеждане
     * Включва коментарите за статията и форма за добавяне на нов
     */
    function getArticleLayout($data)
    {
        return $data->ThemeClass->getArticleLayout();
    }


    /**
     * Добавяме бутон за преглед на статията в публичната част на сайта
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if ($mvc->haveRightFor('article', $data->rec)) {
            $data->toolbar->addBtn('Преглед', array(
                    $this,
                    'Article',
                    $data->rec->id,
                )
             );
        }
    }
	

	/**
	 *  Показваме списък със статии и навигация по категории
	 */
	function act_Browse()
    {
        // Създаваме празен $data обект
		$data = new stdClass();

        // Създаваме заявка към модела
		$data->query = $this->getQuery();
		
		
        // Въвеждаме ако има, категорията от заявката
        $data->category = Request::get('category', 'int');
		
        // Определяме езика от категорията
        if($data->category >0) {
            $domainId = blogm_Categories::fetchField($data->category, 'domainId');
            if($domainId) {
                cms_Domains::setPublicDomain($domainId);
            }
        }

		$categories = blogm_Categories::getCategoriesByDomain();
		$data->query->likeKeylist('categories', keylist::fromArray($categories));

        // По какво заглавие търсим
		$data->q = Request::get('q');

        // Архив
        $data->archive = Request::get('archive');

        if($data->archive) {
            list($data->archiveY, $data->archiveM) = explode('|', $data->archive);
            expect(is_numeric($data->archiveY) && is_numeric($data->archiveM));
            $data->archiveM = str_pad($data->archiveM, 2, '0', STR_PAD_LEFT);
        }
		
        $data->ThemeClass = $this->getThemeClass();
         
        // Подготвяме данните необходими за списъка със стаии
        $this->prepareBrowse($data);

        // Рендираме списъка
        $tpl = $this->renderBrowse($data);
     
        // Добавяме стиловете от темата
        $tpl->push($data->ThemeClass->getStyles(), 'CSS');

        // Генерираме мета таговете на OGP
        $ogpHtml = ograph_Factory::generateOgraph($data->ogp);
        $tpl->append($ogpHtml);
        
        $row = (object) array('seoTitle' => $data->title);
        cms_Content::setSeo($tpl, $row);

        if(core_Packs::fetch("#name = 'vislog'")) {
            vislog_History::add($data->title ? str_replace('&nbsp;', ' ', strip_tags($data->title)) : tr('БЛОГ'));
        }
        
		// Записваме, че потребителя е разглеждал този списък
		$this->logRead('Листване');
		
		return $tpl;
	}

	
    /**
     * Подготвяме данните за показването на списъка с блог-статии
     */
    function prepareBrowse($data)
    {   
        if($data->category) {
            $data->query->where(array("#categories LIKE '%|[#1#]|%'", $data->category));
            $data->selectedCategories[$data->category] = TRUE;
        } else {
            // Добавка, ако няма избрана категория, резултатите да се филтрират само по категориите, които са от текущия език
            $categories = blogm_Categories::getCategoriesByDomain();
            if(!is_array($categories) || !count($categories)) {
                $categories = array('99999999' => 'Няма категории на съответния език');
            }
            $data->query->likeKeylist('categories', keylist::fromArray($categories));
        }
        
        if($data->archive) {  
            $data->query->where("#createdOn LIKE '{$data->archiveY}-{$data->archiveM}-%'");
        }
     
        $data->query->XPR('pubTime', 'datetime', "IF(#publishedOn,#publishedOn,#createdOn)");
		$data->query->orderBy('#pubTime', 'DESC');
        
        // Показваме само публикуваните статии
        $data->query->where("#state = 'active'");
        
        $fields = $this->selectFields("");
        $fields['-browse'] = TRUE;
        
        $conf = core_Packs::getConfig('blogm');
        $data->pager = cls::get('core_Pager', array('itemsPerPage' => $conf->BLOGM_ARTICLES_PER_PAGE));
        $data->pager->setLimit($data->query);
		
        while($rec = $data->query->fetch()) {
            $data->recs[$rec->id] = $rec;

            $row = new stdClass();

            $row = self::recToVerbal($rec, $fields);
 
            $url = self::getUrl($rec);  
  
            $url['q'] = $data->q;
   
            $row->title = ht::createLink($row->title, $url);

            $txt = explode("\n", $rec->body, 2);

            if(count($txt) > 1) {
                $rec->body = trim($txt[0]); 
                $rec->body .=   " [link=" . toUrl(self::getUrl($rec), 'absolute') . "][" . tr('още') . "][/link]";
            }

            $row->body = $this->getVerbal($rec, 'body');

            $row->commentsCnt = $this->getVerbal($rec, 'commentsCnt');

            if($data->q) {
                $url += array('q' => $data->q);
            }
            $data->rows[$rec->id] = $row;
        }

        if($this->haveRightFor('list')) {
            $data->workshop = array('blogm_Articles', 'list');
        }

        // Определяне на титлата
		// Ако е посочено заглавие по-което се търси
        if(isset($data->q)) {
            
            $domainId = cms_Domains::getPublicDomain('id');
            $clsId = core_Classes::getId('blogm_Articles');

            $cRec = cms_Content::fetch("#domainId = {$domainId} AND #source = {$clsId}");

            $data->descr = cms_Content::renderSearchResults($cRec->id, $data->q);
            vislog_History::add("Търсене в блога: {$data->q}");

            $data->title = NULL;
            $data->rows = array();
    
		} elseif( isset($data->archive)) {  
   			$data->title = tr('Архив за месец') . '&nbsp;<b>' . dt::getMonth($data->archiveM, Mode::is('screenMode', 'narrow') ? 'M' : 'F') . ', ' . $data->archiveY . '&nbsp;</b>';
        } elseif(isset($data->category)) {
            $catRec = blogm_Categories::fetch($data->category);
            if(!$catRec) {
                error('404 Липсваща категория', array("Липсва категория:  {$data->category}"));
            }

   			$data->title = tr('Статии в') .  ' "<b>' . blogm_Categories::getVerbal($catRec, 'title') . '</b>"';
            $data->descr = blogm_Categories::getVerbal($catRec, 'description');
            if(!count($data->rows)) {
                $data->descr .= "<p><b style='color:#666;'>" . tr('Все още няма статии в тази категория') . '</b></p>';
            }
        } else {
            $data->title = cms_Content::getLang() == 'bg' ? 'Всички статии в блога' : 'All Articles in the Blog';
            if(!count($data->rows)) {
                $data->descr .= "<p><b style='color:#666;'>" . tr('Все още няма статии в този блог') . '</b></p>';
            }
        }

		
        // Подготвяме OpenGraphProtocol обекта
        $this->prepareOgraph($data);
        
        $this->prepareNavigation($data);
    }
	
	
	/**
	 * Нов екшън, който рендира листовия списък на статиите за външен достъп, Той връща 
	 * нов темплейт, който представя таблицата в подходящия нов дизайн, създаден е по
	 * аналогия на renderList  с заменени методи които да рендират в новия изглед
	 */
	function renderBrowse_($data)
    {
		$layout = $data->ThemeClass->getBrowseLayout();
        
        if(count($data->rows)) {
            foreach($data->rows as $row) {
                $rowTpl = $layout->getBlock('ROW');
                $rowTpl->placeObject($row);
                $rowTpl->append2master();
            }
        }   
        
     
        $layout->replace($data->title, 'BROWSE_HEADER');
        $layout->replace($data->descr, 'BROWSE_DESCR');
        $layout->append($data->pager->getPrevNext("« по-стари", "по-нови »"));
        
        // Рендираме навигацията
        $layout->replace($this->renderNavigation($data), 'NAVIGATION');
        
		return $layout;
	}


	/**
	 * Подготвяме навигационното меню
	 */
	function prepareNavigation_(&$data)
    {
		$this->prepareSearch($data);
        
		$data->categories = blogm_Categories::getCategoriesByDomain();

        $this->prepareArchive($data);
        
        blogm_Links::prepareLinks($data);

        // Тема за блога
        $data->ThemeClass = $this->getThemeClass();

        $selfId = core_Classes::getId($this);
        
        Mode::set('cMenuId', cms_Content::getDefaultMenuId('blogm_Articles'));
	}


	/**
	 * Функция което рендира менюто с категориите, формата за търсене, и менюто с архивите
	 */
	function renderNavigation_($data)
    {   
        $layout = $data->ThemeClass->getNavigationLayout();

        // Рендираме формата за търсене
		$layout->append($this->renderSearch($data), 'SEARCH_FORM');
		
		// Рендираме категориите
 		$layout->append(blogm_Categories::renderCategories($data), 'CATEGORIES');
		
  		
        if($data->workshop) {
            $data->workshop['ret_url'] = TRUE;
            $layout->append(ht::createBtn('Работилница', $data->workshop, NULL, NULL, 'ef_icon=img/16/application_edit.png'), 'WORKSHOP');
        }
        
        // Рендираме архива
        $layout->replace($this->renderArchive($data), 'ARCHIVE');
        
        // Рендираме Линковете
        $layout->replace(blogm_Links::renderLinks($data), 'LINKS');
		
        // Добавяме стиловете от темата
        $layout->push($data->ThemeClass->getStyles(), 'CSS');
		
        // Поставяме шаблона за външен изглед
		Mode::set('wrapper', 'cms_page_External');

        // Добавяме лейаута на страницата
        Mode::set('cmsLayout', $data->ThemeClass->getBlogLayout());


        return $layout;
	}
 

    /**
     * Подготвяме формата за търсене
     */
    function prepareSearch_(&$data)
    {
        $form = cls::get('core_Form', array('method' => 'GET'));
 		$data->searchForm = $form;
	}
	
	
	/**
	 * Рендираме формата за търсене
	 */
	function renderSearch_(&$data)
    {
 		$data->searchForm->layout = $data->ThemeClass->getSearchFormLayout();
 		
        $data->searchForm->layout->replace(toUrl(array('blogm_Articles' )), 'ACTION');
		
        $data->searchForm->layout->replace(sbf('img/16/find.png', ''), 'FIND_IMG');
        $data->searchForm->layout->replace($data->q, 'VALUE');

		return $data->searchForm->renderHtml();
	}	
	
    
    /**
     * Подготвяме архива
     */
    function prepareArchive_(&$data)
    {
		$query = $this->getQuery();
        $query->XPR('month', 'varchar', "CONCAT(YEAR(IF(#publishedOn,#publishedOn,#createdOn)), '|', MONTH(IF(#publishedOn,#publishedOn,#createdOn)))");
        
        $query->XPR('pubTime', 'datetime', "IF(#publishedOn,#publishedOn,#createdOn)");

        $query->groupBy("month");
        $query->show('month,pubTime');
        $query->orderBy('#pubTime', 'DESC');
        $query->where("#state = 'active'");
        
        // Филтриране по категориите на съответния език
        $categories = blogm_Categories::getCategoriesByDomain();
        if(!is_array($categories) || !count($categories)) {
            $categories = array('99999999' => 'Няма категории на съответния език');
        }
        $query->likeKeylist('categories', keylist::fromArray($categories));

        while($rec = $query->fetch()) { 
            $data->archiveArr[] = $rec->month;
        }
	}
	
	
	/**
	 * Рендираме архива
	 */
	function renderArchive_(&$data)
    {
        if(count($data->archiveArr)) {

            // Шаблон, който ще представлява списъка от хиперлинкове към месеците от архива
            $tpl = new ET();

            foreach($data->archiveArr as $month) {

                list($y, $m) = explode('|', $month);
            
                if($data->archive == $month) {
                    $attr = array('class' => 'nav_item sel_page level2');
                } else {
                    $attr = array('class' => 'nav_item level2');
                }
                
                // Създаваме линк, който ще покаже само статиите от избраната категория
                $title = ht::createLink(dt::getMonth($m,   Mode::is('screenMode', 'narrow') ? 'M' : 'F') . '/' . $y, array('blogm_Articles', 'browse', 'archive'  => $month));
                
                // Див-обвивка
                $title = ht::createElement('div', $attr, $title);

                $tpl->append($title);
            }

            return $tpl;
        }
 	}	


	/**
     * Какви роли са необходими за посоченото действие?
     */
	function on_AfterGetRequiredRoles($mvc, &$roles, $act, $rec = NULL, $user = NULL)
    {
        if($act == 'article' && isset($rec)) {
            if($rec->state != 'active') {
                // Само тези, които могат да създават и редактират статии, 
                // могат да виждат статиите, които не са активни (публични)
                $roles = $mvc->canWrite;
            }
        }
    }


    
    /**
     * Имплементиране на интерфейсния метод getItems от cms_FeedsSourceIntf
     * @param int $itemsCnt
     * @param enum $lg
     * @return array
     */
    function getItems($itemsCnt, $domainId, $like = NULL)
    {
    	// Заявка за работа с модела
    	$query = $this->getQuery();
    	
    	// Филтрираме, подреждаме и ограничаваме броя на резултатите
    	$categories = blogm_Categories::getCategoriesByDomain($domainId);
    	$query->likeKeylist('categories', keylist::fromArray($categories));
    	
    	if($like){
    		$fldType = $this->getFieldType($this->feedFilterField);
    		if($fldType instanceof type_Keylist){
    			$query->likeKeylist($this->feedFilterField, $like);
    		} else {
    			$query->where("{$this->feedFilterField} = '{$like}'");
    		}
    	}
    	
    	$query->where("#state = 'active'");
        $query->XPR('pubTime', 'datetime', "IF(#publishedOn,#publishedOn,#createdOn)");
		$query->orderBy('#pubTime', 'DESC');
    	$query->limit($itemsCnt);
    	
    	$items = array();
    	
    	if($query->count()) {
    		$richText = cls::get('type_Richtext');
	    	while($rec = $query->fetch()) {
	    		
	    		// Извличаме необходимите ни данни
	    		$item = new stdClass();
	    		$item->title = $rec->title;
	    		$item->link = toUrl(self::getUrl($rec), 'absolute');
	    		$item->date = $rec->pubTime;
	    		
	    		// Извличаме описанието на статията, като съкръщаваме тялото и 
	    		$desc = explode("\n", $rec->body);
	    		if(count($desc) > 1) {
	    			$rec->body = ht::extractText($richText->toHtml($desc[0]));
	    			$rec->body .= "[...]";
	    		}
	    		
	    		$item->description = $rec->body;
	    		
	    		// Натрупваме информацията за статиите
	    		$items[] = $item;
	    	}
    	}
    	
    	return $items;
    }
    
    
	/**
      * Помощен метод връщащ пътя към темата зададена
      * от потребителя, или базовата тема ако няма зададена
      */
     public function getThemeClass()
     {
     	$conf = core_Packs::getConfig('blogm');

     	return cls::get($conf->BLOGM_DEFAULT_THEME);
     }

    
    
    /**********************************************************************************************************
     *
     * Интерфейс cms_SourceIntf
     *
     **********************************************************************************************************/

    /**
     * Връща URL към себе си (блога)
     */
    function getUrlByMenuId($cMenuId)
    {
        return array('blogm_Articles', 'Default');
    }


    /**
     * Връща URL към вътрешната част (работилницата), отговарящо на посочената точка в менюто
     */
    function getWorkshopUrl($menuId)
    {
        $url = array('blogm_Articles', 'list');

        return $url;
    }


    /**
     * Връща URL към посочената статия
     */
    static function getUrl($rec, $canonical = FALSE)
    {
        $res = array('A', 'B', $rec->vid ? urlencode($rec->vid) : $rec->id, 'PU' => (haveRole('powerUser') && !$canonical) ? 1 : NULL);

        return $res;
    }


    /**
     * Връща кратко URL към статия от блога
     */
    static function getShortUrl($url)
    {
        $vid = urldecode($url['id']);
        $act = strtolower($url['Act']);

        if($vid && $act == 'article') {
            $id = cms_VerbalId::fetchId($vid, 'blogm_Articles'); 

            if(!$id) {
                $id = self::fetchField(array("#vid = '[#1#]'", $vid), 'id');
            }
            
            if(!$id && is_numeric($vid)) {
                $id = $vid;
            }


            if($id) {
                $url['Ctr'] = 'A';
                $url['Act'] = 'b';
                $url['id'] = $id;
            }
        }

        unset($url['PU']);

        return $url;
    }


    /**
     * Връща връща масив със заглавия и URL-ta, които отговарят на търсенето
     */
    static function getSearchResults($menuId, $q, $maxResults = 15)
    { 
        $res = array();

        $cRec = cms_Content::fetch($menuId);
        
        $gQuery = blogm_Categories::getQuery();
        $groupsArr = array();
        while($gRec = $gQuery->fetch("#domainId = {$cRec->domainId}")) {
            $groupsArr[$gRec->id] = $gRec;
        }

        $queryM = self::getQuery();
        $queryM->where("#state = 'active'");
        $queryM->likeKeylist('categories', keylist::fromArray($groupsArr));
        $queryM->limit($maxResults);
        $queryM->orderBy('modifiedOn=DESC');

        $query = clone($queryM);
        plg_Search::applySearch($q, $query, NULL, 5, 64);

        while($r = $query->fetch()) {
            $title = $r->title;
            $url = self::getUrl($r);
            $url['q'] = $q;

            $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url);
        }
        
        if(count($res) < $maxResults) {
            $query = clone($queryM);
            plg_Search::applySearch($q, $query, NULL, 9);
            while($r = $query->fetch()) {
                $title = $r->title;
                $url = self::getUrl($r);
                $url['q'] = $q;

                $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url);
            }
        }

        if(count($res) < $maxResults) {
            $query = clone($queryM);
            plg_Search::applySearch($q, $query, NULL, 3);
            while($r = $query->fetch()) {
                $title = $r->title;
                $url = self::getUrl($r);
                $url['q'] = $q;

                $res[toUrl($url)] = (object) array('title' => $title, 'url' => $url);
            }
        }
      
 
        return $res; 
    }


    
    /**
     * След рендиране на синъл изгледа
     *
     * @param blogm_Articles $mvc
     * @param core_ET $tpl
     * @param object $data
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        // Оттегляне на нотификацията
        $url = array($mvc, 'single', $data->rec->id);
        bgerp_Notifications::clear($url);
    }


    /**
     * Публикива чакащите статии на които им е дошло времето
     */
    function cron_PublicPending()
    {
        $now = dt::verbal2mysql();

        $query = self::getQuery();
        $query->where("#state = 'pending' AND #publishedOn < '{$now}'");

        while($rec = $query->fetch()) {
            $rec->state = 'active';
            self::save($rec);
        }
    }

}
