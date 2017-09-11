<?php


/**
 * Генериране и изпълняване на шел скриптове за конвертиране в различни формати
 *
 *
 * @category  vendors
 * @package   fconv
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
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
    public $stopRemote = FALSE;
    
    
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
    public  $script;
    
    
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
    public $runAsynch = TRUE;
    
    
    /**
     * Масив с файлове, които няма да се връщата обратно след приключване на обработките
     */
    public $skipOnRemote = array();
    
    
    /**
     * Програми, които трябва да се проверят, преди да се изпълни
     */
    protected $checkProgramsArr = array();
    
    
    /**
     * 
     */
    public $tempPath;
    
    
    /**
     * 
     */
    public $id;
    
    
    /**
     * 
     */
    public $tempDir;
    
    
    /**
     * Инициализиране на уникално id
     */
    function __construct($tempDir = NULL)
    {
        $conf = core_Packs::getConfig('fconv');
        $this->tempPath = $conf->FCONV_TEMP_PATH . "/";
        $this->id = fconv_Processes::getProcessId();
        setIfNot($tempDir, $this->tempPath . $this->id . "/");
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
     * @param boolean $checkFile
     */
    function setFile($placeHolder, $file, $checkFile = FALSE)
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
                log_System::add('fconv_Script', "Файлът не съществува: '{$file}'", NULL, 'err');
            }
        }
    }
    
    
    /**
     * Задаване на път до изпълнима външна програма
     * 
     * @param string $name
     * @param string $binPath
     * @param boolean $escape
     */
    function setProgram($name, $binPath, $escape=TRUE)
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
    function setProgramPath($name, $path)
    {
        $this->programPaths[$name] = $path;
    }
    
    
    /**
     * Задаване на друго общи за целия скрипт параметри
     */
    function setParam($placeHolder, $value=NULL, $escape=TRUE)
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
     * @param boolean $silent
     * 
     * @return string
     */
    public function getCmdLine($cmdLine, $silent = FALSE)
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
     * Добавя извикване на външна програма в текста на скрипта
     * 
     * @param string $cmdLine
     * @param array $params
     * @param boolean $addTimeLimit
     */
    function lineExec($cmdLine, $params = array(), $addTimeLimit = TRUE)
    {
        $this->cmdLine[] = $cmdLine;
        $this->lineParams[] = $params;
        
        $cmdLine = $this->getCmdLine($cmdLine, TRUE);
        
        $cmdArr = explode(' ', $cmdLine);
        $program = $cmdArr[0];
        $binPath = $this->programs[$program] ? $this->programs[$program] : $program;
        $cmdArr[0] = $binPath;
        $cmdLine = implode(' ', $cmdArr);
        
        if (count($this->cmdParams)) {
            foreach ($this->cmdParams as $placeHolder => $value) {
                $cmdLine = str_replace("[#{$placeHolder}#]", $value, $cmdLine);
            }
        }
        
        if ($addTimeLimit && $cmdLine) {
            $conf = core_Packs::getConfig('fconv');
            if ($conf->FCONV_USE_TIME_LIMIT == 'yes') {
                
                $timeLimitArr = explode(' ', $conf->FCONV_TIME_LIMIT);
                
                $timeLimitArr[0] = escapeshellcmd($timeLimitArr[0]);
                
                foreach ($timeLimitArr as $key => &$val) {
                    if ($key == 0) continue;
                    if ($val{0} == '-') continue;
                    
                    $val = escapeshellarg($val);
                }
                
                $cmdLine = implode(' ', $timeLimitArr) . ' ' . $cmdLine;
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
    function nl($cmdLine)
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
    function lineSH($cmd)
    {
        if (stristr(PHP_OS, 'WIN')) return ;
        
        $this->script .= $this->nl($cmd);
    }
    
    
    /**
     * Добавя линия Visual Basic Script. Изпълнява се само ако текущата OS е Windows
     * 
     * @param string $cmd
     */
    function lineVBS($cmd)
    {
        if (!stristr(PHP_OS, 'WIN')) return ;
        
        $this->script .= $this->nl($cmd);
    }
    
    
    /**
     * Добавя текст в скрипта, който извиква указания callback
     * 
     * @param string $callback
     */
    function callBack($callback)
    {
        $this->callBack[] = $callback;
        
        Request::setProtected('pid, func');
        
        $url = toUrl(array('fconv_Processes',
                'CallBack', 'func' => $callback, 'pid' => $this->id), 'absolute');
        
        $cmdLine = "wget -q --spider --no-check-certificate '{$url}'";
        $this->setCheckProgramsArr('wget');
        
        $this->lineExec($cmdLine, array('skipOnRemote' => TRUE));
    }
    
    
    /**
     * Задаваме програми, които ще се проверяват преди да се пусни обработка
     */
    public function setCheckProgramsArr($programs)
    {
        $programs = arr::make($programs, TRUE);
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
     * @param boolean $asynch
     * @param integer $time
     * @param string $timeoutCallback
     */
    function run($asynch=TRUE, $time = 2, $timeoutCallback = '')
    {
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
                    
                    log_System::add('fconv_Remote', "Отдалечен скрипт: " . $url, $rRec->id);
                    
                    return ;
                }
            }
        }
        
        $checkProgramsArr = $this->getCheckProgramsArr();

        // Ако са зададени програми, които да се проверят преди обработка.
        if (!empty($checkProgramsArr)) {
            foreach ($checkProgramsArr as $program) {
                if (isset($this->programs[$program])) {
                    $path = $this->programs[$program];
                } else {
                    $path = escapeshellcmd($program);
                }
                
                exec($path . ' --help', $output, $code);
                
                if ($code == 127 || ($code == 1)) {
                    if ($code == 1) {
                        exec($path . ' -h', $output, $code);
                        
                        if ($code === 0) continue ;
                        
                        if ($code == 1) {
                            
                            exec("which {$path}", $output, $code);
                            
                            if ($code === 0) continue ;
                        }
                    }
                    
                    log_System::add('fconv_Remote', "Липсва програма: " . $path, $rRec->id, 'warning');
                    
                    return FALSE;
                }
            }
        }
        
        if (!stristr(PHP_OS, 'WIN')) {
            $this->script = "#!/bin/bash \n" . $this->script;
        }
        
        expect(mkdir($this->tempDir, 0777, TRUE));
        
        $foldersArr = $this->getFolders();
        
        if (!empty($foldersArr)) {
            foreach ((array)$foldersArr as $placeHolder => $folderName) {
                $nFolderPath = $this->tempDir . $folderName;
                @mkdir($nFolderPath, 0777, TRUE);
                $this->script = str_replace("[#{$placeHolder}#]", escapeshellarg($nFolderPath), $this->script);
            }
        }
        
        if (count($this->files)){
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
        
        log_System::add('fconv_Script', "Стартиран скрипт: " . $this->script);
        
        pclose(popen($shell, "r"));
    }
    
    
    /**
     * Проверява и генерира уникално име на файла
     * 
     * @param string $fname
     * @param string $fpath
     * @return string|boolean
     */
    function getUniqName($fname, $fpath)
    {
        // Циклим докато генерираме име, което не се среща до сега
        $fn = $fname;
        
        if (!is_dir($fn)) {
            if(($dotPos = mb_strrpos($fname, '.')) !== FALSE) {
                $firstName = mb_substr($fname, 0, $dotPos);
                $ext = mb_substr($fname, $dotPos);
            } else {
                $firstName = $fname;
                $ext = '';
            }
            
            $i = 0;
            $files = scandir($this->tempDir);
            
            while(in_array($fn, $files)) {
                $fn = $firstName . '_' . (++$i) . $ext;
            }
            
            return $fn;
        }
        
        return FALSE;
    }
    
    
    /**
     * Копира избрания файл или създава софт линк под Linux
     * 
     * @param string $fileName
     * @param string $filePath
     * @return boolean
     */
    function copy($fileName, $filePath) {
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
        
        return TRUE;
    }
    
    
    /**
     * Добавя разширение в зависимост от ОС, към файла на скрипта
     * 
     * @return string
     */
    function addExtensionScript()
    {
        if (stristr(PHP_OS, 'WIN')) {
            
            return '.bin';
        }
        
        return '.sh';
    }
    
    
    /**
     * Добавя разширение за асинхронно стартиране на скрипта за Линукс
     * 
     * @return string
     */
    function addRunAsinchronLinux()
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
    function addRunAsinchronWin()
    {
        if (stristr(PHP_OS, 'WIN')) {
            
            return 'start ';
        }
        
        return '';
    }
}
