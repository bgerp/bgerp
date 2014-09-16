<?php



/**
 * Клас 'findeals_Wrapper'
 *
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('findeals_Deals', 'Финансови->Сделки', 'findealsMaster, ceo');
        $this->TAB('findeals_ClosedDeals', 'Финансови->Приключвания', 'findealsMaster, ceo');
        $this->TAB('findeals_AdvanceDeals', 'ПОЛ->Аванси', 'findealsMaster, ceo');
        $this->TAB('findeals_AdvanceReports', 'ПОЛ->Отчети', 'findealsMaster, ceo');
        $this->TAB('findeals_DebitDocuments', 'Прехвърляния->Вземания', 'findealsMaster, ceo');
        $this->TAB('findeals_CreditDocuments', 'Прехвърляния->Задължения', 'findealsMaster, ceo');
        
        $this->title = 'Сделки';
    }
}