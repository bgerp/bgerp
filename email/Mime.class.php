<?php 

/**
 * Максималната разрешена памет за използване
 */
defIfNot('MAX_ALLOWED_MEMORY', '500M');


/**
 *
 * @category   BGERP
 * @package    email
 * @author	   Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 * @see        https://github.com/bgerp/bgerp/issues/115
 */
class email_Mime
{
	
	
	/**
	 * Масив със всичките данни за съответния мейл
	 */
	protected $mime = NULL;
	
	
	/**
	 * Хедърите за съответния мейл
	 */
	protected $headers = NULL;
	
	
	/**
	 * HTML частта на мейла
	 */
	protected $html = NULL;
	
	
	/**
	 * Текстовата частт на мейла
	 */
	protected $text = NULL;
	
	
	/**
	 * Charset' а на текстовата част взета от парсера
	 */
	protected $textCharset = NULL;
	
	
	/**
	 * Масив с всички прикрепените файлове (id' тата им) 
	 */
	protected $attachments = NULL;
	
	
	/**
	 * Масив, в който се съдържа ключа и стойността на всеки subtype
	 */
	protected $subtype = NULL;
	
	
	/**
	 * Масив, в който се съдържа ключа и стойността на всеки type
	 */
	protected $type = NULL;
	
	
	/**
	 * Масив, в който се съдържа ключа и номера на всеки type
	 */
	protected $typenumber = NULL;
	
	
	/**
	 * Дължина на текстовата част
	 */
	protected $textLen = NULL;
	
	
	/**
	 * Charset на хедърната част
	 */
	protected $headerCharset = NULL;
	
	
	/**
	 * Масив с парсирани хедъри
	 */
	protected $headersArr = NULL;
	
	
	/**
	 * Message частта на едно съобщение (type = 2)
	 */
	protected $message = NULL;
	
	
	/**
	 * Целия мейл
	 */
	protected $eml = NULL;
	
	
	/**
	 * До кой мейил е изпратено съобщението
	 */
	protected $to = NULL;
	
	
	/**
	 * Масив с данни за изпращача
	 */
	protected $from = NULL;
	
	
	/**
	 * Темета на съобщението
	 */
	protected $subject = NULL;
	
	
	/**
	 * IP адреса на изпращача
	 */
	protected $ip = NULL;
	
	
	/**
	 * messageId' то на мейла
	 */
	protected $messageId = NULL;
	
	
	/**
	 * Езика на мейла
	 */
	protected $lg = NULL;
	
	
	/**
	 * Дали мейла е спам или не
	 */
	protected $spam = NULL;
	
	
	/**
	 * Хеша за проверка на уникалността на мейла
	 */
	protected $hash = NULL;
	
	
	/**
	 * Двубуквения код на държавата, от който е мейла, взет от домейна
	 */
	protected $countryCode = NULL;
	
	
	/**
	 * Двубуквения код на държавата, от който е мейла, взет от ip адреса на изпращача
	 */
	protected $ipCode = NULL;
	
	
	/**
	 * Двубуквения код на държавата, от който е мейла, взет от езика на изпращача
	 */
	protected $lgCode = NULL;
	
	
	/**
	 * id' то на държвата от къдете е мейла
	 */
	protected $fromCountry = NULL;
	
	
	/**
	 * id' то на eml файла в системата
	 */
	protected $emlFileId = NULL;
	
	
	/**
	 * id' то на html файла в системата
	 */
	protected $htmlFileId = NULL;
	
	
	/**
	 * id' то на message файла в системата
	 */
	protected $messageFileId = NULL;
	
	
	/**
	 * Имената на прикачените файлове и техните filehandler' и
	 */
	protected $attachedFilesName = NULL;
			
	
    /**
     * Сваля избранто съобщение от пощенската кутия и го праща за обработка
     * 	
     * @param resource $imap   - ресурс с връзката към пощенската кутия
     * @param number   $number - номер на съобщението
     */
	function setFromImap($imap, $number)
	{
		ini_set('memory_limit', MAX_ALLOWED_MEMORY);
		$EmailParser = cls::get('email_Parser');
		$this->mime = $EmailParser->mailMimeToArray($imap, $number);
		
	}
	
	
	/**
	 * Сетва всичките subtype и type атрибути в масива
	 */
	protected function setTypes()
	{
		if (($this->subtype) || ($this->type) || ($this->typenumber)) {
			
			return ;
		}
		expect($this->mime);
		$arr = $this->mime;
		foreach ($arr as $key => $value) {
			if (isset($arr[$key]['subtype'])) {
				$this->subtype[$key] = $arr[$key]['subtype'];
			}
			
			if (isset($arr[$key]['type'])) {
				$this->type[$key] = $arr[$key]['type'];
			}
			
			if (isset($arr[$key]['typenumber'])) {
				$this->typenumber[$key] = $arr[$key]['typenumber'];
			}
			
		}
		
	}
	
	
	/**
	 * Връща хедърите
	 */
	function getHeaders()
	{
		if (!$this->headers) {
			expect($this->mime);
			$this->headers = $this->mime[0]['data'];
		}
		
		return $this->headers;
	}
	
	
	/**
	 * Връща текстовата част
	 */
	function getText()
	{
		if (!$this->text) {
			expect($this->mime);
			$this->setTypes();
			$key = $this->getSubTypeKey('PLAIN');
			
			if ($key !== FALSE) {
				$this->text = $this->mime[$key]['data'];
			
				$this->textCharset = $this->mime[$key]['charset'];
			}
					
			$this->checkTextPart();
				
			$this->decodeTextPart();
			
		}
				
		return $this->text;
	}
	
	
	/**
	 * Връща HTML частта
	 */
	function getHtml()
	{
		if (!$this->html) {
			expect($this->mime);
			$this->setTypes();
			$key = $this->getSubTypeKey('HTML');
			
			if ($key === FALSE) {
				
				return FALSE;
			}
			$this->html = $this->mime[$key]['data'];
			
			$this->replaceCid();			
		}
		
		return $this->html;
	}
	
	
	/**
	 * Декодира текстовата част
	 */
	protected function decodeTextPart()
	{
		expect($this->text);
		if ($this->is7Bit($this->text, $this->textLen)) {
			$this->text = $this->decodeMime($this->text, $this->textCharset);
		} else {
			$this->text = $this->decode($this->text, $this->textCharset);
		}
		
	}
	
	
	/**
	 * Декодира хедърната част част
	 */
	protected function decodeHeaderPart($val)
	{
		$len = mb_strlen($val);
		if ($this->is7Bit($val, $len)) {
			$decoded = $this->decodeMime($val);
		} else {
			$decoded = $this->decode($val);
		}
		
		return $decoded;
	}
	
	
	/**
	 * Декодира 7 битовите текстове чрез imap_mime_header_decode и ги конвертира
	 */
	protected function decodeMime($string, $textCharset=FALSE)
	{
		$imapDecode = imap_mime_header_decode($string);
		$res = '';
		
		if (count($imapDecode) > 0) {
    		foreach ($imapDecode as $value) { 
    			
    			$text = $value->text;
    			
                $charset = $this->findCharset($text, $value->charset, $textCharset);
    			
    			$charset = strtoupper($charset);
    			    			
    			$res .= $this->convertToUtf($text, $charset);
    		}
	    }	
	    
	    return $res;
	}
	
