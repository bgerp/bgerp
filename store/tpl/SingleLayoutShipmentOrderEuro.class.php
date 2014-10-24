<?php


/**
 * Помощен модел за лесна работа с баланс, в който участват само определени пера и сметки
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_tpl_SingleLayoutShipmentOrderEuro extends sales_tpl_InvoiceHeaderEuro {
	
	
	/**
	 * Метод който подава данните на мастъра за обработка на скрипта
	 *
	 * @param core_Mvc $mvc - мастър на документа
	 * @param stdClass $data - данни
	 * @return void
	 */
	public function modifyMasterData(core_Mvc $mvc, &$data)
	{
		$euroRate = round(currency_CurrencyRates::getRate($data->rec->valior, 'EUR', NULL), 4);
		
		$Double = cls::get('type_Double');
		$data->row->euroRate = $Double->toVerbal($euroRate);
		
		$Double->params['decimals'] = 2;
		$data->row->amountEuro = $Double->toVerbal($data->rec->amountDelivered / $euroRate);
	}
}