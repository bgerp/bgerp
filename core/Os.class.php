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
 * @copyright 2006 - 2026 Experta EOOD
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
        return stripos(PHP_OS, 'WIN') === 0;
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
    public static function getTempFile($uniqId, $ext = 'out')
    {
        
        return EF_TEMP_PATH . '/' . $uniqId . ".{$ext}";
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function getErrorFile($uniqId)
    {
        
        return self::getTempFile($uniqId, 'err');
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
     * Изтрива старите файлове и връзки, без да следва символни връзки.
     * По подразбиране работи във временните и кеш директориите на инсталацията.
     * Премахва и старите празни поддиректории, но запазва началната директория.
     *
     * @param string      $dir
     * @param int         $maxAge          Срок в секунди
     * @param string|null $negativePattern Регулярен израз за изключване на файлове и цели папки
     * @param string|null $positivePattern Регулярен израз за включване
     * @param string|null $allowedRoot     Друга разрешена база от настройките на модула, не от Request
     *
     * @return int - Броя на изтритите файлове
     */
    public static function deleteOldFiles($dir, $maxAge = 86400, $negativePattern = null, $positivePattern = null, $allowedRoot = null)
    {
        $dir = self::getCleanupPath($dir, $allowedRoot);
        if ($dir === false || !is_numeric($maxAge) || !is_finite((float) $maxAge) || $maxAge <= 0) {
            return 0;
        }

        // Грешен филтър не трябва да превръща почистването в неограничено изтриване.
        foreach (array($negativePattern, $positivePattern) as $pattern) {
            if ($pattern !== null && $pattern !== '' && $pattern !== false &&
                (!is_string($pattern) || @preg_match($pattern, '') === false)) {
                return 0;
            }
        }
        // Изключена папка се запазва заедно със съдържанието, преди да я обходим.
        if ($negativePattern && @preg_match($negativePattern, $dir) !== 0) {
            return 0;
        }

        $handle = @opendir($dir);
        if (!$handle) {
            return 0;
        }

        $delCnt = 0;
        $before = time() - $maxAge;
        try {
            while (false !== ($entry = readdir($handle))) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                $path = $dir . DIRECTORY_SEPARATOR . $entry;
                clearstatcache(true, $path);
                $stat = @lstat($path);
                if ($stat === false) {
                    continue;
                }

                // lstat() използва времената на самата връзка, включително при липсваща цел.
                $type = $stat['mode'] & 0170000;
                if ($type === 0040000) {
                    $delCnt += self::deleteOldFiles($path, $maxAge, $negativePattern, $positivePattern, $allowedRoot);

                    // Обхождането променя atime, а изтриването на децата - mtime.
                    $lastUse = $stat['mtime'];
                } elseif ($type === 0100000 || $type === 0120000) {
                    $lastUse = max($stat['atime'], $stat['mtime']);
                } else {
                    continue;
                }

                // При грешка по време на съпоставянето също запазваме файла.
                if ($lastUse >= $before ||
                    ($negativePattern && @preg_match($negativePattern, $path) !== 0) ||
                    ($positivePattern && @preg_match($positivePattern, $path) !== 1)) {
                    continue;
                }

                // Проверяваме отново пътя и записа след обхождането на децата.
                if (self::getCleanupPath($dir, $allowedRoot) !== $dir) {
                    break;
                }
                $current = @lstat($path);
                if ($current === false || $current['dev'] !== $stat['dev'] ||
                    $current['ino'] !== $stat['ino'] || ($current['mode'] & 0170000) !== $type) {
                    continue;
                }
                if ($type === 0040000) {
                    @rmdir($path);
                } elseif (max($current['atime'], $current['mtime']) < $before && @unlink($path)) {
                    $delCnt++;
                }
            }
        } finally {
            closedir($handle);
        }
        
        return $delCnt;
    }


    /**
     * Ограничава почистването до изричната база или стандартните временни и кеш директории.
     *
     * @return string|false
     */
    private static function getCleanupPath($dir, $allowedRoot = null)
    {
        $path = self::normalizeCleanupPath($dir);
        if ($path === false) {
            return false;
        }

        $roots = array($allowedRoot);
        if ($allowedRoot === null) {
            $roots = array();
            foreach (array('EF_TEMP_PATH', 'EF_SBF_PATH', 'FILEMAN_TEMP_PATH', 'FCONV_TEMP_PATH', 'THUMB_IMG_PATH') as $name) {
                if (defined($name)) {
                    $roots[] = constant($name);
                }
            }
        }
        foreach ($roots as $root) {
            $root = self::normalizeCleanupPath($root);
            if ($root !== false && ($path === $root || strpos($path, $root . DIRECTORY_SEPARATOR) === 0)) {
                return $path;
            }
        }

        return false;
    }


    /**
     * Проверява реалния път, без системния корен, директните му деца или символни връзки.
     *
     * @return string|false
     */
    private static function normalizeCleanupPath($dir)
    {
        if (!is_string($dir) || $dir === '' || strpos($dir, "\0") !== false) {
            return false;
        }

        clearstatcache(true);
        $path = realpath($dir);
        if ($path === false || !is_dir($path)) {
            return false;
        }

        // Изискваме конкретна папка, а не цял системен раздел като /etc или /var.
        $parent = dirname($path);
        if ($parent === $path || dirname($parent) === $parent) {
            return false;
        }

        // Не допускаме обхождане на цялата инсталация или на неин родител.
        $appPath = realpath(dirname(__DIR__));
        if ($path === $appPath || strpos($appPath, $path . DIRECTORY_SEPARATOR) === 0 || $path === realpath(sys_get_temp_dir())) {
            return false;
        }

        // Постоянните оригинали не са временни файлове, независимо от разрешената база.
        $uploadsPaths = array();
        if (defined('EF_UPLOADS_PATH') && is_string(EF_UPLOADS_PATH) && EF_UPLOADS_PATH !== '') {
            $uploadsPaths[] = EF_UPLOADS_PATH;
            // fileman_Data може още да не е зареден в този крон процес.
            $uploadsPaths[] = defined('FILEMAN_UPLOADS_PATH') ? FILEMAN_UPLOADS_PATH : substr(EF_UPLOADS_PATH, 0, strrpos(EF_UPLOADS_PATH, '/')) . '/fileman';
        } elseif (defined('FILEMAN_UPLOADS_PATH')) {
            $uploadsPaths[] = FILEMAN_UPLOADS_PATH;
        }
        foreach ($uploadsPaths as $uploadsPath) {
            if (!is_string($uploadsPath) || $uploadsPath === '' || strpos($uploadsPath, "\0") !== false) {
                continue;
            }
            $uploadsPath = realpath($uploadsPath);
            if ($uploadsPath !== false && ($path === $uploadsPath ||
                strpos($path, rtrim($uploadsPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0 ||
                strpos($uploadsPath, $path . DIRECTORY_SEPARATOR) === 0)) {
                return false;
            }
        }

        for ($part = rtrim($dir, DIRECTORY_SEPARATOR === '\\' ? '/\\' : '/'); ; $part = $parent) {
            if (is_link($part)) {
                return false;
            }
            $parent = dirname($part);
            if ($parent === $part) {
                break;
            }
        }

        return $path;
    }


    /**
     * Връща времето на последен достъп до файла
     *
     * @param string $path - Пътя до файла
     *
     * @return int - Времето на последен достъп до файла
     */
    public static function getFileLastAccessTime($path)
    {
        
        $res = @fileatime($path);

        if(!$res) {
            $res = @filemtime($path);
        }

        return (int) $res;
    }
    
    
    /**
     * Почиства старите временни файлове и статичния кеш по крон.
     */
    public static function cron_clearOldFiles()
    {
        // Конфигурацията на пакета core
        $conf = core_Packs::getConfig('core');
        $maxAge = $conf->CORE_TEMP_PATH_MAX_AGE ?? 864000;
        
        // Резултат във вербален вид
        $resText = '';

        // SBF има собствени изключения, дори когато е вложен във временна папка.
        $sbfPath = defined('EF_SBF_PATH') ? self::getCleanupPath(EF_SBF_PATH) : false;
        $tempExclude = $sbfPath === false ? null : '#^' . preg_quote($sbfPath, '#') . '(?:' . preg_quote(DIRECTORY_SEPARATOR, '#') . '|$)#';
        
        // Пакетите може да използват временни папки извън общата EF_TEMP_PATH.
        $cleanedPaths = array();
        foreach (array('EF_TEMP_PATH', 'FILEMAN_TEMP_PATH', 'FCONV_TEMP_PATH') as $name) {
            $tempPath = defined($name) ? self::getCleanupPath(constant($name)) : false;
            if ($tempPath === false) {
                continue;
            }
            foreach ($cleanedPaths as $cleanedPath) {
                if ($tempPath === $cleanedPath || strpos($tempPath, $cleanedPath . DIRECTORY_SEPARATOR) === 0) {
                    continue 2;
                }
            }
            $delCnt = self::deleteOldFiles($tempPath, $maxAge, $tempExclude);
            $cleanedPaths[] = $tempPath;
            if ($delCnt > 0) {
                $resText .= ($resText ? "\n" : '') . ($delCnt > 1 ? 'Бяха изтрити' : 'Беше изтрит') . " {$delCnt} " . ($delCnt > 1 ? 'файла' : 'файл') . ' от ' . htmlspecialchars($tempPath, ENT_QUOTES, 'UTF-8');
            }
        }
        
        // Изтриваме всички стари файлове в поддиректории на sbf които не започват със символа '_'
        if ($sbfPath !== false) {
            $delCnt = 0;
            if ($handle = @opendir($sbfPath)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != '.' && $entry != '..' && false === strpos($entry, '_') && is_dir($sbfPath . "/{$entry}")) {
                        $delCnt += self::deleteOldFiles($sbfPath . "/{$entry}", $maxAge);
                    }
                }
                closedir($handle);
            }
            if ($delCnt > 0) {
                $resText .= ($resText ? "\n" : '') . ($delCnt > 1 ? 'Бяха изтрити' : 'Беше изтрит') . " {$delCnt} " . ($delCnt > 1 ? 'файла' : 'файл') . ' от ' . htmlspecialchars($sbfPath, ENT_QUOTES, 'UTF-8');
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
        if (empty($lastModificationDir[$dir])) {
            
            // Обхождаме файловете
            foreach ($files as $file) {
                
                // Прескачаме ги
                if ($file == '.' || $file == '..' || !str::matchPatterns($file, $negativePattern, $positivePattern)) {
                    continue;
                }
                
                // Вземаме времето на промяна на последния файл
                $time = filemtime($dir . DIRECTORY_SEPARATOR . $file);
                
                // Ако времето е по - голямо от записаното в директорията
                if ($time > ($lastModificationDir[$dir] ?? null)) {
                    
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
            // PHP_BINARY сочи към CLI бинарника само когато сме в CLI. Под FPM/CGI
            // сочи към php-fpm/php-cgi, които не могат да изпълняват скриптове
            (PHP_SAPI == 'cli' && defined('PHP_BINARY')) ? PHP_BINARY : false);

        foreach ($paths as $p) {
            if ($p && file_exists($p) && is_executable($p) && self::isPhpCliBin($p)) {

                return $p;
            }
        }

        // Търсим CLI от същата версия, за да не се разминава с тази на уеб сървъра
        $names = array('php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, 'php');

        foreach ($names as $name) {
            $cmd = self::isWindows() ? "where {$name}" : "which {$name}";

            $lines = array();
            exec($cmd, $lines, $returnVal);

            if ($returnVal != 0 || empty($lines[0])) {
                continue;
            }

            $p = trim($lines[0]);

            if (is_executable($p) && self::isPhpCliBin($p)) {

                return $p;
            }
        }

        return false;
    }


    /**
     * Изпълнимият файл PHP ли е и от вид, който може да изпълнява скриптове?
     * Отсяват се php-fpm и php-cgi, които при подаден скрипт връщат само usage
     */
    private static function isPhpCliBin($path)
    {
        $name = strtolower(basename($path));

        if (strpos($name, 'php') === false) {

            return false;
        }

        return strpos($name, 'fpm') === false && strpos($name, 'cgi') === false;
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
        $memory_usage = null;
        if (!$this->isWindows()) {
            $mem = $this->getFreeRes();
            if (!empty($mem[1])) {
                $memory_usage = $mem[2] / $mem[1] * 100;
            }
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
            $memoryLimit = $mem[1] ?? null;
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
            $memoryLimit = $mem[3] ?? null;
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
            $mem = explode(' ', $freeArr[1] ?? '');
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
            if((fileperms($path) & 0777) != $permissions && !isDocker()) {
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
        $val = substr($val, 0, strlen($val)-1);
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
    

    /**
     * Стартира шел команда, без да чака изпълнението и
     */
    public static function startCmd($cmd)
    {
        if (self::isWindows()){
            $h = popen("start \"php\" /B ". $cmd . ' 2>nul >nul', "r");
            pclose($h); 
        } else { 
            exec($cmd . " > /dev/null &"); 
        }   
    }
}
