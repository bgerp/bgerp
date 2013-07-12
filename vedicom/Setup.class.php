<?php



/**
 * class vedicom_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с vedicom везни
 *
 *
 * @category  vendors
 * @package   vedicom
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class vedicom_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'vedicom_Weight';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Чете тегло от Vedicom - VEDIA VDI везни";


    /**
     * Необходими пакети
     */
    var $depends = '';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array();
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'vedicom_Weight'
        );
 
    
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