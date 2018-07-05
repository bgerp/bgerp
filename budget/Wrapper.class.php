<?php



/**
 * Бюджетиране - опаковка
 *
 *
 * @category  bgerp
 * @package   budget
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class budget_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('budget_Assets', 'Парични средства', 'ceo,budget');
        $this->TAB('budget_IncomeExpenses', 'Приходи / Разходи', 'ceo,budget');
        $this->TAB('budget_Balances', 'Баланс', 'ceo,budget');
        $this->TAB('budget_Reports', 'По подразделения / Дейности', 'ceo,budget');
    }
}
