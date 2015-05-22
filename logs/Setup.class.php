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
class logs_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'logs_Data';
    
    
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
    		'logs_Data',
    		'logs_Actions',
    		'logs_Browsers',
    		'logs_Classes',
    		'logs_Ips',
    		'logs_Referer',
        );
}
