<?php



/**
 * @todo Чака за документация...
 */
defIfNot('EF_DOWNLOAD_ROOT', '_dl_');


/**
 * @todo Чака за документация...
 */
defIfNot('EF_DOWNLOAD_DIR', EF_INDEX_PATH . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . EF_DOWNLOAD_ROOT);


/**
 * @todo Чака за документация...
 */
defIfNot('EF_DOWNLOAD_PREFIX_PTR', '$*****');




/**
 * Клас 'fileman_Download' -
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
class fileman_Download extends core_Manager {
    
    
    /**
     * @todo Чака за документация...
     */
    var $pathLen = 6;
    
    
    /**
     * Заглавие на модула
     */
    var $title = 'Сваляния';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Файлов манипулатор - уникален 8 символно/цифров низ, започващ с буква.
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD("fileName", "varchar(255)", 'notNull,caption=Име');
        
        $this->FLD("prefix", "varchar(" . strlen(EF_DOWNLOAD_PREFIX_PTR) . ")",
            array('notNull' => TRUE, 'caption' => 'Префикс'));
        
        // Име на файла
        $this->FLD("fileId",
            "key(mvc=fileman_Files)",
            array('notNull' => TRUE, 'caption' => 'Файл'));
        
        // Крайно време за сваляне
        $this->FLD("expireOn",
            "datetime",
            array('caption' => 'Активен до'));
        
        // Плъгини за контрол на записа и модифицирането
        $this->load('plg_Created,Files=fileman_Files,fileman_Wrapper,Buckets=fileman_Buckets');
        
        // Индекси
        $this->setDbUnique('prefix');
    }
    
    
    /**
     * Връща URL за сваляне на файла с валидност publicTime часа
     */
    static function getDownloadUrl($fh, $lifeTime = 1)
    {
        // Намираме записа на файла
        $fRec = fileman_Files::fetchByFh($fh);
        
        if(!$fRec) return FALSE;
        
        $time = dt::timestamp2Mysql(time() + $lifeTime * 3600);
        
        //Ако имаме линк към файла, тогава използваме същия линк
        $dRec = static::fetch("#fileId = '{$fRec->id}'");

        if ($dRec) {
            
            // Ако времето, за което е активен линка е по малко от времето, което искаме да зададем
            if ($dRec->expireOn < $time) {
                $dRec->expireOn = $time;
            }
            
            $link = sbf(EF_DOWNLOAD_ROOT . '/' . $dRec->prefix . '/' . $dRec->fileName, '', TRUE);
            
            static::save($dRec);
            
            return $link;
        }
        
        $rec = new stdClass();
        
        // Генерираме името на директорията - префикс
        do {
            $rec->prefix = str::getRand(EF_DOWNLOAD_PREFIX_PTR);
        } while (static::fetch("#prefix = '{$rec->prefix}'"));
        
        // Задаваме името на файла за сваляне - същото, каквото файла има в момента
        $rec->fileName = $fRec->name;
        
        if(!is_dir(EF_DOWNLOAD_DIR . '/' . $rec->prefix)) {
            mkdir(EF_DOWNLOAD_DIR . '/' . $rec->prefix, 0777, TRUE);
        }
        
        // Вземаме пътя до данните на файла
        $originalPath = fileman_Files::fetchByFh($fRec->fileHnd, 'path');
        
        // Генерираме пътя до файла (hard link) който ще се сваля
        $downloadPath = EF_DOWNLOAD_DIR . '/' . $rec->prefix . '/' . $rec->fileName;
        
        // Създаваме хард-линк или копираме
        if(!function_exists('link') || !@link($originalPath, $downloadPath)) {
            if(!@copy($originalPath, $downloadPath)) {
                error("Не може да бъде копиран файла|* : '{$originalPath}' =>  '{$downloadPath}'");
            }
        }
        
        // Задаваме id-то на файла
        $rec->fileId = $fRec->id;
        
        // Задаваме времето, в което изтича възможността за сваляне
        $rec->expireOn = $time;
        
        // Записваме информацията за свалянето, за да можем по-късно по Cron да
        // премахнем линка за сваляне
        static::save($rec);
        
        static::createHtaccess($fRec->name, $rec->prefix);
        
        // Връщаме линка за сваляне
        return sbf(EF_DOWNLOAD_ROOT . '/' . $rec->prefix . '/' . $rec->fileName, '', TRUE);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Download()
    {
        // Манипулатора на файла
        $fh = Request::get('fh');
        
        // Очакваме да има подаден манипулатор
        expect($fh, 'Липсва манупулатора на файла');
        
        // Ескейпваме манупулатора
        $fh = $this->db->escape($fh);
        
        // Вземаме записа на манипулатора
        $fRec = $this->Files->fetchByFh($fh);
        
        // Очакваме да има такъв запис
        expect($fRec, 'Няма такъв запис.');
        
        // Очакваме да има права за сваляне
        $this->Files->requireRightFor('download', $fRec);
        
        // Генерираме линк за сваляне
        $link = $this->getDownloadUrl($fh, 1);
        
        // Ако искам да форсираме свалянето
        if (Request::get('forceDownload')) {

            // Големина на файла
            $fileLen = fileman_Data::fetchField($fRec->dataId, 'fileLen');

            // Задаватам хедърите
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($link));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
//            header('Pragma: public'); //TODO Нужен е когато се използва SSL връзка в браузъри на IE <= 8 версия
            header('Content-Length: ' . $fileLen);
            
            // Предизвикваме сваляне на файла
            readfile($link);  
            
            // Прекратяваме изпълнението на скрипта
            shutdown(); 
        } else {
            
            // Редиректваме към линка
            redirect($link);  
        }
    }
    
    
    /**
     * Изтрива линковете, които не се използват и файловете им
     */
    function clearOldLinks()
    {
        $now = dt::timestamp2Mysql(time());
        $query = self::getQuery();
        $query->where("#expireOn < '{$now}'");
        
        $htmlRes .= "<hr />";
        
        $count = $query->count();
        
        if (!$count) {
            $htmlRes .= "\n<li style='color:green'> Няма записи за изтриване.</li>";
        } else {
            $htmlRes .= "\n<li'> {$count} записа за изтриване.</li>";
        }
        
        while ($rec = $query->fetch()) {
            
            $htmlRes .= "<hr />";
            
            $dir = EF_DOWNLOAD_DIR . '/' . $rec->prefix;
            
            if (self::delete("#id = '{$rec->id}'")) {
                $htmlRes .= "\n<li> Deleted record #: $rec->id</li>";
                
                if (core_Os::deleteDir($dir)) {
                    $htmlRes .= "\n<li> Deleted dir: $rec->prefix</li>";
                } else {
                    $htmlRes .= "\n<li style='color:red'> Can' t delete dir: $rec->prefix</li>";
                }
            } else {
                $htmlRes .= "\n<li style='color:red'> Can' t delete record #: $rec->id</li>";
            }
        }
        
        return $htmlRes;
    }
    
    
    /**
     * Стартиране на процеса за изтриване на ненужните файлове
     */
    function act_ClearOldLinks()
    {
        $clear = $this->clearOldLinks();
        
        return $clear;
    }
    
    
    /**
     * Стартиране на процеса за изтриване на ненужните файлове по крон
     */
    function cron_ClearOldLinks()
    {
        $clear = $this->clearOldLinks();
        
        return $clear;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        if(!is_dir(EF_DOWNLOAD_DIR)) {
            if(!mkdir(EF_DOWNLOAD_DIR, 0777, TRUE)) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') .
                ' "' . EF_DOWNLOAD_DIR . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' .
                EF_DOWNLOAD_DIR . '"</font>';
            }
        }
        
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec = new stdClass();
        $rec->systemId = 'ClearOldLinks';
        $rec->description = 'Изчиства старите линкове за сваляне';
        $rec->controller = $mvc->className;
        $rec->action = 'ClearOldLinks';
        $rec->period = 100;
        $rec->offset = 0;
        $rec->delay = 0;
        
        // $rec->timeLimit = 200;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да изчиства линкове и директории, с изтекъл срок.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да изчиства линкове и директории, с изтекъл срок.</li>";
        }
        
        return $res;
    }
    
    
    /**
     * Ако имаме права за сваляне връща html <а> линк за сваляне на файла.
     */
    static function getDownloadLink($fh)
    {
    	$conf = core_Packs::getConfig('fileman');
    	
        //Намираме записа на файла
        $fRec = fileman_Files::fetchByFh($fh);
        
        //Проверяваме дали сме отркили записа
        if(!$fRec) return FALSE;
        
		// Дали файла го има? Ако го няма, вместо линк, връщаме името му
		$path = fileman_Files::fetchByFh($fh, 'path');

        //Името на файла
        $name = $fRec->name;
        
        //Разширението на файла
        $ext = fileman_Files::getExt($fRec->name);
        
        //Иконата на файла, в зависимост от разширението на файла
        $icon = "fileman/icons/{$ext}.png";
        
        //Ако не можем да намерим икона за съответното разширение, използваме иконата по подразбиране
        if (!is_file(getFullPath($icon))) {
            $icon = "fileman/icons/default.png";
        }
        
        //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        //Атрибути на линка
        $attr['class'] = 'linkWithIcon';
//        $attr['target'] = '_blank';
        $attr['style'] = 'background-image:url(' . sbf($icon, "'", $isAbsolute) . ');';
        
        //Инстанция на класа
        $FileSize = cls::get('fileman_FileSize');
        
        //Ако имаме права за сваляне на файла
        if (fileman_Files::haveRightFor('download', $fRec) && ($fRec->dataId) && file_exists($path)) {
            
            //Големината на файла в байтове
            $fileLen = fileman_Data::fetchField($fRec->dataId, 'fileLen');
            
            //Преобразуваме големината на файла във вербална стойност
            $size = $FileSize->toVerbal($fileLen);

            //Ако сме в режим "Тесен"
            if (Mode::is('screenMode', 'narrow')) {
                
                //Ако големината на файла е по - голяма от константата
                if ($fileLen >= $conf->LINK_NARROW_MIN_FILELEN_SHOW) {
                    
                    //След името на файла добавяме размера в скоби
                    $name = $fRec->name . "&nbsp;({$size})";     
                }
            } else {
                
                //Заместваме &nbsp; с празен интервал
                $size =  str_ireplace('&nbsp;', ' ', $size);
                
                //Добавяме към атрибута на линка информация за размера
                $attr['title'] = tr("|Размер:|* {$size}");
				
            }
            
            //Генерираме връзката 
            $url  = toUrl(array('fileman_Files', 'Single', $fh), $isAbsolute);
            $link = ht::createLink($name, $url, NULL, $attr);
        } else {
			if(!file_exists($path)) {
				$attr['style'] .= ' color:red;';
			}
            //Генерираме името с иконата
            $link = "<span class='linkWithIcon' style=\"" . $attr['style'] . "\"> {$name} </span>";
        }

        $ext = fileman_Files::getExt($fRec->name);

        //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
       
        return $link;
    }
    
    
    /**
     * Връща линк за сваляне, според ID-то
     */
    static function getDownloadLinkById($id)
    {
        $fh = fileman_Files::fetchField($id, 'fileHnd');
        
        return fileman_Download::getDownloadLink($fh);
    }


    /**
     * Проверява mime типа на файла. Ако е text/html добавя htaccess файл, който посочва charset'а с който да се отвори.
     * Ако не може да се извлече charset, тогава се указва на сървъра да не изпраща default charset' а си.
     * Ако е text/'различно от html' тогава добавя htaccess файл, който форсира свалянето на файла при отварянето му.
     */
    static function createHtaccess($fileName, $prefix)
    {
        $folderPath = EF_DOWNLOAD_DIR . '/' . $prefix;

        $filePath = $folderPath . '/' . $fileName;
        
        $ext = fileman_Files::getExt($fileName);
        
        if (strlen($ext)) {
            
            $mime = fileman_Mimes::getMimeByExt($ext);
            
            $mimeExplode = explode('/', $mime);
            
            if ($mimeExplode[0] == 'text') {
                if ($mimeExplode[1] == 'html') {
                    
                    $charset = static::findCharset($filePath);
                    
                    if ($charset) {
                        $str = "AddDefaultCharset {$charset}";
                    } else {
                        $str = "AddDefaultCharset Off";
                    }
                } else {
                    $str = "AddType application/octet-stream .{$ext}";
                }
                static::addHtaccessFile($folderPath, $str);
            }
        }
    }
    
    
    /**
     * Намира charset'а на файла
     */
    static function findCharset($file)
    {
        $content = file_get_contents($file);
        
        $pattern = '/<meta[^>]+charset\s*=\s*[\'\"]?(.*?)[[\'\"]]?[\/\s>]/i';
        
        preg_match($pattern, $content, $match);
        
        if ($match[1]) {
            $charset = strtoupper($match[1]);
        } else {
            //Ако във файла няма мета таг оказващ енкодинга, тогава го определяме
            $res = lang_Encoding::analyzeCharsets(strip_tags($content));
            $charset = arr::getMaxValueKey($res->rates);
        }
        
        return $charset;
    }
    
    
    /**
     * Създава .htaccess файл в директорията
     */
    static function addHtaccessFile($path, $str)
    {
        $file = $path . '/' . '.htaccess';
        
        $fh = @fopen($file, 'w');
        fwrite($fh, $str);
        fclose($fh);
    }
    
    
    /**
     * Екшън за генериране на линк за сваляне на файла
     */
    function act_GenerateLink()
    {
        //Права за работа с екшън-а
        requireRole('user');
        
        // Манипулатора на файла
        $fh = Request::get('fh');
        
        // Очакваме да има подаден манипулатор на файла
        expect($fh, 'Липсва манупулатора на файла');
        
        // Ескейпваме манипулатора
        $fh = $this->db->escape($fh);

        // Записа за съответния файл
        $fRec = $this->Files->fetchByFh($fh);
        
        // Очакваме да има такъв запис
        expect($fRec, 'Няма такъв запис.');
        
        // Проверяваме за права за сваляне на файла
        $this->Files->requireRightFor('download', $fRec);
        
        
        $this->FNC('activeMinutes', 'enum(
    										0.5 = Половин час, 
    										1=1 час,
    										3=3 часа,
    										5=5 часа,
    										12=12 часа,
    										24=1 ден,
    										168=1 седмица
    								 	  )', 'caption=Валидност, mandatory');
        
        
        //URL' то където ще се редиректва при отказ
        $retUrl = getRetUrl();
        $retUrl = ($retUrl) ? ($retUrl) : (array('fileman_Files', 'single', $fh));
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Въвеждаме id-то (и евентуално други silent параметри, ако има)
        $form->input(NULL, 'silent');
        
        // Въвеждаме съдържанието на полетата
        $form->input('activeMinutes');
        
        // Ако формата е изпратена без грешки, показваме линка за сваляне
        if($form->isSubmitted()) {
            
            // Вземаме линка, за да може да се запише новото време до когато е активен линка
            $link = self::getDownloadUrl($fRec->fileHnd, $form->rec->activeMinutes);
            
//            Redirect(array('fileman_Files', 'single', $fh));
            Redirect(array('fileman_Files', 'single', $fh));
        }
        
        // По подразбиране 12 часа да е активен
        $form->setDefault('activeMinutes', 12);
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'activeMinutes';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));

        $fileName = fileman_Files::getVerbal($fRec, 'name');
        
        // Добавяме титлата на формата
        $form->title = tr("Генериране на линк за {$fileName}");
        
        return $this->renderWrapping($form->renderHtml());
    }
}
