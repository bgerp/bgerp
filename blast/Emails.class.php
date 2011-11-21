<?php 


/**
 * Шаблон за писма за масово разпращане
 */
class blast_Emails extends core_Master
{
	

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
    var $canDelete = 'admin, blast';
    
	
	/**
	 * 
	 */
	var $canBlast = 'admin, blast';
	
    
    /**
     * 
     */
	var $loadList = 'blast_Wrapper, plg_Created, doc_DocumentPlg, plg_State';
       	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('listId', 'key(mvc=blast_Lists, select=title)', 'caption=Лист');
		$this->FLD('from', 'varchar', 'caption=От');
		$this->FLD('subject', 'varchar', 'caption=Тема');
		$this->FLD('textPart', 'richtext', 'caption=Tекстова част');
		$this->FLD('htmlPart', 'text', 'caption=HTML част');
		$this->FLD('file1', 'key(mvc=fileman_Files, select=name)', 'caption=Файл1');
		$this->FLD('file2', 'key(mvc=fileman_Files, select=name)', 'caption=Файл2');
		$this->FLD('file3', 'key(mvc=fileman_Files, select=name)', 'caption=Файл3');
		$this->FLD('sendPerMinut', 'int', 'caption=Изпращания в минута');
		$this->FLD('startOn', 'datetime', 'caption=Време на започване');
		$this->FLD('state','enum(draft=Чернова,active=Активирано,waiting=Чакащо,closed=Приключено)',
			'caption=Състояние,column=none,input=none');
	}
	
	
	/**
	 * Връща персоналния е-мейл
	 */
	function getEmailFor($email, $documentId)
	{
		$recEmails = blast_Emails::fetch(array("#containerId=[#1#]", $documentId));
		
		$rec = new stdClass;
		
		$rec->toEmail = $email;
		$rec->subject = $recEmails->subject;
		$rec->textPart = $recEmails->textPart;
		$rec->htmlPart = $recEmails->htmlPart;
		
		if ($recEmails->file1) {
			$rec->attachments[$recEmails->file1] = fileman_Files::fetchField("id=$recEmails->file1", 'fileHnd');
		}
		
		if ($recEmails->file2) {
			$rec->attachments[$recEmails->file2] = fileman_Files::fetchField("id=$recEmails->file2", 'fileHnd');
		}
		
		if ($recEmails->file3) {
			$rec->attachments[$recEmails->file3] = fileman_Files::fetchField("id=$recEmails->file3", 'fileHnd');
		}
		
		$recList = blast_ListDetails::fetch(array("#listId=[#1#] AND #key='[#2#]'", $recEmails->listId, $email));

		$listData = unserialize($recList->data);
		
		if ($recList) {
			foreach ($listData as $key => $value) {
				$rec->subject = str_ireplace('[#' . $key . '#]', $value, $rec->subject);
				$rec->textPart = str_ireplace('[#' . $key . '#]', $value, $rec->textPart);
				$rec->htmlPart = str_ireplace('[#' . $key . '#]', $value, $rec->htmlPart);
			}
		}
		
		$Richtext = cls::get('type_Richtext');
		$rec->textPart = $Richtext->richtext2text($rec->textPart);
		
		//TODO Да се направи линк, който добавя хората в списъка за блокирани акаунти
		
		return $rec;
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
		if ($query->count()) {
			while ($rec = $query->fetch()) {
				switch ($rec->state) {
					case 'draft':
						//bp($rec);
					//break;
					
					case 'active':
						//променяме статуса на мейла на чакащ
						$recNew = new stdClass();
						$recNew->id = $rec->id;
						$recNew->state = 'waiting';
						blast_Emails::save($recNew);
						
						$queryList = blast_ListDetails::getQuery();
						$queryList->where("#listId = '$rec->listId'");
						
						if ($queryList->count()) {
							
							//Записваме всички емейли в модела за изпращане
							while ($recList = $queryList->fetch()) {
								$recListSend = new stdClass();
								$recListSend->mail = $recList->id;
								$recListSend->listId = $recList->listId;
								
								blast_ListSend::save($recListSend, NULL, 'IGNORE');
							}
							
						}
						
						$this->beginSending($rec);
												
					break;
					
					case 'waiting':
						$this->beginSending($rec);
					break;
					
					default:
						return ;
					break;
				}
			}	
		} 
	}
	
	
	/**
	 * Обработва данните и извиква фукцията за ипзращане на е-мейлите
	 */
	function beginSending($rec)
	{
		$containerId = $rec->containerId;
		$fromEmail = $rec->from;
		
		//Вземаме ($rec->sendPerMinut) мейли, на които не са пратени е-мейли
		$query = blast_ListSend::getQuery();
		$query->where("#listId = '$rec->listId'");
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
		$listAllowed = array_diff($listMail, $listBlocked);
		if (count($listAllowed)) {
			foreach ($listAllowed as $toEmail) {
				//TODO да се премахната коментарите
				//Извикваме функцията, която ще изпраща е-мейлите
				//email_Sender::send($containerId, $fromEmail, $toEmail);
			}
		}
		
	}
	
	
	/**
     * Да сваля имейлите
     */
    function cron_SendEmails()
    {		
		$this->checkForSending();
		
		return 'Изпращенто приключи';
    }
    
	
	/**
     * Изпълнява се след създаването на модела
     */
	function on_AfterSetupMVC($mvc, $res)
    {
    	$res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec->systemId = 'SendEmails';
        $rec->description = 'Изпращане на много е-мейли';
        $rec->controller = $this->className;
        $rec->action = 'SendEmails';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 0;
     // $rec->timeLimit = 200;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да изпраща много е-мейли.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да изпраща е-мейли.</li>";
        }

	}
}
?>