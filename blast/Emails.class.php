<?php 


/**
 * Шаблон за писма за масово разпращане
 */
class blast_Emails extends core_Master
{
	
	
	/**
	 * Данните за съобщението
	 */
	var $data;
	
	
	/**
	 * Данните за заместване на placeHolder' ите
	 */
	var $listData;
	
	
	/**
	 * 
	 */
	var $text = NULL;
	
	
	/**
	 * 
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
     * 
     */
	var $loadList = 'blast_Wrapper, plg_Created, doc_DocumentPlg, plg_State, plg_RowTools';
       	
	
	/**
	 * 
	 */
	 var $listFields = 'id, listId, from, subject, file1, file2, file3, sendPerMinut, startOn';
	
	
	 /**
	  * 
	  */
	 var $details = 'blast_ListSend';
	 
	 
	/**
	* Нов темплейт за показване
	*/
	var $singleLayoutFile = 'blast/tpl/SingleLayoutEmails.html';
	 
	 
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('listId', 'key(mvc=blast_Lists, select=title)', 'caption=Лист');
		$this->FLD('from', 'key(mvc=email_Inboxes, select=mail)', 'caption=От');
		$this->FLD('subject', 'varchar', 'caption=Тема, width=100%');
		$this->FLD('textPart', 'richtext', 'caption=Tекстова част, width=100%, height=200px');
		$this->FLD('htmlPart', 'html', 'caption=HTML част, width=100%, height=200px');
		$this->FLD('file1', 'fileman_FileType(bucket=Blast)', 'caption=Файл1');
		$this->FLD('file2', 'fileman_FileType(bucket=Blast)', 'caption=Файл2');
		$this->FLD('file3', 'fileman_FileType(bucket=Blast)', 'caption=Файл3');
		$this->FLD('sendPerMinut', 'int', 'caption=Изпращания в минута');
		$this->FLD('startOn', 'datetime', 'caption=Време на започване, input=none');
		$this->FLD('state','enum(draft=Чернова, waiting=Чакащо, active=Активирано, closed=Приключено)',
			'caption=Състояние, input=none');
	}
	
	
	/**
	 * Взема данните за мейла, ако не са взети
	 * @access private
	 */
	function setData($id)
	{
		if ($this->data['id'] != $id) {
			$rec = blast_Emails::fetch(array("#id=[#1#]", $id));
			
			$this->data['subject'] = $rec->subject;
			$this->data['textPart'] = $rec->textPart;
			$this->data['htmlPart'] = $rec->htmlPart;
			$this->data['file1'] = $rec->file1;
			$this->data['file2'] = $rec->file2;
			$this->data['file3'] = $rec->file3;
			$this->data['listId'] = $rec->listId;
			$this->data['from'] = $this->getVerbal($rec,'from');
		}
	}
	
	
	/**
	 * Взема данните на потребителя, до когото ще се изпрати мейла
	 * @access private
	 */
	function setListData($mail)
	{
		expect($this->data);
		$listId = $this->data['listId'];
		
		//Ако нямаме данните за съответния мейл и лист, тогава ги 
		if (($this->listData['listId'] != $listId) || ($this->listData['mail'] != $mail)) {
			//Изчистваме старите полета
			unset($this->listData);
			unset($this->text);
			unset($this->html);
			$this->listData['listId'] = $listId;
			$this->listData['mail'] = $mail;
			
			$recList = blast_ListDetails::fetch(array("#listId=[#1#] AND #key='[#2#]'", $listId, $mail));
			$this->listData['data'] = unserialize($recList->data);
			
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

			$this->listData['data']['otpisvane'] = $linkBg;
			$this->listData['data']['unsubscribe'] = $linkEn;
		}
	}
		
	
	/**
	 * Връща стойността от модела в зависимост oт id' то и полето
	 * @access private
	 */
	function getData($id, $mail, $field, $replace=TRUE)
	{
		$this->setData($id);
		
		$data = $this->data[$field];
		
		//Ако сме въвели mail адрес, тогава проверяваме дали има съответен placeholder
		if ($mail) {
			$data = $this->replace($mail, $data);
		}
		
		return $data;
		
	}
	
	
	/**
	 * Замества плейсхолдерите със сътоветните стойност
	 * @access private
	 */
	function replace($mail, $data)
	{		
		$this->setListData($mail);
		
		if (count($this->listData['data'])) {
			foreach ($this->listData['data'] as $key => $value) {
				
				$data = str_ireplace('[#' . $key . '#]', $value, $data);
			}
		}
		
		return $data;
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
				$this->getEmailHtml($id, $emailTo, $boxFrom);
				$this->textFromHtml();
			}
			$text = $Rich->richtext2text($this->text);
		} else {
			$text = $this->text;
		}
		
		return $text;
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
		}
		
		return $this->html;
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
		$from = $this->getData($id, FALSE, 'from');
		if (!strlen(str::trim($from))) {
			
			//TODO да се вземе от конфигурационната константа
			$from = 'team@ep-bags.com';
		}
		
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
        
		//Проверяваме дали имаме текстова или HTML част. Задължително е да имаме и двете
		if (!$this->checkTextPart($form->rec->textPart)) {
			if (!$this->checkHtmlPart($form->rec->htmlPart)) {
				$form->setError('textPart, htmlPart', 'Текстовата част или HTML частта трябва да се попълнят.');
			}
		}
	}
	
	
	
	/**
	 * След преобразуване на данните в човешки вид
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		//Преди визуализация на singleView да се вземе чистия вид на текста
		$row->textPart = $rec->textPart;
		
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
		//Проверяваме дали имаме запис, който не е затворен и му е дошло времето за стартиране
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
     * Слага state = draft по default при нов запис
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	if (!$data->form->rec->id) {
            $data->form->setDefault('state', 'draft');
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
				$Sent->send($containerId, $toEmail, NULL, NULL, $options);
			}
		}
		
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
        
        $rec->systemId = 'SendEmails';
        $rec->description = 'Изпращане на много имейли';
        $rec->controller = $this->className;
        $rec->action = 'SendEmails';
        $rec->period = 100;
        $rec->offset = 0;
        $rec->delay = 0;
     // $rec->timeLimit = 200;
        
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

        if(!trim($subject)) {
            $subject = '[' . tr('Липсва заглавие') . ']';
        }

        $row->title = $subject;

		$row->author =  $this->getVerbal($rec, 'createdBy');

        $row->state  = $rec->state;
				
		return $row;
	}
	
	
	/**
	 * Добавяне или премахване на е-мейл в блокираните мейли
	 * 
	 * @todo Да се промени дизайна
	 */
	function act_Unsubscribe()
	{
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
				
		$link = ht::createLink($click, array($this, 'Unsubscribe', 'mid' => $mid, 'lang' => $lang, 'uns' => $act));
		
		$res = $res . $link . '.';
		
		return $res;
	}
}

