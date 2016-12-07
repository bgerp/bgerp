<?php


/**
 * Отдалечени системи за конвертиране
 *
 *
 * @category  bgep
 * @package   fconv
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fconv_Remote extends core_Manager
{
    
    
    /**
     * Име на директорията, където ще се свалят файловете
     */
    protected static $remoteDownloadedFilesDir = 'remoteDownloadedFiles';
    
    
    /**
     * Време на заключване на едни и същи заявки
     */
    protected static $lockTime = 200;
    
    
    /**
     * Заглавие на модула
     */
    public $title = "Отдалечени системи за конвертиране";
    
    
    /**
     * Плъгини, които ще се зареждат
     */
    public $loadList = 'plg_Created, fconv_Wrapper, plg_RowTools2';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('command', 'varchar(ci)', 'caption=Команда, mandatory');
        $this->FLD('address', 'url', 'caption=Адрес, mandatory');
        
        $this->setDbUnique('command');
    }
    
    
    /**
     * Проверва дали програмта може да се пусне отдалечено
     * Дали текущия хост не е в частна мрежа и дали има такава команда и адрес
     * 
     * @param string $commandName
     * 
     * return boolean
     */
    public static function canRunRemote($commandName)
    {
        
        return (boolean)(fconv_Remote::getRemoteCommand($commandName));
    }
    
    
    /**
     * Връща запис за отдалечената команда
     * 
     * @param string $commandName
     * 
     * @return FALSE|stdObject
     */
    public static function getRemoteCommand($commandName)
    {
        
        return self::fetch(array("#command = '[#1#]'", $commandName));
    }
    
    
    /**
     * Подготвя файловете, които ще се използват при отдалечено конвертиране
     * 
     * @param unknown $scriptInst
     */
    public static function prepareFiles(&$scriptInst)
    {
        foreach ($scriptInst->files as $key => &$file) {
            if (strstr($file, '/')) {
                if (!is_file($file)) continue;
                
                $scriptInst->fileName[$key] = basename($file);
                $file = fileman_Download::getDownloadUrl($file, '1', 'path');
            } else {
                $scriptInst->fileName[$key] = fileman_Files::fetchByFh($file, 'name');
                $file = toUrl(array('F', 'D', $file), 'absolute');
            }
        }
    }
    
    
    /**
     * Callback функция, която се вика след приключване на конвертирането
     * Подготвя конвертираните файлове за сваляне и извиква URL към инициатора на събонитето с подадените файлове
     * 
     * @param stdClass $script
     * 
     * @return string
     */
    public static function afterRemoteConv($script)
    {
        $filesArr = self::prepareConvertedFiles($script);
        
        $files = urlencode(core_Crypt::encodeVar($filesArr, fconv_Setup::get('SALT')));
        $cUrl = $script->remoteAfterConvertCallback . '&files=' . $files;
        
        return file_get_contents($cUrl);
    }
    
    
    /**
     * Сваля подадения файл от URL-то във файла
     * 
     * @param string $fPath
     * @param string $url
     */
    protected static function downloadFilesFromUrl($fPath, $url)
    {
        expect(URL::isValidUrl($url));
        
        $dir = dirname($fPath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0777, TRUE);
        }
        
        expect(is_dir($dir) && is_writable($dir));
        
        core_App::setTimeLimit(300);
        $fp = fopen($fPath, 'w+'); // Отваряме файл във временната директория където ще се копира
        $curl = curl_init(str_replace(" ","%20", $url));
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        curl_setopt($curl, CURLOPT_FILE, $fp); // записваме отговора във временния файл
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($curl);
        curl_close($curl);
        fclose($fp);
    }
    
    
    /**
     * Връща пътя до директорията, където ще се свалят файловете преди конвертиране
     * 
     * @return string
     */
    protected static function getRemoteDownloadedPath()
    {
        
        return fconv_Setup::get('TEMP_PATH') . '/' . self::$remoteDownloadedFilesDir;
    }
    
    
    /**
     * Подготвя отдалечените файлове
     * Сваля файловете в директотирията и ги сетва в новия скрипт
     * 
     * @param stdClass $scripInst
     * @param stdClass $nScript
     */
    protected static function prepareRemoteFiles($scripInst, $nScript)
    {
        $urlType = cls::get('type_Url');
        
        foreach ($scripInst->files as $key => $fileUrl) {
            if (URL::isValidUrl($fileUrl)) {
                $fPath = self::getRemoteDownloadedPath() . '/' . str::getRand() . '/' . $scripInst->fileName[$key];
                self::downloadFilesFromUrl($fPath, $fileUrl);
            } else {
                $fPath = str_replace($scripInst->tempDir, $nScript->tempDir, $fileUrl);
            }
            
            $nScript->setFile($key, $fPath);
        }
    }
    
    
    /**
     * Подготвя пътищата до изпълнимите програми
     * 
     * @param stdClass $scriptObj
     * @param stdClass $nScriptObj
     */
    protected static function prepareRemotePrograms($scriptObj, $nScriptObj)
    {
        $resArr = array();
        
        foreach ($scriptObj->programPaths as $clsName => $paramName) {
            
            $clsInst = cls::get($clsName);
            
            foreach ((array)$clsInst->$paramName as $name => $path) {
                list($cls, $val) = explode('::', $path);
                if (!cls::load($cls, TRUE)) continue;
                
                $clsInst = cls::get($cls);
                $bPath = $clsInst->get($val, TRUE);
                
                if (!$bPath) continue;
                
                $nScriptObj->setProgram($name, $bPath);
            }
        }
    }
    
    
    /**
     * Подготвя папките
     * 
     * @param stdClass $scriptObj
     * @param stdClass $nScriptObj
     */
    protected static function prepareRemoteFolders($scriptObj, $nScriptObj)
    {
        foreach ($scriptObj->folders as $place => $val) {
            $nScriptObj->setFolders($place, $val);
        }
    }
    
    
    /**
     * Подготвя параметрите за програмата
     * 
     * @param stdClass $scriptObj
     * @param stdClass $nScriptObj
     */
    protected static function prepareRemoteCmdParams($scriptObj, $nScriptObj)
    {
        foreach ($scriptObj->cmdParamsOrig as $place => $val) {
            $nScriptObj->setParam($place, $val);
        }
    }
    
    
    /**
     * Подготвя реда, който ще се стартира
     * 
     * @param stdClass $scriptObj
     * @param stdClass $nScriptObj
     */
    protected static function prepareRemoteLineExec($scriptObj, $nScriptObj)
    {
        foreach ($scriptObj->cmdLine as $key => $val) {
            
            // Ако няма да се добавя при отдалечено стартиране
            if ($scriptObj->lineParams[$key]['skipOnRemote']) continue;
            
            $val = $nScriptObj->getCmdLine($val, FALSE);
            
            $lineParam = str_replace($scriptObj->tempDir, $nScriptObj->tempDir, $scriptObj->lineParams[$key]);
            $nScriptObj->lineExec($val, $lineParam);
        }
    }
    
    
    /**
     * Подготвя конвертираните файлове, като връща името и линк за сваляне
     * 
     * @param stdClass $script
     * 
     * @return array
     */
    protected static function prepareConvertedFiles($script)
    {
        $tempDir = $script->tempDir;
        
        $res = array();
        
        try {
            // Вземаме итератор
            // RecursiveIteratorIterator::SELF_FIRST - Служи за вземане и на директориите
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir), RecursiveIteratorIterator::SELF_FIRST);
        } catch (ErrorException $e) {
        
            reportException($e);
        
            return $res;
        }
        
        // Сетваме флаговете
        // NEW_CURRENT_AND_KEY = FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::CURRENT_AS_FILEINFO
        // FilesystemIterator::KEY_AS_FILENAME - ->key() да връща името на файла
        // FilesystemIterator::CURRENT_AS_FILEINFO - ->current() да връща инстанция на SplInfo
        // FilesystemIterator::SKIP_DOTS - Прескача . и ..
        $iterator->setFlags(FilesystemIterator::NEW_CURRENT_AND_KEY | FilesystemIterator::SKIP_DOTS);
        
        $tempDir = rtrim($tempDir, '/');

        // Обхождаме итератора
        while($iterator->valid()) {
        
            // Вземаме името на файла
            $fileName = $iterator->key();
            
            // Вземаме пътя
            $path = $iterator->current()->getPath();
            
            $tDir = rtrim($script->tempDir, '/');
            $path = rtrim($path, '/');
            
            // Вземаме релативния път до файла
            $dir = str_ireplace($tDir, '', $path);
            
            // Ако няма път, за да не е празна стойност
            if (!$dir) {
                $dir = '/';
            }
            
            // Ако няма такъв запис
            if (!isset($res[$dir])) {
                $res[$dir] = array();
            }
            
            // Ако е директория
            if (!$iterator->isDir()) {
                
                $fPath = $path . '/' . $fileName;
                
                // Ако е зададено да се прескача и да не се връща файла обратно към инициатора
                if (isset($script->skipOnRemote[$fPath])) {
                    $iterator->next();
                    continue;
                }
                
                $res[$dir][$fileName] = fileman_Download::getDownloadUrl($fPath, '1', 'path');
            }
        
            // Преместваме итератора
            $iterator->next();
        }
        
        return $res;
    }
    
    
    /**
     * Стартира конвертирането на файла
     * Вика се отдалечено (автоматично) от инициатора на конвертирането
     */
    function act_Convert()
    {
        $script = Request::get('script');
        
        expect($script);
        
        expect(core_Locks::get($script, self::$lockTime));
        
        $scriptObj = core_Crypt::decodeVar($script, fconv_Setup::get('SALT'));
        
        expect($scriptObj);
        
        $nScript = cls::get('fconv_Script');
        self::prepareRemoteFiles($scriptObj, $nScript);
        self::prepareRemotePrograms($scriptObj, $nScript);
        self::prepareRemoteFolders($scriptObj, $nScript);
        self::prepareRemoteCmdParams($scriptObj, $nScript);
        self::prepareRemoteLineExec($scriptObj, $nScript);
        
        $nScript->params = $scriptObj->params;
        $nScript->runAsynch = $scriptObj->runAsynch;
        $nScript->callBack('fconv_Remote::afterRemoteConv');
        $nScript->remoteAfterConvertCallback = $scriptObj->remoteAfterConvertCallback;
        
//         $nScript->stopRemote = TRUE;
        
        $nScript->run($nScript->params['asynch']);
    }
    
    
    /**
     * Callback фунцкия, която се вика от инициатора на конвертиранието
     * Сваля файловете в директорията и извиква първоначалната callback функция,
     * която знае какво да прави с файловете
     */
    function act_AfterConvertCallback()
    {
        $pid = Request::get('pid');
        $files = Request::get('files');
        
        expect($pid && $files);
        
        expect(core_Locks::get($files, self::$lockTime));
        
        $rRec = fconv_Processes::fetch(array("#processId = '[#1#]'", $pid));
        
        $filesArr = core_Crypt::decodeVar($files, fconv_Setup::get('SALT'));
        
        expect($filesArr !== FALSE);
        
        $scriptObj = unserialize($rRec->script);
        
        // Създаваме същата директория (и свяля файловете), които биха се получили при локална обработка
        // Това е с цел callBack функцията да работи коректно
        foreach ($filesArr as $dir => $files) {
            
            $path = rtrim($scriptObj->tempDir, '/');
            $path .= $dir;
            expect(mkdir($path, 0777, TRUE));
            
            foreach ($files as $fName => $fUrl) {
                
                $path = rtrim($path, '/');
                $fPath = $path . '/' . $fName;
                self::downloadFilesFromUrl($fPath, $fUrl);
            }
        }
        
        // Извикваме callBack функците, за съответната обработка
        foreach ($scriptObj->callBack as $cb) {
            fconv_Processes::runCallbackFunc($pid, $cb);
        }
    }
    
    
    /**
     * Функция, която се изпълнява от крона и изтрива старите свалени файлове
     * 
     * @return void|string
     */
    function cron_DeleteOldDir()
    {
        $dirName = self::getRemoteDownloadedPath();
        
        if (!is_dir($dirName) || !is_readable($dirName)) return ;
        
        $delCnt = core_Os::deleteOldFiles($dirName, 2 * 60 * 60);
        
        if($delCnt) {
            $res = "<li class=\"green\">Изтрити са {$delCnt} файла в {$dirName}</li>";
            
            // След изтвиване, ако има директории без файлове - премахваме ги
            $allFiles = core_Os::listFiles($dirName);
            foreach ((array)$allFiles['dirs'] as $dPath) {
                $dPathList = core_Os::listFiles($dPath);
                if (!empty($dPathList['files'])) continue;
                
                core_Os::deleteDir($dPath);
            }
            
            return $res;
        }
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     * 
     * @param fconv_Remote $mvc
     * @param string $res
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'deleteOldDir';
        $rec->description = 'Изтриване на стари директории/файлове за конвертиране';
        $rec->controller = $mvc->className;
        $rec->action = 'deleteOldDir';
        $rec->period = 1440; // 24h;
        $rec->offset = rand(60, 240); // от 2 до 3h;
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
    }
}
