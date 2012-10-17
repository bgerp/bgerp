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
	var $title = 'Постове';

	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_Created, plg_Modified, forum_Wrapper';
	
	
	/**
	 * Мастър ключ към статиите
	 */
	var $masterKey = 'boardId';
	
	
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
		$this->FLD('boardId', 'key(mvc=forum_Boards, select=title)', 'caption=Дъска, input=hidden, silent');
		$this->FLD('title', 'varchar(50)', 'caption=Заглавие, mandatory, notNull,width=100%');
		$this->FLD('author', 'varchar(50)', 'caption=Автор, mandatory, notNull,width=100%');
		$this->FLD('body', 'richtext', 'caption=Съдържание, mandatory, notNull,width=100%');
		$this->FLD('postingsCnt', 'int', 'caption=Брой на постингите,input=hidden,width=100%');
		$this->FLD('themeId', 'int', 'caption=Ид на темата,input=hidden,width=100%');
	}
	
	
	/**
	 *  Подготовка на списъка от теми, които принадлежат на дъската и са начало
	 *  на нова нишка (тези с themeId = NULL)
	 */
	function prepareBoardThemes_($data)
	{
		$query = $this->getQuery();
        $fields = $this->selectFields("");
        $fields['-browse'] = TRUE;
        
        // Избираме темите, които са начало на нова нишка от дъската
        $query->where("#boardId = {$data->rec->id} AND #themeId IS NULL");
        if($this->haveRightFor('read', $data->rec)) {
        	
        	// Ако имаме права да виждаме темите в дъската, ние ги извличаме
	        while($rec = $query->fetch()) {
	            $data->themeRecs[$rec->id] = $rec;
	            $data->themeRows[$rec->id] = $this->recToVerbal($rec, $fields);
	            $url = array('forum_Postings', 'Theme', $rec->id);
	            
	            // Заглавието на постинга, който е начало на тема става линк към
	            // самата тема
	            $data->themeRows[$rec->id]->title = ht::createLink($data->themeRows[$rec->id]->title, $url);
	        }
        }
        
        // Ако имаме права да добавяме нова тема в дъската
        if($this->haveRightFor('add', $data->rec)) {
        	$data->submitUrl = array($this, 'add', $this->masterKey => $data->rec->id);
        }
    }
    
    
    /**
	 * Рендиране на списъка от теми, принадлежащи на дъската, които са начало на
	 * нова нишка
	 */
    function renderBoardThemes_($data, $layout)
	{
		$tpl = new ET(getFileContent($data->forumTheme . '/Themes.shtml'));
		
		// Ако имаме теми в дъската ние ги рендираме
		if(count($data->themeRows)) {
	      foreach($data->themeRows as $row) {
	      		$themeTpl = $tpl->getBlock('ROW');
	         	$themeTpl->placeObject($row);
	         	$themeTpl->append2master();
	         }
        } else {
            $tpl->replace('<h2>Няма Теми</h2>');
        }
        
         $layout->replace($tpl, 'THEMES');
         
         
         return $layout;
	}
	
	
	/**
	 * Задаване на име на автора по подразбиране
	 */
	static function on_AfterPrepareEditForm($mvc, $res, $data)
	{
	    $data->form->setDefault('author', core_Users::getCurrent('nick'));
	    $data->form->setHidden('postingsCnt', '0');
	}
	
	
	/**
	 * Екшън, който показва постингите от една тема в хронологичен ред. Началото на
	 * една тема я поставя постинг с themeId = NULL, а постингите добавни след него
	 * към темата имат за themeId  ид-то на мастър постинга
	 */
	function act_Theme()
	{
		// Ид на разглежданата тема
		$id = Request::get('id', 'int');
		if(!$id) {
            expect($id = Request::get('themeId', 'int'));
        }
		
		$data = new stdClass();
		$data->query = $this->getQuery();
		$conf = core_Packs::getConfig('forum');
        $data->forumTheme = $conf->FORUM_DEFAULT_THEME;
        expect($data->rec = $this->fetch($id));
        
        // Към коя дъска принадлежи темата
		$data->board = $this->Master->fetch($data->rec->boardId);
		
		// Потребителят трябва да има права да чете темите от дъската
		$this->requireRightFor('read', $data->board);
		
		// Подготвяме постингите от избраната тема
		$this->prepareTheme($data);
		
		// Ако имаме форма за добавяне на нов постинг към темата
		if($data->postForm) {
        
            // Зареждаме REQUEST данните във формата за коментар
            $rec = $data->postForm->input();
            
            // Трябва да имаме права да добавяме постинг към тема от дъската
            $this->requireRightFor('add', $data->board);
            
            // Ако формата е успешно изпратена - запис, лог, редирек
            if ($data->postForm->isSubmitted() && Request::get('body')) {
            	$id = static::save($rec);
                $this->log('add', $id);
                
                return new Redirect(array('forum_Postings', 'Theme', $data->rec->id), 'Благодарим за вашия коментар;)');
            }
		}
		
		// Рендираме темата
		$layout = $this->renderTheme($data);
		
		return $layout;
	}

	
	/**
	 * Подготовка на Постингите от нишката, и формата за коментар (ако имаме права)
	 */
	function prepareTheme_($data)
	{
		$query = $this->getQuery();
		$fields = $this->selectFields("");
        $fields['-theme'] = TRUE;
        
        // Избираме темите, които принадлежът към темата
        $query->where("#themeId = {$data->rec->id}");
        
        // Първия постинг в нишката е мастър постинга ( този който е начало
        // на темата
        $data->thread[] = $this->recToVerbal($data->rec, $fields);
		while($rec = $query->fetch()) {
			
			// Добавяме другите постинги, които имат за themeId, id-то на темата
			$data->thread[] = $this->recToVerbal($rec, $fields);
		}
		$data->title = "Разглеждане на тема {$data->rec->title}, дъска {$data->rec->boardId}";
		
		// Ако можем да добавяме нов постинг в темата
		if($this->haveRightFor('add', $data->board)) {
			
			// Подготвяме формата за добавяне на нов постинг към нишката
			$data->postForm = $this->getForm();
			$data->postForm->setField('author', 'input=hidden');
			$data->postForm->setField('title', 'input=none');
			$data->postForm->setHidden('author', core_Users::getCurrent('nick'));
			$data->postForm->setHidden('themeId', $data->rec->id);
			$data->postForm->setHidden('boardId', $data->rec->boardId);
			$data->postForm->toolbar->addSbBtn('Коментирай');
		}
	}
	
	
	/**
	 * Рендираме темата
	 */
	function renderTheme_($data)
	{
		$tpl = new ET(getFileContent($data->forumTheme . '/Thread.shtml'));
		$tpl->replace($data->title, 'THREAD_HEADER');
		
		// Ако имаме теми в нишката ние ги рендираме
		if(count($data->thread)){
			foreach($data->thread as $row) {
				$rowTpl = $tpl->getBlock('ROW');
				$rowTpl->placeObject($row);
	            $rowTpl->append2master();
			}
		}
		
		// Ако имаме право да добавяме коментар рендираме формата в края на нишката
		if($data->postForm) {
            $data->postForm->layout = new ET(getFileContent($data->forumTheme . '/PostForm.shtml'));
            $data->postForm->fieldsLayout = new ET(getFileContent($data->forumTheme . '/PostFormFields.shtml'));
            $tpl->replace($data->postForm->renderHtml(), 'COMMENT_FORM');
        }
		
		return $tpl;
	}
	
	
	/**
	 * Модифициране на данните за преглеждане на темите и коментиране
	 */
	static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if($action == 'read' && isset($rec)) {
			
			// Могат да виждат темите, единствено потребителите с роли, които са
			// зададени в полето 'canSeeThemes' от дъската, чиито теми разглеждаме
			$res = forum_Boards::getVerbal($rec, 'canSeeThemes');
		}
		
		if($action == 'add' && isset($rec->canComment)) {
			
			// Могат да коментират, единствено потребителите с роли, зададени в
			// полето 'canComment' от дъската, към която принадлежи темата
			$res = forum_Boards::getVerbal($rec, 'canComment');
		} 
	}
	
	
	/**
	 * Обновяване на статистическата информация в моделите, след създаване на
	 * нов постинг
	 */
	static function on_AfterSave($mvc, &$id, &$rec, $fieldList = NULL)
    {
      if($rec->themeId === NULL){
      	
      	// Ако themeId е NULL, то постинга е начало на нова Тема. Обновяваме
      	// броя на темите в Дъската, където е създаденена темата
    	forum_Boards::updateThemesCount($rec->boardId);
      } else {
      	
      	// Ако themeId не е NULL, То постинга е добавен към тема. Обновяваме броя
      	// на постингите в темата след началния
      	$mvc->updatePostingCount($rec->themeId);
      	
      	// Обновяваме информацията за последния коментар в дъската, където е темата
      	forum_Boards::updateLastComment($rec->boardId, $rec->createdOn);
      }
   }
   
   
   /**
	 * Обновяваме броя на постингите, в темата
	 */
   function updatePostingCount($themeId)
    {
   	   		$query = $this->getQuery();
   	   		
   	   		// Избираме тези постинги, принадлежащи на темата
	        $query->where("#themeId = {$themeId}");
	        $rec = $this->fetch($themeId);
	        $rec->postingsCnt = $query->count();
	        static::save($rec);
   }
}