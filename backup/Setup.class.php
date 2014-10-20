<?php



/**
 * Уникален префикс за имената на архивираните файлове
 */
defIfNot('BACKUP_PREFIX', 'bgerp.localhost');


/**
 * Използван клас за реализиране на архива - локална система, FTP, rsync, Amazon ...
 */
defIfNot('BACKUP_STORAGE_TYPE', 'local');


/**
 * Период на който се прави пълен бекъп
 * Всеки петък в 4:00 през нощта
 */
defIfNot('BACKUP_FULL_PERIOD', 5 * 24 * 60);


/**
 * Отместване за пълния бекъп
 */
defIfNot('BACKUP_FULL_OFFSET', 4 * 60);


/**
 * Период в който се прави binlog бекъп-a
 */
defIfNot('BACKUP_BINLOG_PERIOD', 7);


/**
 * Отместване за бинлог бекъп-а
 */
defIfNot('BACKUP_BINLOG_OFFSET', 0);


/**
 * Потребител с права за бекъп на mysql сървъра
 */
defIfNot('BACKUP_MYSQL_USER_NAME', 'backup');


/**
 * Парола на потребителя за бекъп
 */
defIfNot('BACKUP_MYSQL_USER_PASS', 'swordfish');


/**
 * MySql host за бекъп
 */
defIfNot('BACKUP_MYSQL_HOST', 'localhost');


/**
 * Брой пълни бекъпи, които да се пазят
 */
defIfNot('BACKUP_CLEAN_KEEP', 4);


/**
 * Период на почистването
 */
defIfNot('BACKUP_CLEAN_PERIOD', 24 * 60);


/**
 * Отместване на почистването в крон-а
 */
defIfNot('BACKUP_CLEAN_OFFSET', 53);


/**
 * Период на почистването
 */
defIfNot('BACKUP_FILEMAN_PERIOD', 13);


/**
 * Отместване в крон-а на архивирането на Fileman-a
 */
defIfNot('BACKUP_FILEMAN_OFFSET', 0);


/**
 * Парола за криптиране на архива
 */
defIfNot('BACKUP_PASS_OFFSET', 'secret');


/**
 * Път до масива за съхранение на файлове
 */
defIfNot('BACKUP_LOCAL_PATH', '/storage');


/**
 * Клас 'backup_Setup' - Начално установяване на пакета 'backup'
 *
 *
 * @category  bgerp
 * @package   backup
 * @author    Dimitar Minekov<mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class backup_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Архивиране на системата: база данни, конфигурация, файлове";
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'fileman=0.1';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array (
        
        'BACKUP_PREFIX'   => array ('varchar', 'caption=Имена на архивираните файлове->Префикс'),
        'BACKUP_STORAGE_TYPE'   => array ('enum(local=Локално, ftp=FTP, rsync=Rsync)', 'caption=Място за съхранение на архива->Тип'),
        'BACKUP_LOCAL_PATH' => array ('varchar', 'notNull, value=/storage, caption=Локален архив->Път'),
        'BACKUP_MYSQL_USER_NAME'   => array ('varchar', 'caption=Връзка към MySQL (с права за бекъп)->Потребител, hint=(SELECT, RELOAD, SUPER)'),
        'BACKUP_MYSQL_USER_PASS'   => array ('password', 'caption=Връзка към MySQL (с права за бекъп)->Парола'),
        'BACKUP_MYSQL_HOST'     => array ('varchar', 'caption=Връзка към MySQL->Хост'),
        'BACKUP_CLEAN_KEEP'     => array ('int', 'caption=Колко пълни бекъп-и да се пазят?->Брой'),
        'BACKUP_CRYPT'     => array ('enum(yes=Да, no=Не)', 'notNull,value=no,maxRadio=2,caption=Сигурност на архивите->Криптиране'),
        'BACKUP_PASS'     => array ('password', 'caption=Сигурност на архивите->Парола')
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        // Архивиране в локалната файлова система;
        'backup_Local',
        
        // Архивиране по ftp;
        //'backup_Ftp',
        
        // Архивиране по rsync
        //'backup_Rsync',
        
        // Архивиране на Amazon
        //'backup_Amazon',
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        // Отключваме процеса, ако не е бил легално отключен
        backup_Start::unLock();
        
        // Залагаме в cron
        $rec = new stdClass();
        $rec->systemId = 'BackupStartFull';
        $rec->description = 'Архивиране на пълните данни на MySQL';
        $rec->controller = 'backup_Start';
        $rec->action = 'full';
        $rec->period = BACKUP_FULL_PERIOD;
        $rec->offset = BACKUP_FULL_OFFSET;
        $rec->delay = 40;
        $rec->timeLimit = 50;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'BackupStartBinLog';
        $rec->description = 'Архивиране на binlog-а на MySQL';
        $rec->controller = 'backup_Start';
        $rec->action = 'binlog';
        $rec->period = BACKUP_BINLOG_PERIOD;
        $rec->offset = BACKUP_BINLOG_OFFSET;
        $rec->delay = 45;
        $rec->timeLimit = 50;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'BackupClean';
        $rec->description = 'Изтриване на стари бекъпи';
        $rec->controller = 'backup_Start';
        $rec->action = 'clean';
        $rec->period = BACKUP_CLEAN_PERIOD;
        $rec->offset = BACKUP_CLEAN_OFFSET;
        $rec->delay = 50;
        $rec->timeLimit = 50;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'BackupFileman';
        $rec->description = 'Архивиране на файловете от fileman-a';
        $rec->controller = 'backup_Start';
        $rec->action = 'fileman';
        $rec->period = BACKUP_FILEMAN_PERIOD;
        $rec->offset = BACKUP_FILEMAN_OFFSET;
        $rec->delay = 51;
        $rec->timeLimit = 50;
        $html .= core_Cron::addOnce($rec);
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
