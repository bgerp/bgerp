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
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Download extends core_Manager
{
    /**
     * @todo Чака за документация...
     */
    public $pathLen = 6;
    
    
    /**
     * Заглавие на модула
     */
    public $title = 'Сваляния';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, debug';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // Файлов манипулатор - уникален 8 символно/цифров низ, започващ с буква.
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD('fileName', 'varchar(255)', 'notNull,caption=Име');
        
        $this->FLD(
            
            'prefix',
            
            'varchar(' . strlen(EF_DOWNLOAD_PREFIX_PTR) . ')',
            array('notNull' => true, 'caption' => 'Префикс')
        
        );
        
        // Име на файла
        $this->FLD(
            'fileId',
            'varchar(32)',
            array('notNull' => true, 'caption' => 'Файл')
        );
        
        // Крайно време за сваляне
        $this->FLD(
            'expireOn',
            'datetime',
            array('caption' => 'Активен до')
        );
        
        // Плъгини за контрол на записа и модифицирането
        $this->load('plg_Created,Files=fileman_Files,fileman_Wrapper,Buckets=fileman_Buckets');
        
        // Индекси
        $this->setDbUnique('prefix');
    }
    
    
    /**
     * Връща URL за сваляне на файла с валидност publicTime часа
     *
     * @param string $src      - Манипулатор на файл, път до файл или URL
     * @param int    $lifeTime - Колко време да се пази линка (в часове)
     * @param string $type     -  - Типа на сорса - handler, url, path
     *
     * @return bool|string - Линк към файла
     */
    public static function getDownloadUrl($src, $lifeTime = 1, $type = 'handler')
    {
        // Очакваме типа да е един от дадените
        expect(in_array($type, array('url', 'path', 'handler')));
        
        // Ако е подаден празен стринг
        if (!trim($src)) {
            
            return false;
        }
        
        // Ако типа е URL
        if ($type == 'url') {
            
            // Връщаме сорса
            return $src;
        } elseif ($type == 'handler') {
            // Ако е манипулато на файл
            
            // Намираме записа на файла
            $fRec = fileman_Files::fetchByFh($src);
            
            // Ако няма запис връщаме
            if (!$fRec) {
                
                return false;
            }
            
            // Името на файла
            $name = $fRec->name;
            
            // id' то на файла
            $fileId = $fRec->id;
            
            // Пътя до файла
            $originalPath = fileman_Files::fetchByFh($fRec->fileHnd, 'path');
        } else {
            // Ако е път до файл
            
            // Ако не е подаден целия път до файла
            if (!is_file($src)) {
                
                // Пътя до файла
                $originalPath = getFullPath($src);
            } else {
                
                // Целия път до файла
                $originalPath = $src;
            }
            
            // Ако не е файл
            if (!is_file($originalPath)) {
                
                return false;
            }
            
            // Времето на последна модификация на файла
            $fileTime = filemtime($originalPath);
            
            // id' то на файла - md5 на пътя и времето
            $fileId = md5($originalPath . $fileTime);
            
            // Името на файла
            $name = basename($originalPath);
        }
        
        // Генерираме времето на изтриване
        $time = dt::timestamp2Mysql(time() + $lifeTime * 3600);
        
        // Записите за файла
        $dRec = static::fetch("#fileId = '{$fileId}'");
        
        // Ако имаме линк към файла, тогава използваме същия линк
        if ($dRec) {
            
            // Ако времето, за което е активен линка е по малко от времето, което искаме да зададем
            if ($dRec->expireOn < $time) {
                
                // Променяме времето
                $dRec->expireOn = $time;
            }
            
            // Вземаме URL
            $link = static::getSbfDownloadUrl($dRec, true);
            
            // Записваме
            static::save($dRec);
            
            // Връщаме URL' то
            return $link;
        }
        
        // Обект
        $rec = new stdClass();
        
        // Генерираме името на директорията - префикс
        // Докато не се генерира уникално име в модела
        do {
            $rec->prefix = str::getRand(EF_DOWNLOAD_PREFIX_PTR);
        } while (static::fetch("#prefix = '{$rec->prefix}'"));
        
        // Задаваме името на файла за сваляне - същото, каквото файла има в момента
        $rec->fileName = $name;
        
        // Проверяваме или създаваме директорията
        core_Os::requireDir(EF_DOWNLOAD_DIR . '/' . $rec->prefix);
        
        // Генерираме пътя до файла (hard link) който ще се сваля
        $downloadPath = EF_DOWNLOAD_DIR . '/' . $rec->prefix . '/' . $rec->fileName;
        
        // Създаваме хард-линк или копираме
        if (!@copy($originalPath, $downloadPath)) {
            error('@Не може да бъде копиран файла', $originalPath, $downloadPath);
        }
        
        // Задаваме id-то на файла
        $rec->fileId = $fileId;
        
        // Задаваме времето, в което изтича възможността за сваляне
        $rec->expireOn = $time;
        
        // Записваме информацията за свалянето, за да можем по-късно по Cron да
        // премахнем линка за сваляне
        static::save($rec);
        
        // Връщаме линка за сваляне
        return static::getSbfDownloadUrl($rec, true);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function act_Download()
    {
        // Ако файла се сваля от vt - за да не се подават вирусни файлове
        if (log_Browsers::checkUserAgent('virustotalcloud')) {
            
            return new Redirect(array('Index'));
        }
        
        // Манипулатора на файла
        $fh = Request::get('fh');
        
        // Очакваме да има подаден манипулатор
        expect($fh, 'Липсва манупулатора на файла');
        
        // Ескейпваме манупулатора
        $fh = $this->db->escape($fh);
        
        // Вземаме записа на манипулатора
        $fRec = $this->Files->fetchByFh($fh);
        
        fileman::updateLastUse($fRec);
        
        // Очакваме да има такъв запис
        expect($fRec, 'Няма такъв запис.');
        
        // TODO не е необходимо да има права за сваляне ?
        // Очакваме да има права за сваляне
//        $this->Files->requireRightFor('download', $fRec);
        
        // Генерираме линк за сваляне
        $link = $this->getDownloadUrl($fh, 1);
        
        // Ако искам да форсираме свалянето
//        if (Request::get('forceDownload')) {
//
//            // Големина на файла
//            $fileLen = fileman_Data::fetchField($fRec->dataId, 'fileLen');
//
//            // 1024*1024
//            $chunksize = 1048576;
//
//            // Големината на файловете, над която ще се игнорира forceDownload
//            $chunksizeOb = 30 * $chunksize;
//
//            // Ако файла е по - малък от $chunksizeOb
//            if ($fileLen < $chunksizeOb) {
//
//                // Задаваме хедърите
//                header('Content-Description: File Transfer');
//                header('Content-Type: application/octet-stream');
//                header('Content-Disposition: attachment; filename='.basename($link));
//                header('Content-Transfer-Encoding: binary');
//                header('Expires: 0');
//                header('Cache-Control: must-revalidate');
//                header('Content-Length: ' . $fileLen);
//                header("Connection: close");
//
        ////                header('Pragma: public'); //TODO Нужен е когато се използва SSL връзка в браузъри на IE <= 8 версия
        ////                header("Pragma: "); // TODO ако има проблеми с някои версии на IE
        ////                header("Cache-Control: "); // TODO ако има проблеми с някои версии на IE
//
//                // Ако е файла по - малък от 1 MB
//                if ($fileLen < $chunksize) {
//
//                    // Предизвикваме сваляне на файла
//                    readfile($link);
//                } else {
//
//                    // Стартираме нов буфер
//                    ob_start();
//
//                    // Вземаме манипулатора на файла
//                    $handle = fopen($link, 'rb');
//                    $buffer = '';
//
//                    // Докато стигнем края на файла
//                    while (!feof($handle)) {
//
//                        // Вземаме част от файла
//                        $buffer = fread($handle, $chunksize);
//
//                        // Показваме го на екрана
//                        echo $buffer;
//
//                        // Изчистваме буфера
//                        ob_flush();
//                        flush();
//                    }
//
//                    // Затваряме файла
//                    fclose($handle);
//
//                    // Спираме буфера, който сме стартирали
//                    ob_end_clean();
//                }
//
//                // Прекратяваме изпълнението на скрипта
//                shutdown();
//            }
//        }
        
        if (Request::get('forceDownload')) {
            redirect($link);
        } else {
            
            // Редиректваме към линка
            return new Redirect($link);
        }
    }
    
    
    /**
     * Изтрива линковете, които не се използват и файловете им
     */
    public function clearOldLinks()
    {
        $now = dt::timestamp2Mysql(time());
        $query = self::getQuery();
        $query->where("#expireOn < '{$now}'");
        
        $htmlRes .= '<hr />';
        
        $count = $query->count();
        
        if (!$count) {
            $htmlRes .= "\n<li style='color:green'> Няма записи за изтриване.</li>";
        } else {
            $htmlRes .= "\n<li'> {$count} записа за изтриване.</li>";
        }
        
        while ($rec = $query->fetch()) {
            $htmlRes .= '<hr />';
            
            $dir = static::getDownloadDir($rec);
            
            if (self::delete("#id = '{$rec->id}'")) {
                $htmlRes .= "\n<li> Deleted record #: {$rec->id}</li>";
                
                if (core_Os::deleteDir($dir)) {
                    $htmlRes .= "\n<li> Deleted dir: {$rec->prefix}</li>";
                } else {
                    $htmlRes .= "\n<li style='color:red'> Can' t delete dir: {$rec->prefix}</li>";
                }
            } else {
                $htmlRes .= "\n<li style='color:red'> Can' t delete record #: {$rec->id}</li>";
            }
        }
        
        return $htmlRes;
    }
    
    
    /**
     * Стартиране на процеса за изтриване на ненужните файлове
     */
    public function act_ClearOldLinks()
    {
        $clear = $this->clearOldLinks();
        
        return $clear;
    }
    
    
    /**
     * Стартиране на процеса за изтриване на ненужните файлове по крон
     */
    public function cron_ClearOldLinks()
    {
        $clear = $this->clearOldLinks();
        
        return $clear;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        $res .= core_Os::createDirectories(EF_DOWNLOAD_DIR);
        
        if (CORE_OVERWRITE_HTAACCESS) {
            $filesToCopy = array(
                core_App::getFullPath('fileman/tpl/htaccessDL.txt') => EF_DOWNLOAD_DIR . '/.htaccess',
            );
            
            foreach ($filesToCopy as $src => $dest) {
                if (file_exists($dest) && is_readable($dest)) {
                    if (md5_file($dest) == md5_file($src)) {
                        $res .= "<li>От преди съществуващ файл: <b>{$dest}</b></li>";
                        continue;
                    }
                }
                
                if (copy($src, $dest)) {
                    $res .= "<li class=\"debug-new\">Копиран е файла: <b>{$src}</b> => <b>{$dest}</b></li>";
                } else {
                    $res .= "<li class=\"debug-error\">Не може да бъде копиран файла: <b>{$src}</b> => <b>{$dest}</b></li>";
                }
            }
        }
        
        // Нагласяне на Крон
        $rec = new stdClass();
        $rec->systemId = 'ClearOldLinks';
        $rec->description = 'Изчистване на старите линкове за сваляне';
        $rec->controller = $mvc->className;
        $rec->action = 'ClearOldLinks';
        $rec->period = 100;
        $rec->offset = mt_rand(0, 60);
        $rec->delay = 0;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Връща SBF линк за сваляне на файла
     *
     * @param object $rec      - Записа за файла
     * @param bool   $absolute - Дали линка да е абсолютен или не
     *
     * @return string $link - Текстов линк за сваляне
     */
    public static function getSbfDownloadUrl($rec, $absolute = false)
    {
        // Линка на файла
        $link = sbf(EF_DOWNLOAD_ROOT . '/' . $rec->prefix . '/' . $rec->fileName, '', $absolute);
        
        return $link;
    }
    
    
    /**
     * Връща директорията, в който е записан файла
     *
     * @param fileman_Download $rec - Записа, за който търсим директорията
     */
    public static function getDownloadDir($rec)
    {
        // Очакваме да е обект
        expect(is_object($rec), 'Не сте подали запис');
        
        // Директорията на файла
        $dir = EF_DOWNLOAD_DIR . '/' . $rec->prefix;
        
        return $dir;
    }
    
    
    /**
     * Изтрива подадени файл от sbf директорията и от модела
     *
     * @param fileman_Files $fileId - id' то на записа, който ще изтриваме
     */
    public static function deleteFileFromSbf($fileId)
    {
        // Очакваме да има
        expect($fileId);
        
        // Ако има такъм запис
        if ($rec = static::fetch("#fileId = '{$fileId}'")) {
            
            // Директорията, в която се намира
            $dir = static::getDownloadDir($rec);
            
            // Изтриваме директорията
            core_Os::deleteDir($dir);
            
            // Изтриваме записа от модела
            $deleted = static::delete("#fileId = '{$fileId}'");
        }
    }
}
