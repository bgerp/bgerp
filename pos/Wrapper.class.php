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
    	$rId = Request::get('id', 'int');
    	if($rId) {
    		$receiptUrl= array('pos_Receipts', 'single',$rId);
    	} else {
    		$receiptUrl= array('pos_Receipts', 'new');
    	}
        $this->TAB('pos_Points', 'Точки на продажба', 'admin,pos');
        $this->TAB('pos_Receipts', 'Бележки за продажба', 'admin,pos');
        $this->TAB('pos_Favourites', 'Бързи бутони', 'admin,pos');
        $this->TAB('pos_Reports', 'Репорти', 'admin,pos');
        $this->TAB('pos_Payments', 'Методи на плащане', 'admin,pos');
        
        $this->title = 'Точки на Продажба';
    }
}