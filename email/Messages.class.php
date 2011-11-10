<?php 


/**
 * Директорията, където ще се съхраняват временните файлове
 */
defIfNot('IMAP_TEMP_PATH', EF_TEMP_PATH . "/imap/");


/**
 * Входящи писма
 */
class email_Messages extends core_Master
{
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Входящи писма";
    
    
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
	var $loadList = 'email_Wrapper, plg_Created, doc_DocumentPlg, plg_KeyToLink';
    
	
	/**
	 * Нов темплейт за показване
	 */
	var $singleLayoutFile = 'email/tpl/SingleLayoutMessages.html';
	
	
	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	var $textHtmlKey;
    	
	
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
		$this->FLD("headers", "text", 'caption=Хедъри');
		$this->FLD("textPart", "richtext", 'caption=Текстова част');
		$this->FLD("htmlPart", "text", 'caption=HTML част');
		$this->FLD("spam", "int", 'caption=Спам');
		$this->FLD("lg", "varchar", 'caption=Език');
		$this->FLD('hash', 'varchar(32)', 'caption=Keш');
		$this->FLD('country', 'key(mvc=drdata_countries,select=commonName)', 'caption=Държава');
		$this->FLD('fromIp', 'ip', 'caption=IP');
		
		$this->FLD('files', 'keylist(mvc=fileman_Files,select=name,maxColumns=1)', 'caption=Файлове, hyperlink');		
		$this->FLD('emlFile', 'key(mvc=fileman_Files,select=name)', 'caption=eml файл, hyperlink');
		$this->FLD('htmlFile', 'key(mvc=fileman_Files,select=name)', 'caption=html, hyperlink');
		
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
		
		
		
		while ($accaunt = $query->fetch()) {
			$host = $accaunt->server;
			$port = $accaunt->port;
			$user = $accaunt->eMail;
			//$user = $accaunt->user;
			$pass = $accaunt->password;
			$subHost = $accaunt->subHost;
			$ssl = $accaunt->ssl;
			$accId = $accaunt->id;
			
			$imapCls = cls::get('email_Imap');
			$imap = $imapCls->login($host, $port, $user, $pass, $subHost, $folder="INBOX", $ssl);
			
			if (!$imap) {
				continue;
			}
			
			$statistics = $imapCls->statistics($imap);
			
			$numMsg = $statistics['Nmsgs'];
			$i = 1; //messageId - Номера на съобщението
			while ($i <= $numMsg) {
				$rec = new stdClass();
				$imapParse = cls::get('email_Parser');
				
				//$lists = $imapCls->lists($imap, $i);
	    		
				$header = $imapCls->header($imap, $i);
				
				$body = $imapCls->body($imap, $i);
				$imapParse->body = $body;
				
				$mailMimeToArray = $imapParse->mailMimeToArray($imap, $i);
				
				unset($mailMimeToArray[0]);
				
				$mailMimeToArray = $this->getTextHtmlKey($mailMimeToArray, 1);
				
				
				$textKey = $this->textHtmlKey['text'];
				$htmlKey = $this->textHtmlKey['html'];
				
				$text = $mailMimeToArray[$textKey]['data']; 
				$html = $mailMimeToArray[$htmlKey]['data'];
				$textCharset = $mailMimeToArray[$textKey]['charset'];
				$htmlCharset = $mailMimeToArray[$htmlKey]['charset'];
				
				unset($mailMimeToArray[$textKey]);
				unset($mailMimeToArray[$htmlKey]);
				
				$imapParse->setHeaders($header);
				
				$imapParse->setHtmlCharset($htmlCharset);
				$imapParse->setHtml($html);
				
				$imapParse->setTextCharset($textCharset);
				$imapParse->setText($text);
				
				$rec->textPart = $imapParse->getText();
				$rec->htmlPart = $imapParse->getHtml();	
				$rec->subject = $imapParse->getSubject();
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
				
				$rec->country = $imapParse->calcCountry($rec->from, $rec->fromIp, $rec->lg);
				
				//bp($imapParse->getCodeFromCountry($rec->country), $imapParse->getCodeFromIp($rec->fromIp));
				//TODO getCodeFromCountry, getCodeFromIp - calcCountry
				//$rec->from = $imapParse->getHeader('from');
				//$rec->fromName = $this->getEmailName($rec->from);
								
				//$rec->to = $mailTo;
				//$rec->toName = $this->getEmailName($rec->to);
				$htmlFile = $rec->htmlPart;	
				$Fileman = cls::get('fileman_Files');
				
				unset($fhId);
					
				if (count($mailMimeToArray)) {
					$pattern = '/src\s*=\s*\"*\'*cid:\s*\S*/';
					preg_match_all($pattern, $htmlFile, $match);
					
					if (count($match[0])) {
						foreach ($match[0] as $oneMatch) {
							$pattern = '/:[\w\W]+@/';
							preg_match($pattern, $oneMatch, $matchName);
							
							if (count($matchName)) {
								$matchName = trim($matchName[0]);
								$matchName = substr($matchName, 0, -1);
								$matchName = substr($matchName, 1);
								$cidName[] = $matchName;
								$cidSrc[] = $oneMatch;
								
							}
							
						}
					}		
											
					foreach ($mailMimeToArray as $key => $value) {
						if ($value['fileHnd']) {
							$Download = cls::get('fileman_Download');
							$fh = $value['fileHnd'];
							$id = $Fileman->fetchByFh($fh); 
							$fhId[$id->id] = $fh;
							$keyCid = array_search($value['filename'], $cidName);
							if ($keyCid !== FALSE) {
								//TODO Да времето в което е активен линка (10000*3600 секунди) ?
								$filePath = 'src="' . $Download->getDownloadUrl($fh, 10000) . '"';
								$htmlFile = str_replace($cidSrc[$keyCid], $filePath, $htmlFile);
							}
							
						}
						
					}
					
					$rec->files = type_Keylist::fromArray($fhId);
				}
				$htmlFilePath = IMAP_TEMP_PATH . $rec->hash . '.html';
				$htmlFilePath = $imapParse->getUniqName($htmlFilePath);
				$htmlFh= $this->insertToFile($htmlFilePath, $htmlFile);
				$htmlCls = $Fileman->fetchByFh($htmlFh); 
				$rec->htmlFile = $htmlCls->id;
				
				$eml = $header . "\n\n" . $body;
				$emlPath = IMAP_TEMP_PATH . $rec->hash . '.eml';
				$emlPath = $imapParse->getUniqName($emlPath);
				$emlFh= $this->insertToFile($emlPath, $eml);
				$emlCls = $Fileman->fetchByFh($emlFh); 
				$rec->emlFile = $emlCls->id;
				
				email_Messages::save($rec, NULL, 'IGNORE');
				
				//TODO Да се премахне коментара
				//$imapCls->delete($imap, $i);
	    		$i++;
	    		
			}
			//TODO Да се премахне коментара
			//$imapCls->expunge($imap);
		
			$imapCls->close($imap);
		}
		
