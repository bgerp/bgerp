<?php


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
    function getDownloadUrl($fh, $lifeTime = 1)
    {
        // Намираме записа на файла
        $fRec = fileman_Files::fetchByFh($fh);
        
        if(!$fRec) return FALSE;
        
        $time = dt::timestamp2Mysql(time() + $lifeTime * 3600);
        
        //Ако имаме линк към файла, тогава използваме същия линк
        $dRec = $this->fetch("#fileId = '{$fRec->id}'");
        
        if ($dRec) {
            $dRec->expireOn = $time;
            
            $link = sbf(EF_DOWNLOAD_ROOT . '/' . $dRec->prefix . '/' . $dRec->fileName, '', TRUE);
            
            self::save($dRec);
            
            return $link;
        }
        
        // Генерираме името на директорията - префикс
        do {
            $rec->prefix = str::getRand(EF_DOWNLOAD_PREFIX_PTR);
        } while(self::fetch("#prefix = '{$rec->prefix}'"));
        
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
        self::save($rec);
        
        $this->checkFileMime($fRec->name, $rec->prefix);
        
        // Връщаме линка за сваляне
        return sbf(EF_DOWNLOAD_ROOT . '/' . $rec->prefix . '/' . $rec->fileName, '', TRUE);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Download()
    {
        $fh = Request::get('fh');
        
        $fRec = $this->Files->fetchByFh($fh);
        
        $this->Files->requireRightFor('download', $fRec);
        
        redirect($this->getDownloadUrl($fh, 1));
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
    function on_AfterSetupMVC($mvc, &$res)
    {
        if(!is_dir(EF_DOWNLOAD_DIR)) {
            if(!mkdir(EF_DOWNLOAD_DIR, 0777, TRUE)) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') .
                ' "' . EF_DOWNLOAD_DIR . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' .
                EF_DOWNLOAD_DIR . '"</font';
            }
        }
        
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec->systemId = 'ClearOldLinks';
        $rec->description = 'Изчиства старите линкове за сваляне';
        $rec->controller = $this->className;
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
     * Връща html <а> линк за сваляне на файла
     */
    function getDownloadLink($fh, $type = 'relative')
    {   
        // Намираме записа на файла
        $fRec = fileman_Files::fetchByFh($fh);
        
        if(!$fRec) return FALSE;
        
        $ext = self::getExt($fRec->name);
        
        $icon = "fileman/icons/{$ext}.png";
        
        if (!is_file(getFullPath($icon))) {
            $icon = "fileman/icons/default.png";
        }
        
        $attr['class'] = 'linkWithIcon';
        $attr['target'] = '_blank';
        $attr['style'] = 'background-image:url(' . sbf($icon, '"', $type == 'absolute') . ');';
        
        if (fileman_Files::haveRightFor('download', $fRec)) {
            //Генерираме връзката
            $link = ht::createLink($fRec->name, toUrl(array('fileman_Download', 'Download', 'fh' => $fh), $type), NULL, $attr);
        } else {
            //Генерираме името с иконата
            $link = "<span class='linkWithIcon'; style=" . $attr['style'] . "> {$fRec->name} </span>";
        }
        
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
     * Връща разширението на файла
     */
    static function getExt($name)
    {
        if(($dotPos = mb_strrpos($name, '.')) !== FALSE) {
            $ext = mb_substr($name, $dotPos + 1);
        } else {
            $ext = '';
        }
        
        return $ext;
    }
    
    
    /**
     * Проверява mime типа на файла. Ако е text/html добавя htaccess файл, който посочва charset'а с който да се отвори.
     * Ако не може да се извлече charset, тогава се указва на сървъра да не изпраща default charset' а си.
     * Ако е text/'различно от html' тогава добавя htaccess файл, който форсира свалянато на файла при отварянето му.
     */
    function checkFileMime($fileName, $prefix)
    {
        $folderPath = EF_DOWNLOAD_DIR . '/' . $prefix;
        $filePath = $folderPath . '/' . $fileName;
        
        $ext = $this->getExt($fileName);
        
        if (strlen($ext)) {
            include(dirname(__FILE__) . '/data/mimes.inc.php');
            
            $mime = strtolower($mimetypes["{$ext}"]);
            
            $mimeExplode = explode('/', $mime);
            
            if ($mimeExplode[0] == 'text') {
                if ($mimeExplode[1] == 'html') {
                    $charset = $this->findCharset($filePath);
                    
                    if ($charset) {
                        $str = "AddDefaultCharset {$charset}";
                    } else {
                        $str = "AddDefaultCharset Off";
                    }
                } else {
                    $str = "AddType application/octet-stream .{$ext}";
                }
                $this->addHtaccessFile($folderPath, $str);
            }
        }
    }
    
    
    /**
     * Намира charset'а на файла
     */
    function findCharset($file)
    {
        $content = file_get_contents($file);
        
        $pattern = '/<meta[^>]+charset\s*=\s*[\'\"]?(.*?)[[\'\"]]?[\/\s>]/i';
        
        preg_match($pattern, $content, $match);
        
        if ($match[1]) {
            $charset = strtoupper($match[1]);
        }
        
        return $charset;
    }
    
    
    /**
     * Създава .htaccess файл в директорията
     */
    function addHtaccessFile($path, $str)
    {
        $file = $path . '/' . '.htaccess';
        
        $fh = @fopen($file, 'w');
        fwrite($fh, $str);
        fclose($fh);
    }
}