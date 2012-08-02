<?php



/**
 * Клас 'cat_wrapper_Prices'
 *
 * "Опаковка" на изгледа на ценовия раздел в каталога
 *
 *
 * @category  bgerp
 * @package   catpr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class catpr_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
      
        
        $this->TAB('catpr_Costs', 'Себестойности', 'admin,user');
        $this->TAB('catpr_Pricegroups', 'Групи продукти', 'admin,user');
        $this->TAB('catpr_Discounts', 'Класове клиенти', 'admin,user');
        $this->TAB('catpr_Pricelists', 'Ценоразписи', 'admin,user');
      
        $this->title = 'Цени « Продукти';
    }
}
