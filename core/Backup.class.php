<?php


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
    public static $temp;
    
    
    /**
     * Създаване на пълен бекъп
     */
    public function cron_Create()
    {
        core_Debug::$isLogging = false;
        
        if (core_Setup::get('BACKUP_ENABLED') != 'yes') {
            return;
        }
        
        core_App::setTimeLimit(120);
        
        // Мета-данни за бекъпа
        $description = array();
        $description['times']['start'] = dt::now();
        
        // Парола за създаване на архивните файлове
        $pass = core_Setup::get('BACKUP_PASS');
        
        // Форсираме директориите
        $backDir = self::getDir();
        $workDir = self::getDir('backup_work');
        
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
            if (!$mvc->dbTableName || !$mvc->doReplication || !$exists || !$cnt || isset($instArr[$mvc->dbTableName])) {
                continue;
            }
            $instArr[$mvc->dbTableName] = $mvc;
            $this->lmt[$mvc->dbTableName] = $lmt;
            $lockTables .= ",`{$mvc->dbTableName}` WRITE";
        }
        
        uksort($instArr, array($this, 'compLmt'));
        
        // Правим пробно експортиране на всички таблици, без заключване
        $tables = array();
        $this->exportTables($instArr, $tables);
        
        // Пускаме завесата
        $lockTables = trim($lockTables, ',');
        core_SystemLock::block('Процес на архивиране на данните', 600); // 10 мин.
        $description['times']['lock'] = dt::now();
        
        @$this->db->query('FLUSH TABLES');
        
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
        debug::log('Генериране SQL за структурата на базата');
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
                debug::log('Компресиране на ' . basename($dest));
                archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
            }
            $description['dbStruct'] = $file . '.zip';
        }
        
        // Копираме или компресираме файловете с експортатите данни
        foreach ($tables as $table) {
            $path = $workDir . $table . '.csv';
            $dest = $backDir . $table . '.csv.zip';
            
            if (file_exists($dest)) {
                debug::log('Файлът `' . basename($dest) . '` е наличен от прешишен бекъп');
            } else {
                debug::log('Компресиране на ' . basename($dest));
                archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
            }
            
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
                debug::log('Компресиране на ' . basename($dest));
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
            if (substr($name, 0, 4) == 'log.') {
                $time = self::getTimeFromFilemane($name);
                
                if ($time > $minTime) {
                    continue;
                }
            }
            
            @unlink($path);
        }
        // Почистваме работната директория
        core_Os::deleteDirectory($workDir, true);
        
        core_Os::deleteDirectory(self::$temp);
    }
    
    
    /**
     * Извлича информация за времето от името на файла
     */
    public static function getTimeFromFilemane($name)
    {
        $m = array();
        
        preg_match('/(\\d{4})[\\-_ ](\\d{2})[\\-_ ](\\d{2})[\\-_ ](\\d{2})[\\-_ ](\\d{2})[\\-_ ](\\d{2})/', $name, $m);
        
        $res = $m[1] . '-' . $m[2] . '-' . $m[3] . ' ' . $m[4] . ':' . $m[5] . ':' . $m[6];
        
        return $res;
    }
    
    
    /**
     * Експортира всички таблици, като CSV файлове в работната директория
     */
    public function exportTables($instArr, &$tables)
    {
        $pass = core_Setup::get('BACKUP_PASS');
        $addCrc32 = crc32(EF_SALT . $pass);

        foreach ($instArr as $table => $inst) {
            core_App::setTimeLimit(120);
            
            if ($inst === null) {
                continue;
            }
            
            list($exists, $cnt, $lmt) = $this->getTableInfo($inst);
            
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
                        
                        DEBUG::startTimer('Query Table:' . $table);
                        $dbRes = $inst->db->query($sql);
                        $rec = $inst->db->fetchObject($dbRes);
                        DEBUG::stopTimer('Query Table:' . $table);
                        // if($table == 'fileman_files') bp($sql, $rec);
                        self::$crcArr[$key] = $rec->_crc32backup + $addCrc32;
                    }
                    
                    if (self::$crcArr[$key] > 0) {
                        $suffix = ($i + 1) . '-' . base_convert(abs(self::$crcArr[$key]), 10, 36);
                        $this->backupTable($inst, $table, $suffix, $limit);
                        $tables[] = "{$table}.{$suffix}";
                    }
                }
            } else {
                $suffix = base_convert($lmt + $addCrc32, 10, 36);
                $this->backupTable($inst, $table, $suffix);
                $tables[] = "{$table}.{$suffix}";
            }
        }
    }
    
    
    /**
     * Прави бекъп файл на конкретна таблица
     */
    public function backupTable($inst, $table, $suffix, $limit = '')
    {
        
        // Форсираме директориите
        $backDir = self::getDir();
        $workDir = self::getDir('backup_work');
        
        $fileName = "{$table}.{$suffix}";
        
        $path = $workDir . $fileName . '.csv';
        $dest = $backDir . $fileName . '.csv.zip';
        
        if (file_exists($dest) && filesize($dest)) {
            debug::log("Таблица `{$fileName}` вече съществува като zip файл");
            
            return;
        }
        
        if (file_exists($path) && filesize($path)) {
            debug::log("Таблица `{$fileName}` вече съществува като csv файл");
            
            return;
        }
        
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
        $out = fopen("{$path}.tmp", 'w');
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
        debug::log("Експорт в CSV на таблица `{$fileName}`");
    }
    
    
    /**
     * Връща посочената директория за бекъп
     */
    public static function getDir($subDir = '')
    {
        if ($subDir == '') {
            $dir = core_Os::normalizeDir(core_Setup::get('BACKUP_PATH'));
        } else {
            $dir = core_Os::normalizeDir(EF_UPLOADS_PATH) . '/' . $subDir;
        }
        
        if (core_Os::forceDir($dir, 0744)) {
            return $dir . '/';
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
            $backDir = self::getDir();
            $dest = $backDir . '/' . $newFile . '.zip';
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
                $path = self::getDir('sql_log') . '/' . EF_DB_NAME . '.log.sql';
            } else {
                $path = false;
            }
        }
        
        return $path;
    }
    
    
    /**
     * Връща името на флага за правене или не на записи
     */
    public static function getFlagDoSqlLog()
    {
        $path = crc32(EF_SALT . 'BKP') . '.doSqlLog';
        
        return $path;
    }
    
    
    /**
     * Възстановява системата от направен бекъп
     */
    public static function restore(&$log)
    {
        core_Debug::$isLogging = false;
        core_SystemLock::stopIfBlocked();
        core_SystemLock::block('Възстановяване от бекъп', 1800);  

        try {
            core_App::setTimeLimit(120);
            
            // Масив за съобщенията
            $log = array();
            
            // Път от където да възстановяваме
            $dir = core_Os::normalizeDir(BGERP_BACKUP_RESTORE_PATH) . '/';
            
            // Парола за разархивиране
            $pass = defined('BGERP_BACKUP_RESTORE_PASS') ? BGERP_BACKUP_RESTORE_PASS : '';
            
            // Вземаме манипулатора на базата данни
            $db = cls::get('core_Db');
            
            // Първо очакваме празна база. Ако в нея има нещо - излизаме
            $dbRes = $db->query("SELECT count(*) AS tablesCnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$db->dbName}'");
            $res = $db->fetchArray($dbRes);
            
            if (array_values($res)[0] > 0) {
                $log[] = 'err: Базата не е празна. Преди възстановяване от бекъп в нея не трябва да има нито една таблица.';
                core_SystemLock::remove();

                return false;
            }
            
            // Подготвяме структурата на базата данни
            $descrArr = self::discover($dir, $pass, $log);
            
            $description = array_values($descrArr)[0];
            
            $path = self::unzipToTemp($dir . $description->dbStruct, $pass, $log);
            $sql = file_get_contents($path);
            unlink($path);
            
            $log[] = 'msg: Създаване на структурата на таблиците';
            $db->multyQuery($sql);
            
            // Наливаме съдържанието от всички налични CSV файлове
            // Извличаме от CSV последователно всички таблици
            $cnt = count($description->files);
            foreach ($description->files as $file) {
                $src = $dir . $file;
                core_App::setTimeLimit(1200);
                list($table, ) = explode('.', $file);
                core_SystemLock::block('Възстановяване на ' . basename($file), ($cnt--)*4 + 240);  
                $dest = self::unzipToTemp($src, $pass, $log);
                expect($dest, $src, $pass);
                $log[] = $res = self::importTable($db, $table, $dest);
                unlink($dest);
                if (substr($res, 0, 4) == 'err:') {
                    return;
                }
            }
            
            // Наливане на наличните SQL логове
            $files = glob($dir . 'log.*.sql.zip');
            asort($files);
            
            $cnt = count($files);
            foreach ($files as $src) {
                $time = self::getTimeFromFilemane(basename($src));
                if ($time <= $description->time) {
                    continue;
                }
                $src = str_replace('\\', '/', $src);
                core_App::setTimeLimit(120);
                $dest = self::unzipToTemp($src, $pass, $log);
                $sql = file_get_contents($dest);
                $log[] = 'msg: Прилагане на ' . basename($src);
                core_SystemLock::block('Възстановяване на ' . basename($src), ($cnt--) * 2 + 30);  
                $db->multyQuery($sql);
                unlink($dest);
            }
            
            $log[] = 'msg: Възстановяването завърши успешно';
            core_SystemLock::remove();
            core_Os::deleteDirectory(self::$temp);

            return true;
        } catch (core_exception_Expect $e) {
            $log[] = 'err: ' . ht::mixedToHtml(array($e->getMessage(), $e->getTraceAsString(), $e->getDebug(), $e->getDump()), 4);
        }
        
        core_SystemLock::remove();
        core_Os::deleteDirectory(self::$temp);
    }
    
    
    /**
     * Импортира таблица от CSV файл
     */
    public static function importTable($db, $table, $dest)
    {
        static $maxMysqlQueryLength;
        if (!isset($maxMysqlQueryLength)) {
            $maxMysqlQueryLength = $db->getVariable('max_allowed_packet') - 100000;
        }
        
        $handle = fopen($dest, 'r');
        if ($handle) {
            $query = '';
            do {
                $line = fgets($handle);
                if (!$cols) {
                    $cols = $line;
                    continue;
                }
                if ($line === false || (strlen($query) + strlen($line) > $maxMysqlQueryLength)) {
                    try {
                        $query = "INSERT INTO `{$table}` ({$cols}) VALUES " . $query;
                        
                        //@file_put_contents("C:\\xampp\\htdocs\\ef_root\\uploads\\bgerp\\backup_work\query.log", $query);
                        $db->query($query);
                        $query = '';
                    } catch (Exception $e) {
                        $query = substr($query, 0, 1000);
                        $res = "err: Грешка при изпълняване на `{$query}`";
                        return $res;
                    }
                }
                $query .= ($query ? ",\n" : "\n") . "({$line})";
            } while ($line !== false);
            fclose($handle);
            $res = 'msg: Импортиране на ' . $table;
        } else {
            // Не може да се отвори файла
            $res = "err: Не може да се отвори файла `{$dest}`";
        }
        
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
        if (!self::$temp) {
            self::$temp = core_Os::normalizeDir(EF_TEMP_PATH) . '/backup/' . base_convert(rand(1000000, 99999999), 10, 36) . '/';
            core_Os::forceDir(self::$temp);
        }
        $file = basename($path);
        $tempPath = self::$temp . substr($file, 0, -4);
        
        expect(file_exists($path), $path);
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
        $log[] = "msg: Разкомпресиране на `{$file}`";
        
        $res = @archive_Adapter::uncompress($path, self::$temp, $pass);
        if ($res === 0 && file_exists($tempPath)) {
            return $tempPath;
        }
    }
    
    
    /**
     * Поверява дали конфига е добре настроен
     */
    public static function checkConfig()
    {
        if (core_Setup::get('BACKUP_ENABLED') != 'yes') {
            return;
        }
        
        $res .= core_Os::hasDirErrors(core_Setup::get('BACKUP_PATH'), 'Директорията за backup');
        
        return $res;
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
     * @return stdObject Обект, съдържащ
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
     * Pomo
     */
    public static function sortDescr($a, $b)
    {
        return $a['time'] > $b['time'];
    }
}
