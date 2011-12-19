<?php 


/**
 * Шаблон за писма за масово разпращане
 */
class blast_Emails extends core_Master
{
	
	
	/**
	 * Данните за съобщението, за съответния потребител
	 */
	var $data;
		
	
	/**
	 * Шаблона, без да е заместен с данните за потребителя
	 */
	var $originData = NULL;
	
	
	/**
	 * Данните за заместване на placeHolder' ите
	 */
	var $listData;
	
	
	/**
	 * Текстовата част на мейла
	 */
	var $text = NULL;
	
	
	/**
	 * HTML частта на мейла
	 */
	var $html = NULL;
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Шаблон за масови писма";
    
    
    /**
     * Права
     */
    var $canRead = 'admin, blast';
    
    
    /**
     *  
     */
    var $canEdit = 'admin, blast';
    
    
    /**
     *  
     */
    var $canAdd = 'admin, blast';
    
    
    /**
     *  
     */
    var $canView = 'admin, blast';
    
    
    /**
     *  
     */
    var $canList = 'admin, blast';
    
    
    /**
     *  
     */
    var $canDelete = 'no_one';
    
	
	/**
	 * 
	 */
	var $canBlast = 'admin, blast';
	
	
	/**
	 * 
	 */
	var $interfaces = 'email_DocumentIntf';
	
    
    /**
     * Плгънитите и враперите, които ще се използват
     */
	var $loadList = 'blast_Wrapper, plg_Created, doc_DocumentPlg, plg_State, plg_RowTools, plg_Modified';
       	
	
	/**
	 * 
	 */
	 var $listFields = 'id, listId, from, email, recipient, attentionOf, subject, file1, file2, file3, sendPerMinut, startOn';
	
	
	 /**
	  * Детайла, на модела
	  */
	 var $details = 'blast_ListSend';
	 
	 
	/**
	* Нов темплейт за показване
	*/
	var $singleLayoutFile = 'blast/tpl/SingleLayoutEmails.html';
	
	
	/**
	 * id'то на текущия бласт шаблон
	 */
	var $id = NULL;
	
	
	/**
	 * емейла, към когото се праща шаблона с неговите данни
	 */
	var $mail = NULL;
	 
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('listId', 'key(mvc=blast_Lists, select=title)', 'caption=Лист');
		$this->FLD('from', 'key(mvc=email_Inboxes, select=mail)', 'caption=От');
		$this->FLD('subject', 'varchar', 'caption=Относно, width=100%');
		$this->FLD('textPart', 'richtext(bucket=Blast)', 'caption=Tекстова част, width=100%, height=200px');
		$this->FLD('htmlPart', 'html', 'caption=HTML част, width=100%, height=200px');
		$this->FLD('file1', 'fileman_FileType(bucket=Blast)', 'caption=Файл1');
		$this->FLD('file2', 'fileman_FileType(bucket=Blast)', 'caption=Файл2');
		$this->FLD('file3', 'fileman_FileType(bucket=Blast)', 'caption=Файл3');
		$this->FLD('sendPerMinut', 'int', 'caption=Изпращания в минута, input=none, mandatory');
		$this->FLD('startOn', 'datetime', 'caption=Време на започване, input=none');
		$this->FLD('recipient', 'varchar', 'caption=До');
		$this->FLD('attentionOf', 'varchar', 'caption=На вниманието на');
		$this->FLD('email', 'varchar', 'caption=Емайл');
		$this->FLD('state','enum(draft=Чернова, waiting=Чакащо, active=Активирано, rejected=Оттеглено, closed=Приключено)',
			'caption=Състояние, input=none');
	}
	
	
	/**
	 * Връща стойността от модела в зависимост oт id' то и полето
	 * @access private
	 */
	function getData($id, $mail, $field)
	{
		if ($this->id != $id){
			$this->id = $id;
			$this->setData();
		}
		
		if ($mail === FALSE) {
				
			return $this->data[$field];
		}
		
		if ($this->mail != $mail) {
			$this->mail = $mail;
			$this->setListData();
			
			$this->data = $this->originData;
			
			$this->replace('subject');
			$this->replace('htmlPart');
			$this->replace('textPart');
			$this->replace('recipient');
			$this->replace('attentionOf');
			$this->replace('email');
		}
		
		return $this->data[$field];
	}
	
	
	/**
	 * Взема данните за мейла, ако не са взети
	 * @access private
	 */
	function setData()
	{
		$rec = blast_Emails::fetch(array("#id=[#1#]", $this->id));
		$this->data['subject'] = $rec->subject;
		$this->data['textPart'] = $rec->textPart;
		$this->data['htmlPart'] = $rec->htmlPart;
		$this->data['file1'] = $rec->file1;
		$this->data['file2'] = $rec->file2;
		$this->data['file3'] = $rec->file3;
		$this->data['listId'] = $rec->listId;
		$this->data['from'] = $this->getVerbal($rec,'from');
		$this->data['modifiedOn'] = dt::mysql2verbal($rec->modifiedOn, 'd-m-Y');
		$this->data['recipient'] = $rec->recipient;
		$this->data['attentionOf'] = $rec->attentionOf;
		$this->data['email'] = $rec->email;
		$this->originData = $this->data;
	}
	
	
	/**
	 * Взема данните на потребителя, до когото ще се изпрати мейла
	 * @access private
	 */
	function setListData()
	{	
		//Премахваме старите данни, защото вече работим с нов акаунтs
		unset($this->listData);
		unset($this->text);
		unset($this->html);
		
		//Вземаме персоналаната информация за потребитяля
		$recList = blast_ListDetails::fetch(array("#listId=[#1#] AND #key='[#2#]'", $this->data['listId'], $this->mail));
		$this->listData = unserialize($recList->data);
		
		$urlBg = array($this, 'Unsubscribe', 'mid' => '[#mid#]', 'lang' => 'bg');
		$urlEn = array($this, 'Unsubscribe', 'mid' => '[#mid#]', 'lang' => 'en');
		
		//Създаваме линковете
		$linkBg = ht::createLink('тук', toUrl($urlBg, 'absolute'), NULL, array('target'=>'_blank'));
		$linkEn = ht::createLink('here', toUrl($urlEn, 'absolute'), NULL, array('target'=>'_blank'));
		
		//Заместваме URL кодирания текст, за да може после да се замести плейсхолдера със стойността
		$rep = '%5B%23mid%23%5D';
		$repWith = '[#mid#]';
		$linkBg = str_ireplace($rep, $repWith, $linkBg);
		$linkEn = str_ireplace($rep, $repWith, $linkEn);

		$this->listData['otpisvane'] = $linkBg;
		$this->listData['unsubscribe'] = $linkEn;
		
	}
	
	/**
	 * Замества плейсхолдерите със сътоветните стойност
	 * @access private
	 */
	function replace($field)
	{			
		//Заместваме всички плейсхолдери със съответана стойност, ако в изпратеното поле има такива
		//След това ги записваме в масива $this->data
		if (count($this->listData)) {
			foreach ($this->listData as $key => $value) {
				$this->data[$field] = str_ireplace('[#' . $key . '#]', $value, $this->data[$field]);
			}
		}
	}
	
	
	/**
	 * Взема текстовата част на мейла
	 */
	function getEmailText($id, $emailTo=NULL, $boxFrom=NULL)
	{
		if (!$this->text) {
			$Rich = cls::get('type_Richtext');
			
			$this->text = $this->getData($id, $emailTo, 'textPart');
			//Ако липсва текстовата част, тогава вземаме HTML частта, като такавас
			if (!$this->checkTextPart($this->text)) {
				//Ако липсва текстовата част, тогава вземаме html частта за текстова
				$this->getEmailHtml($id, $emailTo, $boxFrom);
				$this->textFromHtml();
			}
			//Изчистваме richtext' а, и го преобразуваме в чист текстов вид
			$this->text = $Rich->richtext2text($this->text);
			//Създава хедърната част
			$this->text = $this->createHeader('text');
		}
		
		return $this->text;
	}
	
	
	/**
	 * Взема HTML частта на мейла
	 */
	function getEmailHtml($id, $emailTo=NULL, $boxFrom=NULL)
	{
		if (!$this->html) {
			$this->html = $this->getData($id, $emailTo, 'htmlPart');
			if (!$this->checkHtmlPart($this->html)) {
				//Ако лиспва HTML частта, тогава вземаме текстовата, като HTML
				$this->getEmailText($id, $emailTo, $boxFrom);
				$this->htmlFromText();
			}
			//Създава хедърната част
			$this->html = $this->createHeader('html');
		}
		
		return $this->html;
	}
	

	/**
	 * Добавя антетка към HTML и текстовата част
	 */
	function createHeader($type)
	{
		//Очаква данните да са сетнати
		expect($this->data);
		
		//Записваме стария Mode, за да можем да го върнем, след края на операцията
		$oldMode = Mode::get('text');
		
		//Проверяваме какъв е подададения тип и спрямо него променяме Mode.
		if ($type == 'text') {
			Mode::set('text', 'plain');
		} else {
			Mode::set('text', 'html');
		}
		
		//Вземаме шаблона за тялото на съобщението
		$tpl = doc_Postings::getBodyTpl();
		
		//Заместваме всички полета в шаблона с данните за съответния потребител
		$tpl->replace($this->data['subject'], 'subject');
		$tpl->replace($this->data['recipient'], 'recipient');
		$tpl->replace($this->data['attentionOf'], 'attentionOf');
		$tpl->replace($this->data['email'], 'email');
		$tpl->replace($this->data['attentionOf'], 'attentionOf');
		$tpl->replace($this->data['modifiedOn'], 'modifiedOn');
		$tpl->replace($this->$type, 'body');

		//Връщаме стария mode на text
		Mode::set('text', $oldMode);
		
		return $tpl->getContent();
	}
	
	
	/**
	 * Проверява за надеждността на HTML частта
	 * @access private
	 */
	function checkHtmlPart($html)
	{
		if (!str::trim(strip_tags($html))) {
			
			return FALSE;
		}
		
		return TRUE;
	}
	
	
	/**
	 * Проверява за надеждността на текстовата част
	 * @access private
	 */
	function checkTextPart($text)
	{
		if (!str::trim($text)) {
			
			return FALSE;
		}
		
		return TRUE;
	}
	
	
	/**
	 * Преобразува текстовата част в HTML
	 * 
	 * @access private
	 */
	function htmlFromText()
	{
		$Rich = cls::get('type_Richtext');
		$this->html = $Rich->toHtml($this->text)->content;
	}
	
	
	/**
	 * Преобразува HTMl частта в текстова
	 * 
	 * @access private
	 */
	function textFromHtml()
	{
		$this->text = strip_tags($this->html);
	}
	
	
	/**
	 * Взема прикрепените файлове
	 */
	function getEmailAttachments($id)
	{
		$file[1] = $this->getData($id, FALSE, 'file1');
		$file[2] = $this->getData($id, FALSE, 'file2');
		$file[3] = $this->getData($id, FALSE, 'file3');
		
		return $file;
	}
	
	
	/**
	 * Връща заглавиете по подразбиране без да се заменят placeholder' ите
	 */
	function getDefaultSubject($id, $emailTo=NULL, $boxFrom=NULL)
	{
		$subject = $this->getData($id, $emailTo, 'subject');
		
		return $subject;
	}
	
	
	/**
	 * До кой имейл или списък ще се изпраща
	 */
	function getDefaultEmailTo($id)
	{
		
		return NULL;
	}
	
	
	/**
	 * Връща id' то на пощенската кутия от нашата система
	 */
	function getDefaultBoxFrom($id)
	{
		//Ако няма въведен изпращач, тогава използваме конфигурационната константа по default
		//TODO да се вземе от конфигурационната константа
		$from = 'team@ep-bags.com';
		
		return $from;
	}
	
	
	/**
	 * msgId на писмото на което в отговор е направен този постинг
	 */
	function getInReplayTo($id)
	{
		
		return NULL;
	}
		
	
	/**
	 *  Извиква се след въвеждането на данните
	 */
	function on_AfterInputEditForm($mvc, &$form)
	{
		if (!$form->isSubmitted()){
			
            return;
        }
        
		//Проверяваме дали имаме текстова или HTML част. Задължително е да имаме поне едно от двете
		if (!$this->checkTextPart($form->rec->textPart)) {
			if (!$this->checkHtmlPart($form->rec->htmlPart)) {
				$form->setError('textPart, htmlPart', 'Текстовата част или HTML частта трябва да се попълнят.');
			}
		}
	}
	
	
	/**
	 * Добавя сътоветени бътони в тулбара, в зависимост от състоянието
	 */
	function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		$id = $data->rec->id;
		$state = $data->rec->state;
		
		//Добавяме два нови бутона в тулбара в зависимост от състоянието
		//Ако състоянието е затворено не се добавят бутони
		//Не може да се спира или активира задачи, които са в състояние затворено
		if ($state != 'closed') {
			
			//Ако състоянието е активно, тогава не се добавя бутона Активирай
			if ($state != 'active') {
				$data->toolbar->addBtn('Активирай', array($mvc, 'changestate', $id), 'class=btn-conto');
			}
			
			//Ако състоянието е оттеглено, тогава не се добавя бутона Спри
			if ($state != 'rejected') {
				$data->toolbar->addBtn('Спри', array($mvc, 'changestate', $id,'action' => 'reject'), 'class=btn-cancel');
			}
		}
	}
	
	
	/**
	 * Екшън за активиране или спиране на изпращане на мейли
	 */
	function act_ChangeState()
	{
		//Права за работа с екшъна
		requireRole('blast, admin');
		
		//Вземаме get и post променливите
		$form = cls::get('core_Form');
		
		expect($id = Request::get('id', 'int'));
		
		expect($rec = $this->fetch($id));
		
		$act = Request::get('action');
		
		//URL' то където ще се редиректнем
		$retUrl = getRetUrl()?getRetUrl():array($this);
		
		//Ако бласта е приключен, не можем повече да го активираме или спираме
		if ($rec->state == 'closed') {
			
			$redirect = redirect($retUrl, FALSE, tr("Не може да редактирате статуса на приключените бласт мейли."));
			
			$res = new Redirect($redirect);
			
	        return FALSE;
		}
				
		//Сменя състоянието на отхвърлено
		if ($act == 'reject') {
			$rec = new stdClass();
			$rec->id = $id;
			$rec->state = 'rejected';
			
			if (self::save($rec)) {
				$redirect = redirect($retUrl, FALSE, tr("Вие успешно \"оттеглихте\" blast №{$id}."));
			} else {
				$redirect = redirect($retUrl, FALSE, tr("Възникна грешка. Моля опитайте пак."));
			}
			
			$res = new Redirect($redirect);
			
	        return FALSE;
		}
		
		//Добавяме бутони на формата
		$form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
		
        $form->input();
        
        //Ако формата е субмитната
		if($form->isSubmitted()) {
			$perMin = (int)Request::get('sendPerMinut');
			$startOn = Request::get('startOn');
			
			//Може да се въведата само целочислени стойности
			if (!$perMin) {
				$form->setError('sendPerMinut', 'Полето е задължително и трябва да съдържа целочислена стойност.');
			}
			
			$startOn = dt::verbal2mysql($startOn);
			
			//Ако е въведена коректна дата, тогава използва нея
			//Ако не е въведено нищо, тогава използва сегашната дата
			//Ако е въведена грешна дата показва съобщение за грешка
			if (!$startOn) {
				$form->setError('startOn', 'Въведената дата е грешна.');
			}
			
			//Ако нямам грешки във валидирането на формата
            if(!$form->gotErrors()) {
            	$rec->startOn = $startOn;
            	$rec->sendPerMinut = $perMin;
            	$rec->state = 'waiting';
            	//Записваме новите данни и сменяме статуса на чакащ
                if ($this->save($rec)) {
                	$redirect = redirect($retUrl, FALSE, tr("Успешно активирахте бласт №{$id}."));
                } else {
                	$redirect = redirect($retUrl, FALSE, tr("Възникна грешка. Моля опитайте пак."));
                }
                
                $res = new Redirect($redirect);
			
		        return FALSE;
            } 
        }
        
        //Заглавие на формата
        $form->title = "Стартиране на масово разпращане";
        
        //Полетата, които ще се покажат във формата
       	$form->FNC('sendPerMinut', 'int', 'caption=Изпращания в минута, mandatory');
	    $form->FNC('startOn', 'datetime', 'caption=Време на започване');

	    if ($rec->sendPerMinut) {
	    	$form->setDefault('sendPerMinut', $rec->sendPerMinut);
	    }
	    
		if ($rec->startOn) {
	    	$form->setDefault('startOn', $rec->startOn);
	    }
	    
	    //Кои полета да се показват
	    $form->showFields = 'sendPerMinut, 
                             startOn';
        
        return $this->renderWrapping($form->renderHtml());
        
	}
	
	
	/**
	 * Сортиране на записите
	 */
	function on_BeforePrepareListRecs($mvc, &$res, $data)
	{
		//Добавя филтър за търсене по "Тема" и "Време на заоичване"
		$data->listFilter->FNC('filter', 'varchar', 'caption=Търсене,input, width=100%, 
				hint=Търсене по "Тема" и "Време на започване"');
    	
    	$data->listFilter->showFields = 'filter';
        
        $data->listFilter->view = 'horizontal'; 
		
        //Добавяме бутон "Филтрирай"
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
		
		$filterInput = trim($data->listFilter->input()->filter);
		
    	if($filterInput) {
 			$data->query->where(array("#startOn LIKE '%[#1#]%' OR #subject LIKE '%[#1#]%'", $filterInput));
		}
		
		// Сортиране на записите по времето им на започване
		$data->query->orderBy('startOn', 'DESC');
	}
	
	
	/**
	 * Получава управлението от cron' а и проверява дали има съобщения за изпращане
	 */
	function checkForSending()
	{
		$query = blast_Emails::getQuery();
		$now = (dt::verbal2mysql());
		$query->where("#startOn <= '$now'");
		$query->where("#state != 'closed'");
		//Проверяваме дали имаме запис, който не е затворен и му е дошло времето за активиране
		while ($rec = $query->fetch()) {
			switch ($rec->state) {
				//Ако не е активен, да не се прави нищо
				case 'draft':
					
					return ;
				break;
				//Ако е на изчакване, тогава стартираме процеса
				case 'waiting':
					//променяме статуса на мейла на чакащ
					$recNew = new stdClass();
					$recNew->id = $rec->id;
					$recNew->state = 'active';
					blast_Emails::save($recNew);
					
					$queryList = blast_ListDetails::getQuery();
					$queryList->where("#listId = '$rec->listId'");
					
					//Записваме всички имейли в модела за изпращане, окъдето по - късно ще ги вземем за изпращане
					while ($recList = $queryList->fetch()) {
						$recListSend = new stdClass();
						$recListSend->mail = $recList->id;
						$recListSend->emailId = $rec->id;
						
						blast_ListSend::save($recListSend, NULL, 'IGNORE');
					}
					//Стартираме процеса на изпращане
					$this->beginSending($rec);
											
				break;
				
				//Ако процеса е активен, тогава продължава с изпращането на мейли до следващите получатели
				case 'active':
					$this->beginSending($rec);
				break;
				
				default:
					return ;
				break;
			}
		}	
	}
	    
	
	/**
	 * Обработва данните и извиква фукцията за ипзращане на имейлите
	 */
	function beginSending($rec)
	{
		//Записваме в лога
		blast_Emails::log("Изпращене на бласт мейли с id {$rec->id}.");
		
		$containerId = $rec->containerId;
		$fromEmail = $rec->from;
		
		//Вземаме ($rec->sendPerMinut) мейли, на които не са пратени имейли
		$query = blast_ListSend::getQuery();
		$query->where("#emailId = '$rec->id'");
		$query->where("#sended IS NULL");
		$query->limit($rec->sendPerMinut);
		//Ако няма повече пощенски кутии, на които не са пратени мейли сменяме статуса на затворен
		if (!$query->count()) {
			$recNew = new stdClass();
			$recNew->id = $rec->id;
			$recNew->state = 'closed';
			blast_Emails::save($recNew);
			
			return ;
		}
		
		//обновяваме времето на изпращане на всички мейли, които сме взели.
		while ($recListSend = $query->fetch()) {
			$listMail[] = blast_ListSend::getVerbal($recListSend, 'mail');
			$recListSendNew = new stdClass();
			$recListSendNew->id = $recListSend->id;
			$recListSendNew->sended = dt::verbal2mysql();
			blast_ListSend::save($recListSendNew);
		}
		
		//Вземаме всички пощенски кутии, които са блокирани
		$queryBlocked = blast_Blocked::getQuery();
		while ($recBlocked = $queryBlocked->fetch()) {
			$listBlocked[] = $recBlocked->mail;
		}
		
		//Премахваме пощенските кутии от листата за изпращане, на които няма да изпращаме
		if (is_array($listMail)) {
			if (is_array($listBlocked)) {
				$listAllowed = array_diff($listMail, $listBlocked);
			} else {
				$listAllowed = $listMail;
			}
		}
		
		if (count($listAllowed)) {
			foreach ($listAllowed as $toEmail) {
				//Извикваме функцията, която ще изпраща имейлите
	
				$options = array(
					'no_thread_hnd' => 'no_thread_hnd',
					'attach' => 'attach'
				);
				//Извикваме метода за изпращане на мейли
				$Sent = cls::get('email_Sent');
				$Sent->send($containerId, $toEmail, NULL, $fromEmail, $options);
			}
		}
		
	}
		
	
	/**
     * Изпълнява се след подготвяне на формата за редактиране
     */
    function on_AfterPrepareEditForm(&$mvc, &$res, &$data)
    {
    	//Слага state = draft по default при нов запис
    	if (!$data->form->rec->id) {
            $data->form->setDefault('state', 'draft');
        }
        
        //Добавя в лист само списъци на с е-мейли
        $query = blast_Lists::getQuery();
		$query->where("#keyField = 'email'");
		
		while ($rec = $query->fetch()) {
			$files[$rec->id] = $rec->title;
		}
		
		//Ако няма нито един запис, тогава редиректва към станицата за добавяне на списъци.
		if (!$files) {
			$redirect = redirect(array('blast_Lists', 'add'), FALSE, tr("Нямате добавен списък. Моля добавете."));
			
			$res = new Redirect($redirect);
	
	        return FALSE;
		}
		
		$form = $data->form;
		
		$form->setOptions('listId', $files);
    }  
	
	
	/**
     * Функция, която се изпълнява от крона и стартира процеса на изпращане на blast мейли
     */
    function cron_SendEmails()
    {		
		$this->checkForSending();
		
		return 'Изпращането приключи';
    }
    
	
	/**
     * Изпълнява се след създаването на модела
     */
	function on_AfterSetupMVC($mvc, $res)
    {
    	$res .= "<p><i>Нагласяне на Cron</i></p>";
        
    	//Данни за работата на cron
        $rec->systemId = 'SendEmails';
        $rec->description = 'Изпращане на много имейли';
        $rec->controller = $this->className;
        $rec->action = 'SendEmails';
        $rec->period = 10;
        $rec->offset = 0;
        $rec->delay = 0;
		$rec->timeLimit = 500;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да изпраща много имейли.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да изпраща имейли.</li>";
        }
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на blast мейлите
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Blast', 'Прикачени файлове в масовите мейли', NULL, '104857600', 'user', 'user');

	}
	
	
	/**
     * Интерфейсен метод на doc_DocumentIntf
     */
	function getDocumentRow($id)
	{
		$rec = $this->fetch($id);
		
		$subject = $this->getVerbal($rec, 'subject');
		
		//Ако заглавието е празно, тогава изписва сътоветния текст
        if(!trim($subject)) {
            $subject = '[' . tr('Липсва заглавие') . ']';
        }
		
        //Заглавие
        $row->title = $subject;
		
        //Създателя
		$row->author =  $this->getVerbal($rec, 'createdBy');
		
		//Състояние
        $row->state  = $rec->state;
		
        //id на създателя
        $row->authorId = $rec->createdBy;
        
		return $row;
	}
	
	
	/**
	 * Добавяне или премахване на е-мейл в блокираните мейли
	 * 
	 * @todo Да се промени дизайна
	 */
	function act_Unsubscribe()
	{
		//GET променливите от линка
		$mid = Request::get("mid");
		$lang = Request::get("lang");
		$uns = Request::get("uns");
		if ($uns == 'del') {
			if (isset($mid)) {
				$act = 'add';
				$rec->mail = email_Sent::fetchField("#mid='$mid'", 'emailTo');
				
				//Добавя е-мейла към листата на блокираните бласт мейли
				if ($rec->mail) {
					blast_Blocked::save($rec, NULL, 'IGNORE');
				}
				
				//Текста, който ще се показва на екрана, след операцията
				if ($lang == 'bg') {
					$click = 'тук';
					$res = 'Ако искате да премахнете е-мейла си от листата на блокираните, моля натиснете ';
				} else {
					$click = 'here';
					$res = 'If you want to remove your e-mail from the blocked list, please click ';
				}
				
			}
		} else {
			$act = 'del';
			if ($uns == 'add') {
				
				if (isset($mid)) {
					$rec->mail = email_Sent::fetchField("#mid='$mid'", 'emailTo');
					//Премахва е-мейла от листата на блокирание бласт мейли
					if ($rec->mail) {
						blast_Blocked::delete("#mail='$rec->mail'");
					}
				}
			}
			
			//Текста, който ще се показва на екрана, след операцията
			if ($lang == 'bg') {
				$click = 'тук';
				$res = 'Ако не искате да получавате повече писма от нас, моля натиснете ';
			} else {
				$click = 'here';
				$res = 'If you do not wish to receive emails from us, please click ';
			}
		}

		//Генерираме линка
		$link = ht::createLink($click, array($this, 'Unsubscribe', 'mid' => $mid, 'lang' => $lang, 'uns' => $act));
		
		$res = $res . $link . '.';
		
		return $res;
	}

	
	/**
	 * След рендиране на singleLayout заместваме плейсхолдера 
	 * с шаблонa за тялото на съобщение в документната система
	 */
	function on_AfterRenderSingleLayout($mvc, $tpl)
 	{
 		$tpl->replace(doc_Postings::getBodyTpl(), 'DOC_BODY');
	}
	
	
	/**
	 * След подготвяне на single излгеда
	 */
	function on_AfterPrepareSingle($mvc, $data)
	{
		//Създаваме и заместваме полето body от текстовата и HTML частта
		$data->row->body = new ET();	
		$data->row->body->append($data->rec->textPart . "\n\n" .$data->rec->htmlPart);
		
		//Създаваме и заместваме полето modifiedOn от датата на последните направени промени
		$data->row->modifiedOn = new ET();	
		$data->row->modifiedOn->append($data->row->modifiedDate);
	}
}