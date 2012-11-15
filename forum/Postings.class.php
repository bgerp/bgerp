<?php

/**
 * Постинги
 *
 *
 * @category  bgerp
 * @package   forum
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class forum_Postings extends core_Detail {
	
	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Постинги';

	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Modified, forum_Wrapper, plg_Sorting';
	
	
	/**
	 * Поле за лентата с инструменти
	 */
	var $rowToolsField = 'tools';
	
	
	/**
	 * Полета за изглед
	 */
	var $listFields = 'tools=Пулт, id, title, type, boardId, postingsCnt, views, last, lastWho, createdBy, createdOn';
	
	
	/**
	 *  Брой теми на страница
	 */
	var $listItemsPerPage = "30";
	
	
	/**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'boardId';
	
	
	/**
	 * Кой може да листва дъските
	 */
	var $canRead = 'forum, cms, ceo, admin';
	
	
	/**
	 * Кой може да добявя,редактира или изтрива дъска
	 */
	var $canWrite = 'forum, admin, cms, user';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('boardId', 'key(mvc=forum_Boards, select=title)', 'caption=Дъска, input=hidden, silent');
		$this->FLD('title', 'varchar(190)', 'caption=Заглавие, mandatory, placeholder=Заглавие, width=100%');
		$this->FLD('body', 'richtext', 'caption=Съдържание, mandatory, placeholder=Добавете вашия коментар, width=100%');
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
		
    	$data->form->title = tr("Започване на нова тема в ") . "<b>{$boardRow->title}</b>";
    	
    	// Ако постинга е коментар
    	if($themeId = Request::get('themeId')) {
    		
    		expect($themeRec = static::fetch($themeId));
    		$themeRow = $mvc->Master->recToVerbal($themeRec, 'id,title');
    		
    		// Трябва да имаме права да коментираме темата
    		static::requireRightFor('add', $themeRec);
    		
    		$data->form->setField('type', 'input=none');
	    	$data->form->setField('title', 'input=none');
	    	$data->form->setField('status', 'input=none');
	    	$data->form->setHidden('themeId', $themeRow->id);
	    	$data->form->title = tr("Добавяне на коментар в: ") . "<b>{$themeRow->title}</b>";
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
		$tpl = new ET(getFileContent($data->forumTheme . '/Themes.shtml'));
		
		// Иконките на отключените и заключените теми взети от текущата тема
		$openIcon = ht::createElement('img', array('src' => sbf($data->forumTheme . "/img/32/unlocked.png", ""), 'width' => '32px'));
		$lockedIcon = ht::createElement('img', array('src' => sbf($data->forumTheme . "/img/32/locked.png", ""), 'width' => '32px'));
		
		// Ако имаме теми в дъската ние ги рендираме
		if(count($data->themeRows)) {
	      foreach($data->themeRows as $row) {
	      		$themeTpl = $tpl->getBlock('ROW');
	         	$themeTpl->placeObject($row);
	         	
	         	// Добавяме иконката взависимост дали темата е заключена/отключена
	         	if($row->locked == "заключена") {
	         		$themeTpl->replace($lockedIcon, 'ICON');
	         	} else {
	         		$themeTpl->replace($openIcon, 'ICON');
	         	  }
	         	
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
            $tpl->replace("<h2>" . tr("Няма теми") . "</h2>");
          }
        
         $layout->replace($tpl, 'THEMES');
         
         // Рендираме пагинаторът
         $layout->replace($this->renderListPager($data), 'PAGER');
         
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
		$conf = core_Packs::getConfig('forum');
        $data->forumTheme = $conf->FORUM_DEFAULT_THEME;
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
                $this->log('add', $id);
                
                return new Redirect(array('forum_Postings', 'Theme', $data->rec->id));
            }
		}
		
		// Рендираме темата
		$layout = $this->renderTheme($data);
		
		// Записваме че темата е посетена в лога
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
			$data->postForm->toolbar->addSbBtn('Добави');
			
			// Котва към формата за коментар
			$data->formAnchor =  array($this, 'Theme', $data->rec->id, '#'=>'comment');
		}
		
		if($this->haveRightFor('single')) {
			
			// Линк за вътрешен преглед на темата
			$data->topicUrl = array($this, 'Topic', $data->rec->id);
		}
		
		// Подготвяме навигацията
		$this->Master->prepareNavigation($data);
	}
	
	
	/**
	 * Рендираме темата
	 */
	function renderTheme_($data)
	{
		$tpl = new ET(getFileContent($data->forumTheme . '/SingleTheme.shtml'));
		$commentTpl = new ET(getFileContent($data->forumTheme . '/Comments.shtml'));
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
			
            $data->postForm->layout = new ET(getFileContent($data->forumTheme . '/PostForm.shtml'));
            $data->postForm->fieldsLayout = new ET(getFileContent($data->forumTheme . '/PostFormFields.shtml'));
            $tpl->replace($data->postForm->renderHtml(), 'COMMENT_FORM');
        } else {
        	(core_Users::getCurrent()) ? $msg = 'темата е заключена' : $msg = 'За коментар е нужна регистрация !!!';
        	$tpl->replace("<b>" . tr($msg) . "</b>", 'COMMENT_FORM');
          }
		
        if($data->formAnchor) {
        	$tpl->append(ht::createBtn('Нов отговор', $data->formAnchor), 'ANSWER');
        }
        
        if($data->topicUrl) {
        	$tpl->append(ht::createBtn('Работилница', $data->topicUrl), 'ANSWER');
        }
        
        $tpl->push($data->forumTheme . '/styles.css', 'CSS');
		
		$tpl->replace($this->Master->renderNavigation($data), 'NAVIGATION');
		
        return $tpl;
	}
	
	
	/**
	 * Екшън за създаване на нова тема от външен достъп
	 */
	function act_New()
	{
		expect($boardId = Request::get('boardId'));
		
		expect($rec = $this->Master->fetch($boardId));
		
		$this->requireRightFor('add', $rec);
		
		$data = new stdClass();
		$data->rec = $rec;
		$conf = core_Packs::getConfig('forum');
        $data->forumTheme = $conf->FORUM_DEFAULT_THEME;
        
        // Попълваме вербалната и невербалната информация за дъската, където ще добавяме тема
        $fields = $this->Master->selectFields('');
        $data->row = $this->Master->recToVerbal($data->rec, $fields);
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
            	$id = $this->save($rec);
                $this->log('add', $id);
                
                return new Redirect(array('forum_Boards', 'Browse', $data->rec->id));
            }
		}
        
        // Рендираме Формата
		$layout = $this->renderNew($data);
		
		$layout->push($data->forumTheme . '/styles.css', 'CSS');
		
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
		$form->setHidden('boardId', $data->row->id);
		
		// Ако потребителя няма права да заключва/отключва тема, ние скриваме полето от формата
		if(!$this->haveRightFor('write', $data->rec)) {
			$form->setField('status', 'input=none');
			$form->setField('type', 'input=none');
		}
		
		$form->setAction($this, 'new');
		$form->toolbar->addSbBtn('Нова тема');
		$data->form = $form;
		
		// Заглавие на формата
		$data->header = tr("Започване на нова тема в:") . "&nbsp;&nbsp;&nbsp;" . $data->row->title;
		
		// Подготвяме навигацията
		$this->Master->prepareNavigation($data);
	}
	
	
	/**
	 *  Рендираме формата за добавяне на нова тема
	 */
	function renderNew($data)
	{
		$data->form->layout = new ET(getFileContent($data->forumTheme . '/AddForm.shtml'));
        $data->form->fieldsLayout = new ET(getFileContent($data->forumTheme . '/AddFormFields.shtml'));
		
        $tpl = new ET(getFileContent($data->forumTheme . '/New.shtml'));
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
			$data->details[$rec->id] = $this->recToVerbal($rec, $fields);
		}
        
		$this->prepareTopicToolbar($data);
        
		$this->Master->prepareInnerNavigation($data);
	}
	
	
	/**
	 * Подготовка на туулбара на темата
	 */
	function prepareTopicToolbar($data)
	{
		if($this->haveRightFor('write', $data->rec)) {
			
			// Местене на тема
        	$data->moveUrl = array($this, 'Move', 'themeId' => $data->rec->id);
			
			// Адрес за заключване/отключване на тема
			$data->lockUrl = array($this, 'Lock', $data->rec->id);
			
			// Редактиране на темата
			$data->editUrl = array($this, 'Edit', $data->rec->id, 'ret_url' => TRUE );
		} 
	}
	
	
	/**
	 *  Рендираме темата за вътрешен изглед
	 */
	function renderTopic($data)
	{
		$tpl = new ET(getFileContent('forum/tpl/SingleTopic.shtml'));
		$detailsTpl = new ET(getFileContent('forum/tpl/Comments.shtml'));
		$tpl->placeObject($data->row);
		
		// Ако има коментари ние ги рендираме
		if(count($data->details)) {
			$cloneTpl = clone($detailsTpl);
			
			foreach($data->details as $row) {
				$rowTpl = $cloneTpl->getBlock('ROW');
				$row->title = tr("Коментар");
				$rowTpl->placeObject($row);
				$rowTpl->append2master();
			}
			
			$tpl->replace($cloneTpl, 'DETAILS');
			$tpl->replace($data->pager->getHtml(), 'BOTTOM_PAGER');
		}
		
		// Ако можем да добавяме нов постинг в темата и тя е отключена
		if($this->haveRightFor('add', $data->rec)) { 
			$addUrl = array($this, 'Add', 'boardId' => $data->board->id , 'themeId' => $data->rec->id, 'ret_url' => TRUE );
			$tpl->replace(ht::createBtn('Коментирай', $addUrl, NULL, NULL, 'id=btnAdd,class=btn-add'), 'ADD_COMMENT');
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
        	$tpl->append(ht::createBtn('Редакция', $data->editUrl, NULL, NULL, 'id=btnEdit,class=btn-edit'), 'TOOLS');
        }
        
        // Бутон за преглед във външния изглед
        $themeUrl = array($this, 'Theme', $data->rec->id);
        $tpl->append(ht::createBtn('Преглед', $themeUrl, NULL, NULL, 'class=btn-add'), 'TOOLS');
        
		// Бутон за заключване/отключване на темата за коментиране
		if($data->lockUrl) {
        	($data->rec->status == 'unlocked') ?  $str = 'Заключване' : $str = 'Отключване';
        	  
        	$tpl->append(ht::createBtn(tr($str), $data->lockUrl, NULL, NULL, 'class=btn-add'), 'TOOLS');
         }
		
        // Ако имаме право да местим темата, рендираме формата за местене
        if($data->moveUrl) {
        	$tpl->append(ht::createBtn('Премести', $data->moveUrl, NULL, NULL, 'class=btn-move'), 'TOOLS');
        }
        
        return $tpl;
	}
	
	
	/**
	 * Екшън за местене на избрана тема
	 */
	function act_Move()
	{
		$this->requireRightFor('write');
		expect($id = Request::get('themeId'));
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
					
					return new Redirect(array($this, 'Topic', $data->rec->id), tr('Темата е преместена успешно'));
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
		$data->form->FNC('boardTo', 'key(mvc=forum_Boards,select=title)', 'caption = Избери,input,width=100%');
		$data->form->setHidden('theme', $data->rec->id);
		$data->form->setDefault('boardTo', $data->board->id);
		$data->form->title = tr('Местене на тема') . ":&nbsp;<b>" . $data->row->title . "</b>";
		$data->form->toolbar->addSbBtn('Премести', array($this, 'move', 'themeId' => $data->rec->id), array('class' => 'btn-move'));
		$data->form->toolbar->addBtn('Отказ', array($this, 'Topic', $data->rec->id), array('class' => 'btn-cancel'));
		
		$this->Master->prepareInnerNavigation($data);
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
	 *  Екшън за заключване/отключване на тема. Заключена тема неможе да бъде коментирана повече
	 */
	function act_Lock()
	{
		expect($rec = $this->fetch(Request::get('id')));
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
			if($rec->status == 'locked') {
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
   function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
   {
   	 	if($row->themeId === NULL) { 
   	 		 
   	 		if($fields['-list']) {
				
   	 			if($rec->status == 'locked') {
   	 				$row->status = ht::createElement('img', array('src' => sbf("forum/tpl/img/32/locked.png", ""), 'width' => '20px'));
   	 				$row->status .= "&nbsp";
   	 			} else {
   	 				$row->status = '';
   	 			  }
   	 			
   	 			$row->title =$row->status. ht::createLink($row->title, array($mvc, 'Topic', $rec->id));
   	 			
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
   	 	}
   	 	
   	 	if($fields['-theme'] || $fields['-topic']) {
   	 		$row->avatar = avatar_Plugin::getImg(0, core_Users::fetch($rec->createdBy)->email, 100);
   	 		$row->topLink = ht::createLink(tr('начало'), getCurrentUrl(), NULL, array('class' => 'button'));
   	 	}
   	 	
   	 	// Линк към преглед на темата във външния изглед
   	 	if($fields['-public']) {
   	 		$row->title = ht::createLink($row->title, array($mvc, 'Theme', $row->id));
   	 	}
   	 	
   	 	// Линк към преглед на темата във вътрешния изглед
   		if($fields['-private']) {
   	 		$row->title = ht::createLink($row->title, array($mvc, 'Topic', $row->id));
   	 	}
    }
   
    
    /**
	 *  Показваме само постингите, които са теми при Лист изгледа и Single-a  на дъска
	 */
    function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		$data->query->where("#themeId IS NULL");
		$data->query->orderBy('type, createdOn', 'DESC');
		
		// Филтрирваме по име на темата, ако е посочено
		if($search = $data->listFilter->rec->search) {
			$data->query->where(array("#title LIKE '%[#1#]%'", $search));
		}
		
		// Филтрирваме по дъска ако е посочено
	 	if(($board = $data->listFilter->rec->board) > 0) {
           $data->query->where("#boardId = {$board}");
        }
	}
	
	
	/**
	 * Премахваме тези теми, които принадлежат на дъски, до които нямаме достъп
	 */
	function on_AfterPrepareListRecs($mvc, $res, $data)
	{
		if(!$mvc->masterMvc) {
			
			$cu = core_Users::getCurrent();
			if($data->recs){
				foreach($data->recs as $rec) {
					
					// за всяка тема проверяваме достъпа до дъската и, ако не я премахваме
					$board = $this->Master->fetch($rec->boardId);
					if(!$this->Master->haveRightToObject($board, $cu)) {
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
    	} 
    }
	
    
    /**
     * Филтриране по име на темата
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->listFilter->title = 'Търсене';
    	$data->listFilter->FNC('search', 'varchar(190)', 'caption=Тема,input,silent');
        $data->listFilter->FNC('board', 'key(mvc=forum_Boards,select=title,allowEmpty)', 'placeholder=Дъска,input,silent');
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
   		$data->listFilter->view = 'horizontal';
   		$data->listFilter->showFields = 'search, board';
        $data->listFilter->input('search, board', 'silent');
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
}