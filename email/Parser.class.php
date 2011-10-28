<?php 

 
/**
 * 
 * Парсиране на емейл съобщение
 *
 */
class email_Parser
{

    /**
     * Текста на хедърите
     */
    var $header; 
    

    /**
     * Текстовата част на писмото
     */
    var $text; 
 
    
    /**
     * HTML част на писмото
     */
    var $html; 


    /**
     * Масив за хедърите
     */
    var $headersArr = array();
	
    
    /**
     * Charset' а на хедъра
     */
    var $headerCharset;
    
    
    /**
     * Charset' а на текстовата част
     */
    var $textCharset;
    
    
    /**
     * Charset' а на HTML частта
     */
    var $htmlCharset;
    
    
    /**
     * 
     * @param string $header
     */
    function setHeaders($header)
    {
        $this->headers = $header;
        $this->parseHeaders();
    }
    

	/**
	 * 
	 * @param string $text
	 */
    function setText($text)
    {	
        $this->text = $text; 
        $this->htmlToText();
    	$this->decodeEntity();
    	$this->text = $this->makeDecodingBody($this->text, TRUE);
    	
    }
	
   	
    /**
     * 
     */
    function getText()
    {
    	
    	return $this->text;
    }
    
    
	/**
	 * 
	 * @param string $text
	 */
    function setTextCharset($text)
    {
        $this->textCharset = $text;
    }
    
    
	/**
	 * 
	 * @param string $text
	 */
    function setHtmlCharset($text)
    {
        $this->htmlCharset = $text;
    }
    
    	
    /**
     * 
     * @param string $html
     */
    function setHtml($html)
    { 
        $this->html = $html;
        $this->html = $this->makeDecodingBody($this->html, FALSE); 
        
    }
	
    
	/**
     * 
     */
    function getHtml()
    {
    	
    	return $this->html;
    }
    
    
    /**
     * 
     */
    function getTo()
    {
    	$to = $this->getHeader('to');
    	$cc = $this->getHeader('cc');
    	$bcc = $this->getHeader('bcc');
    	//$to = NULL;
    	//$bcc = NULL;
		$all = '';
		if (isset($to)) {
			$all .= $to . ', ';
		}
    	if (isset($cc)) {
			$all .= $cc . ', ';
		}
   		if (isset($bcc)) {
			$all .= $bcc . ', ';
		}
		
		$all = substr($all,0,-2); //премахва последните 2 символа
		
		$mails = $this->parseAddrList($all);
		
		foreach ($mails as $value) {
			
			$mailArr['mail'] .= $value->mailbox . '@' . $value->host . ', ';
			$mailArr['name'] .= $value->personal . ', ';
		}
		
		//$mailArr['mail'] = substr($mailArr['mail'],0,-2);
		//$mailArr['name'] = substr($mailArr['name'],0,-2);
		
		return $mailArr;
    }
    
    
    /**
     * 
     */
    function getFrom()
    {
    	$from = $this->getHeader('from');
    	$parseFrom = $this->parseAddrList($from);		
		$mailArr['mail'] = $parseFrom[0]->mailbox . '@' . $parseFrom[0]->host;
		$mailArr['name'] = $parseFrom[0]->personal;
    	
    	return $mailArr;
    }
    
    
    /**
     * Парсира хедърите в масив
     */
    function parseHeaders()
    {
        // Очакваме хедърите да са сетнати
        expect(isset($this->headers));

        $headers = str_replace("\n\r", "\n", $this->headers);
        $headers = str_replace("\r\n", "\n", $headers);
        $headers = str_replace("\r", "\n", $headers);
       	$headers = trim($headers); //
        $headers = explode("\n", $headers);
		
        // парсира масив с хедъри на е-маил
        foreach($headers as $h) {
            if( substr($h, 0, 1) != "\t" && substr($h, 0, 1) != " ") {
                $pos = strpos($h, ":");
                $index = strtolower(substr($h, 0, $pos));
				
                $this->headersArr[$index][] = trim(substr($h, $pos - strlen($h) + 1));
          
            } else {
                $current = count($this->headersArr[$index]) - 1;
                $this->headersArr[$index][$current] .= "\n" . $h;  
            }
        }
        
        $this->headerCharset = $this->contentTypeParser();
        
        foreach ($this->headersArr as $key => $value) {
        	foreach ($value as $i => $val) {
        		$v[$key][$i] = $this->makeDecodingHeader($val);
        	}
        }
        
        $this->headersArr = $v;
	
    }


