<?php 

 
/**
 * 
 * Парсиране на емейл съобщение
 *
 */
class email_Parser
{
	
	
    /**
     * Парсира хедърите в масив
     */
    static function parseHeaders($headersStr)
    {
        $headers = str_replace("\n\r", "\n", $headersStr);
        $headers = str_replace("\r\n", "\n", $headers);
        $headers = str_replace("\r", "\n", $headers);
       	$headers = trim($headers); //
        $headers = explode("\n", $headers);
		
        // парсира масив с хедъри на е-маил
        foreach($headers as $h) {
            if( substr($h, 0, 1) != "\t" && substr($h, 0, 1) != " ") {
                $pos = strpos($h, ":");
                $index = strtolower(substr($h, 0, $pos));
				
                $headersArr[$index][] = trim(substr($h, $pos - strlen($h) + 1));
          
            } else {
                $current = count($headersArr[$index]) - 1;
                $headersArr[$index][$current] .= "\n" . $h;  
            }
        }
        
        return $headersArr;
        
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
    static function parseAddrList($addrStr, $defHost='')
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
	    $part = imap_fetchstructure($connection, $messageId); 
			
	    $mail = $this->mailGetParts($connection, $messageId, $part, 0); 
	   
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
	    $attachments = array(); 
		
	    $attachments[$prefix] = $this->mailDecodePart($connection, $messageId, $part, $prefix); 
	    
		if (isset($part->parts)) // multipart 
	    {
	        if ($prefix == 0) {
	        	$prefix = '';
	        } else {
	        	$prefix = $prefix . '.';
	        }
	        
	        foreach ($part->parts as $number => $subpart) {
	        	$attachments = array_merge($attachments, $this->mailGetParts($connection, $messageId, $subpart, $prefix.($number+1))); 
	        }
	    }
	    
	    if (!$prefix) {
	    	if ($part->type != 1) {
	    		if ($part->type != 2) {
	    			//Ако текстовата част е вградена в хеадър частта, тогава ще се изпълни
					$attachments[1] = $this->mailDecodePart($connection, $messageId, $part, 1);
					//$attachments[$newprefix]['data'] = imap_body($connection, $messageId);
					$attachments[0]['subtype'] = 'changed';
	    		}
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
        static $counter;

	    $attachment = array(); 
		
        if($part->type >= 3) {
            $attachment['isAttachment'] = TRUE; 
            $attachment['name'] = 'part_' . (1 + $count++);
        }

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
	    
	    $attachment['typenumber'] = $part->type;
	    
        $attachment['subtype'] = $part->subtype;
		
	    $attachment['data'] = imap_fetchbody($connection, $messageId, $prefix);
	    
	    if($part->encoding == 3) { // 3 = BASE64 
	        //$attachment['data'] = base64_decode($attachment['data']); 
	        $attachment['data'] = imap_base64($attachment['data']); 
	    } 
	    elseif($part->encoding == 4) { // 4 = QUOTED-PRINTABLE 
	        //$attachment['data'] = quoted_printable_decode($attachment['data']); 
	        $attachment['data'] = imap_qprint($attachment['data']); 
	    } 
	    
	    return $attachment; 
	} 
 }