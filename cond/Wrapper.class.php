<?php


/**
 * Клас 'cond_Wrapper'
 *
 * Поддържа системното меню на пакета trans
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('cond_DeliveryTerms', 'Доставки', 'ceo,admin');
        $this->TAB('cond_PaymentMethods', 'Плащания->Методи', 'ceo,admin');
        $this->TAB('cond_Payments', 'Плащания->Средства', 'ceo,admin');
        $this->TAB('cond_TaxAndFees', 'Данъци и такси', 'ceo,admin');
        $this->TAB('cond_Countries', 'Търг. условия->Условия', 'ceo,admin');
        $this->TAB('cond_Parameters', 'Търг. условия->Видове', 'ceo,admin');
        $this->TAB('cond_Texts', 'Пасажи->Текстове', 'ceo,admin');
        $this->TAB('cond_Groups', 'Пасажи->Групи', 'ceo,admin');
        $this->TAB('cond_Ranges', 'Диапазони', 'ceo,admin');
        $this->TAB('uiext_Labels', 'Тагове', 'ceo,admin');
        $this->TAB('doc_LinkedTemplates', 'Връзки', 'admin');
        $this->TAB('cond_ConditionsToCustomers', 'Debug->Условия към контрагенти', 'debug');
        $this->TAB('uiext_ObjectLabels', 'Debug->Тагнати обекти', 'debug');
        
        $this->title = 'Терминология';
    }
}
