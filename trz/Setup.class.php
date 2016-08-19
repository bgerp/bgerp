<?php



/**
 * ТРЗ - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trz_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'trz_SalaryPayroll';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Труд и работна заплата";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'trz_Payroll',
            'trz_SalaryPayroll',
		    'trz_SalaryIndicators',
		    'trz_SalaryRules',
            'trz_Bonuses',
            'trz_Sickdays',
            'trz_Trips',
            'trz_Fines',
            'trz_Orders',
            'trz_Requests',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'trz';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.32, 'Персонал', 'ТРЗ', 'trz_SalaryPayroll', 'default', "trz, ceo"),
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