<?php



/**
 * Бюджетиране - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   budget
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class budget_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'budget_Assets';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Финансово бюджетиране";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'budget_Assets',
            'budget_IncomeExpenses',
            'budget_Balances',
            'budget_Reports',
        );

    /**
     * Роли за достъп до модула
     */
    var $roles = 'budget'; 
  
    
    /**
     * Връзки от менюто, сочещи към модула
     */
//     var $menuItems = array(
//             array(2.2, 'Финанси', 'Бюджет', 'budget_Assets', 'default', "budget, ceo"),
//         );    

    
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