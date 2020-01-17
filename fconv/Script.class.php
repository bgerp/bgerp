<?php


/**
 * Генериране и изпълняване на шел скриптове за конвертиране в различни формати
 *
 *
 * @category  vendors
 * @package   fconv
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fconv_Script
{
    /**
     * @param array files - Масив за входните файлове
     */
    public $files = array();
    
    
    /**
     * Масив за папките
     */
    public $folders = array();
    
    
    /**
     * @param array programs - Масив за изпълнимите команди
     */
    public $programs = array();
    
    
    /**
     * Масив с програмите и пътя в конфига към тях
     */
    public $programPaths = array();
    
    
    /**
     * Ако е зададено да се спре, няма да се пуска отдалечена обработка
     */
    public $stopRemote = false;
    
    
    /**
     * @param array - Масив за параметрите на скрипта
     */
    public $cmdParams = array();
    
    
    /**
     * @param array - Масив за допълнителни параметрите при изпълнение
     */
    public $params = array();
    
    
    /**
     * @param array - Масив за параметрите на скрипта
     */
    public $cmdParamsOrig = array();
    
    
    /**
     * @param string script - Текст на скрипта
     */
    public $script;
    
    
    /**
     * Масив с изпълнимите стрингове, без обработки
     */
    public $cmdLine = array();
    
    
    /**
     * Параметрите на изпълнимите стрингове
     */
    public $lineParams = array();
    
    
    /**
     * Масив с подадените callback функции
     */
    public $callBack = array();
    
    
    /**
     * УРЛ, което ще се извика след приключване на отдалеченото конвертиране
     */
    public $remoteAfterConvertCallback = '';
    
    
    /**
     * Дали скрипта е пуснат синхронно или асинхронно
     */
    public $runAsynch = true;
    
    
    /**
     * Масив с файлове, които няма да се връщата обратно след приключване на обработките
     */
    public $skipOnRemote = array();
    
    
    /**
     * Програми, които трябва да се проверят, преди да се изпълни
     */
    protected $checkProgramsArr = array();
    
    
    public $tempPath;
    
    
    public $id;
    
    
    public $tempDir;
    
    
    /**
     * Инициализиране на уникално id
     */
    public function __construct($tempDir = null)
    {
        $conf = core_Packs::getConfig('fconv');
        $this->tempPath = $conf->FCONV_TEMP_PATH . '/';
        $this->id = fconv_Processes::getProcessId();
        setIfNot($tempDir, $this->tempPath . $this->id . '/');
        $this->tempDir = $tempDir;
    }
    
    
    /**
     *
     *
     * @param string $placeHolder
     * @param string $folder
     */
    public function setFolders($placeHolder, $folder)
    {
        $this->folders[$placeHolder] = $folder;
    }
    
    
    /**
     *
     *
     * @return array
     */
    public function getFolders()
    {
        return $this->folders;
    }
    
    
    /**
     * Задаване на входен файл
     *
     * @param string $placeHolder
     * @param string $file
     * @param bool   $checkFile
     */
    public function setFile($placeHolder, $file, $checkFile = false)
    {
        $this->files[$placeHolder] = $file;
        
        // Ако е зададен параметър, файла да се валидира
        if ($checkFile) {
            
            // Ако е файл в директория
            if (strstr($file, '/')) {
                $isValid = is_file($file);
            } else {
                
                // Ако е манупулатор на файл
                $isValid = fileman_Files::fetchField("#fileHnd='{$file}'");
            }
            
            // Ако не е валиден файл, изписваме съобщение за грешка в лога
            if (!$isValid) {
                log_System::add('fconv_Script', "Файлът не съществува: '{$file}'", null, 'err');
            }
        }
    }
    
    
    /**
     * Задаване на път до изпълнима външна програма
     *
     * @param string $name
     * @param string $binPath
     * @param bool   $escape
     */
    public function setProgram($name, $binPath, $escape = true)
    {
        if ($escape) {
            $binPath = escapeshellcmd($binPath);
        }
        
        if (strpos($binPath, ' ')) {
            $binPath = '\"' . $binPath . '\"';
        }
        
        $this->programs[$name] = $binPath;
    }
    
    
    /**
     * Задаване на команда и пътя до командата в конфига на пакета
     *
     * @param string $name
     * @param string $path
     */
    public function setProgramPath($name, $path)
    {
        $this->programPaths[$name] = $path;
    }
    
    
    /**
     * Задаване на друго общи за целия скрипт параметри
     */
    public function setParam($placeHolder, $value = null, $escape = true)
    {
        $this->cmdParamsOrig[$placeHolder] = $value;
        
        if ($escape) {
//            $this->cmdParams[$placeHolder] = escapeshellcmd($value);
            $this->cmdParams[$placeHolder] = escapeshellarg($value);
        } else {
            $this->cmdParams[$placeHolder] = $value;
        }
    }
    
    
    /**
     * Връща линията за изпълнени
     *
     * @param string $cmdLine
     * @param bool   $silent
     *
     * @return string
     */
    public function getCmdLine($cmdLine, $silent = false)
    {
        $sepPos = strpos($cmdLine, '::');
        
        if (!$silent) {
            expect($sepPos, 'Изпълнимият ред не може да се задава директно');
        }
        
        if ($sepPos) {
            list($cls, $p) = explode('::', $cmdLine);
            if (cls::load($cls, $silent)) {
                $clsInst = cls::get($cls);
                $cmdLine = $clsInst->{$p};
            }
        }
        
        return $cmdLine;
    }
    
    
    /**
     * Връща скриптва, който се добавя преди процесите, за спиране на обработката след определено време
     *
     * @return string
     */
    public static function getTimeLimitScript()
    {
        $timeLimitScr = '';
        if (fconv_Setup::get('USE_TIME_LIMIT') == 'yes') {
            $timeLimitArr = explode(' ', fconv_Setup::get('TIME_LIMIT'));
            
            $timeLimitArr[0] = escapeshellcmd($timeLimitArr[0]);
            
            foreach ($timeLimitArr as $key => &$val) {
                if ($key == 0) {
                    continue;
                }
                if ($val{0} == '-') {
                    continue;
                }
                
                $val = escapeshellarg($val);
            }
            
            $timeLimitScr = implode(' ', $timeLimitArr);
        }
        
        return $timeLimitScr;
    }
    
    
    /**
     * Добавя извикване на външна програма в текста на скрипта
     *
     * @param string $cmdLine
     * @param array  $params
     * @param bool   $addTimeLimit
     */
    public function lineExec($cmdLine, $params = array(), $addTimeLimit = true)
    {
        $this->cmdLine[] = $cmdLine;
        $this->lineParams[] = $params;
        
        $cmdLine = $this->getCmdLine($cmdLine, true);
        
        $cmdArr = explode(' ', $cmdLine);
        $program = $cmdArr[0];
        $binPath = $this->programs[$program] ? $this->programs[$program] : $program;
        $cmdArr[0] = $binPath;
        $cmdLine = implode(' ', $cmdArr);
        
        if (countR($this->cmdParams)) {
            foreach ($this->cmdParams as $placeHolder => $value) {
                $cmdLine = str_replace("[#{$placeHolder}#]", $value, $cmdLine);
            }
        }
        
        if ($addTimeLimit && $cmdLine) {
            $timeLimitScr = $this->getTimeLimitScript();
            if ($timeLimitScr) {
                $cmdLine = $timeLimitScr . ' ' . $cmdLine;
            }
        }
        
        // Възможност за логване на грешките при изпълняване на скрипт
        if ($params['errFilePath'] && !stristr(PHP_OS, 'WIN')) {
            $cmdLine .= ' 2> ' . escapeshellarg($params['errFilePath']);
        }
        
        $this->script .= $this->nl($cmdLine);
        
        // Ако е подаден параметър език, тогава се добавя в началото на скрипта
        if ($params['LANG']) {
            $this->script = "LANG='{$params['LANG']}' " . $this->script;
        }
        
        // Ако е подаден параметър език, тогава се добавя в началото на скрипта
        if ($params['HOME']) {
            $this->script = "HOME='{$params['HOME']}' " . $this->script;
        }
    }
    
    
    /**
     * Добавя нов ред
     *
     * @param string $cmdLine
     *
     * @return string
     */
    public function nl($cmdLine)
    {
        if (stristr(PHP_OS, 'WIN')) {
            $cmdLine .= "\n\r";
        } else {
            $cmdLine .= "\n";
        }
        
        return $cmdLine;
    }
    
    
    /**
     * Добавя линия Bash Script. Изпълнява се само ако текущата OS е Linux
     *
     * @param string $cmd
     */
    public function lineSH($cmd)
    {
        if (stristr(PHP_OS, 'WIN')) {
            
            return ;
        }
        
        $this->script .= $this->nl($cmd);
    }
    
    
    /**
     * Добавя линия Visual Basic Script. Изпълнява се само ако текущата OS е Windows
     *
     * @param string $cmd
     */
    public function lineVBS($cmd)
    {
        if (!stristr(PHP_OS, 'WIN')) {
            
            return ;
        }
        
        $this->script .= $this->nl($cmd);
    }
    
    
    /**
     * Добавя текст в скрипта, който извиква указания callback
     *
     * @param string $callback
     */
    public function callBack($callback)
    {
        $this->callBack[] = $callback;
        
        Request::setProtected('pid, func');
        
        $url = toUrl(array('fconv_Processes',
            'CallBack', 'func' => $callback, 'pid' => $this->id), 'absolute');
        
        $cmdLine = "wget -q --spider --no-check-certificate '{$url}'";
        $this->setCheckProgramsArr('wget');
        
        $this->lineExec($cmdLine, array('skipOnRemote' => true));
    }
    
    
    /**
     * Задаваме програми, които ще се проверяват преди да се пусни обработка
     */
    public function setCheckProgramsArr($programs)
    {
        $programs = arr::make($programs, true);
        $this->checkProgramsArr += $programs;
    }
    
    
    /**
     * Програми, които ще се проверяват преди да се пусни обработка
     */
    public function getCheckProgramsArr()
    {
        return $this->checkProgramsArr;
    }
    
    
    /**
     * Изпълнява скрипта, като му дава време за изпълнение
     *
     * @param bool   $asynch
     * @param int    $time
     * @param string $timeoutCallback
     */
    public function run($asynch = true, $time = 2, $timeoutCallback = '')
    {
        // Кеш за липсващи програми
        static $missing = array();

        // Ако е зададена програма, може да се пусне скрипта отдалечено, на друг сървър
        // и да се чака резултат от там
        if (!$this->stopRemote) {
            foreach ($this->programs as $program => $programPath) {
                if ($rRec = fconv_Remote::fetch(array("#command = '[#1#]'", $program))) {
                    $this->runAsynch = $asynch;
                    
                    fconv_Remote::prepareFiles($this);
                    
                    $this->remoteAfterConvertCallback = toUrl(array('fconv_Remote', 'afterConvertCallback', 'pid' => $this->id), 'absolute');
                    
                    $script = urlencode(core_Crypt::encodeVar($this, fconv_Setup::get('SALT')));
                    
                    $url = rtrim($rRec->address, '/');
                    
                    $url .= '/fconv_Remote/convert/?script=' . $script;
                    
                    fconv_Processes::add($this->id, serialize($this), $time, $timeoutCallback);
                    
                    file_get_contents($url);
                    
                    log_System::add('fconv_Remote', 'Отдалечен скрипт: ' . $url, $rRec->id);
                    
                    return ;
                }
            }
        }
        
        $checkProgramsArr = $this->getCheckProgramsArr();
        
        // Ако са зададени програми, които да се проверят преди обработка.
        $which = 'which';
        if (stristr(PHP_OS, 'WIN')) {
            $which = 'where.exe';
        }
        if (!empty($checkProgramsArr)) {
            foreach ($checkProgramsArr as $program) {
                if($missing[$program]) return false;
                if (isset($this->programs[$program])) {
                    $path = $this->programs[$program];
                } else {
                    $path = escapeshellcmd($program);
                }
                
                if (!(is_executable($path) || exec("{$which} {$path}"))) {
                    log_System::add('fconv_Remote', 'Липсва програма: ' . $path, $rRec->id, 'warning');
                    $missing[$program] = true;
                    return false;
                }
            }
        }
        
        if (!stristr(PHP_OS, 'WIN')) {
            $this->script = "#!/bin/bash \n" . $this->script;
        } elseif(!$asynch) {
            $this->script = 'start /wait ' . $this->script;
        }
 
        core_Os::requireDir($this->tempDir);
        
        $foldersArr = $this->getFolders();
        
        if (!empty($foldersArr)) {
            foreach ((array) $foldersArr as $placeHolder => $folderName) {
                $nFolderPath = $this->tempDir . $folderName;
                core_Os::requireDir($nFolderPath);
                $this->script = str_replace("[#{$placeHolder}#]", escapeshellarg($nFolderPath), $this->script);
            }
        }
        
        if (countR($this->files)) {
            foreach ($this->files as $placeHolder => $file) {
                if (strstr($file, '/')) {
                    $path_parts = pathinfo($file);
                    $fileName = $path_parts['basename'];
                    $filePath = $file;
                } else {
                    $Files = cls::get('fileman_Files');
                    $fileName = $Files->fetchByFh($file, 'name');
                    $filePath = $Files->fetchByFh($file, 'path');
                }
                
                $newFileName = $this->getUniqName($fileName, $filePath);
                
                if ($newFileName) {
                    $copy = $this->copy($newFileName, $filePath);
                    $this->script = str_replace("[#{$placeHolder}#]", escapeshellarg($this->tempDir . $newFileName), $this->script);
                }
            }
        }
        
        $shellName = $this->tempDir . $this->id . $this->addExtensionScript();
        
        $this->skipOnRemote[$shellName] = $shellName;
        
        if (!($fh = fopen($shellName, 'w'))) {
            die("can't open file");
        }
        fwrite($fh, $this->script);
        fclose($fh);
        
        fconv_Processes::add($this->id, serialize($this), $time, $timeoutCallback);
        
        chmod($shellName, 0777);
        
        // Ако е зададено да се стартира асинхронно
        if ($asynch) {
            $shell = $this->addRunAsinchronWin() . escapeshellarg($shellName) . $this->addRunAsinchronLinux();
        } else {
            $shell = $shellName;
        }
        
        log_System::add('fconv_Script', 'Стартиран скрипт: ' . $this->script);

        pclose(popen($shell, 'r'));
    }
    
    
    /**
     * Проверява и генерира уникално име на файла
     *
     * @param string $fname
     * @param string $fpath
     *
     * @return string|bool
     */
    public function getUniqName($fname, $fpath)
    {
        // Циклим докато генерираме име, което не се среща до сега
        $fn = $fname;
        
        if (!is_dir($fn)) {
            if (($dotPos = mb_strrpos($fname, '.')) !== false) {
                $firstName = mb_substr($fname, 0, $dotPos);
                $ext = mb_substr($fname, $dotPos);
            } else {
                $firstName = $fname;
                $ext = '';
            }
            
            $i = 0;
            $files = scandir($this->tempDir);
            
            while (in_array($fn, $files)) {
                $fn = $firstName . '_' . (++$i) . $ext;
            }
            
            return $fn;
        }
        
        return false;
    }
    
    
    /**
     * Копира избрания файл или създава софт линк под Linux
     *
     * @param string $fileName
     * @param string $filePath
     *
     * @return bool
     */
    public function copy($fileName, $filePath)
    {
        if (is_file($filePath)) {
            if (stristr(PHP_OS, 'WIN')) {
                $copied = copy($filePath, $this->tempDir . $fileName);
            } else {
                $filePath = escapeshellarg($filePath);
                $copyPath = escapeshellarg($this->tempDir . $fileName);
                $copied = exec("ln -s {$filePath} {$copyPath}");
            }
            
            $fPath = $this->tempDir . $fileName;
            $this->skipOnRemote[$fPath] = $fPath;
        }
        
        return true;
    }
    
    
    /**
     * Добавя разширение в зависимост от ОС, към файла на скрипта
     *
     * @return string
     */
    public function addExtensionScript()
    {
        if (stristr(PHP_OS, 'WIN')) {
            
            return '.bat';
        }
        
        return '.sh';
    }
    
    
    /**
     * Добавя разширение за асинхронно стартиране на скрипта за Линукс
     *
     * @return string
     */
    public function addRunAsinchronLinux()
    {
        if (stristr(PHP_OS, 'WIN')) {
            
            return '';
        }
        
        return ' > /dev/null &';
    }
    
    
    /**
     * Добавя разширение за асинхронно стартиране на скрипта за Windows
     *
     * @return string
     */
    public function addRunAsinchronWin()
    {
        if (stristr(PHP_OS, 'WIN')) {
            
            return 'start ';
        }
        
        return '';
    }
}
