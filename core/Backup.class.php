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
        $curDir = self::getDir('current');
        $pastDir = self::getDir('past');
        $workDir = self::getDir('backup_work');
        
        // Определяме всички mvc класове, на които ще правим бекъп
        $mvcArr = core_Classes::getOptionsByInterface('core_ManagerIntf');
        $instArr = array();
        $files = array();
        $lockTables = '';
        
        foreach ($mvcArr as $className) {
            if (!cls::load($className, true)) {
                continue;
            }
            
            $mvc = cls::get($className);
            
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
        $files = array();
        $this->exportTables($instArr, $files, $curDir, $pastDir, $workDir);
        
        // Пускаме завесата
        $lockTables = trim($lockTables, ',');
        core_SystemLock::block('Процес на архивиране на данните', 600); // 10 мин.
        $description['times']['lock'] = dt::now();
        
        $this->db->query('FLUSH TABLES');
        
        $this->db->query("LOCK TABLES {$lockTables}");
        
        // Флъшваме всичко, каквото има от SQL лога
        $this->cron_FlushSqlLog();
        
        // Ако в `current` има нещо - преместваме го в `past`
        if (!core_Os::isDirEmpty($curDir)) {
            // Изтриваме директорията past
            core_Os::deleteDirectory($pastDir);
            
            // Преименуваме текущата директория на past
            if (!@rename($curDir, $pastDir)) {
                sleep(1);
                rename($curDir, $pastDir);
            }
        }
        
        // Създаваме празна текуща директория
        core_Os::forceDir($curDir, 0777);
        
        // Масив за всички генерирани файлове
        $files = array();
        
        // Експортираме всички таблици
        $this->exportTables($instArr, $files, $curDir, $pastDir, $workDir);
        
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
            $path = $workDir . '/db_structure.sql';
            $dest = $curDir . '/db_structure.sql.zip';
            if (file_exists($dest)) {
                unlink($dest);
            }
            file_put_contents($path, $dbStructure);
            debug::log('Компресиране на ' . basename($dest));
            archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
        }
        
        // Копираме или компресираме файловете с експортатите данни
        foreach ($files as $file => $cols) {
            $path = $workDir . '/' . $file . '.csv';
            $past = $workDir . '/' . $file . '.csv.zip';
            $dest = $curDir . '/' . $file . '.csv.zip';
            if (file_exists($dest)) {
                unlink($dest);
            }
            if (file_exists($past)) {
                debug::log('Файлът `' . basename($dest) . '` се копира без промени от прешишния бекъп');
                copy($past, $dest);
                unlink($past);
            } else {
                debug::log('Компресиране на ' . basename($dest));
                archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
            }
            
            $description['files'][$file] = $cols;
        }
        
        // Бекъп на двата конфиг файла
        $indCfg = EF_INDEX_PATH . '/index.cfg.php';
        if (file_exists($indCfg)) {
            expect(is_readable($indCfg));
            $indZip = $curDir . '/index.cfg.php.zip';
            if (file_exists($indZip)) {
                unlink($indZip);
            }
            archive_Adapter::compressFile($indCfg, $indZip, $pass);
        }
        $appCfg = EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php';
        expect(file_exists($appCfg) && is_readable($appCfg));
        $appZip = $curDir . '/app.cfg.php.zip';
        if (file_exists($appZip)) {
            unlink($appZip);
        }
        archive_Adapter::compressFile($appCfg, $appZip, $pass);
        
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
            $path = $workDir . '/description.json';
            $dest = $curDir . '/description.json.zip';
            if (file_exists($dest)) {
                unlink($dest);
            }
            file_put_contents($path, $descriptionStr);
            debug::log('Компресиране на ' . basename($dest));
            archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
        }
        
        // Почистваме работната директория
        core_Os::deleteDirectory($workDir, true);
    }
    
    
    /**
     * Експортира всички таблици, като CSV файлове в работната директория
     */
    public function exportTables($instArr, &$files, $curDir, $pastDir, $workDir)
    {
        foreach ($instArr as $table => $inst) {
            core_App::setTimeLimit(120);
            
            if ($inst === null) {
                continue;
            }
            
            if (isset($inst->backupMaxRows, $inst->backupDiffFields)) {
                $maxId = $inst->db->getNextId($table);
                $diffFields = arr::make($inst->backupDiffFields);
                $expr = '';
                foreach ($diffFields as $fld) {
                    $expr .= " + crc32(#${fld})";
                }
                $crc = 'SUM(' . trim($expr, ' +') . ')';
                $n = 1;
                for ($i = 0; $i <= $maxId; $i += $inst->backupMaxRows) {
                    core_App::setTimeLimit(120);
                    $query = $inst->getQuery();
                    $query->XPR('crc32backup', 'int', $crc);
                    $query->where($where = ('id BETWEEN ' . ($i + 1) . ' AND ' . ($i + $inst->backupMaxRows)));
                    $query->show('crc32backup');
                    $rec = $query->fetch();
                    if ($rec->crc32backup > 0) {
                        $suffix = $n . '-' . base_convert(abs($rec->crc32backup), 10, 36);
                        $this->backupTable($inst, $table, $suffix, $workDir, $curDir, $pastDir, $where, $files);
                    }
                    $n++;
                }
            } else {
                $lmtTable = $inst->db->getLMT($table);
                $suffix = base_convert($lmtTable, 10, 36);
                $this->backupTable($inst, $table, $suffix, $workDir, $curDir, $pastDir, '', $files);
            }
        }
    }
    
    
    /**
     * Прави бекъп файл на конкретна таблица
     */
    public function backupTable($inst, $table, $suffix, $workDir, $curDir, $pastDir, $where, &$files)
    {
        if (!$where) {
            $where = '1=1';
        }
        
        $fileName = "{$table}.{$suffix}";
        
        $path = $workDir . '/' . $fileName . '.csv';
        $dest = $curDir . '/' . $fileName . '.csv.zip';
        $past = $pastDir . '/' . $fileName . '.csv.zip';
        
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
        $files[$fileName] = $cols;
        
        if (file_exists($dest)) {
            debug::log("Таблица `{$fileName}` вече съществува като zip файл");
            
            $save = dirname($path) . '/' . basename($dest);
            copy($dest, $save);
            
            return;
        }
        
        if (file_exists($path)) {
            debug::log("Таблица `{$fileName}` вече съществува като csv файл");
            
            return;
        }
        
        if (file_exists($past)) {
            debug::log("Таблица `{$fileName}` вече съществува като zip файл");
            
            $save = dirname($path) . '/' . basename($past);
            copy($past, $save);
            
            return;
        }
        
        $dbRes = $inst->db->query("SELECT * FROM `{$table}` WHERE {$where}");
        $out = fopen($path, 'w');
        fwrite($out, "{$cols}");
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
            fwrite($out, "\n{$vals}");
        }
        fclose($out);
        debug::log("Експорт в CSV на таблица `{$fileName}`");
    }
    
    
    /**
     * Връща посочената директория за бекъп
     */
    public static function getDir($subDir)
    {
        if ($subDir == 'current' || $subDir == 'past') {
            $base = core_Setup::get('BACKUP_PATH');
        } else {
            $base = EF_UPLOADS_PATH;
        }
        
        $dir = core_Os::normalizeDir($base) . '/' . $subDir;
        
        if (core_Os::forceDir($dir, 0777)) {
            
            return $dir;
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
        } catch(Exception $e) {}
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
            $newFile = date('Y-m-d_H-i-s') . '.log.sql';
            $newPath = str_replace("/{$file}", "/{$newFile}", $path);
            rename($path, $newPath);
            $curDir = self::getDir('current');
            $dest = $curDir . '/' . $newFile . '.zip';
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
        
        try {
            core_App::setTimeLimit(120);
            
            // Масив за съобщенията
            $log = array();
            
            // Път от където да възстановяваме
            $path = BGERP_BACKUP_RESTORE_PATH;
            
            // Парола за разархивиране
            $pass = defined('BGERP_BACKUP_RESTORE_PASS') ? BGERP_BACKUP_RESTORE_PASS : '';
            
            // Вземаме манипулатора на базата данни
            $db = cls::get('core_Db');
            
            // Първо очакваме празна база. Ако в нея има нещо - излизаме
            $dbRes = $db->query("SELECT count(*) AS tablesCnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$db->dbName}'");
            $res = $db->fetchArray($dbRes);
            
            if (array_values($res)[0] > 0) {
                $log[] = 'err: Базата не е празна. Преди възстановяване от бекъп в нея не трябва да има нито една таблица.';
                
                return false;
            }
            
            // Подготвяме структурата на базата данни
            $path = core_Os::normalizeDir($path) . '/';
            
            $descPath = self::unzipToTemp($path . 'description.json.zip', $pass, $log);
            $description = json_decode(file_get_contents($descPath));
            unlink($descPath);
            
            // Подготвяме структурата на базата данни
            $dbStructZip = $path . 'db_structure.sql.zip';
            $dbStructSql = self::unzipToTemp($dbStructZip, $pass, $log);
            $sql = file_get_contents($dbStructSql);
            unlink($dbStructSql);
            $log[] = 'msg: Създаване на структурата на таблиците';
            $db->multyQuery($sql);
            
            // Наливаме съдържанието от всички налични CSV файлове
            // Извличаме от CSV последователно всички таблици
            foreach ($description->files as $file => $cols) {
                $src = $path . $file . '.csv.zip';
                core_App::setTimeLimit(120);
                list($table, ) = explode('.', $file);
                $dest = self::unzipToTemp($src, $pass, $log);
                $log[] = self::importTable($db, $table, $cols, $dest);
                unlink($dest);
            }
            
            // Наливане на наличните SQL логове
            $files = glob($path . '*.log.sql.zip');
            asort($files);
            
            foreach ($files as $src) {
                $src = str_replace('\\', '/', $src);
                core_App::setTimeLimit(120);
                $dest = self::unzipToTemp($src, $pass, $log);
                $sql = file_get_contents($dest);
                $log[] = 'msg: Прилагане на ' . basename($src);
                $db->multyQuery($sql);
                unlink($dest);
            }
            
            $log[] = 'msg: Възстановяването завърши успешно';
            
            return true;
        } catch (core_exception_Expect $e) {
            $log[] = 'err: ' . ht::mixedToHtml(array($e->getMessage(), $e->getTraceAsString(), $e->getDebug(), $e->getDump()), 4);
        }
    }
    
    
    /**
     * Импортира таблица от CSV файл
     */
    public static function importTable($db, $table, $cols, $dest)
    {
        static $maxMysqlQueryLength;
        if (!isset($maxMysqlQueryLength)) {
            $maxMysqlQueryLength = $db->getVariable('max_allowed_packet') - 1000;
        }
        
        $handle = fopen($dest, 'r');
        if ($handle) {
            $query = '';
            do {
                $line = fgets($handle);
                if ($line === false || (strlen($query) + strlen($line) > $maxMysqlQueryLength)) {
                    try {
                        $query = "INSERT INTO `{$table}` ({$cols}) VALUES " . $query;
                        
                        //@file_put_contents("C:\\xampp\\htdocs\\ef_root\\uploads\\bgerp\\backup_work\query.log", $query);
                        $db->query($query);
                        $query = '';
                    } catch (Exception $e) {
                        $res = "err: Грешка при изпълняване на `{$query}`";
                    }
                }
                $query .= ($query ? ",\n" : "\n") . "({$line})";
            } while ($line !== false);
            fclose($handle);
            $рес = 'msg: Импортиране на ' . $table;
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
        $temp = core_Os::normalizeDir(EF_TEMP_PATH) . '/backup/';
        $file = basename($path);
        $tempPath = $temp . substr($file, 0, -4);
        
        expect(file_exists($path), $path);
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
        $log[] = "msg: Разкомпресиране на `{$file}`";
        archive_Adapter::uncompress($path, $temp, $pass);
        expect(file_exists($tempPath));
        
        return $tempPath;
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
        static $info = array();
        
        $hash = md5($mvc->db->dbHost . '|' . $mvc->db->dbUser . '|' . $mvc->db->dbName);
        
        $selfHash = md5($this->db->dbHost . '|' . $this->db->dbUser . '|' . $this->db->dbName);
        
        if (!isset($info[$hash]) && $hash == $selfHash) {
            $info[$hash] = array();
            $dbRes = $mvc->db->query("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA LIKE '{$mvc->db->dbName}'");
            while ($row = $mvc->db->fetchArray($dbRes)) {
                $lmt = $row['UPDATE_TIME'] ? strtotime($row['UPDATE_TIME']) : null;
                $info[$hash][$row['TABLE_NAME']] = array(true, $row['TABLE_ROWS'], $lmt);
            }
        }
        
        if (isset($info[$hash][$mvc->dbTableName])) {
            $res = $info[$hash][$mvc->dbTableName];
        } else {
            $res = array(0, 0, null);
        }
        
        return $res;
    }
}
