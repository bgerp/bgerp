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
        exec("mysqldump --no-data --no-create-info --no-create-db --skip-set-charset --skip-comments -u" . $conf->BACKUP_MYSQL_USER_NAME. " -p" . $conf->BACKUP_MYSQL_USER_PASS. " bgerp 2>&1", $output ,  $returnVar);
        if ($returnVar !==0) {
            core_Logs::add("Backup", "", "FULL Backup ERROR!");

            exit(1);
        }
        
        exec("mysqldump --lock-tables --delete-master-logs -u"
              . BACKUP_MYSQL_USER_NAME . " -p" . BACKUP_MYSQL_USER_PASS . " " . EF_DB_NAME 
              . " | gzip -9 >" . EF_TEMP_PATH ."/" . BACKUP_PREFIX . "_" . EF_DB_NAME
            );
        // Сваляме мета файла с описанията за бекъпите
        
        // Добавяме нов запис за пълния бекъп
        // Качваме EF_TEMP_PATH ."/" . BACKUP_PREFIX . "_" . EF_DB_NAME.gz
        // като BACKUP_PREFIX . "_" . EF_DB_NAME . "_". date("D_M_Y_H_i") . ".gz"
        // Качваме и мета файла
        // Изтриваме бекъп-а от temp-a и metata
                
        core_Logs::add("Backup", "", "FULL Backup OK!");
        
        return "FULL Backup OK!"; 
    }

    static function BinLog()
    {
        // Взима бинарния лог
        // 1. взимаме името на текущия лог
        // 2. флъшваме лог-а
        // 3. взимаме съдържанието на binlog-a
        // 4. сваля се метафайла
        // 5. добавя се инфо за бинлога
        // 6. Качва се binloga с подходящо име
        // 7. Качва се и мета файла
            
        core_Logs::add("Backup", "", "Backup binlog OK!");
            
        return "DIFF Backup OK!";
    }    
    
    /**
     * Стартиране от крон-а
     *
     * 
     */
    static function cron_StartFull()
    {
        self::StartFull();
    }
    
    /**
     * Метод за извикване през WEB
     *
     *
     */
    public function act_StartFull()
    {
        return self::StartFull();
    }
    
}