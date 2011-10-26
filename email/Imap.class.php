<?php 

/**
 * Директорията, където ще се съхраняват временните файлове
 */
defIfNot('IMAP_TEMP_PATH', EF_TEMP_PATH . "/imap/");


/**
 * 
 * Апита за използване на IMAP
 *
 */
class email_Imap
{
    
	
	/**
	 * Свързва се към пощенската кутия
	 * 
	 * @param string $host    - Хоста, където се намира пощенската кутия
	 * @param number $port    - Порта през който ще се свързваме
	 * @param string $user    - Потребителското име /емйла/
	 * @param string $pass    - Паролата
	 * @param string $subHost - Допълнителната част в името на домейна след номера на порта
	 * @param string $folder  - Коя папка в пощенската кутия
	 * @param string $ssl     - SSL връзката, която се намира след subHost
	 * 
	 * @return resource
	 */
	function login($host, $port, $user, $pass, $subHost=FALSE, $folder="INBOX", $ssl=FALSE)
	{
		if ($ssl) {
			$ssl = '/' . $ssl;
		}
		
		if ($subHost) {
			$subHost = '/' . $subHost;
		}
		
		$imap = imap_open("{"."{$host}:{$port}{$subHost}{$ssl}"."}{$folder}", $user, $pass);

		if ( $imap === false ) {
	       exit ("Can't connect: " . imap_last_error() ."\n");
		}
		
    	return $imap;
	}
	
	
	/**
	 * Информация за съдържанието на пощенската кутия
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * 
	 * @return array
	 */
	function statistics($connection)        
	{ 
	    $check = imap_mailboxmsginfo($connection); 
	    return (array)$check; 
	} 
	
	
	/**
	 * Връща състоянието на писмата или посоченото писмо
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param number   $messageId  - Номера на съобщението, което да се покаже
	 * 
	 * @return array
	 */
	function lists($connection, $messageId=FALSE) 
	{ 
	    if ($messageId) { 
	        $range=$messageId; 
	    } else { 
	        $MC = imap_check($connection); 
	        $range = "1:".$MC->Nmsgs; 
	    } 
	    
	    $response = imap_fetch_overview($connection,$range); 
	    foreach ($response as $msg) {
	    	$result[$msg->msgno]=(array)$msg; 
	    }
	    
	    return $result; 
	} 
	
	
	/**
	 * Връща хедъра на избраното съобщение
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param number   $messageId  - Номера на съобщението, което да се покаже
	 * 
	 * @return string
	 */
	function header($connection,$messageId) 
	{ 
	    $header = imap_fetchheader($connection,$messageId,FT_PREFETCHTEXT);
		
	    return $header; 
	} 
	
	
	/**
	 * Подготвя посоченото съобщение за изтриване
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param number   $messageId  - Номера на съобщението, което да се покаже
	 * 
	 *  @return boolean
	 */
	function delete($connection,$messageId) 
	{ 
		$delete = imap_delete($connection,$messageId);
		
	    return $delete; 
	} 
	
	
	/**
	 * Преобразува подадения хедър в масив, за по лесен достъп
	 * 
	 * @param string $headers - Хедъра
	 * 
	 * @return array
	 */
	function mailParseHeaders($headers) 
	{ 
	    $headers=preg_replace('/\r\n\s+/m', '',$headers); 
	    preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers, $matches); 
	    
	    foreach ($matches[1] as $key =>$value) {
	    	$result[$value]=$matches[2][$key]; 
	    }
	    
