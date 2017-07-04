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
class store_tpl_SingleLayoutShipmentOrderEuro extends doc_TplScript {
	
	
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
		currency_CurrencyRates::checkRateAndRedirect($euroRate);
		
		$Double = cls::get('type_Double');
		$data->row->euroRate = $Double->toVerbal($euroRate);
		
		$Double->params['decimals'] = 2;
		$data->row->amountEuro = $Double->toVerbal($data->rec->amountDelivered / $euroRate);
	}


	/**
	 * Метод който подава данните на детайла на мастъра, за обработка на скрипта
	 *
	 * @param core_Mvc $detail - Детайл на документа
	 * @param stdClass $data - данни
	 * @return void
	 */
	public function modifyDetailData(core_Mvc $detail, &$data)
	{
		if(!count($data->rows)) return;
	
		if($data->masterData->rec->currencyId == 'EUR') return;
	
		arr::placeInAssocArray($data->listFields, 'priceEuro=Ед. цена в EUR', 'packPrice');
		$data->listFields['packPrice'] = "Ед. цена";
	
		$euroRate = round(currency_CurrencyRates::getRate($data->masterData->rec->date, 'EUR', NULL), 4);
	    currency_CurrencyRates::checkRateAndRedirect($euroRate);
	    
		$conf = core_Packs::getConfig('core');
		$decPoint = html_entity_decode($conf->EF_NUMBER_DEC_POINT);
	
		foreach ($data->rows as $id => $row){
			$rec = $data->recs[$id];
			$priceEuro = ($rec->packPrice * $data->masterData->rec->currencyRate) / $euroRate;
				
			$Double = cls::get('type_Double');
			$Double->params['decimals'] = 2;
				
			$row->priceEuro = "<span style='float:right'>" . $Double->toVerbal($priceEuro) . "</span>";
		}
	}
}