		return TRUE;
	}
	
	
	/**
	 * Връща ключа на текстовата и html частта
	 */
	function getTextHtmlKey($mail, $key)
	{
		$newKey = $key . '.1';
		if (isset($mail[$newKey])) {
						
			unset($mail[$key]);
			$this->getTextHtmlKey($mail, $newKey);
		} else {
			
			$arr['text'] = $key;
			$htmlText = substr($arr['text'], 0, -1).'2';
			
			$arr['html'] = $htmlText;
			
			$this->textHtmlKey = $arr;
			
		}
		
		return $mail;
	}
	
	
	/**
	 * Връща аватара на Автора
	 */
	function getAuthorAvatar($id)
	{
		
		return NULL;
	}
	
	
	/**
	 * Връща името и мейла
	 */
	function getAuthorName($id)
	{
		$query = email_Messages::getQuery();
		$query->where("#id = '$id'");
		$query->show('from, fromName');
		$rec = $query->fetch();
		$from = trim($rec->from);
		$fromName = trim($rec->fromName);

		$name = $from;
		
		if ($fromName) {
			$name = $fromName . "<br />" . $name;
		}
		
		$len = mb_strlen($fromName) + mb_strlen($from);
		if ($len > 32) {
			$name = mb_substr($name, 0, 32);
            $name .= "...";
		}
				
		return $name;
	}
	
	
	
	/**
	 * Връща датана на създаване на мейла
	 */
	function getDate($id)
	{
		$query = email_Messages::getQuery();
		$query->where("#id = '$id'");
		$query->show('createdOn');
		$rec = $query->fetch();
		
		$date = $rec->createdOn;
		
		return $date;
	}
	
	
	/**
	 * TODO ?
	 * Преобразува threadDocumentId в машинен вид
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		$row->threadDocumentId = $rec->threadDocumentId;
		
		//TODO team@ep-bags.com да се сложи в конфигурационния файл
		if (trim(strtolower($rec->to)) == 'team@ep-bags.com') {
			$row->to = NULL;
		}
		
		$pattern = '/\s*[0-9a-f_A-F]+.eml\s*/';
		$row->emlFile = preg_replace($pattern, 'EMAIL.eml', $row->emlFile);
		
		$pattern = '/\s*[0-9a-f_A-F]+.html\s*/';
		$row->htmlFile = preg_replace($pattern, 'EMAIL.html', $row->htmlFile);
		
		$row->files .= $row->emlFile . $row->htmlFile;
		
		
	}
	
	
	/**
	 * Връща заглавието на писмото за записване в нишките
	 */
	function getThreadTitle($mvc)
	{
		return $mvc->subject;
	}
	
	
	/**
	 * Вкарваме файла във fileman
	 */
	function insertToFileman($path)
	{
		$Fileman = cls::get('fileman_Files');
		$fh = $Fileman->addNewFile($path, 'Email');
		
		@unlink($path);
		
		return $fh;
	}
	
	
	/**
	 * Записва файловете
	 */
	function insertToFile($path, $file)
	{
		$fp = fopen($path, w);
		fputs($fp, $file);
		fclose($fp);
		
		$fh = $this->insertToFileman($path);
		
		return $fh;		
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
        
        return $res;
    }

}

?>