<?php 


/**
 * Шаблон за писма за масово разпращане
 */
class blast_Emails extends core_Master
{
	
	
	/**
	 * Данните за съобщението
	 */
	protected $data;
	
	
	/**
	 * Данните за заместване на placeHolder' ите
	 */
	protected $listData;
	

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
	var $interfaces = 'email_DocumentIntf';
	
    
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
	 * Взема данните за мейла, ако не са взети
	 */
	protected function setData($id)
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
		}
	}
	
	
	/**
	 * Взема данните на потребителя, до когото ще се изпрати мейла
	 */
	protected function setListData($mail)
	{
		expect($this->data);
		$listId = $this->data['listId'];
		
		if (($this->listData['listId'] != $listId) && ($this->listData['mail'] != $mail)) {
			$this->listData['listId'] = $listId;
			$this->listData['mail'] = $mail;
			
			$recList = blast_ListDetails::fetch(array("#listId=[#1#] AND #key='[#2#]'", $listId, $mail));
			$this->listData['data'] = unserialize($recList->data);
		}
	}
	
	
	/**
	 * Връща стойността от модела в зависимост oт id' то и полето
	 */
	protected function getData($id, $mail, $field, $replace=TRUE)
	{
		$this->setData($id);
		
		$data = $this->data[$field];
		
		if ($mail) {
			$data = $this->replace($mail, $data);
		}
		
		return $data;
		
	}
	
	
	/**
	 * Замества плейсхолдерите със сътоветните стойност
	 */
	protected function replace($mail, $data)
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
		
		$text = $this->getData($id, $emailTo, 'textPart');
		
		return $text;
	}
	
	
	/**
	 * Взема HTML частта на мейла
	 */
	function getEmailHtml($id, $emailTo=NULL, $boxFrom=NULL)
	{
		$html = $this->getData($id, $emailTo, 'htmlPart');
		
		return $html;
	}
	
	
	/**
	 * Взема HTML частта на мейла
	 */
	//function getEmailSubject($id, $emailTo=NULL, $boxFrom=NULL)
	//{
	//	$subject = $this->getData($id, $emailTo, 'subject');
	//	
	//	return $subject;
	//}
	
	
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
	 * Връща html частта заглавиете по подразбиране без да се заменят placeholder' ите
	 */
//	function getDefaultHtml($id, $emailTo=NULL, $boxFrom=NULL)
//	{
//		$subject = $this->getData($id, FALSE, 'htmlPart');
//		
//		
//		return $subject;
//	}
	
	
	/**
	 * Връща текстовата част по подразбиране без да се заменят placeholder' ите
	 */
//	function getDefaultText($id, $emailTo=NULL, $boxFrom=NULL)
//	{
//		$subject = $this->getData($id, FALSE, 'textPart');
//		
//		return $subject;
//	}
	
	
	/**
	 * До кой емейл или списък ще се изпраща
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
			return 'yusein@ep-bags.com';
		return NULL;
	}
	
	
	/**
	 * msgId на писмото на което в отговор е направен този постинг
	 */
	function getInReplayTo($id)
	{
		
		return NULL;
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
				//Извикваме функцията, която ще изпраща е-мейлите
				
				$options = array(
					'no_thread_hnd' => TRUE,
					'attach' =>TRUE
				);
				
				$Sent = cls::get('email_Sent');
				$Sent->send($containerId, $toEmail, NULL, NULL, $options);
			}
		}
		
	}
	
	
	/**
     * Да сваля имейлите
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
        $rec->description = 'Изпращане на много е-мейли';
        $rec->controller = $this->className;
        $rec->action = 'SendEmails';
        $rec->period = 100;
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
        
        if(str::trim($rec->from)) {
            $row->author =  $this->getVerbal($rec, 'from');
        } else {
        	//TODO да се вкара в конфигурационния файл
            $row->author = "<small>team@ep-bags.com</small>";
        }
 
        $row->state  = $rec->state;
				
		return $row;
	}
	
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $id
	 */
	//function getHandle($id)
	//{
	//	return $id;
	//}
	
}
?>