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
	var $loadList = 'plg_RowTools, plg_State, plg_Printing, blogm_Wrapper, 
        plg_Search, plg_Created, plg_Modified, plg_Vid, plg_Rejected';
	

    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'cms_SourceIntf';


	/**
	 * Полета за листов изглед
	 */
	var $listFields ='id, title, categories, author, createdOn, createdBy, modifiedOn, modifiedBy';
	
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';

    
	/**
	 *  Брой статии на страница 
	 */
	var $listItemsPerPage = "4";
	
	
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
	var $canRead = 'cms, ceo, admin';
	
	
	/**
	 * Кой може да добявя,редактира или изтрива статия
	 */
	var $canWrite = 'cms, ceo, admin';
	
	/**
	 * Кой може да вижда публичните статии
	 */
	var $canArticle = 'every_one';
	
	/**
	 * Файл за единичен изглед
	 */
	//var $singleLayoutFile = 'blogm/tpl/SingleArticle.shtml';


    /**
	 * Единично заглавие на документа
	 */
	var $singleTitle = 'Статия';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('author', 'varchar(40)', 'caption=Автор, mandatory, notNull,width=100%');
		$this->FLD('title', 'varchar(190)', 'caption=Заглавие, mandatory, width=100%');
		$this->FLD('categories', 'keylist(mvc=blogm_Categories,select=title)', 'caption=Категории,mandatory');
		$this->FLD('body', 'richtext(bucket=' . self::FILE_BUCKET . ')', 'caption=Съдържание,mandatory');
 		$this->FLD('commentsMode', 
            'enum(enabled=Разрешени,confirmation=С потвърждение,disabled=Забранени,stopped=Спрени)',
            'caption=Коментари->Режим,maxRadio=4,columns=4,mandatory');
        $this->FLD('commentsCnt', 'int', 'caption=Коментари->Брой,value=0,notNul,input=none');
  		$this->FLD('state', 'enum(draft=Чернова,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,mandatory');
         
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
        if($fields['-browse']) { 
            $txt = explode("\n", $rec->body, 2);
            if(count($txt) > 1) {
                $rec->body = trim($txt[0]); 
                $rec->body .=   " [link=" . toUrl(array('blogm_Articles', 'Article', $rec->vid ? $rec->vid : $rec->id), 'absolute') . "][още][/link]";
            }

            $row->body = $mvc->getVerbal($rec, 'body');
        }

        if($q = Request::get('q')) {
            $row->body = plg_Search::highlight($row->body, $q);
        }

        if($fields['-browse'] || $fields['-article']) {
            if($row->commentsCnt == 1) {
                $row->commentsCnt .= '&nbsp;' . tr('коментар');
            } else {
                $row->commentsCnt .= '&nbsp;' . tr('коментара');
            }
        }

	}
	
	
	/**
	 *  извършва филтриране и подреждане на статиите
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		// Подреждаме статиите по датата им на публикуане в низходящ ред	
		$data->query->orderBy('createdOn', 'DESC');
		
		// Ако метода е 'browse' показваме само активните статии
		if($data->action == 'browse'){
			
			// Показваме само статиите които са активни
			$data->query->where("#state = 'active'");
			
		}
	}
	

    /**
     * След обновяването на коментарите, обновяваме информацията в статията
     */
    function on_AfterUpdateDetail($mvc, $articleId, $Detail)
    {
        if($Detail->className == 'blogm_Comments') {
            $queryC = $Detail->getQuery();
            $queryC->where("#articleId = {$articleId} AND #state = 'active'");
            $rec = $mvc->fetch($articleId);
            $rec->commentsCnt = $queryC->count();
            $mvc->save($rec);
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
                $data->title = 'Статии от категория:&nbsp;&nbsp;&nbsp;&nbsp;' . $title;
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
 	}
	
	
	/**
	 *  Филтриране на статиите по ключови думи и категория
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{	
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->FNC('category', 'key(mvc=blogm_Categories,select=title,allowEmpty)', 'placeholder=Категория,silent');

        $data->listFilter->showFields = 'search,category';
        
        // Активиране на филтъра
        $recFilter = $data->listFilter->input(NULL, 'silent');

        if(($cat = $recFilter->category) > 0) {
           $data->query->where("#categories LIKE '%|{$cat}|%'");
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
            expect($id = Request::get('articleId', 'int'));
        }
		
		// Създаваме празен $data обект
		$data = new stdClass();
		$data->query = $this->getQuery();
		$data->articleId = $id;

		// Трябва да има $rec за това $id
		expect($data->rec = $this->fetch($id));
		
        // Трябва да имаме права за да видим точно тази статия
		$this->requireRightFor('article', $data->rec);
 		
		// Подготвяме данните за единичния изглед
		$this->prepareArticle($data);
        
        // Обработка на формата за добавяне на коментари
        if($cForm = $data->commentForm) {
        
            // Зареждаме REQUEST данните във формата за коментар
            $rec = $cForm->input();
            
            // Мениджърът на блог-коментарите
            $Comments = cls::get('blogm_Comments');

            // Генерираме събитие в $Comments, след въвеждането на формата
            $Comments->invoke('AfterInputEditForm', array($cForm));
            
            // Дали имаме права за това действие към този запис?
            $Comments->requireRightFor('add', $rec, NULL);
            
            // Ако формата е успешно изпратена - запис, лог, редирект
            if ($cForm->isSubmitted() && !Request::get('Comment')) {
                
                // Записваме данните
                $id = $Comments->save($rec);
                
                // Правим запис в лога
                $Comments->log('add', $id);
                
                // Редиректваме към предварително установения адрес
                return new Redirect(array('blogm_Articles', 'Article', $data->rec->id), 'Благодарим за вашия коментар;)');
            }
        }
		 // Подготвяме лейаута за статията
        $layout = $this->getArticleLayout($data);
        
		// Рендираме статията във вид за публично разглеждане
		$tpl = $this->renderArticle($data, $layout);
		
		// Записваме, че потребителя е разглеждал тази статия
		$this->log(('Blog article: ' .  $data->row->title), $id);
		
        if(core_Packs::fetch("#name = 'vislog'")) {
            vislog_History::add($data->row->title);
        }

		
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

        $this->blogm_Comments->prepareComments($data);
		
        $data->selectedCategories = type_Keylist::toArray($data->rec->categories);
		
       	$this->prepareNavigation($data);

        if($this->haveRightFor('single', $data->rec)) {
            $data->workshop = array('blogm_Articles', 'single', $data->rec->id);
        }
    }
	
	
	/**
     * Рендиране на статия за публичната част на блога
	 */
	function renderArticle_($data, $layout)
	{
		// Поставяме данните от реда
		$layout->placeObject($data->row);

		
		$layout = $this->blogm_Comments->renderComments($data, $layout);
        
        // Рендираме тулбара за споделяне
        $conf = core_Packs::getConfig('cms');
        $layout->replace($conf->CMS_SHARE, 'SHARE_TOOLBAR');

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
        return new ET(getFileContent($data->theme . '/Article.shtml'));
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
		
        // По какво заглавие търсим
		$data->q = Request::get('q');

        // Архив
        $data->archive = Request::get('archive');

        if($data->archive) {
            list($data->archiveY, $data->archiveM) = explode('|', $data->archive);
            expect(is_numeric($data->archiveY) && is_numeric($data->archiveM));
        }
		
		// Ограничаваме показаните статии спрямо спрямо константа и номера на страницата
		$conf = core_Packs::getConfig('blogm');
        $data->theme = $conf->BLOGM_DEFAULT_THEME;
         
        // Подготвяме данните необходими за списъка със стаии
        $this->prepareBrowse($data);

        // Рендираме списъка
        $tpl = $this->renderBrowse($data);
        
        // Добавяме стиловете от темата
        $tpl->push($data->theme . '/styles.css', 'CSS');

		// Записваме, че потребителя е разглеждал този списък
		$this->log('List: ' . ($data->log ? $data->log : tr($data->title)));
		
		
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
        }
        
        if($data->q) {
        	plg_Search::applySearch($data->q, $data->query);
        }
        
        if($data->archive) {  
            $data->query->where("#createdOn LIKE '{$data->archiveY}-{$data->archiveM}-%'");
        }
     
        $data->query->orderBy('createdOn', 'DESC');
        
        // Показваме само публикуваните статии
        $data->query->where("#state = 'active'");
		
        
        $fields = $this->selectFields("");
        $fields['-browse'] = TRUE;
        
        $conf = core_Packs::getConfig('blogm');
        $data->pager = cls::get('core_Pager', array('itemsPerPage' => $conf->BLOGM_ARTICLES_PER_PAGE));
        $data->pager->setLimit($data->query);

        while($rec = $data->query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
            $url = array('blogm_Articles', 'Article', $rec->vid ? $rec->vid : $rec->id );
            if($data->q) {
                $url += array('q' => $data->q);
            }
            $data->rows[$rec->id]->title = ht::createLink($data->rows[$rec->id]->title, $url);
        }

        if($this->haveRightFor('list')) {
            $data->workshop = array('blogm_Articles', 'list');
        }

        $this->prepareNavigation($data);
    }
	
	
	/**
	 * Нов екшън, който рендира листовия списък на статиите за външен достъп, Той връща 
	 * нов темплейт, който представя таблицата в подходящия нов дизайн, създаден е по
	 * аналогия на renderList  с заменени методи които да рендират в новия изглед
	 */
	function renderBrowse_($data)
    {
		$layout = new ET(getFileContent($data->theme . '/Browse.shtml'));
        
        if(count($data->rows)) {
            foreach($data->rows as $row) {
                $rowTpl = $layout->getBlock('ROW');
                $rowTpl->placeObject($row);
                $rowTpl->append2master();
            }
            
        } else {
            $rowTpl = $layout->getBlock('ROW');
            $rowTpl->replace('<h2>Няма статии</h2>');
            $rowTpl->append2master();
        }
        
		// Ако е посочено заглавие по-което се търси
        if(isset($data->q)) {
			$layout->replace('Резултати при търсене на "<b>' . 
                type_Varchar::escape($data->q) . '</b>"', 'BROWSE_HEADER');
		} elseif( isset($data->archive)) {  
   			$layout->replace('Архив за месец&nbsp;<b>' . 
                dt::getMonth($data->archiveM, 'F') . ', ' . $data->archiveY . '&nbsp;г.</b>', 'BROWSE_HEADER');
        } elseif( isset($data->category)) {
            $category = type_Varchar::escape(blogm_Categories::fetchField($data->category, 'title'));
   			$layout->replace('Статии в категорията&nbsp;"<b>' . $category .
                '</b>"', 'BROWSE_HEADER');
        }

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
                
        blogm_Categories::prepareCategories($data);

        $this->prepareArchive($data);
        
        blogm_Links::prepareLinks($data);
        
        // Конфигурация на пакета
        $data->conf = core_Packs::getConfig('blogm');

        // Тема за блога
        $data->theme = $data->conf->BLOGM_DEFAULT_THEME;

        $selfId = core_Classes::fetchIdByName($this);

        Mode::set('cMenuId', cms_Content::fetchField("#source = {$selfId}", 'id'));
	}


	/**
	 * Функция което рендира менюто с категориите, формата за търсене, и менюто с архивите
	 */
	function renderNavigation_($data)
    {   
        $layout = new ET(getFileContent($data->theme . '/Navigation.shtml'));

        // Рендираме формата за търсене
		$layout->append($this->renderSearch($data), 'SEARCH_FORM');
		
		// Рендираме категориите
 		$layout->append(blogm_Categories::renderCategories($data), 'CATEGORIES');
		
  		
        if($data->workshop) { 
            $layout->append(ht::createBtn('Работилница', $data->workshop, NULL, NULL, 'ef_icon=img/16/application_edit.png'), 'WORKSHOP');
        }
        
        // Рендираме архива
        $layout->replace($this->renderArchive($data), 'ARCHIVE');
        
        // Рендираме Линковете
        $layout->replace(blogm_Links::renderLinks($data), 'LINKS');

        // Добавяме стиловете от темата
        $layout->push($data->theme . '/styles.css', 'CSS');
		
        // Поставяме шаблона за външен изглед
		Mode::set('wrapper', 'cms_tpl_Page');

        // Добавяме лейаута на страницата
        Mode::set('cmsLayout', $data->theme . '/BlogLayout.shtml');


        return $layout;
	}
 

    /**
     * Подготвяме формата за търсене
     */
    function prepareSearch_(&$data)
    {
		$form = cls::get('core_Form');
 		$data->searchForm = $form;
	}
	
	
	/**
	 * Рендираме формата за търсене
	 */
	function renderSearch_(&$data)
    {
 		$data->searchForm->layout = new ET(getFileContent($data->theme . '/SearchForm.shtml'));
 		
        $data->searchForm->layout->replace(toUrl(array('blogm_Articles' )), 'ACTION');
		
        $data->searchForm->layout->replace(sbf('img/16/find.png', ''), 'FIND_IMG');

		return $data->searchForm->renderHtml();
	}	
	
    
    /**
     * Подготвяме архива
     */
    function prepareArchive_(&$data)
    {
		$query = $this->getQuery();
        $query->XPR('month', 'varchar', "CONCAT(YEAR(#createdOn), '|', MONTH(#createdOn))");
        $query->groupBy("month");
        $query->show('month');
        $query->where("#state = 'active'");

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
                $title = ht::createLink(dt::getMonth($m, 'F') . '/' . $y, array('blogm_Articles', 'browse', 'archive'  => $month));
                
                // Див-обвивка
                $title = ht::createElement('div', $attr, $title);

                $tpl->append($title);
            }

            return $title;
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
     * Връща URL към себе си (блога)
     */
    function getContentUrl($cMenuId)
    {
        return array('blogm_Articles');
    }
	
}
