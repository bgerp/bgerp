<?php 


/**
 * Помощен клас за парсиране на
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/115
 */
class email_Mime extends core_BaseClass
{

    /**
     * Текстоватана имейл-а
     */
    public $textPart;
    
    
    /**
     * Текстовата част на имйела, без да се взема в предвид HTML частта
     */
    var $justTextPart;
    
    
    /**
     * Рейтинг на текстовата част
     */
    var $bestTextRate = 0;
    
    
    /**
     * Индекса на най-подходящата текстова част
     */
    var $bestTextIndex;
    
    
    /**
     * Събджект на писмото
     */
    var $subject;
     
     
    /**
     * Имейлът от хедъра 'To'
     */
    var $toEmail;
   

    /**
     * Името на изпращача от хедъра 'From'
     */
    var $fromName;
    

    /**
     * Имейла на изпращача от хедъра 'From'
     */
    var $fromEmail;
    
    
    /**
     * Времето на изпращане на имейла
     */
    var $sendingTime;
    
    
    /**
     * Езика на имейл-а
     */
    var $lg;

   
    /**
     * IP адреса на изпращача
     */
    var $senderIp;
    
    
    /**
     * Масив с id => [данни за файл] - прикачени файлове
     * ->name
     * ->data
     * ->type
     * ->param
     */
    public $files = array();
    
    
    /**
     * Масив със съобщения за грешки по време на парсирането
     */
    var $errors = array();


    /**
     * Връща събджекта на писмото
     */
    function getSubject()
    {
        if(!isset($this->subject)) {
            $this->subject = $this->getHeader('Subject');
            $this->subject = str_replace(array("\n\t", "\n"), array('', ''),  $this->subject);
        }

        return $this->subject;
    }


    /**
     * Извлича адрес към когото е насочено писмото
     */
    function getToEmail()
    {
        if(!isset($this->toEmail)) {
            $toHeader = $this->getHeader('To');
            $toParser = new email_Rfc822Addr();
            $parseTo = array();
            $toParser->ParseAddressList($toHeader, $parseTo);
            $toEmlArr = type_Email::extractEmails($parseTo[0]['address']);
            $this->toEmail = $toEmlArr[0];
        }
        
        return $this->toEmail;
    }

    
    /**
     * Връща името на изпращача
     */
    function getFromName()
    {
        if(!isset($this->fromName)) {
            $this->parseFromEmail();
        }

        return $this->fromName;
    }


    /**
     * Връща името на изпращача
     */
    function getFromEmail()
    {
        if(!isset($this->fromEmail)) {
            $this->parseFromEmail();
        }

        return $this->fromEmail;
    }
    

