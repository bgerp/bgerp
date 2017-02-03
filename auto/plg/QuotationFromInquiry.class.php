<?php



/**
 * Плъгин 'auto_plg_QuotationFromInquiry' - За автоматично създаване на оферта от запитване
 *
 *
 * @category  bgerp
 * @package   auto
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class auto_plg_QuotationFromInquiry extends core_Plugin
{
	
	
	/**
	 * Изпълнява се след създаване на нов запис
	 */
	public static function on_AfterCreate($mvc, $rec)
	{
		// Ако създателя е агент, се запсива ивент за създаване на нова оферта
		if(haveRole('agent', $rec->createdBy)){
			auto_Calls::setCall('createdInquiryByPartner', $rec);
		}
	}
}