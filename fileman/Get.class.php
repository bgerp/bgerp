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
     * 
     */
    var $canAdd = 'every_one';
    
    
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
     * Връща линк към подадения обект
     * 
     * @param integer $objId
     * 
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        
        return ht::createLink(get_called_class(), array());
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
     * Функция, за вземане на файла от подаденото URL
     * 
     * @param stdObject $rec - bucketId,callback,url
     * @param string $add
     * @param string $err
     * 
     * @return NULL|string
     */
    public static function getFile($rec, &$add=NULL, &$err=array())
    {
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
                                "DNT: 1\r\n" .
                                "Connection: close\r\n"
                        ),
                )
        );
        
        $context  = stream_context_create($opts);
        
        // Вземаме данните от посоченото URL
        $data = @file_get_contents($rec->url, FALSE, $context);
        
        if(!$data) {
            $data = @file_get_contents($rec->url);
        }
        
        $fh = NULL;
        
        $fRec = new stdClass();
        
        if($data === FALSE) {
            $err[] = "Грешка при свалянето на файла.";
        } else {
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
                    
                    // Вземаме нещо, което прилича на екстеншън от URL-то
                    if(!$ext) {
                        $ext = fileman_Files::getExt($rec->url, 4);
                    }

                    // Вземаме дефолтния екстеншън от МИМЕ-типа
                    if(!$ext) {
                        $ext = $exts[0];
                    }
               }
            }
            
            $fileName = $headers['filename'];
            
            if(!$fileName && $ext) {
        		
                $fPattern = "/[^\\?\\/*:;{}\\\\]+\\.{$ext}/i";
        		
                preg_match($fPattern, $rec->url, $matches);
        		
                $fileName = decodeUrl($matches[0]);
            }
            
            // Ако URL-то завършва с нещо като име на файл, го вземаме
            if(!$fileName) {
                $fPattern = "/[=\/]([a-z0-9_\-]{0,40}\.([a-z]{2,4}))$/i";
                preg_match($fPattern, $rec->url, $matches);
                if(!in_array(strtolower($matches[2]), array('php', 'asp', 'jsp')) ) {
                    $fileName = $matches[1];
                }
            }
            
            if(!$fileName) {
                $urlArr = parse_url($rec->url);
                $fileName = str_replace('.', '_', $urlArr['host']);
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
        			
                    $bucketName = fileman_Buckets::fetchField($rec->bucketId, 'name');
                    $fh = fileman::absorb($tmpFile, $bucketName, $fileName);
                    
                    $add = $Buckets->getInfoAfterAddingFile($fh);
        			
                    if($rec->callback) {
                        if (isset($fh)) {
                            $fRec = fileman_Files::fetchByFh($fh);
                        }
                        $add->append("<script>  if(window.opener.{$rec->callback}('{$fh}','{$fRec->name}') != true) self.close(); else   self.focus();  </script>");
                    }
                }
            }
        
            @unlink($tmpFile);
        }
        
        // Ако има грешки, показваме ги в прозореца за качване
        if (!empty($err)) {
            $add = new ET("<div style='border:dotted 1px red; background-color:#ffc;'><ul>[#ERR#]</ul></div>");
        	
            foreach((array)$err as $e) {
                $add->append("<li>" . tr($e), 'ERR');
                fileman_Files::logWarning('Грешка при добавяне на файла от URL: ' . $e);
            }
        } else {
            $rec->url = '';
        	
            if (isset($fh)) {
                fileman_Files::logWrite('Добавен файл от линк', $fRec->id);
            }
        }
        
        return $fh;
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
            $this->getFile($rec, $add);
        }
        
        $form->addAttr('url', array('style' => 'width:100%;'));
        
        $form->layout = new ET("
            <form style='margin:0px;' [#FORM_ATTR#] <!--ET_BEGIN ON_SUBMIT-->onSubmit=\"[#ON_SUBMIT#]\"<!--ET_END ON_SUBMIT-->>\n 
            <!--ET_BEGIN FORM_ERROR--><div class=\"formError\">[#FORM_ERROR#]</div><!--ET_END FORM_ERROR-->
            <!--ET_BEGIN FORM_WARNING--><div class=\"formWarning\">[#FORM_WARNING#]</div><!--ET_END FORM_WARNING-->

           <div style='margin: 10px 0;'> " . tr('Линк към файла за вземане') . ":</div>
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

        return $html;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderDialog_($tpl)
    {
        return $tpl;
    }
    
    
 
    /**
     * Инсталация на MVC
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
         
        if(!is_dir(EF_TEMP_PATH)) {
            if(!mkdir(EF_TEMP_PATH, 0777, TRUE)) {
                $res .= '<li class="debug-error">' . tr('Не може да се създаде директорията') . ': "' . EF_TEMP_PATH . '</li>';
            } else {
                $res .= '<li class="debug-new">' . tr('Създадена е директорията') . ': "' . EF_TEMP_PATH . '"</li>';
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