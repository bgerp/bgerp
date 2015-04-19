<?php



/**
 * Клас 'cal_Setup' - Инаталиране на пакета "Календар"
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cal_Calendar';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Календар за задачи, събития, напомняния и празници";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'cal_Calendar',
            'cal_Tasks',
            'cal_TaskProgresses',
            'cal_Holidays',
        	'cal_Reminders',
    		'cal_TaskConditions'
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'user';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.33, 'Указател', 'Календар', 'cal_Calendar', 'default', "powerUser, admin"),
        );

    
    /**
     * Път до js файла
     */
//    var $commonJS = 'cal/js/mouseEvent.js';
    
    
    /**
     * Път до css файла
     */
//    var $commonCSS = 'cal/tpl/style.css';
   
    /**
     * Деинсталиране
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}