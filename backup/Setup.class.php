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
 * Дни от седмицата /по ISO-8601/, в които се прави пълен бекъп 1-Понеделник, 2-Вторник ...
 */
defIfNot('BACKUP_WEEKDAYS_FULL', '1,2,3,4,5,6,7');

/**
 * Час в който се прави пълния бекъп
 */
defIfNot('BACKUP_HOUR_FULL', '5');

/**
 * През колко минути се прави копие на binlog-овете
 */
defIfNot('BACKUP_BINLOG_PER_MINUTES', '5');

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
     * Контролер на връзката от менюто core_Packs
     */
    //var $startCtr = 'starter';
    
    
    /**
     * Екшън на връзката от менюто core_Packs
     */
    //var $startAct = 'default';
    
    
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
       'BACKUP_WEEKDAYS_FULL'   => array ('varchar', 'caption=Дни от седмицата за пълен бекъп'), 
       'BACKUP_HOUR_FULL'   => array ('int', 'caption=Час за пълен бекъп'),
       'BACKUP_BINLOG_PER_MINUTES'   => array ('int', 'caption=На колко минути се прави бекъп на бинарния лог'),
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
    	
    	// Залагаме в cron
    	$rec = new stdClass();
    	$rec->systemId = 'BackupStart';
    	$rec->description = 'Архивиране данни, файлове, конфигурация';
    	$rec->controller = 'backup_Start';
    	$rec->action = 'start';
    	$rec->period = 1;
    	$rec->offset = 0;
    	$rec->delay = 0;
    	$rec->timeLimit = 50;
    	
    	$Cron = cls::get('core_Cron');
    	
    	if ($Cron->addOnce($rec)) {
    	    $html .= "<li><font color='green'>Задаване по крон да стартира бекъп-а.</font></li>";
    	} else {
    	    $html .= "<li>Отпреди Cron е бил нагласен да стартира бекъп-а.</li>";
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
