<?php


/**
 * 
 *
 *
 * @category  bgerp
 * @package   logs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class log_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'log_Data';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Логове и нотификации";
	
	
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
    		'log_Debug',
    		'log_Data',
    		'log_Actions',
    		'log_Browsers',
    		'log_Classes',
    		'log_Ips',
    		'log_Referer',
    		'migrate::removeMaxCrc',
        );
        
        
    /**
     * 
     */
    public static function removeMaxCrc()
    {
        $max = 2147483647;
        log_Actions::delete("#crc = '{$max}'");
        log_Classes::delete("#crc = '{$max}'");
        log_Data::delete("#actionCrc = '{$max}' OR #classCrc = '{$max}'");
    }
}
