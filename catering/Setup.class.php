<?php



/**
 * class catering_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра на кетъринга
 *
 *
 * @category  bgerp
 * @package   catering
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class catering_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'catering_Menu';
    
    
    /**
     * Екшън - входна точка в пакета.
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Кетъринг за служителите";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'catering_Menu',
            'catering_MenuDetails',
            'catering_Companies',
            'catering_EmployeesList',
            'catering_Requests',
            'catering_RequestDetails',
            'catering_Orders'
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'catering';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.39, 'Обслужване', 'Кетъринг', 'catering_Menu', 'default', "catering, ceo"),
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