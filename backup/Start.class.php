<?php



/**
 * Четене и записване на локални файлове
 *
 *
 * @category  bgerp
 * @package   backup
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Архивиране
 */
class backup_Start extends core_Manager
{
    
    /**
     * Заглавие
     */
    var $title = 'Стартира архивиране';
    
    /**
     * Име на семафора за стартиран процес на бекъп
     */
    private static $lockFileName;
    private static $conf;
    private static $backupFileName;
    private static $binLogFileName;
    private static $metaFileName;
    private static $storage;
    
    function init()
    {
        self::$lockFileName = EF_TEMP_PATH . '/backupLock.tmp';
        self::$conf = core_Packs::getConfig('backup');
        $now = date("Y_m_d_H_i");
        self::$backupFileName = self::$conf->BACKUP_PREFIX . "_" . EF_DB_NAME . "_" . $now . ".full.gz";
        self::$binLogFileName = self::$conf->BACKUP_PREFIX . "_" . EF_DB_NAME . "_" . $now . ".binlog.gz";
        self::$metaFileName = self::$conf->BACKUP_PREFIX . "_" . EF_DB_NAME . "_META";
        self::$storage = core_Cls::get("backup_" . self::$conf->BACKUP_STORAGE_TYPE);
    }
    
    /**
     * Стартиране на пълното архивиране на MySQL-a
     * 
     * 
     */
    static function full()
    {
        if (!self::lock()) {
            core_Logs::add("Backup", "", "Full Backup не може да вземе Lock!");
            
            return ("Full Backup не може да вземе Lock!");
            exit (1);
        }
        
        // проверка дали всичко е наред с mysqldump-a
        exec("mysqldump --no-data --no-create-info --no-create-db --skip-set-charset --skip-comments -h"
                 . self::$conf->BACKUP_MYSQL_HOST . " -u"
                 . self::$conf->BACKUP_MYSQL_USER_NAME. " -p"
                 . self::$conf->BACKUP_MYSQL_USER_PASS. " " . EF_DB_NAME ." 2>&1", $output ,  $returnVar);
        if ($returnVar !== 0) {
            core_Logs::add("Backup", "", "FULL Backup mysqldump ERROR!");

            exit(1);
        }
        
        // проверка дали gzip е наличен
        exec("gzip --help", $output,  $returnVar);
        if ($returnVar !== 0) {
            core_Logs::add("Backup", "", "gzip NOT found");
        
            exit(1);
        }
        
        exec("mysqldump --lock-tables --delete-master-logs -u"
              . self::$conf->BACKUP_MYSQL_USER_NAME . " -p" . self::$conf->BACKUP_MYSQL_USER_PASS . " " . EF_DB_NAME 
              . " | gzip -9 >" . EF_TEMP_PATH . "/" . self::$backupFileName 
            , $output, $returnVar);
        
        if ($returnVar !==0 ) {
            core_Logs::add("Backup", "", "ГРЕШКА full Backup: {$returnVar}");
            
            exit(1);
        }
        
        // Сваляме мета файла с описанията за бекъпите
        if (!self::$storage->getFile(self::$metaFileName)) {
            // Ако го няма - създаваме го
            touch(EF_TEMP_PATH . "/" . self::$metaFileName);
            $metaArr = array();
        } else {
            $metaArr = unserialize(file_get_contents(EF_TEMP_PATH . "/" . self::$metaFileName));
        }
        
        if (!is_array($metaArr)) {
            
            core_Logs::add("Backup", "", "Лоша МЕТА информация!");
            exit(1);
        }
        // Добавяме нов запис за пълния бекъп
        $metaArr[][0] = self::$backupFileName;
        file_put_contents(EF_TEMP_PATH . "/" . self::$metaFileName, serialize($metaArr));

        // Качваме бекъп-а
        self::$storage->putFile(self::$backupFileName);
           
        // Качваме и мета файла
        self::$storage->putFile(self::$metaFileName);

        // Изтриваме бекъп-а от temp-a и metata
        unlink(EF_TEMP_PATH . "/" . self::$backupFileName);
        unlink(EF_TEMP_PATH . "/" . self::$metaFileName);
        
        core_Logs::add("Backup", "", "FULL Backup OK!");
        self::UnLock();
        
        return "FULL Backup OK!"; 
    }

