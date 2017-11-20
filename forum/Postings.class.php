<?php



/**
 * Постинги
 *
 *
 * @category  bgerp
 * @package   forum
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class forum_Postings extends core_Detail {
	
	
	/**
	 * Заглавие на страницата
	 */
	public $title = 'Постинги';

	
	/**
	 * Единично заглавие
	 */
	public $singleTitle = 'Постинг';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	public $loadList = 'plg_RowTools2, plg_Created, plg_Modified, forum_Wrapper, plg_Search';
	
	
	/** 
	 *  Полета по които ще се търси
	 */
	public $searchFields = 'title, body';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'forum, ceo, admin, cms';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'forum, ceo, admin, cms';

	
	/**
	 * Полета за изглед
	 */
	public $listFields = 'id, title, type, boardId, postingsCnt, views, last, lastWho, createdBy, createdOn';
	
	
	/**
	 *  Брой теми на страница
	 */
	public $listItemsPerPage = "30";
	
	
	/**
	 * Мастър ключ към дъските
	 */
	public $masterKey = 'boardId';
	
	
	/**
	 * Кой може да листва дъските
	 */
	public $canRead = 'forum, cms, ceo, admin';
	
	
	/**
	 * Кой може да добявя,редактира или изтрива дъска
	 */
	public $canWrite = 'forum, admin, cms, user, ceo';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('boardId', 'key(mvc=forum_Boards, select=title)', 'caption=Дъска, input=hidden, silent');
		$this->FLD('title', 'varchar(190)', 'caption=Заглавие, mandatory, placeholder=Заглавие');
		$this->FLD('body', 'richtext(bucket=Notes)', 'caption=Съдържание, mandatory, placeholder=Добавете вашия коментар');
		$this->FLD('type', 'enum(normal=Нормална,sticky=Важна,announcement=Съобщение)', 'caption=Тип, value=normal');
		$this->FLD('postingsCnt', 'int', 'caption=Коментари, input=none, value=0');
		$this->FLD('views', 'int', 'caption=Прегледи, input=none, value=0');
		$this->FLD('status', 'enum(unlocked=Отключено, locked=Заключено)', 'caption=Състояние, value=unlocked');
		$this->FLD('last', 'datetime(format=smartTime)', 'caption=Последно->Кога, input=none');
		$this->FLD('lastWho', 'int', 'caption=Последно->Кой, input=none');
		$this->FLD('themeId', 'int', 'caption=Тема, input=none');
	}

	
	/**
	 *  Скриване на полето за тип на темата, ако няма права потребителя
	 */
	static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	expect($boardRec = $mvc->Master->fetch($data->form->rec->boardId));
    	$boardRow = $mvc->Master->recToVerbal($boardRec, 'title');
    	
    	// Проверяваме дали можем да правим важни теми, както и да ги заключваме
	    if(!$mvc->haveRightFor('write', $data->form->rec)) {
    		$data->form->setField('type', 'input=none');
    		$data->form->setField('status', 'input=none');
    	}
		
    	$data->form->title = tr("Започване на нова тема в") . " <b>{$boardRow->title}</b>";
    	
    	// Ако постинга е коментар
    	if($themeId = Request::get('themeId', 'int')) {
    		
    		expect($themeRec = static::fetch($themeId));
    		$themeRow = $mvc->Master->recToVerbal($themeRec, 'id,title');
    		
    		// Трябва да имаме права да коментираме темата
    		static::requireRightFor('add', $themeRec);
    		
    		$data->form->setField('type', 'input=none');
	    	$data->form->setField('title', 'input=none');
	    	$data->form->setField('status', 'input=none');
	    	$data->form->setHidden('themeId', $themeRow->id);
	    	$data->form->title = tr("Добавяне на коментар в") . " <b>{$themeRow->title}</b>";
	    }
	 }
	
    
	/**
	 *  Подготовка на списъка от темите от дъската
	 */
	function prepareBoardThemes_($data)
	{
		// Избираме темите, които са начало на нова нишка от дъската
		$query = $this->getQuery();
        $query->where("#boardId = {$data->rec->id} AND #themeId IS NULL");
        
        // Подреждаме темите в последователност: Съобщение, Важна, Нормална
        $query->orderBy('type, createdOn', 'DESC');
        
		// Пейджър на темите на дъската, лимита е дефиниран в FORUM_THEMES_PER_PAGE
        $conf = core_Packs::getConfig('forum');
		$data->pager = cls::get('core_Pager', array('itemsPerPage' => $conf->FORUM_THEMES_PER_PAGE));
        $data->pager->setLimit($query);
        $fields = $this->selectFields("");
        $fields['-browse'] = TRUE;
        
        if($this->Master->haveRightFor('read', $data->rec)) {
        	
        	// Ако имаме права да виждаме темите в дъската, ние ги извличаме
	        while($rec = $query->fetch()) {
	        	
	        	$data->themeRecs[$rec->id] = $rec;
	            $data->themeRows[$rec->id] = $this->recToVerbal($rec, $fields);
	            
	           	// Заявка за работа с темата
	            $themeQuery = $this->getQuery();
	            $themeQuery->where("#themeId = {$rec->id}");
	           
	            if($rec->postingsCnt > $conf->FORUM_POSTS_PER_PAGE) {
		            
		            // Подготвяме пейджъра на темата ако има достатъчно коментари
		            $data->themeRows[$rec->id]->pager = cls::get('core_Pager', array('itemsPerPage' => $conf->FORUM_POSTS_PER_PAGE));
		            $data->themeRows[$rec->id]->pager->setLimit($themeQuery);
	            }
	      }
        }
        
        // Ако имаме права да добавяме нова тема в дъската
        if($this->haveRightFor('write')) {
        	$data->submitUrl = array($this, 'new', $this->masterKey => $data->rec->id);
        }
        
        // Ако имаме права за Single
        if($this->haveRightFor('single')) {
        	$data->singleUrl = array($this->Master, 'single', $data->rec->id);
        }
    }
    
    
    /**
	 * Рендиране на списъка от теми, принадлежащи на дъската, които са начало на
	 * нова нишка
	 */
    function renderBoardThemes_($data, $layout)
	{
		$tpl = $data->ForumTheme->getThemeLayout();
		
		// Иконките на отключените и заключените теми взети от текущата тема
		$openIcon = $data->ForumTheme->getImage('forum-theme.png', '32');
		$lockedIcon = $data->ForumTheme->getImage('locked.png', '32');
		
		// Ако имаме теми в дъската ние ги рендираме
		if(count($data->themeRows)) {
	      foreach($data->themeRows as $row) {
	      		$themeTpl = $tpl->getBlock('ROW');
	         	$themeTpl->placeObject($row);
	         	
	         	// Добавяме иконката взависимост дали темата е заключена/отключена
	         	($row->locked == "заключена") ? $icon = $lockedIcon : $icon = $openIcon;
	         	$themeTpl->replace($icon, 'ICON');
	         	
	         	// Адреса на темата, която ще отваря темата
	         	$pagerUrl = toUrl(array('forum_Postings', 'Theme', $row->id), 'relative');
	         	if($row->pager) {
		         	
	         		// Рендираме пейджъра на темата до заглавието и
		         	$themeTpl->replace($row->pager->getHtml($pagerUrl), 'THEME_PAGER');
	         	}
	         	
	         	$themeTpl->removeBlocks();
	         	$themeTpl->append2master();
	         } 
        } else {
            $tpl->replace("<div class='no-boards'>" . tr("Няма теми") . "</div>");
          }
        
         $layout->replace($tpl, 'THEMES');
         $layout->replace($data->pager->getHtml(), 'PAGER');
         
         return $layout;
	}
	
	
	/**
	 * Екшън, който показва постингите от една тема в хронологичен ред. Началото на
	 * една тема я поставя постинг с themeId = NULL, а постингите добавни след него
	 * към темата имат за themeId  ид-то на мастър постинга
	 */
	function act_Theme()
	{
		$id = Request::get('id', 'int');
		if(!$id) {
            expect($id = Request::get('themeId', 'int'));
        }
		
		$data = new stdClass();
		expect($data->rec = $this->fetch($id));
		$data->query = $this->getQuery();
        $data->ForumTheme = forum_Boards::getThemeClass();
        $data->action = 'theme';
        $data->display = 'public';
        
        // Към коя дъска и категория принадлежи темата
		$data->board = $this->Master->fetch($data->rec->boardId);
		$data->category = forum_Categories::fetch($data->board->category);
		
		// Потребителят трябва да има права да чете темите от дъската
		$this->requireRightFor('read', $data->rec);
		
		// Подготвяме постингите от избраната тема
		$this->prepareTheme($data);
		
		// Ако имаме форма за добавяне на нов постинг към темата
		if($data->postForm) {
        
            // Зареждаме REQUEST данните във формата за коментар
            $rec = $data->postForm->input();
            
            // Трябва да имаме права да добавяме постинг към тема от дъската
            $this->requireRightFor('add', $data->rec);
            
            // Ако формата е успешно изпратена - запис, лог, редирек
            if ($data->postForm->isSubmitted() && Request::get('body')) {
            	$id = $this->save($rec);
                $this->logWrite('add', $id);
                
                return new Redirect(array('forum_Postings', 'Theme', $data->rec->id));
            }
		}
		
		// Рендираме темата
		$layout = $this->renderTheme($data);
		
		// Записваме, че темата е посетена в лога
		if(core_Packs::fetch("#name = 'vislog'")) {
            $cnt = vislog_History::add($data->row->title, TRUE);
            
            // Обновяваме посещенията на темата, ако е направено уникално посещение
            if($cnt) {
            	$this->updateThemeViews($data->rec, $cnt);
            }
        }
        
		return $layout;
	}

	
	/**
	 * Подготовка на Постингите от нишката, и формата за коментар
	 */
	function prepareTheme_($data)
	{
		$query = $this->getQuery();
		$fields = $this->selectFields("");
        $fields['-theme'] = TRUE;
        $data->row = $this->recToVerbal($data->rec, $fields);
        
        // Избираме темите, които принадлежът към темата
        $query->where("#themeId = {$data->rec->id}");
        
        // Подготвяме пагинатора на темите
        $conf = core_Packs::getConfig('forum');
		$data->pager = cls::get('core_Pager', array('itemsPerPage' => $conf->FORUM_POSTS_PER_PAGE));
        $data->pager->setLimit($query);
        
        // Извличаме всички постинги направени относно темата
		while($rec = $query->fetch()) {
			
			// Добавяме другите постинги, които имат за themeId, id-то на темата
			$data->thread[$rec->id] = $this->recToVerbal($rec, $fields);
		}
        
		$data->title = "<h3>{$data->row->title}</h3>";
		
		// Ако можем да добавяме нов постинг в темата и тя е отключена
		if($this->haveRightFor('add', $data->rec->id)) {
			
			// Подготвяме формата за добавяне на нов постинг към нишката
			$data->postForm = $this->getForm();
			$data->postForm->setField('title', 'input=none');
			$data->postForm->setField('type', 'input=none');
			$data->postForm->setField('status', 'input=none');
			$data->postForm->setHidden('themeId', $data->rec->id);
			$data->postForm->setHidden('boardId', $data->rec->boardId);
			$data->postForm->toolbar->addSbBtn('Добави', 'default', 'class=forumbtn addComment');
			
			// Котва към формата за коментар
			$data->formAnchor =  array($this, 'Theme', $data->rec->id, '#'=>'comment');
		}
		
		if($this->haveRightFor('single')) {
			
			// Линк за вътрешен преглед на темата
			$data->topicUrl = array($this, 'Topic', $data->rec->id);
		}
		
		// Подготвяме навигацията
		$data->navigation = $this->Master->prepareNavigation($data->board->category, $data->rec->boardId, $data->rec->id, $data->display);
	}
	
	
	/**
	 * Рендираме темата
	 */
	function renderTheme_($data)
	{
		$tpl = $data->ForumTheme->getSingleThemeLayout();
		$commentTpl = $data->ForumTheme->getCommentsLayout();
		$tpl->replace($data->title, 'THREAD_HEADER');
		$tpl->placeObject($data->row);
		
		// Ако имаме теми в нишката, ние ги рендираме
		if(count($data->thread)){
			foreach($data->thread as $row) {
				$rowTpl = clone($commentTpl);
				$rowTpl->placeObject($row);
				$rowTpl->removeBlocks();
	            $tpl->append($rowTpl, "COMMENTS");
			}
		}
		
		// Рендираме пагинаторът
        $tpl->replace($this->renderListPager($data), 'PAGER');
		
		// Ако имаме право да добавяме коментар рендираме формата в края на нишката
		if($data->postForm) {
			$data->postForm->layout = $data->ForumTheme->getPostFormLayout();
			$data->postForm->fieldsLayout = $data->ForumTheme->getPostFormFieldsLayout();
			$tpl->replace($data->postForm->renderHtml(), 'COMMENT_FORM');
        } else {
        	(core_Users::getCurrent()) ? $msg = 'Темата е заключена' : $msg = 'За коментар е нужна регистрация !!!';
        	$tpl->replace("<p>" . tr($msg) . "</p>", 'COMMENT_FORM');
          }
		
        if($data->formAnchor) {
        	$tpl->append(ht::createBtn('Нов отговор', $data->formAnchor,'', '', array('class' => 'forumbtn new')), 'ANSWER');
        }
        
        if($data->topicUrl) {
        	$tpl->append(ht::createBtn('Работилница', $data->topicUrl,'', '', array('class' => 'forumbtn workshop')), 'ANSWER');
        }
        
        $tpl->push($data->ForumTheme->getStyles(), 'CSS');
		$tpl->replace($this->Master->renderNavigation($data), 'NAVIGATION');
		$tpl->replace($this->Master->renderSearchForm($data), 'SEARCH_FORM');

        return $tpl;
	}
	
	
	/**
	 * Екшън за създаване на нова тема от външен достъп
	 */
	function act_New()
	{
		expect($boardId = Request::get('boardId', 'int'));
		expect($rec = $this->Master->fetch($boardId));
		$this->requireRightFor('add', $rec);
		
		$data = new stdClass();
		$data->rec = $rec;
        $data->ForumTheme = forum_Boards::getThemeClass();
        $data->action = 'new';
        $data->display = 'public';
        
        // Подготвяме $data
        $this->prepareNew($data);
        
        // Ако имаме форма за започване на нова тема
		if($data->form) {
        
            // Зареждаме REQUEST данните във формата за коментар
            $rec = $data->form->input();
            
			// Трябва да имаме права да добавяме постинг към тема от дъската
            $this->requireRightFor('add', $data->rec);
            
            // Ако формата е успешно изпратена - запис, лог, редирек
            if ($data->form->isSubmitted() && Request::get('body')) {
                
                if (!core_Users::isPowerUser(core_Users::getCurrent())) {
                    vislog_History::add('Нова тема във форума');
                }
                
            	$id = $this->save($rec);
                $this->logWrite('add', $id);
                
                return new Redirect(array('forum_Boards', 'Browse', $data->rec->id));
            }
		}
        
        // Рендираме Формата
		$layout = $this->renderNew($data);
		$layout->push($data->ForumTheme->getStyles(), 'CSS');
		$layout->replace($this->Master->renderNavigation($data), 'NAVIGATION');
		
		return $layout;
	}
	
	
	/**
	 * Обработваме необходимата ни информация в $data
	 */
	function prepareNew($data)
	{
		// Подготвяме форма за започване на нова тема
		$form = $this->getForm();
		$form->setHidden('boardId', $data->rec->id);
		
		// Ако потребителя няма права да заключва/отключва тема, ние скриваме полето от формата
		if(!$this->haveRightFor('write', $data->rec)) {
			$form->setField('status', 'input=none');
			$form->setField('type', 'input=none');
		}
		
		$form->setAction($this, 'new');
		$form->toolbar->addSbBtn('Нова тема', 'default', 'class=forumbtn addComment');
		$data->form = $form;
		
		// Заглавие на формата
		$data->header = tr("Започване на нова тема в") . ":&nbsp;&nbsp;&nbsp;" . $data->row->title;
		
		// Подготвяме навигацията
		$data->navigation = $this->Master->prepareNavigation($data->rec->category, $data->rec->id, NULL, $data->display);
	}
	
	
	/**
	 *  Рендираме формата за добавяне на нова тема
	 */
	function renderNew($data)
	{
		$data->ForumTheme->getAddThemeFormLayout($data->form);
		
        $tpl = $data->ForumTheme->getAddThemeLayout();
		$tpl->replace($data->header, 'header');
		$tpl->replace($data->form->renderHtml(), 'FORM');
        
        return $tpl;
	}
	
	
	/**
	 * Екшън за разглеждане на тема във вътрешен изглед ( подобен на act_Theme )
	 */
	function act_Topic()
	{
		// Потребителя трябва да има права за вътрешен изглед
		$this->requireRightFor('read');
		expect($id = Request::get('id', 'int'));
		expect($rec = $this->fetch($id));
		
		$data = new stdClass();
		$data->query = $this->getQuery();
        $data->rec = $rec;
        $data->action = 'topic';
        
        // Към коя дъска принадлежи темата
		$data->board = $this->Master->fetch($data->rec->boardId);
		
		// Потребителят трябва да има права да чете темите от дъската
		$this->requireRightFor('read', $data->rec);
		
		// Подготвяме темата
		$this->prepareTopic($data);
		
		// Рендираме темата
		$layout = $this->renderTopic($data);
		
		return $layout;
	}
	
	
	/**
	 *  Подготвяме темата за вътрешен изглед
	 */
	function prepareTopic($data)
	{
		$fields = $this->selectFields("");
        $fields['-topic'] = TRUE;
        $data->row = $this->recToVerbal($data->rec, $fields);
        
        // Избираме темите, които принадлежът към темата
        $data->query->where("#themeId = {$data->rec->id}");
        
        // Подготвяме пагинатора на темите
        $conf = core_Packs::getConfig('forum');
		$data->pager = cls::get('core_Pager', array('itemsPerPage' => $conf->FORUM_POSTS_PER_PAGE));
        $data->pager->setLimit($data->query);
        
		// Извличаме всички постинги направени относно темата
		while($rec = $data->query->fetch()) {
			$data->postings[$rec->id] = $this->recToVerbal($rec, $fields);
			
			// Заглавието на коментара е комбинация от #C и id-то на коментара
			$data->postings[$rec->id]->title = tr("Коментар") . " <b>#C{$rec->id}</b>";
			$data->postings[$rec->id]->anchor = "C{$rec->id}";
		}
        
		$this->prepareTopicToolbar($data);
		$data->navigation = $this->Master->prepareNavigation($data->board->category, $data->rec->boardId, $data->rec->id);
	}
	
	
	/**
	 * Подготовка на туулбара на темата
	 */
	function prepareTopicToolbar($data)
	{
		if($this->haveRightFor('write', $data->rec)) {
			
			// Местене на тема
        	$data->moveUrl = array($this, 'move', 'themeId' => $data->rec->id);
			
			// Адрес за заключване/отключване на тема
			$data->lockUrl = array($this, 'lock', $data->rec->id);
			
			// Редактиране на темата
			$data->editUrl = array($this, 'edit', $data->rec->id, 'ret_url' => TRUE );
		} 
	}
	
	
	/**
	 *  Рендираме темата за вътрешен изглед
	 */
	function renderTopic($data)
	{
		$tpl = getTplFromFile('forum/tpl/SingleTopic.shtml');
		$detailsTpl = getTplFromFile('forum/tpl/Comments.shtml');
		$tpl->placeObject($data->row);
		
		// Ако има коментари ние ги рендираме
		if(count($data->postings)) {
			$cloneTpl = clone($detailsTpl);
			
			foreach($data->postings as $row) {
				$rowTpl = $cloneTpl->getBlock('ROW');
				$rowTpl->placeObject($row);
				$rowTpl->append2master();
			}
			
			$tpl->replace($cloneTpl, 'DETAILS');
			$tpl->replace($data->pager->getHtml(), 'BOTTOM_PAGER');
		}
		
		// Ако можем да добавяме нов постинг в темата и тя е отключена
		if($this->haveRightFor('add', $data->rec)) { 
			$addUrl = array($this, 'Add', 'boardId' => $data->board->id , 'themeId' => $data->rec->id, 'ret_url' => TRUE );
			$tpl->replace(ht::createBtn('Коментар', $addUrl, NULL, NULL, 'class=btnComment, ef_icon=img/16/comment_add.png'), 'ADD_COMMENT');
		}
		
		$tpl = $this->renderTopicToolbar($data, $tpl);
        $tpl->push('forum/tpl/styles.css', 'CSS');
		$tpl = $this->renderWrapping($tpl);
		$tpl->replace($this->Master->renderNavigation($data), 'NAVIGATION');
		
        return $tpl;
	}
	
	
	/**
	 * Рендира туулбара на темата
	 * @return core_ET
	 */
	function renderTopicToolbar($data, $tpl)
	{
		if($data->editUrl) {
        	$tpl->append(ht::createBtn('Редакция', $data->editUrl, NULL, NULL, 'id=btnEdit'), 'ef_icon = img/16/edit-icon.png', 'TOOLS');
        }
        
        // Бутон за преглед във външния изглед
        $themeUrl = array($this, 'Theme', $data->rec->id);
        $tpl->append(ht::createBtn('Преглед', $themeUrl, NULL, NULL, 'ef_icon = img/16/preview.png'), 'TOOLS');
        
		// Бутон за заключване/отключване на темата за коментиране
		if($data->lockUrl) {
        	($data->rec->status == 'unlocked') ?  $str = 'Заключване' : $str = 'Отключване';
			$img = ($data->rec->status == 'unlocked') ?  "lock_unlock" : "lock";
			$tpl->append(ht::createBtn(tr($str), $data->lockUrl, NULL, NULL, "ef_icon = img/16/{$img}.png"), 'TOOLS');
         }
		
        // Ако имаме право да местим темата, рендираме формата за местене
        if($data->moveUrl) {
        	$tpl->append(ht::createBtn('Премести', $data->moveUrl, NULL, NULL, 'ef_icon = img/16/move.png'), 'TOOLS');
        }
        
        return $tpl;
	}
	
	
	/**
	 * Екшън за местене на избрана тема
	 */
	function act_Move()
	{
		$this->requireRightFor('write');
		expect($id = Request::get('themeId', 'int'));
		expect($rec = $this->fetch($id));
		
		$data = new stdClass();
		$data->rec = $rec;
		$data->row = $this->recToVerbal($rec);
		$data->action = 'move';
		$data->query = $this->getQuery();
		$data->board = $this->Master->fetch($data->rec->boardId);
		
		$this->prepareMove($data);
		
		if($data->form) {
        	$rec = $data->form->input();
        	
            $this->requireRightFor('write', $data->rec);
            
            if ($data->form->isSubmitted()) {
            	$to = $rec->boardTo;
            	
            	//Ако сме посочили нова дъска
            	if($data->rec->boardId != $to) {
		            
					$query = $this->getQuery();
					$query->where("#id = {$data->rec->id}");
					$query->orWhere("#themeId = {$data->rec->id}");
					
					// Ъпдейтваме boardId-то на всеки постинг, който е част от темата
					while($posting = $query->fetch()) {
						$posting->boardId = $to;
						$this->save($posting);
					}
					
					// Ъпдейтвама дъската от която местим темата
					$this->Master->updateBoard($data->rec->boardId);
					
					// Ъпдейтваме дъската където отива темата
					$this->Master->updateBoard($to);
					
					return new Redirect(array($this, 'Topic', $data->rec->id), 'Темата е преместена успешно');
		       } else {
		       		$data->form->setError('boardTo', tr('Посочили сте същата дъска'));
		         }
		   }
		} 
		
		$layout = $this->renderMove($data);
		
		return $layout;
	}
	
	
	/**
	 * Подготовка на формата за местене на тема
	 */
	function prepareMove($data)
	{
		// Форма за местене на тема
		$data->form = cls::get('core_Form');
		$data->form->FNC('boardTo', 'key(mvc=forum_Boards,select=title)', 'caption = Избери,input');
		$data->form->setHidden('theme', $data->rec->id);
		$data->form->setDefault('boardTo', $data->board->id);
		$data->form->title = "Местене на тема|* : <b>{$data->row->title}</b>";
		$data->form->toolbar->addSbBtn('Премести', array($this, 'move', 'themeId' => $data->rec->id), 'ef_icon = img/16/move.png');
		$data->form->toolbar->addBtn('Отказ', array($this, 'Topic', $data->rec->id), 'ef_icon = img/16/close-red.png');
		
		$data->navigation = $this->Master->prepareNavigation($data->board->categoryId, $data->rec->boardId, $data->rec->id);
	}
	
	
	/**
	 * @param stdClass $data
	 * @return core_ET
	 */
	function renderMove($data)
	{
		$layout = new ET("");
		$layout->append($this->Master->renderNavigation($data));
		if($data->form) {
			$layout->append($data->form->renderHtml());
		}
		$layout = $this->renderWrapping($layout);
		$layout->push('forum/tpl/styles.css', 'CSS');
		
		return $layout;
	}
	
	
	/**
	 *  Екшън за заключване/отключване на тема. Заключена тема не може да бъде коментирана повече
	 */
	function act_Lock()
	{   
        $id = Request::get('id', 'int');
		expect($rec = $this->fetch($id));
		$this->requireRightFor('write', $rec);
		
		// променяме статуса на темата на заключенa/отключенa
		if($rec->status == 'unlocked') {
			$rec->status = 'locked';
			$msg = tr('Темата беше успешно заключена');
		} else {
			$rec->status = 'unlocked';
			$msg = tr('Темата беше успешно отключена');
		  }
		
		// Запазваме промененият статус на темата
		$this->save($rec);
		
		return new Redirect(array($this, 'Topic', $rec->id), $msg);
	}
	
	
	/**
    *  Екшън за търсене и визуализиране на намерените теми
    */
	function act_Search()
	{
		$data = new stdClass();
		$data->query = $this->getQuery();
		$data->ForumTheme = forum_Boards::getThemeClass();
		$data->action = 'search';
        $data->display = 'public';
        $data->q = Request::get('q');
        
        $this->prepareSearch($data);
        
        $this->prepareListFilter($data);
        
        $layout = $this->renderSearch($data);
        $layout->push($data->ForumTheme->getStyles(), 'CSS');
        $layout->replace($this->Master->renderNavigation($data), 'NAVIGATION');
        $layout->replace($this->Master->renderSearchForm($data), 'SEARCH_FORM');
        $layout->replace($data->q, 'SEARCH_FOR');
        
        return $layout;
	}
	
	
	/**
    *  Подготвяме резултатите за търсенето, резултатите са линкове към нишките в които има
    *  постинги отговарящи на условието
    */
	function prepareSearch($data)
	{
		$fields = $this->selectFields("");
        $fields['-browse'] = TRUE;
        
        // Подреждаме темите в последователност: Съобщение, Важна, Нормална
        $data->query->orderBy('type, createdOn', 'DESC');
        
        // Използваме  за филтриране по зададен стринг
        if($data->q) {
         	plg_Search::applySearch($data->q, $data->query);
        }
      
        $conf = core_Packs::getConfig('forum');
		$data->pager = cls::get('core_Pager', array('itemsPerPage' => $this->listItemsPerPage));
        $data->pager->setLimit($data->query);
        
        $cu = core_Users::getCurrent();
        while($rec = $data->query->fetch()) {
        	
        	$board = $this->Master->fetch($rec->boardId);
			if($this->Master->haveRightToObject($board, $cu)) {
				
				// Ако записа е коментар
				if($rec->themeId) {
					
					// Намираме от коя тема е коментара
					$themeRec = $this->fetch($rec->themeId);
					if(is_array($data->recs) && array_key_exists($themeRec->id, $data->recs)) {
						
						// Ако темата на коментара е вече в масива, продължаваме на следващата
						// итерация, така коментара не се добавя в резултатите, защото неговата
						// тема е вече там
						continue;
					} else {
						
						// Ако темата на коментара, не е в резултатите ние вкарваме в масива
						// с резултати темата вместо коментара
						$rec = $themeRec;
					}
				}
				
				// Ако имаме достъп до дъската, показваме темите, така в получения масив
				// фигурират само "нишките" от теми които отговарят на условието за търсене
	        	$data->recs[$rec->id] = $rec;
	        	$data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
	        	$boardUrl = array($this, 'browse', $rec->boardId);
	        	$data->rows[$rec->id]->board = ht::createLink($data->rows[$rec->id]->board, $boardUrl);
			}
		} 
		
        $this->Master->prepareSearchForm($data);
		$data->navigation =  $this->Master->prepareNavigation(NULL, NULL, NULL, $data->display);
    }
	
    
    /**
     *  Рендираме резултатите от търсенето
     */
    function renderSearch($data)
    {
    	$tpl = $data->ForumTheme->getResultsLayout();
    	$tableTpl = $tpl->getBlock('ROW');
    	$openIcon = $data->ForumTheme->getImage('unlocked.png', '32');
		$lockedIcon = $data->ForumTheme->getImage('locked.png', '32');
		
		if(count($data->rows)) {
	      foreach($data->rows as $row) {
	      		$themeTpl = clone $tableTpl;
	      		$row->ICON = ($row->locked == "заключена") ? $lockedIcon : $openIcon;
	      		$themeTpl->placeObject($row);
	         	$themeTpl->removeBlocks();
	         	$themeTpl->removePlaces();
	         	
	         	$tpl->append($themeTpl, "ROW");
	      }
		} else {
			$tpl->replace("<div class='no-boards'>" . tr("Няма теми") . "</div>", 'ROW');
		}
		
		$tpl->replace($data->pager->getHtml(), 'PAGER');
		
    	return $tpl;
    }
    
    
	/**
	 * Модифициране на данните за преглеждане на темите и коментиране
	 */
	static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if($action == 'read' && isset($rec)) {
			
			// Ако потребителя има достъп до дъската, той има достъп и до темата
			$board = forum_Boards::fetch($rec->boardId);
			(forum_Boards::haveRightToObject($board) ) ? $res = 'every_one' : $res = 'forum';
		}
		
		if($action == 'add' && isset($rec)) {
			
			// Намираме ид-то на дъската взависимост дали добавяме нова тема или коментар
			$id = ($rec->boardId) ? $id = $rec->boardId : $id = $rec->id;
			
			// Проверяваме дали потребителя има достъп до дъската
			$board = forum_Boards::fetch($id);
			(forum_Boards::haveRightToObject($board) ) ? $res = $mvc->canWrite : $res = 'forum';
			
			// Ако постинга е коментар и темата е заключена
			if($rec->status == 'locked' && $rec->id !== NULL) {
				$res = 'no_one';
			}
		}
		
		if($action== 'add' && !isset($rec)) {
			
			// Предпазване от добавяне на нов постинг в act_List
			$res = 'no_one';
		}
		
		if($action == 'write' && isset($rec)) {
			$id = ($rec->boardId) ? $id = $rec->boardId : $id = $rec->id;
			
			// Който може да създава дъски, той може да прави важни теми, както и да ги заключва
			$board = forum_Boards::fetch($id);
			(forum_Boards::haveRightToObject($board) ) ? $res = $mvc->Master->canWrite : $res = 'forum';
		
		}
		
		if($action == 'edit' && isset($rec->id)) {
			
			// Само 'forum и автора на темата могат да я редактират, ако има достъп до дъската
			$board = forum_Boards::fetch($rec->boardId);
			if(forum_Boards::haveRightToObject($board)){
				if(haveRole('forum') || $rec->createdBy == $userId) {
					$res = $mvc->canWrite;
				}
			} else {
				$res = 'no_one';
			  }
		}
	}
	
	
	/**
	 * Обновяване на статистическата информация, след създаването на нов постинг
	 */
	 static function on_AfterCreate($mvc, $rec)
     {
	      if($rec->themeId) {
	      	
	      	// Ако постинга е коментар към тема, ние обновяваме, кой е последния коментар в нея
	      	$mvc->updateStatistics($rec->themeId, $rec->createdOn, $rec->createdBy);
	      }
	     
	      // Обновяваме статистическата информация в дъската където е направен постинга
	  	  $mvc->Master->updateBoard($rec->boardId);
    }
   
   
    /**
     * Обновяваме статистическата информация на темата
     * @param int $themeId - ид на темата
     * @param datetime $createdOn - дата на публикуване
     * @param int createdBy - автор
     */
    function updateStatistics($themeId, $createdOn, $createdBy)
    {
   	  $rec = $this->fetch($themeId);
   	   		
      // Избираме постингите, принадлежащи на темата
   	  $query = $this->getQuery();
	  $query->where("#themeId = {$themeId}");
	        
	  // Обновяваме, кой и кога е направил последния коментар
	  $rec->last = $createdOn;
	  $rec->lastWho = $createdBy;
	  $rec->postingsCnt = $query->count();
	  $this->save($rec);
   }
   
   
   /**
    *  Модификации на вербалните стойности на някои полета, взависимост от екшъна
    */
   protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
   {
   	 	if($rec->themeId === NULL) { 
   	 		 
   	 		if($fields['-list']) {
				
   	 			if($rec->status == 'locked') {
   	 				$row->status = ht::createElement('img', array('src' => sbf("forum/tpl/img/32/locked.png", ""), 'width' => '20px'));
   	 				$row->status .= "&nbsp";
   	 			} else {
   	 				$row->status = '';
   	 			  }
   	 			
   	 			$row->title = $row->status. ht::createLink($row->title, array($mvc, 'Topic', $rec->id));
   	 			
   	 			if(!$row->last) {
   	 		 		$row->last = tr('няма');
   	 		 	}
   	 		 	
   	 		 	(!$row->lastWho) ? $row->lastWho = tr('няма') : $row->lastWho = core_Users::fetch($rec->lastWho)->nick;
   	 		 
   	 		} elseif($fields['-browse']) {
   	 		 	
   	 		 	// Ако екшъна е browse правим обработки на заглавието и типа
   	 		 	$row->title = ht::createLink($row->title, array($mvc, 'Theme', $row->id));
   	 		 	
   	 		 	if($rec->status == 'locked') {
   	 		 		$row->locked = tr("заключена");
   	 		 	} 
   	 		 	
   	 		 	$row->postingsCnt .= "&nbsp" . tr('Мнения');
   	 		 	$row->views .= "&nbsp" . tr('Прегледа');
   	 		 	
   	 		 	if(isset($rec->lastWho)) {
	            	
	            	// Намираме аватара и ника на потребителят, коментирал последно
	            	$user = core_Users::fetch($rec->lastWho);
		        	$row->avatar = avatar_Plugin::getImg(0, $user->email, 50);
		        	$row->nick = $user->nick;
	            } else {
	            	$row->noComment = tr('няма коментари');
	              }
   	 		 	
   	 		 	// Ако темата е важна или съобщение, я поставяме в контейнер за по-лесно стлизиране
   	 		 	if($rec->type == 'sticky') {
	           		$row->type = ht::createElement('span', array('class' => 'sticky'), tr($row->type));
	           	} elseif($rec->type == 'announcement') {
	           		$row->type = ht::createElement('span', array('class' => 'announcement'), tr($row->type));
	           	} else {
	           		unset($row->type);
	           	  } 
   	 		 } 
   	 	} else {
   	 		if(!$mvc->masterMVC) {
   	 			if($fields['-list']) {
   	 				$row->type = 'коментар';
   	 				$commentURL = array($this, 'Topic', $rec->themeId, '#' => "C{$rec->id}");
   	 				$row->title = ht::createLink("#C{$rec->id}", $commentURL);
   	 			}
   	 		}
   	 	}
   	 	
   	 	if($fields['-theme'] || $fields['-topic']) {
   	 		$row->avatar = avatar_Plugin::getImg(0, core_Users::fetch($rec->createdBy)->email, 100);
   	 		//$row->topLink = ht::createLink(tr('начало'), getCurrentUrl(), NULL, array('class' => 'button'));
   	 	}
    }
	
	
	/**
	 * Премахваме тези теми, които принадлежат на дъски, до които нямаме достъп
	 */
	protected static function on_AfterPrepareListRecs($mvc, $res, $data)
	{
		if(!$mvc->masterMvc) {
			
			$cu = core_Users::getCurrent();
			if($data->recs){
				foreach($data->recs as $rec) {
					
					// за всяка тема проверяваме достъпа до дъската и, ако не я премахваме
					$board = $mvc->Master->fetch($rec->boardId);
					if(!$mvc->Master->haveRightToObject($board, $cu)) {
						unset($data->recs[$rec->id]);
					}
				}
			}
		}
	}
	
	
	/**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
    	if($data->masterMvc) {
    		
    		// Не показваме 'boardId' в Single-a  на Мастъра
    		unset($data->listFields['boardId']);
    		unset($data->listFields['id']);
    		unset($data->listFields['body']);
    	} else {
    		
    		// Премахваме ненужните полета в лист изгледа
    		unset($data->listFields['views']);
    		unset($data->listFields['postingsCnt']);
    		unset($data->listFields['last']);
    		unset($data->listFields['lastWho']);
    		unset($data->listFields['id']);
    	}
    }
	
    
    /**
     * Филтриране по име на темата
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->listFilter->title = tr('Търсене');
    	$data->listFilter->FNC('posting', 'enum(themes=Теми,all=Всички,comments=Коментари)', 'placeholder=Тип,input,value=themes,silent');
    	$data->listFilter->FNC('board', 'key(mvc=forum_Boards,select=title,allowEmpty)', 'placeholder=Дъска,input,silent');
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
   		$data->listFilter->view = 'horizontal';
   		$data->listFilter->showFields = 'search, posting, board';
        $data->listFilter->input('search, board, posting', 'silent');
        
        $data->query->where("#themeId IS  NULL");
		$data->title = tr('Показване на всички теми');
		
        if($filter = $data->listFilter->rec) {
	    	if($filter->board > 0) {
					$data->query->where("#boardId = {$filter->board}");
					$verbalBoard = $data->listFilter->getFieldType('board')->toVerbal($filter->board);
					$data->title .= ' в дъска |*<span style="color:darkblue;">"' . $verbalBoard . '"</font>';
				}
				
        	if($filter->posting == 'all') {
				
				// Ако търсим по всички постинги добавяме и коментарите
				$data->query->orWhere("#themeId IS NOT NULL");
				$data->title = tr('Показване на всички постинги');
			} elseif($filter->posting == 'comments') {
				
				// Ако търсим само в коментари
				unset($data->query->where);
				$data->query->where("#themeId IS NOT NULL");
				$data->title = tr('Показване на всички коментари');
			}
		}
        
		// подреждане на резултатите
		$data->query->orderBy('type, createdOn', 'DESC');
    }
    
    
    /**
	 * Обновява броя на индивидуалните посещения на темата, след запис в лога
	 * @param  stdClass $theme
	 * @param  int $cnt
	 * @return void
	 */
	function updateThemeViews($theme, $cnt)
	{
		$theme->views = $cnt;
		$this->save($theme);
	}
	
	
	/**
	 * workaround
	 * Когато plg_Search  се добави към клас който е Detail, то в Single на 
	 * мастъра, при подготовката на детайлите му не се извиква prepareListFilter
	 * на детайлите му и plg_Search гърми, Това е временен фикс да не изкарва
	 * грешка
	 */
	static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if(!$data->listFilter) {
			
			// Това условие е изпълнено само ако сме в Single на master класа
			$mvc->prepareListFilter($data);
		}
	}
}