<?php



/**
 * Покупки - опаковка
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('purchase_Purchases', 'Покупки', 'ceo,purchase,acc');
        $this->TAB('purchase_Invoices', 'Фактури', 'ceo,purchase,acc');
    	$this->TAB('purchase_Offers', 'Оферти', 'ceo,purchase');
        $this->TAB('purchase_Services', 'Протоколи', 'ceo,purchase');
        $this->TAB('purchase_ClosedDeals', 'Приключвания', 'ceo,purchase');
        
        $this->title = 'Покупки « Доставки';
        Mode::set('menuPage', 'Доставки:Покупки');
    }
}