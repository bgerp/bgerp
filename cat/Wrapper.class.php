<?php



/**
 * Клас 'cat_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'cat'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('cat_Products', 'Списък', 'ceo,user');
        $this->TAB('cat_Groups', 'Маркери', 'ceo,user');
        $this->TAB('cat_Categories', 'Категории', 'ceo,user');
        $this->TAB('cat_Boms', 'Рецепти', 'ceo,cat');
        $this->TAB('cat_Packagings', 'Опаковки', 'ceo,user');
        $this->TAB('cat_Params', 'Параметри', 'ceo,user');
        $this->TAB('cat_UoM', 'Мерки', 'ceo,user');
        $this->TAB('cat_ProductTplCache', 'Кеш', 'ceo,cat');
        
        $this->title = 'Продукти';
    }
}