<?php



/**
 * class sms_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със SMS-и
 *
 *
 * @category  vendors
 * @package   sms
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sms_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'sms_Sender';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Регистър на SMS-ите";


    /**
     * Необходими пакети
     */
    var $depends = 'mobio=0.1';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'sms_Sender'
        );

        
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