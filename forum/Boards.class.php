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
	var $listFields ='tools, title, category, shortDesc, themesCnt, canSeeBoard, canSeeThemes, canComment,lastComment,lastCommentedTheme,createdOn,createdBy,  modifiedOn, modifiedBy';
	
	
	/**
	 * Коментари на статията
	 */
	var $details = 'forum_Postings';
	
	
	/**
	 * Кой може да листва дъските
	 */
	var $canRead = 'cms, ceo, admin';
	
	
	/**
	 * Кой може да добявя,редактира или изтрива дъска
	 */
	var $canWrite = 'cms, ceo, admin';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar(50)', 'caption=Наименование, mandatory, notNull,width=400px');
		$this->FLD('shortDesc', 'varchar(100)', 'caption=Oписание, mandatory, notNull,width=100%');
		$this->FLD('category', 'key(mvc=forum_Categories,select=title,groupBy=type)', 'caption=Категория на дъската,mandatory');
		$this->FLD('canSeeBoard', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Роли за достъп->Дъска,mandatory');
		$this->FLD('canSeeThemes', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Роли за достъп->Теми,mandatory');
		$this->FLD('canComment', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Роли за достъп->Коментиране,mandatory');
		$this->FLD('themesCnt', 'int', 'caption=Брой на темите,notNull,input=hidden,value=0');
		$this->FLD('commentsCnt', 'int', 'caption=Брой на Коментарите,notNull,input=hidden,value=0');
		$this->FLD('lastComment', 'datetime(format=smartTime)', 'caption=Последно->кога, input=none');
		$this->FLD('lastCommentBy', 'int', 'caption=Последно->кой, input=none');
		$this->FLD('lastCommentedTheme', 'varchar(100)', 'caption=Последно->къде, input=none');
		$this->setDbUnique('title');
	}
	
	
	/**
	 * Подрежане на дъските по категории
	 */ 
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		$data->query->orderBy('#category');
	}
	
	
	/**
	 *  Обновява броя на темите в дъската
	 */
	static function updateThemesCount($id)
	{
	    $query = forum_Postings::getQuery();
	    // Преброяваме тези постинги, които принадлежат на дъската и са начало на
	    // нова тема (themeId е NULL)
	    
	    $query->where("#boardId = {$id} AND #themeId IS NULL");
	    $rec = static::fetch($id);
	    $rec->themesCnt = $query->count();
	    static::save($rec);
	}
	
	
	/**
	 * Обновяваме, къде и кога е публикуван последния коментар в дъската , както и броя на
	 * всички коментари в дъската
	 */
	static function updateLastComment($id, $date, $by, $theme)
	{
		$query = forum_Postings::getQuery();
		$query->where("#boardId = {$id} AND #themeId IS NOT NULL");
		$rec = static::fetch($id);
		
		// Броя на всички коментари в дъската
		$rec->commentsCnt = $query->count();
		
		// Коя е последно коментираната тема
		$rec->lastCommentedTheme = $theme;
		
		// Кога е направен последния коментар
	    $rec->lastComment = $date;
	    
	    // Кой е направил последния коментар
	    $rec->lastCommentBy = $by;
	    
	    // Ъпдейтваме записа
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
		
		// Подготвяме навигационните линкове
	    $this->prepareNavigation($data);
	}
	
	
	/**
	 * Подготвяме, навигационните линкове за бърз достъп до избраната категория/дъска/тема
	 * в навигационното поле на форума
	 */
	function prepareNavigation($data){
		// Линк към началото на форума
		$data->navigation[] = ht::createLink('Форуми', array('forum_Boards', 'Forum'));
		 if($data->action == 'forum'){
		 	if(isset($data->category)){
		 		
		 		// Ако е сетнато $data->category, то е избрана само една категория
		 		$categoryUrl =  array('forum_Boards', 'Forum', 'cat' => $data->category);
		 		$category = forum_Categories::fetch($data->category);
		 		$data->navigation[]= ht::createLink(forum_Categories::getVerbal($category, 'title'), $categoryUrl);
		 	}
		 } elseif($data->action == 'browse') {
			
			 // Ако разглеждаме дъска,навигацията ще от рода  Форуми->Категория->Дъска, като
			 // всяко едно от тях ще е линк към началото, категорията и дъската
			 $categoryUrl =  array('forum_Boards', 'Forum', 'cat' => $data->rec->category);
			 $boardUrl =  array('forum_Boards', 'Browse', $data->row->id);
			 $data->navigation[]= ht::createLink($data->row->category, $categoryUrl);
			 $data->navigation[]= ht::createLink($data->row->title, $boardUrl);
		}  elseif ($data->action == 'theme') {
			
			 // Ако разглеждаме тема,навигацията ще от рода  Форуми->Категория->Дъска->Тема,
			 // като всяко едно от тях ще е линк към началото, категорията, дъската и темата
			 $board = $this->recToVerbal($data->board);
			 $boardUrl = array('forum_Boards', 'Browse', $board->id);
			 $categoryUrl =  array('forum_Boards', 'Forum', 'cat' => $data->board->category);
			 $themeUrl = array('forum_Postings', 'Theme', $data->rec->id);
			 $data->navigation[] = ht::createLink($board->category, $categoryUrl);
			 $data->navigation[] = ht::createLink($board->title, $boardUrl);
			 $data->navigation[] = ht::createLink($data->rec->title, $themeUrl);
		}
	}
	
	
	/**
	 * Добавяме всеки елемент на в последователност от линкове
	 */
	function renderNavigation($data){
		foreach($data->navigation as $link){
			$navigation .=  $link . "&nbsp;»&nbsp;";
		}
		Mode::set('wrapper', 'cms_tpl_Page');

        // Добавяме лейаута на страницата
        Mode::set('cmsLayout', $data->forumTheme . '/Layout.shtml');
        
	 	
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
		while($rec = $query->fetch()) {
			
		// Ако имаме права да виждаме дъските, ние ги подготвяме 
		if($this->haveRightFor('read', $rec)){
				$category->boards->recs[$rec->id] = $rec;
	 			$category->boards->rows[$rec->id] = $this->recToVerbal($rec, $fields);
	 			$url = array('forum_Boards', 'Browse', $rec->id);
	            
	            // Правим заглавието на дъската, като линк
	            $category->boards->rows[$rec->id]->title = ht::createLink($category->boards->rows[$rec->id]->title, $url);
	 		
	            if(isset($rec->lastCommentBy)) {
	            
	            	// извличаме данните на потребителят направил последния коментар
	            	$lastUser =core_Users::fetch($rec->lastCommentBy);
	            
	            	// Намираме аватара спрямо имейла на потребителя
	            	$category->boards->rows[$rec->id]->lastAvatar =  avatar_Plugin::getImg(0,$lastUser->email, 50);
	            	
	            	// Намираме ника на потребителя направил последния коментар
	            	$category->boards->rows[$rec->id]->lastNick = $lastUser->nick;
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
		foreach($data->categories as $category) {
			
			// За всяка категория ние поставяме името и преди  списъка с нейните дъски
			$catTpl = new ET(getFileContent($data->forumTheme . '/Boards.shtml'));
			$catTpl->replace($category->title,'cat');
			if($category->boards->rows) { 
				// За всички дъски от категорията ние ги поставяме под нея в шаблона
				foreach($category->boards->rows as $row) {
					$rowTpl = $catTpl->getBlock('ROW');
					$rowTpl->placeObject($row);
					$rowTpl->append2master();
				}
			} 	else {
            		$rowTpl = $catTpl->getBlock('ROW');
            		$rowTpl->replace('<li>Няма Дъски</li>');
            		$rowTpl->append2master();
        		}
        	// Добавяме категорията с нейните дъски към главния шаблон
			$tpl->append($catTpl, 'BOARDS');
		}
		
		// рендиране на навигацията
		$tpl->replace($this->renderNavigation($data), 'NAVIGATION');
        
		// Връщаме шаблона с всички дъски групирани по категории
		return $tpl;
	}
	
	
	/**
	 * Екшън за преглеждане на темите в една дъска
	 */
	function act_Browse() 
	{
		// Ид-то на дъската, която разглеждаме	
		$id = Request::get('id', 'int');

		$data = new stdClass();
		$data->query = $this->getQuery();
		
		// Тема по подразбиране
		$conf = core_Packs::getConfig('forum');
        $data->forumTheme = $conf->FORUM_DEFAULT_THEME;
        $data->action = 'browse';
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
        $data->row = $this->recToVerbal($data->rec, $fields);
		
        // Извличаме всички Постинги, които са начало на нова тема в дъската
        $this->forum_Postings->prepareBoardThemes($data);
        $this->prepareNavigation($data);
    }
	// TODO action za mestene na tema Move, s prava forum
	// dyska za temite koito ti si zadal. paraeter na duskata support desk
	
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
			
			// Добавяме бутон за добавяне на нова тема, ако имаме права
			$tpl->append(ht::createBtn('Нова Тема', $data->submitUrl), 'TOOLBAR');
		}
		
		return $tpl;
	}
	
 
    
    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if($action == 'read' && isset($rec)) {
			
			// Могат да виждат дъските, единствено потребителите с роли, които са
			// зададени в полето 'canSeeBoard' от дъската
			$res = $mvc::getVerbal($rec, 'canSeeBoard');
		}
		
	}

    
    /**
     * Връща URL към себе си (блога)
     */
    function getContentUrl($cMenuId)
    {
        return array('forum_Boards', 'forum');
    }

}