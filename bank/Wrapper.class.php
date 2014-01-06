<?php



/**
 * Клас 'bank_Wrapper'
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
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
       $this->TAB('bank_IncomeDocuments', 'Документи', 'ceo, bank');
       $this->TAB('bank_PaymentOrders', 'Бланки', 'ceo, bank');
       $this->TAB(array('acc_OpenDeals', 'list', 'show' => 'bank'), 'Сделки', 'bank, ceo');
       
       $this->title = 'Банка';
    }
}