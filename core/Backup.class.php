<?php

/**
 * Максимален брой паралелни нишки при бекъп
 */
defIfNot('BACKUP_MAX_THREAD', 20);

/**
 * Максимален брой паралелни нишки при възстановяване
 */
defIfNot('RESTORE_MAX_THREAD', 10);


/**
 * Клас 'core_Backup' - добавя бекъп възможности към ядрото
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Backup extends core_Mvc
{
    /**
     * Последно време за модифициране на всички таблици
     */
    public $lmt = array();
    
    
    /**
     * Информация за всички таблици
     */
    public static $info = array();
    
    
    /**
     * Кеширане на контролните суми
     */
    public static $crcArr = array();
    
    
    /**
     * Директория за временни файлове
     */
    public static $tempDir;
    
    
    /**
     * Директория за бекъпи и sql логове
     */
    public static $backupDir;
    

    /**
     * Създаване на пълен бекъп
     */
    public function cron_Create()
    {
        //core_Debug::$isLogging = false;
        
        if (core_Setup::get('BACKUP_ENABLED') != 'yes') {
            
            return;
        }

        $file = self::getTempPath('log.txt');
        if(file_exists($file)) {
            unlink($file);
        }

        core_App::setTimeLimit(120);
        
        // Мета-данни за бекъпа
        $description = array();
        $description['times']['start'] = dt::now();
        
        // Парола за създаване на архивните файлове
        $pass = core_Setup::get('BACKUP_PASS');
        
        // Форсираме директориите
        $backDir = self::getBackupPath();
        $workDir = self::getTempPath();
        
        // Определяме всички mvc класове, на които ще правим бекъп
        $mvcArr = core_Classes::getOptionsByInterface('core_ManagerIntf');
        $instArr = array();
        $lockTables = '';
        
        foreach ($mvcArr as $className) {
            if (!cls::load($className, true)) {
                continue;
            }
            
            // Инстанцираме класа
            $mvc = cls::get($className);
            
            // Пропускаме класовете, които имат модели в други бази данни
            if (!self::hasEqualDb($this, $mvc)) {
                continue;
            }
            
            if ($mvc->dbTableName) {
                list($exists, $cnt, $lmt) = $this->getTableInfo($mvc);
                if ($exists && !isset($instArr[$mvc->dbTableName])) {
                    $instArr[$mvc->dbTableName] = null;
                }
            }
            if (!$mvc->dbTableName || (isset($mvc->doReplication) && !$mvc->doReplication) || !$exists || !$cnt || isset($instArr[$mvc->dbTableName])) {
                continue;
            }
            $instArr[$mvc->dbTableName] = $mvc;
            $this->lmt[$mvc->dbTableName] = $lmt;
            $lockTables .= ",`{$mvc->dbTableName}` READ";
        }
        
        uksort($instArr, array($this, 'compLmt'));
        
        // Правим пробно експортиране на всички таблици, без заключване
        $tables = array();
        $this->exportTables($instArr, $tables, time() - 3600);
        
       
        // Пускаме завесата
        $lockTables = trim($lockTables, ',');
        core_SystemLock::block('Процес на архивиране на данните', 600); // 10 мин.
        $description['times']['lock'] = dt::now();
                
        $this->db->query("LOCK TABLES {$lockTables}");
        
        // Изтриваме статистическата информация за таблиците, за да се генерира на ново
        self::$info = array();
        
        // Флъшваме всичко, каквото има от SQL лога
        $this->cron_FlushSqlLog();
        
        // Записваме времето на бекъпа
        $description['time'] = dt::now();
        
        // Експортираме всички таблици, като зачистваме масива
        $tables = array();
        $this->exportTables($instArr, $tables);
        
        // Освеобождаваме LOCK-а на таблиците
        $this->db->query('UNLOCK TABLES');
        
        // Освобождаваме системата
        core_SystemLock::remove();
        $description['times']['unlock'] = dt::now();
        
        // SQL структура на базата данни
        $dbStructure = '';
        
        // Запазваме структурата на базата със всички таблици
        debug::log($msg = 'Генериране SQL за структурата на базата');
        self::fLog($msg);
        foreach ($instArr as $table => $inst) {
            $query = "SHOW CREATE TABLE `{$table}`";
            $dbRes = $this->db->query($query);
            $res = $this->db->fetchArray($dbRes);
            $dbStructure .= "\n" . array_values($res)[1] . ';';
        }
        
        if ($dbStructure = trim($dbStructure)) {
            $hash = base_convert(abs(crc32($dbStructure)), 10, 36);
            $file = "dbstruct.{$hash}.sql";
            $path = $workDir . $file;
            $dest = $backDir . $file . '.zip';
            if (!file_exists($dest)) {
                file_put_contents($path, $dbStructure);
                debug::log($msg = ('Компресиране на ' . basename($dest)));
                self::fLog($msg);
                archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
            }
            $description['dbStruct'] = $file . '.zip';
        }
        
        // Правим описание на файловете с експортатите данни
        foreach ($tables as $table) {
            $description['files'][] = "{$table}.csv.zip";
        }
        
        // Бекъп на двата конфиг файла
        $indCfg = rtrim(EF_INDEX_PATH, '/\\') . '/index.cfg.php';
        if (file_exists($indCfg)) {
            expect(is_readable($indCfg));
            $hash = base_convert(md5_file($indCfg), 16, 36);
            $file = "index.{$hash}.cfg.php";
            $tmpFile = $workDir . $file;
            copy($indCfg, $tmpFile);
            $indZip = $backDir . $file . '.zip';
            if (!file_exists($indZip)) {
                archive_Adapter::compressFile($tmpFile, $indZip, $pass, '-sdel');
            }
            $description['indexConfig'] = $file . '.zip';
        }
        
        $appCfg = rtrim(EF_CONF_PATH, '/\\') . '/' . EF_APP_NAME . '.cfg.php';
        expect(file_exists($appCfg) && is_readable($appCfg));
        $hash = base_convert(md5_file($appCfg), 16, 36);
        $file = "app.{$hash}.cfg.php";
        $tmpFile = $workDir . $file;
        copy($appCfg, $tmpFile);
        $appZip = $backDir . $file . '.zip';
        if (!file_exists($appZip)) {
            archive_Adapter::compressFile($tmpFile, $appZip, $pass, '-sdel');
        }
        $description['appConfig'] = $file . '.zip';
        
        // всема стойностите на някои константи
        $constArr = array('EF_SALT', 'EF_USERS_PASS_SALT', 'EF_USERS_HASH_FACTOR');
        foreach ($constArr as $const) {
            if (defined($const)) {
                $description['const'][$const] = constant($const);
            }
        }
        
        // Записваме времето за финиширане на бекъпа
        $description['times']['finish'] = dt::now();
        
        // Записване на файла с описанието на бекъпа
        if ($descriptionStr = json_encode($description)) {
            $hash = base_convert($md5 = md5($descriptionStr), 16, 36);
            $file = "description.{$hash}.json";
            $path = $workDir . $file;
            $dest = $backDir . $file . '.zip';
            if (!file_exists($dest)) {
                file_put_contents($path, $descriptionStr);
                debug::log($msg = ('Компресиране на ' . basename($dest)));
                self::fLog($msg);
                archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
            }
        }
        
        // Почистваме всички ненужни файлове от бекъпите, които са в повече
        $backupMaxCnt = core_Setup::get('BACKUP_MAX_CNT');
        
        $log = array();
        $descrArr = self::discover($backDir, $pass, $log);
        
        $used = array();
        $minTime = time();
        foreach ($descrArr as $path => $descr) {
            foreach ($descr->files as $file) {
                $used[$file] = true;
            }
            $used[basename($path)] = true;
            $used[$descr->appConfig] = true;
            $used[$descr->indexConfig] = true;
            $used[$descr->dbStruct] = true;
            $minTime = min($minTime, $descr->time);
            $backupMaxCnt--;
            if (!$backupMaxCnt) {
                break;
            }
        }
        
        // Вземаме всички файлове, кито са от вида на използваните в архива
        $files = glob("{$backDir}*.{csv.zip,cfg.php.zip,json.zip,sql.zip}", GLOB_BRACE);
        foreach ($files as $path) {
            $name = basename($path);
            if ($used[$name]) {
                continue;
            }

            // Оставяме SQL-логовете, които са с време на създаване по-голямо от текущото?
            if (substr($name, 0, 4) == 'log.') {
                $time = self::getTimeFromFilename($name);
                
                if ($time > $minTime) {
                    continue;
                }
            }
            
            @unlink($path);
        }
    }
    
    
    /**
     * Извлича информация за времето от името на файла
     */
    public static function getTimeFromFilename($name)
    {
        $m = array();
        
        preg_match('/(\\d{4})[\\-_ ](\\d{2})[\\-_ ](\\d{2})[\\-_ ](\\d{2})[\\-_ ](\\d{2})[\\-_ ](\\d{2})/', $name, $m);
        
        $res = $m[1] . '-' . $m[2] . '-' . $m[3] . ' ' . $m[4] . ':' . $m[5] . ':' . $m[6];
        
        return $res;
    }
    
    
    /**
     * Експортира всички таблици, като CSV файлове в работната директория
     */
    public function exportTables($instArr, &$tables, $maxLmt = null)
    {
        self::fLog("Начало на експортирането на таблиците");

        if(!isset($maxLmt)) {
            $maxLmt = time();
        }

        // Изчистваме останали процесни инфикатори
        $processes = glob(self::getTempPath() . '*.bpr');
        if(is_array($processes)) {
            foreach($processes as $file) {
                unlink($file);
            }
        }
        $pass = core_Setup::get('BACKUP_PASS');
        $addCrc32 = crc32(EF_SALT . $pass);
        
        foreach ($instArr as $table => $inst) {
            core_App::setTimeLimit(120);
            
            if ($inst === null) {
                continue;
            }
            
            list($exists, $cnt, $lmt) = $this->getTableInfo($inst);
            
            if($lmt > $maxLmt) continue;

            if (isset($inst->backupMaxRows, $inst->backupDiffFields)) {
                $maxId = $inst->db->getNextId($table);
                $diffFields = arr::make($inst->backupDiffFields);
                $expr = "CONCAT_WS('|'";
                foreach ($diffFields as $fld) {
                    $mySqlFld = str::phpToMysqlName($fld);
                    $expr .= ', `' . str::phpToMysqlName($fld) . '`';
                }
                $expr = "crc32(${expr}))";
                
                for ($i = 0; $i * $inst->backupMaxRows < $cnt; $i++) {
                    core_App::setTimeLimit(120);
                    $key = "{$table}-{$lmt}-" . ($i + 1);
                    if (!isset(self::$crcArr[$key])) {
                        $len = $inst->backupMaxRows;
                        $start = $i * $len;
                        $limit = " LIMIT {$start},{$len}";
                        $sql = "SELECT SUM(`_backup`) AS `_crc32backup` FROM  (SELECT  {$expr} AS `_backup` FROM `{$table}` ORDER BY `id`{$limit}) `_backup_table`";
                        
                        DEBUG::startTimer('Check table for changes: ' . $table);
                        $dbRes = $inst->db->query($sql);
                        $rec = $inst->db->fetchObject($dbRes);
                        DEBUG::stopTimer('Check table for changes: ' . $table);
                        
                        self::$crcArr[$key] = $rec->_crc32backup + $addCrc32;
                    }
                    
                    if (self::$crcArr[$key] > 0) {
                        $suffix = ($i + 1) . '-' . base_convert(abs(self::$crcArr[$key]), 10, 36);
                        $this->runBackupTable($inst, $table, $suffix, $limit);
                        $tables[] = "{$table}.{$suffix}";
                    }
                }
            } else {
                $suffix = base_convert($lmt + $addCrc32, 10, 36);
                $this->runBackupTable($inst, $table, $suffix);
                $tables[] = "{$table}.{$suffix}";
            }
        }
    }


    /**
     * извиква по http процес, който бекъпва съдържанието на една таблица
     */
    public function runBackupTable($inst, $table, $suffix, $limit = '')
    {
        $fileName = "{$table}.{$suffix}";
        $path = self::getTempPath($fileName . '.csv');
        $dest = self::getBackupPath($fileName . '.csv.zip');
        $tmpCsv = "{$path}.tmp";

        if (file_exists($dest)) {
            debug::log($msg = "Таблица `{$fileName}` вече съществува като zip файл");
            self::fLog($msg);

            return;
        }
        
        if (file_exists($path)) {
            debug::log($msg = "Таблица `{$fileName}` вече съществува като csv файл");
            self::fLog($msg);

            return;
        }

        if (file_exists($tmpCsv)) {
            debug::log($msg = "Таблица `{$fileName}` вече съществува като tmp файл");
            self::fLog($msg);

            return;
        }

        

        $className = cls::getClassName($inst);

        $params = "{$className}|{$table}|{$suffix}|{$limit}";

        // Изчакваме, докато има повече от BACKUP_MAX_THREAD процесни файла
        do {
            $processes = glob(self::getTempPath() . '*.bpr');
            if($processes === false) $processes = array();
            usleep(10000);
        } while(count($processes) >= BACKUP_MAX_THREAD);
           
        $url = toUrl(array('Index', 'default', 'SetupKey' => setupKey(), 'step' => "backup-{$params}"), 'absolute-force');
        $processFile = self::getTempPath("{$table}.bpr");
        file_put_contents($processFile, $params, FILE_APPEND);
        
        $cmd = escapeshellarg(EF_INDEX_PATH . '/index.php');
        $app = EF_APP_NAME;
        $ctr = 'core_Backup';
        $act = 'doBackupTable';
        

        core_Os::startCmd($msg = "php {$cmd} {$app} {$ctr} {$act} " . escapeshellarg($processFile));
        self::fLog($msg);
    }
    

    /**
     * Прави бекъп файл на конкретна таблица
     */
    public static function cli_doBackupTable()
    {
        global $argv;

        $processFile = $argv[4];
        $params = file_get_contents($processFile);
        self::$tempDir = dirname($processFile) .'/';
        list($className, $table, $suffix, $limit) = explode('|', $params);
        
        $inst = cls::get($className);

        // Подготвяме пътищата
        $fileName = "{$table}.{$suffix}";
        $path = self::getTempPath($fileName . '.csv');
        $dest = self::getBackupPath($fileName . '.csv.zip');
        $tmpCsv = "{$path}.tmp";

        // Вземаме паролата
        $pass = core_Setup::get('BACKUP_PASS');


        if (file_exists($dest)) {
            debug::log($msg = "Таблица `{$dest}` вече съществува като zip файл");
            self::fLog($msg);
            exit(0);
        }
        
        if (file_exists($tmpCsv)) {
            debug::log($msg = "Таблица `{$fileName}` вече съществува като tmp файл");
            self::fLog($msg);
            exit(0);
        }

        debug::log($msg = "Експорт в CSV на таблица `{$fileName}`");
        self::fLog($msg); 
        
        // Отваряме файла за писане
        $out = fopen($tmpCsv, 'w');

        // Извличаме информация за колоните
        $cols = '';
        $i = 0;
        $fields = $inst->db->getFields($table);
        foreach ($fields as $fRec) {
            list($type, ) = explode('(', $fRec->Type);
            if (strpos('|tinyint|smallint|mediumint|int|integer|bigint|float|double|double precision|real|decimal|', '|' . strtolower($type) . '|') === false) {
                $mustEscape[$i] = true;
            }
            $cols .= ($cols ? ',' : '') . '`' . $fRec->Field . '`';
            $i++;
        }
        
        $dbRes = $inst->db->query("SELECT * FROM `{$table}`{$limit}");
        
        fwrite($out, $cols);
        while ($row = $inst->db->fetchArray($dbRes, MYSQLI_NUM)) {
            $vals = '';
            foreach ($row as $i => &$f) {
                if ($f === null) {
                    $f = '\\N';
                } elseif ($mustEscape[$i]) {
                    $f = '"' . $inst->db->escape($f) . '"';
                }
            }
            $vals = implode(',', $row);
            fwrite($out, "\n" . $vals);
        }

        fclose($out);
        rename("{$path}.tmp", $path);

        self::fLog('Компресиране на ' . basename($dest));
        archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
        self::fLog('Край на компресиране на ' . basename($dest));

        unlink($processFile);

        die;
    }
    
    
    /**
     * Добавя mySQL заявките в SQL лога
     */
    public static function addSqlLog($sql)
    {
        try {
            if ($path = self::getSqlLogPath()) {
                @file_put_contents($path, $sql . ";\n\r", FILE_APPEND);
            }
        } catch (Exception $e) {
        }
    }
    
    
    /**
     * Флъшване на SQL лога към текущата бекъп директория
     */
    public static function cron_FlushSqlLog()
    {
        if (core_Setup::get('BACKUP_ENABLED') == 'yes') {
            $path = self::getSqlLogPath();
            
            // Регенерираме файлов флаг за това, дали се прави SQL лог
            core_SystemData::set('flagDoSqlLog');
            
            // Не може да се флъшва, а бекъпът е зададен
            if (!file_exists($path) || !is_readable($path) || !filesize($path)) {
                
                return;
            }
            $file = basename($path);
            $newFile = 'log.' . date('Y-m-d_H-i-s') . '.sql';
            $newPath = str_replace("/{$file}", "/{$newFile}", $path);
            rename($path, $newPath);
            $backDir = self::getBackupPath();
            $dest = $backDir . $newFile . '.zip';
            archive_Adapter::compressFile($newPath, $dest, core_Setup::get('BACKUP_PASS'), '-sdel');
        }
    }
    
    
    /**
     * Връща пътя до SLQ лога за текущата база
     */
    public static function getSqlLogPath()
    {
        static $path;
        
        if (!isset($path)) {
            if (core_SystemData::isExists('flagDoSqlLog')) {
                $path = self::getBackupPath(EF_DB_NAME . '.log.sql');
            } else {
                $path = false;
            }
        }
        
        return $path;
    }
    
    
    /**
     * Възстановява системата от направен бекъп
     */
    public static function restore(&$log, $tableAndSess)
    {
        core_Debug::$isLogging = false;
        
        $start = time();
         
        try {
            core_App::setTimeLimit(320);
            
            // Масив за съобщенията
            $log = array();
            
            // Път от където да възстановяваме
            $dir = core_Os::normalizeDir(BGERP_BACKUP_RESTORE_PATH) . '/';
            
            // Парола за разархивиране
            $pass = defined('BGERP_BACKUP_RESTORE_PASS') ? BGERP_BACKUP_RESTORE_PASS : '';
            
            // Вземаме манипулатора на базата данни
            $db = cls::get('core_Db');
            
            
            if($tableAndSess) {
                return self::doRestoreTable($tableAndSess, $db, $dir, $pass);
            }
 
            core_SystemLock::stopIfBlocked();
            
            // Първо очакваме празна база. Ако в нея има нещо - излизаме
            $dbRes = $db->query("SELECT count(*) AS tablesCnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$db->dbName}'");
            $res = $db->fetchArray($dbRes);
            
            
            if (array_values($res)[0] > 0) {
                $log[] = 'err: Базата не е празна. Преди възстановяване от бекъп в нея не трябва да има нито една таблица.';
                core_SystemLock::remove();
                
                return false;
            }
            
            // От тук нататък блокираме
            core_SystemLock::block('Възстановяване на структурата', 240);
            
            // Изтриваме стари сесии
            if (isset($_SESSION)) {
                session_destroy();
            }
            
            // Създаваме празна директория за отчитане на процесите, които наливат данните
            $sess = base_convert(rand(1000000, 99999999), 10, 36);
            $tempRestoreDir = self::getTempPath();            
            
            // Подготвяме структурата на базата данни
            $descrArr = self::discover($dir, $pass, $log);
            
            $description = array_values($descrArr)[0];
            
            $path = self::unzipToTemp($dir . $description->dbStruct, $pass, $log);
            $sql = file_get_contents($path);
            unlink($path);
            
            $log[] = $msg = 'msg: Създаване на структурата на таблиците';
            self::fLog($msg);

            $db->multyQuery($sql);
            
            // Наливаме съдържанието от всички налични CSV файлове
            // Извличаме от CSV последователно всички таблици
            $tablesCnt = countR($description->files);
            $log[] = $msg = 'msg: Извличанне на ' . $tablesCnt . ' таблици';
            self::fLog($msg);
            foreach ($description->files as $file) {
                self::runRestoreTable($file, $sess);
                $log[] = $msg = 'msg: Възстановяване на: ' . $file;
                self::fLog($msg);
                do {
                    $runned = self::getRuningProcess($tempRestoreDir);
                    $runnedCnt = countR($runned);
                    if($runnedCnt) {
                        core_SystemLock::block('Възстановяване на <li>' . implode("</li>\n<li>", $runned), ($cnt--) * 4 + 240);
                    }
                    if($runnedCnt > RESTORE_MAX_THREAD) {
                        usleep(1000); 
                    }
                } while($runnedCnt > RESTORE_MAX_THREAD);
            }
            
            // Наливане на наличните SQL логове
            $files = glob($dir . 'log.*.sql.zip');
            asort($files);
            
            $cnt = countR($files);
            foreach ($files as $src) {
                $time = self::getTimeFromFilename(basename($src));
                if ($time <= $description->time) {
                    continue;
                }
                $src = str_replace('\\', '/', $src);
                core_App::setTimeLimit(120);
                $dest = self::unzipToTemp($src, $pass, $log);
                $sql = file_get_contents($dest);
                $log[] = $msg = 'msg: Прилагане на ' . basename($src);
                self::fLog($msg);
                core_SystemLock::block('Възстановяване на ' . basename($src), ($cnt--) * 2 + 30);
                
                $db->multyQuery($sql);
                
                unlink($dest);
            }
            
            $log[] = $msg = 'msg: Възстановяването завърши успешно за ' . (time() - $start) . ' секунди';
            self::fLog($msg);

            core_SystemLock::remove();
  
            
            return true;
        } catch (core_exception_Expect $e) {
            $log[] = 'err: ' . ht::mixedToHtml(array($e->getMessage(), $e->getTraceAsString(), $e->getDebug(), $e->getDump()), 4);
        }
        
        core_SystemLock::remove();
       
    }
    
    /**
     * Извършва възстановяването на посочената таблица и хеш на директория
     * @param string  $fileAndSess
     * @param core_Db $db
     */
    public function doRestoreTable($fileAndSess, $db, $dir, $pass)
    {
        self::closeConnection();
        
        list($file, $sess) = explode('|', trim($fileAndSess, '-'));
        $tempRestoreDir = self::getTempPath();
        
        if(!is_dir($tempRestoreDir)) return;
        // Създаваме файл инфикатор, че процесът е започнал
        $logFile = $tempRestoreDir . $file . '.prc';
        $err = "Starting restore {$file}";
        file_put_contents($logFile,  $err . PHP_EOL , FILE_APPEND);
        self::fLog($err);

        $src = $dir . $file;
        core_App::setTimeLimit(1200);
        list($table, ) = explode('.', $file);
        
        $dest = self::unzipToTemp($src, $pass, $log);
        
        if(!$dest) {
            $err = "Usuccesfull unzipToTemp {$src}";
            file_put_contents($logFile, $err . PHP_EOL , FILE_APPEND);
            self::fLog($err);
        }

        $res = self::importTable($db, $table, $dest);
        
        file_put_contents($logFile, "Import: {$res}" . PHP_EOL, FILE_APPEND);
        
        unlink($dest);
        
        // rename($logFile, $tempRestoreDir . $file . '.OK');
        unlink($logFile);

        self::fLog("Importing {$file} has finished.");
        die;
    }
    
    /**
     * Прави извикване през Apache към себе си, за да се ресторне един файл
     * @param string $file
     * @param string $sess
     */
    public function runRestoreTable(string $file, $sess)
    {
        $url = toUrl(array('SetupKey' => $_GET['SetupKey'],'step' => "restore-{$file}|{$sess}"), 'absolute-force');
        $tempRestoreDir = self::getTempPath();
        $logFile = $tempRestoreDir . $file . '.prc';
        file_put_contents($logFile, "{$url}" . PHP_EOL , FILE_APPEND);
        $handle = fopen($url, "r");
        fread($handle, 1);
    }
    
    /**
     * Връща броя на файловете в посочената директория, които с определено разширение
     * 
     * @param string  $dir
     * @param string $suffix
     * @return array
     */
    public function getRuningProcess($dir, $suffix = '.prc')
    {
        $files = scandir($dir);

        foreach($files as $id => $file) {
            if(substr($file, -strlen($suffix)) != $suffix) {
                unset($files[$id]);
            }
        }
   
        return $files;
    }
    
    
    /**
     * Импортира таблица от CSV файл
     */
    public static function importTable($db, $table, $dest)
    {
        static $maxMysqlQueryLength;
        if (!isset($maxMysqlQueryLength)) {           
            $maxMysqlQueryLength = $db->getVariable('max_allowed_packet') / 2;            
        }
        
        $link = $db->connect();
        $handle = fopen($dest, 'r');
        $query = array();
        $totalLen = 0;
        $linesCnt = 0;
        if ($handle) {
            do {
                $line = fgets($handle);
                if ($line !== false) {
                    $line = rtrim($line, "\n\r");
                    $totalLen += strlen($line);
                    $linesCnt++;
                }
                if (!$cols) {
                    $cols = $line;
                    continue;
                }
                if ($line === false || ($totalLen > $maxMysqlQueryLength)) {
                    try {
                        if (strlen($line)) {
                            $query[] = $line;
                        }
                        $link->query("INSERT INTO `{$table}` ({$cols}) VALUES \n (" . implode("),\n(", $query) . ')');
                        
                        #file_put_contents("C:\\xampp\\htdocs\\ef_root\\uploads\\bgerp\\backup_work\query.log", $d);
                        //file_put_contents("/tmp/query.log", $queryStr);
                        $query = array();
                        $totalLen = 0;
                        continue;
                    } catch (Exception $e) {
                        fclose($handle);
                        $res = "err: Грешка при изпълняване на `INSERT INTO `{$table}` ({$cols}) VALUES  (" . implode(') (', array_slice($query, 0, 3)) .')`';
                        self::fLog($res);

                        return $res;
                    }
                }
                
                $query[] = $line;
            } while ($line !== false);
            fclose($handle);
            $res = 'msg: Импортиране на ' . $table . ' с общо ' . $linesCnt . ' линии';
            self::fLog($res);
        } else {
            // Не може да се отвори файла
            $res = "err: Не може да се отвори файла `{$dest}`";
            self::fLog($res);
        }
        
        gc_collect_cycles();
        
        return $res;
    }
    
    
    /**
     * Разархивира файл във времена директория и връща път до него
     *
     * @param string      $path Пътя до зипнатия файл
     * @param string|null $pass Парола за разархивиране
     *
     * @return string Пътят в темп директорията до файла
     */
    public static function unzipToTemp($path, $pass, &$log)
    {
        $temp = self::getTempPath();
        
        $file = basename($path);
        $tempPath = $temp . substr($file, 0, -4);
        
        expect(file_exists($path), $path);
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
        $log[] = "msg: Разкомпресиране на `{$file}`";
        
        $res = @archive_Adapter::uncompress($path, $temp, $pass);
        if ($res === 0 && file_exists($tempPath)) {
            
            return $tempPath;
        }
    }
    
    
    /**
     * Връща път до темп директория или до посочен в нея файл
     *
     * @param string $filename
     *
     * return string
     */
    public static function getTempPath($filename = '')
    {
        if(!isset(self::$tempDir)) {
            self::$tempDir = core_Os::normalizeDir(EF_TEMP_PATH) . '/backup/';
            if (!file_exists(rtrim(self::$tempDir, '/'))) {
                mkdir(self::$tempDir, 0744, true);
            }
        }
        
        return self::$tempDir . $filename;
    }
    

    /**
     * Връща път до бекъп директория или до посочен в нея файл
     *
     * @param string $filename
     *
     * return string
     */
    public static function getBackupPath($filename = '')
    {
        if(!isset(self::$backupDir)) {
            self::$backupDir = core_Os::normalizeDir(EF_UPLOADS_PATH) . '/backup/';
            if (!file_exists(rtrim(self::$backupDir, '/'))) {
                mkdir(self::$backupDir, 0744, true);
            }
        }
        
        return self::$backupDir . $filename;
    }

    
    /**
     * Поверява дали конфига е добре настроен
     */
    public static function checkConfig()
    {
        $res = '';

        if (core_Setup::get('BACKUP_ENABLED') != 'yes') {
            
            return;
        }
        
        $backupDir = core_Backup::getBackupPath();
        $res .= core_Os::hasDirErrors($backupDir, 'Директорията за backup ' . $backupDir);

        $tempDir = core_Backup::getTempPath();
        $res .= core_Os::hasDirErrors($tempDir, 'Временната директория за backup ' . $tempDir);

        return $res;
    }


    /**
     * Затваряме връзката, за да не чака викащия процес
     */
    public static function closeConnection()
    {
        // Затваряме връзката
        ignore_user_abort(true);
        if(session_id()) session_destroy();
        header('Connection: close');
        header('Content-Length: 2');
        header('Content-Encoding: none');
        echo 'OK';
        ob_end_flush();
        flush();
    }
    
    
    /**
     * Сравнява времето за модифициране на две таблици
     */
    public function compLmt($a, $b)
    {
        $aT = $this->lmt[$a];
        if (!$aT) {
            $aT = time();
        }
        $bT = $this->lmt[$b];
        if (!$bT) {
            $bT = time();
        }
        
        return $aT > $bT;
    }
    
    
    /**
     * Връща обща информация за посочена таблица
     *
     * @return array - $exists, $cnt, $lmt
     */
    public function getTableInfo($mvc)
    {
        $hash = md5($mvc->db->dbHost . '|' . $mvc->db->dbUser . '|' . $mvc->db->dbName);
        
        $selfHash = md5($this->db->dbHost . '|' . $this->db->dbUser . '|' . $this->db->dbName);
        
        if (!isset(self::$info[$hash]) && $hash == $selfHash) {
            self::$info[$hash] = array();
            $dbRes = $mvc->db->query("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA LIKE '{$mvc->db->dbName}'");
            while ($row = $mvc->db->fetchArray($dbRes)) {
                $lmt = $row['UPDATE_TIME'] ? strtotime($row['UPDATE_TIME']) : null;
                self::$info[$hash][$row['TABLE_NAME']] = array(true, $row['TABLE_ROWS'], $lmt);
            }
        }
        
        if (isset(self::$info[$hash][$mvc->dbTableName])) {
            $res = self::$info[$hash][$mvc->dbTableName];
        } else {
            $res = array(0, 0, null);
        }
        
        return $res;
    }
    
    
    /**
     * Дали са еднакви базите данни за двата модела
     */
    public static function hasEqualDb($mvc1, $mvc2)
    {
        $db1 = $mvc1->db1;
        $db2 = $mvc2->db2;
        
        $res = $db1->dbName == $db2->dbName && $db1->dbHost == $db2->dbHost && $db1->dbUser == $db2->dbUser;
        
        return $res;
    }
    
    
    /**
     * Открива всички валидни дескриптори на бекъп
     *
     * @param string $dir В коя директория да търси
     *
     * @return stdClass Обект, съдържащ
     *                   о name - Път до файла
     *                   о time - Време на създаване
     *                   о files - Масив с имена на файлове, които се съдържат в архива
     */
    public static function discover($dir, $pass, &$log)
    {
        $res = array();
        $mask = core_Os::normalizeDir($dir) . '/description.*.json.zip';
        $files = glob($mask);
        foreach ($files as $path) {
            $descPath = self::unzipToTemp($path, $pass, $log);
            if ($descPath && filesize($descPath)) {
                $description = json_decode(file_get_contents($descPath));
                unlink($descPath);
                if ($description) {
                    $res[$path] = $description;
                }
            }
        }
        
        uasort($res, function ($a, $b) {
            
            return $a->time < $b->time;
        });
        
        return $res;
    }


    /**
     * Добавя към лог файл съобщението
     */
    static function fLog($msg)
    {
        $file = self::getTempPath('log.txt');
        $msg = date('Y-m-d H:i:s') . ' ' . $msg . PHP_EOL;
        file_put_contents($file, $msg, FILE_APPEND);
    }
}
