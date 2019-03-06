<?php



/**
 * Клас 'h18_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'h18'
 *
 *
 * @category  bgerp
 * @package   H18
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class h18_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('h18_CashRko', 'РКО', 'admin');
        $this->TAB('h18_CashPko', 'ПКО', 'admin');
        $this->TAB('h18_PosPoints', 'Точки на продажби', 'admin');
        $this->TAB('h18_PosReceipts', 'Касови бележки', 'admin');
        $this->TAB('h18_PosReceiptDetails', 'Касови бележки - детайли', 'admin');
        $this->TAB('h18_PosStocks', 'Артикули ПОС', 'admin');
        $this->TAB('h18_SalesInvoices', 'Фактури', 'admin');
        $this->TAB('h18_SalesInvoiceDetails', 'Фактури - детайли', 'admin');
        $this->TAB('h18_SalesSales', 'Продажби', 'admin');
        $this->TAB('h18_SalesSalesDetails', 'Продажби - детайли', 'admin');
        $this->TAB('h18_SalesServices', 'Услуги', 'admin');
        $this->TAB('h18_CatProducts', 'Продукти', 'admin');
        $this->TAB('h18_CoreRoles', 'Роли', 'admin');
        $this->TAB('h18_CoreUsers', 'Потребители', 'admin');
        $this->TAB('h18_CrmCompanies', 'Контрагенти', 'admin');
        
        $this->title = 'H18';
    }
}