<?php


/**
 * Уникален префикс за имената на архивираните файлове
 */
defIfNot('BACKUP_PREFIX', '');


/**
 * Използван клас за реализиране на архива - локална система, FTP, rsync, Amazon ...
 */
defIfNot('STORAGE_TYPE', 'local');





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
    var $startCtr = 'backup';
    
    
    /**
     * Екшън на връзката от менюто core_Packs
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Архивиране на системата: база данни, конфигурация, файлове";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
               
       'BACKUP_PREFIX'   => array ('varchar', 'caption=Префикс за архивираните файлове'),
           
       'STORAGE_TYPE'   => array ('enum("local", "ftp", "rsync", "amazon")', 'caption=Тип на мястото за архивиране'), 
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
    	
        // Инсталираме
        
        
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
