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
        $this->TAB('price_Lists', 'Ценоразписи', 'ceo,price');
        $this->TAB('price_Groups', 'Ценови групи', 'ceo,price');
        $this->TAB('price_History', 'История', 'ceo');

    }
}