	    return $result; 
	} 
	
	
	/**
	 * Връща цялата информация за посочения мейл
	 * 
	 * @param resource $connection   - Връзката към пощенската кутия
	 * @param number   $messageId    - Номера на съобщението, което да се покаже
	 * @param boolean  $parseHeaders - Оказва дали да се обработи хедъра
	 * 
	 * @return array
	 */
	function mailMimeToArray($connection, $messageId, $parseHeaders=FALSE) 
	{ 
	    $mail = imap_fetchstructure($connection,$messageId);
	    $mail = $this->mailGetParts($connection,$messageId,$mail,0); 
	    
	    if ($parseHeaders) {
	    	$mail[0]["parsed"] = $this->mailParseHeaders($mail[0]["data"]); 
	    }
	    
	    return $mail; 
	} 
	
	
	/**
	 * Връща цялата информация за посочения мейл
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param number   $messageId  - Номера на съобщението, което да се покаже
	 * @param object   $part       - Оказва дали има прикрепени файлове. Получава се от imap_fetchstructure
	 * @param number   $prefix     - Префикс
	 * 
	 * @return array
	 */
	function mailGetParts($connection, $messageId, $part, $prefix=0) 
	{    
	    $attachments=array(); 
	    $attachments[$prefix] = $this->mailDecodePart($connection,$messageId,$part,$prefix); 
	    if (isset($part->parts)) // multipart 
	    { 
	        if ($prefix == 0) {
	        	$prefix = '';
	        } else {
	        	$prefix = $prefix.'.';
	        }
	        
	        foreach ($part->parts as $number=>$subpart) {
	        	$attachments=array_merge($attachments, $this->mailGetParts($connection,$messageId,$subpart,$prefix.($number+1))); 
	        }
	            
	    } 
	    
	    return $attachments; 
	} 
	
	
	/**
	 * Декодира мейла
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param number   $messageId  - Номера на съобщението, което да се покаже
	 * @param object   $part       - Оказва дали има прикрепени файлове. Получава се от imap_fetchstructure
	 * @param number   $prefix     - Префикс
	 * 
	 * @return array
	 */
	function mailDecodePart($connection, $messageId, $part, $prefix=0) 
	{ 
	    $attachment = array(); 
	
	    if($part->ifdparameters) {
	        foreach($part->dparameters as $object) { 
	            $attachment[strtolower($object->attribute)]=$object->value; 
	            if(strtolower($object->attribute) == 'filename') {
	                $attachment['isAttachment'] = true; 
	                $attachment['filename'] = $object->value; 
	            } 
	        } 
	    } 
	
	    if($part->ifparameters) { 
	        foreach($part->parameters as $object) {
	            $attachment[strtolower($object->attribute)]=$object->value; 
	            if(strtolower($object->attribute) == 'name') { 
	                $attachment['isAttachment'] = true; 
	                $attachment['name'] = $object->value; 
	            } 
	        } 
	    } 
	
	    $attachment['data'] = imap_fetchbody($connection, $messageId, $prefix);
	    
	    if($part->encoding == 3) { // 3 = BASE64 
	        //$attachment['data'] = base64_decode($attachment['data']); 
	        $attachment['data'] = imap_base64($attachment['data']); 
	    } 
	    elseif($part->encoding == 4) { // 4 = QUOTED-PRINTABLE 
	        //$attachment['data'] = quoted_printable_decode($attachment['data']); 
	        $attachment['data'] = imap_qprint($attachment['data']); 
	    } 
	    
	    if ($attachment['isAttachment']) {	    	
	    	$attachment['fileHnd'] = $this->insertFilesToFileman($attachment);	    	
	    }
	    
	    return $attachment; 
	} 
	
	
	/**
	 * Изтрива e-мейлите, които са маркирани за изтриване
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * 
	 * @return boolean
	 */
	function expunge($connection) 
	{ 
		$expunge = imap_expunge($connection);
		
	    return $expunge; 
	} 
	
	
	/**
	 * Затваря връзката
	 * 
	 * @param resource $connection - Връзката към пощенската кутия
	 * @param const    $flag       - Ако е CL_EXPUNGE тогава преди затварянето на конекцията 
	 * се изтриват всички е-мейли, които са маркирани за изтриване
	 * 
	 *  @return boolean
	 */
	function close($connection, $flag=0) 
	{ 
		$close = imap_close($connection, $flag);
		
	    return $close; 
	}
	
	
	/**
	 * Вкарва прикрепените файлове във Fileman
	 * 
	 * @param array $attachment - Масив, който съдържа необходимите данни за създаването на файла
	 * 
	 * @return string - FileHandler' а на файла
	 */
	function insertFilesToFileman($attachment)
	{
		//Проверяваме за името на файла
		if (isset($attachment['filename'])) {
			$fileName = $attachment['filename'];
		} else {
			if (isset($attachment['filename'])) {
				$fileName = $attachment['name'];
			} else {
				$fileName = str::getUniqId();
			}
		}
		
		$filePath = IMAP_TEMP_PATH . $fileName;
		
		//Проверяваме дали съществува файл със същото име
		if (is_file($filePath)) {
			$filePath = $this->getUniqName($filePath);
		}
		
		//Записваме новия файла
		$fp = fopen($filePath, w);
		fputs($fp,$attachment['data']);
		fclose($fp);
		
		//Вкарваме файла във Fileman
		$Fileman = cls::get('fileman_Files');
		$fh = $Fileman->addNewFile($filePath, 'Email');
		
		//Изтриваме фременния файл
		@unlink($filePath);
		
		return $fh;
	}
	
	
	/**
	 * Проверява и генерира уникално име на файла
	 * 
	 * @param string $filePath - Пътя до файла
	 * 
	 * @return Новия път до файла
	 */
	function getUniqName($filePath)
	{
		$pathParts = pathinfo($filePath);
        $baseName = $pathParts['basename'];
        $directory = $pathParts['dirname'];
        
		$fn = $baseName;
        
        if (($dotPos = mb_strrpos($baseName, '.')) !== FALSE ) {
	    	$firstName = mb_substr($baseName, 0, $dotPos);
	        $ext = mb_substr($baseName, $dotPos);
	    } else {
            $firstName = $baseName;
            $ext = '';
        }
        
        $i = 0;
        $files = scandir($directory);
	    
        // Циклим докато генерирме име, което не се среща до сега
        while (in_array($fn, $files)) {
            $fn = $firstName . '_' . (++$i) . $ext;
        }
        
        return $directory . '/' . $fn;	
        
	}
	
	
	/**
	 * Взема записите от пощенската кутия и ги вкарва в модела
	 */
	function getMailInfo()
	{
		
		$accaunt = email_Accounts::fetch("#id=1");
		//TODO while fetch all
		
		$host = $accaunt->server;
		$port = $accaunt->port;
		//$user = $accaunt->eMail;
		$user = 'testbgerp@gmail.com';
		$pass = $accaunt->password;
		$subHost = $accaunt->subHost;
		$ssl = $accaunt->ssl;
		$mailId = $accaunt->id;
		
		$imap = $this->login($host, $port, $user, $pass, $subHost,$folder="INBOX", $ssl);
		
		$statistics = $this->statistics($imap);
		
		$numMsg = $statistics['Nmsgs'];
		$i = 1; //$messageId - Номера на съобщението
		while ($i <= $numMsg) {
			$rec = new stdClass();
			
			$lists = $this->lists($imap, $i);
    		
			$header = $this->header($imap, $i);
			
			$mailMimeToArray = $this->mailMimeToArray($imap, $i);
			
			unset($mailMimeToArray[0]);
			
			$textKey = '1';
			$htmlKey = '2';
			
			if (isset($mailMimeToArray['1.1'])) {
				$textKey = '1.1';
				$htmlKey = '1.2';
				unset($mailMimeToArray[1]);
			}
			
			$rec->textPart = $mailMimeToArray[$textKey]['data'];
			$rec->htmlPart = $mailMimeToArray[$htmlKey]['data'];
			
			if (!(strlen($rec->textPart))) {
				$html2Text = cls::get('html2text_Html2Text');
				$txt = $html2Text->convert2text($rec->htmlPart);
				$rec->textPart = $txt;
			}
			
			unset($mailMimeToArray[$textKey]);
			unset($mailMimeToArray[$htmlKey]);
			
			if (count($mailMimeToArray)) {
				foreach ($mailMimeToArray as $key => $value) {
					
					//TODO Вмъкването на id' тата на файловете в модела (в keylist поле)
					
					//$rec->files = $value['fileHnd'];
				}
			}
			
			
			//bp($header, $mailMimeToArray);
						
			$rec->emailId = $mailId;
			$rec->messageId = $lists[$i]['message_id'];
			$rec->from = $lists[$i]['from'];
			//$rec->fromName = $lists[$i]['from'];
			$rec->to = $lists[$i]['to'];
			//$rec->to = $lists[$i]['to'];
			//$rec->date = $lists[$i]['date'];
			$rec->headers = $header;
			
			
			
			
			//$rec->subject = $lists[$i]['subject'];
			
			email_Messages::save($rec);
			
			
			
    		$i++;
		}
		bp($rec);
	}
	
	
	
	
	
	
	
	
	
	
}

?>