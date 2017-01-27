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
	var $loadList = 'plg_RowTools2, plg_Created, blogm_Wrapper, plg_State, plg_Sorting, plg_LastUsedKeys, plg_RowNumbering, plg_Rejected';
	
    
	/**
	 * Поле за лентата с инструменти
	 */
	var $rowToolsField = 'RowNumb';
	
	
	/**
	 * Полета за изглед
	 */
	var $listFields = 'name, email, web, ip, brid, userDelay, spamRate, articleId, comment=@, createdOn=Създаване||Created';
	
		
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
		$this->FLD('comment', 'richtext(bucket=' . blogm_Articles::FILE_BUCKET . ')', 'caption=Коментар,mandatory,placeholder=Въведете вашия коментар тук');
  		$this->FLD('state', 'enum(pending=Чакащ,active=Публикуван,closed=Затворен,rejected=Оттеглен)', 'caption=Състояние,mandatory');
  		$this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none, oldFieldName=browserId');
        $this->FLD('ip', 'ip', 'caption=IP,input=none');
        $this->FLD('userDelay', 'time', 'caption=Спам->Закъснение,input=none');
        $this->FLD('spamRate', 'int', 'caption=Спам->Рейтинг,input=none');

        $this->setDbIndex('ip');
        $this->setDbIndex('brid');
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
            } elseif($data->commentsRecs[$rec->id]->state == 'closed') {
                $data->commentsRows[$rec->id]->status = 'Затворен';
                $data->commentsRows[$rec->id]->stateColor = '#cccccc';
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
            
            // Да се записва само при нов запис и и когато няма регистриран потребител
            log_Browsers::setVars(array('name' => $rec->name, 'email' => $rec->email, 'web' => $rec->web));
        }

        // Начален рейтинг
        $sr = 0;
 
        // Ако потребителя е посочил уеб-сайт +1
        if($rec->web) $sr += 1;
        
        // Ако има файлови окончания +1
        $sr += self::hasWord($rec->web, '.pdf,.html,.htm,.doc,.xls,.ppt,#') ? 1 : 0;
        
        // Ако в името на сайта има sex, xxx, porn, cam, teen, adult, cheap, sale, xenical, pharmacy, pills, prescription, опционы 
        $sr += self::hasWord($rec->web, 'sex,xxx,porn,cam,teen,adult,cheap,sale,xenical,pharmacy,pills,prescription,опционы');
        
        // Ако в името на сайта има директория
        $sr += explode('/', $rec->web) > 2 ? 0.5 : 0;
    
        // Ако има линкове в описанието
        $sr += self::hasWord($rec->comment, array('href=', 'src='));
 
        // Ако в името на сайта има sex, xxx, porn, cam, teen, adult, cheap, sale, xenical, pharmacy, pills, prescription, опционы 
        $sr += self::hasWord($rec->comment, 'sex,xxx,porn,cam,teen,adult,cheap,sale,xenical,pharmacy,pills,prescription,опционы');

        // Ако в коментара има http://
        $sr += self::hasWord($rec->comment, 'http://');
        
        // Ако в коментара има линк 
        $sr += self::hasWord($rec->comment, array('[link=')) ? 2 : 0;

        // Ако е написано за под 50 секунди
        if(isset($rec->userDelay) && $rec->userDelay < 20) {
            $sr += 1;
        }

        // Ако е написано за под 10 секунди
        if(isset($rec->userDelay) && $rec->userDelay < 10) {
            $sr += 1;
        }
        
        // Ако е написано за под 65 секунди
        if(isset($rec->userDelay) && $rec->userDelay < 65) {
            $sr += 0.5;
        }

        // Ако е написано за над 24 часа
        if(isset($rec->userDelay) && $rec->userDelay > 24*3600) {
            $sr += round($rec->userDelay / (24*3600));
        }

        // Изключваме текущия запис, ако е записан
        if($rec->id) {
            $idCond = " AND #id != {$rec->id}";
        } else {
            $idCond = "";
        }
            
        // Има ли от същото IP
        $query = self::getQuery();
        $query->limit(28);
        $cnt = $query->count("#state != 'active' AND #state != 'closed' AND #ip = '{$rec->ip}'" . $idCond);
        if($cnt > 1) {
            $sr +=  pow($cnt, 1/3);
        }

        // Има ли от същия brid?
        $query = self::getQuery();
        $query->limit(28);
        $cnt = $query->count("#state != 'active' AND #state != 'closed' AND #brid = '{$rec->brid}'" . $idCond);
        if($cnt > 1) {
            $sr +=  pow($cnt, 1/3);
        }
        
        $rec->spamRate = (int) $sr;

        if(!$rec->id && $rec->spamRate <= 3) {
            $artRec = $mvc->Master->fetch($rec->articleId);
            $title = $mvc->Master->getVerbal($artRec, 'title');
            bgerp_Notifications::add(
                "Нов коментар към \"{$title}\"", // съобщение
                array($mvc->Master, 'single', $rec->articleId), // URL
                $artRec->createdBy
            );
        }
    }


    /**
     * Проверка дали стринг съдържа дума от подаден списък. 
     * caseinsensitive
     */
    static function hasWord($str, $words)
    {
        $words = arr::make($words);
 
        foreach($words as $w) {  
            if(stripos($str, $w) !== FALSE) {
                return TRUE;
            }
        }

        return FALSE;
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


    /**
     * Изтрива спам коментарите
     */
    function cron_DeleteSPAM()
    {
        // Изтриваме всички чакъщи коментари, които имат спам рейтинг над 10 и са по-стари от 1 ден
        // Изтриваме всички чакъщи коментари, които имат спам рейтинг над 5 и са по-стари от 7 дни
        // Изтриваме всички чакъщи коментари, които имат спам рейтинг над 3 и са по-стари от 10 дни

        $before25m = dt::addSecs(-25*60);
        $before5d = dt::addDays(-5);
        $before14d = dt::addDays(-14);
        $deleteCnt = $deleteCnt = 0;

        // Оттегляме, всички, които по-голям рейтинг от 5 и са на повече от 25 минути или имат по-голям рейтинг от 3 и са от преди повече от 5 дни
        $query = $this->getQuery();
        $query->where("#state = 'pending' AND ((#spamRate > 5 AND #createdOn < '{$before25m}') OR (#spamRate > 3 AND #createdOn < '{$before5d}'))");
        while($rec = $query->fetch()) {
            $rec->state = 'rejected';
            $this->save_($rec, 'state');
            $rejectedCnt++;
        }

        $deleteCnt = $this->delete("#state = 'rejected' AND #createdOn < '{$before14d}'");
        
        if($deleteCnt + $deleteCnt) {
            $res = "Бяха оттеглени {$rejectedCnt} и изтрити {$deleteCnt} СПАМ коментара от блога.";
        }

        return $res;
    }
}
