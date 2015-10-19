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
	var $listFields = 'name, email, web, ip, brid, articleId, comment=@, createdOn=Създаване';
	
		
	/**
	 * Кой може да изтрива коментари
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
	 * Кой има достъп до Спосъка с коментати
	 */
	var $canWrite = 'cms, ceo, admin,blog';
	

	/**
	 * Кой има достъп до Спосъка с коментати
	 */
	var $canDelete = 'cms, ceo, admin,blog';

	
	/**
	 * Мастър ключ към статиите
	 */
	var $masterKey = 'articleId';
    
    
    /**
     * По колко реда от резултата да показва на страница в детайла на документа
     * Стойност '0' означава, че детайла няма да се странира
     */
    var $listItemsPerPage = 200;

	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('articleId', 'key(mvc=blogm_Articles, select=title)', 'caption=Тема, input=hidden, silent');
		$this->FLD('name', 'varchar(64)', 'caption=Име, mandatory, width=65%,placeholder=Името ви (задължително)');
		$this->FLD('email', 'email(64)', 'caption=Имейл, mandatory, width=65%,placeholder=Имейлът ви (задължително)');
   		$this->FLD('web', 'url(72)', 'caption=Сайт, width=65%,placeholder=Вашият сайт или блог');
		$this->FLD('comment', 'richtext(bucket=Notes)', 'caption=Коментар,mandatory,placeholder=Въведете вашия коментар тук');
  		$this->FLD('state', 'enum(pending=Чакащ,active=Публикуван,rejected=Оттеглен)', 'caption=Състояние,mandatory');
  		$this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none, oldFieldName=browserId');
        $this->FLD('ip', 'ip', 'caption=IP,input=none');
	}


    /**
     * Моделна функиця за подготовка на данните, необходими за показването на
     * коментарите към дадена статия и форма за добавянето на нов
     */       
    function prepareComments_($data)
    {
        $query = self::getQuery();
        
        $me = cls::get(get_called_class());
        $fields = $me->selectFields("");
        $fields['-article'] = TRUE;
        
        // Търсим brid в сесията
        $data->brid = log_Browsers::getBrid();
        
        $query->where(array("#articleId = {$data->articleId} AND (#state = 'active' OR #brid = '[#1#]')", $data->brid));
        
        while($rec = $query->fetch()) {
            $data->commentsRecs[$rec->id] = $rec;
            $data->commentsRows[$rec->id] = self::recToVerbal($rec, $fields);
            
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
        if(self::haveRightFor('add', $cRec)) {  
        	$data->commentForm = self::getForm();
            $data->commentForm->setField('state', 'input=none');
            $data->commentForm->setHidden('articleId', $data->articleId);
            
            $Crypt = cls::get('core_Crypt');
            $key = Mode::getPermanentKey();
            $now = $Crypt->encodeVar(time(), $key);
            $data->commentForm->setHidden('renderOn', $now);

            $valsArr = log_Browsers::getVars(array('name', 'email', 'web'));
            
            foreach ($valsArr as $vName => $val) {
                $data->commentForm->setDefault($vName, $val);
            }
            
            $data->commentForm->toolbar->addSbBtn('Изпращане');
        }
    }
	
    
	/**
	 * Нова функция която се извиква blogm_Articles - act_Show
	 * от и рендира коментарите в нов шаблон
	 */
	public static function renderComments_($data, $layout)
	{
        if(count($data->commentsRows)) {
            foreach($data->commentsRows as $row) {
                $commentTpl = $data->ThemeClass->getCommentsLayout();
              	$commentTpl->placeObject($row);  
                $layout->append($commentTpl, 'COMMENTS');
            }
        }

        if($data->commentForm) { 
            $data->commentForm->layout = $data->ThemeClass->getCommentFormLayout();
            $data->commentForm->fieldsLayout = $data->ThemeClass->getCommentFormFieldsLayout();
            $layout->replace($data->commentForm->renderHtml(), 'COMMENT_FORM');
        }
	
		// Връщаме шаблона
		return $layout;
	}
	
	
    /**
     * Всички нови коментари, направени през формата в единичния 
     * изглед на статията се създават в състояние "чакъщ"
     */
    public static function on_BeforeSave($mvc, &$id, &$rec, $fields =  NULL)
    {
        if(!$rec->id) {  
            
            if(!haveRole('cms,ceo,admin') || $rec->state == 'draft') {
                $artRec = $mvc->Master->fetch($rec->articleId);
                $rec->state = ($artRec->commentsMode == 'enabled') ? 'active' : 'pending';
            }

            $rec->ip = core_Users::getRealIpAddr();

            $rec->brid = log_Browsers::getBrid();
            
            $Crypt = cls::get('core_Crypt');
            $key = Mode::getPermanentKey();
            $rec->userDelay = time() - $Crypt->decodeVar($rec->renderOn, $key);
            
            // Начален рейтинг
            $sr = 0;

            // Ако потребителя е посочил уеб-сайт +1
            if($rec->web) $sr += 1;
            
            // Ако има файлови окончания +1
            // $sr += 1 - 1/(1 + str::countInside($rec->web, array('.pdf', '.htm', 'html')));

            // if(stripos($rec->web, '.html')) $sr += 1;


            // Ако в името на сайта има sex, xxx, porn, cam, teen, adult, cheap, sale, xenical, pharmacy, pills, prescription, опционы 

            // Колко линка се съдържат в тялото

            // Колко href= се съдържат или  src=

            // Дали е от UA или RU

            // Да се записва само при нов запис и и когато няма регистриран потребител
            if (core_Users::getCurrent() < 1) {
                log_Browsers::setVars(array('name' => $rec->name, 'email' => $rec->email, 'web' => $rec->web));
            }
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

        $data->query->orderBy('#createdOn', 'DESC');
    }
	
	
	/**
	 *  Ако статията не може да бъде коментираме, премахваме правото за добавяне на нов коментар
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
                    dt::addDays((dt::timestamp2Mysql(dt::now() - $conf->BLOGM_MAX_COMMENT_DAYS)), $artRec->modifiedOn) < dt::verbal2mysql() ) {
                    $res = 'no_one'; // Коментарите са забранени
                } else {
                    $res = 'every_one';  // Коментарите са разрешени
                }
            } else {
                $res = 'no_one'; // Коментарите са забранени
            }
        }
        
        // Могат да се изтриват само оттеглените
        if($action == 'delete' && isset($rec) && $rec->state != 'rejected' && ((!stripos($rec->comment, '<a ')) || $rec->state == 'active')) {
            $res = 'no_one'; 
        }
	}
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, TRUE);
    	
        $row->brid = log_Browsers::getLink($rec->brid);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     * Форма за търсене по дадена ключова дума
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->showFields = 'ip, brid';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input($data->listFilter->showFields, 'silent');
        
        if($ip = $data->listFilter->rec->ip){
            $ip = str_replace('*', '%', $ip);
            $data->query->where(array("#ip LIKE '[#1#]'", $ip));
        }
        
        if($brid = $data->listFilter->rec->brid){
            $data->query->where(array("#brid LIKE '[#1#]'", $brid));
        }
        
        $data->query->orderBy("#createdOn=DESC");
    }
}
