<?php 


/**
 * Максимално време за еднократно фетчване на писма
 */
defIfNot('IMAP_MAX_FETCHING_TIME',  30);

/**
 * Максималната разрешена памет за използване
 */
defIfNot('MAX_ALLOWED_MEMORY', '800M');


/**
 * Входящи писма
 */
class email_Messages extends core_Master
{
    /**
     * Поддържани интерфейси
     */
	var $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Получени имейли";
    
    
    /**
     * Права
     */
    var $canRead = 'admin, email';
    
    
    /**
     *  
     */
    var $canEdit = 'no_one';
    
    
    /**
     *  
     */
    var $canAdd = 'admin, email';
    
    
    /**
     *  
     */
    var $canView = 'admin, email';
    
    
    /**
     *  
     */
    var $canList = 'admin, email';
    

    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, email';

    
    /**
     *  
     */
    var $canDelete = 'no_one';
    
	
	/**
	 * 
	 */
	var $canEmail = 'admin, email';
	
    
    /**
     * 
     */
	var $loadList = 'email_Wrapper, doc_DocumentPlg, plg_RowTools, 
		 plg_Printing, email_plg_Document';
    
	
	/**
	 * Нов темплейт за показване
	 */
	var $singleLayoutFile = 'email/tpl/SingleLayoutMessages.html';
	
	
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/email.png';
       

    /**
     * Абривиатура
     */
    var $abbr = "E";
    

    /**
     * Първоначално състояние на документа
     */
    var $firstState = 'closed';
    
    /**
     *
     */
    var $listFields = 'id,accId,date,fromEml=От,toEml=До,subject,boxIndex,createdOn,createdBy';

	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('accId', 'key(mvc=email_Inboxes,select=email)', 'caption=Акаунт');
		$this->FLD("messageId", "varchar", "caption=Съобщение ID");
		$this->FLD("subject", "varchar", "caption=Тема");
		$this->FLD("fromEml", "email", 'caption=От->Имейл');
		$this->FLD("fromName", "varchar", 'caption=От->Име');
		$this->FLD("toEml", "email", 'caption=До->Имейл');
        $this->FLD("toBox", "email", 'caption=До->Кутия');
		$this->FLD("headers", "text", 'caption=Хедъри');
		$this->FLD("textPart", "richtext", 'caption=Текстова част');
		$this->FLD("spam", "int", 'caption=Спам');
		$this->FLD("lg", "varchar", 'caption=Език');
   		$this->FLD("date", "datetime(format=smartTime)", 'caption=Дата');
		$this->FLD('hash', 'varchar(32)', 'caption=Keш');
		$this->FLD('country', 'key(mvc=drdata_countries,select=commonName)', 'caption=Държава');
		$this->FLD('fromIp', 'ip', 'caption=IP');
		$this->FLD('files', 'keylist(mvc=fileman_Files)', 'caption=Файлове, input=none');		
		$this->FLD('emlFile', 'key(mvc=fileman_Files)', 'caption=eml файл, input=none');
		$this->FLD('htmlFile', 'key(mvc=fileman_Files)', 'caption=html файл, input=none');
		$this->FLD('boxIndex', 'int', 'caption=Индекс');
	
