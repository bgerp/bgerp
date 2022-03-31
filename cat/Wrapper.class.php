<?php


/**
 * Клас 'cat_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'cat'
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cat_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('cat_Products', 'Списък', 'powerUser');
        $this->TAB('cat_Groups', 'Групи', 'cat,ceo,sales,purchase');
        $this->TAB('cat_Categories', 'Категории', 'cat,ceo,sales,purchase');
        $this->TAB('cat_Listings', 'Листвания', 'cat,ceo');
        $this->TAB('cat_Boms', 'Рецепти', 'cat,ceo,sales,purchase,planning');
        $this->TAB(array('cat_UoM', 'type' => 'uom'), 'Мерки->Основни', 'packEdit,ceo,sales,purchase');
        $this->TAB(array('cat_UoM', 'type' => 'packaging'), 'Мерки->Опаковки', 'packEdit,ceo,sales,purchase');
        $this->TAB('cat_PackParams', 'Мерки->Размери', 'packEdit,ceo,sales,purchase');

        $this->TAB('cat_Params', 'Параметри', 'cat,ceo,sales,purchase');
        $this->TAB('cat_ProductTplCache', 'Дебъг->Кеш', 'ceo, debug, cat');
        $this->TAB('cat_Serials', 'Дебъг->Генерирани номера', 'debug');
        $this->TAB('cat_products_Params', 'Дебъг->Продуктови параметри', 'debug');

        $this->title = 'Продукти';
    }
}
