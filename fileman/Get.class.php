<?php



/**
 * Клас 'fileman_Get' -
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Get extends core_Manager {
    
    
    /**
     * @todo Чака за документация...
     */
    var $maxActive = 6;
    
    
    /**
     * Заглавие на модула
     */
    var $title = 'Вземания от URL';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Обща информация за заявката
        $this->FLD("fileHnd", "varchar(8)", 'caption=Манипулатор');
        $this->FLD("url", "varchar(256,valid=fileman_Get->isValidUrl)", 'caption=URL,mandatory,');
        $this->FLD("maxTrays", "int", 'caption=Макс. опити');
        $this->FLD("priority", "enum(low,medium,high)", 'caption=Приоритет');
        
        // Информация от хедър-а
        $this->FLD("contentType", "varchar", 'caption=Тип');
        $this->FLD("contentLength", "int", 'caption=Размер');
        $this->FLD("eTag", "varchar(32)", 'caption=Етаг');
        $this->FLD("lastModified", "varchar", 'caption=Последна промяна');
        $this->FLD("fileName", "varchar", 'caption=Име');
        
        // Локални параметри
        $this->FLD('tempFile', 'varchar', 'caption=Временен файл');
        $this->FLD('pid', 'varchar(32)', 'caption=ID на процеса');
        $this->FLD('dataId', 'int', 'caption=ID на данните');
        $this->FLD('currentSize', 'int', 'caption=Последен размер');
        $this->FLD('state', 'enum(draft,active,copy,break,finished,error)', 'caption=Състояние,notNull');
        $this->FLD('trays', 'int', 'caption=Опити');
        $this->FLD('errorInfo', 'int', 'caption=Грешка');
        
        $this->load('Files=fileman_Files,expert_Plugin,fileman_Wrapper,fileman_DialogWrapper');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_ValidateFormDownloadFromUrl(&$form)
    {
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderLayoutOfFormDownloadFromUrl_()
    {
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_DownloadFormValidate(&$form)
    {
        $rec = $form->rec;
        cls::load('core_URL');
        $pArr = URL::parseUrl($rec->url);
        
        if(!$pArr['tld']) {
            setIfNot($pArr['error'], 'Липсва разширение на домейна');
        }
        
        $rec->domain = $pArr['domain'];
        
        // Позволени протоколи
        $allowedProtocols = array('http', 'https', 'ftp', 'ftps');
        
        if(!in_array($pArr['scheme'], $allowedProtocols)) {
            setIfNot($pArr['error'], 'Неподдържан протокол:|* <b>' . $pArr['scheme'] . '</b>');
        }
        
        if($pArr['error']) {
            $form->setError('url', $pArr['error']);
        } else {
            $Curl = cls::get('curl_Curl');
            
            $headersArr = $Curl->getHeadersArr($rec->url);
            
            $lastHeader = $headersArr[count($headersArr)-1];
            
            if($lastHeader['Response Code'] == '200') {
                
                if(str::findOn($lastHeader['Content-Type'], 'text/html') &&
                    !Request::get('ignore_warnings')) {
                    $form->setWarning('url', 'На посоченото URL има само web-съдържание');
                } else {
                    $form->rec = $rec;
                    
                    return;
                }
            } elseif($lastHeader['Response Code']{0} == '4') {
                $form->setError('url', 'Грешка в пътя за сваляне:|* <br><small>' . $pArr['path'] . '</small>');
            } elseif(count($headersArr) == 1) {
                $form->setError('url', 'Невъзможно свързване с:|* <b>' . $pArr['host'] . '</b>');
            }
            
            $form->rec = $rec;
            
            return;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function isValidUrl($url, &$result)
    {
        cls::load('core_URL');
        $pArr = URL::parseUrl($url);
        
        // Ако парсирането дава грешка - връщаме я
        if($pArr['error']) {
            $result['error'] = $pArr['error'];
            
            return;
        }
        
        // Ако нямаме разширение на домейна - връщаме грешка
        // от localhost например не можем да теглим
        if(!$pArr['tld']) {
            $result['error'] = 'Липсва разширение на домейна';
            
            return;
        }
        
        // Дали протоколът е от позволените?
        $allowedProtocols = array('http', 'https', 'ftp', 'ftps');
        
        if(!in_array($pArr['scheme'], $allowedProtocols)) {
            $result['error'] = 'Неподдържан протокол:|* <b>' . $pArr['scheme'] . '</b>';
            
            return;
        }
        
        $Curl = cls::get('curl_Curl');
        
        $headersArr = $Curl->getHeadersArr($url);
        
        bp($mvc->extractFileName($headersArr, $url));
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Dialog()
    {
        set_time_limit(300);

        $form = cls::get('core_Form', array('name' => 'Download', 'method' => 'GET'));
        $form->FNC('bucketId', 'int', 'input=none,silent');
        $form->FNC('callback', 'varchar', 'input=none,silent');
        $form->FNC('url', 'url(600)', 'caption=URL,mandatory');

        $rec = $form->input('bucketId,callback,url', TRUE);
 
        if($form->isSubmitted()) {
            
            // Определяне на името на файла
            // 1. От URL-to , ако има след интервал нещо друго
            // 2. От Хедърите
            // 3. От URL-то ако в него има стринг, приличащ на име на файл

            // Име на временния файл
            $tmpFile = str::getRand('********') . '_' . time();
            

            $opts = array('http' =>
                array(
                    'method'  => 'GET',
                    'header' => array(
                        "User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:14.0) Gecko/20100101 Firefox/14.0.1\r\n" .
                        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*\/*;q=0.8\r\n" .
                        "Accept-Language: bg,en-us;q=0.7,en;q=0.3\r\n" .
                        "Accept-Encoding: gzip, deflate\r\n" .
                        "DNT: 1\r\n" .
                        "Connection: keep-alive\r\n" 
                    ), 
                )
            );
            
            $context  = stream_context_create($opts);



            // Вземаме данните от посоченото URL
            $data = @file_get_contents($rec->url, FALSE, $context);
            
            if(!$data) {
                $data = @file_get_contents($rec->url);
            }

            foreach($http_response_header as $l) {
                $hArr = explode(':', $l, 2);
                if(isset($hArr[1])) {
                    $h = $headers[strtolower(trim($hArr[0]))] = strtolower(trim($hArr[1]));
                    $hArr = explode(';', $h);
                    foreach($hArr as $part) {
                        if(strpos($part, '=')) {
                            $pair = explode('=', $part, 2);
                            $headers[trim(strtolower($pair[0]))] = trim($pair[1], "\"\' \t");
                        }
                    }
                }
            }
 
            // Вземаме миме-типа от хедърите
            if(isset($headers['content-type'])) {
                $ct = $headers['content-type'];
                $ct = explode(';', $ct);
                $ct = $ct[0];
                $exts = fileman_Mimes::getExtByMime($ct);
                if(count($exts)) {
                    foreach($exts as $e) {
                        if(stripos($rec->url, '.' . $e)) {
                            $ext = $e;
                            break;
                        }
                    }

                    if(!$ext) $ext = $exts[0];
                }
            }

            $fileName = $headers['filename'];
             
            if(!$fileName) {

                $fPattern = "/[^\\?\\/*:;{}\\\\]+\\.{$ext}/i";

                preg_match($fPattern, $rec->url, $matches);

                $fileName = urldecode($matches[0]);
            }
            // bp($headers, $matches, $fPattern, $rec->url);

            if(!$fileName) {
                $urlArr = core_Url::parseUrl($rec->url);
                $fileName = $urlArr['host'];
            }

            if(!$fileName) {
                $fileName = $tmpFile;
            }
            
            if($ct && $fileName) {
                $fileName = fileman_Mimes::addCorrectFileExt($fileName, $ct);
            }

            // Записваме данните в посочения файл
            file_put_contents($tmpFile, $data);
 
            if($rec->bucketId) {
                    
                // Вземаме инфото на обекта, който ще получи файла
                $Buckets = cls::get('fileman_Buckets');
                    
                // Ако файла е валиден по размер и разширение - добавяме го към собственика му
                if($Buckets->isValid($err, $rec->bucketId, $fileName, $tmpFile)) {
                        
                    // Създаваме файла
                    $fh = $this->Files->createDraftFile($fileName, $rec->bucketId);
                    
                    // Записваме му съдържанието
                    $this->Files->setContent($fh, $tmpFile);
                        
                    $add = $Buckets->getInfoAfterAddingFile($fh);
                        
                    if($rec->callback) {
                        $name = fileman_Files::fetchByFh($fh, 'name');
                        $add->append("<script>  if(window.opener.{$rec->callback}('{$fh}','{$name}') != true) self.close(); else   self.focus();  </script>");
                    }
                }
            }
            
            
            @unlink($tmpFile);

            // Ако има грешки, показваме ги в прозореца за качване
            if(count($err)) {
                $add = new ET("<div style='border:dotted 1px red; background-color:#ffc;'><ul>[#ERR#]</ul></div>");
                
                foreach($err as $e) {
                    $add->append("<li>" . tr($e), 'ERR');
                }
            } else {
                $rec->url = '';
            }

        }
        
        $form->addAttr('url', array('style' => 'width:300px;'));
        
        $form->layout = new ET("
            <form style='margin:0px;' method=\"[#FORM_METHOD#]\" action=\"[#FORM_ACTION#]\" <!--ET_BEGIN ON_SUBMIT-->onSubmit=\"[#ON_SUBMIT#]\"<!--ET_END ON_SUBMIT-->>\n 
            <!--ET_BEGIN FORM_ERROR--><div class=\"formError\">[#FORM_ERROR#]</div><!--ET_END FORM_ERROR-->
            <!--ET_BEGIN FORM_WARNING--><div class=\"formWarning\">[#FORM_WARNING#]</div><!--ET_END FORM_WARNING-->

            Линк към файла за вземане:<p>
            <!--ET_BEGIN FORM_FIELDS--><div class=\"formFields\">[#FORM_FIELDS#]</div><!--ET_END FORM_FIELDS-->
            <p>[#FORM_TOOLBAR#]</p>
            <input name='Protected' type='hidden' value='[#Protected#]'/>
            </form>
        ");
        
        if($add) {
            $form->layout->prepend($add);
        }

        $form->layout->replace(Request::get('Protected'), 'Protected');
        
        $form->toolbar = cls::get('core_Toolbar');
        $form->toolbar->addSbBtn('Вземи файла от това URL') ;

        $html = $form->renderHtml('url', $rec);

        
        $html = $this->renderDialog($html);

        return $html;bp($name);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderDialog_($tpl)
    {
        return $tpl;
    }
    
    
    /**
     * Стартиране на нови сваляния
     */
    function cron_ProcessDraft()
    {
        $OS = cls::get('core_Os');
        $Curl = cls::get('curl_Curl');
        $active = $this->countActive();
        
        // Ако максималния лимит от активни задания е близо
        // не добавяме нови сваляния, а само правим поддръжка на текущите
        
        // Имаме ли задание за сваляне?
        $query = $this->getQuery();
        $query->orderBy("#priority");
        $query->limit(1);
        $rec = $query->fetch("#state = 'draft'");
        
        if($rec) {
            
            if(! ($active >= $this->maxActive ||
                    ($active == $this->maxActive - 1) && $rec->priority == 'medium' ||
                    ($active == $this->maxActive - 2) && $rec->priority == 'low')
            ){
                // Стартираме заданието
                // Генерираме името на временен файл, където ще се сваля
                $rec->trays = 1;
                
                if(!$rec->tempFile) {
                    $rec->tempFile = EF_TEMP_PATH . "/" . $OS->getUniqId();
                }
                
                $headersArr = $Curl->getHeadersArr($rec->url);
                
                $lastHeader = $headersArr[count($headersArr)-1];
                
                if($lastHeader['Response Code'] == '200') {
                    
                    $rec->contentLength = $lastHeader['Content-Length'];
                    $rec->lastModified = $lastHeader['Last-Modified'];
                    $rec->contentType = $lastHeader['Content-Type'];
                    $rec->eTag = str::convertToFixedKey($lastHeader['Etag'], 32);
                    $rec->fileName = ($this->extractFileName($headersArr)) ? ($this->extractFileName($headersArr)) : $rec->fileName;
                    
                    // Ако преди сме сваляли същия файл, то опитваме се да го вземем от данните
                    if($rec->contentLength && $rec->eTag) {
                        $lastRec = $this->fetch("#contentLength = {$rec->contentLength} AND #eTag = '{$rec->eTag}' AND  #dataId > 0 ");
                        
                        if($lastRec) {
                            if($this->Files->setData($lastRec->fileHnd, $lastRec->dataId)) {
                                $rec->state = 'finished';
                            }
                        } elseif ($lastRec->state == 'active') {
                            $rec->state = 'copy';
                            $rec->pid = $lastRec->id;
                            $rec->currentSize = $lastRec->currentSize;
                        }
                    }
                    
                    // Стартираме свалянето, ако не сме сваляли същото нещо 
                    if($rec->state != 'finished' && $rec->state != 'copy') {
                        $rec->pid = $Curl->startDownload($rec->url, $rec->tempFile, $rec->maxTrays);
                        
                        if($rec->pid) {
                            $rec->state = 'active';
                        } else {
                            $rec->state = 'break';
                        }
                    }
                } else {
                    $rec->state = 'break';
                }
                
                $this->save($rec);
            }
        }
        
        // Изчакваме 2 секунди
        sleep(2);
        
        // Правим поддръжка на текущите сваляния
        $query = $this->getQuery();
        
        while($rec = $query->fetch("#state = 'active'")) {
            
            // Дали свалянето не е свършило?
            // array( status ('running', 'done', 'error'), errorInfo => "")
            $status = $Curl->getStatus($rec->pid);
            
            // Вкарваме данните за текущия размер на файла
            if(file_exists($rec->tempFile)) {
                $rec->currentSize = filesize($rec->tempFile);
                $this->save($rec, 'currentSize');
            }
            
            if(($rec->contentLength > 0) &&
                ($rec->contentLength != $rec->currentSize) &&
                ($status['status'] == 'done')) {
                
                $status['status'] = 'error';
            }
            
            if($status['status'] == 'running') {
                // TODO timeout
                continue;
            } elseif ($status['status'] == 'error') {
                // значи е станала грешка
                
                $rec->trays++;
                
                if($rec->trays > $rec->maxTrays) {
                    $rec->state = 'error';
                    
                    if($starus['errorInfo']) {
                        $rec->errorInfo = $starus['errorInfo'];
                    } else {
                        $rec->errorInfo = 'unknown';
                    }
                } else {
                    $rec->state = 'break';
                }
                
                $this->save($rec);
                $this->invoke('DownloadError', array(&$rec));
            } elseif ($status['status'] == 'done') {
                
                $rec->state = 'finished';
                
                $rec->dataId = $this->Files->setContent($rec->fileHnd, $rec->tempFile);
                
                $this->save($rec);
                
                $this->invoke('DownloadDone', array(&$rec));
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function processActive()
    {
    
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Down()
    {
        return $this->cron_ProcessDraft();
    }
    
    
    /**
     * Мониторинг на текущите сваляния
     */
    function cron_ProcessActive()
    {
    
    }
    
    
    /**
     * Подновяване на прекъснати сваляния
     */
    function cron_ProcessBreak()
    {
    
    }
    
    
    /**
     * Колко сваляния в момента имаме активни?
     */
    function countActive()
    {
        $query = $this->getQuery();
        
        return $query->count("#state = 'active'");
    }
    
    
    /**
     * Инсталация на MVC
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $Cron = cls::get('core_Cron');
        
        $systemId = 'fileDownloader';
        
        $res .= "<h4>Нагласяне на Cron</h4>";
        
        for($i = 0; $i<12; $i++) {
            
            $rec = NULL;
            $rec->systemId = $systemId . ($i * 5);
            
            if(!$Cron->fetch("#systemId = '{$rec->systemId}'")) {
                $rec->description = 'Сваля файлове по разписание';
                $rec->controller = "{$mvc->className}";
                $rec->action = 'processDraft';
                $rec->period = 1;
                $rec->offset = 0;
                $rec->delay = $i * 5;
                $rec->timeLimit = 200;
                $Cron->save($rec);
                $res .= "<li><font color='green'>Задаване на Cron {$rec->systemId}</font></li>";
            } else {
                $res .= "<li>Отпреди Cron е бил нагласен за {$rec->systemId}</li>";
            }
        }
        
        $res .= "</ol>";
        
        if(!is_dir(EF_TEMP_PATH)) {
            if(!mkdir(EF_TEMP_PATH, 0777, TRUE)) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') . ' "' . EF_TEMP_PATH . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' . EF_TEMP_PATH . '"</font>';
            }
        }
        
        return $res;
    }
    
    
    /**
     * Изважда предполагаемото име на файл от хедъри-те,
     * които получава cUrl, с допустими редирект-и
     */
    function extractFileName($headersArr, $url)
    {
        
        // Ако сървърът ни дава име на файл - вземаме него
        foreach($headersArr as $h) {
            if($h['Response Code'] == '200' && $h['Content-Disposition']) {
                $fileName = str::cut($h['Content-Disposition'], 'filename=');
                
                if($filename) {
                    $filename = str_replace('"', '', $filename);
                    break;
                }
            }
        }
        
        // Ако не сме намерили име на файл или той няма разширение
        // Определяме разширението на файла от Content-Type
        if(!strpos($filename, '.')) {
            $lastHeader = $headersArr[count($headersArr)];
            $cType = addslashes($lastHeader['Content-Type']);
            
            $Mime2ext = cls::get('fileman_Mime2ext');
            $ext = $Mime2ext->fetchField("#mime = '{$cType}'", 'ext');
            
            // Ако имаме име на файл, което само няма никакво разширение
            // добавяме така намереното разширение
            if($filename && $ext) {
                $filename .= '.' . $ext;
            }
        }
        
        // Ако дотук сме намерили име на файл - връщаме го
        if($filename) return $filename;
        
        // Търсим последователно в URL-тата име на файл
        // Даваме повече точки на този, който:
        // 1. има разширение, което съответства на намерения Content-Type
        // 2. има разширение, което не е в списъка на уеб-скриптовете
        // 
        
        
        $urlArr = URL::parseUrl($url) ;
        $filename = basename($urlArr['path']);
        $bestScore = $this->getFilenameScore($filename, $ext);
        
        $i = 1;
        
        foreach($headersArr as $h) {
            if($h['Location']) {
                $urlArr = URL::parseUrl($h['Location']);
                $tempName = basename($urlArr['path']);
                $i++;
                $newScore = $this->getFilenameScore($filename, $ext) + $i++;
                
                if($newScore > $bestScore) {
                    $filename = $tempName;
                    $bestScore = $newScore;
                }
            }
        }
        
        // Ако сме намерили име на файл от локацията 
        if($filename) {
            
            return $filename;
        }
        
        return $filename;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getFilenameScore($filename, $ext)
    {
        $scriptExt = array('html', 'htm', 'php', 'php2', 'php3', 'php4',
            'php5', 'phtml', 'pwml', 'inc', 'asp', 'aspx',
            'ascx', 'jsp', 'cfm', 'cfc', 'pl', 'cgi');
        
        $score = 0;
        
        $extPart = mb_substr($filename, mb_strrpos($fname, '.'));
        
        if(strtolower($extPart) == strtolower($ext)) {
            $score = 100;
        }
        
        if($extPart && !in_array(strtolower($extPart), $scriptExt)) {
            $score = 10;
        }
    }
}