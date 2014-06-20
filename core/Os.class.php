<?php



/**
 * Клас 'core_Os' - Стартиране на процеси на OS
 *
 * PHP versions 4 and 5
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Os
{
    
    
    /**
     * Конструктор
     */
    function core_Os()
    {
        if ($this->isWindows()) {
            $this->wshShell = @new COM("WScript.Shell");
            
            if (!$this->wshShell)
            error('Невъзможност да се инициализира WScript.Shell', TRUE);
            $this->wmi = @new COM("winmgmts://./root/cimv2");
            
            if (!$this->wmi)
            error('Невъзможност да се инициализира winmgmts://./root/cimv2', TRUE);
        }
    }
    
    
    /**
     * Връща TRUE ако операционната система е Windows
     */
    static function isWindows()
    {
        return stristr(PHP_OS, 'WIN');
    }
    
    
    /**
     * Изпълнява команда/програма на ОС
     * Ако е необходимо, командата се изпълнява с текуща
     * директория посочена в $dir
     * Ако timeout e дефиниран, след неговото изтичане се прекратява
     * изпълняването на командата
     * при грешка се връща FALSE
     * При mode == 'execSync' - изпълнява и връща кода
     * При mode == 'getOutput' - изпълнява и връща изхода
     * При mode == 'execBkg' - стартира във фонов режим и връща pid
     */
    function exec($cmd, $mode = 'execSync', $dir = NULL, $timeout = 0)
    {
        // Ескейпваме аргументите
        if (is_array($cmd)) {
            foreach ($cmd as $id => $arg) {
                if ($id > 0) {
                    $cmd[0] = str_replace("[#{$id}#]", '"' . $cmd[$id] . '"', $cmd[0]);
                }
            }
            $cmd = $cmd[0];
        }
        
        // Синхронно ли ще изпълняваме процеса?
        $sync = ($mode == 'execSync' || $mode == 'getOutput') && (!$timeout);
        
        $uniqId = $this->getUniqId();
        
        if ($this->isWindows()) {
            // Ако е необходимо да  сменяме текущата директория
            // преди това я запазваме
            if ($dir) {
                $curDir = $this->wshShell->CurrentDirectory;
                $dir = str_replace('/', DIRECTORY_SEPARATOR, $dir);
                $this->wshShell->CurrentDirectory = $dir;
            }
            
            if ($mode == 'getOutput') {
                $tempOutputFile = $this->getTempFile($uniqId);
                $osCmd = $cmd . " >\"{$tempOutputFile}\"";
            }
            
            if($mode == 'execSync') {
                $osCmd = $cmd;
            }
            
            if (!$sync) {
                $tempErrorFile = $this->getErrorFile($uniqId);
                $osCmd = $cmd . " 2>\"{$tempErrorFile}\"";
            }
            
            // $osCmd = "cmd /c \"" . $osCmd . "\"";
            
            // Изпълняваме командата
            $res = exec($osCmd);
            
            // Логваме каква команда сме изпълнили
            Debug::log($osCmd . "($res)");
            
            // Ако изпълняваме а-синхронно процеса, тогава опитваме се да намерим PID-то 
            if (!$sync) {
                $pid = $this->_getPidByCommand($cmd);
                
                // Ако не сме намерили pid, връщаме грешка. Процесът трябва да е стартиран. 
                // А дали не е завършил бързо?
                if (!$pid)
                return FALSE;
            }
            
            // Възстановяваме, ако е необходимо предишната директория
            if ($dir) {
                $this->wshShell->CurrentDirectory = $curDir;
            }
            
            // Ако изпълняваме процес в бекграунд, връщаме pid
            if ($mode == 'execBkg') {
                return "{$pid}_{$uniqId}";
            }
            
            // Ако изпълняваме процеса а-синхронно, чакаме да свърши или
            // да изтече тайм-аута
            if (!$sync) {
                $time = 0;
                
                do {
                    // Ако процесът е приключил - излизаме
                    if (!$this->isRunning($pid)) {
                        //TODO
                        break;
                    }
                    
                    // Изчакваме 1 секунди
                    sleep(1);
                    
                    //Проверка за прекъсване по таймаут
                    if ($timeout > 0) {
                        $time++;
                        
                        if ($time > $timeout) {
                            $this->killProcess($pid);
                            $this->lastError = 'timeout';
                            
                            return FALSE;
                        }
                    }
                } while (TRUE);
                
                if (file_exists($tempErrorFile)) {
                    $this->lastErrorInfo = file_get_contents($tempErrorFile);
                    unlink($tempErrorFile);
                    
                    if (strlen(trim($this->lastErrorInfo))) {
                        $this->lastError = 1;
                        
                        return FALSE;
                    }
                }
            }
            
            if ($mode == 'getOutput') {
                $outputLines = explode("\n", str_replace("\r", '', file_get_contents($tempOutputFile)));
                unlink($tempOutputFile);
            }
            
            // Ако има грешка
            if ($res && !$sync) {
                $this->lastError = $res;
                
                return FALSE;
            }
            
            if ($mode == 'getOutput') {
                return $outputLines;
            } else {
                return $res;
            }
        } else {
            // Ако ОС е Linux
            if ($dir) {
                $curDir = getcwd();
                chdir($dir);
            }
            
            $res = exec($cmd, $outputLines);
            Debug::log($cmd . " ($res)");
            
            if ($dir) {
                chdir($curDir);
            }
            
            if ($mode == 'getOutput') {
                return $outputLines;
            } else {
                return $res;
            }
        }
    }
    
    
    /**
     * Проверява дели определен процес се изпълнява
     */
    function _getPidByCommand($cmd)
    {
        if ($this->isWindows()) {
            $processes = $this->wmi->execQuery("select Handle, Caption, CommandLine, ExecutablePath  from win32_process");
            
            foreach ($processes as $p) {
                if (strpos($p->CommandLine, $cmd) !== FALSE) {
                    return $p->Handle;
                }
            }
            
            /* PHP4 ??
            for($i = 0; $i < $processes->Count; $i++) {
            $p = $processes->Next();
            if( strpos($p->CommandLine, $cmd) !== FALSE ) {
            return $p->Handle;
            }
            }
            */
        
        } else {
            $result = shell_exec("ps -o %p : %a");
            $lines = preg_split("/\n/", $result);
            
            for ($i = 1; $i < count($lines); $i++) {
                if (strpos($lines[$i], $cmd) !== FALSE) {
                    return (int) trim($lines[$i]);
                }
            }
        }
    }
    
    
    /**
     * Проверява дели определен процес се изпълнява
     * Не е достатъчно да проверим само processId , трябва да видим и
     * в командната линия дали присъства уникалното id на output и error
     * файла
     */
    function isRunning($pHnd)
    {
        // първият елемент трябва да е id-то на процеса в ОС, а втория 
        // уникалното ид, използвано за името на файловете
        list($pid, $unicId) = explode('_', $pHnd);
        
        // Windows
        if ($this->isWindows()) {
            $processes = $this->wmi->execQuery("select * from Win32_Process Where ProcessID = {$pid} AND CommandLine LIKE '%{$unicId}%'");
            
            if ($processes->Count) {
                return TRUE;
            } else {
                return FALSE;
            }
            
            // Linux
        } else {
            $result = shell_exec(sprintf("ps %d", $pid));
            
            if (count(preg_split("/\n/", $result)) > 2) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }
    
    
    /**
     * Връща съобщенията за грешки, генерирани от съответния процес
     */
    function getErrors($pid)
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
     * Терминира процес
     */
    function killProcess($pid)
    {
        $pid = (int) $pid;
        
        if ($this->isWindows()) {
            $processes = $this->wmi->execQuery("select * from Win32_Process Where ProcessID = {$pid}");
            
            if ($processes->Count) {
                $p = $processes->Next();
                $p->Terminate();
            }
        } else {
            return shell_exec(sprintf("kill %d", $pid));
        }
    }
    
    
    /**
     * Връща уникален глобален идентификатор
     */
    static function getUniqId($base = 'id')
    {
        static $i, $uniqId;
        
        if (!$uniqId) {
            $uniqId = uniqid($base);
        }
        $i++;
        
        return $uniqId . "_" . $i;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getTempFile($uniqId)
    {
        return EF_TEMP_PATH . "\\" . $uniqId . ".out";
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getErrorFile($uniqId)
    {
        return EF_TEMP_PATH . "\\" . $uniqId . ".err";
    }
    
    
    /**
     * Изтрива директория
     * Връща false при неуспех
     */
    static function deleteDir($dir)
    {
		foreach(glob($dir . '/*') as $file) {
		        if(is_dir($file))
		            self::deleteDir($file);
		        else
		            @unlink($file);
		}
		
	    return @rmdir($dir);
    }


    /**
     * Изтрива файловете в посочената директория и нейните под-директории,
     * които не са прочитани в последните скудни указани от $maxAge
     * 
     * @param string $dir
     * @param integer $maxAge
     * 
     * @return integer - Броя на изтритите файлове
     */
    static function deleteOldFiels($dir, $maxAge = 86400)
    {
        $allFiles = self::listFiles($dir);
        
        $delCnt = 0;
        if(is_array($allFiles['files'])) {
            foreach($allFiles['files'] as $fPath) {
                if(time() - fileatime($fPath) > $maxAge) {
                    
                    if (@unlink($fPath)) {
                        $delCnt++;
                    }
                }
            }
        }
        
        return $delCnt;
    }
    
    
    /**
     * Изтрива всички файлове от EF_TEMP_PATH по крон
     */
    static function cron_clearOldFiles()
    {
        // Ако не е дефиниран пътя
        if (EF_TEMP_PATH == 'EF_TEMP_PATH') return ;
        
        $conf = core_Packs::getConfig('core');
        
        // Изтриваме всички, файлове, кото са по стари от дадено време
        $delCnt = static::deleteOldFiels(EF_TEMP_PATH, $conf->CORE_TEMP_PATH_MAX_AGE);
        
        // Показваме броя на изтритите файлове
        if ($delCnt) {
            
            if ($delCnt == 1) {
                $resText = "Изтрит 1 файл";
            } else {
                $resText = "Изтрити {$delCnt} файла";
            }
            
            $resText .= " от '" . EF_TEMP_PATH . "'";
            
            return $resText;
        }
    }
    

    /**
     * Връща масив със всички поддиректории и файлове от посочената начална директория
     *
     * array(
     * 'files' => [],
     * 'dirs'  => [],
     * )
     * @param string $root
     * @result array
     */
    static function listFiles($root)
    {
        $files = array('files'=>array(), 'dirs'=>array());
        $directories = array();
        $last_letter = $root[strlen($root)-1];
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;        //?
        $directories[] = $root;
        
        while (sizeof($directories)) {
            
            $dir = array_pop($directories);
            
            if ($handle = opendir($dir)) {
                while (FALSE !== ($file = readdir($handle))) {
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
                closedir($handle);
            }
        }
 
        return $files;
    }

    
    /**
     * Връща времето на последната промяна на файл в директорията
     * 
     * @param string $dir - Директорията
     * 
     * @return integer - Времето на последната промяна
     */
    static function getLastModified($dir)
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
                if ($file == '.' || $file == '..') continue;
                
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
    static function pregLastError()
    {
        $pregLastError = preg_last_error();
        
        if ($pregLastError == PREG_NO_ERROR) {
            $res = 'There is no error.';
        } else if ($pregLastError == PREG_INTERNAL_ERROR) {
            $res = 'There is an internal error!';
        } else if ($pregLastError == PREG_BACKTRACK_LIMIT_ERROR) {
            $res = 'Backtrack limit was exhausted!';
        } else if ($pregLastError == PREG_RECURSION_LIMIT_ERROR) {
            $res = 'Recursion limit was exhausted!';
        } else if ($pregLastError == PREG_BAD_UTF8_ERROR) {
            $res = 'Bad UTF8 error!';
        } else if ($pregLastError == PREG_BAD_UTF8_ERROR) {
            $res = 'Bad UTF8 offset error!';
        } else {
            $res = 'Unrecognized error!';
        }
        
        return $res;
    }


    /**
     * Връща броя на стартираните процеси на Apache
     */
    function countApacheProc()
    {   
        $processes = 0;

        if($this->isWindows()) {
            $output = shell_exec("tasklist");
            $lines = explode("\n", $output);
            foreach($lines as $l) { 
                if(strpos($l, 'httpd.exe') !== FALSE) {
                    $processes++; 
                }
            }
        } else {
            exec('ps aux | grep apache', $output);
            $processes = count($output);
        }

        return $processes;
    }
}