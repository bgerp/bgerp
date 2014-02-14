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
    	$this->TAB('pos_Points', 'Точки на продажба', 'ceo,pos');
        $this->TAB('pos_Receipts', 'Бележки за продажба', 'ceo,pos');
        $this->TAB('pos_Favourites', 'Бързи бутони', 'ceo,pos');
        $this->TAB('pos_Reports', 'Отчети', 'ceo,pos');
        $this->TAB('pos_Payments', 'Средства за плащане', 'ceo,pos');
        
        $this->title = 'Точки на продажба';
    }
}