    /**
     * Съхраняване на бинарния лог на MySQL-a
     * 
     * 
     */
    private static function binLog()
    {
        if (!self::Lock()) {
            core_Logs::add("Backup","", "Warning: BinLog не може да вземе Lock.");
            
            exit(1);
        }
        
        // Взима бинарния лог
        $db = cls::get("core_Db", array('dbUser'=>self::$conf->BACKUP_MYSQL_USER_NAME,
                'dbHost'=>self::$conf->BACKUP_MYSQL_HOST,
                'dbPass'=>self::$conf->BACKUP_MYSQL_USER_PASS,
                'dbName'=>'information_schema')
               );
        // 1. взимаме името на текущия лог
        $db->query("SHOW MASTER STATUS");
        $resArr = $db->fetchArray();
        // $resArr['file'] e името на текущия бинлог

        // 2. флъшваме лог-а
        $db->query("FLUSH LOGS");

        // 3. взимаме съдържанието на binlog-a в temp-a и го компресираме
        exec("mysqlbinlog --read-from-remote-server -u"
                . self::$conf->BACKUP_MYSQL_USER_NAME
                . " -p" . self::$conf->BACKUP_MYSQL_USER_PASS . " {$resArr['file']} -h"
                . self::$conf->BACKUP_MYSQL_HOST . "| gzip -9 > " . EF_TEMP_PATH . "/" . self::$binLogFileName, $output, $returnVar);
        if ($returnVar !== 0) {
            core_Logs::add("Backup", "", "ГРЕШКА при mysqlbinlog!");
            
            exit(1);
        }
        // 4. сваля се метафайла
        if (!self::$storage->getFile(self::$metaFileName)) {
            //Създаваме го
            touch(EF_TEMP_PATH . "/" . self::$metaFileName);
            $metaArr = array();
        } else {
            $metaArr = unserialize(file_get_contents(EF_TEMP_PATH . "/" . self::$metaFileName));
        }
        
        if (!is_array($metaArr)) {
            core_Logs::add("Backup", "", "Лоша МЕТА информация!");
            
            exit(1);
        }
        
        // 5. добавя се инфо за бинлога
        $maxKey = max(array_keys($metaArr)); 
        $metaArr[$maxKey][] = self::$binLogFileName;
        file_put_contents(EF_TEMP_PATH . "/" . self::$metaFileName, serialize($metaArr));
        
        // 6. Качва се binloga с подходящо име
        self::$storage->putFile(self::$binLogFileName);
         
        // 7. Качва се и мета файла
        self::$storage->putFile(self::$metaFileName);
        
        // 8. Изтриваме бекъп-а от temp-a и metata
        unlink(EF_TEMP_PATH . "/" . self::$binLogFileName);
        unlink(EF_TEMP_PATH . "/" . self::$metaFileName);
        
        core_Logs::add("Backup", "", "binLog Backup OK!");
        self::unLock();
            
        return "binLog Backup OK!";
    }    
    
    /**
     * Вдига семафор за стартиран бекъп
     * Връща false ако семафора е вече вдигнат
     * 
     *  return boolean
     */
    private static function lock()
    {
        if (self::isLocked()) {
            
            return FALSE;
        }
        
        return touch(self::$lockFileName);
    }
    
    /**
     * Смъква семафора на бекъп-а
     * 
     *  return boolean
     */
    private static function unLock()
    {
        
        return unlink(self::$lockFileName);
    }
    
    /**
     * Показва състоянието на семафора за бекъп
     *
     *  return boolean
     */
    public static function isLocked()
    {
        self::init();
        
        return file_exists(self::$lockFileName);
    }
    
    /**
     * Стартиране от крон-а
     *
     * 
     */
    static function cron_Full()
    {
        self::full();
    }
    
    /**
     * Метод за извикване през WEB
     *
     *
     */
    public function act_Full()
    {
        return self::full();
    }
    
    /**
     * Стартиране от крон-а
     *
     *
     */
    static function cron_BinLog()
    {
        self::binLog();
    }
    
    /**
     * Метод за извикване през WEB
     *
     *
     */
    public function act_BinLog()
    {
        return self::binLog();
    }
       
}