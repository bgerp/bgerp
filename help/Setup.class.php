<?php



/**
 * class help_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с модул Help
 *
 *
 * @category  bgerp
 * @package   help
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class help_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'help_Info';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Подсистема за помощ";

    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
   var $managers = array(
            'help_Info',
			'help_Log',

        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'help';

        
    /**
     * Инсталиране на пакета
     */
    function install()
    {  
    	$html = parent::install(); 
    	         
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