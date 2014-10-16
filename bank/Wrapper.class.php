<?php



/**
 * Клас 'bank_Wrapper'
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('bank_Accounts', 'Всички сметки', 'ceo, bank');
        $this->TAB('bank_OwnAccounts', 'Наши сметки', 'ceo, bank');
        $this->TAB('bank_IncomeDocuments', 'Документи->Приходни документи', 'ceo, bank');
        $this->TAB('bank_SpendingDocuments', 'Документи->Разходни документи', 'ceo, bank');
        $this->TAB('bank_InternalMoneyTransfer', 'Документи->Вътрешни трансфери', 'ceo, bank');
        $this->TAB('bank_ExchangeDocument', 'Документи->Обмени на валути', 'ceo, bank');
        $this->TAB('bank_PaymentOrders', 'Бланки->Платежни Нареждания', 'ceo, bank');
        $this->TAB('bank_CashWithdrawOrders', 'Бланки->Нареждане Разписка', 'ceo, bank');
        $this->TAB('bank_DepositSlips', 'Бланки->Вносни Бележки', 'ceo, bank');
        $this->TAB(array('deals_OpenDeals', 'list', 'show' => 'bank'), 'Чакащи', 'bank, ceo');
        
        $this->title = 'Банка';
    }
}