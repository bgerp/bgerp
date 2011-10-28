<?php 


/**
 * Директорията, където ще се съхраняват временните файлове
 */
defIfNot('IMAP_TEMP_PATH', EF_TEMP_PATH . "/imap/");


/**
 * Директорията, където ще се съхраняват eml файловете
 */
defIfNot('IMAP_EML_PATH', EF_TEMP_PATH . "/imapeml/");


/**
 * 
 * Емейли
 *
 */
class email_Messages extends core_Manager
{
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Емейли";
    
    
    /**
     * Права
     */
    var $canRead = 'admin, email';
    
    
    /**
     *  
     */
    var $canEdit = 'admin, email';
    
    
    /**
     *  
     */
    var $canAdd = 'admin, email';
    
    
    /**
     *  
     */
    var $canView = 'admin, rip';
    
    
    /**
     *  
     */
    var $canList = 'admin, email';
    
    /**
     *  
     */
    var $canDelete = 'admin, email';
    
	
	/**
	 * 
	 */
	var $canRip = 'admin, email';
	
    
    /**
     * 
     */
	var $loadList = 'email_Wrapper, plg_Created';
    //var $loadList = 'plg_RowTools, plg_Created, plg_Selected, rip_Wrapper, plg_State';
    
    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('accId', 'key(mvc=email_Accounts,select=eMail)', 'caption=Е-мейл');
		$this->FLD("messageId", "varchar", "caption=Съобщение ID");
		$this->FLD("subject", "varchar", "caption=Тема");
		$this->FLD("from", "varchar", 'caption=От');
		$this->FLD("fromName", "varchar", 'caption=От Име');
		$this->FLD("to", "varchar", 'caption=До');
		$this->FLD("toName", "varchar", 'caption=До Име');
		$this->FLD("headers", "blob(70000)", 'caption=Хедъри');
		$this->FLD("textPart", "blob(70000)", 'caption=Текстова част');
		$this->FLD("htmlPart", "blob(70000)", 'caption=HTML част');
		$this->FLD("spam", "int", 'caption=Спам');
		$this->FLD("lg", "varchar", 'caption=Език');
		$this->FLD('hash', 'varchar(32)', 'caption=Keш');
		$this->FLD('country', 'key(mvc=drdata_countries,select=commonName)', 'caption=Държава');
		$this->FLD('fromIp', 'ip', 'caption=IP');
		
