<?php



/**
 * Клас 'currency_Wrapper'
 *
 *
 * @category  bgerp
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class currency_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на опаковката с табове
     */
    function description()
    {
        
        
        $this->TAB('currency_Currencies', 'Списък');
        $this->TAB('currency_CurrencyGroups', 'Групи валути');
        $this->TAB('currency_CurrencyRates', 'Валутни курсове');
        $this->TAB('currency_FinIndexes',    'Индекси');
        
        $this->title = 'Валути « Финанси';
    }
}