    /**
     * Извлича масив с два елемента: Името на изпращача и имейла му
     */
    private function parseFromEmail()
    {
        $fromHeader = $this->getHeader('From');
        $fromParser = new email_Rfc822Addr();
        $parseFrom = array();
        $fromParser->ParseAddressList($fromHeader, $parseFrom);
        $fromEmlStr = $parseFrom[0]['address'] ? $parseFrom[0]['address'] : $parseFrom[1]['address'];
        $this->fromName = $parseFrom[0]['name'] . ' ' . $parseFrom[1]['name'];
        
        if(!$fromEmlStr) {
            $fromEmlArr = type_Email::extractEmails($this->getHeader('Return-Path'));
        } else {
            $fromEmlArr = type_Email::extractEmails($fromEmlStr);
        }
       
        $this->fromEmail = $fromEmlArr[0];
    }
    
    
    /**
     * Определяне на датата на писмото, когато е изпратено
     */
    function getSendingTime()
    {
        if(!isset($this->sendingTime)) {
            // Определяме датата на писмото
            $d = date_parse($this->getHeader('Date'));
            
            if(count($d)) {
                $time = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], $d['day'] , $d['year']);
                
                if($d['is_localtime']) {
                    $time = $time + $d['zone'] * 60 + (date("O") / 100 * 60 * 60);
                }
                
                $this->sendingTime = dt::timestamp2Mysql($time);
            }
        }

        return $this->sendingTime;
    }


    /**
     * Връща езика на който предполага, че е написан имейла
     */
    function getLg()
    {
        if(!isset($this->lg)) {
            
            $defLg = '';
            $defLgArr = array();
            
            if (defined('EF_DEFAULT_LANGUAGE')) {
                $defLg = EF_DEFAULT_LANGUAGE;
                
                $defLgArr[$defLg] = 5;
            }
            
            if ($defLg != 'en' && !preg_match('/\p{Cyrillic}/ui', $this->textPart)) {
                $defLgArr['en'] = 3;
            }
            
            $this->lg = i18n_Language::detect($this->textPart, $defLgArr);
        }

        return $this->lg;
    }


    /**
     * Дали сървърът от който е изпратен имейла е публичен?
     */
    function isFromPublicMailServer()
    {
        if(!isset($this->isFromPublicMailServer)) {
            $this->isFromPublicMailServer = drdata_Domains::isPublic($this->getFromEmail());
        }

        return $this->isFromPublicMailServer;
    }
    
    
    /**
     * Прави опит да намери IP адреса на изпращача
     */
    function getSenderIp()
    {
        if(!isset($this->senderIp)) {
            $ip = trim($this->getHeader('X-Originating-IP', 1, -1), '[]');
            
            if(empty($ip) || (!type_Ip::isPublic($ip))) {
                $ip = trim($this->getHeader('X-Sender-IP', 1, -1), '[]');
            }
         
            if(empty($ip) || !type_Ip::isPublic($ip)) {
                $regExp = '/Received:.*\[((?:\d+\.){3}\d+)\]/';
                preg_match_all($regExp, $this->getHeadersStr(), $matches);
                
                if($ipCnt = count($matches[1])) {
                    for($i = $ipCnt - 1; $i >= 0; $i--) {
                        if(type_Ip::isPublic($matches[1][$i])) {
                            if(strpos($matches[0][$i], '.google.com')) continue;
                            $ip = $matches[1][$i];
                            break;
                        }
                    }
                }
            }

            if(empty($ip) || !type_Ip::isPublic($ip)) {
                $regExp = '/Received:.*?((?:\d+\.){3}\d+)/';
                preg_match_all($regExp, $this->getHeadersStr(), $matches);
                
                if($ipCnt = count($matches[1])) {
                    for($i = $ipCnt - 1; $i >= 0; $i--) {
                        if(strpos($matches[0][$i], '.google.com')) continue;
                        if(type_Ip::isPublic($matches[1][$i])) {
                            $ip = $matches[1][$i];
                            break;
                        }
                    }
                }
            }

            $this->senderIp = $ip;
        }
         
        return $this->senderIp;
    }


    /**
     * Изчислява коя е вероятната държава от където e изпратен имейл-а
     */
    function getCountry()
    {
        $from = $this->getFromEmail();
        $lg   = $this->getLg();
        $ip   = $this->getSenderIp();

        // Вземаме топ-левъл-домейна на имейл-а на изпращача
        $tld = strtolower(substr($from, strrpos($from, '.')));
        
        // Двубуквен код на държава, според домейна, на изпращача на имейл-а
        if(strlen($tld) == 2) {
            if($ccByEmail = strtolower(drdata_Countries::fetchField("#domain = '{$tld}'", 'letterCode2'))) {
                switch($ccByEmail) {
                    case 'us' :
                        $rate = 10;
                        break;
                    case 'gb' :
                    case 'de' :
                    case 'ru' :
                        $rate = 20;
                    default :
                    $rate = 40;
                }
                $countries[$ccByEmail] += $rate;
            }
        }
        
        // Двубуквен код на държава според $ip-то на изпращача
        if($ip) {
            if($ccByIp = strtolower(drdata_ipToCountry::get($ip))) {
                switch($ccByIp) {
                    case 'us' :
                        $rate = 30;
                        break;
                    case 'gb' :
                    case 'de' :
                    case 'ru' :
                        $rate = 40;
                    default :
                    $rate = 60;
                }
                
                // Намаме голямо доверие на IP-то получено от публична услуга
                if($this->isFromPublicMailServer()) {
                    $rate = $rate / 1.2;
                }

                $countries[$ccByIp] += $rate;
            }
        }
        
        // Според държавата където е локиран маил-сървъра на изпращача
        
        // Списък с държави в които се говори намерения език
        if($lg) {
            $countries[$lg] += 30;
        }
    
        // Намираме страната с най-много събрани точки
        if(count($countries)) {
            $firstCountry = strtoupper(arr::getMaxValueKey($countries));
            $countryId = drdata_Countries::fetchField("#letterCode2 = '{$firstCountry}'", 'id');
            
            return $countryId;
        }
    }
    
    
    /**
     * Изходния код на писмото
     */
    function getData()
    {
        return $this->data;
    }


    /**
     * Връща манипулатора на eml файл, отговарящ на писмото
     */
    function getEmlFile()
    {
        // Записваме текста на писмото, като [hash].eml файл
        $emlFileName = md5($this->getHeadersStr()) . '.eml';
        
        $fmId = $this->addFileToFileman($this->data, $emlFileName);
        
        return $fmId;
    }


    /**
     * Връща прикачените файлове
     * 
     * @return array - Масив с всички прикачени файлове
     */
    function getFiles()
    {
        foreach($this->files as  $fRec) {
            
            $list .= ($list ? '' : '|') . $fRec->fmId . '|';
        }
      
        return $list;
    }


    /**
     * Връща id на файла, в който е записана html часта
     */
    function getHtmlFile()
    {
        return $this->htmlFile;
    }
    

    /**
     * Връща съдържанието на HTML часта, ако такава има
     */
    function getHtml()
    {
        if($this->firstHtmlIndex) {
            $p = $this->parts[$this->firstHtmlIndex];

            $html = i18n_Charset::convertToUtf8($p->data, $p->charset, TRUE);
        }

        return $html;
    }


    /**
     * Записва във fileman всички файлове, които са извлечени при парсирането
     */
    function saveFiles_()
    {
        foreach($this->files as $id => &$fRec) { 
            if(!$fRec->fmId) {
                $fRec->fmId = $this->addFileToFileman($fRec->data, $fRec->name);
            }
        }
        
        // Минаваме по всички текстови и HTML части да ги запишем като прикачени файлове
        // Пропускаме само тази PLAIN TEXT част, която е използване
        foreach($this->parts as $index => $p) {
            if($p->type == 'TEXT') {
                if(($index == $this->bestTextIndex) || (!$p->data)) continue;
                
                // В HTML часта заместваме cid:... с линкве към файловете
                if($p->subType == 'HTML') {
                    $p->data = $this->placeInlineFiles($p->data);
                }

                $fileName = $this->getFileName($index);
                
                $p->fileId = $this->addFile($p->data, $fileName, 'part', $p->subType);

                $FRecText = $this->files[$p->fileId];
 
                $FRecText->fmId = $this->addFileToFileman($FRecText->data, $FRecText->name);

                if($index == $this->firstHtmlIndex) {
                    $this->htmlFile = $FRecText->fmId;
                } 
            }
        }
    }


    /**
     * Добавя файл в списъка на прикачените файлове
     */
    function addFile($data, $name, $type = NULL, $param = NULL)
    {   
        $rec = (object) array(
            'name' => $name, 
            'data' => $data, 
            'type' => $type, 
            'param' => $param);
        $id  = count($this->files) + 1;
        $this->files[$id] = $rec;

        return $id;
    }

    
    /**
     * Вкарва прикрепените файлове във Fileman
     *
     * @return fh - манипулатора на файла
     */
    function addFileToFileman($data, $name)
    {
        $fh = fileman::absorbStr($data, 'Email', $name);
        
        $id = fileman::fetchByFh($fh, 'id');
        
        return $id;
    }
    
    
    /**
     * Замества cid' овете в html частта с линкове от системата
     */
    function placeInlineFiles($html)
    {
        if (count($this->files)) {

            foreach ($this->files as $fRec) {
                
                if($fRec->type != 'inline') continue;
                
                $cid = $fRec->param;

                $patterns = array("cid:{$cid}" => '', "\"cid:{$cid}\"" => '"', "'cid:{$cid}'" => "'");
                
                $Download = cls::get("fileman_Download");
                
                foreach($patterns as $ptr => $q) {
                    if(stripos($html, $ptr) !== FALSE) {
                        $fh = fileman_Files::fetchField($fRec->fmId, 'fileHnd');
                        $fileUrl = toUrl(array('fileman_Download', 'Download', 'fh' => $fh));
                        $html = str_ireplace($ptr, "{$q}{$fileUrl}{$q}", $html);
                    }
                }
            }
        }

        return $html;
    }



    /***********************************************************************************
     *                                                                                 *
     *  ФУНКЦИИ  ЗА РАБОТА С ХЕДЪРИ                                                    *
     *                                                                                 *
     ***********************************************************************************/

    /**
     * Връща масив с парсирани хедъри на миме-съобщение
     */
    static function parseHeaders($headersStr)
    {
        $headers = str_replace("\n\r", "\n", $headersStr);
        $headers = str_replace("\r\n", "\n", $headers);
        $headers = str_replace("\r", "\n", $headers);
        $headers = trim($headers);     //
        $headers = explode("\n", $headers);
        
        // парсира масив с хедъри на имейл
        foreach($headers as $h) {
            if(substr($h, 0, 1) != "\t" && substr($h, 0, 1) != " ") {
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
     * Връща хедърната част на писмото като текст
     */
    function getHeadersStr($partIndex = 1)
    {
        return $this->parts[$partIndex]->headersStr;
    }
    
    
    /**
     * Връща указания хедър.
     * Ако се очаква повече от един хедър с това име, то:
     *
     * - ако $id е положително -се записа с индекс $id
     *
     * - ако $id e отрицателно - връща се хедър-а с номер $id, като броенето започва отзад на пред.
     * при $id == -1 се връща последния срещнат хедър с указаното име
     *
     * - ако $id == 0 се връща първият срещнат хедър с това име. Тази стойност за $id се приема по
     * подразбиране и може да не се цитира, ако се очаква с посоченото име да има само един хедър
     *
     * - ако $id == '*'конкатенация между всички записи за дадения хедър
     * разделени с интервал
     */
    function getHeader($name, $part = 1, $headerIndex = 0, $decode=TRUE)
    {
        if(is_object($part)) {
            $headersArr = $part->headersArr;
        } else {
            
            //Ако искаме всички части
            if ($part == '*') {
                foreach ($this->parts as $tPart) {
                    foreach ($tPart->headersArr as $key => $type) {
                        foreach ($type as $id => $val) {
                            
                            //Масив с всички хедъри
                            $headersArr[$key][$id] = $val;
                        }
                    }
                }
            } else {
                
                //Ако искаме точно определена част
                $headersArr = $this->parts[$part]->headersArr;    
            }
        }
        
        return static::getHeadersFromArr($headersArr, $name, $headerIndex, $decode, $this->parts[1]->charset);
    }
    
    
    /**
     * Връща даден хедът от масив
     * 
     * @param array $headersArr - Масив с хедърите
     * @param string $name - Името на хедъра
     * @param mixed $headerIndex - Число или * - Указва, кои да се извлекат
     * @param boolean $decode - Дали да се декодира хедъра
     * 
     * @retun string $res - Съдържанието на хедъра
     */
    static function getHeadersFromArr($headersArr, $name, $headerIndex = 0, $decode = TRUE, $charset = NULL)
    {
        $name = strtolower($name);
        
        if ($headerIndex == "*") {
            if (is_array($headersArr[$name])) {
                $res = implode(' ', $headersArr[$name]);
            }
        } else {
            
            if($headerIndex < 0) {
                $headerIndex = count($headersArr[$name]) + $headerIndex;
            }
            
            expect(is_int($headerIndex));
            
            $res = $headersArr[$name][$headerIndex];
        }

        if ($decode) {
            $res = static::decodeHeader($res, $charset);
        }
        
        return $res;
    }
    
    
    /**
     * Екстрактва информационните части на всеки хедър
     */
    function extractHeader(&$part, $headerName, $autoAttributes = array())
    {
        $header = $this->getHeader($headerName, $part);
        
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
     * Декодира хедърната част част
     */
    static function decodeHeader($val, $charset = NULL)
    {
        // Ако стойността на хедъра е 7-битова, той може да е кодиран
        if(i18n_Charset::is7Bit($val) || (strpos($val, '=?') !== FALSE)) {
            
            $imapDecodeArr = @imap_mime_header_decode($val);
            
            $decoded = '';

            if ($imapDecodeArr && count($imapDecodeArr) > 0) {
                foreach ($imapDecodeArr as $id => $value) {
                    
                    // Нулираме флага
                    $flagAcumText = 0;
                    
                    // Ако е сетнат и не е default
                    if ($imapDecodeArr[$id]->charset && $imapDecodeArr[$id]->charset != 'default') {
                        
                        // Масив с чарсета и вероятността
                        $charsetArr = array($value->charset => 50);
                    }
                    
                    // Ако е сетнат следващич чарсет
                    if ($imapDecodeArr[$id+1]->charset) {
                        
                        // Ако следващия е еднакъв с текущия
                        if ($imapDecodeArr[$id+1]->charset == $value->charset) {
                            
                            // Вдигама флага
                            $flagAcumText = TRUE;
                        } else {
                            if ($imapDecodeArr[$id+1]->charset == 'default') {
                                $flagAcumText = TRUE;
                                // TRUE
                            }
                        }
                    }
                    
                    // Ако има предишен чарсет
                    if ($imapDecodeArr[$id-1]->charset) {
                        
                        // Ако текущия е default и ако следващия и предишния са еднакви
                        if ($imapDecodeArr[$id]->charset == 'default' && $imapDecodeArr[$id-1]->charset == $imapDecodeArr[$id+1]->charset) {
                            
                            // Вдигаме флага
                            $flagAcumText = TRUE;
                        }
                    }
                    
                    // Ако флага е вдигнат
                    if($flagAcumText) {
                        
                        // Добавяме към текста
                        $acumText .= $value->text;
                    } else {
                        
                        // Декодираме текст
                        $decoded .= i18n_Charset::convertToUtf8($acumText . $value->text, $charsetArr);
                        $acumText = '';
                    }
                }
            } else {
                $decoded = $val;
            }
        } else {

            $decoded = i18n_Charset::convertToUtf8($val, $charset);
        }
        
        return $decoded;
    }


    /**
     * Парсира цяло MIME съобщение
     */
    function parseAll($data, $index = 1)
    {
        // Ако не е записано, зачистваме цялото съдържание на писмото
        if(empty($this->data)) $this->data = $data;
        
        $bestPos = strlen($data);
        
        foreach(array("\r\n", "\n\r", "\n", "\r") as $c) {
            $pos = strpos($data, $c . $c);
            
            if($pos > 0 && $pos < $bestPos) {
                $bestPos = $pos;
                $nl = $c;
            }
        }


        // Отделяме хедърите от данните
        if($bestPos < strlen($data)) {
            do {
                list($line, $data) = explode($nl, $data, 2);

                if(!trim($line)) {
                    break;
                } elseif(substr($line, 0, 3) == '--=') {
                    $data = $line . $nl . $data;
                    break;
                }

                $headerStr .= ($headerStr ? $nl : '') . $line;

            } while ($data);
        }
        
        $p = &$this->parts[$index];
        
        if(!is_object($p)) {
            $p = new stdClass();
        }
        
        // Записваме хедъри-те на тази част като стринг
        $p->headersStr = $headerStr;
        
        // Записваме хедъри-те на тази част като масив (за по-лесно търсене)
        // Масивът е двумерен, защото един хедър може (макар и рядко) 
        // да се среща няколко пъти
        $p->headersArr = $this->parseHeaders($headerStr);
        
        // Парсираме хедър-а 'Content-Type'
        $ctParts = $this->extractHeader($p, 'Content-Type', array('boundary', 'charset', 'name'));
        
        // Ако има текст в в началото на боундарито, да го премести в хедърите
        if ($b = $ctParts['boundary']) {
            if ($bPos = mb_strpos($data, '--'. $b)) {
                $headerStr .= $nl . ' ' . mb_strcut($data, 0, $bPos);
                $p->headersStr = $headerStr;
                $p->headersArr = $this->parseHeaders($headerStr);
//                 $ctParts = $this->extractHeader($p, 'Content-Type', array('boundary', 'charset', 'name'));
                
                $data = mb_strcut($data, $bPos);
            }
        }
        
        list($p->type, $p->subType) = explode('/', strtoupper($ctParts[0]), 2);
        
        $p->type = trim($p->type);
        $p->subType = trim($p->subType);
        
        $knownTypes = array('MULTIPART', 'TEXT', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'MODEL', 'X-UNKNOWN');
     
        // Ако типа не е от познатите типове, търсим ги като стринг в хедър-а 'Content-Type'
        // Ако някой познат тип се среща в хедър-а, то приемаме, че той е търсения тип
        if(!in_array($p->type, $knownTypes)) {
            $ct = $this->getHeader('Content-Type', $p);
            
            foreach($knownTypes as $t) {
                if(stripos($ct, $t)) {
                    $p->type = $t;
                    break;
                }
            }
        }
        
        // Ако по никакъв начин не сме успели да определим типа, приемаме че е 'TEXT'
        if(empty($p->type)) {
            if(!$p->name) {
                $p->type = 'TEXT';
            } else {
                $p->type = 'X-UNKNOWN';
            }
        }
        
        $knownSubTypes = array('PLAIN', 'HTML');
        
        // Ако под-типа не е от познатите под-типове, търсим ги като стринг в хедър-а 'Content-Type'
        // Ако някой познат под-тип се среща в хедър-а, то приемаме, че той е търсения под-тип
        if(!in_array($p->subType, $knownSubTypes)) {
            $ct = $this->getHeader('Content-Type', $p);
            
            foreach($knownSubTypes as $t) {
                if(stripos($ct, $t)) {
                    $p->subType = $t;
                    break;
                }
            }
        }
        
        $p->charset = i18n_Charset::getCanonical($p->charset);
        
        // Парсираме хедър-а 'Content-Transfer-Encoding'
        $cte = $this->extractHeader($p, 'Content-Transfer-Encoding');
        
        if($cte[0]) {
            $p->encoding = i18n_Encoding::getCanonical($cte[0]);
        }
        
        // Парсираме хедър-а 'Content-Disposition'
        $cd = $this->extractHeader($p, 'Content-Disposition', array('filename'));
        
        // Парсираме хедър-а 'Content-ID'
        $cid = $this->getHeader('Content-ID', $p);
        
        if ($cd[0]) {
            $p->attachment = $cd[0];
        } else {
            
            // Ако е изпуснат Content-Disposition, но има Content-ID, отбелязваме файла, като inline
            if ($cid) {
                $p->attachment = 'inline';
            }
        }
        
        // Ако частта е съставна, рекурсивно изваждаме частите и
        if(($p->type == 'MULTIPART') && $p->boundary) {
            $data = explode("--" . $p->boundary, $data);
            
            $cntParts = count($data);
            
            if($cntParts == 2) {
                $this->errors[] = "Само едно  boundary в MULTIPART частта ($cntParts)";
                
                if(strlen($data[0]) > strlen($data[1])) {
                    unset($data[1]);
                } else {
                    unset($data[0]);
                }
            }
            
            if($cntParts == 1) {
                $this->errors[] = "Няма нито едно boundary в MULTIPART частта ($cntParts)";
            }
            
            if($cntParts >= 3) {
                if(strlen($data[0]) > 255) {
                    $this->errors[] = "Твърде много текст преди първата MULTIPART част";
                } else {
                    unset($data[0]);
                }
                
                if(strlen($data[$cntParts-1]) > 255) {
                    $this->errors[] = "Твърде много текст след последната MULTIPART част";
                } else {
                    unset($data[$cntParts-1]);
                }
            }
            
            for($i = 0; $i < $cntParts; $i++) {
                if($data[$i]) {
                    $this->parseAll(ltrim($data[$i], $nl), $index . "." . $i);  
                }
            }
            
            // Ако частта не е съставна, декодираме, конвертираме към UTF-8 и 
            // евентуално записваме прикачения файл
        } else {
            
            $data2 = FALSE; 
            // Декодиране
            switch($p->encoding) {
                case 'BASE64' :
                    $data2 = imap_base64($data);
                    break;
                case 'QUOTED-PRINTABLE' :
                    $data2 = imap_qprint($data);
                    break;
                case '8BIT' :
                case '7BIT' :
                default :
            }
            if ($data2 !== FALSE) {
                $data = $data2;
            }
            
            // Ако часта e текстова и не е атачмънт, то по подразбиране, този текст е PLAIN
            if($p->attachment != 'attachment' && $p->type == 'TEXT' && !trim($p->subType)) {
                $p->subType = 'PLAIN';
            }

            // Конвертиране към UTF-8
            if($p->type == 'TEXT' && ($p->subType == 'PLAIN' || $p->subType == 'HTML') && ($p->attachment != 'attachment')) {
                
                $text = i18n_Charset::convertToUtf8($data, $p->charset, $p->subType == 'HTML');
                
                // Текстовата част, без да се гледа HTML частта
                if ($p->subType == 'PLAIN') $this->justTextPart = $text;
                
                // Ако часта е HTML - конвертираме я до текст
                if($p->subType == 'HTML') {
                    $text = html2text_Converter::toRichText($text);
                }

                $textRate = $this->getTextRate($text);
                
                // Отдаваме предпочитания на плейн-частта, ако идва от bgERP
                if($p->subType == 'PLAIN') {
                    
                    if($this->getHeader('X-Bgerp-Hash')) {
                        $textRate = $textRate * 4;
                    } else {
                        $textRate = $textRate * 0.8;
                    }

                    // Ако обаче, текст частта съдържа значително количество HTML елементи,
                    // ние не я предпочитаме
                    $k = (mb_strlen(strip_tags($text)) + 1) / (mb_strlen($text) + 1);
                    $textRate = $textRate * $k * $k;
                }
                
                // Ако нямаме никакъв текст или картинки в тази текстова част, не записваме данните
                if(($textRate < 1) && (stripos($data, '<img ') === FALSE)) return;
                
                if($p->subType == 'HTML') {
                    $p->data = $data;
                } else {
                    $p->data = $text;
                }
                
                // Ако е прикачен файл, намаляме рейтинга
                if ($p->attachment) {
                    $textRate = $textRate * 0.5;
                }
                
                if($textRate > (1.05 * $this->bestTextRate)) {
                    // Записваме данните
                    $this->textPart = $text;
                    
                    // Премахваме излишните празни линии
                    $this->textPart = type_Richtext::removeEmptyLines($this->textPart, 2);

                    if($p->subType != 'HTML') {
                        $this->bestTextIndex = $index;
                    }

                    $this->bestTextRate = $textRate;
                    $this->charset = i18n_Charset::getCanonical($p->charset);
                    $this->detectedCharset = i18n_Charset::detect($data, $p->charset, $p->subType == 'HTML');
                }
               
                if($p->subType == 'HTML' && (!$this->firstHtmlIndex) && ($textRate > 1 || (stripos($data, '<img ') === FALSE))) {
                    
                    $this->firstHtmlIndex = $index;
                }
            } else {
                
                // Ако частта представлява атачнат файл, определяме името му и разширението му
                $fileName = $this->getFileName($index);
                
                $cid = trim($cid, '<>');
                
                $p->filemanId = $this->addFile($data, $fileName, 'inline', $cid);
            }
        }
    }
    
    
    /**
     * Връща рейтинга на текст
     * Колкото е по-голям рейтинга, толкова текста е по-съдържателен
     */
    static function getTextRate($text)
    {
        $textRate = 0;
        $text = str_replace('&nbsp;', ' ', $text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        if(trim($text, " \n\r\t" . chr(194) . chr(160))) {
            $textRate += 1; 
            $notWords = preg_replace('/(\pL{2,})/iu', '', $text);
            $textRate += mb_strlen($text) - mb_strlen($notWords);
        }

        return $textRate;
    }


    /**
     * Връща най-доброто име за прикачен файл съответстващ на прикачената част
     */
    private function getFileName($partIndex)
    {
        $p = $this->parts[$partIndex];
        
        setIfNot($fileName, $p->filename, $p->name);
        
        // Ако липсва файл, името му е производно на хеша на съдържанието му
        if(!$fileName) {
            $partIndexName = str_replace('.', '-', $partIndex);
            $fileName = $partIndexName . '_' . substr(md5($p->data), 0, 6);
        }

        // Ако липсва файлово разширение се опитваме да го определим от 'Content-Type'
        if(!fileman_Files::getExt($fileName)) {
            $ctParts = $this->extractHeader($partIndex, 'Content-Type');
            $mimeT = strtolower($ctParts[0]);
            $fileName = fileman_mimes::addCorrectFileExt($fileName, $mimeT);
        }

        return $fileName;
    }
    



    //---------------------------------------------------------------------------------------------------------------------------------------

    
    /**
     * Взема хедърите от манипулатора на eml файл
     * 
     * @param fileman_Files $emlFileHnd - Манипулатора на eml файла
     * @param boolean $parseHeaders - Дали да се парсират в масив откритите хедъри
     * 
     * @return array $headersArr - Масив с хедърите
     * 		   string $headersArr['string'] - Стринг с хедърите
     * 		   array $headersArr['array'] - Масив с парсираните хедърите /Ако е зададено/
     */
    function getHeadersFromEmlFile($emlFileHnd, $parseHeaders=FALSE)
    {
        // Ако хедърите не са били извлечени
        if (!($headersStr = $this->getHeadersStr())) {
            
            // Вземаме съдържанието на eml файла
            $emlFileContent = fileman_Files::getContent($emlFileHnd);
            
            // Парсираме съдържанието
            $this->parseAll($emlFileContent);
    
            // Стринг с хедърите
            $headersStr = $this->getHeadersStr();    
        }
        
        // Добавяме в масива
        $headersArr['string'] = $headersStr;
        
        // Ако е зададено да се парсират хедърите
        if ($parseHeaders) {
            
            // Добавяме в масива парсираните хедъри
            $headersArr['array'] = $this->parseHeaders($headersStr);
        }
        
        return $headersArr;
    }
    
    
    /**
     * Връща текстовата част на EML файла /Без да взема в предвид HTML частта/
     * 
     * @return string - Текстова част на имейла
     */
    function getJustTextPart()
    {
        
        return $this->justTextPart;
    }
    
    
    /**
     * Екстрактва имейлите и връща само имейл частта на масива
     * 
     * @param string $str - Стринг с имейлите
     * @param boolean $uniq - Дали да е уникален имейла
     * 
     * @return string $res - Резултата
     */
    static function getAllEmailsFromStr($str, $uniq = FALSE)
    {
        // Инстанция на класа
        $toParser = new email_Rfc822Addr();
        
        // Масив в който ще парсираме
        $parseToArr = array();
        
        // Парсираме
        $toParser->ParseAddressList($str, $parseToArr);
        
        // Обхождаме масива
        foreach ((array)$parseToArr as $key => $dummy) {
           
            // Извличаме само имейлите
            $emlArr = type_Email::extractEmails($parseToArr[$key]['address']); 
            
            // Преобразуваме в стринг
            $implode = implode(', ', $emlArr);
            
            // Добавяме към полето
            $res .= ($res) ? ', '. $implode : $implode;
        }

        // Ако имейла трябва да е уникален
        if ($uniq) {
            
            // Разделяме стринга в масив
            $resExplode = explode(', ', $res);
            
            // Махаме повтарящите се записи
            $uniqArr = array_unique($resExplode);
            
            // Обръщаме в стринг
            $res = implode(', ', $uniqArr);    
        }
        
        return $res;
    }


    /**
     * Преобразува списък от имейли, както се срещат в хедърите, във врбална стойност
     */
    static function emailListToVerbal($list)
    {
        if(count($list)) {
            foreach($list as $item) {
                
                $address = $item['address'];
                
                if($address) {
                    
                    if ($item['isExternal']) {
                        $inst = cls::get('type_Email');
                        $address = $inst->toVerbal($address);
                    } else {
                        $address = type_Email::escape($address);
                        
                        if ($item['isWrong']) {
                            $address = "<span style='border-bottom: 1px solid red;'>" . $address . "</span>";
                        }
                    }
                    
                    $res .= "<span>" . $address;
                    if($item['name']) {
                        $res .= ' (' . $item['name'] . ')';
                    }
                    $res .= '</span>, ';
                }
            }
            $res = rtrim($res, ', ');
        }
        
        return $res;
    }


    /**
     * Връща вербално представяне на хедърите на съобщението
     * 
     * @param boolean $decode
     * @param boolean $escape
     *
     * @return string
     */
    public function getHeadersVerbal($decode = TRUE, $escape = TRUE)
    {
        $headers = $this->getHeadersStr();
        $headers = $this->parseHeaders($headers);
        $res = '';
        if(is_array($headers)) {
            $me = cls::get(get_called_class());
            foreach($headers as $h => $c) {
                
                if ($h == 'subject') {
                    $s = TRUE;
                }
                
                $a = implode('; ', $c);
                $h = str_replace(' ', '-', ucwords(str_replace('-', ' ', $h)));
                
                if ($decode) {
                    $a = $me->decodeHeader($a);
                }
                
                if ($escape) {
                    $a = type_Varchar::escape($a);
                }
                
                $res .= "<div><b>{$h}</b>: {$a}</div>";
            }
        }
        
        return $res;
    }
}