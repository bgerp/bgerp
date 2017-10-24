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
		// Ако създателя е агент, се записва ивент за създаване на нова оферта
		if(haveRole('agent', $rec->createdBy)){
			$Cover = doc_Folders::getCover($rec->folderId);
			$value = cond_Parameters::getParameter($Cover->getInstance(), $Cover->that, 'autoCreateQuote');
			if($value != 'yes') return;
			
			$Driver = $mvc->getDriver($rec);
			
			// Ако има драйвър
			if(is_object($Driver)){
				
				// И той може да върне цена за артикула, връща се
				$Cover = doc_Folders::getCover($rec->folderId);
				if($Cover->haveInterface('crm_ContragentAccRegIntf')){
					if($Driver->canAutoCalcPrimeCost($rec) === TRUE){
						auto_Calls::setCall('createdInquiryByPartner', $rec, FALSE, TRUE);
					}
				}
			}
		}
	}
}