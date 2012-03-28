<?php



/**
 * Клас 'core_Os' - Стартиране на процеси на OS
 *
 * PHP versions 4 and 5
 *
 *
 * @category  all
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
    function isWindows()
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
    function getUniqId($base = 'id')
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
     * Изтрива директорията
     */
    function deleteDir($dir)
    {
        //Проверяваме дали е подадена директория
        if ((!$dir) || (!is_dir($dir) && (!is_file($dir)))) return FALSE;
        
        if (substr($dir, strlen($dir)-1, 1) != '/') {
            $dir .= '/';
        }
        
        if ($handle = opendir($dir)) {
            while ($obj = readdir($handle)) {
                if ($obj != '.' && $obj != '..') {
                    if (is_dir($dir . $obj)) {
                        if (!$this->deleteDir($dir . $obj))
                        
                        return false;
                    } else {
                        if (!unlink($dir . $obj)) {
                            
                            return false;
                        }
                    }
                }
            }
            closedir($handle);
            
            if (!@rmdir($dir)) {
                
                return false;
            }
            
            return true;
        }
        
        return false;
    }
}