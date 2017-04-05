<?php



/**
 * Клас 'cash_Wrapper'
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('cash_Cases', 'Каси', 'cash, ceo');
        $this->TAB('cash_Pko', 'ПКО', 'cash, ceo');
        $this->TAB('cash_Rko', 'РКО', 'cash, ceo');
        $this->TAB('cash_InternalMoneyTransfer', 'ВКТ', 'cash, ceo');
        $this->TAB('cash_ExchangeDocument', 'КОВ', 'cash, ceo');

        $this->title = 'Фирмени каси';
    }
}