		$this->setDbUnique('hash');
	}
	
		
	/**
	 * Взема записите от пощенската кутия и ги вкарва в модела
	 *
	 * @param number $oneMailId - Потребителя, за когото ще се проверяват записите.
	 * 							Ако е празен, тогава ще се проверяват за всички.
	 * @param boolean $deleteFetched TRUE - изтрива писмото от IMAP при успешно изтегляне
	 * @return boolean
	 */
	function getMailInfo($oneMailId = FALSE, $deleteFetched = FALSE)
	{  
		ini_set('memory_limit', MAX_ALLOWED_MEMORY);
		        
		$accQuery = email_Accounts::getQuery();


        while ($accRec = $accQuery->fetch("#state = 'active'")) {
			$imapConn = cls::get('email_Imap', array('host' => $accRec->server,
                                                     'port' => $accRec->port,
                                                     'user' => $accRec->user,
                                                     'pass' => $accRec->password,
                                                     'subHost' => $accRec->subHost,
                                                     'folder' => "INBOX",
                                                     'ssl' => $accRec->ssl));
			
 			// Логването и генериране на съобщение при грешка е винаги в контролерната част
			if ($imapConn->connect() === FALSE) {
                
                $this->log("Не може да се установи връзка с пощенската кутия на <b>\"{$accRec->user} ({$accRec->server})\"</b>. " .
                           "Грешка: " . $imapConn->getLastError());

				$htmlRes .= "\n<li style='color:red'> Възникна грешка при опит да се свържем с пощенската кутия: <b>{$arr['user']}</b>".
					$imapConn->getLastError().
				"</li>";
				
				continue;
			}

 			$htmlRes .= "\n<li> Връзка с пощенската кутия на: <b>\"{$accRec->user} ({$accRec->server})\"</b></li>";
            
            // Получаваме броя на писмата в INBOX папката
			$numMsg = $imapConn->getStatistic('messages');

			// До коя секунда в бъдещето максимално да се теглят писма?
            $maxTime = time() + IMAP_MAX_FETCHING_TIME;

            // даваме достатъчно време за изпълнението на PHP скрипта
			set_time_limit(IMAP_MAX_FETCHING_TIME + 49);
            
            // Правим цикъл по всички съобщения в пощенската кутия
            // Цикълът може да прекъсне, ако надвишим максималното време за сваляне на писма
            for ($i = 1; ($i <= $numMsg) && ($maxTime > time()); $i++) {
                
                if(is_array($testMsgs) && !in_array($i, $testMsgs)) continue;
     
                $mail = new email_Mime();

                Debug::log("Започва обработката на е-мейл MSG_NUM = $i");

                $hash = $mail->getHash($imapConn->getHeaders($i));

                if ($this->fetch("#hash = '{$hash}'", 'id')) {
                    Debug::log("Е-мейл MSG_NUM = $i е вече при нас, пропускаме го");
            		$htmlRes .= "\n<li> Skip: $hash</li>";
            	} else {
               		$htmlRes .= "\n<li style='color:green'> Get: $hash</li>";
                    
                    Debug::log("Започваме да сваляме и парсираме е-мейл MSG_NUM = $i");

                    $mail->parseAll($imapConn->getEml($i));
                    
                    Debug::log("Композираме записа за е-мейл MSG_NUM = $i");

	               	$rec = $mail->getEmail();
 	                // Само за дебъг. Todo - да се махне
	                $rec->boxIndex = $i;
	
	               	$rec->accId = $accRec->id;
	                
                    Debug::log("Записваме -мейл MSG_NUM = $i");
	                $saved = email_Messages::save($rec);
	                
	                // Добавя грешки, ако са възникнали при парсирането
	                if(count($mail->errors)) {
	                    foreach($mail->errors as $err) {
	                        $this->log($err . " ({$i})", $rec->id);
	                    }
	                }
            	}
	
               	if ($deleteFetched) {
					// $imapConn->delete($i);
               	}
            }
            
			$imapConn->expunge();

			$imapConn->close();
			
		}
		
		return $htmlRes;
	}


    /**
	 * TODO ?
	 * Преобразува containerId в машинен вид
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec, $fields)
	{ 
		$row->containerId = $rec->containerId;
		
        if(!$rec->subject) {
		    $row->subject = '[' . tr('Липсва заглавие') . ']';
        }

        $row->subject .= " ($rec->boxIndex)";

		if ($rec->files) {
			$vals = type_Keylist::toArray($rec->files);
			if (count($vals)) {
				$row->files = '';
				foreach ($vals as $keyD) { 
					$row->files .= fileman_Download::getDownloadLinkById($keyD);
				}
			}
		}
 
        if(!$rec->toBox) {
            $row->toBox = $row->toEml;
        }
        
        if($rec->fromIp && $rec->country) {
            $row->fromIp .= " ($row->country)";
        }

         
        if(trim($rec->fromName) && (strtolower(trim($rec->fromName)) != strtolower(trim($rec->fromEml)))) {
            $row->fromEml = $row->fromEml . ' (' . trim($row->fromName) . ')';
        }
		
        if($rec->emlFile) {
		    $row->emlFile  = fileman_Download::getDownloadLinkById($rec->emlFile);
        }

        if($rec->htmlFile) {
		    $row->htmlFile = fileman_Download::getDownloadLinkById($rec->htmlFile);
        }
		
 		
		$pattern = '/\s*[0-9a-f_A-F]+.eml\s*/';
		$row->emlFile = preg_replace($pattern, 'EMAIL.eml', $row->emlFile);
		
		$pattern = '/\s*[0-9a-f_A-F]+.html\s*/';
		//$row->htmlFile = preg_replace($pattern, 'EMAIL.html', $row->htmlFile);
		
		$row->files .= $row->emlFile . $row->htmlFile;

        $row->iconStyle = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
        
        if($fields['-list']) {
            $row->textPart = mb_Substr($row->textPart, 0, 100);
        }
	}
	
	
	/**
     * Да сваля имейлите
     */
    function act_DownloadEmails()
    {   
        requireRole('admin');
        
		$mailInfo = $this->getMailInfo();
		
		return $mailInfo;
    }
    
    
	/**
     * Сваля и изтрива от IMAP свалените имейли.
     */
    function act_DownloadAndDelete()
    {		
		$mailInfo = $this->getMailInfo(NULL, TRUE /* изтриване след изтегляне */);
		
		return $mailInfo;
    }
    
    
	/**
     * Да сваля имейлите по - крон
     */
    function cron_DownloadEmails()
    {		
		$mailInfo = $this->getMailInfo();
		
		return $mailInfo;
    }
    
	
	/**
     * Изпълнява се след създаването на модела
     */
	function on_AfterSetupMVC($mvc, $res)
    {
    	$res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec->systemId = 'DownloadEmails';
        $rec->description = 'Сваля и-мейлите в модела';
        $rec->controller = $this->className;
        $rec->action = 'DownloadEmails';
        $rec->period = 2;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да сваля имейлите в модела.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да сваля имейлите.</li>";
        }
        
        return $res;
    }
    
    
    
    /******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ НА email_DocumentIntf
     * 
     ******************************************************************************************/

    /**
	 * Текстов вид (plain text) на документ при изпращането му по имейл 
	 *
	 * @param int $id ид на документ
	 * @param string $emailTo
	 * @param string $boxFrom
	 * @return string plain text
	 */
	public function getEmailText($id, $emailTo = NULL, $boxFrom = NULL)
	{
		return static::fetchField($id, 'textPart');
	}
	
	
	/**
	 * Прикачените към документ файлове
	 *
	 * @param int $id ид на документ
	 * @return array 
	 */
	public function getEmailAttachments($id)
	{
		/**
		 * @TODO
		 */
		return array();
	}
	
	/**
	 * Какъв да е събджекта на писмото по подразбиране
	 *
	 * @param int $id ид на документ
	 * @param string $emailTo
	 * @param string $boxFrom
	 * @return string
	 */
	public function getDefaultSubject($id, $emailTo = NULL, $boxFrom = NULL)
	{
		return 'FW: ' . static::fetchField($id, 'subject');
	}
	
	
	/**
	 * До кой имейл или списък с е-мейли трябва да се изпрати писмото
	 *
	 * @param int $id ид на документ
	 */
	public function getDefaultEmailTo($id)
	{
		return '';
	}
	
	
	/**
	 * Адреса на изпращач по подразбиране за документите от този тип.
	 *
	 * @param int $id ид на документ
	 * @return int key(mvc=email_Inboxes) пощенска кутия от нашата система
	 */
	public function getDefaultBoxFrom($id)
	{
		/**
		 * @TODO Това вероятно трябва да е inbox-а на текущия потребител.
		 */
		return 'me@here.com';
	}
	
	
	/**
	 * Писмото (ако има такова), в отговор на което е направен този постинг
	 *
	 * @param int $id ид на документ
	 * @return int key(email_Messages) NULL ако документа не е изпратен като отговор 
	 */
	public function getInReplayTo($id)
	{

		return NULL;
	}
	

    
    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/

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

        $row->title = $subject;// . " ({$rec->boxIndex})";
        
        if(trim($rec->fromName)) {
            $row->author =  $this->getVerbal($rec, 'fromName');
        } else {
            $row->author = "<small>{$rec->fromEml}</small>";
        }
 
        $row->authorEmail = $rec->fromEml;

        $row->state  = $rec->state;

        return $row;
    }
    
    
    /**
     * Рутиране на писмо още преди записването му.
     * 
     * Тук писмата се рутират при възможност директно в нишката, за която са предназначени.
     * Ако това рутиране пропадне, задейства се метода @see doc_DocumentPlg::on_AfterRoute() и 
     * той изпраща писмото в специална папка за несортирани писма. От там по-късно писмата биват 
     * рутирани @link email_Router.
     *
     * @param stdClass $rec запис на модела email_Messages
     */
    public function route_($rec)
    {
    	$rec->threadId = $this->extractThreadId($rec);
    }
    
    
    /**
     * Извлича при възможност треда от наличната информация в писмото
     * 
     * Първо се прави опит за извличане на тред от MIME хедърите и ако той пропадне, тогава се
     * прави опит за извличане на тред от subject-а. 
     *
     * @param StdClass $rec запис на модела @link email_Messages
     * @return int key(mvc=doc_Threads) NULL ако треда не може да бъде извлечен
     */
    protected function extractThreadId($rec)
    {
    	$threadId = NULL;
    	
    	// Опит за извличане на ключ на тред от MIME хедърите
    	$threadKeyHdr = $this->extractHdrThreadKey($rec->headers);

    	if (!empty($threadKeyHdr)) {
    		$threadId = static::getThreadByHandle($threadKeyHdr);	
    	}
    	
    	if (empty($threadId)) {
    		// Опит за извличане на ключ на тред от subject. В един събджект може да нула или 
    		// повече кандидати за хендлъри на тред.
    		$threadHnds = static::extractSubjectThreadHnds($rec->subject);
    		
    		// Премахваме кандидата, който е маркиран като хендлър на тред от друга инстанция
    		// на BGERP. Това маркиране става чрез MIME хедъра 'X-Bgerp-Thread'
    		if (!empty($rec->headers['X-Bgerp-Thread']) && !empty($threadHnds[$rec->headers['X-Bgerp-Thread']])) {
    			unset($threadHnds[$rec->headers['X-Bgerp-Thread']]);
    		}
    		
    		// Намираме първия кандидат за тред-хендлър на който съответства съществуващ тред. 
	    	foreach ($threadHnds as $handle) {
	    		$threadId = static::getThreadByHandle($handle);
	    		if (!empty($threadId)) {
	    			break;
	    		}
	    	}
    	}
    	
    	return $threadId;
    }
    

    /**
     * Намира тред по хендъл на тред.
     *
     * @param string $handle хендъл на тред
     * @return int key(mvc=doc_Threads) NULL ако няма съответен на хендъла тред
     */
    protected static function getThreadByHandle($handle)
    {
    	return doc_Threads::getByHandle($handle);
    }
    
    
    /**
     * Извлича ключ на тред от MIME хедърите на писмо (ако има)
     *
     * @param array $headers
     * @return string
     */
    protected function extractHdrThreadKey($headers)
    {
    	$key = FALSE;
    	
    	if (!empty($headers['In-Reply-To'])) {
    		$key = $headers['In-Reply-To'];
    	}
    	
    	return $key;
    }
    
    
    /**
     * Извлича всички (кандидати за) ключове на тред от събджекта на писмо
     *
     * @param string $subject
     * @return array
     * 
     */
    static function extractSubjectThreadHnds($subject)
    {
    	$key = array();
    	
    	if (preg_match_all('/<([a-z\d]{4,})>/i', $subject, $matches)) {
    		$key = arr::make($matches[1], TRUE);
    	}
    	
    	return $key;
    }
    
    
    /**
     * Връща ключовете, използвани в правилата за рутиране
     *
     * @param int $id key(mvc=email_Messages)
     * @return array масив от обекти с индекс 'type' и членове 'key' и 'priority'
     */
    public function getRoutingKeys($id, $type = NULL)
    {
    	$rec = static::fetch($id);
    	
    	$priority = strtotime($rec->date);
    	
    	if (empty($type)) {
    		$type = 'fromTo, to, from, domain';
    	}
    	
    	$type = arr::make($type, TRUE);
    	
    	$keys = array();
    	
    	if ($type['fromTo']) {
    		$keys['fromTo'] = (object)array(
    			'key'      => md5($rec->fromEml . '|' . $rec->toEml),
    			'priority' => $priority
    		);
    	} 
    	if ($type['to']) {
    		$keys['to'] = (object)array(
    			'key'      => md5($rec->toEml),
    		    'priority' => $priority
    		);
    	} 
    	if ($type['from']) {
    		$keys['from'] = (object)array(
    			'key'      => md5($rec->fromEml),
    		    'priority' => $priority
    		);
    	} 
    	if ($type['domain']) {
	    	if (!static::isPublicDomain($domain = static::extractDomain($rec->fromEml))) {
	    		$keys['domain'] = (object)array(
	    			'key'      => md5($domain),
	    			'priority' => $priority
	    		);
	    	}
    	} 
    	
    	return $keys;
    }

    protected static function extractDomain($email)
    {
    	list(, $domain) = explode('@', $email, 2);
    	
    	$domain = empty($domain) ? FALSE : trim($domain); 

    	return $domain;
    }
    
    
    /**
     * Дали домейна е на публична е-поща (като abv.bg, mail.bg, yahoo.com, gmail.com)
     *
     * @param string $domain TLD
     * @return boolean
     */
    static function isPublicDomain($domain) {
    	/**
    	 * @TODO реализацията на този метод вероятно ще е много по-различна
    	 */
    	static $publicDomains = array(
    		'abv.bg', 'mail.bg', 'yahoo.com', 'gmail.com'
    	);
    	
    	return in_array($domain, $publicDomains);
    }
    
    
    /**
     * Преди вкарване на запис в модела
     */
    function on_BeforeSave($mvc, $id, &$rec) {
    	//При сваляне на мейла, състоянието е затворено
    	if (!$rec->id) {
    		$rec->state = 'closed';
    	}
    }
}
