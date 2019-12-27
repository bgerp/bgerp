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
     * Създаване на пълен бекъп
     */
    public function cron_Create()
    {
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
        $curDir = $this->getDir('current');
        $pastDir = $this->getDir('past');
        $workDir = core_Setup::get('BACKUP_WORK_DIR');
        $sqlDir = $this->getExportCsvDir();
        
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
            if ($mvc->dbTableName && $this->db->tableExists($mvc->dbTableName) && !isset($instArr[$mvc->dbTableName])) {
                $instArr[$mvc->dbTableName] = null;
            }
            if (!$mvc->dbTableName || !$mvc->doReplication || !$this->db->tableExists($mvc->dbTableName) || !$mvc->count() || isset($instArr[$mvc->dbTableName])) {
                continue;
            }
            $instArr[$mvc->dbTableName] = $mvc;
            $lockTables .= ",`{$mvc->dbTableName}` WRITE";
        }
        
        $lockTables = trim($lockTables, ',');
        
        // Пускаме завесата
        core_SystemLock::block('Процес на архивиране на данните', 600); // 10 мин.
        
        $this->db->query('FLUSH TABLES');
        
        $this->db->query("LOCK TABLES {$lockTables}");
        
        // Флъшваме всичко, каквото има от SQL лога
        $this->cron_FlushSqlLog();
        
        // Ако в `current` има нещо - преместваме го в `past`
        if (!$this->isDirEmpty($curDir)) {
            // Изтриваме директорията past
            $this->deleteDirectory($pastDir);
            
            // Преименуваме текущата директория на past
            if (!@rename($curDir, $pastDir)) {
                sleep(1);
                rename($curDir, $pastDir);
            }
        }
        
        // Създаваме празна текуща директория
        core_Os::forceDir($curDir, 0645);
        
        // Масив за всички генерирани файлове
        $files = array();
        
        foreach ($instArr as $table => $inst) {
            core_App::setTimeLimit(120);
            
            if ($inst === null) {
                continue;
            }
            
            $path = $workDir . '/' . $table . '.csv';
            $sqlPath = $sqlDir . '/' . $table . '.csv';
            $dest = $curDir . '/' . $table . '.csv.zip';
            $past = $pastDir . '/' . $table . '.csv.zip';
            
            if (file_exists($path)) {
                unlink($path);
            }
            if (file_exists($dest)) {
                unlink($dest);
            }
            
            if (isset($inst->backupMaxRows, $inst->backupDiffFields)) {
                $maxId = $this->db->getNextId($table);
                
                $diffFields = arr::make($inst->backupDiffFields);
                $expr = '';
                foreach ($diffFields as $fld) {
                    $expr .= " + crc32(#${fld})";
                }
                $crc = 'SUM(' . trim($expr, ' +') . ')';
                
                for ($i = 0; $i <= $maxId; $i += $inst->backupMaxRows) {
                    $query = $inst->getQuery();
                    $query->XPR('crc32backup', 'int', $crc);
                    $query->where($where = ('id BETWEEN ' . ($i + 1) . ' AND ' . ($i + $inst->backupMaxRows)));
                    $query->show('crc32backup');
                    $rec = $query->fetch();
                    if ($rec->crc32backup > 0) {
                        $this->backupTable($table, abs($rec->crc32backup), $sqlDir, $workDir, $curDir, $pastDir, $where, $files);
                    }
                }
            } else {
                $this->backupTable($table, null, $sqlDir, $workDir, $curDir, $pastDir, '', $files);
            }
        }
        
        // Освеобождаваме LOCK-а на таблиците
        $this->db->query('UNLOCK TABLES');
        
        // Освобождаваме системата
        core_SystemLock::remove();
        
        // SQL структура на базата данни
        $dbStructure = '';
        
        // Запазваме структурата на базата
        debug::log('Генериране SQL за структурата на базата');
        foreach ($instArr as $table => $inst) {
            $query = "SHOW CREATE TABLE `{$table}`";
            $dbRes = $this->db->query($query);
            $res = $this->db->fetchArray($dbRes);
            
            //$dbStructure .= "\nDROP TABLE IF EXISTS `{$table}`;";
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
        foreach ($files as $path => $dest) {
            if (file_exists($dest)) {
                unlink($dest);
            }
            if (substr($path, -4) == '.zip') {
                debug::log('Файлът `' . basename($dest) . '` се копира без промени от прешишния бекъп');
                copy($path, $dest);
            } else {
                debug::log('Компресиране на ' . basename($dest));
                archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
            }
            
            $file = basename($dest);
            $description['files'][] = $dest;
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
    }
    
    
    /**
     * Прави бекъп файл на конкретна таблица
     */
    public function backupTable($table, $suffix, $sqlDir, $workDir, $curDir, $pastDir, $where, &$files)
    {
        if (!$where) {
            $where = '1=1';
        }
        
        $fileName = $suffix ? "{$table}.{$suffix}" : $table;
        
        $path = $workDir . '/' . $fileName . '.csv';
        $sqlPath = $sqlDir . '/' . $fileName . '.csv';
        $dest = $curDir . '/' . $fileName . '.csv.zip';
        $past = $pastDir . '/' . $fileName . '.csv.zip';
        
        if (file_exists($path)) {
            unlink($path);
        }
        if (file_exists($dest)) {
            unlink($dest);
        }
        
        if (file_exists($past)) {
            $lmtTable = $this->db->getLMT($table);
            
            // Таблицата не е променяна, нама да променяме и ZIP файла
            if ($lmtTable < filemtime($past) || strlen($suffix)) {
                debug::log("Таблица `{$fileName}` е без промени");
                $files[$past] = $dest;
                
                return;
            }
        }
        
        $query = "SELECT * 
                    INTO OUTFILE '{$sqlPath}'
                    FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
                    LINES TERMINATED BY '\n'
                    FROM `{$table}` 
                    WHERE {$where}";
        debug::log("Експорт в CSV на таблица `{$fileName}`");
        
        $this->db->query($query);
        if ($sqlPath != $path) {
            expect(file_exists($sqlPath));
            rename($sqlPath, $path);
        }
        $files[$path] = $dest;
    }
    
    
    /**
     * Връща пътя за експортиране на CSV файлове
     */
    public static function getExportCsvDir()
    {
        // Вземаме манипулатора на базата данни
        $db = cls::get('core_Db');
        
        $mysqlCsvPath = $db->getVariable('secure_file_priv');
        
        if ($mysqlCsvPath === '') {
            
            return self::normDir(core_Setup::get('BACKUP_WORK_DIR'));
        }
        
        if ($mysqlCsvPath != 'NULL') {
            core_Os::forceDir(core_Backup::normDir($mysqlCsvPath), 0747);
            if (is_dir($mysqlCsvPath) && is_readable($mysqlCsvPath) && is_writable($mysqlCsvPath)) {
                
                return self::normDir($mysqlCsvPath);
            }
        }
    }
    
    
    /**
     * Връща посочената директория за бекъп
     */
    public static function getDir($subDir)
    {
        $dir = self::normDir(core_Setup::get('BACKUP_PATH')) . '/' . $subDir;
        
        if (core_Os::forceDir($dir, 0747)) {
            
            return $dir;
        }
    }
    
    
    /**
     * Добавя mySQL заявките в SQL лога
     */
    public static function addSqlLog($sql)
    {
        if ($path = self::getSqlLogPath()) {
            @file_put_contents($path, $sql . ";\n\r", FILE_APPEND);
        }
    }
    
    
    /**
     * Флъшване на SQL лога към текущата бекъп директория
     */
    public static function cron_FlushSqlLog()
    {
        if (core_Setup::get('BACKUP_ENABLED') == 'yes') {
            $path = self::getSqlLogPath();
            
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
            
            // Регенерираме файлов флаг за това, дали се прави SQL лог
            $flagDoSqlLog = core_Backup::normDir(core_Setup::get('BACKUP_WORK_DIR')) . '/' . core_Backup::getFlagDoSqlLog();
            file_put_contents($flagDoSqlLog, 'OK');
        }
    }
    
    
    /**
     * Връща пътя до SLQ лога за текущата база
     */
    public static function getSqlLogPath()
    {
        static $path;
        
        if (!isset($path)) {
            $wDir = self::normDir(core_Setup::get('BACKUP_WORK_DIR'));
            if (file_exists($wDir . '/' . self::getFlagDoSqlLog())) {
                $path = $wDir . '/' . EF_DB_NAME . '.log.sql';
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
            $path = self::normDir($path) . '/';
            
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
            foreach ($description->files as $file) {
                $src = $path . basename($file);
                core_App::setTimeLimit(120);
                $table = substr(basename($src), 0, -8);
                list($table, $suffix) = explode('.', $table);
                $dest = self::unzipToTemp($src, $pass, $log);
                $log[] = 'msg: Импортиране на ' . $table;
                $sql = "LOAD DATA INFILE '{$dest}' INTO TABLE `{$table}` FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\n'";
                $db->query($sql);
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
                $log[] = 'msg: Импортиране на ' . basename($src);
                $db->multyQuery($sql);
                unlink($dest);
            }
            
            $log[] = 'msg: Възстановяването завърши успешно';
            
            return true;
        } catch (core_exception_Expect $e) {
            echo $e->getMessage();
            echo $e->getTraceAsString();
            die;
        }
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
        $temp = self::normDir(EF_TEMP_PATH) . '/backup/';
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
        
        $csvDir = self::getExportCsvDir();
        $res .= self::checkPath($csvDir, 'Директорията за CSV екпорт');
        
        $workDir = core_Setup::get('BACKUP_WORK_DIR');
        $res .= self::checkPath($workDir, 'Работната директория');
        
        $res .= self::checkPath(core_Setup::get('BACKUP_PATH'), 'Директорията за backup');
        
        return $res;
    }
    
    
    /**
     * Прави проверка на даден път
     */
    public static function checkPath($dir, $title, $features = 'dir,readable,writable')
    {
        $features = arr::make($features);
        
        if (empty($dir)) {
            $res = "Директорията {$title} не е определена * ";
            
            return $res;
        }
        foreach ($features as $f) {
            if ($f == 'dir') {
                if (!is_dir($dir)) {
                    
                    return "Директорията `{$dir}` не е директория * ";
                }
                if (!is_readable($dir)) {
                    
                    return "Директорията `{$dir}` не е четима * ";
                }
                if (!is_writable($dir)) {
                    
                    return "Директорията `{$dir}` не е записваема * ";
                }
            }
        }
    }
    
    
    /**
     * Изтриване на директория
     */
    public static function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            
            return true;
        }
        
        if (!is_dir($dir)) {
            
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!self::deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                
                return false;
            }
        }
        
        return rmdir($dir);
    }
    
    
    /**
     * Проверява дали в директорията е празна
     */
    public static function isDirEmpty($dir)
    {
        if (!is_readable($dir)) {
            
            return false;
        }
        
        return (countR(scandir($dir)) <= 2);
    }
    
    
    /**
     * Нормализиране на път до директория
     */
    public static function normDir($dir)
    {
        return rtrim(str_replace('\\', '/', $dir), ' /');
    }
}