    /**
     * Връща указания хедър. Ако се очаква повече от един хедър с това име, може да се вземе
     * точно посочен номер. Ако номера е отрицателен, броенето започва от зад на пред.
     * Хедър с номер 0 е първия срещнат с това име, а хедър с номер -1 е последния срещнат
     */
    function getHeader($name, $id = 0)
    {
        $name = strtolower($name);
        if($id < 0) {
            $id = count($this->headersArr[$name]) + $id;
        }
		
        //$res = $this->decodeHeaderMime($this->headersArr[$name][$id]);
        
        $res = $this->headersArr[$name][$id];
        
        return $res;
    }
	
    
    /**
     * Проверява дали въведената стойност е 7 битова
     * 
     * @param string $str - Стринга, който ще се проверява
     * 
     * @return boolean
     * 
     */
    function is7Bit($str)
    {
		$arr = str_split($str);
		foreach ($arr as $value) {
			if (ord($value) > 127) {
				
				return FALSE;
			}
		}
		
		return TRUE;
    }
    
    
    /**
     * Взема charset' а от хедъра
     * 
     * @return string 
     */
    function contentTypeParser()
    {	
    	// Очакваме хедърите да са сетнати
    	expect(isset($this->headersArr)); 
    	
    	$headers = $this->headersArr['content-type'];
    	
    	if (!isset($headers)) {
    		
    		return FALSE;
    	}
    	
    	foreach ($headers as $value) {
    		$header .= $value . '; ';
    	}
    	
    	$arr = explode(';', $header);
    	
    	foreach ($arr as $value) {
    		if (strpos($value, 'charset') !== FALSE) {
    			$charsetArr = explode('=', $value);
    			$charset = trim($charsetArr[1]);
    			
    			return $charset;
    		}
    	}
    	
        return FALSE;
    }
    
        
    /**
     * Стартира декодирането
     */
    function makeDecodingHeader($header)
    {
    	if ($this->is7Bit($header)) {
    		$res = $this->decodeHeaderMime($header);
    	} else {
    		$res = $this->decodeHeader($header);
    	}
    	
    	return $res;
    }
    
    
	/**
     * Стартира декодирането
     */
    function makeDecodingBody($body, $text)
    {
    	if ($this->is7Bit($body)) {
    		$res = $this->decodeBodyMime($body, $text);
    	} else {
    		$res = $this->decodeBody($body, $text);
    	}
    	
    	return $res;
    }
 	
 	
 	/**
     * Декодира MIME стринга в UTF-8
     * 
     * @param string $header - Стринг, който ще се декодира
     */
    function decodeHeaderMime($header)
    {
    	//$charset = $this->findHarsetHeader($string);
    	$imapDecode = imap_mime_header_decode($header);
    	$res = '';
    	if (count($imapDecode) > 0) {
    		foreach ($imapDecode as $value) { 
    			$charset = ($value->charset == 'default') ? 'ASCII' : $value->charset;
    			
    			$charset = strtoupper($charset);
    			
    			$text = $value->text;
    			$res .= iconv("{$charset}", "UTF-8", $text);
    			
    		}
	    }
	    
	    return $res;
    }
    
    
    /**
     * Декодира стринга в UTF-8
     * 
     * @param string $header - Стринг, който ще се декодира
     */
    function decodeHeader($string)
    {
    	$charset = $this->findHarsetHeader($string);
    	    		
		$charset = strtoupper($charset);
    			    	
    	$res = iconv("{$charset}", "UTF-8", $string);
    	
	    return $res;
    }
    
    
	/**
     * Декодира стринга в UTF-8
     * 
     * @param string $header - Стринг, който ще се декодира
     */
    function decodeBodys($string, $text=TRUE)
    {
    	$charset = $this->findHarsetText($string, $text);
    	    		
		$charset = strtoupper($charset);
    			    	
    	$res = iconv("{$charset}", "UTF-8", $string);
    	
	    return $res;
    }
    
    
	/**
     * Декодира MIME стринга в UTF-8
     * 
     * @param string $header - Стринг, който ще се декодира
     */
    function decodeBody($string, $text=TRUE)
    {
    	//$charset = $this->findHarsetText($string, $text);
    	$imapDecode = imap_mime_header_decode($string);
    	$res = '';
    	
    	if (count($imapDecode) > 0) {
    		foreach ($imapDecode as $value) { 
    			$charset = ($value->charset == 'default') ? 'UTF-8' : $value->charset;
    			
    			$charset = strtoupper($charset);
    			
    			$text = $value->text;
    			$res .= iconv("{$charset}", "UTF-8", $text);
    			
    		}
	    }
	    
	    return $res;
    }
    
    
    /**
     * Намира charset' а на текущия хедър
     */
    function findHarsetHeader($value)
    {
   	 	$expCharset = $this->getExpCharset($value);
    	if ($expCharset) {
    		$charset = $expCharset;
    	} else {
    		$textCharset = $this->getTextCharset($value);
    		if ($textCharset) {
    			$charset = $textCharset;
    		} else {
    			$charset = $this->headerCharset;
    		}
    	}
    	
    	if (($charset == 'default') || (!$charset)) {
    		$charset = 'ASCII';
    	}
    	
    	return $charset;
    }
    
    
	/**
     * Намира charset' а на текущия текст
     */
    function findHarsetText($str, $text=TRUE)
    {
   	 	$expCharset = $this->getExpCharset($str);
    	if ($expCharset) {
    		$charset = $expCharset;
    	} else {
    		if ($text) {
    			$textCharset = $this->textCharset;
    		} else {
    			$textCharset = $this->htmlCharset;
    		}
    		
    		if ($textCharset) {
    			$charset = $textCharset;
    		} else {
    			$charset = $this->headerCharset;
    		}
    	}
    	
    	if (($charset == 'default') || (!$charset)) {
    		$charset = 'ASCII';
    	}
    	
    	return $charset;
    }
    
    
	/**
     * Прави опит да познае какъв е charset' а от хедърите на текста
     */
	function getTextCharset($string)
	{
		
		return FALSE;
	}
    
    
    /**
     * Прави опит да познае какъв е charset' а от текста
     */
	function getExpCharset($string)
	{
		
		return FALSE;
	}
	
    
    /**
     * Прави опит да намери IP адреса на изпращача
     */
    function getSenderIp()
    {
        $ip = trim($this->getHeader('X-Originating-IP'), '[]');
       
        if(empty($ip) || (!type_Ip::isPublic($ip))) {
            
            $ip = trim($this->getHeader('X-Sender-IP'), '[]');

        }

        if(empty($ip) || (!type_Ip::isPublic($ip))) { 
            $regExp = '/Received:.*\[((?:\d+\.){3}\d+)]/';
            preg_match_all($regExp, $this->headers, $matches);  
            if($ipCnt = count($matches[1])) {							
                 for($i = $ipCnt - 1; $i>=0; $i--) {
                     if(type_Ip::isPublic($matches[1][$i])) {
                         $ip = $matches[1][$i];
                         break;
                     }
                 }
            }
        }
        
        if(empty($ip) || (!type_Ip::isPublic($ip))) { 
            $regExp = '/Received:.*?((?:\d+\.){3}\d+)/';
            preg_match_all($regExp, $this->headers, $matches);   
            if($ipCnt = count($matches[1])) {							
                 for($i = $ipCnt - 1; $i>=0; $i--) {
                     if(type_Ip::isPublic($matches[1][$i])) {
                         $ip = $matches[1][$i]; 
                         break;
                     }
                 }
            }
        }
          
        return $ip;
    }
        
    
    /**
     * Ако няма текст в текстовата част, тогава вземе изчистения вариант от html частта
     */
    function htmlToText()
    {
    	if (!(strlen($this->text))) {
    		$html2Text = cls::get('html2text_Html2Text');
			$this->text = $html2Text->convert2text($this->html);
		}
    }
    
    
	/**
     * Конвертира нечетимия стринг към четим
     */
    function decodeEntity()
    {
    	if (!($this->chekIsEntity())) {
    		$this->text = html_entity_decode($this->text);
    	}
    	
    }
    
    
    /**
     * Проверява дали стинга е четим
     */
    function chekIsEntity()
    {
    	$str = $this->text;
    	$len = mb_strlen($str);
    	if ($len > 4) {
    		$question = mb_substr_count($str, '?');
    		
    		$percentQ = $question / $len;
    		
    		if ($percentQ > 0.5) {

    			return FALSE;
    		}
    	}
    	
    	return TRUE;
    }    
    
	
    /**
	 * Преобразува подадения стринг от мейл адреси в масив
	 * 
	 * @param string $addrStr - Масив от мейли
	 * 
	 * @param string $defHost - Хоста по подразбиране. Ако не се намери хоста в мейла, тогава се използва.
	 * 
	 * @return array
	 * 	mailbox - пощенска кутия
	 * 	host - хост
	 * 	personal - име
	 */
    function parseAddrList($addrStr, $defHost='')
    {
    	$arr = imap_rfc822_parse_adrlist($addrStr, $defHost);
    	
    	return $arr;
    }
    
    
    /**
	 * Преобразува хедъра в обект
	 * 
	 * @param string $header - Хедъра, който ще се преобразува
	 * 
	 * @return obj
	 */
    function rfcParseHeaders($header)
    {
    	$obj = imap_rfc822_parse_headers($header);
    	
    	return $obj;
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
	    //$headers=preg_replace('/\r\n\s+/m', '',$headers); 
	    
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
			if (isset($attachment['name'])) {
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
	 * 
	 * 
	 * 
	 */
	function getCodeFromTld($mail)
	{
		$dotPos = mb_strrpos($mail, '.');
		$tld = mb_substr($mail, $dotPos);
		
		$code2 = drdata_countries::fetchField("#domain='{$tld}'", 'letterCode2');
		
		return $code2;
		
	}
	
	
	/**
	 * 
	 */
	function getCodeFromIp($ip)
	{
		$code2 = drdata_ipToCountry::get($ip);
		
		return $code2;
	}
	
	
	/**
	 * 
	 * Enter description here ...
	 */
	function calcCountry()
	{
		
		return $code2;
	}
    
    
    
    
    
    

}