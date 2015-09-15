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
    var $files = array();
    
    
    /**
     * Масив за папките
     */
    protected $folders = array();
    
    
    /**
     * @param array programs - Масив за изпълнимите команди
     */
    var $programs = array();
    
    
    /**
     * @param array params - Масив за параметрите на скрипта
     */
    var $params = array();
    
    
    /**
     * @param string script - Текст на скрипта
     */
    var  $script;
    
    
    /**
     * Инициализиране на уникално id
     */
    function fconv_Script($tempDir = NULL)
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
                log_Data::add('err', "Файлът не съществува: '{$file}'", 'fconv_Script');
            }
        }
    }
    
    
    /**
     * Задаване на път до изпълнима външна програма
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
     * Задаване на друго общи за целия скрипт параметри
     */
    function setParam($placeHolder, $value=NULL, $escape=TRUE)
    {
        if ($escape) {
//            $this->params[$placeHolder] = escapeshellcmd($value);
            $this->params[$placeHolder] = escapeshellarg($value);
        } else {
            $this->params[$placeHolder] = $value;
        }
    }
    
    
    /**
     * Добавя извикване на външна програма в текста на скрипта
     */
    function lineExec($cmdLine, $params = array(), $addTimeLimit = TRUE)
    {
        $cmdArr = explode(' ', $cmdLine);
        $program = $cmdArr[0];
        $binPath = $this->programs[$program] ? $this->programs[$program] : $program;
        $cmdArr[0] = $binPath;
        $cmdLine = implode(' ', $cmdArr);
        
        if (count($this->params)) {
            foreach ($this->params as $placeHolder => $value) {
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
        if ($params['errFilePath']) {
            $cmdLine .= ' > ' . escapeshellarg($params['errFilePath']) . ' 2>&1';
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
     */
    function lineSH($cmd)
    {
        if (stristr(PHP_OS, 'WIN')) {
            return ;
        }
        $this->script .= $this->nl($cmd);
    }
    
    
    /**
     * Добавя линия Visual Basic Script. Изпълнява се само ако текущата OS е Windows
     */
    function lineVBS($cmd)
    {
        if (!stristr(PHP_OS, 'WIN')) {
            return ;
        }
        $this->script .= $this->nl($cmd);
    }
    
    
    /**
     * Добавя текст в скрипта, който извиква указания callback
     */
    function callBack($callback)
    {
        $url = toUrl(array('fconv_Processes',
                'CallBack', 'func' => $callback, 'pid' => $this->id), 'absolute');
        
        if (stristr(PHP_OS, 'WIN')) {
        
        } else {
            $cmdLine = "wget -q --spider --no-check-certificate '{$url}'";
        }
        
        $this->lineExec($cmdLine);
    }
    
    
    /**
     * изпълнява скрипта, като му дава време за изпълнение
     */
    function run($asynch=TRUE, $time = 2, $timeoutCallback = '')
    {
        
        if (!stristr(PHP_OS, 'WIN')) {
            $this->script = "#!/bin/bash \n" . $this->script;
        }
        
        expect(mkdir($this->tempDir, 0777, TRUE));
        
        $foldersArr = $this->getFolders();
        
        if ($foldersArr) {
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
        $fh = fopen($shellName, 'w') or die("can't open file");
        fwrite($fh, $this->script);
        fclose($fh);
        
        $rec = new stdClass();
        $rec->processId = $this->id;
        $rec->start = dt::verbal2mysql();
        $rec->script = serialize($this);
        $rec->timeOut = $time;
        $rec->callBack = $timeoutCallback;
        fconv_Processes::save($rec);
        
        chmod($shellName, 0777);
        
        // Ако е зададено да се стартира асинхронно
        if ($asynch) {
            $shell = $this->addRunAsinchronWin() . $shellName . $this->addRunAsinchronLinux();    
        } else {
            $shell = $shellName;    
        }
        
        log_Debug::add('fconv_Script', NULL, "Стартиран скрипт: " . $this->script);
        
        pclose(popen($shell, "r"));
    }
    
    
    /**
     * Проверява и генерира уникално име на файла
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
        }
        
        return TRUE;
    }
    
    
    /**
     * Добавя разширение в зависимост от ОС, към файла на скрипта
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
     */
    function addRunAsinchronWin()
    {
        if (stristr(PHP_OS, 'WIN')) {
            
            return 'start ';
        }
        
        return '';
    }
}
