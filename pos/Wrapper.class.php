<?php



/**
 * Покупки - опаковка
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
    	$this->TAB('pos_Points', 'Точки', 'ceo,pos');
        $this->TAB('pos_Receipts', 'Бележки', 'ceo,pos');
        $this->TAB('pos_Reports', 'Отчети', 'ceo,pos');
        $this->TAB('pos_Stocks', 'Наличности', 'ceo,pos');
        $this->TAB('pos_Favourites', 'Настройки->Бързи бутони', 'ceo,pos');
        $this->TAB('pos_FavouritesCategories', 'Настройки->Категории', 'ceo,pos');
        $this->TAB('pos_Cards', 'Настройки->Клиентски карти', 'ceo,pos');
        
        $this->title = 'Точки на продажба';
    }
}
