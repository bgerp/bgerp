<?php


/**
 * Покупки - опаковка
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('sales_Sales', 'Продажби', 'ceo,sales,acc');
        $this->TAB('sales_Quotations', 'Оферти', 'ceo,sales');
        $this->TAB('sales_Invoices', 'Фактури', 'ceo,sales,acc');
        $this->TAB('sales_Proformas', 'Проформи', 'ceo,sales,acc');
        $this->TAB('sales_Services', 'Протоколи', 'ceo,sales');
        $this->TAB('dec_Declarations', 'Декларации->Списък', 'ceo,dec');
        $this->TAB('dec_Statements', 'Декларации->Твърдения', 'ceo,dec');
        $this->TAB('dec_Materials', 'Декларации->Материали', 'ceo,dec');
        $this->TAB('sales_ClosedDeals', 'Приключвания', 'ceo,sales');
        $this->TAB('sales_Routes', 'Маршрути', 'ceo,sales');
        $this->TAB('sales_PrimeCostByDocument', 'Дебъг->Делти', 'admin,ceo,debug');
        $this->TAB('sales_TransportValues', 'Дебъг->Навла', 'debug');
        $this->TAB('sales_ProductRelations', 'Дебъг->Сходни продукти', 'debug');
        $this->TAB('sales_ProductRatings', 'Дебъг->Продуктови рейтинги', 'debug');
        
        $this->title = 'Продажби « Търговия';
        Mode::set('menuPage', 'Търговия:Продажби');
    }
}
