<?php


/**
 * Клас 'core_Os' - Стартиране на процеси на OS
 *
 * PHP versions 4 and 5
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Os
{


    const STATUS_ERROR_CREATE = 1;

    const STATUS_OK_CREATED = 2;

    const STATUS_ALREADY_EXISTS = 3;

    const STATUS_ERROR_CHMOD = 4;


    /**
     * Връща TRUE ако операционната система е Windows
     */
    public static function isWindows()
    {
        return stristr(PHP_OS, 'WIN');
    }
    
    
    /**
     * Връща съобщенията за грешки, генерирани от съответния процес
     */
    public function getErrors($pid)
    {
        $uniqId = substr($pid, strpos($pid, '_') + 1);
        $fName = $this->getErrorFile($uniqId);
        
        if (file_exists($fName)) {
            if (@filesize($fName)) {
                $errorMsg = file_get_contents($fName);
                
                // Премахва изходящия файл. Дали така трябва?
                unlink($this->getTempFile($uniqId));
            }
            unlink($fName);
            
            return $errorMsg;
        }
    }
    
    
    /**
     * Връща уникален глобален идентификатор
     */
    public static function getUniqId($base = 'id')
    {
        static $i, $uniqId;
        
        if (!$uniqId) {
            $uniqId = uniqid($base);
        }
        $i++;
        
        return $uniqId . '_' . $i;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function getTempFile($uniqId)
    {
        return EF_TEMP_PATH . '\\' . $uniqId . '.out';
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function getErrorFile($uniqId)
    {
        return EF_TEMP_PATH . '\\' . $uniqId . '.err';
    }
    
    
    /**
     * Изтрива директория
     * Връща false при неуспех
     */
    public static function deleteDir($dir)
    {
        expect($dir && (strlen($dir) > 1));
        foreach (glob(rtrim($dir, '/') . '/*') as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                @unlink($file);
            }
        }
        
        return @rmdir($dir);
    }
    
    
    /**
     * Изтрива файловете в посочената директория и нейните под-директории,
     * които не са прочитани в последните скудни указани от $maxAge
     *
     * @param string $dir
     * @param int    $maxAge
     *
     * @return int - Броя на изтритите файлове
     */
    public static function deleteOldFiles($dir, $maxAge = 86400, $negativePattern = null, $positivePattern = null)
    {
        $allFiles = self::listFiles($dir);
        
        $delCnt = 0;
        if (is_array($allFiles['files'])) {
            foreach ($allFiles['files'] as $fPath) {
                if (file_exists($fPath)) {
                    if ((time() - @fileatime($fPath) > $maxAge) && str::matchPatterns($fPath, $negativePattern, $positivePattern)) {
                        if (@unlink($fPath)) {
                            $delCnt++;
                        }
                    }
                }
            }
        }
        
        return $delCnt;
    }
    
    
    /**
     * Изтрива всички файлове от EF_TEMP_PATH по крон
     */
    public static function cron_clearOldFiles()
    {
        // Конфигурацията на пакета core
        $conf = core_Packs::getConfig('core');
        
        // Резултат във вербален вид
        $resText = '';
        
        // Брояч за изтриванията
        $delCnt = 0;
        
        // Изтриваме всички, файлове, кото са по стари от дадено време в директорията за временни файлове
        if (defined('EF_TEMP_PATH')) {
            if (!is_dir(EF_TEMP_PATH)) {
                mkdir(EF_TEMP_PATH);
            }
            $delCnt = self::deleteOldFiles(EF_TEMP_PATH, $conf->CORE_TEMP_PATH_MAX_AGE);
            if ($delCnt > 0) {
                $resText .= ($resText ? "\n" : '') . ($delCnt > 1 ? 'Бяха изтрити' : 'Беше изтрит') . " {$delCnt} " . ($delCnt > 1 ? 'файла' : 'файл') . ' от ' . EF_TEMP_PATH;
            }
        }
        
        // Изтриваме всички стари файлове в поддиректории на sbf които не започват със символа '_'
        if (defined('EF_SBF_PATH')) {
            if ($handle = opendir(EF_SBF_PATH)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != '.' && $entry != '..' && false === strpos($entry, '_') && is_dir(EF_SBF_PATH . "/{$entry}")) {
                        $delCnt = self::deleteOldFiles(EF_SBF_PATH . "/{$entry}", $conf->CORE_TEMP_PATH_MAX_AGE);
                    }
                }
                closedir($handle);
            }
            if ($delCnt > 0) {
                $resText .= ($resText ? "\n" : '') . ($delCnt > 1 ? 'Бяха изтрити' : 'Беше изтрит') . " {$delCnt} " . ($delCnt > 1 ? 'файла' : 'файл') . ' от ' . EF_SBF_PATH;
            }
        }
        
        return $resText;
    }
    
    
    /**
     * Връща масив със всички поддиректории и файлове от посочената начална директория
     *
     * array(
     * 'files' => [],
     * 'dirs'  => [],
     * )
     *
     * @param string $root
     * @result array
     */
    public static function listFiles($root)
    {
        $files = array('files' => array(), 'dirs' => array());
        $directories = array();
        $last_letter = $root[strlen($root) - 1];
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;        //?
        $directories[] = $root;
        
        while (sizeof($directories)) {
            $dir = array_pop($directories);
            
            if ($handle = @opendir($dir)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $file = $dir . $file;
                    
                    if (is_dir($file)) {
                        $directory_path = $file . DIRECTORY_SEPARATOR;
                        array_push($directories, $directory_path);
                        $files['dirs'][] = $directory_path;
                    } elseif (is_file($file)) {
                        $files['files'][] = $file;
                    }
                }
                @closedir($handle);
            }
        }
        
        return $files;
    }
    
    
    /**
     * Връща времето на последната промяна на файл в директорията
     *
     * @param string $dir - Директорията
     *
     * @return int - Времето на последната промяна
     */
    public static function getTimeOfLastModifiedFile($dir, $negativePattern = null, $positivePattern = null)
    {
        // Всички файлове
        $files = scandir($dir);
        
        // Запазваме в променлива, за да не вземаме 2 пъти за една и съща директория
        static $lastModificationDir = array();
        
        // Ако вече сме гледали в директория
        if (!$lastModificationDir[$dir]) {
            
            // Обхождаме файловете
            foreach ($files as $file) {
                
                // Прескачаме ги
                if ($file == '.' || $file == '..' || !str::matchPatterns($file, $negativePattern, $positivePattern)) {
                    continue;
                }
                
                // Вземаме времето на промяна на последния файл
                $time = filemtime($dir . DIRECTORY_SEPARATOR . $file);
                
                // Ако времето е по - голямо от записаното в директорията
                if ($time > $lastModificationDir[$dir]) {
                    
                    // Записваме времето на последната промяна
                    $lastModificationDir[$dir] = $time;
                }
            }
        }
        
        return $lastModificationDir[$dir];
    }
    
    
    /**
     * Функция, която връща резултата от изпълнението на посленидния preg
     * В preg_ фунцкиите, ако възникне грешка връщат NULL
     */
    public static function pregLastError()
    {
        $pregLastError = preg_last_error();
        
        if ($pregLastError == PREG_NO_ERROR) {
            $res = 'There is no error.';
        } elseif ($pregLastError == PREG_INTERNAL_ERROR) {
            $res = 'There is an internal error!';
        } elseif ($pregLastError == PREG_BACKTRACK_LIMIT_ERROR) {
            $res = 'Backtrack limit was exhausted!';
        } elseif ($pregLastError == PREG_RECURSION_LIMIT_ERROR) {
            $res = 'Recursion limit was exhausted!';
        } elseif ($pregLastError == PREG_BAD_UTF8_ERROR) {
            $res = 'Bad UTF8 error!';
        } elseif ($pregLastError == PREG_BAD_UTF8_ERROR) {
            $res = 'Bad UTF8 offset error!';
        } else {
            $res = 'Unrecognized error!';
        }
        
        return $res;
    }
    
    
    /**
     * Връща броя на стартираните процеси на Apache
     */
    public function countApacheProc()
    {
        if ($this->isWindows()) {
            $proc = 'httpd.exe';
        } else {
            $proc = 'apache';
        }
        
        return $this->countProc($proc);
    }
    
    
    /**
     * Връща път към изпълним php
     */
    public static function getPhpCmd()
    {
        $paths = array(defined('EF_PHP_PATH') ? EF_PHP_PATH : false,
            defined('PHP_PATH') ? PHP_PATH : false,
            getenv('PHP_PATH'),
            defined('PHP_BINARY') ? PHP_BINARY : false);
        
        foreach ($paths as $p) {
            if ($p && file_exists($p) && is_executable($p) && strpos(basename($p), 'php') !== false) {
                
                return $p;
            }
        }
        
        if (self::isWindows()) {
            $cmd = 'where php';
        } else {
            $cmd = 'which php';
        }
        
        exec($cmd, $lines, $returnVal);
        
        $php = false;
        
        if ($returnVal == 0 && strlen($lines[0]) && is_executable($lines[0])) {
            $php = $lines[0];
        }
        
        return $php;
    }
    
    
    /**
     * Връща броя на стартираните процеси на Apache
     */
    public function countProc($proc)
    {
        $processes = 0;
        
        if ($this->isWindows()) {
            $output = shell_exec('tasklist');
            $lines = explode("\n", $output);
            foreach ($lines as $l) {
                if (strpos($l, $proc) !== false) {
                    $processes++;
                }
            }
        } else {
            $processes = exec("ps -A | grep {$proc} | wc -l");
        }
        
        return $processes;
    }
    
    
    /**
     * Връща информация колко памет е заета.
     * За сега работи само под Linux
     */
    public function getMemoryUsage()
    {
        if (!$this->isWindows()) {
            $mem = $this->getFreeRes();
            $memory_usage = $mem[2] / $mem[1] * 100;
        }
        
        return $memory_usage;
    }
    
    
    /**
     * Връща информация с колко памет разполага ОС
     * За сега работи само под Linux
     *
     * @return int|NULL
     */
    public static function getMemoryLimit()
    {
        $memoryLimit = null;
        if (!self::isWindows()) {
            $mem = self::getFreeRes();
            $memoryLimit = $mem[1];
        }
        
        return $memoryLimit;
    }
    
    
    /**
     * Връща информация с колко памет разполага ОС
     * За сега работи само под Linux
     *
     * @return int|NULL
     */
    public static function getFreeMemory()
    {
        $memoryLimit = null;
        if (!self::isWindows()) {
            $mem = self::getFreeRes();
            $memoryLimit = $mem[3];
        }
        
        return $memoryLimit;
    }
    
    
    /**
     * Помощна функция за вземане на стойностите на паметта
     * За сега работи само под Linux
     *
     * @return array
     */
    protected static function getFreeRes()
    {
        $mem = array();
        if (!self::isWindows()) {
            $free = shell_exec('free');
            $free = (string) trim($free);
            $freeArr = explode("\n", $free);
            $mem = explode(' ', $freeArr[1]);
            $mem = array_filter($mem);
            $mem = array_merge($mem);
        }
        
        return $mem;
    }
    
    
    /**
     * Връща информация за диска в който се намира подадения път
     * За сега работи само под Linux
     *
     * @param string $path
     * @param bool   $percent
     *
     * @return string|NULL
     */
    public static function getFreePathSpace($path, $percent = false)
    {
        $pathSpaceArr = self::getPathSpace($path);
        
        if ($percent) {
            
            return $pathSpaceArr[4];
        }
        
        return $pathSpaceArr[3];
    }
    
    
    /**
     * Връща информация за диска в който се намира подадения път
     * За сега работи само под Linux
     *
     * @return array
     */
    protected static function getPathSpace($path)
    {
        if (!self::isWindows()) {
            $df = shell_exec('df ' . escapeshellarg($path));
            $df = (string) trim($df);
            $dfArr = explode("\n", $df);
            $pathSpaceArr = explode(' ', $dfArr[1]);
            $pathSpaceArr = array_filter($pathSpaceArr);
            $pathSpaceArr = array_merge($pathSpaceArr);
        }
        
        return $pathSpaceArr;
    }
    
    
    /**
     * Създава директория, ако тя не съществува
     */
    public static function forceDir($path, $permissions = 0754, $recursive = true, &$status = null)
    {
        if (!is_dir($path)) {
            
            // Създаваме директория
            if (!@mkdir($path, $permissions, $recursive)) {
                $status = core_Os::STATUS_ERROR_CREATE;
                
                return false;
            }
            $status = self::STATUS_OK_CREATED;
        } else {
            if((fileperms($path) & 0777) != $permissions) {
                if(!@chmod($path, $permissions)) {
                    $status = self::STATUS_ERROR_CHMOD;

                    return false;
                }
            }
            $status = self::STATUS_ALREADY_EXISTS;
        }

        return true;
    }
    
    
    /**
     * Съдава пътищата посочени във входния аргумент
     *
     * return string
     */
    public static function createDirectories($directories, $permissions = 0754, $recursive = true)
    {   
        // Резултат
        $res = '';

        // Създава, ако е необходимо зададените папки
        foreach (arr::make($directories) as $path => $caption) {
            if (is_numeric($path)) {
                $path = $caption;
                $caption = '';
            }
            
            $status = '';
            if(self::forceDir($path, $permissions, $recursive, $status)) {
                if($status == self::STATUS_OK_CREATED) {
                    $res .= "<li class='debug-new'>Създадена е директорията <b>{$path}</b> {$caption}</li>";
                } elseif($status == self::STATUS_ALREADY_EXISTS) {
                    $res .= "<li class='debug-info'>Съществуваща директория <b>{$path}</b> {$caption}</li>";
                }
            } else {
                if($status == self::STATUS_ERROR_CREATE) {
                    $res .= "<li class='debug-error'>Не може да се създаде директорията <b>{$path}</b> {$caption}</li>";
                } elseif($status == self::STATUS_ERROR_CHMOD) {
                    $perm = '0' . decoct(fileperms($path) & 0777) . ' => ' . '0' . decoct($permissions);
                    $res .= "<li class='debug-error'>Не може да се зададат правата {$perm} за директорията <b>{$path}</b> {$caption}</li>";
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Създава директория, ако тя не съществува. Ако не успее, хвърля грешка
     */
    public static function requireDir($path, $permissions = 0754, $recursive = true)
    {
        expect(self::forceDir($path, $permissions, $recursive), "Не може да се създаде директорията `{$path}`");
        expect(is_writable($path), "Не може да се записва в директорията `{$path}`");
    }
    
    
    /**
     *
     *
     * @param resource $file
     * @param float   $limit
     * @param string  $trim
     *
     * @return array
     */
    public static function getLastLinesFromFile($file, $limit = 0, $trim = true, &$errStr = '')
    {
        $linesArr = array();
        
        if (!is_file($file)) {
            $errStr = 'Не е подаден валиден файл';
            
            return $linesArr;
        }
        
        $fp = @fopen($file, 'r');
        
        if (!$fp) {
            $errStr = 'Не може да се отвори файла';
            
            return $linesArr;
        }
        
        $pos = 0;
        $cnt = 0;
        $fs = 0;
        $linesArr = array();
        while ($fs != -1) {
            $fs = fseek($fp, $pos, SEEK_END);
            $t = fgetc($fp);
            --$pos;
            if ($t == "\n") {
                $line = fgets($fp);
                if (($line !== false) && (!$trim || trim($line))) {
                    $cnt++;
                    $linesArr[] = $line;
                }
            }
            
            if ($cnt >= $limit) {
                break;
            }
        }
        
        if (!@fclose($fp)) {
            wp($fp);
        }
        
        return $linesArr;
    }
    
    
    /**
     * Връща размера на memory_limit в байтове
     *
     * @return int
     */
    public static function getBytesFromMemoryLimit($memoryLimit = null)
    {
        if (!isset($memoryLimit)) {
            $memoryLimit = ini_get('memory_limit');
        }
        
        return self::getBytes($memoryLimit);
    }
    
    
    /**
     * Converts shorthand memory notation value to bytes
     * From http://php.net/manual/en/function.ini-get.php
     *
     * @param $val Memory size shorthand notation string
     */
    public static function getBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
                
                // no break
            case 'm':
                $val *= 1024;
                
                // no break
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }


    /**
     * Изтриване на директория
     */
    public static function deleteDirectory($dir, $onlyEmpty = false)
    {
        if (!file_exists($dir)) {
            
            return true;
        }
        
        if (!is_dir($dir)) {
            
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!self::deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                
                return false;
            }
        }

        if(!$onlyEmpty) {
            $res = rmdir($dir);
        } else {
            $res = true;
        }

        return $res;
    }
    
    
    /**
     * Проверява дали в директорията е празна
     */
    public static function isDirEmpty($dir)
    {
        if (!is_readable($dir)) {
            
            return false;
        }
        
        return (countR(scandir($dir)) <= 2);
    }
    
    
    /**
     * Нормализиране на път до директория
     */
    public static function normalizeDir($dir)
    {
        return rtrim(str_replace('\\', '/', $dir), ' /');
    }


    /**
     * Прави проверка на даден път
     */
    public static function hasDirErrors($dir, $title)
    {
        if (empty($dir)) {

            return "Директорията {$title} не е определена * ";
        }

        if (!is_dir($dir)) {
                    
            return "Директорията `{$dir}` не е директория * ";
        }

        if (!is_readable($dir)) {
                    
            return "Директорията `{$dir}` не е четима * ";
        }
        if (!is_writable($dir)) {
                    
            return "Директорията `{$dir}` не е записваема * ";
        }
    }
}
