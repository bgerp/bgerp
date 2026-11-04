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
 * @copyright 2006 - 2026 Experta EOOD
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
     * @param boolean $isAbsolute
     *
     * @return bool|string - Линк към файла
     */
    public static function getDownloadUrl($src, $lifeTime = 1, $type = 'handler', $isAbsolute = true)
    {
        // Очакваме типа да е един от дадените
        expect(in_array($type, array('url', 'path', 'handler')));
        
        // Ако е подаден празен стринг
        if (!trim($src)) {
            
            return false;
        }

        $res = null;
        $mvc = cls::get(get_called_class());
        if ($mvc->invoke('BeforeGetDownloadUrl', array(&$res, $src, $lifeTime, $type, $isAbsolute)) === false) {
            return $res;
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
            if (@file_exists(EF_DOWNLOAD_DIR . '/' . $dRec->prefix . '/' . $dRec->fileName)) {
                // Ако времето, за което е активен линка е по малко от времето, което искаме да зададем
                if ($dRec->expireOn < $time) {

                    // Променяме времето
                    $dRec->expireOn = $time;
                }

                // Вземаме URL
                $link = static::getSbfDownloadUrl($dRec, $isAbsolute);

                // Записваме
                static::save($dRec);

                // Връщаме URL' то
                return $link;
            }
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

        // Създаваме хард-линк или копираме
        self::copyFile($originalPath, $rec->prefix, $rec->fileName);

        // Задаваме id-то на файла
        $rec->fileId = $fileId;
        
        // Задаваме времето, в което изтича възможността за сваляне
        $rec->expireOn = $time;
        
        // Записваме информацията за свалянето, за да можем по-късно по Cron да
        // премахнем линка за сваляне
        static::save($rec);
        
        // Връщаме линка за сваляне
        return static::getSbfDownloadUrl($rec, $isAbsolute);
    }


    /**
     * Помощна функция за копиране на файловете
     * Ако файлът не съществува в `white-list` тогава ще се архивира и ще се сервира архива
     *
     * @param string$originalPath
     * @param string $downloadPath
     *
     * @return bool
     */
    protected static function copyFile($originalPath, $prefix, &$fileName, $aExt = 'zip')
    {
        // Генерираме пътя до файла (hard link) който ще се сваля
        $downloadPath = EF_DOWNLOAD_DIR . '/' . $prefix . '/' . $fileName;

        include_once(getFullPath('fileman/data/white-list-extensions.inc.php'));

        $ext = fileman::getExt($fileName);

        if (!$ext || !in_array($ext, WHITE_LIST_EXTENSIONS)) {

            $tempPath = fileman::getTempPath();
            $tFile = $tempPath . '/' . $fileName;

            try {
                @copy($originalPath, $tFile);

                $fileName .= '.' . $aExt;
                $downloadPath = $downloadPath . '.' . $aExt;

                archive_Adapter::compressFile($tFile, $downloadPath);
            } catch (core_exception_Redirect $e) {
                throw $e;
            } catch (Exception $e) {
                reportException($e);
                expect(false);
            } catch (Throwable $t) {
                reportException($t);
                expect(false);
            } finally {
                fileman::deleteTempPath($tFile);
                @unlink($tFile);
            }
        } else {
            if (!@copy($originalPath, $downloadPath)) {
                error('@Не може да бъде копиран файла', $originalPath, $downloadPath);
            }
        }
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

        fileman::logRead('Сваляне', $fRec->id);

        if (Request::get('forceDownload')) {
            redirect($link);
        } else {
            
            // Редиректваме към линка
            return new Redirect($link);
        }
    }


    /**
     * @todo Чака за документация...
     */
    public function act_Serve()
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

        // Очакваме да има такъв запис
        expect($fRec, 'Няма такъв запис.');

        fileman::updateLastUse($fRec);

        if (ob_get_level()) {
            ob_end_clean();
        }

        $headers = array(
            'Content-Type' => fileman::getType($fRec->name ?? ''),
            'Content-Length' => $fRec->fileLen ?? 0,
            'Content-Disposition' => 'inline; filename="' . addslashes($fRec->name ?? '') . '"',
            'Cache-Control' => 'public, max-age=3600',
            'Pragma' => 'public',
        );
        $this->invoke('BeforeServeFile', array($fRec, &$headers));
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        readfile(fileman::extract($fRec->fileHnd ?? null));

        fileman::logRead('Сваляне', $fRec->id ?? null);

        shutdown();
    }


    /**
     * Изтрива линковете, които не се използват и файловете им
     */
    public function clearOldLinks()
    {
        $now = dt::timestamp2Mysql(time());
        $query = self::getQuery();
        $query->where("#expireOn < '{$now}'");
        
        $htmlRes = '<hr />';
        
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
        $rec->isRandOffset = true;
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
     * Отменя публичните копия на файл, без да премахва оригинала или други файлове.
     *
     * @param int|string $fileId Идентификатор от fileman_Files или MD5 за източник по път
     * @return int Брой премахнати записи за публични адреси
     */
    public static function revokePublicFile($fileId)
    {
        expect(is_int($fileId) || is_string($fileId), 'Невалиден идентификатор на файл');
        $fileId = (string) $fileId;
        expect(preg_match('/^(?:[1-9][0-9]*|[a-f0-9]{32})$/D', $fileId), 'Невалиден идентификатор на файл');
        $query = static::getQuery();
        $query->where(array("#fileId = '[#1#]'", $fileId));
        $query->show('id,prefix,fileName');
        $count = 0;
        while ($rec = $query->fetch()) {
            $path = self::getPublicCopyPath($rec);
            if ($path !== null && is_dir($dir = dirname($path))) {
                if (file_exists($path) || is_link($path)) {
                    expect((is_file($path) || is_link($path)) && @unlink($path), 'Неуспешно премахване на публичния файл');
                }
                $entries = scandir($dir);
                expect($entries !== false, 'Недостъпна директория за сваляне');
                if (count($entries) === 2) {
                    expect(@rmdir($dir), 'Неуспешно премахване на директорията за сваляне');
                }
            }
            $id = (int) ($rec->id ?? 0);
            expect($id > 0, 'Невалиден запис за сваляне');
            $count += static::delete(array("#id = '[#1#]'", $id));
        }

        return $count;
    }


    /**
     * Отменя и старите копия, публикувани от друг временен път към същото съдържание.
     *
     * @param string $sourcePath Каноничен изходен файл извън публичната директория
     * @return int Брой премахнати записи за публични адреси
     */
    public static function revokePublicContent($sourcePath)
    {
        expect(is_string($sourcePath) && is_file($sourcePath), 'Невалиден изходен файл');
        $sourcePath = realpath($sourcePath);
        $root = realpath(EF_DOWNLOAD_DIR);
        expect($sourcePath !== false && ($root === false || strpos($sourcePath . '/', $root . '/') !== 0), 'Невалиден изходен файл');
        $size = filesize($sourcePath);
        $hash = md5_file($sourcePath);
        expect($size !== false && $hash !== false, 'Недостъпен изходен файл');
        $query = static::getQuery();
        $query->show('fileId,prefix,fileName');
        $matches = array();
        while ($rec = $query->fetch()) {
            $path = self::getPublicCopyPath($rec);
            if ($path === null || (!file_exists($path) && !is_link($path))) continue;
            expect(!is_link($path) && is_file($path), 'Невалиден публичен файл');
            $copySize = filesize($path);
            expect($copySize !== false, 'Недостъпен публичен файл');
            if ($copySize !== $size) continue;
            $copyHash = md5_file($path);
            expect($copyHash !== false, 'Недостъпен публичен файл');
            if ($copyHash === $hash) $matches[$rec->fileId ?? ''] = $rec->fileId ?? '';
        }
        $count = 0;
        foreach ($matches as $fileId) $count += static::revokePublicFile($fileId);

        return $count;
    }


    /**
     * Проверява пътя само в директорията с генерирани публични копия.
     *
     * @param stdClass $rec
     * @return string|null null при липсваща публична директория
     */
    private static function getPublicCopyPath($rec)
    {
        $prefix = $rec->prefix ?? '';
        $name = $rec->fileName ?? '';
        expect(is_string($prefix) && preg_match('/^[a-zA-Z0-9]+$/D', $prefix)
            && strlen($prefix) === strlen(EF_DOWNLOAD_PREFIX_PTR), 'Невалиден адрес за сваляне');
        expect(is_string($name) && $name !== '' && $name !== '.' && $name !== '..'
            && strpbrk($name, "/\\\0") === false, 'Невалидно име на публичен файл');
        $root = realpath(EF_DOWNLOAD_DIR);
        if ($root === false) {
            expect(!file_exists(EF_DOWNLOAD_DIR) && !is_link(EF_DOWNLOAD_DIR), 'Недостъпна директория за сваляне');

            return null;
        }
        expect(is_dir($root), 'Невалидна директория за сваляне');
        $dir = $root . '/' . $prefix;
        expect(!is_link($dir), 'Невалидна директория за сваляне');
        if (file_exists($dir)) expect(is_dir($dir) && realpath($dir) === $dir, 'Невалидна директория за сваляне');

        return $dir . '/' . $name;
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
