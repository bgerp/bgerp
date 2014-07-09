<?php



/**
 * Клас 'deals_Wrapper'
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('deals_Deals', 'Финансови->Сделки', 'dealsMaster, ceo');
        $this->TAB('deals_ClosedDeals', 'Финансови->Приключвания', 'dealsMaster, ceo');
        $this->TAB('deals_AdvanceDeals', 'ПОЛ->Аванси', 'dealsMaster, ceo');
        $this->TAB('deals_AdvanceReports', 'ПОЛ->Отчети', 'dealsMaster, ceo');
        $this->TAB('deals_DebitDocuments', 'Прехвърляния->Вземания', 'dealsMaster, ceo');
        $this->TAB('deals_CreditDocuments', 'Прехвърляния->Задължения', 'dealsMaster, ceo');
        
        $this->title = 'Сделки';
    }
}