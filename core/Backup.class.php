<?php

/**
 * Включена ли е бекъп функционалността?
 */
defIfNot('BGERP_BACKUP_ENABLED', false);


/**
 * Парола за архиви
 */
defIfNot('BGERP_BACKUP_PASS', '');


/**
 * Работна директория за бекъпите
 */
defIfNot('BGERP_BACKUP_WORK_DIR', EF_UPLOADS_PATH . '/backup_work');


/**
 * Път до текущия и миналия бекъп
 */
defIfNot('BGERP_BACKUP_PATH', EF_UPLOADS_PATH . '/backup');


/**
 * Колко минути е периода за флъшване на SQL лога
 */
defIfNot('BGERP_BACKUP_SQL_LOG_FLUSH_PERIOD', 60);


/**
 * Колко колко минути е периода за пълен бекъп?
 */
defIfNot('BGERP_BACKUP_CREATE_FULL_PERIOD', 60 * 24);


/**
 * В колко минути след периода да започва пълният бекъп?
 */
defIfNot('BGERP_BACKUP_CREATE_FULL_OFFSET', 60 * 3 + 50);


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
        if(!BGERP_BACKUP_ENABLED) return;

        core_App::setTimeLimit(120);
        
        // Парола за създаване на архивните файлове
        $pass = BGERP_BACKUP_PASS;

        // Форсираме директориите
        core_Os::forceDir($curPath = BGERP_BACKUP_PATH . '/current');
        core_Os::forceDir($pastPath = BGERP_BACKUP_PATH . '/past');
        
        if (!self::isDirEmpty($curPath)) {
            // Изтриваме директорията past
            self::deleteDirectory($pastPath);
            
            // Преименуваме текущата директория на past
            rename($curPath, $pastPath);
        }
        
        // Създаваме празна текуща директория
        core_Os::forceDir($curPath = BGERP_BACKUP_PATH . '/current');
        
        // Определяме всички mvc класове, на които ще правим бекъп
        $mvcArr = core_Classes::getOptionsByInterface('core_ManagerIntf');
        $instArr = array();
        $files = array();
        
        foreach ($mvcArr as $classId => $className) {
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

        foreach ($instArr as $table => $inst) {
            core_App::setTimeLimit(120);
            
            if ($inst === null) {
                continue;
            }
            
            $path = BGERP_BACKUP_WORK_DIR . '/' . $table . '.csv';
            $dest = $curPath . '/' . $table . '.csv.zip';
            $past = $pastPath . '/' . $table . '.csv.zip';
            
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
                    copy($past, $dest);
                    continue;
                }
            }
            
            $query = "SELECT * 
                      INTO OUTFILE '{$path}'
                      FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
                      LINES TERMINATED BY '\n'
                      FROM `{$table}` WHERE id > 0";
            
            $this->db->query($query);
            $files[$path] = $dest;
        }
        
        // Освеобождаваме LOCK-а на таблиците
        $this->db->query('UNLOCK TABLES');
        
        // Освобождаваме системата
        core_SystemLock::remove();
        
        $dbStructure = '';
        
        // Запазваме структурата на базата
        foreach ($instArr as $table => $inst) {
            $query = "SHOW CREATE TABLE `{$table}`";
            $dbRes = $this->db->query($query);
            $res = $this->db->fetchArray($dbRes);
            
            //$dbStructure .= "\nDROP TABLE IF EXISTS `{$table}`;";
            $dbStructure .= "\n" . array_values($res)[1] . ';';
        }
        
        if ($dbStructure = trim($dbStructure)) {
            $path = BGERP_BACKUP_WORK_DIR . '/db_structure.sql';
            $dest = BGERP_BACKUP_PATH . '/current/db_structure.sql.zip';
            if (file_exists($dest)) {
                unlink($dest);
            }
            file_put_contents($path, $dbStructure);
            archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
        }
        
        foreach ($files as $path => $dest) {
            if (file_exists($dest)) {
                unlink($dest);
            }
            archive_Adapter::compressFile($path, $dest, $pass, '-sdel');
        }
        
        // Бекъп на двата конфиг файла
        $indCfg = EF_INDEX_PATH . '/index.cfg.php';
        if (file_exists($indCfg)) {
            expect(is_readable($indCfg));
            $indZip = BGERP_BACKUP_PATH . '/current/index.cfg.php.zip';
            if (file_exists($indZip)) {
                unlink($indZip);
            }
            archive_Adapter::compressFile($indCfg, $indZip, $pass);
        }
        $appCfg = EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php';
        expect(file_exists($appCfg) && is_readable($appCfg));
        $appZip = BGERP_BACKUP_PATH . '/current/app.cfg.php.zip';
        if (file_exists($appZip)) {
            unlink($appZip);
        }
        archive_Adapter::compressFile($appCfg, $appZip, $pass);
    }
    
    
    /**
     * Добавя mySQL заявките в SQL лога
     */
    public static function addSqlLog($sql)
    {
        if (BGERP_BACKUP_ENABLED) {
            $path = self::getSqlLogPath();
            @file_put_contents($path, $sql . ";\n\r", FILE_APPEND);
        }
    }
    

    /**
     * Флъшване на SQL лога към текущата бекъп директория
     */
    public static function cron_FlushSqlLog()
    {
        if (BGERP_BACKUP_ENABLED) {
            $path = self::getSqlLogPath();
            // Не може да се флъшва, а бекъпът е зададен
            if(!file_exists($path) || !is_readable($path) || !filesize($path)) return;
            $file = basename($path);
            $newFile = date('Y-m-d_H-i-s') . '.log.sql';
            $newPath = str_replace("/{$file}", "/{$newFile}", $path);
            rename($path, $newPath);
            core_Os::forceDir($curPath = BGERP_BACKUP_PATH . '/current');
            $dest = $curPath . '/' . $newFile . '.zip';
            archive_Adapter::compressFile($newPath, $dest, BGERP_BACKUP_PASS, '-sdel');
        }
    }



    /**
     * Връща пътя до SLQ лога за текущата база
     */
    public static function getSqlLogPath()
    {
        static $path;
        if(!$path) {
            core_Os::forceDir($wDir = BGERP_BACKUP_WORK_DIR);
            $path = $wDir . '/' . EF_DB_NAME . '.log.sql';
        }

        return $path;
    }


    /**
     * Възстановява системата от направен бекъп
     */
    public function act_Restore()
    {
        requireRole('debug');

        core_App::setTimeLimit(120);
         
        $path = BGERP_BACKUP_PATH . '/current/';
        $dbName = 'alpha';
        $dbUser = 'root';
        $dbPass = '';
        
        // Парола за разархивиране
        $pass = '';
        
        $db = cls::get(
            'core_Db',
            array('dbName' => $dbName,
                'dbUser' => $dbUser,
                'dbPass' => $dbPass,
            )
        );
        
        // Първо очакваме празна база. Ако в нея има нещо - излизаме
        $dbRes = $db->query("SELECT count(*) AS tablesCnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$dbName}'");
        $res = $db->fetchArray($dbRes);
        
        
        if (array_values($res)[0] > 0) {
            
            return 'The database is not empty.';
        }
        
        // Подготвяме структурата на базата данни
        $dbStructZip = $path . 'db_structure.sql.zip';
        $dbStructSql = $path . 'db_structure.sql';
        expect(file_exists($dbStructZip));
        @unlink($dbStructSql);
        archive_Adapter::uncompress($dbStructZip, $path, $pass);
        expect(file_exists($dbStructSql));
        $sql = file_get_contents($dbStructSql);
        unlink($dbStructSql);
        $db->multyQuery($sql);
        
        // Наливаме съдържанието от всички налични CSV файлове
        // Извличаме от CSV последователно всички таблици
        $files = glob($path . '*.csv.zip');
        foreach ($files as $src) {
            core_App::setTimeLimit(120);
            $dest = substr($src, 0, -4);
            $table = substr(basename($src), 0, -8);
            @unlink($dest);
            archive_Adapter::uncompress($src, $path, $pass);
            expect(file_exists($dest));
            $sql = "LOAD DATA INFILE '{$dest}' INTO TABLE `{$table}` FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\n'";
            $db->query($sql);
            unlink($dest);
        }
        
        // Наливане на наличните SQL логове
        $files = glob($path . '*.log.sql.zip');
        asort($files);

        foreach ($files as $src) {
            core_App::setTimeLimit(120);
            $dest = substr($src, 0, -4);
            @unlink($dest);
            archive_Adapter::uncompress($src, $path, $pass);
            expect(file_exists($dest));
            $sql = file_get_contents($dest);
            $db->multyQuery($sql);
            unlink($dest);
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
            
            return;
        }
        
        return (count(scandir($dir)) < 2);
    }
}
