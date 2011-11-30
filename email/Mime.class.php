<?php 

/**
 *
 * @category   BGERP
 * @package    email
 * @author	   Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 * @since      v 0.1
 * @see        https://github.com/bgerp/bgerp/issues/115
 */
class email_Mime extends core_BaseClass
{

    /**
	 * Текстовата частт на мейла
	 */
	var $textPart;


    /**
     * Рейтинг на текстовата част
     */
    var $bestTextRate = 0;

    /**
     * Индекса на най-подходящата текстова част
     */
    var $bestTextIndex;
		
	
	/**
	 * Масив с данни за изпращача
	 */
	var $from;
		
	
	/**
	 * IP адреса на изпращача
	 */
	var $ip;
		
	
	/**
	 * Езика на мейла
	 */
	var $lg;
	
	
	/**
	 * Дали мейла е спам или не
	 */
	var $spam;
	
	
	/**
	 * Хеша за проверка на уникалността на мейла
	 */
	var $hash;
		
	
	/**
	 * Масив с id => [име на причкаен файл]
	 */
	var $attachedFiles;
	
    
    /**
	 * Масив с cid => fh - вградени (embedded) файлове
	 */
	var $linkedFiles;


    /**
	 * Ресурса на връзката с пощенската кутия
	 */
	var $imapConn;


    /**
     * Масив със съобщения за грешки по време на парсирането
     */
    var $errors = array();


    /**
     * Връща хеша, който служи за проверка на уникалността на мейла
     */
    function getHash()
    {
		if (!isset($this->hash)) {
			$this->hash = md5($this->getHeadersStr());
		}
    	
    	return $this->hash;
    }
    
    
    /**
     * Връща обект с данните в едно писмо.
     */
    function getEmail()
    {	
        // Очакваме, че преди това с метода ->parseAll е парсиран текста на писмото
        expect($this->parts);

        // Минаваме по всички текстови и HTML части да ги запишем като прикачени файлове
        // Пропускаме само тази PLAIN TEXT част, която е използване
    	foreach($this->parts as $index => $p) {
            if($p->type == 'TEXT') {
                if(($index == $this->bestTextIndex) || (!$p->data)) continue;
                if($p->subType == 'HTML') {
                    $p->data = $this->replaceCid($p->data);
                }
                $fileName = $this->getFileName($index);
                $p->filemanId = $this->addFileToFileman($p->data, $fileName);
                if($index == $this->firstHtmlIndex) {
                    $this->htmlFile = $p->filemanId;
                } else {
                    $this->attachedFiles[$p->filemanId] = $fileName;
                }
            }
        }
        
        // Запазваме Message-ID, като премахваме ограждащите скоби
    	$rec->messageId = trim($this->getHeader('Message-ID'), '<>');
    	
        // Декодираме и запазваме събджекта на писмото
    	$rec->subject  = $this->getHeader('Subject');
    	
        // Извличаме информация за изпращача
        $fromHeader    = $this->getHeader('From');
        $fromParser    = new email_Rfc822Addr();
        $parseFrom     = array();
        $fromParser->ParseAddressList($fromHeader, &$parseFrom);
    	$rec->fromEml  = $parseFrom[0]['address']?$parseFrom[0]['address']:$parseFrom[1]['address'];
    	$rec->fromName = $parseFrom[0]['name'] . ' ' . $parseFrom[1]['name'];
        $rec->fromIp   = $this->getSenderIp();

        // Извличаме информация за получателя (към кого е насочено писмото)
        $toHeader      = $this->getHeader('To');
        $toParser      = new email_Rfc822Addr();
        $parseTo       = array();
        $toParser->ParseAddressList($toHeader, &$parseTo);
    	$rec->toEml    = $parseTo[0]['address'];
    	$rec->toBox    = $this->getToBox();
    	
        // Дали писмото е спам
    	$rec->spam     = $this->getSpam();
    	
        // Пробваме да определим езика на който е написана текстовата част
        $rec->lg       = $this->getLg();
    	
        // Определяме датата на писмото
        $d = date_parse($this->getHeader('Date'));
        if(count($d)) {
            $time = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], $d['day'] , $d['year']);
            if($d['is_localtime']) {
                $time = $time + $d['zone'] * 60 + (date("O") / 100 * 60 * 6);
            }

