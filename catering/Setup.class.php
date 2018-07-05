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
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'catering_Menu';
    
    
    /**
     * Екшън - входна точка в пакета.
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Кетъринг за служителите';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
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
    public $roles = 'catering';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(2.39, 'Обслужване', 'Кетъринг', 'catering_Menu', 'default', 'catering, ceo'),
        );

    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
