<?php



/**
 * Клас 'price_Wrapper'
 *
 * "Опаковка" на изгледа на ценовия раздел в каталога
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class price_Wrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('price_Lists', 'Политики', 'price,sales,ceo');
        $this->TAB('price_ListDocs', 'Ценоразписи', 'sales, priceDealer, ceo');
        $this->TAB('price_Updates', 'Правила за обновяване', 'priceMaster,ceo');
        $this->TAB('price_History', 'Кеш', 'priceMaster,ceo');
        $this->TAB('price_ListToCustomers', 'Клиентски политики', 'price,ceo');
	}
}