            $rec->date = dt::timestamp2Mysql($time);  
        }

        // Опитваме се да определим държавата на изпращача
    	$rec->country  = $this->getCountry($rec->fromEml, $rec->lg, $rec->fromIp);
    	
        // Задаваме прикачените файлове като keylist
    	$rec->files    = type_Keylist::fromArray($this->attachedFiles);
    	
        // Задаваме първата html част като .html файл
        $rec->htmlFile = $this->htmlFile;
    	
        // Записваме текста на писмото, като [hash].eml файл
     	$emlFileName   = $this->getHash() . '.eml';
    	$emlFileId     = $this->addFileToFileman($this->data, $emlFileName);
    	$rec->emlFile  = $emlFileId;
    	
    	// Задаваме текстовата част
    	$rec->textPart = $this->textPart;
    	
        // Задаваме хеша на писмото
        $rec->hash     = $this->getHash();

    	return $rec;
    }
    
   
    /**
     * Връща хедърната част на писмото като текст
	 * Ако липсват, извлича ги чрез imap връзката
	 */
	function getHeadersStr($partIndex = 1)
	{

        return $this->parts[$partIndex]->headersStr;
	}
	
    
    /**
     * Връща указания хедър.
     * Ако се очаква повече от един хедър с това име, то:
     *
     * - ако $id е положително - връще се записа с индекс $id
     *
     * - ако $id e отрицателно - връща се хедъра с номер $id, като броенето започва отзад на пред. 
     *   при $id == -1 се връща последния срещнат хедър с указаното име
     *
     * - ако $id == 0 се връща първият срещнат хедър с това име. Тази стойност за $id се приема по 
     *   подразбиране и може да не се цитира, ако се очаква с посоченото име да има само един хедър
     * 
     * - ако $id == '*' рвъща конкатенация между всички записи за дадения хедър
     * разделени с интервал
     */
    function getHeader($name, $part = 1, $id = 0)
    {
        if(is_object($part)) {
            $headersArr = $part->headersArr;
        } else {
            $headersArr = $this->parts[$part]->headersArr;
        }

        $name = strtolower($name);
    	
        if ($id == "*") {
    		if (is_array($headersArr[$name])) {
                $res = implode(' ', $headersArr[$name]);
     		}
    	} else {

            if($id < 0) {
                $id = count($headersArr[$name]) + $id;
            }

            expect(is_int($id));
                    
            $res = $headersArr[$name][$id];
        }
        
        return $this->decodeHeader($res);
    }


    /**
	 * Връща адреса, към когото е изпратен мейла. Проверява в email_Inboxes, за първия срещнат.
	 * Ако няма връща първия мейл от масива, който би трябвало да е 'X-Origin-To'
	 */
	function getToBox()
    {
    	$recepients = $this->getHeader('X-Original-To', '*') . ' ' . 
                      $this->getHeader('Delivered-To', '*') . ' ' . 
    		          $this->getHeader('To') . ' ' . 
                      $this->getHeader('Cc') . ' ' . 
                      $this->getHeader('Bcc');

    	$to = email_Inboxes::findFirstInbox($recepients);
    	
    	return $to;
	}


    /**
     * Връща езика на който предполага, че е написано съобщението
     */
    function getLg()
    {
        $lgRates = lang_Encoding::getLgRates($this->textPart);
        
        return arr::getMaxValueKey($lgRates);
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
            preg_match_all($regExp, $this->getHeadersStr(), $matches);  
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
            preg_match_all($regExp, $this->getHeadersStr(), $matches);   
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
     * Проверява дали мейла е спам
     * @todo да се реализира
     */
    protected function getSpam()
    {
    	    	
    	return $this->spam;
    }


    /**
	 * Изчислява коя е вероятната държава от където e изпратен мейла
	 */
    function getCountry($from, $lg, $ip) 
    {
        // Вземаме топ-левъл-домейна на е-мейла на изпращача
		$tld = substr($from, strrpos($from, '.'));
		
        // Двубуквен код на държава, според домейна, на изпращача на е-мейла
        if($tld) {
		    if($ccByEmail = drdata_countries::fetchField("#domain = '{$tld}'", 'letterCode2')) {
                switch($ccByEmail) {
                    case 'us': 
                        $rate = 10;
                        break;
                    case 'gb':
                    case 'de':
                    case 'ru':
                        $rate = 20;
                    default:
                        $rate = 40;
                }
                $countries[$ccByEmail] += $rate;
            }

        }
		
        // Двубуквен код на държава според $ip-то на изпращача
        if($ip) {
		    if($ccByIp = drdata_ipToCountry::get($ip)) {
                switch($ccByIp) {
                    case 'us': 
                        $rate = 30;
                        break;
                    case 'gb':
                    case 'de':
                    case 'ru':
                        $rate = 40;
                    default:
                        $rate = 60;
                }
                $countries[$ccByIp] += $rate;
            }
        }
        
        // Според дъжавата където е локиран сървъра на изпращача

        // Списък с държави в които се говори намерения език
        if($lg) {
            $countries[$lg] += 30;
        }
        
        if(count($countries)) {
            arsort($countries);
            reset($countries);
            $firstCountry = key($countries);
            $countryId = drdata_Countries::fetchField("#letterCode2 = '{$firstCountry}'", 'id');

            return $countryId;
        }
    }


    /**
	 * Вкарва прикрепените файлове във Fileman
	 * 
	 * @return number - id' то на файла
	 */
	function addFileToFileman($data, $name)
	{
        //Вкарваме файла във Fileman
		$Fileman = cls::get('fileman_Files');
	    
        $fh = $Fileman->addNewFileFromString($data, 'Email', $name);
		
		$id = $Fileman->fetchByFh($fh, 'id');

        return $id;
	}
     

    /**
     * Замества cid' овете в html частта с линкове от системата
     */
    function replaceCid($html)
    { 
        if (count($this->linkedFiles)) {
    	
             foreach ($this->linkedFiles as $cid => $fileId) {
            
                $patterns = array("cid:{$cid}" => '', "\"cid:{$cid}\"" => '"', "'cid:{$cid}'" => "'");

                $Download = cls::get("fileman_Download");

                foreach($patterns as $ptr => $q) {
                    if( stripos($html, $ptr) !== FALSE) {
                        //TODO Времето в което е активен линка (100000*3600 секунди) ?
                        $fh = fileman_Files::fetchField($fileId, 'fileHnd');
                        $fileUrl = $Download->getDownloadUrl($fh, 100000) ;
                        $html = str_ireplace($ptr, "{$q}{$fileUrl}{$q}", $html);
                    }
                    
                } 
            }
        }

        return $html;
    }


    /**
     * Връща рейтинга на текст
     * Колкото е по-голям рейтинга, толкова текста е по-съдържателен
     */
    function getTextRate($text)
    {
        $textRate = 0;
        $text = str_replace('&nbsp;', ' ', $text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        if(trim($text)) {
            $textRate += 1;
            $words = preg_replace('/[^\pL\p{Zs}\d]+/u', ' ', $text);

            $textRate += mb_strlen($words);
        }

        return $textRate;
     }


    /**
     * Парсира хедърите в масив
     */
    function parseHeaders($headersStr)
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
	 * Конвертира към UTF-8 текст
	 */
	function convertToUtf8($str, $charset, $subtype)
	{	
        if ($this->is7Bit($str)) {
				// Тук трябва да има магическа функция, която да разпознае
                // дали евентуално няма някаква кодировка на текста (BASE64, QUOTED PRINTABLE ...
                // иначе в 99% от случаите това е просто текст на базова латиница
        } else {
                
            // Частета е с 50% вероятност този, който е посочен в аргумента
            // с 10% е вероятно да е този, който е посочен в хедъра
            // Може да се опитаме да си го разпознаем
            
            $text = preg_replace('/\n/',' ', $str); 
            $text = preg_replace('/<script.*<\/script>/U',' ', $text);
            $text = preg_replace('/<style.*<\/style>/U',' ', $text);
            $text = strip_tags($text);
            $text = str_replace('&nbsp;', ' ', $text);
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            $res = lang_Encoding::analyzeCharsets($text);

            if($charset) {
                $res->rates[$charset] = $res->rates[$charset]*1.2 + 10;
            } 
            
            $charset = arr::getMaxValueKey($res->rates);
 
            if($charset) {
                $str = iconv($charset, 'UTF-8//IGNORE', $str);
                $this->lastLg =  $res->langs[$charset];               
            }


		}

		return $str;
	}


    /**
	 * Декодира хедърната част част
	 */
	function decodeHeader($val)
	{
		if ($this->is7Bit($val)) {
            $imapDecodeArr = imap_mime_header_decode($val);
            
            $decoded = '';
            
            if (count($imapDecodeArr) > 0) {
                foreach ($imapDecodeArr as $value) { 

                    $charset = lang_Encoding::canonizeCharset($value->charset);
                    
                    $decoded .= $charset ? iconv($charset, "UTF-8", $value->text) : $value->text;
                 }
            } else {
                $decoded = $val;
            }
        
        } else {  
            if(mb_detect_encoding($val, "UTF-8", TRUE) == "UTF-8") {
                $charset = 'UTF-8';
             } else {
                 $charset = $this->parts[0]->charset;
            }

			$decoded = $this->convertToUtf8($val, $charset, 'PLAIN'); 
		}
		
		return $decoded;
	}


    /**
     * Проверява дали аргумента е 7 битов стринг
     * 
     * @param string $str - Стринга, който ще се проверява
     * 
     * @return boolean
     * 
     */
    function is7Bit($str)
    {   
        $len = strlen($str);

		for ($i = 0; $i < $len; $i++) {
			if (ord($str{$i}) > 127) {
				
				return FALSE;
			}
		}
		
		return TRUE;
    }

 
    /**
     * Парсира цяло MIME съобщение
     */
    function parseAll($data, $index = 1)
    {
        // Ако не е записано, зашисваме цялото съдържание на писмото
        if(empty($this->data)) $this->data = $data;

        $bestPos = strlen($data);

        foreach( array("\r\n", "\n\r", "\n", "\r") as $c) {
            $pos = strpos($data, $c . $c);
            if($pos > 0 && $pos < $bestPos) {
                $bestPos = $pos;
                $nl = $c;
            }
        }

        if($bestPos < strlen($data)) {
            $data = explode($nl . $nl, $data, 2);
        }

        $p = &$this->parts[$index];
        
        // Записваме хедърите на тази част като стринг
        $p->headersStr = $data[0];

        // Записваме хедърите на тази част като масив (за по-лесно търсене)
        // Масивът е двумерен, защото един хедър може (макар и рядко) 
        // да се среща няколко пъти
        $p->headersArr = $this->parseHeaders($data[0]);
        
        // Парсираме хедъра 'Content-Type'
        $ctParts = $this->extractHeader($p, 'Content-Type', array('boundary', 'charset', 'name'));
        list($p->type, $p->subType) = explode('/', strtoupper($ctParts[0]), 2);
        if(!trim($p->type)) $p->type = 'TEXT';
        $p->charset = lang_Encoding::canonizeCharset($p->charset);

        // Парсираме хедъра 'Content-Transfer-Encoding'
        $cte = $this->extractHeader($p, 'Content-Transfer-Encoding');
        if($cte[0]) {
            $p->encoding = lang_Encoding::canonizeEncoding($cte[0]);
        }

        // Парсираме хедъра 'Content-Disposition'
        $cd = $this->extractHeader($p, 'Content-Disposition', array('filename'));
        if($cd[0]) {
            $p->attachment = $cd[0];
        }
        

        // Ако часта е съставна, рекурсивно изваждаме частите и
        if(($p->type == 'MULTIPART') && $p->boundary) {
 
            $data[1] = explode("--" . $p->boundary, $data[1]);  

            $cntParts = count($data[1]);
            
            if($cntParts < 3) {
                $this->errors[] = "Твърде малко MULTIPART части ($cntParts)";
                $p->data = $data[1];
                return;
            }

            if(strlen($data[1][0]) > 255) {
                $this->errors[] = "Твърде много текст преди първата MULTIPART част";
            }
            
            if(strlen($data[1][$cntParts-1]) > 255) {
                $this->errors[] = "Твърде много текст след последната MULTIPART част";
            }

            for($i = 1; $i < $cntParts-1; $i++) {
                $this->parseAll($data[1][$i], $index . "." . $i);
            }
        
        // Ако частта не е съставна, декодираме, конвертираме към UTF-8 и 
        // евентуално записваме прикачения файл
        } else {
            
            // Декодиране
            switch($p->encoding) {
                case 'BASE64': 
                    $data[1] = imap_base64($data[1]);
                    break;
                case 'QUOTED-PRINTABLE':
                    $data[1] = imap_qprint($data[1]);
                    break;
                case '8BIT':
                case '7BIT':
                default:
            }

            // Конвертиране към UTF-8 
            if($p->type == 'TEXT' && ($p->subType == 'PLAIN' || $p->subType == 'HTML') ) {
                
                $data[1] = $this->convertToUtf8($data[1], $p->charset, $p->subType);
                
                $textRate = $this->getTextRate($data[1]);

                // Ако нямаме никакъв текст в тази текстова част, не записваме данните
                if($textRate < 1) return;
                
                // Записваме данните
                $p->data = $data[1];

                if($textRate > 1.1 * $this->bestTextRate) {
                    if($p->subType == 'HTML') {
                        $this->textPart = html2text_Converter::toRichText($p->data);
                        $this->textPart = html_entity_decode($this->textPart, ENT_QUOTES, 'UTF-8');
                    } else {
                        $this->textPart = $p->data;
                        $this->bestTextIndex = $index;
                    }
                    $this->bestTextRate  = $textRate;
                }
                if($p->subType == 'HTML' && (!$this->firstHtmlIndex) && $textRate > 1) {
                    $this->firstHtmlIndex = $index;
                }

            } else {
            
                // Ако частта представлява атачнат файл, определяме името му и разширението му
                $fileName = $this->getFileName($index);
 
                $p->filemanId = $this->addFileToFileman($data[1], $fileName);
                // Ако имаме 'Content-ID', запазваме го с връзката към файла, 
                // за да можем да свържем вградените граф. файлове в HTML частите
                if($cid = trim($this->getHeader('Content-ID', $p), '<>')) {
                    $this->linkedFiles[$cid] = $p->filemanId;
                }
                $this->attachedFiles[$p->filemanId] = $fileName;
            } 
        }
    }


    /**
     * Екстрактва информационните части на всеки хедър
     */
    function extractHeader(&$part, $headerName, $autoAttributes = array())
    {
        $header = $this->getHeader($headerName, $part);
        $header = str_replace(array("\n", "\r", "\t"), array(';', ';', ';'), $header);
        $hParts = explode(';', $header);
        
        foreach($hParts as $p) {
            if(!trim($p)) continue;
            $p2 = explode('=', $p, 2);
            if(count($p2) == 1) {
                $res[] = $p;
            } else {
                $key = strtolower(trim($p2[0]));
                $value = trim($p2[1], "\"' ");
                $res[$key] = $value;
                if(in_array($key, $autoAttributes)) {
                    $part->{$key} = $value;
                }
            }
        }

        return $res;
    }


    /**
     * Връща най-доброто име за прикачен файл съответстващ на прикачената част
     */
    function getFileName($partIndex)
    {   
        $p = $this->parts[$partIndex];
        
        setIfNot($fileName, $p->filename, $p->name);
        
        if(!$fileName) {
            $fileName = $partIndex . '_' . substr($this->getHash(), 0, 6);
            // Опитваме се да определим разширението от 'Content-Type'
            $ctParts = $this->extractHeader($partIndex, 'Content-Type');
            $mimeT = strtolower($ctParts[0]);
            if($ext = fileman_Mime2Ext::fetchField(array("#mime = '[#1#]' AND #priority = 'yes'", $mimeT), 'ext')) {
                $fileName .= '.' . $ext;
            }
        }

        return $fileName;

    }

}