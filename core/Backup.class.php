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
        
        // Парола за създаване на архивните файлове
        $pass = core_Setup::get('BACKUP_PASS');
        
        // Форсираме директориите
        $curDir  = self::getDir('current');
        $pastDir = self::getDir('past');
        $workDir  = core_Setup::get('BACKUP_WORK_DIR');
        $sqlDir = self::getExportCsvDir();
        
        // Определяме всички mvc класове, на които ще правим бекъп
        $mvcArr = core_Classes::getOptionsByInterface('core_ManagerIntf');
        $instArr = array();
        $files = array();
        $lockTables = '';

        foreach ($mvcArr as $classId => $className) {
            if(!cls::load($className, true)) continue;
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
        core_SystemLock::block('Процес на архивиране на данните', $time = 600); // 10 мин.
        
        $this->db->query('FLUSH TABLES');
        
        $this->db->query("LOCK TABLES {$lockTables}");
        
        // Флъшваме всичко, каквото има от SQL лога
        self::cron_FlushSqlLog();
        
        // Ако в `current` има нещо - преместваме го в `past`
        if (!self::isDirEmpty($curDir)) {
            // Изтриваме директорията past
            self::deleteDirectory($pastDir);
            
            // Преименуваме текущата директория на past
            rename($curDir, $pastDir);
        }
        
        // Създаваме празна текуща директория
        core_Os::forceDir($curDir);
        
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
            
            if (file_exists($past)) {
                $lmtTable = $this->db->getLMT($table);
                
                // Таблицата не е променяна, нама да променяме и ZIP файла
                if ($lmtTable < filemtime($past)) {
                    debug::log("Таблица `{$table}` е баз промени");
                    copy($past, $dest);
                    continue;
                }
            }
            
            $query = "SELECT * 
                      INTO OUTFILE '{$sqlPath}'
                      FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
                      LINES TERMINATED BY '\n'
                      FROM `{$table}` WHERE id > 0";
            debug::log("Експорт в CSV на таблица `{$table}`");
            $this->db->query($query);
            if($sqlPath != $path) {
                rename($sqlPath, $path);
            }
            $files[$path] = $dest;
        }
        
        // Освеобождаваме LOCK-а на таблиците
        $this->db->query('UNLOCK TABLES');
        
        // Освобождаваме системата
        core_SystemLock::remove();
        
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
        
        foreach ($files as $path => $dest) {
            if (file_exists($dest)) {
                unlink($dest);
            }
            debug::log('Компресиране на ' . basename($dest));
            archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
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
    }


    /**
     * Връща пътя за експортиране на CSV файлове
     */
    public static function getExportCsvDir()
    {   
        // Вземаме манипулатора на базата данни
        $db = cls::get('core_Db');

        $mysqlCsvPath = $db->getVariable('secure_file_priv');

        if($mysqlCsvPath === '') {

            return self::normDir(core_Setup::get('BACKUP_WORK_DIR'));
        }

        if($mysqlCsvPath != 'NULL' && is_dir($mysqlCsvPath) && is_readable() && is_writable()) {

            return self::normDir($mysqlCsvPath);
        }
    }


    /**
     * Връща посочената директория за бекъп
     */
    public static function getDir($subDir)
    {
        $dir = self::normDir(core_Setup::get('BACKUP_PATH')) . '/' . $subDir;

        if(core_Os::forceDir($dir)) {

            return $dir;
        }
    }
   
    

    /**
     * Добавя mySQL заявките в SQL лога
     */
    public static function addSqlLog($sql)
    {
        if (defined('CORE_BACKUP_ENABLED') && CORE_BACKUP_ENABLED == 'yes') {
            $path = self::getSqlLogPath();
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
        }
    }
    
    
    /**
     * Връща пътя до SLQ лога за текущата база
     */
    public static function getSqlLogPath()
    {
        static $path;
        if (!$path) {
            core_Os::forceDir($wDir = self::normDir(core_Setup::get('BACKUP_WORK_DIR')));
            $path = $wDir . '/' . EF_DB_NAME . '.log.sql';
        }
        
        return $path;
    }
    
    
    /**
     * Възстановява системата от направен бекъп
     */
    public static function restore(&$log)
    {   
        try {
            core_App::setTimeLimit(120);
            
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
            $dbStructZip = $path . 'db_structure.sql.zip';
            $dbStructSql = $path . 'db_structure.sql';
            expect(file_exists($dbStructZip), $dbStructZip);
            @unlink($dbStructSql);
            $log[] = 'msg: Разкомпресиране на структурата на таблиците';
            archive_Adapter::uncompress($dbStructZip, $path, $pass);
            expect(file_exists($dbStructSql));
            $sql = file_get_contents($dbStructSql);
            unlink($dbStructSql);
            
            $log[] = 'msg: Създаване на структурата на таблиците';
            $db->multyQuery($sql);
            
            // Наливаме съдържанието от всички налични CSV файлове
            // Извличаме от CSV последователно всички таблици
            $files = glob($path . '*.csv.zip');
            foreach ($files as $src) {
                $src = str_replace('\\', '/', $src);
                core_App::setTimeLimit(120);
                $dest = substr($src, 0, -4);
                $table = substr(basename($src), 0, -8);
                @unlink($dest);
                $log[] = 'msg: Разкомпресиране на ' . $src;
                archive_Adapter::uncompress($src, $path, $pass);
                expect(file_exists($dest));
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
                $dest = substr($src, 0, -4);
                @unlink($dest);
                $log[] = 'msg: Разкомпресиране на ' . $src;
                archive_Adapter::uncompress($src, $path, $pass);
                expect(file_exists($dest));
                $sql = file_get_contents($dest);
                $log[] = 'msg: Импортиране на ' . $src;
                $db->multyQuery($sql);
                unlink($dest);
            }
            
            $log[] = 'msg: Възстановяването завърши успешно';
            
            return true;
        } catch(core_exception_Expect $e) {

            echo $e->getMessage();
            echo $e->getTraceAsString();
            die;
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

        $csvDir = self::getExportCsvDir();
        $res .= self::checkPath($csvDir, 'Директорията за CSV екпорт');

        $workDir  = core_Setup::get('BACKUP_WORK_DIR');
        $res .= self::checkPath($workDir, 'Работната директория');
        
        $res .= self::checkPath(core_Setup::get('BACKUP_PATH'), 'Директорията за backup');
        
        return $res;
    }


    /**
     * Прави проверка на даден път
     */
    static function checkPath($dir, $title, $features = 'dir,readable,writable')
    {
        $features = arr::make($features);
        
        if(empty($dir)) {
            $res = "Директорията {$title} не е определена * ";

            return $res;
        }
        foreach($features as $f) {
            if($f == 'dir') {
                if(!is_dir($dir)) {
                    
                    return "Директорията `{$dir}` не е директория * ";
                }
                if(!is_readable($dir)) {
                    
                    return "Директорията `{$dir}` не е четима * ";
                }
                if(!is_writable($dir)) {
                    
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

        return (count(scandir($dir)) <= 2);
    }


    /**
     * Нормализиране на път до директория
     */
    public static function normDir($dir)
    {
        return rtrim(str_replace('\\', '/', $dir), ' /');
    }
}
