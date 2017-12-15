<?php



/**
 * Транспорт
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_Wrapper extends plg_ProtoWrapper
{

	
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('trans_Lines', 'Линии', 'ceo,trans');
        $this->TAB('trans_Cmrs', 'ЧМР', 'ceo,trans');
    	$this->TAB('trans_Vehicles', 'Превозни средства', 'ceo,trans');
        $this->title = 'Транспорт';
    }
}