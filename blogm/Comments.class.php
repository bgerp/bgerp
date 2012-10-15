<?php

/**
 * Коментари на статиите
 *
 *
 * @category  bgerp
 * @package   blogm
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class blogm_Comments extends core_Detail {

	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Блог коментари';
	
	
	/**
	 * Единично заглавие
	 */
	var $singleTitle = 'Коментар';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, plg_Created, blogm_Wrapper, plg_State, plg_Sorting, plg_LastUsedKeys, plg_RowNumbering';
	
    
	/**
	 * Поле за лентата с инструменти
	 */
	var $rowToolsField = 'RowNumb';
	
	
	/**
	 * Полета за изглед
	 */
	var $listFields = 'name, email, web, articleId, comment=@, createdOn=Създаване';
	
		
	/**
	 * Кой може да изтрива коментари
	 */
	var $canRead = 'cms, ceo, admin';
	
	
	/**
	 * Кой има достъп до Спосъка с коментати
	 */
	var $canWrite = 'cms, ceo, admin';
	

	/**
	 * Кой има достъп до Спосъка с коментати
	 */
	var $canDelete = 'cms, ceo, admin';

	
	/**
	 * Мастър ключ към статиите
	 */
	var $masterKey = 'articleId';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('articleId', 'key(mvc=blogm_Articles, select=title)', 'caption=Тема, input=hidden, silent');
		$this->FLD('name', 'varchar(64)', 'caption=Име, mandatory, width=65%,placeholder=Името ви (задължително)');
		$this->FLD('email', 'email(64)', 'caption=Имейл, mandatory, width=65%,placeholder=Имейлът ви (задължително)');
   		$this->FLD('web', 'url(72)', 'caption=Сайт, width=65%,placeholder=Вашият сайт или блог');
		$this->FLD('comment', 'richtext', 'caption=Коментар,mandatory,placeholder=Въведете вашия коментар тук');
  		$this->FLD('state', 'enum(pending=Чакъщ,active=Публикуван,rejected=Оттеглен)', 'caption=Състояние,mandatory');
  		$this->FLD('browserId', 'varchar(16)', 'caption=ID на браузър,input=none');
	}


    /**
     * Моделна функиця за подготовка на данните, необходими за показването на
     * коментарите към дадена статия и форма за добавянето на нов
     */       
    function prepareComments_($data)
    {
        $query = $this->getQuery();
        $fields = $this->selectFields("");
        $fields['-article'] = TRUE;
        
        // Търсим browserId в сесията
        $data->browserId = Mode::get('browserId');
        
        // Ако няма в сесията - търсим в Cookie
        if(!$data->browserId) {
            $data->browserId = str::checkHash($_COOKIE['userCookie']['browserId'], 8);
        }

        if($data->browserId){
        	$browserSelect = " OR #browserId = '{$data->browserId}'";
        }

        $query->where("#articleId = {$data->articleId} AND (#state = 'active'{$browserSelect})");
        
        while($rec = $query->fetch()) {
            $data->commentsRecs[$rec->id] = $rec;
            $data->commentsRows[$rec->id] = $this->recToVerbal($rec, $fields);
            
            if($data->commentsRecs[$rec->id]->state == 'pending') {
                $data->commentsRows[$rec->id]->status = 'Чака одобрение';
                $data->commentsRows[$rec->id]->stateColor = '#cceeff';
            } elseif($data->commentsRecs[$rec->id]->state == 'rejected') {
                $data->commentsRows[$rec->id]->status = 'Отхвърлен';
                $data->commentsRows[$rec->id]->stateColor = '#cc6666';
            }

            $data->commentsRows[$rec->id]->name = str::limitLen($data->commentsRows[$rec->id]->name, 32);

            if($data->commentsRows[$rec->id]->web) {
                $data->commentsRows[$rec->id]->name = ht::createLink(
                    $data->commentsRows[$rec->id]->name, 
                    $rec->web, 
                    NULL, 'target=_blank,rel=external nofollow');
            }

            // Аватара на коментиращия
            $data->commentsRows[$rec->id]->avatar = avatar_Plugin::getImg(0, $rec->email, 50);
        }

        // Към статията може ли да има форма за коментари?
        $cRec = (object) array('articleId' => $data->articleId); 
        if($this->haveRightFor('add', $cRec)) {  
        	$data->commentForm = $this->getForm();
            $data->commentForm->setField('state', 'input=none');
            $data->commentForm->setHidden('articleId', $data->articleId);
            
            // Ако $browserId е правилно, и имаме бисквитка да се помни потребителя то ние
            // извличаме информацията на потребителя, който последно е добавил коментара
            // от това $browserId
            if($data->browserId){  
            	$query = static::getQuery();
	        	$query->where("#browserId = '{$data->browserId}'");
	        	$query->orderBy('#createdOn', 'DESC');
                $query->limit(1);
	        	$lastComment = $query->fetch();
	        	
	        	$data->commentForm->setDefault('name', $lastComment->name);
	            $data->commentForm->setDefault('email', $lastComment->email);
	            $data->commentForm->setDefault('web', $lastComment->web);
            }
            $data->commentForm->toolbar->addSbBtn('Изпращане');
        }
    }
	
    
	/**
	 * Нова функция която се извиква blogm_Articles - act_Show
	 * от и рендира коментарите в нов шаблон
	 */
	function renderComments_($data, $layout)
	{
        if(count($data->commentsRows)) {
            foreach($data->commentsRows as $row) {
                $commentTpl = new ET(getFileContent($data->theme . '/Comment.shtml'));
              	$commentTpl->placeObject($row);  
                $layout->append($commentTpl, 'COMMENTS');
            }
        }

        if($data->commentForm) { 
            $data->commentForm->layout = new ET(getFileContent($data->theme . '/CommentForm.shtml'));
            $data->commentForm->fieldsLayout = new ET(getFileContent($data->theme . '/CommentFormFields.shtml'));
            $layout->replace($data->commentForm->renderHtml(), 'COMMENT_FORM');
        }
	
		// Връщаме шаблона
		return $layout;
	}
	
	
    /**
     * Всички нови коментари, направени през формата в единичния 
     * изглед на статията се създават в състояние "чакъщ"
     */
    function on_BeforeSave($mvc, &$id, &$rec, $fields =  NULL)
    {
        if(!$rec->id) {  
            
            if(!haveRole('cms,ceo,admin') || $rec->state == 'draft') {
                $artRec = $mvc->Master->fetch($rec->articleId);
                $rec->state = ($artRec->commentsMode == 'enabled') ? 'active' : 'pending';
            }

            // Търсим browserId в сесията
            $rec->browserId = Mode::get('browserId');
        
            // Ако няма в сесията - търсим в Cookie
            if(!$rec->browserId) {
                $rec->browserId = str::checkHash($_COOKIE['userCookie']['browserId'], 8);
            }
            
            // Ако няма - генерираме ново, и го записваме в сесията и Cookie
            if(!$rec->browserId) {
                $rec->browserId =str::getRand();
            }
            
            // Сетваме сесията
            Mode::setPermanent('browserId', $rec->browserId);

            // Сетваме Бисквитка с добавен хеш към browserId
            $conf = core_Packs::getConfig('blogm');
            setcookie("userCookie[browserId]", str::addHash($rec->browserId, 8), time() + $conf->BLOGM_COOKIE_LIFETIME);
        }
    }


    /**
     * Махаме articleId когато показваме списък коментари към конкретна статия
     */
    function on_AfterPrepareListFields($mvc, $data)
    {
        if($data->masterMvc) {
            unset($data->listFields['articleId']);
        }
    }
	
	
	/**
	 *  Ако статията неможе да бъде коментираме, премахваме правото за добавяне на 
	 *  нов коментар
	 */
	static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{   
        // Конфигурацията на пакета 'blogm' 
        static $conf;

        if(!$conf) {
            $conf = core_Packs::getConfig('blogm');
        }

		// Проверяваме имаме ли запис и дали екшъна е 'add'
        if($action == 'add') {  
            if(isset($rec->articleId)) {
                
                $artRec = $mvc->Master->fetch($rec->articleId);

                // Ако записа е то статията е заключена за коментиране
                if( $artRec->commentsMode == 'disabled' ||
                    $artRec->commentsMode == 'stopped' ||
                    $artRec->state != 'active' || 
                    dt::addDays($conf->BLOGM_MAX_COMMENT_DAYS, $artRec->modifiedOn) < dt::verbal2mysql() ) {
                    $res = 'no_one'; // Коментарите са забранени
                } else {
                    $res = 'every_one';  // Коментарите са разрешени
                }
            } else {
                $res = 'no_one'; // Коментарите са забранени
            }
        }
        
        // Могат да се изтриват само оттеглените
        if($action == 'delete' && isset($rec) && $rec->state != 'rejected') {
            $res = 'no_one'; 
        }
	}
}