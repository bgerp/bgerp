<?php


/**
 * Клас 'cash_Wrapper'
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cash_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('cash_Cases', 'Каси', 'cash, ceo, cashAll');
        $this->TAB('cash_Pko', 'ПКО', 'cash, ceo, cashAll');
        $this->TAB('cash_Rko', 'РКО', 'cash, ceo, cashAll');
        $this->TAB('cash_InternalMoneyTransfer', 'ВКТ', 'cash, ceo, cashAll');
        $this->TAB('cash_ExchangeDocument', 'КОВ', 'cash, ceo, cashAll');
        $this->TAB(array('deals_OpenDeals', 'list', 'show' => 'cash'), 'Чакащи', 'cash, ceo');
        $this->TAB('cash_NonCashPaymentDetails', 'Дебъг->Безкасови плащания', 'debug');

        $this->title = 'Фирмени каси';
    }
}
