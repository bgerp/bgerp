<?php 


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
	 * Тялото на мейла
	 */
	protected $body = NULL;
	
	
	/**
	 * Хедърите за съответния мейл
	 */
	protected $headers = NULL;
	
	
	/**
	 * HTML частта на мейла
	 */
	protected $html = NULL;
	
	
	/**
	 * Всичките html части на мейла
	 */
	protected $allHtml = NULL;
	
	
	/**
	 * Всичките текстови части на мейла
	 */
	protected $allText = NULL;
	
	
	/**
	 * Текстовата частт на мейла
	 */
	protected $text = NULL;
		
	
	/**
	 * Charset на хедърната част
	 */
	protected $headerCharset = NULL;
	
	
	/**
	 * Масив с парсирани хедъри
	 */
	protected $headersArr = NULL;
		
	
	/**
	 * Масив с данни за изпращача
	 */
	protected $from = NULL;
		
	
	/**
	 * IP адреса на изпращача
	 */
	protected $ip = NULL;
		
	
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
	protected $handler = NULL;
		
	
	/**
	 * Имената на прикачените файлове и техните filehandler' и
	 */
	protected $attachedFilesName = NULL;

	
	/**
	 * Номера на съобщението
	 */
	protected $msgNum = NULL;
	
	
	/**
	 * Поредния номер на частта
	 */
	protected $partNumber = 0;
	
	
	/**
	 * Ресурса на връзката с пощенската кутия
	 */
	protected $connection;
	
	
    /**
     * Сетва конекцията и номера на съобщението при инициализиране на класа
     * 	
     * @param resource connection   - ресурс с връзката към пощенската кутия
     * @param number   $msgNum - номер на съобщението
     */
	function init($data)
	{
		$this->connection = $data['connection'];
		$this->msgNum = $data['msgNum'];
	}
	
	/**
	 * Сваля цялото съобщение и го записва в $this->mime
	 */
	protected function getMime()
	{
		$this->mime = $this->mailMimeToArray($this->msgNum);
	}
	
	
	/**
	 * Подготвя хедърите, ако не са готови
	 */
	protected function prepareHeader()
	{
		if (empty($this->headers)) {
			$this->headers = imap_fetchheader($this->connection, $this->msgNum, FT_PREFETCHTEXT);
		}
	}
	
	
	/**
	 * Подготвя тялото на мейла
	 */
	protected function prepareBody()
	{
		if (empty($this->body)) {
			$this->body = imap_body($this->connection, $this->msgNum);
		}
	}
	
	
	/**
	 * Подготвя hash' а на хедърите, ако не са готови
	 */
	protected function prepareHandler()
	{
		if (empty($this->handler)) {
			$this->prepareHeader();
			$this->handler = md5($this->getHeaders());
		}
	}
	
    
    /**
     * Връща хеша, който служи за проверка на уникалността на мейла
     */
    function getHandler()
    {
    	$this->prepareHandler();
    	
    	return $this->handler;
    }
    
    
    /**
     * Връща всички данни или само избраната стойност на хедъра, който търсим като стринг
     */
    protected function getHeaderAll($header, $num='*')
    {
    	$this->prepareHeadersArr();
    	if ($num === "*") {
    		if (is_array($this->headersArr[$header])) {
	    		foreach ($this->headersArr[$header] as $value) {
	    			$headerStr .= $value;
	    		}
    		}
    	} else {
    		$headerStr = $this->headersArr[$header][$num];
    	}
    	
    	return $headerStr;
    }
    	
	
	/**
	 * Връща хедърите
	 */
	function getHeaders()
	{
		$this->prepareHeader();
		
		return $this->headers;
	}
	
	
	/**
	 * Декодира текстовата част
	 */
	protected function decodeTextPart($data, $charset)
	{	
		$len = mb_strlen($data);
		if ($len) {
			if ($this->is7Bit($data, $len)) {
				$data = $this->decodeMime($data, $charset);
			} else {
				$data = $this->decode($data, $charset);
			}
		}
		
		return $data;
	}
	
	
	/**
	 * Декодира хедърната част част
	 */
	protected function decodeHeaderPart($val)
	{
		$len = mb_strlen($val);
		if ($this->is7Bit($val, $len)) {
			$decoded = $this->decodeMime($val, FALSE, TRUE);
		} else {
			$decoded = $this->decode($val, FALSE, TRUE);
		}
		
		return $decoded;
	}
	
	
	/**
	 * Декодира 7 битовите текстове чрез imap_mime_header_decode и ги конвертира
	 */
	protected function decodeMime($data, $textCharset=FALSE, $isHeader=FALSE)
	{
		$imapDecode = imap_mime_header_decode($data);
		$res = '';
		
		if (count($imapDecode) > 0) {
    		foreach ($imapDecode as $value) { 
    			
    			$text = $value->text;
    			
                $charset = $this->findCharset($text, $value->charset, $textCharset, $isHeader);
    			
    			$charset = strtoupper($charset);
    			  			
    			$res .= $this->convertToUtf($text, $charset);
    		}
	    }	
	    
	    return $res;
	}
	
	/**
	 * Декодира текстовете различни от 7 битова структура и ги конвертира
	 */
	protected function decode($data, $textCharset=FALSE, $isHeader=FALSE)
	{
		$charset = $this->findCharset($data, FALSE, $textCharset, $isHeader);
    	
		$charset = strtoupper($charset);
		
		$res = $this->convertToUtf($data, $charset);
		
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
    protected function findCharset($data, $valCharset=FALSE, $charsetFromMime=FALSE, $isHeader=FALSE)
    {	
    	if (!$isHeader) {
	    	if ($charsetFromMime) {
	    		$charset = $charsetFromMime;
	    	} else {
	    		if ($valCharset) {
	    			$charset = $valCharset;
	    		} else {
	    			$charset = $this->getHeaderCharset();
	    		}
	    	}
	    	
	    	if (!$charset) {
	    		$charset = $this->getProbableCharset($data);
	    	}
    	} else {
    		if ($valCharset) {
    			$charset = $valCharset;
    		} else {
    			$charset = $this->getProbableCharset($data);
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
     * Връща предполагаемия charset, от подадения стринг
     */
    protected function getProbableCharset($data)
    {
    	$charset = lang_Encoding::detectCharset($data, $lg);
    	
    	return $charset;
    }
    
    
    /**
     * Връща charset' а на хедърната част
     */
    protected function getHeaderCharset()
    {
    	if (!$this->headerCharset) {
    		
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
	    			$charset = trim($charsetArr[1], " \t\"'");
	    			
	    			$this->headerCharset = lang_Encoding::canonizeCharset($charset);
	    			
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
    protected function prepareHeadersArr()
    {	
    	if (empty($this->headersArr)) {
    		$this->prepareHeader();
    		$this->parseHeaders();
	   		$this->decodeHeadersArr();
    	}
    }
	
	
	/**
	 * Декодира целия масив с хедърите
	 */
	protected function decodeHeadersArr()
	{
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
		$html = $this->html;
		$text = $this->text;
		$textLen = mb_strlen($text);
		$convert = FALSE;
		$html2Text = new html2text_Html2text2($html); 
        $textFromHtml = $html2Text->get_text();
        $lenTextFromHtml = mb_strlen($textFromHtml);
			
		if (($textLen) < 4) {
    		
    		$convert = TRUE;
		} else {
    		$question = mb_substr_count($text, '?');
    		
    		$percentQ = $question / $textLen;
    		
    		if ($percentQ > 0.3) {

    			$convert = TRUE;
    		}
    		
		}
		
		if (4 * $textLen < $lenTextFromHtml) {
			
			$convert = TRUE;
		}
		
		if ($convert) {
			$trimedText = trim($text);
			if (!empty($trimedText)) {
				$this->insertFilesToFileman($text, 'part' . $this->partNumber++ . '.txt');
			}
			
			$this->text = $textFromHtml;
 		}
		
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
     * Конвертира $this->html htmlEntity кодиран стринг към UTF-8 
     */
    protected function decodeEntity($html)
    {
        return html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    }
		
	
	/**
	 * Връща адреса, към когото е изпратен мейла. Проверява в email_Inboxes, за първия срещнат.
	 * Ако няма връща първия мейл от масива, който би трябвало да е 'X-Origin-To'
	 */
	protected function getTo()
    {
    	$recepients = $this->getHeaderAll('x-original-to') . ' ' . $this->getHeaderAll('delivered') . ' ' . 
    		$this->getHeaderAll('to') . ' ' . $this->getHeaderAll('cc') . ' ' . $this->getHeaderAll('bcc');
		
    	$Inboxes = cls::get('email_Inboxes');
    	$to = $Inboxes->findFirstInbox($recepients);
    	
    	return $to;
	}
	
	
    /**
     * Връща заглавието на мейла
     */
    protected function getSubject()
    {
    	$subject = $this->getHeaderAll('subject', 0);
    	if (!(trim($subject))) {
    		$subject = '[Липсва заглавие]';
    	}
    	
    	$subject;
	    
    	return $subject;
    }
	
	
	/**
     * Сетва масива с данните за изпращача
     * 
     * @return $this->from['mail'] - Мейли
     * @return $this->from['name'] - Имена
     */
    protected function prepareFrom()
    {
    	if (!$this->from) {
    		$from = $this->getHeaderAll('from', 0);
    		
    		$parseFrom = $this->parseAddrList($from);
    		$fromArr['mail'] = $parseFrom[0]->mailbox . '@' . $parseFrom[0]->host;
			$fromArr['name'] = $parseFrom[0]->personal;
			
    		$this->from = $fromArr;
    	}
    	
    }
    
    
    /**
     * Връща мейла на изпрача
     */
    protected function getFrom()
    {
    	$this->prepareFrom();
    	
    	return $this->from['mail'];
    }
	
	
	/**
     * Връща името на изпращача
     */
    protected function getFromName()
    {
    	$this->prepareFrom();
    	
    	return $this->from['name'];
    }
	
	
	/**
     * Връща указания хедър. Ако се очаква повече от един хедър с това име, може да се вземе
     * точно посочен номер. Ако номера е отрицателен, броенето започва от зад на пред.
     * Хедър с номер 0 е първия срещнат с това име, а хедър с номер -1 е последния срещнат
     */
    protected function getHeader($name, $id = 0)
    {
    	$this->prepareHeadersArr();
    	
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
    protected function getSenderIp()
    {
    	if ($this->ip) {
    		
    		return $this->ip;
    	}
    	
        $ip = trim($this->getHeaderAll('X-Originating-IP'), '[]');
       
        if(empty($ip) || (!type_Ip::isPublic($ip))) {
            
            $ip = trim($this->getHeaderAll('X-Sender-IP'), '[]');

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
    protected function getMessageId()
    {
    	$messageId = $this->getHeaderAll('message-id', 0);
    	
    	return $messageId;
    }
    
    
    /**
     * Връща предполагаемия език на мейла
     * @todo да се реализира
     */
    protected function getLg()
    {
    	if (!$this->lg) {
    		lang_Encoding::detectCharset($this->text, $this->lg);
    	}
    	
    	return $this->lg;
    }
    
    
    /**
     * Проверява дали мейла е спам
     * @todo да се реализира
     */
    protected function getSpam()
    {
    	    	
    	return $this->spam;
    }
        
    
	/**
	 * Връща двубуквения код на държавата от който е мейла
	 */
	protected function getCodeFromCountry()
	{	
		$from = $this->getFrom();
		$dotPos = mb_strrpos($from, '.');
		$tld = mb_substr($from, $dotPos);
		
		$countryCode = drdata_countries::fetchField("#domain = '{$tld}'", 'letterCode2');
		
		return $countryCode;
		
	}
	
	
	/**
	 * Връща двубуквения код на държавата от IP' то
	 */
	protected function getCodeFromIp()
	{
		$ip = $this->getSenderIp();
		$ipCode = drdata_ipToCountry::get($ip);

		return $ipCode;
	}
	
	
	/**
	 * Връща двубуквения код на държавата от езика на писмото
	 * @todo Да се реализира
	*/
	protected function getCodeFromLg()
	{
		$lgCode = $this->getLg();
		
		return $lgCode;
	}
    
    
	/**
	 * Изчислява коя е вероятната държава от където e изпратен мейла
	 */
    protected function getCountry() 
    {
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
			$country = $code2;
		}
		
    	return $country;
    }
    
    
    /**
     * Връща id' тата на всички прикрепени файлове
     */
    protected function getAttachments()
    {
    	if (is_array($this->attachedFilesName)) {
    		$Fileman = cls::get('fileman_Files');
    		foreach ($this->attachedFilesName as $key => $value) {
    			$id = $Fileman->fetchByFh($key, 'id');
    			$arrFiles[$id] = $value;
    		}
    	}

    	$attachments = type_Keylist::fromArray($arrFiles);
    	
    	return $attachments;
    }
        
	
	/**
	 * Вкарва прикрепените файлове във Fileman
	 * 
	 * @return number - id' то на файла
	 */
	protected function insertFilesToFileman($data, $fileName=FALSE, $name=FALSE, $ret=FALSE)
	{
		//Проверяваме за името на файла
		if (isset($fileName)) {
			$newName = $fileName;
		} else {
			if (isset($name)) {
				$newName = $name;
			} else {
				$newName = 'part' . $this->partNumber++;
			}
		}
		
        //Вкарваме файла във Fileman
		$Fileman = cls::get('fileman_Files');
		$fh = $Fileman->addNewFileFromString($data, 'Email', $newName);
		
		if ($ret) {
			$id = $Fileman->fetchByFh($fh, 'id');
			
			return $id;
		}
		
		$this->attachedFilesName[$fh] = $newName;
		
	}
    
    
	/**
	 * Връща целия текст необходим за създаване на eml файл
	 */
    protected function getEml()
    {
    	$this->prepareHeader();
    	$header = $this->headers;
    	
    	$this->prepareBody();
    	$body = $this->body;
    			
    	$eml = $header . "\n\n" . $body;
    	
    	return $eml;
    }
    
    
    /**
     * Записва eml файла в кофата и връща id' то му
     */
    protected function getEmlFileId()
    {
		$data = $this->getEml();

    	$name = $this->getHandler() . '.eml';
    	$emlFileId = $this->insertFilesToFileman($data, $name, FALSE, TRUE);
    	   	
    	return $emlFileId;
    }
    
    
	/**
     * Записва html файла в кофата и връща id' то му
     */
    function getHtmlFileId()
    {
    	$data = $this->html;
    	$name = $this->getHandler() . '.html';
	    $htmlFileId = $this->insertFilesToFileman($data, $name, FALSE, TRUE);
    	
    	return $htmlFileId;
    }
        
    
    
    
    
    /**
     * Изчиства HTML' а против XSS атаки
     */
    protected function clearHtml($html, $charset = NULL)
    {
    	$Purifier = cls::get('hclean_Purifier');
  			
  		return $Purifier->clean($html, $charset);
    }
    
    
	/**
     * Замества cid' овете в html частта с линкове в системата
     */
    protected function replaceCid($html)
    {
    	$files = $this->getAttachments();
    	if (!$files) {
    		
    		return $html;
    	}
    	
    	$pattern = '/src\s*=\s*\"*\'*cid:\s*\S*/';
		preg_match_all($pattern, $html, $match);
		
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
					$filePath = 'src="' . $Download->getDownloadUrl($fh, 10000) . '"';
					$html = str_replace($cidSrc[$keyCid], $filePath, $html);
				}
			} 
		}
		
		return $html;
    }
    
    
    /**
     * Обработва мейла във вебален вид. 
     */
    protected function prepareMime()
    {
    	$this->getMime();
    	
    	foreach ($this->mime as $key => $value) {
    		
    		if (($value['typenumber']) == 0) {
    			
    			$attach = TRUE;
    			$ext = FALSE;
    			
    			$data = trim($value['data']);
    			
    			if (empty($data)) {
    				
    				continue;    				
    			}
    			
    			$this->mime[$key]['data'] = $this->decodeTextPart($value['data'], $value['charset']);
    			
    			if ($value['subtype'] == 'PLAIN') {
    				$attach = FALSE;
    				if (!$this->allText['attach']) {
    					$this->allText['attach'] = $this->mime[$key]['data'];
    				} else {
    					$this->allText[] = $this->mime[$key]['data'];
    				}
    			}
    			
    			if ($value['subtype'] == 'HTML') {
    				$attach = FALSE;
    				$this->mime[$key]['data'] = $this->decodeEntity($value['data']);
		    		
    				if (!$this->allHtml['attach']) {
    					$this->allHtml['attach'] = $this->mime[$key]['data'];
    				} else {
    					$this->allHtml[] = $this->mime[$key]['data'];
    				}
    			}
    			
    			if ($attach) {
    				$name = 'part' . $this->partNumber++;
    				$this->insertFilesToFileman($this->mime[$key]['data'], $name);
    			}
    		}
    		
    		if ($value['isAttachment']) {
    			$this->insertFilesToFileman($this->mime[$key]['data'], $value['filename'], $value['name']);
    		}
    	}
    	
    	if (is_array($this->allHtml)) {
    		foreach ($this->allHtml as $key => $html) {
    			
    			$html = $this->replaceCid($html);
    			$html = $this->clearHtml($html, 'UTF-8');
    			
    			if ($key == 'attach') {
    				$this->html = $html;
    			} else {
    				$name = 'part' . $this->partNumber++ . '.html';
    				$this->insertFilesToFileman($html, $name);
    			}
    		}
    	}
    	
   	 	if (is_array($this->allText)) {
    		foreach ($this->allText as $key => $text) {
    			
    			if ($key == 'attach') {
    				$this->text = $text;
    			} else {
    				$name = 'part' . $this->partNumber++ . '.txt';
    				$this->insertFilesToFileman($text, $name);
    			}
    		}
    	}
    	
    	$this->checkTextPart();
    }
    
    
    /**
     * Връща обект с данните в едно писмо.
     */
    function getEmail()
    {	
    	$this->prepareMime();
    	
    	$rec->messageId = $this->getMessageId();
    	
    	$rec->subject = $this->getSubject();
    	
    	$rec->from = $this->getFrom();
    	
    	$rec->fromName = $this->getFromName();
    	
    	$rec->to = $this->getTo();
    	
    	$rec->spam = $this->getSpam();
    	
    	$rec->lg = $this->getLg();
    	
    	$rec->country = $this->getCountry();
    	
    	$rec->fromIp = $this->getSenderIp();
    	
    	$rec->files = $this->getAttachments();
    	
    	$rec->emlFile = $this->getEmlFileId();
    	
    	$rec->htmlFile = $this->getHtmlFileId();
    	
    	$rec->textPart = $this->text;
    	
    	$rec->htmlPart = $this->html;

    	return $rec;
    }
      
    
	/**
     * Парсира хедърите в масив
     */
    protected function parseHeaders()
    {
    	$this->prepareHeader();
    	$headersStr = $this->headers;
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
        $this->headersArr = $headersArr;
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
    protected function parseAddrList($addrStr, $defHost='')
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
    protected function rfcParseHeaders($header)
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
	protected function mailParseHeaders($headers) 
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
	protected function mailMimeToArray($messageId, $parseHeaders=FALSE) 
	{ 
	    $part = imap_fetchstructure($this->connection, $messageId); 
			
	    $mail = $this->mailGetParts($messageId, $part, 0); 
	   
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
	protected function mailGetParts($messageId, $part, $prefix=0) 
	{   
	    $attachments = array(); 
		
	    $attachments[$prefix] = $this->mailDecodePart($messageId, $part, $prefix); 
	    
		if (isset($part->parts)) // multipart 
	    {
	        if ($prefix == 0) {
	        	$prefix = '';
	        } else {
	        	$prefix = $prefix . '.';
	        }
	        
	        foreach ($part->parts as $number => $subpart) {
	        	$attachments = array_merge($attachments, $this->mailGetParts($messageId, $subpart, $prefix.($number+1))); 
	        }
	    }
	    
	    if (!$prefix) {
	    	if ($part->type != 1) {
	    		if ($part->type != 2) {
	    			//Ако текстовата част е вградена в хеадър частта, тогава ще се изпълни
					$attachments[1] = $this->mailDecodePart($messageId, $part, 1);
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
	protected function mailDecodePart($messageId, $part, $prefix=0) 
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
		
	    $attachment['data'] = imap_fetchbody($this->connection, $messageId, $prefix);
	    
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

?>