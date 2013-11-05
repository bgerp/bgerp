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
     * Стартиране на пълното архивиране на MySQL-a
     * 
     * 
     */
    static function Full()
    {
        
        $conf = core_Packs::getConfig('backup');
        
        // проверка дали всичко е наред с mysqldump-a
        exec("mysqldump --no-data --no-create-info --no-create-db --skip-set-charset --skip-comments -h"
                 . $conf->BACKUP_MYSQL_HOST . " -u"
                 . $conf->BACKUP_MYSQL_USER_NAME. " -p"
                 . $conf->BACKUP_MYSQL_USER_PASS. " " . EF_DB_NAME ." 2>&1", $output ,  $returnVar);
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
        $now = date("Y_m_d_H_i");
        $backupFileName = $conf->BACKUP_PREFIX . "_" . EF_DB_NAME . "_" . $now . ".gz";
        $metaFileName = $conf->BACKUP_PREFIX . "_" . EF_DB_NAME;
        
        exec("mysqldump --lock-tables --delete-master-logs -u"
              . $conf->BACKUP_MYSQL_USER_NAME . " -p" . $conf->BACKUP_MYSQL_USER_PASS . " " . EF_DB_NAME 
              . " | gzip -9 >" . EF_TEMP_PATH . "/" . $backupFileName 
            , $output, $returnVar);
        
        if ($returnVar !==0 ) {
            core_Logs::add("Backup", "", "ГРЕШКА full Backup: {$returnVar}");
            
            exit(1);
        }
        
        $storage = "backup_" . $conf->BACKUP_STORAGE_TYPE;
        
        // Сваляме мета файла с описанията за бекъпите
        if (!$storage::getFile($metaFileName)) {
            //Създаваме го
            touch(EF_TEMP_PATH . "/" . $metaFileName);
            $metaArr = array();
        } else {
            $metaArr = unserialize(file_get_contents(EF_TEMP_PATH . "/" . $metaFileName));
        }
        
        if (!is_array($metaArr)) {
            
            core_Logs::add("Backup", "", "Лоша МЕТА информация!");
            exit(1);
        }
        // Добавяме нов запис за пълния бекъп
        $metaArr[][0] = $backupFileName;
        file_put_contents(EF_TEMP_PATH . "/" . $metaFileName, serialize($metaArr));

        $storage = "backup_" . $conf->BACKUP_STORAGE_TYPE;
        
        // Качваме бекъп-а
        $storage::putFile($backupFileName);
           
        // Качваме и мета файла
        $storage::putFile($metaFileName);

        // Изтриваме бекъп-а от temp-a и metata
        unlink(EF_TEMP_PATH . "/" . $backupFileName);
        unlink(EF_TEMP_PATH . "/" . $metaFileName);
        
        core_Logs::add("Backup", "", "FULL Backup OK!");
        
        return "FULL Backup OK!"; 
    }

    /**
     * Съхраняване на бинарния лог на MySQL-a
     * 
     * 
     */
    static function BinLog()
    {
        $conf = core_Packs::getConfig('backup');
        $now = date("Y_m_d_H_i");
        $binLogFileName = $conf->BACKUP_PREFIX . "_" . EF_DB_NAME . "_" . $now . ".binlog.gz";
        $metaFileName = $conf->BACKUP_PREFIX . "_" . EF_DB_NAME;
        
        $storage = "backup_" . $conf->BACKUP_STORAGE_TYPE;
        
        // Взима бинарния лог
        $db = cls::get("core_Db", array('dbUser'=>$conf->BACKUP_MYSQL_USER_NAME,
                'dbHost'=>$conf->BACKUP_MYSQL_HOST,
                'dbPass'=>$conf->BACKUP_MYSQL_USER_PASS,
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
                . $conf->BACKUP_MYSQL_USER_NAME
                . " -p" . $conf->BACKUP_MYSQL_USER_PASS . " {$resArr['file']} -h"
                . $conf->BACKUP_MYSQL_HOST . "| gzip -9 > " . EF_TEMP_PATH . "/" . "{$binLogFileName}", $output, $returnVar);
        if ($returnVar !== 0) {
            core_Logs::add("Backup", "", "ГРЕШКА при mysqlbinlog!");
            
            exit(1);
        }
        // 4. сваля се метафайла
        if (!$storage::getFile($metaFileName)) {
            //Създаваме го
            touch(EF_TEMP_PATH . "/" . $metaFileName);
            $metaArr = array();
        } else {
            $metaArr = unserialize(file_get_contents(EF_TEMP_PATH . "/" . $metaFileName));
        }
        
        if (!is_array($metaArr)) {
        
            core_Logs::add("Backup", "", "Лоша МЕТА информация!");
            exit(1);
        }
        
        // 5. добавя се инфо за бинлога
        $maxKey = max(array_keys($metaArr)); 
        $metaArr[$maxKey][] = $binLogFileName;
        file_put_contents(EF_TEMP_PATH . "/" . $metaFileName, serialize($metaArr));
        
        // 6. Качва се binloga с подходящо име
        $storage::putFile($binLogFileName);
         
        // 7. Качва се и мета файла
        $storage::putFile($metaFileName);
        
        // 8. Изтриваме бекъп-а от temp-a и metata
        unlink(EF_TEMP_PATH . "/" . $binLogFileName);
        unlink(EF_TEMP_PATH . "/" . $metaFileName);
        
        core_Logs::add("Backup", "", "BinLog Backup OK!");
        
            
        return "BinLog Backup OK!";
    }    
    
   /**
     * Стартиране от крон-а
     *
     * 
     */
    static function cron_Full()
    {
        self::Full();
    }
    
    /**
     * Метод за извикване през WEB
     *
     *
     */
    public function act_Full()
    {
        return self::Full();
    }
    
    /**
     * Стартиране от крон-а
     *
     *
     */
    static function cron_BinLog()
    {
        self::BinLog();
    }
    
    /**
     * Метод за извикване през WEB
     *
     *
     */
    public function act_BinLog()
    {
        return self::BinLog();
    }
       
}