	/**
	 * Декодира текстовете различни от 7 битова структура и ги конвертира
	 */
	protected function decode($text, $textCharset=FALSE)
	{
		$charset = $this->findCharset($text, FALSE, $textCharset);
    	
		$charset = strtoupper($charset);

		$res = $this->convertToUtf($text, $charset);
		
	    return $res;
	}
	
	
	/**
	 * Конвертира от подадения charset в UTF-8
	 */
	protected function convertToUtf($text, $charset)
	{
		$res = iconv("{$charset}", "UTF-8", $text);
		
		return $res;
	}
	
	
	/**
     * Намира charset' а на текущия текст
     */
    protected function findCharset($str, $valCharset=FALSE, $charsetFromMime=FALSE)
    {	
    	$probableCharset = $this->getProbableCharset($str);
    	
    	if ($probableCharset) {
    		$charset = $probableCharset;
    	} else {
    		if ($charsetFromMime) {
    			$charset = $charsetFromMime;
    		} else {
	    		if ($valCharset) {
	    			$charset = $valCharset;
	    		} else {
	    			$charset = $this->getHeaderCharset();
	    		}
    		}
    			
    	}
    	
    	$charset = strtolower($charset);
    	if (($charset == 'default') || (!$charset)) {
    		$charset = 'UTF-8';
    	}
    	
    	if ($charset == 'ks_c_5601-1987') {
    		$charset = 'EUC-KR';
    	}
    					
    	return $charset;
    }
	
    
    /**
     * @todo да се реализира
     * Връща предполагаемия charset, от подадения стринг
     */
    protected function getProbableCharset($str)
    {
    	
    	return FALSE;
    }
    
    
    /**
     * Връща charset' а на хедърната част
     */
    protected function getHeaderCharset()
    {
    	if (!$this->headerCharset) {
    		
    		$this->getHeadersArr();
    		
    		$headers = $this->headersArr['content-type'];
	    	if (!isset($headers)) {
	    		
	    		$this->headerCharset = FALSE;
	    		
	    		return $this->headerCharset;
	    	}
	    	
	    	foreach ($headers as $value) {
	    		$header .= $value . '; ';
	    	}
	    	
	    	$arr = explode(';', $header);
	    	
	    	foreach ($arr as $value) {
	    		if (strpos($value, 'charset') !== FALSE) {
	    			$charsetArr = explode('=', $value);
	    			$charset = trim($charsetArr[1]);
	    			
	    			$this->headerCharset = $charset;
	    			
	    			return $this->headerCharset;
	    		}
	    	}
    	}
    	
    	return $this->headerCharset;
    	
    }
    
    
    /**
     * Връща масива с хедърите. 
     * Обработва хедъра, който е стринг и го парсира в масив
     */
    protected function getHeadersArr()
    {
		if (!$this->headersArr) {
			$this->getHeaders();
	    	$this->headersArr = email_Parser::parseHeaders($this->headers);
	    	$this->decodeHeadersArr();
		}
    	
    	return $this->headersArr;
    }
	
	
	/**
	 * Декодира целия масив с хедърите
	 */
	protected function decodeHeadersArr()
	{
		expect($this->headersArr);
		foreach ($this->headersArr as $key => $value) {
	        foreach ($value as $i => $val) {             
	        	$v[$key][$i] = $this->decodeHeaderPart($val);
	        }
	    }
	    $this->headersArr = $v;
	}
	
	
	/**
	 * Проверява надеждността на данните в тектовата част. Ако, не са надеждни, тогава се използва html частта
	 */
	protected function checkTextPart()
	{	
		$this->getHtml();
		$lenTextFromHtml = mb_strlen($textFromHtml);
		
		$html2Text = new html2text_Html2text2($this->html); 
        $textFromHtml = $html2Text->get_text();
		
		$text = $this->text;
		$this->textLen = mb_strlen($text);
		$lenText = $this->textLen;
		
		
		if (($lenText) < 4) {
    		
    		$convert = TRUE;
		} else {
    		$question = mb_substr_count($text, '?');
    		
    		$percentQ = $question / $lenText;
    		
    		if ($percentQ > 0.3) {

    			$convert = TRUE;
    		}
    		
			if (($lenText) < 100) {
				
				$convert = TRUE;
			}
    		
		}
		
		if (4 * $this->textLen < $lenTextFromHtml) {
			
			$convert = TRUE;
		}
		
		if ($convert) {
			$this->text = $textFromHtml;
			$this->textLen = $lenTextFromHtml;
			$this->decodeEntity();
		}
		
	}
	
	
	/**
	 * Ако текстовата част е твърде малка спрямо HTML частта, тогава използва html частта вместо текстова
	 */
	protected function htmlToText()
	{
		
		
		
		
        
		//$html2Text = cls::get('html2text_Html2Text');
		//$textFromHtml = $html2Text->convert2text($this->html);
		
	}
	
	
	/**
     * Проверява дали въведената стойност е 7 битова
     * 
     * @param string $str - Стринга, който ще се проверява
     * 
     * @return boolean
     * 
     */
    protected function is7Bit($str, $len)
    {	
		for ($i = 0; $i < $len; $i++) {
			if (ord($str{$i}) > 127) {
				
				return FALSE;
			}
		}
		
		return TRUE;
    }
	
	
	/**
     * Конвертира htmlEntity кодиран стринг към UTF-8, ако текстовата ч
     */
    protected function decodeEntity()
    {
    	if ($this->chekIsEntity()) { 
    		$this->text = html_entity_decode($this->text, ENT_QUOTES, 'UTF-8');
    	}
    }
    
	
    /**
     * Проверява дали стинга е четим
     */
    protected function chekIsEntity()
    {
    	$str = $this->text;
    	$len = $this->textLen;
    	
    	$amp = mb_substr_count($str, '&');
    	$ds = mb_substr_count($str, '#');
    	$dsAmp = $amp + $ds;
    	if (!$len) {
    		
    		return TRUE;
    	}
    	$percentDsAmp = $dsAmp / $len;
    	
    	if ($percentDsAmp > 0.05) {
   			
    		return TRUE;
   		}
    		
    	return FALSE;
    }  
	
	
    /**
     * Връща ключа на първия срещнат subtype
     * @param string $str - стринга, който ще се търси
     */
	protected function getSubTypeKey($str)
	{
		if (is_array($this->subtype)) {
			foreach ($this->subtype as $key => $value) {
				if ($value == $str) {
					
					return $key;
				}
			}
		}
		
		return FALSE;
	}
	
	
	/**
     * Връща ключа на първия срещнат type
     * @param number $id - номера, който ще се търси
     */
	protected function getTypeKey($id)
	{
		if (is_array($this->typenumber)) {
			foreach ($this->typenumber as $key => $value) {
				if ($value == $id) {
					
					return $key;
				}
			}
		}
		
		return FALSE;
	}
	
	
	/**
	 * Връща message частта, ако има такава
	 */
	protected function getMessage()
	{
		if (!$this->message) {
			$key = $this->getTypeKey(2);
			
			if ($key === FALSE) {
				
				return FALSE;
			}
			
			$this->message = $this->mime[$key]['data'];
		}
		
		return $this->message;
	}
	
	
	/**
	 * Връща адреса, към когото е изпратен мейла. Проверява в email_Inboxes, за първия срещнат.
	 * Ако няма връща първия мейл от масива, който би трябвало да е 'X-Origin-To'
	 */
	function getTo()
    {
    	
    	if ($this->to) {
    		
    		return $this->to;
    	}
    	
    	$this->getHeadersArr();
    	$arr['xorigin'] = $this->headersArr['x-original-to'];
    	$arr['delivered'] = $this->headersArr['delivered-to'];
    	$arr['to'] = $this->headersArr['to'];
    	$arr['cc'] = $this->headersArr['cc'];
    	$arr['bcc'] = $this->headersArr['bcc']; //TODO да се премахне или да остане
    	
    	$allTo = '';
    	
    	foreach ($arr as $value) {
    		if (is_array($value)) {
    			foreach ($value as $val) {
    				$allTo .= ' ' . $val;
    			}
    		}
    	}
    	
   	 	$pattern = '/[\s,:;\\\[\]\(\)\>\<]/';
		$values = preg_split( $pattern, $allTo, NULL, PREG_SPLIT_NO_EMPTY );
		$values = array_unique($values);	
		
		foreach ($values as $value) {
			if (type_Email::isValidEmail($value)) {
				$value = trim($value);
				$valExplode = explode("@", $value);
				
				$name = $valExplode[0];
				$domain = $valExplode[1];
				
				$rec = email_Inboxes::fetchField(array("#name = '[#1#]' AND #domain = '[#2#]'", $name, $domain), 'id');
				if ($rec) {
					$this->to = $value;
					
					return $this->to;
				}
				if(!$firstTo) {
					$firstTo = $value;
				}
			}
		}
		
		$this->to = $firstTo;
		
		return $this->to;
	}
	
	
    /**
     * Връща заглавието на мейла
     */
    function getSubject()
    {
    	if (!$this->subject) {
    		$this->getHeadersArr();
	    	$subject = $this->getHeader('subject');
	    	if (!(trim($subject))) {
	    		$subject = '[Липсва заглавие]';
	    	}
	    	
	    	$this->subject = $subject;
	    }
	    
    	return $this->subject;
    }
	
	
	/**
     * Сетва масива с данните за изпращача
     * 
     * @return $this->from['mail'] - Мейли
     * @return $this->from['name'] - Имена
     */
    protected function setFrom()
    {
    	if (!$this->from) {
    		$this->getHeadersArr();
    		$from = $this->getHeader('from');
    		
    		$parseFrom = email_Parser::parseAddrList($from);
    		$fromArr['mail'] = $parseFrom[0]->mailbox . '@' . $parseFrom[0]->host;
			$fromArr['name'] = $parseFrom[0]->personal;
			
    		$this->from = $fromArr;
    	}
    	
    }
    
    
    /**
     * Връща мейла на изпрача
     */
    function getFrom()
    {
    	$this->setFrom();
    	
    	return $this->from['mail'];
    }
	
	
	/**
     * Връща името на изпращача
     */
    function getFromName()
    {
    	$this->setFrom();
    	
    	return $this->from['name'];
    }
	
	
	/**
     * Връща указания хедър. Ако се очаква повече от един хедър с това име, може да се вземе
     * точно посочен номер. Ако номера е отрицателен, броенето започва от зад на пред.
     * Хедър с номер 0 е първия срещнат с това име, а хедър с номер -1 е последния срещнат
     */
    protected function getHeader($name, $id = 0)
    {
    	expect($this->headersArr);
    	
        $name = strtolower($name);

        if($id < 0) {
            $id = count($this->headersArr[$name]) + $id;
        }
		        
        $res = $this->headersArr[$name][$id];
        
        return $res;
    }
	
	
	/**
     * Прави опит да намери IP адреса на изпращача
     */
    function getSenderIp()
    {
    	if ($this->ip) {
    		
    		return $this->ip;
    	}
    	
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
        $this->ip = $ip;
        
        return $this->ip;
    }
	
	
	/**
     * Връща message-id' то на мейла от хедърите
     */
    function getMessageId()
    {
    	if (!$this->messageId) {
    		$this->getHeadersArr();
    		$this->messageId = $this->getHeader('message-id');
    	}
    	
    	return $this->messageId;
    }
    
    
    /**
     * Връща предполагаемия език на мейла
     * @todo да се реализира
     */
    function getLg()
    {
    	if ($this->lg) {
    		
    		return $this->lg;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Проверява дали мейла е спам
     * @todo да се реализира
     */
    function getSpam()
    {
    	if ($this->spam) {
    		
    		return $this->spam;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Връща хеша, който служи за проверка на уникалността на мейла
     */
    function getHash()
    {
    	if ($this->hash) {
    		
    		return $this->hash;
    	}
    	
    	$this->getHeaders();
    	$this->hash = md5($this->headers);
    	
    	return $this->hash;
    }
    
    
	/**
	 * Връща двубуквения код на държавата от който е мейла
	 */
	protected function getCodeFromCountry()
	{	
		if (!$this->countryCode) {
			$from = $this->getFrom();
			$dotPos = mb_strrpos($from, '.');
			$tld = mb_substr($from, $dotPos);
			
			$this->countryCode = drdata_countries::fetchField("#domain = '{$tld}'", 'letterCode2');
		}
		
		return $this->countryCode;
		
	}
	
	
	/**
	 * Връща двубуквения код на държавата от IP' то
	 */
	protected function getCodeFromIp()
	{
		if (!$this->ipCode) {
			$ip = $this->getSenderIp();
			$this->ipCode = drdata_ipToCountry::get($ip);
		}
		
		
		return $this->ipCode;
	}
	
	
	/**
	 * Връща двубуквения код на държавата от езика на писмото
	 * @todo Да се реализира
	*/
	protected function getCodeFromLg()
	{
		if (!$this->lgCode) {
			
		}
		
		return $this->lgCode;
	}
    
    
	/**
	 * Изчислява коя е вероятната държава от където e изпратен мейла
	 */
    function getCountry() 
    {
    	if ($this->fromCountry) {
    		
    		return $this->fromCountry;
    	}
    	
    	$country = $this->getCodeFromCountry();
    	$ip = $this->getCodeFromIp();
    	$lg = $this->getCodeFromLg();
    	
    	//Колко да се добави за посочената държава
    	$fromCountryPlus['bg'] = 2;
    	$fromCountryPlus['us'] = 0;
    	
    	$fromIpPlus['bg'] = 2;
    	$fromIpPlus['gb'] = 3;
    	
    	$fromLgPlus['bg'] = 3;
    	$fromLgPlus['en'] = 0;
    	$fromLgPlus['us'] = 0;
    	$fromLgPlus['gb'] = 0;    	
    			
    	if (strlen($country)) {
    		
    		//Колко да се добави за държава по подразбиране
   	 		$countryPlus = 1;
   	 		
	   	 	if (isset($fromCountryPlus[$country])) {
	    		$countryPlus = intval($fromCountryPlus[$country]);
	    	}
	    	
	    	$arrCode[$country] += $countryPlus; 
   	 	}
    	
    	if (strlen($ip)) {
   	 		$ipPlus = 3;
	   	 	if (isset($fromIpPlus[$ip])) {
	    		$ipPlus = intval($fromIpPlus[$ip]);
	    	}
	    	
	    	$arrCode[$ip] += $ipPlus;
   	 	}
    	
   	 	if (strlen($lg)) {
   	 		$lgPlus = 2;
	   	 	if (isset($fromLgPlus[$lg])) {
	    		$lgPlus = intval($fromLgPlus[$lg]);
	    	}
	    	
	    	$arrCode[$lg] += $lgPlus;
   	 	}
   	 	
    	//Взема ключа на най - голямата стойност
    	asort($arrCode);
    	end($arrCode);   
		$code2 = key($arrCode); 
		
		if (strlen($code2) > 1) {
			$code2 = strtoupper($code2);
			$code2 = drdata_countries::fetchField("#letterCode2='{$code2}'", 'id');
			$this->fromCountry = $code2;
		}
		
    	return $this->fromCountry;
    }
    
    
    /**
     * Връща id' тата на всички прикрепени файлове
     */
    function getAttachments()
    {
    	if ($this->attachments) {
    		
    		return $this->attachments;
    	}
    	
    	expect($this->mime);
    	
    	$arrFiles = $this->insertAttachedFiles();
		    	
    	$this->attachments = type_Keylist::fromArray($arrFiles);
    	
    	return $this->attachments;
    }
    
    
	/**
     * Записваме прикрепените файлове в кофата
     */
	protected function insertAttachedFiles()
	{
		expect($this->mime);
		foreach ($this->mime as $value) {
			if ($value['isAttachment']) {
				$id = $this->insertFilesToFileman($value['data'], $value['filename'], $value['name']);
				$arr[$id] = $id;
			}
		}
		
		return $arr;
	}
    
	
	/**
	 * Вкарва прикрепените файлове във Fileman
	 * 
	 * @return number - id' то на файла
	 */
	protected function insertFilesToFileman($data, $fileName=FALSE, $name=FALSE)
	{
		//Проверяваме за името на файла
		if (isset($fileName)) {
			$newName = $fileName;
		} else {
			if (isset($name)) {
				$newName = $name;
			} else {
				$newName = str::getUniqId();
			}
		}
		
        //Вкарваме файла във Fileman
		$Fileman = cls::get('fileman_Files');
		$fh = $Fileman->addNewFileFromString($data, 'Email', $newName);
		$id = $Fileman->fetchByFh($fh, 'id');
		
		$this->attachedFilesName[$fh] = $newName;
		
		return $id;
	}
    
    
	/**
	 * Връща целия текст необходим за създаване на eml файл
	 */
    protected function getEml()
    {
    	if (!$this->eml) {
    		expect($this->mime);
    		$eml = '';
	    	foreach ($this->mime as $value) {
	    		$eml .= $value['data'] . "\n\n";
	    	}
	    	
	    	$this->eml = $eml;
    	}

    	return $this->eml;
    }
    
    
    /**
     * Записва eml файла в кофата и връща id' то му
     */
    function getEmlFileId()
    {
    	if (!$this->emlFileId) {
    		$data = $this->getEml();
    		if (!$data) {
    			
    			return NULL;
    		}
	    	$name = $this->getHash() . '.eml';
	    	$id = $this->insertFilesToFileman($data, $name);
	    	$this->emlFileId = $id;
    	}
    	
    	return $this->emlFileId;
    }
    
    
	/**
     * Записва html файла в кофата и връща id' то му
     */
    function getHtmlFileId()
    {
    	if (!$this->htmlFileId) {
    		$data = $this->getHtml();
    		if (!$data) {
    			
    			return NULL;
    		}
    		$name = $this->getHash() . '.html';
	    	$id = $this->insertFilesToFileman($data, $name);
	    	$this->htmlFileId = $id;
    	}
    	
    	return $this->htmlFileId;
    }
    
    
	/**
     * Записва message файла в кофата и връща id' то му
     */
    function getMessageFileId()
    {
    	if (!$this->messageFileId) {
    		$data = $this->getMessage();
    		if (!$data) {
    			
    			return NULL;
    		}
    		$name = $this->getHash() . '.msg';
	    	$id = $this->insertFilesToFileman($data, $name);
	    	$this->messageFileId = $id;
    	}
    	
    	return $this->messageFileId;
    }
    
    
    /**
     * Замества cid' овете в html частта с линкове в системата
     */
    protected function replaceCid()
    {
    	expect($this->html);
    	$files = $this->getAttachments();
    	if (!$files) {
    		
    		return $this->html;
    	}
    	
    	$pattern = '/src\s*=\s*\"*\'*cid:\s*\S*/';
		preg_match_all($pattern, $this->html, $match);
		
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
		
    	foreach ($this->attachedFilesName as $fh => $name) {
			
			$Download = cls::get('fileman_Download');
			if ($cidName) {
				$keyCid = array_search($name, $cidName);
				if ($keyCid !== FALSE) {
					//TODO Да времето в което е активен линка (10000*3600 секунди) ?
					//TODO вместо файл да се сложи placeholder' и, които да се променят
					$filePath = 'src="' . $Download->getDownloadUrl($fh, 10000) . '"';
					$this->html = str_replace($cidSrc[$keyCid], $filePath, $this->html);
				}
			} 
		}
    }
    
    
    /**
     * Изчиства $mime променливата
     */
    function unsetMime()
    {
    	unset($this->mime);
    }
        
}

?>