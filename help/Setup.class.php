<?php


/**
 * Колко пъти поне да се покаже дадена помощна информация на даден потребител
 */
defIfNot('HELP_MAX_SEE_CNT', 3);

/**
 * Колко време след първото показване, да се показва дадена помощна информация
 * По подразбиране едно денонощие
 */
defIfNot('HELP_MAX_SEE_TIME', 1*24*60*60);


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
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
           
       'HELP_MAX_SEE_TIME' => array ('time', 'caption=Показване на помощна информация на потребител->Максимално време'),

       'HELP_MAX_SEE_CNT'   => array ('int', 'caption=Показване на помощна информация на потребител->Максимален брой пъти'),
    
    );

        
    /**
     * Инсталиране на пакета
     */
    function install()
    {  
    	$html = parent::install(); 
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('helpHint', 'help_Plugin', 'plg_ProtoWrapper', 'family');
 	         
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