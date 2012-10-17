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
	var $title = 'Дъски';
	
	
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
	var $listFields ='tools, title, shortDesc, author, themesCnt, canSeeBoard, canSeeThemes, canComment,lastComment,createdOn,createdBy,  modifiedOn, modifiedBy';
	
	
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
		$this->FLD('title', 'varchar(50)', 'caption=Наименование, mandatory, notNull,width=100%');
		$this->FLD('author', 'varchar(50)', 'caption=Автор, mandatory, notNull,width=100%');
		$this->FLD('shortDesc', 'varchar(100)', 'caption=Кратко описание, mandatory, notNull,width=100%');
		$this->FLD('canSeeBoard', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Кой може да вижда дъската,mandatory');
		$this->FLD('canSeeThemes', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Кой може да вижда темите,mandatory');
		$this->FLD('canComment', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Кой може да коментира,mandatory');
		$this->FLD('themesCnt', 'int', 'caption=Брой на темите,value=0,input=hidden,width=100%');
		$this->FLD('lastComment', 'datetime(format=smartTime)', 'caption=Последен Коментар, input=none,width=100%');
		$this->setDbUnique('title');
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
	 * Обновява, кой е последния коментар в тема от дъската
	 */
	static function updateLastComment($id, $date)
	{
		$rec = static::fetch($id);
	    $rec->lastComment = $date;
	    static::save($rec);
	}
	
	
	/**
	 * Екшън за преглеждане на всички дъски
	 */
	function act_Boards()
	 {
		// Създаваме празен $data обект
		$data = new stdClass();

        // Създаваме заявка към модела
		$data->query = $this->getQuery();
		
		// Тема по подразбиране
		$conf = core_Packs::getConfig('forum');
        $data->forumTheme = $conf->FORUM_DEFAULT_THEME;
        
        // Подготвяме необходимите данни за показване на дъските
        $this->prepareBoards($data);
        
        // Рендираме Дъските в форума
        $layout = $this->renderBoards($data);
       
        $layout->push($data->forumTheme . '/styles.css', 'CSS');
        
        $layout = $this->renderWrapping($layout);
        
        return $layout;
	}
	
	
	/**
	 *  Подготовка на списъка с дъски
	 */
	function prepareBoards(&$data)
	{
		$data->query->orderBy('createdOn', 'ASC');
		$fields = $this->selectFields("");
	 	while($rec = $data->query->fetch()) {
	 		if($this->haveRightFor('read', $rec)){
	 			
	 			// Ако потребителят може да вижда дъската, ние я добавяме в списъка
	            $data->recs[$rec->id] = $rec;
	            $data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
	            $url = array('forum_Boards', 'Browse', $rec->id);
	            
	            // Правим заглавието на дъската, като линк
	            $data->rows[$rec->id]->title = ht::createLink($data->rows[$rec->id]->title, $url);
		   	}
	   }
	}
	
	
	/**
	 *  Рендиране на списъка с дъските
	 */
	function renderBoards($data)
	{
		$tpl = new ET(getFileContent($data->forumTheme . '/Boards.shtml'));
		
		// Ако имаме дъски, то ние ги рендираме
		if(count($data->rows)) {
	            foreach($data->rows as $row) {
	                $rowTpl = $tpl->getBlock('ROW');
	                $rowTpl->placeObject($row);
	                $rowTpl->append2master();
	            }
	     }  else {
            $rowTpl = $tpl->getBlock('ROW');
            $rowTpl->replace('<h2>Няма Дъски</h2>');
            $rowTpl->append2master();
        }
        
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
			
			// Добавяме бутон за добавяне на нова тема, ако имаме права
			$tpl->append(ht::createBtn('Нова Тема', $data->submitUrl), 'TOOLBAR');
		}
		
		return $tpl;
	}
	
	
	/**
	 * Задаване на име на автора по подразбиране и броя на темите в дъската да е 0
	 */
	static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$data->form->setDefault('author', core_Users::getCurrent('nick'));
    	$data->form->setHidden('themesCnt', '0');
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
}