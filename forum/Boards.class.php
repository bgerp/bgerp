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
	var $loadList = 'plg_RowTools, plg_Created, plg_Modified, forum_Wrapper'; 
	
	
	/**
	 * Поле за лентата с инструменти
	 */
	var $rowToolsField = 'tools';
	
	
	/**
	 * Полета за листов изглед 
	 */
	var $listFields ='tools, title, category, shortDesc, themesCnt, canSeeBoard, canSeeThemes, canComment,canStick,lastComment,lastCommentedTheme,createdOn,createdBy,  modifiedOn, modifiedBy';
	
	
	/**
	 * Коментари на статията
	 */
	var $details = 'forum_Postings';
	
	
	/**
	 * Кой може да листва дъските
	 */
	var $canRead = 'forum, cms, ceo, admin';
	
	
	/**
	 * Кой може да добявя,редактира или изтрива дъска
	 */
	var $canWrite = 'forum, cms, ceo, admin';
	
	
	/**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'forum/tpl/SingleBoard.shtml';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar(50)', 'caption=Наименование, mandatory, notNull, width=400px');
		$this->FLD('shortDesc', 'varchar(100)', 'caption=Oписание, mandatory, notNull, width=100%');
		$this->FLD('category', 'key(mvc=forum_Categories,select=title,groupBy=type)', 'caption=Категория, mandatory');
		$this->FLD('canSeeBoard', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Роли за достъп->Дъска, mandatory');
		$this->FLD('canSeeThemes', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Роли за достъп->Теми, mandatory');
		$this->FLD('canStick', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Роли за достъп->Важни теми, mandatory');
		$this->FLD('canComment', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Роли за достъп->Коментиране, mandatory');
		$this->FLD('themesCnt', 'int', 'caption=Темите, notNull, input=hidden, value=0');
		$this->FLD('commentsCnt', 'int', 'caption=Коментари, notNull, input=hidden, value=0');
		$this->FLD('lastComment', 'datetime(format=smartTime)', 'caption=Последно->кога, input=none');
		$this->FLD('lastCommentBy', 'int', 'caption=Последно->кой, input=none');
		$this->FLD('lastCommentedTheme', 'int', 'caption=Последно->къде, input=none');
		$this->FLD('supportBoard', 'enum(FALSE=Не,TRUE=Да)', 'caption=Support дъска ?, notNull, value=FALSE');
		$this->setDbUnique('title');
	}
	
	
	/**
	 * Подрежане  и филтриране на дъските по категории
	 */ 
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		if($category = Request::get('category')) {
			$data->query->where("#category = {$category}");
		}
		
		$data->query->orderBy('#category');
	}
	
	
	/**
	 * Обновяваме броя на темите и коментарите на дъската. Обновяваме кой, къде и
	 * кога е направил последния коментар
	 */
	static function updateBoard($id)
	{
		// Заявка за работа с темите от дъската
		$themesQuery = forum_Postings::getQuery();
		$themesQuery->where("#boardId = {$id} AND #themeId IS NULL");
		
		// Заявка за работа с коментарите от дъската
		$commentsQuery = forum_Postings::getQuery();
		$commentsQuery->where("#boardId = {$id} AND #themeId IS NOT NULL");
		
		// Извличане на последния коментар в дъската
		$commentsQuery->XPR('maxId', 'int', 'max(#id)');
		try{
			// Намираме постинга който е последния коментар в дъската (този с най-голямо ид)
			$lastComment = forum_Postings::fetch($commentsQuery->fetch()->maxId); 
		} catch (core_exception_Expect $e) {
			
			// В случай че дъската няма коментари задаваме на $lastComment стойност NULL
			$lastComment = NULL;
		}
		
		// Дъската в която ще обновяваме информацията
		$rec = static::fetch($id);
		
		// Броя на постингите, които са теми
		$rec->themesCnt = $themesQuery->count();
		
		// Броя на постингите, които са коментари
		$rec->commentsCnt = $commentsQuery->count();
		
		// Ако има коментар в дъската, ние обновяваме кой, кога и къде го е направил
		if($lastComment) {
		
			$rec->lastCommentedTheme = $lastComment->themeId;
			$rec->lastComment = $lastComment->createdOn;
		    $rec->lastCommentBy = $lastComment->createdBy;
		}
		
	    // Обновяваме дъската
	    static::save($rec);
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
        $data->action = 'forum';
        $data->display ='public';
        $data->category = Request::get('cat');
        
        // Подготвяме необходимите данни за показване на дъските
        $this->prepareForum($data);
        
        // Рендираме Дъските в форума
        $layout = $this->renderForum($data);
       
        $layout->push($data->forumTheme . '/styles.css', 'CSS');
        
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
			$data->listUrl = array($this, 'list');
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
		$data->navigation[] = ht::createLink('Форуми', array('forum_Boards', 'Forum'));
		 if($data->action == 'forum'){
		 	if(isset($data->category)){
		 		
				// Ако е сетнато $data->category, то е избрана само една категория
				$categoryRec = forum_Categories::fetch($data->category);
				$categoryRow = forum_Categories::recToVerbal($categoryRec, "id,title,-public");
				$data->navigation[] = $categoryRow->title;
		 	}
		 } elseif($data->action == 'browse' || $data->action == 'new') {
			 
		 	// Ако разглеждаме дъска,навигацията ще от рода  Форуми->Категория->Дъска
			$row = $this->recToVerbal($data->rec, "id,title,category,-public");
			$data->navigation[] = $row->category->title;
			$data->navigation[] = $row->title;
			 
		}  elseif ($data->action == 'theme') {
			
			// Ако разглеждаме тема,навигацията ще от рода  Форуми->Категория->Дъска->Тема
			$board = $this->recToVerbal($data->board, "id,title,category,-public");
			$theme = forum_Postings::recToVerbal($data->rec, "id,title,-public");
			$data->navigation[] = $board->category->title;
			$data->navigation[] = $board->title;
			$data->navigation[] = $theme->title;
		} 
	}
	
	
	/**
	 * Подготвя навигацията за вътрешния изглед
	 */
	function prepareInnerNavigation($data)
	{
		// Линк към началото на форума
		$data->navigation[] = ht::createLink('Форуми', array('forum_Boards', 'list'));
		if($data->action == 'single') {
			 
		 	 // Ако разглеждаме дъска,навигацията ще от рода  Форуми->Категория->Дъска
			$row = static::recToVerbal($data->rec, "id,title,category,-private");
			$data->navigation[] = $row->category->title;
			$data->navigation[] = $row->title;
			 
		}  elseif ($data->action == 'topic') {
			
			// Ако разглеждаме тема,навигацията ще от рода  Форуми->Категория->Дъска->Тема
			$board = $this->recToVerbal($data->board, "id,title,category,-private");
			$theme = forum_Postings::recToVerbal($data->rec, "id,title,-private");
			$data->navigation[] = $board->category->title;
			$data->navigation[] = $board->title;
			$data->navigation[] = $theme->title;
		} 
	}
	
	
	/**
	 * Добавяме всеки елемент на в последователност от линкове
	 */
	function renderNavigation($data)
	{
		for($i=0; $i<count($data->navigation); $i++) {
			$navigation .= $data->navigation[$i];
			if($i < count($data->navigation) - 1) {
				$navigation  .= "&nbsp;»&nbsp;";
			}
		}
		
		if($data->display) {
			Mode::set('wrapper', 'cms_tpl_Page');
		
		    // Добавяме лейаута на страницата
		    Mode::set('cmsLayout', $data->forumTheme . '/Layout.shtml');
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
		$fields = $this->selectFields("");
		$fields['-forum'] = TRUE;
		while($rec = $query->fetch()) {
			
		// Ако имаме права да виждаме дъските, ние ги подготвяме 
		if($this->haveRightFor('read', $rec)){
				$category->boards->recs[$rec->id] = $rec;
	 			$category->boards->rows[$rec->id] = $this->recToVerbal($rec, $fields);
	 			$url = array('forum_Boards', 'Browse', $rec->id);
	            
	            // Правим заглавието на дъската, като линк
	            $category->boards->rows[$rec->id]->title = ht::createLink($category->boards->rows[$rec->id]->title, $url);
	 			
	             if((bool)$rec->supportBoard){
		        	if(!static::haveRightFor('read')) {
		        		
		        		// Ако дъската е съпорт, преброяваме темите създадени от текущия потребител
		        		$query = forum_Postings::getQuery();
		        		$query->where("#boardId = {$rec->id} AND #themeId IS NULL");
		        		$query->where("#createdBy = " . core_users::getCurrent() . "");
		        		$category->boards->rows[$rec->id]->themesCnt = $query->count();
		        	}
        		}
	            
	      }
		}
	}
	
	
	/**
	 *  Рендираме списъка с дъските групирани по категории
	 */
	function renderForum($data)
	{
		$tpl = new ET(getFileContent($data->forumTheme . '/Index.shtml'));
 
        if(count($data->categories)) {
        	
        	// Зареждаме шаблоните веднъж в паметта и после само ги клонирваме
        	$categoryTpl = new ET(getFileContent($data->forumTheme . '/Category.shtml'));
        	$boardTpl = new ET(getFileContent($data->forumTheme . '/Boards.shtml'));
            
        	foreach($data->categories as $category) {
                
                // За всяка категория ние поставяме името и преди  списъка с нейните дъски
                $catTpl = clone($categoryTpl);
                $catTpl->replace($category->title, 'cat');
                if($category->boards->rows) { 
                    
                    // За всички дъски от категорията ние ги поставяме под нея в шаблона
                    foreach($category->boards->rows as $row) {
                    	$rowTpl = clone($boardTpl);
                        $rowTpl->placeObject($row);
                        $rowTpl->removeBlocks();
                    	$catTpl->append($rowTpl, 'BOARDS');
                    }
                } else {
                       $catTpl->replace(new ET('<li>Няма Дъски</li>'), 'BOARDS');
                    }

                // Добавяме категорията с нейните дъски към главния шаблон
                $tpl->append($catTpl, 'CATEGORIES');
            }
        }
		
		if($data->listUrl) { 
			$tpl->append(ht::createBtn('Работилница', $data->listUrl, NULL, NULL, 'ef_icon=img/16/application_edit.png'), 'TOOLBAR');
		}
		
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
        $data->display ='public';
        expect($data->rec = $this->fetch($id));
		
		// Изискваме потребителя да има права да вижда  дъската
		$this->requireRightFor('read', $data->rec);
		
		// Подготвяме информацията нужна за преглеждане на дъската
		$this->prepareBrowse($data);
		
		// Рендираме разглежданата дъска
		$layout = $this->renderBrowse($data);
		
		$layout->push($data->forumTheme . '/styles.css', 'CSS');
        
        $layout->replace($this->renderNavigation($data), 'NAVIGATION');
		
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
			$tpl->append(ht::createBtn('Работилница', $data->singleUrl, NULL, NULL , 'ef_icon=img/16/application_edit.png'), 'TOOLBAR');
		}
		
		return $tpl;
	}
	
	
	/**
     * Бутон за преглед на дъските във външен изглед
     */
	static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	 $data->toolbar->addBtn('Преглед', array($this, 'Forum'));
    }
 	
    
    /**
     * Бутон за преглед на дъската във външен изглед
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
		 if ($mvc->haveRightFor('article', $data->rec)) {
            $data->toolbar->addBtn('Преглед', array($this, 'Browse', $data->rec->id));
        }
    }
    
    
    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if($action == 'read' && isset($rec)) {
			 
			// Могат да виждат дъските, единствено потребителите с роли, зададени в 'canSeeBoard' 
			$res = $mvc::getVerbal($rec, 'canSeeBoard'); 
		}
		
		if($action == 'add' && isset($rec)) {
			 
			$res = $mvc::getVerbal($rec, 'canSeeBoard'); 
		}
		
		if($action == 'write' && isset($rec)) {
			 
			$res = $mvc::getVerbal($rec, 'canStick'); 
		}
	}
	
	
	/**
	 * Модификация на вербалните записи
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
   	{
   		if($fields['-list']) {
   			
   			// Правим заглавието на линк за единичен изглед
   			$row->title = ht::createLink($row->title, array($mvc, 'Single', $rec->id));
   			
   			if(!$rec->lastCommentedTheme) {
   				$row->lastCommentedTheme = 'няма';
   			}
   			
   			if(!$rec->lastComment) {
   				$row->lastComment = 'няма';
   			}
   			
   			if($rec->lastCommentedTheme) {
   				$themeRec = forum_Postings::fetch($rec->lastCommentedTheme);
   				$themeRow = forum_Postings::recToVerbal($themeRec, 'id,title,-list');
   				$row->lastCommentedTheme = $themeRow->title;
   			}
   		}
   		
   		// Модификации по вербалното представяне на записите  в екшъна forum
   		if($fields['-forum']) {
   			
   			// Ако темата има последен коментар
   			if($rec->lastCommentBy) {
	            
	           // преобразуваме ид-то на последно коментираната тема в разбираем вид
	           $themeRec = forum_Postings::fetch($rec->lastCommentedTheme);
	           $themeRow = forum_Postings::recToVerbal ($themeRec, 'id,title,-forum');
	           $row->lastCommentedTheme = $themeRow->title;
	           
	           // Намираме граватара и ника на потребителя коментирал последно
	           $lastUser =core_Users::fetch($rec->lastCommentBy);
	           $row->lastAvatar =  avatar_Plugin::getImg(0, $lastUser->email, 50);
	           $row->lastNick = $lastUser->nick;
	       } else {
	          ($rec->themesCnt == 0) ? $str = 'форума е празен' : $str ='няма коментари';
	           $row->noComment = $str;
	        }
   		}
   		
   		// Превръщане на името на дъската и категорията линкове за външен изглед, 
   		// ако се изисква за подготовка при навигацията
   		if($fields['-public']) { 
   			$row->title = ht::createLink($row->title, array($mvc, 'Browse', $rec->id));
   			$categoryRec = forum_Categories::fetch($rec->category);
   			$row->category =  forum_Categories::recToVerbal($categoryRec, 'id,title,-public');
   		}
   		
   		// Превръщане на името на дъската и категорията линкове за вътрешен изглед, 
   		// ако се изисква за подготовка при навигацията
   		if($fields['-private']) { 
   			$row->title = ht::createLink($row->title, array($mvc, 'Single', $rec->id));
   			$categoryRec = forum_Categories::fetch($rec->category);
   			$row->category =  forum_Categories::recToVerbal($categoryRec, 'id,title,-list');
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
     * Рендираме навигацията след рендирането на обвивката
     */
    function on_AfterRenderWrapping($mvc, &$tpl, $content, $data = NULL) {
    	
    	if($data->navigation){
    		$tpl->replace($this->renderNavigation($data), 'NAVIGATION');
    	}
     }
}