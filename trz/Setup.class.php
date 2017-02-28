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
            'trz_PayrollDetails',
            'trz_SalaryPayroll',
            'trz_SalaryIndicatorNames',
		    'trz_SalaryIndicators',
		    'trz_SalaryRules',
            'trz_Bonuses',
            'trz_Sickdays',
            'trz_Trips',
            'trz_Fines',
            'trz_Requests',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'trz';

    
     
    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
        array(
            'systemId' => "Salary Rules",
            'description' => "Правила за формиране на работна заплата",
            'controller' => "trz_SalaryRules",
            'action' => "SalaryRules",
            'period' => 180,
            'offset' => 15,
            'timeLimit' => 100
        ),

        array(
            'systemId' => "CollectIndicators",
            'description' => "Изпращане на данните към показателите за заплатите",
            'controller' => "trz_SalaryIndicators",
            'action' => "Indicators",
            'period' => 180,
            'offset' => 60,
        ),
        
        array(
            'systemId' => 'CalculateSalary',
            'description' => 'Изчисляване на заработката',
            'controller' => 'trz_SalaryPayroll',
            'action' => 'CalcSalaryPay',
            'period' => 1440,
            'offset' => 23,
            'timeLimit' => 600,
        ),
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