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
 * Максимална дължина на експортираните данни (ориентировъчно)
 */
defIfNot('BACKUP_MAX_CHUNK_SIZE', 30000000);


/**
 * Максимална дължина на БЛОБ-овете, които могат да бъдат записани инлайн в базата
 */
defIfNot('BACKUP_MAX_INLINE_BLOB', 64000);


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
     * Броя редове в един партишън от таблицата за архивиране
     */
    public $chunks = array();

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
        core_Debug::$isLogging = false;
        
        if (core_Setup::get('BACKUP_ENABLED') != 'yes') {
            
            return;
        }

        $file = self::getTempPath('log.txt');
        if(file_exists($file)) {
            unlink($file);
        }

        //file_put_contents($file, 'fwefwe');
        //self::compressFile($file, $file . '.7z');
        //die;

        // Изчистваме стари процесорни индикатори
        $processes = glob(self::getTempPath() . '*.bpr');
        // Изчистваме останали процесни индикатори
        if(is_array($processes)) {
            foreach($processes as $file) {
                unlink($file);
            }
        }

        // Изчистваме стари темплейт индикатори
        $tplFiles = glob(self::getTempPath() . '*.tmp');
        // Изчистваме останали процесни индикатори
        if(is_array($tplFiles)) {
            foreach($tplFiles as $file) {
                unlink($file);
            }
        }

        // Изчистваме стари csv файлове
        $csvFiles = glob(self::getTempPath() . '*.csv');
        // Изчистваме останали процесни индикатори
        if(is_array($csvFiles)) {
            foreach($csvFiles as $file) {
                unlink($file);
            }
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
        $lockTables = $flushTables = '';
 
        foreach ($mvcArr as $className) {
            if (!cls::load($className, true)) {
                self::fLog("Липсва клас `{$className}`");
                continue;
            }
            
            // Инстанцираме класа
            $mvc = cls::get($className);
            
            // Пропускаме класовете, които имат модели в други бази данни
            if (!self::hasEqualDb($this, $mvc)) {
                self::fLog("DB е различна за `{$className}`");
                continue;
            }
            
            if ($mvc->dbTableName) {
                list($exists, $cnt, $lmt, $size) = $this->getTableInfo($mvc);
            }

            if (!$mvc->dbTableName) {
                self::fLog("Липсва dbTabeName за `{$className}`");
                continue;
            }

            if (isset($mvc->doReplication) && !$mvc->doReplication) {
                self::fLog("Изключен бекъп за `{$className}`");
                continue;
            }

            if (!$exists) {
                self::fLog("Липсва таблица в DB за `{$className}`");
                continue;
            }
            
            if (!$cnt) {
                self::fLog("Липват записи за `{$className}`");
                continue;
            }

            if (isset($instArr[$mvc->dbTableName])) {
                self::fLog("Повтарящ се клас клас `{$className}`");
                continue;
            }

            $instArr[$mvc->dbTableName] = $mvc;
            $this->lmt[$mvc->dbTableName] = $lmt;
            $maxChunk = $mvc->backupMaxRows ?? (($mvc->dbTableName == 'cat_product_tpl_cache') ? 5000000 : BACKUP_MAX_CHUNK_SIZE);
            $this->chunks[$mvc->dbTableName] = pow(4, floor(log($maxChunk, 4)));
            $lockTables .= ",`{$mvc->dbTableName}` READ";
            $flushTables .= ",`{$mvc->dbTableName}` ";
        }

        uksort($instArr, array($this, 'compLmt'));
       
        // Правим пробно експортиране на всички таблици, без заключване
        $tables = array();
        $time = time();
        $this->exportTables($instArr, $tables);
        // Добавяме експортираните файлове в описанието
        foreach ($tables as $table => $file) {
            $description['files'][$table] = "{$file}.csv.7z";
        }
       
        // Пускаме завесата
        
        core_SystemLock::block('Процес на архивиране на данните', 600); // 10 мин.
        $description['times']['lock'] = dt::now();
        
        // Флъшваме всички таблици, които ни трябват
        $flushTables = trim($flushTables, ',');
        $this->db->query("FLUSH TABLES {$flushTables}");
        
        // Локваме ги
        $lockTables = trim($lockTables, ',');        
        $this->db->query("LOCK TABLES {$lockTables}");
        
        // Изтриваме статистическата информация за таблиците, за да се генерира на ново
        self::$info = array();
        
        // Флъшваме всичко, каквото има от SQL лога
        $this->cron_FlushSqlLog();
        
        // Записваме времето на бекъпа
        $description['time'] = dt::now();
        
        // Експортираме всички таблици, като зачистваме масива
        $tables = array();
        $this->exportTables($instArr, $tables, $time);
        
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
            $dest = $backDir . $file . '.7z';
            if (!file_exists($dest)) {
                file_put_contents($path, $dbStructure);
                debug::log($msg = ('Компресиране на ' . basename($dest)));
                self::fLog($msg);
                self::compressFile($path, $dest, $pass);
            }
            $description['dbStruct'] = $file . '.7z';
        }
        
        // Добавяме променените файлове в описанието на експортираните файлове
        foreach ($tables as $table => $file) {
            $description['files'][$table] = "{$file}.csv.7z";
        }
        
        // Бекъп на двата конфиг файла
        $indCfg = rtrim(EF_INDEX_PATH, '/\\') . '/index.cfg.php';
        if (file_exists($indCfg)) {
            expect(is_readable($indCfg));
            $hash = base_convert(md5_file($indCfg), 16, 36);
            $file = "index.{$hash}.cfg.php";
            $tmpFile = $workDir . $file;
            copy($indCfg, $tmpFile);
            $indZip = $backDir . $file . '.7z';
            if (!file_exists($indZip)) {
                self::compressFile($tmpFile, $indZip, $pass);
            }
            $description['indexConfig'] = $file . '.7z';
        }
        
        $appCfg = rtrim(EF_CONF_PATH, '/\\') . '/' . EF_APP_NAME . '.cfg.php';
        expect(file_exists($appCfg) && is_readable($appCfg));
        $hash = base_convert(md5_file($appCfg), 16, 36);
        $file = "app.{$hash}.cfg.php";
        $tmpFile = $workDir . $file;
        copy($appCfg, $tmpFile);
        $appZip = $backDir . $file . '.7z';
        if (!file_exists($appZip)) {
            self::compressFile($tmpFile, $appZip, $pass);
        }
        $description['appConfig'] = $file . '.7z';
        
        // Взема стойностите на някои константи
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
            $dest = $backDir . $file . '.7z';
            if (!file_exists($dest)) {
                file_put_contents($path, $descriptionStr);
                debug::log($msg = ('Компресиране на ' . basename($dest)));
                self::fLog($msg);
                self::compressFile($path, $dest, $pass);
            }
        }
        
        // Почистваме всички ненужни файлове от бекъпите, които са в повече
        $backupMaxCnt = core_Setup::get('BACKUP_MAX_CNT');
        
        $log = array();
        $used = array();
        
        // Файлове, които ще пазим
        foreach($description['files'] as $file) {
            $used[$file] = true;
        }
 
        $descrArr = self::discover($backDir, $pass, $log);
        
        
        $minTime = time();
        foreach ($descrArr as $path => $descr) {
            
            $descr = (object) $descr;

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
        $files = glob("{$backDir}*.{csv.7z,cfg.php.7z,json.7z,sql.7z}", GLOB_BRACE);

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
        self::fLog("Начало на експортирането на таблиците общо " . count($instArr) . ' бр');

        $pass = core_Setup::get('BACKUP_PASS');
        $addCrc32 = crc32(EF_SALT . $pass);
  
        foreach ($instArr as $table => $inst) {
            core_App::setTimeLimit(120);
            
            if ($inst === null) {
                self::fLog("Таблицата {$table} има null за инстанция");
                continue;
            }
            
            list($exists, $cnt, $lmt, $size) = $this->getTableInfo($inst);
            self::fLog("Таблицата {$table} съдържа {$cnt} записа, последно модифицирани в " . date('m/d/Y H:i:s', $lmt));


            if(isset($maxLmt) && ($lmt < $maxLmt)) {
                self::fLog("Пропускаме `{$table}` защото е последно модифицирана след " .  date('m/d/Y H:i:s', $lmt) . " > " . date('m/d/Y H:i:s', $maxLmt));
                continue;
            }

            $backupMaxRows = $this->chunks[$table];
            
            // Дали да бекъпваме на партишъни
            if ($backupMaxRows < $cnt) {
                $diffFields = array();
                // Ако няма $inst->backupDiffFields правим ги от всички полета, които не са текстови или блоб
                if(!isset($inst->backupDiffFields)) { 
                    foreach($inst->fields as $fName => $fRec) {
                        if($fRec->kind != 'FLD' ||
                            is_a($fRec->type, 'type_Blob') ||
                            is_a($fRec->type, 'type_Text') ||
                            is_a($fRec->type, 'type_Keylist') ||
                            is_a($fRec->type, 'type_Set')  ) {

                            continue;
                        }

                        $diffFields[] = $fName;
                    }
                } else {
                    $diffFields = arr::make($inst->backupDiffFields);
                }
 
                $expr = "CONCAT_WS('|'";
                foreach ($diffFields as $fld) {
                    $expr .= ', `' . str::phpToMysqlName($fld) . '`';
                }
                $expr = "crc32(${expr}))";
                $maxId = 0;

                for ($i = 0; $i * $backupMaxRows < $cnt; $i++) {
                    core_App::setTimeLimit(120);
                    $limit = "{$backupMaxRows}/{$maxId}";
                    $key = "{$table}-{$lmt}-" . ($i + 1);
                    if (!isset(self::$crcArr[$key])) {
 
                        $sql = "SELECT MAX(_id) AS _maxId, SUM(`_backup`) AS `_crc32backup` FROM  (SELECT id AS _id, {$expr} AS `_backup` FROM `{$table}` WHERE id > {$maxId} ORDER BY `id` LIMIT {$backupMaxRows}) `_backup_table`";
                        
                        DEBUG::startTimer('Check table for changes: ' . $table);
                        $dbRes = $inst->db->query($sql);
                        $rec = $inst->db->fetchObject($dbRes);
                        DEBUG::stopTimer('Check table for changes: ' . $table);
 
                        $maxId = $rec->_maxId;
                        self::$crcArr[$key] = $rec->_crc32backup + $addCrc32;
                    }
                    
                    if (self::$crcArr[$key] > 0) {
                        $suffix = ($i + 1) . '-' . base_convert(abs(self::$crcArr[$key]), 10, 36);
                        $this->runBackupTable($inst, $table, $suffix, $limit);
                        $tables["{$table}-" . ($i + 1)] = "{$table}.{$suffix}";
                    }
                }
            } else {
                $suffix = base_convert($lmt + $addCrc32, 10, 36);
                $this->runBackupTable($inst, $table, $suffix);
                $tables[$table] = "{$table}.{$suffix}";
            }
        }
    }


    /**
     * извиква по cli процес, който бекъпва съдържанието на една таблица
     */
    public function runBackupTable($inst, $table, $suffix, $limit = '')
    {
        $fileName = "{$table}.{$suffix}";
        $path = self::getTempPath($fileName . '.csv');
        $dest = self::getBackupPath($fileName . '.csv.7z');
        $tmpCsv = "{$path}.tmp";

        if (file_exists($dest)) {
            debug::log($msg = "Таблица `{$fileName}` вече съществува като 7z файл");
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
        $processFile = self::getTempPath("{$table}.{$suffix}.bpr");
        file_put_contents($processFile, $params, FILE_APPEND);
        
        $cmd = escapeshellarg(EF_INDEX_PATH . '/index.php');
        $app = EF_APP_NAME;
        $ctr = 'core_Backup';
        $act = 'doBackupTable';
        
        $phpCmd = core_Os::getPHPCmd();

        $msg = "$phpCmd {$cmd} {$app} {$ctr} {$act} " . escapeshellarg($processFile);

        core_Os::startCmd($msg);
        self::fLog($msg);
    }
    

    /**
     * Прави бекъп файл на конкретна таблица
     */
    public static function cli_doBackupTable()
    {  
        // Спираме логването в core_Debug
        core_Debug::$isLogging = false;
 
        global $argv;
        
        try {
            $processFile = $argv[4];
            $params = file_get_contents($processFile);
            self::$tempDir = dirname($processFile) .'/';
            list($className, $table, $suffix, $limit) = explode('|', $params);
            
            $inst = cls::get($className);

            // Подготвяме пътищата
            $fileName = "{$table}.{$suffix}";
            $path = self::getTempPath($fileName . '.csv');
            $dest = self::getBackupPath($fileName . '.csv.7z');
            $tmpCsv = "{$path}.tmp";

            // Вземаме паролата
            $pass = core_Setup::get('BACKUP_PASS');


            if (file_exists($dest)) {
                self::fLog("Таблица `{$dest}` вече съществува като 7z файл");
                exit(0);
            }
            
            if (file_exists($tmpCsv)) {
                self::fLog("Таблица `{$fileName}` вече съществува като tmp файл");
                exit(0);
            }

            self::fLog("Експорт в CSV на таблица `{$fileName}`"); 
            
            // Отваряме файла за писане
            $out = fopen($tmpCsv, 'w');

            // Извличаме информация за колоните
            $types = $headers = array();
            $i = 0;
            $fields = $inst->db->getFields($table);
            foreach ($fields as $fRec) {
                list($type, ) = explode('(', $fRec->Type);
                if (strpos('|tinyint|smallint|mediumint|int|integer|bigint|float|double|double precision|real|decimal|', '|' . strtolower($type) . '|') != false) {
                    $types[$i] = 'numeric';
                } elseif(strpos('|blob|binary|tinyblob|mediumblob|longblob', '|' . strtolower($type) . '|') != false) {
                    $types[$i] = 'binary';
                } else {
                    $types[$i] = 'string';
                }
                $headers[$i] = $fRec->Field . ':' . $types[$i];
                $i++;
            }

            $link = $inst->db->connect();

            if(strlen($limit)) {
                list($backupMaxRows, $maxId) = explode('/', $limit);
                $q = "SELECT * FROM `{$table}` WHERE `id` > {$maxId} ORDER BY `id` LIMIT {$backupMaxRows}";
            } else {
                $q = "SELECT * FROM `{$table}`";
            }
            $dbRes = $link->query($q);
            
            if(!$dbRes) {
                self::fLog('DB Error: ' . $q . ' => ' . $link->error . ' [' .$params . ']');
                unlink($processFile);
                @fclose($out);
                @unlink($tmpCsv);
                @unlink($path);
                die;
            }

            fputcsv($out, $headers);
            while ($row = $inst->db->fetchArray($dbRes, MYSQLI_NUM)) {
                $vals = '';
                foreach ($row as $i => $f) {
                    if ($f === null) {
                        $row[$i] = '\\N';
                    } elseif ($types[$i] === 'binary') {
                        if(strlen($f) > BACKUP_MAX_INLINE_BLOB)  {
                            $row[$i]  = 'e:' . self::storeBinary($f);
                        } else {
                            $row[$i]  = 'i:' . $f;
                        }
                    } 
                }

                fputcsv($out,  $row);
            }

            fclose($out);
            rename("{$path}.tmp", $path);

            self::fLog('Компресиране на ' . basename($dest));
            self::compressFile($path, $dest, $pass);
            self::fLog('Край на компресиране на ' . basename($dest));

            unlink($processFile);
        }  catch (Throwable $e) {
            self::fLog('Exception: ' . $e->getMessage());
            unlink($processFile);
            @fclose($out);
            @unlink($tmpCsv);
            @unlink($path);
            die('error');
        }
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
            $dest = $backDir . $newFile . '.7z';
            self::compressFile($newPath, $dest, core_Setup::get('BACKUP_PASS'));
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
                
                // Ако сме надвишили максималния брой нишки на възстановяване изчакваме докато падне техния брой
                // В заключващото съобщение показваме текущите таблици които възстановяваме в паралелни нишки
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
            $files = glob($dir . 'log.*.sql.7z');
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
            $maxMysqlQueryLength = $db->getVariable('max_allowed_packet') / 5;            
        }
        
        $link = $db->connect();
        $handle = fopen($dest, 'r');
        $linesArr = array();
        $totalLen = 0;
        $linesCnt = 0;
        if ($handle) {
            do {
                $line = fgetcsv($handle);
                if ($line !== false) {
                    $linesCnt++;
                }
                if (!$cols) {
                    if(!empty($line)) {
                        $cols = $headersArr = array();
                        foreach($line as $c) {
                            list($name, $type) = explode(':', $c, 2);
                            $cols[$name] = $type;
                            $headersArr[] = "`" . $db->escape($name) . "`";
                        }
                        $headers = implode(',', $headersArr);
                    }

                    continue;
                }
                if ($line === false || ($totalLen > $maxMysqlQueryLength)) {
                    try {
                        if (!empty($line)) {
                            $linesArr[] = self::getValuesAsLine($line);
                        }
                        
                        $link->query("INSERT INTO `{$table}` ({$headers}) VALUES \n (" . implode("),\n(", $linesArr) . ')');

                        $linesArr = array();
                        $totalLen = 0;
                        continue;
                    } catch (Exception $e) {
                        fclose($handle);
                        $res = "err: Грешка при изпълняване на `INSERT INTO `{$table}` ({$headers}) VALUES  (" . implode(') (', array_slice($query, 0, 3)) .')`';
                        self::fLog($res);

                        return $res;
                    }
                }
                
                $linesArr[] = self::getValuesAsLine($line);
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
     * Превръща масив със стойности в линия за INSERT команда
     */
    static function getValuesAsLine(array $valuesArr, array $cols, $db)
    {
        foreach($cols as $name => $type) {
            $v = $valuesArr[$name];
            if($type === 'string') {
                $valuesArr[$name] = '"' . $db->escape($v) . '"';
            } elseif($type === 'binary') {
                list($store, $bVal) = explode(':', $valuesArr[$name], 2);
                // e - външно складиране
                if($store === 'e') {
                    $valuesArr[$name] = '"' . $db->escape(self::getExternalBlob($bVal)) . '"';
                } else {
                    $valuesArr[$name] = '"' . $db->escape($bVal) . '"';
                }
            }
        }

        return implode(',', $valuesArr);
    }


    /**
     * Връща стойността на блоб от хранилището 
     */
    static function getExternalBlob($hash)
    {
        $file = 'blob/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash;
        $path = self::getBackupPath($file);

        $res = file_get_contents($path);

        return $res;
    }
    

    /**
     * Записва blob и връща sha256 от него
     */
    static function storeBinary($f)
    {
        $hash = hash('sha256',  $f);
        $file = 'blob/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash;
        $path = self::getBackupPath($file);
        self::writeFileSafe($f, $path, null, 'ignore');

        return $hash;
    }

    /**
     * Writes content to a file, creating directories if needed.
     *
     * @param string      $content  File content
     * @param string      $path     Directory path OR full file path if $filename is null
     * @param string|null $filename File name or null (taken from $path)
     * @param string      $mode     'force' = overwrite, 'ignore' = no-op if exists
     * @param int         $dirPerm  Permissions for created directories
     * @param int         $filePerm Permissions for created file
     * @return bool true on success or no-op (ignore), false on error
     */
    static function writeFileSafe(
        string $content,
        string $path,
        $filename = null,
        string $mode = 'force',
        int $dirPerm = 0775,
        int $filePerm = 0664
    ): bool {
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        // If filename is null, treat $path as full file path
        if ($filename === null) {
            $filename = basename($path);
            $path = dirname($path);
        }

        if ($filename === '' || $path === '') {
            return false;
        }

        $fullPath = $path . DIRECTORY_SEPARATOR . $filename;

        // Create directories if missing
        if (!is_dir($path)) {
            if (!mkdir($path, $dirPerm, true) && !is_dir($path)) {
                return false;
            }
        }

        // If file exists and mode is ignore → do nothing
        if (is_file($fullPath) && $mode === 'ignore') {
            return true;
        }

        // Atomic write
        $tmp = $fullPath . '.tmp.' . getmypid();

        if (file_put_contents($tmp, $content, LOCK_EX) === false) {
            return false;
        }

        chmod($tmp, $filePerm);

        if (!rename($tmp, $fullPath)) {
            @unlink($tmp);
            return false;
        }

        return true;
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
        $tempPath = $temp . substr($file, 0, -3);
        
        expect(file_exists($path), $path);
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
        $log[] = "msg: Разкомпресиране на `{$file}`";
        $res = @self::uncompressFile($path, $temp, $pass);

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
                $lmt = isset($row['UPDATE_TIME']) ? strtotime($row['UPDATE_TIME']) : null;
                self::$info[$hash][$row['TABLE_NAME']] = array(true, $row['TABLE_ROWS'], $lmt, $row['DATA_LENGTH']);
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
        $mask = core_Os::normalizeDir($dir) . '/description.*.json.7z';
       
        $files = glob($mask);
       
        foreach ($files as $path) {
            $descPath = self::unzipToTemp($path, $pass, $log);
            if ($descPath && filesize($descPath)) {
                $description = json_decode(file_get_contents($descPath),  JSON_OBJECT_AS_ARRAY);
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
        $msg = date('Y-m-d H:i:s') . ' ' . $msg .  ' ;' . PHP_EOL;
        file_put_contents($file, $msg, FILE_APPEND | LOCK_EX);
    }

    /**
     * Компресиране на файл
     */
    static function compressFile($path, $dest, $pass = '')
    {
        return archive_Adapter::compressFileNew($path, $dest, $pass, '-t7z -mx=1 -ms=off -mhe=on -y');
    }

    /**
     * Декомпресиране на файл
     */
    static function uncompressFile($path, $dest, $pass = '')
    {
        return archive_Adapter::uncompressNew($path, $dest, $pass, '-e -y');
    }

}
