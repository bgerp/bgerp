<?php


/**
 * Уникален префикс за имената на архивираните файлове
 */
defIfNot('BACKUP_PREFIX', 'bgerp.local-mitko');


/**
 * Използван клас за реализиране на архива - локална система, FTP, rsync, Amazon ...
 */
defIfNot('BACKUP_STORAGE_TYPE', 'local');

/**
 * Период на който се прави пълен бекъп
 * 5/дена/*24/часа/*60/мин/ = 7200 e всеки петък 00 часа
 * + 4/часа/*60/мин/=240
 * За да е бекъп-а всеки петък в 4:00 през нощта
 * 
 */
defIfNot('BACKUP_FULL_PERIOD', '7440');

/**
 *  Отместване за пълния бекъп
 */
defIfNot('BACKUP_FULL_OFFSET', '50');

/**
 * Час в който се прави пълния бекъп
 */
defIfNot('BACKUP_BINLOG_PERIOD', '5');

/**
 *  Отместване за бинлог бекъп-а
 */
defIfNot('BACKUP_BINLOG_OFFSET', '50');

/**
 * Потребител с права за бекъп на mysql сървъра
 */
defIfNot('BACKUP_MYSQL_USER_NAME', 'backup');

/**
 * Парола на потребителя за бекъп
 */
defIfNot('BACKUP_MYSQL_USER_PASS', 'swordfish');


/**
 * Клас 'backup_Setup' - Начално установяване на пакета 'backup'
 *
 *
 * @category  vendors
 * @package   backup
 * @author    Dimitar Minekov<mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
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
     * Описание на конфигурационните константи
     */
    var $configDescription = array (
            
       'BACKUP_PREFIX'   => array ('varchar', 'caption=Префикс за архивираните файлове'),
       'BACKUP_STORAGE_TYPE'   => array ('enum(local=локален, ftp=ФТП, rsync=rsync)', 'caption=Тип на мястото за архивиране'), 
       'BACKUP_MYSQL_USER_NAME'   => array ('varchar', 'caption=Потребител в MySQL сървъра с права за бекъп'),
       'BACKUP_MYSQL_USER_PASS'   => array ('varchar', 'caption=Парола')
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
    	
    	$conf = core_Packs::getConfig('backup');
    	
    	// Залагаме в cron
    	$rec = new stdClass();
    	$rec->systemId = 'BackupStartFull';
    	$rec->description = 'Архивиране пълните данни на MySQL';
    	$rec->controller = 'backup_Start';
    	$rec->action = 'full';
    	$rec->period = BACKUP_FULL_PERIOD;
    	$rec->offset = BACKUP_FULL_OFFSET;
    	$rec->delay = 0;
    	$rec->timeLimit = 50;
    	
    	$Cron = cls::get('core_Cron');
    	
    	if ($Cron->addOnce($rec)) {
    	    $html .= "<li><font color='green'>Задаване по крон да стартира пълния бекъп.</font></li>";
    	} else {
    	    $html .= "<li>Отпреди Cron е бил нагласен да стартира full бекъп.</li>";
    	}
    	
    	$rec->systemId = 'BackupStartBinLog';
    	$rec->description = 'Архивиране binlog на MySQL';
    	$rec->controller = 'backup_Start';
    	$rec->action = 'binlog';
    	$rec->period = BACKUP_BINLOG_PERIOD;
    	$rec->offset = BACKUP_BINLOG_OFFSET;
    	$rec->delay = 0;
    	$rec->timeLimit = 50;

    	if ($Cron->addOnce($rec)) {
    	    $html .= "<li><font color='green'>Задаване по крон да стартира binlog бекъп.</font></li>";
    	} else {
    	    $html .= "<li>Отпреди Cron е бил нагласен да стартира binlog бекъп.</li>";
    	}
    	 
    	
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}
