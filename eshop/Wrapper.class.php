<?php


/**
 * Клас 'eshop_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'eshop'
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class eshop_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('eshop_Groups', 'Групи', 'ceo,eshop');
        $this->TAB('eshop_Products', 'Артикули->Списък', 'ceo,eshop');
        $this->TAB('eshop_ProductDetails', 'Артикули->Опции', 'ceo,eshop');
        $this->TAB('eshop_Carts', 'Кошници', 'eshop,ceo');
        $this->TAB('eshop_Settings', 'Настройки', 'ceo,eshop,admin');
        $this->TAB('eshop_Favourites', 'Дебъг->Любими артикули', 'debug');
    }
}
