<?php


/**
 * Клас 'price_Wrapper'
 *
 * "Опаковка" на изгледа на ценовия раздел в каталога
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class price_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('price_Lists', 'Политики->Всички политики', 'price,sales,ceo');
        $this->TAB('price_ListToCustomers', 'Политики->Клиентски политики', 'price,ceo');
        $this->TAB('price_ListDocs', 'Ценоразписи', 'sales, priceDealer, ceo');
        $this->TAB('price_Updates', 'Обновяване', 'priceMaster,ceo');
        $this->TAB('price_Cache', 'Дебъг->Кеш', 'debug');
    }
}
