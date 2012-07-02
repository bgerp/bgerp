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
 * @copyright 2006 - 2012 Experta OOD
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
           
        $this->TAB('cat_Products', 'Списък', 'admin,user');
        $this->TAB('cat_Groups', 'Групи', 'admin,user');
        $this->TAB('cat_Categories', 'Категории', 'admin,user');
        $this->TAB('cat_Packagings', 'Опаковки', 'admin,user');
        $this->TAB('cat_Params', 'Параметри', 'admin,user');
        $this->TAB('cat_UoM', 'Мерки');
        
        $this->title = 'Продукти';
    }
}