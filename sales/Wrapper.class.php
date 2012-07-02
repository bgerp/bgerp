<?php



/**
 * Покупки - опаковка
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        
        
        $this->TAB('sales_Deals', 'Сделки', 'admin,sales');
        $this->TAB('sales_Invoices', 'Фактури', 'admin,sales');
        
        $this->title = 'Покупки';
        
    }
}