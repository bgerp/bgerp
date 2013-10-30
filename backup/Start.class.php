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
     * Начална точка за стартиране на архивирането
     * 
     * 
     */
    static function Start()
    {
        
        // Дали е време за пълен mysqldump?
        $conf = core_Packs::getConfig("backup");
        
        $daysArr = explode(',', $conf->BACKUP_WEEKDAYS_FULL);
        $currDay = date('N', time());
        // Проверяваме за деня
        if (array_search($currDay, $daysArr) !== false) {
            // Проверяваме за часа
            if ($conf->BACKUP_WEEKDAYS_FULL == date("H", time())) {
                // Прави пълен бекъп, като ресетва бинарните логове
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
        }
        
        // Проверяваме дали е време да запишем бинарния лог
        $minutesUnix = floor(time()/60);
        if ($minutesUnix % $conf->BACKUP_BINLOG_PER_MINUTES == 0 ) {
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
        
        return ("Не е време за бекъпване");
    }    
    
    /**
     * Стартиране от крон-а
     *
     * 
     */
    static function cron_Start()
    {
        self::Start();
    }
    
    /**
     * Метод за извикване през WEB
     *
     *
     */
    public function act_Start()
    {
        return self::Start();
    }
    
}