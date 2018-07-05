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
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'budget_Assets';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Финансово бюджетиране';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'budget_Assets',
            'budget_IncomeExpenses',
            'budget_Balances',
            'budget_Reports',
        );

    /**
     * Роли за достъп до модула
     */
    public $roles = 'budget';
  
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(2.2, 'Финанси', 'Бюджет', 'budget_Assets', 'default', 'budget, ceo'),
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