		$this->FLD('files', 'keylist(mvc=fileman_Files,select=name,maxColumns=1)', 'caption=Файлове');
		//$this->FLD('date', 'datetime', 'Caption=Дата');
		
		
		$this->setDbUnique('hash');
		
	}
	
		
	/**
	 * Взема записите от пощенската кутия и ги вкарва в модела
	 *
	 * @param number $oneMailId - Потребителя, за когото ще се проверяват записите.
	 * Ако е празен, тогава ще се проверяват за всички.
	 * 
	 * @return boolean TRUE
	 */
	function getMailInfo($oneMailId=FALSE)
	{
		$query = email_Accounts::getQuery();
		if (!$oneMailId) {
			
			//$query->where("#period = ''");
						
		} else {
			$oneMailId = intval($oneMailId);
			$query->where("#id = '$oneMailId'");
		}
		
		$imapCls = cls::get('email_Imap');
		$imapParse = cls::get('email_Parser');
		
		while ($accaunt = $query->fetch()) {
			$host = $accaunt->server;
			$port = $accaunt->port;
			$user = $accaunt->eMail;
			//$user = $accaunt->user;
			$pass = $accaunt->password;
			$subHost = $accaunt->subHost;
			$ssl = $accaunt->ssl;
			$accId = $accaunt->id;
			
			$imap = $imapCls->login($host, $port, $user, $pass, $subHost, $folder="INBOX", $ssl);
			
			if (!$imap) {
				continue;
			}
			
			$statistics = $imapCls->statistics($imap);
			
			$numMsg = $statistics['Nmsgs'];
			$i = 1; //messageId - Номера на съобщението
			while ($i <= $numMsg) {
				$rec = new stdClass();
				
				//$lists = $imapCls->lists($imap, $i);
	    		
				$header = $imapCls->header($imap, $i);
				
				$body = $imapCls->body($imap, $i);
				
				$mailMimeToArray = $imapParse->mailMimeToArray($imap, $i);
				
				unset($mailMimeToArray[0]);
				
				$textKey = '1';
				$htmlKey = '2';
				if (isset($mailMimeToArray['1.1'])) {
					$textKey = '1.1';
					$htmlKey = '1.2';
					unset($mailMimeToArray[1]);
				}
				$text = $mailMimeToArray[$textKey]['data']; 
				$html = $mailMimeToArray[$htmlKey]['data'];
				$textCharset = $mailMimeToArray[$textKey]['charset'];
				$htmlCharset = $mailMimeToArray[$htmlKey]['charset'];
				unset($mailMimeToArray[$textKey]);
				unset($mailMimeToArray[$htmlKey]);
				
				$imapParse->setHeaders($header);
				
				$imapParse->setText($text);
				$imapParse->setTextCharset($textCharset);
				
				$imapParse->setHtml($html);
				$imapParse->setHtmlCharset($htmlCharset);
				
				$rec->textPart = $imapParse->getText();
				$rec->htmlPart = $imapParse->getHtml();	
				$rec->subject = $imapParse->getHeader('subject');
				$rec->messageId = $imapParse->getHeader('message-id');
				$rec->accId = $accId;
				$rec->headers = $header;
				$rec->hash = md5($header);
				$rec->fromIp = $imapParse->getSenderIp();
				
				$mailFrom = $imapParse->getFrom();		
				$rec->from = $mailFrom['mail'];
				$rec->fromName = $mailFrom['name'];
				
				$mailTo = $imapParse->getTo();
				$rec->to = $mailTo['mail'];
				$rec->toName = $mailTo['name'];
				
				//TODO getCodeFromTld, getCodeFromIp - calcCountry
				//$rec->from = $imapParse->getHeader('from');
				//$rec->fromName = $this->getEmailName($rec->from);
								
				//$rec->to = $mailTo;
				//$rec->toName = $this->getEmailName($rec->to);
								
				if (count($mailMimeToArray)) {
					$Fileman = cls::get('Fileman_files');
					foreach ($mailMimeToArray as $key => $value) {
						$fh = $value['fileHnd'];
						$id = $Fileman->fetchByFh($fh); 
						$fhId[$id->id] = $fh;
					}
					
					$rec->files = type_Keylist::fromArray($fhId);
				}
				bp($rec);
				email_Messages::save($rec, NULL, 'IGNORE');
				
				$eml = $header . "\n\n" . $body;
				$emlPath = IMAP_EML_PATH . $rec->hash . '.eml';
				$emlPath = $imapParse->getUniqName($emlPath);
				
				//Записваме новия файла
				$fp = fopen($emlPath, w);
				fputs($fp, $eml);
				fclose($fp);
				
				//TODO Да се премахне коментара
				//$imapCls->delete($imap, $i);
				//bp($rec);
	    		$i++;
	    		
			}
			//TODO Да се премахне коментара
			//$imapCls->expunge($imap);
		
			$imapCls->close($imap);
		}
		
		return TRUE;
	}
	
	
	
		
	
	
	
	
	
	
	
	
	
	
	
	/**
     * Да сваля имейлите
     */
    function cron_DownloadEmails()
    {		
		$mailInfo = $this->getMailInfo();
		
		return 'Свалянето приключи.';
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
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 0;
     // $rec->timeLimit = 200;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да сваля имейлите в модела.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да сваля имейлите.</li>";
        }
    	
        if(!is_dir(IMAP_TEMP_PATH)) {
            if( !mkdir(IMAP_TEMP_PATH, 0777, TRUE) ) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') . ' "' . IMAP_TEMP_PATH . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' . IMAP_TEMP_PATH . '"</font>';
            }
        } else {
        	$res .= '<li>' . tr('Директорията съществува: ') . ' <font color=black>"' . IMAP_TEMP_PATH . '"</font>';
        }
        
    	if(!is_dir(IMAP_EML_PATH)) {
            if( !mkdir(IMAP_EML_PATH, 0777, TRUE) ) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') . ' "' . IMAP_EML_PATH . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' . IMAP_EML_PATH . '"</font>';
            }
        } else {
        	$res .= '<li>' . tr('Директорията съществува: ') . ' <font color=black>"' . IMAP_EML_PATH . '"</font>';
        }
        
        return $res;
    }

}

?>