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
    public function description()
    {
        $this->TAB('currency_Currencies', 'Списък', 'ceo,admin,cash,bank,currency');
        //$this->TAB('currency_CurrencyGroups', 'Групи валути', 'ceo,admin,cash,bank,currency');
        $this->TAB('currency_CurrencyRates', 'Валутни курсове', 'ceo,admin,cash,bank,currency');
        $this->TAB('currency_FinIndexes', 'Индекси', 'ceo,admin,cash,bank,currency');
        
        $this->title = 'Валути « Финанси';
    }
}
