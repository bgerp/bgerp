<?php

/**
 * Дъски
 *
 *
 * @category  bgerp
 * @package   forum
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class forum_Boards extends core_Master {
	
	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Форумни дъски';
   

    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'cms_SourceIntf';
	

	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Modified, forum_Wrapper, plg_Sorting'; 
	
	
	/**
	 * Поле за лентата с инструменти
	 */
	var $rowToolsField = 'tools';
	
	
	/**
	 * Полета за листов изглед 
	 */
	var $listFields = 'tools=Пулт, title, category, shortDesc, themesCnt, commentsCnt, boardType, shared, lastComment, lastCommentedTheme, lastCommentBy, createdOn, createdBy';
	
	
	/**
	 * Теми и коментари на дъската
	 */
	var $details = 'forum_Postings';
	
	
	/**
	 * Кой може да листва дъските
	 */
	var $canRead = 'forum, cms, admin';
	
	
	/**
	 * Кой може да добявя,редактира или изтрива дъска
	 */
	var $canWrite = 'forum, cms, admin';
	
	
	/**
	 * Кой може да изтрива дъските
	 */
	var $canDelete = 'no_one';
	
	
	/**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'forum/tpl/SingleBoard.shtml';
	
	
	/**
	 * Полета които да са достъпни след изтриване на дъска
	 */
	var $fetchFieldsBeforeDelete = 'id,title';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar(50)', 'caption=Име, mandatory, width=400px');
		$this->FLD('shortDesc', 'varchar(100)', 'caption=Oписание, mandatory, width=100%');
		$this->FLD('category', 'key(mvc=forum_Categories,select=title,groupBy=type)', 'caption=Категория, mandatory');
		$this->FLD('boardType', 'enum(normal=Нормална,confidential=Конфиденциална)', 'caption=Достъп->Тип, notNull, value=normal');
		$this->FLD('shared', 'keylist(mvc=core_Users,select=nick)', 'caption=Достъп->Споделяне');
		$this->FLD('themesCnt', 'int', 'caption=Темите, input=none, value=0');
		$this->FLD('commentsCnt', 'int', 'caption=Коментари, input=none, value=0');
		$this->FLD('lastComment', 'datetime(format=smartTime)', 'caption=Последно->кога, input=none');
		$this->FLD('lastCommentBy', 'int', 'caption=Последно->кой, input=none');
		$this->FLD('lastCommentedTheme', 'int', 'caption=Последно->къде, input=none');
		
		// Поставяме уникален индекс
		$this->setDbUnique('title');
	}
	
	
	/**
	 * Подрежане и филтриране на дъските по категории
	 */ 
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		// Предпазване от листване на конфиденциални дъски
		$cu = core_Users::getCurrent();
		
		// Пропускаме конфиденциалните папки,несподелени с текущия потребител
       	if(!haveRole('forum') && $cu >0) {
            $data->query->where("NOT (#boardType = 'confidential'  AND !(#shared LIKE '%|{$cu}|%'))");
        }
		
		if($category = Request::get('category')) {
			$data->query->where(array("#category = [#1#]", $category));
		}
		
		$data->query->orderBy('#category');
	}
	
	
	/**
	 * Обновяваме броя на темите и коментарите на дъската. Обновяваме кой, къде и
	 * кога е направил последния коментар
	 * @param int $id
	 */
	function updateBoard($id)
	{
		// Заявка за работа с темите от дъската
		$themesQuery = forum_Postings::getQuery();
		$themesQuery->where("#boardId = {$id} AND #themeId IS NULL");
		
		// Заявка за работа с коментарите от дъската
		$commentsQuery = forum_Postings::getQuery();
		$commentsQuery->where("#boardId = {$id} AND #themeId IS NOT NULL");
		
		// Извличане на последните тема и коментар
		$themesQuery->XPR('maxId', 'int', 'max(#id)');
		$commentsQuery->XPR('maxId', 'int', 'max(#id)');
		
		if($commentsQuery->count() > 0) {
			
			// Ако има коментари то намираме, последно добавения коментар
			$last = forum_Postings::fetch($commentsQuery->fetch()->maxId);
		} else {
			if($themesQuery->count() > 0) {
				
				// Ако няма коментари но има теми, намираме последно добавената тема
				$last = forum_Postings::fetch($themesQuery->fetch()->maxId);
			} else {
				
				// Ако няма нито коментари нито теми, то $last е NULL
				$last = NULL;
			  }
		  }
		
		// Дъската в която ще обновяваме информацията
		$rec = $this->fetch($id);
		
		// Броят на постингите, които са теми
		$rec->themesCnt = $themesQuery->count();
		
		// Броят на постингите, които са коментари
		$rec->commentsCnt = $commentsQuery->count();
		
		// Ако има коментар в дъската, ние обновяваме кой, кога и къде го е направил
		if($last) { 
			($last->themeId !== NULL) ? $id = $last->themeId : $id = $last->id;
			
			// Обновяваме кога къде и от кого е направн последния коментар, ако няма 
			// коментари обновяваме коя, кога и къде е последно създадената тема
			$rec->lastCommentedTheme = $id;
			$rec->lastComment = $last->createdOn;
		    $rec->lastCommentBy = $last->createdBy;
		   
		} else {
			
			// Ако дъската е празна
			$rec->lastCommentedTheme = NULL;
			$rec->lastComment = NULL;
		    $rec->lastCommentBy = NULL;
		  }
		
	    // Обновяваме дъската
	    $this->save($rec);
	}
	
	
	/**
	 * Екшън за преглеждане на всички дъски
	 */
	function act_Forum()
	{
		// Създаваме празен $data обект
		$data = new stdClass();

        // Създаваме заявка към модела
		$data->query = $this->getQuery();
		
		// Тема по подразбиране
		$conf = core_Packs::getConfig('forum');
        $data->forumTheme = $conf->FORUM_DEFAULT_THEME;
        $data->title = tr($conf->GREETING_MESSAGE);
        $data->action = 'forum';
        $data->display = 'public';
        $data->category = Request::get('cat');
        
        // Подготвяме необходимите данни за показване на дъските
        $this->prepareForum($data);
        
        // Рендираме Дъските в форума
        $layout = $this->renderForum($data);
       
        return $layout;
	}
	
	
	/**
	 *  Подготовка на списъка с дъски, разпределени по техните категории
	 */
	 function prepareForum($data)
	 {
		// Извличаме всички категории на дъските
		forum_Categories::prepareCategories($data);

		if(count($data->categories)) {
			
			// За всяка категория ние подготвяме списъка от дъски, които са част от нея
			foreach($data->categories as $category){
				$this->prepareBoards($category);
			}
		}
		
		if($this->haveRightFor('list')) {
			if($data->category) {
				$url = array($this, 'list', 'category' => $data->category);
			} else {
				$url = array($this, 'list');
			  }
			
			$data->listUrl = $url;
		}
		
		$this->prepareNavigation($data);
	 }
	
	
	/**
	 * Подготвяме, навигационните линкове за бърз достъп до избраната категория/дъска/тема
	 * в навигационното поле на форума
	 */
	function prepareNavigation($data)
	{
		// Линк към началото на форума
		$data->navigation[] = ht::createLink(tr('Форуми'), array('forum_Boards', 'Forum'));
		 if($data->action == 'forum'){
		 	if(isset($data->category)){
		 		
				// Ако е избрана само една категория
				$categoryRec = forum_Categories::fetch($data->category);
				$categoryRow = forum_Categories::recToVerbal($categoryRec, "id,title,-public");
				$data->navigation[] = $categoryRow->title;
		 	}
		 } elseif($data->action == 'browse' || $data->action == 'new') {
			 
		 	// Ако разглеждаме дъска, навигацията е от рода  Форуми->Категория->Дъска
			$boardRow = $this->recToVerbal($data->rec, "id,title,category,-public");
			$data->navigation[] = $boardRow->category->title;
			$data->navigation[] = $boardRow->title;
			 
		}  elseif ($data->action == 'theme') {
			
			// Ако разглеждаме тема,навигацията ще от рода  Форуми->Категория->Дъска->Тема
			$boardRow = $this->recToVerbal($data->board, "id,title,category,-public");
			$themeRow = forum_Postings::recToVerbal($data->rec, "id,title,-public");
			$data->navigation[] = $boardRow->category->title;
			$data->navigation[] = $boardRow->title;
			$data->navigation[] = $themeRow->title;
		} 
	}
	
	
	/**
	 * Подготвя навигацията за вътрешния изглед
	 */
	static function prepareInnerNavigation($data)
	{
		// Линк към началото на форума
		$data->navigation[] = ht::createLink(tr('Форуми'), array('forum_Boards', 'list'));
		
		if($data->action == 'list') {
			if($category = Request::get('category')) {
				
				// Ако е сетнато $data->category, то е избрана само една категория
				$categoryRec = forum_Categories::fetch($category);
				$categoryRow = forum_Categories::recToVerbal($categoryRec, "id,title,-list");
				$data->navigation[] = $categoryRow->title;
			}
			
		}  elseif($data->action == 'single') {
			 
		 	// Ако разглеждаме дъска,навигацията ще от рода  Форуми->Категория->Дъска
			$boardRow = static::recToVerbal($data->rec, "id,title,category,-private");
			$data->navigation[] = $boardRow->category->title;
			$data->navigation[] = $boardRow->title;
			 
		}  elseif ($data->action == 'topic' || $data->action == 'move') {
			
			// Ако разглеждаме тема,навигацията ще от рода  Форуми->Категория->Дъска->Тема
			$boardRow = static::recToVerbal($data->board, "id,title,category,-private");
			$themeRow = forum_Postings::recToVerbal($data->rec, "id,title,-private");
			$data->navigation[] = $boardRow->category->title;
			$data->navigation[] = $boardRow->title;
			$data->navigation[] = $themeRow->title;
		} 
	}
	
	
	/**
	 * Добавяме всеки елемент на в последователност от линкове
	 */
	function renderNavigation($data)
	{
		foreach($data->navigation as $nav) {
			$navigation .= $nav . "&nbsp;»&nbsp;"; 
		}
		
		// Премахваме излишните символи от края на линка
		$navigation = trim($navigation, "&nbsp»&nbsp;");
		$navigation = "<span id='navigation-inner-link'>" . $navigation . "</span>";
		
		if($data->display) {
		   
		   // Добавяме външният изглед, само ако екшъна е за външен изглед
		   Mode::set('wrapper', 'cms_tpl_Page');
		   Mode::set('cmsLayout', $data->forumTheme . '/Layout.shtml');
		   
		   // Засветяване на Форум  в менюто
		   $selfId = core_Classes::fetchIdByName($this);
		   Mode::set('cMenuId', cms_Content::fetchField("#source = {$selfId}", 'id'));
		}
		
        return $navigation;
	}
	
	
	/**
	 * Подготвя дъските от подадената категория
	 */
	function prepareBoards(&$category)
	{
		$query = $this->getQuery();
		$query->where("#category = {$category->id}");
		
	 	// Предпазване от листване на конфиденциални дъски
		$cu = core_Users::getCurrent();

       	if(!haveRole('forum') && $cu >0) {
            $query->where("NOT (#boardType = 'confidential'  AND !(#shared LIKE '%|{$cu}|%'))");
        }
		
		$fields = $this->selectFields("");
		$fields['-public'] = TRUE;
		while($rec = $query->fetch()) {
			
		// Ако имаме права да виждаме дъските
		if($this->haveRightFor('read', $rec)){
				$category->boards->recs[$rec->id] = $rec;
	 			$category->boards->rows[$rec->id] = $this->recToVerbal($rec, $fields);
	 		}
		}
	}
	
	
	/**
	 *  Рендираме списъка с дъските групирани по категории
	 */
	function renderForum($data)
	{
		$tpl = new ET(getFileContent($data->forumTheme . '/Index.shtml'));
 		$tpl->replace('<h2>' . $data->title . '</h2>', 'GREETING');

 		if(count($data->categories)) {
        	
        	// Зареждаме шаблоните веднъж в паметта и после само ги клонирваме
        	$categoryTpl = new ET(getFileContent($data->forumTheme . '/Category.shtml'));
        	$boardTpl = new ET(getFileContent($data->forumTheme . '/Boards.shtml'));
            $icon = ht::createElement('img', array('src' => sbf($data->forumTheme . "/img/32/forum.png", "")));
        	
            foreach($data->categories as $category) {
                
                // За всяка категория ние поставяме името и преди  списъка с нейните дъски
                $catTpl = clone($categoryTpl);
                $catTpl->replace($category->title, 'cat');
                if($category->boards->rows) { 
                    
                    // За всички дъски от категорията ние ги поставяме под нея в шаблона
                    foreach($category->boards->rows as $row) {
                    	$rowTpl = clone($boardTpl);
                        $rowTpl->placeObject($row);
                        $rowTpl->replace($icon, "ICON");
                        $rowTpl->removeBlocks();
                    	$catTpl->append($rowTpl, 'BOARDS');
                    }
                } else {
                       $catTpl->replace(new ET("<li class='no-boards'>" . tr("Няма Дъски") . "</li>"), 'BOARDS');
                  }

                // Добавяме категорията с нейните дъски към главния шаблон
                $tpl->append($catTpl, 'CATEGORIES');
            }
        }
		
		if($data->listUrl) { 
			$tpl->append(ht::createBtn('Работилница', $data->listUrl, NULL, NULL, 'ef_icon=img/16/application_edit.png'), 'TOOLBAR');
		}
		
        $tpl->push($data->forumTheme . '/styles.css', 'CSS');
        
        $tpl->replace($this->renderNavigation($data), 'NAVIGATION');
        
		// Връщаме шаблона с всички дъски групирани по категории
		return $tpl;
	}
	
	
	/**
	 * Екшън за преглеждане на темите в една дъска
	 */
	function act_Browse() 
	{
		$id = Request::get('id', 'int');
		$data = new stdClass();
		$data->query = $this->getQuery();
		
		// Тема по подразбиране
		$conf = core_Packs::getConfig('forum');
        $data->forumTheme = $conf->FORUM_DEFAULT_THEME;
        $data->action = 'browse';
        $data->display = 'public';
        expect($data->rec = $this->fetch($id));
		
		// Изискваме потребителя да има права да вижда  дъската
		$this->requireRightFor('read', $data->rec);
		
		// Подготвяме информацията нужна за преглеждане на дъската
		$this->prepareBrowse($data);
		
		// Рендираме разглежданата дъска
		$layout = $this->renderBrowse($data);
		
		return $layout;
	}
	
	
	/**
	 * Подготовка на темите от дъската
	 */
	function prepareBrowse_($data)
	{
		$data->query->orderBy('createdOn', 'DESC');
		$fields = $this->selectFields("");
        $fields['-browse'] = TRUE;
        $data->row = $this->recToVerbal($data->rec, $fields);
        
        // Извличаме всички Постинги, които са начало на нова тема в дъската
        $this->forum_Postings->prepareBoardThemes($data);
        $this->prepareNavigation($data);
    }
	
	
	/**
	 *  Рендиране на списъка от теми, в разглежданата дъска
	 */
	function renderBrowse_($data) 
	{
		$tpl = new ET(getFileContent($data->forumTheme . '/Browse.shtml'));
		
		$tpl->placeObject($data->row);
		
		// Рендираме всички теми от дъската
		$tpl = $this->forum_Postings->renderBoardThemes($data, $tpl);
		
		if($data->submitUrl) { 
			$tpl->append(ht::createBtn('Нова Тема', $data->submitUrl, NULL, NULL, 'id=btnAdd,class=btn-add'), 'TOOLBAR');
		}
		
		if($data->singleUrl) { 
			$tpl->append(ht::createBtn('Работилница', $data->singleUrl, NULL, NULL, 'ef_icon=img/16/application_edit.png'), 'TOOLBAR');
		}
		
		$tpl->push($data->forumTheme . '/styles.css', 'CSS');
        
        $tpl->replace($this->renderNavigation($data), 'NAVIGATION');
        
		return $tpl;
	}
	
	
	/**
     * Бутон за преглед на дъските във външен изглед
     */
	static function on_AfterPrepareListToolbar($mvc, &$data)
    {
		if($cat = Request::get('category')){
			$url = array($this, 'forum', 'cat' => $cat);
		} else {
			$url = array($this, 'forum');
		  }
		
    	$data->toolbar->addBtn('Преглед', $url);
    }
 	
    
    /**
     * Бутон за преглед на дъската във външен изглед
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
		 if ($mvc->haveRightFor('read', $data->rec)) {
            $data->toolbar->addBtn('Преглед', array($this, 'Browse', $data->rec->id));
        }
    }
    
    
    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
		if($action == 'read' && isset($rec->id)) {
			
			($mvc::haveRightToObject($rec, $userId)) ? $res = 'every_one' : $res = 'forum';
		}
	}
	
	
	/**
	 * Функция проверяваща дали потребителя има достъп до дъската
	 * @param stdClass $rec
	 * @param int $userId 
	 * @return boolean
	 */
	static function haveRightToObject($rec, $userId = NULL)
    {
        if(!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        // 'forum' има достъп до всяка дъска
        if(haveRole('forum')) return TRUE;
        
        // Ако дъската е 'нормална' всички имат достъп до нея
        if($rec->boardType == 'normal') return TRUE;
        
        // Ако дъската е споделена с текущия потребител, той има достъп
        if(strpos($rec->shared, '|' . $userId . '|') !== FALSE) return TRUE;
        
        // Ако никое от горните не е изпълнено - отказваме достъпа
        return FALSE;
    }
	
	
	/**
	 * Модификация на вербалните записи
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
   	{
   		if($fields['-list'] || $fields['-single']) {
   			
   			// Правим заглавието на линк за единичен изглед
   			$row->title = ht::createLink($row->title, array($mvc, 'Single', $rec->id));
   			
   			if(!$rec->lastCommentBy) {
   				$row->lastCommentedTheme = 'няма';
   				$row->lastComment = 'няма';
   				$row->lastCommentBy = 'няма';
   			} else {
   				$row->lastCommentBy = crm_Profiles::createLink($rec->lastCommentBy);
   			}

   			if($rec->lastCommentedTheme) {
   				$themeRec = forum_Postings::fetch($rec->lastCommentedTheme);
   				if(strlen($themeRec->title) >= 10) {
   					
   					// Ако заглавието и е много дълго го съкръщаваме
   					$themeRec->title = mb_substr($themeRec->title,0 , 10);
   					$themeRec->title .= "..."; 
   				}
   				
   				$row->lastCommentedTheme = ht::createLink($themeRec->title, array('forum_Postings', 'Topic', $themeRec->id));
   			}
   		}
   		
   		// Модификации по вербалното представяне на записите  в екшъна forum
   		if($fields['-public']) {
   			
   			$row->title = ht::createLink($row->title, array($mvc, 'Browse', $rec->id));
   			$categoryRec = forum_Categories::fetch($rec->category);
   			$row->category = forum_Categories::recToVerbal($categoryRec, 'id,title,-public');
   			$row->themesCnt .= "&nbsp;" . tr('Теми');
   			$row->commentsCnt .= "&nbsp;" . tr('Мнения');
   			
   			// Ако темата има последен коментар
   			if($rec->lastCommentBy) {
	          
	           // преобразуваме ид-то на последно коментираната тема в разбираем вид
	           $themeRec = forum_Postings::fetch($rec->lastCommentedTheme);
	           if(strlen($themeRec->title) >= 25) {
	           	
	           		// Ако заглавието и е много дълго го съкръщаваме
   					$themeRec->title = mb_substr($themeRec->title, 0, 20);
   					$themeRec->title .= "..."; 
   				}
   				
   				$row->lastCommentedTheme =  ht::createLink($themeRec->title, array('forum_Postings', 'Theme', $themeRec->id));
	           
	           // Намираме граватара и ника на потребителя коментирал последно
	           $lastUser = core_Users::fetch($rec->lastCommentBy);
	           $row->lastAvatar =  avatar_Plugin::getImg(0, $lastUser->email, 50);
	           $row->lastNick = $lastUser->nick;
	       } else {
	           $row->noComment = tr('дъската е празна');
	        }
   		}
   		
   		// Превръщане на името на дъската и категорията линкове за вътрешен изглед, 
   		// ако се изисква за подготовка при навигацията
   		if($fields['-private']) { 
   			$row->title = ht::createLink($row->title, array($mvc, 'Single', $rec->id));
   			$categoryRec = forum_Categories::fetch($rec->category);
   			$row->category = forum_Categories::recToVerbal($categoryRec, 'id,title,-list');
   		}
    }
    
   
    /**
     * Връща URL към себе си (форума)
     */
    function getContentUrl($cMenuId)
    {
        return array('forum_Boards', 'forum');
    }
    
    
    /**
     * Ако сме в екшън за единичен изглед, подготвяме навигацията
     */
    static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$data->action = 'single';
    	$mvc::prepareInnerNavigation($data);
    }
    
    
    /**
     * Подготвяме вътрешната навигация в List екшъна
     */
    static function on_BeforePrepareListTitle($mvc, &$res, $data)
    {
    	$data->action = 'list';
    	$mvc::prepareInnerNavigation($data);
    }
    
    
	/**
     * Подготвяме вътрешната навигация в List екшъна
     */
    static function on_AfterRenderListTitle($mvc, &$tpl, $data)
    {
    	$tpl->replace(new ET("[#NAVIGATION#]"));
    }
    
    
    /**
     * Рендираме навигацията след рендирането на обвивката
     */
    function on_AfterRenderWrapping($mvc, &$tpl, $content, $data = NULL) {
    	
    	$tpl->push('forum/tpl/styles.css', 'CSS');
    	if($data->navigation){ 
    		$tpl->replace($this->renderNavigation($data), 'NAVIGATION');
    	}
     }
     
     
     /**
      *  Обновяване на категория, след добавяне на нова тема
      */
     static function on_AfterCreate($mvc, $rec)
     {
     	forum_Categories::updateCategory($rec->category);
     }
}
