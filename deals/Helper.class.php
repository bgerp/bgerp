<?php


/**
 * Помощен клас за конвертиране на суми и цени, изпозлван в бизнес документите
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_Helper
{
	
	/**
	 * Масив за мапване на стойностите от мениджърите
	 */
	private static $map = array(
			'priceFld' 	    => 'packPrice',
			'quantityFld'   => 'packQuantity',
			'amountFld'     => 'amount',
			'rateFld' 	    => 'currencyRate',
			'productId'	    => 'productId',
			'chargeVat'     => 'chargeVat',
			'valior' 	    => 'valior',
			'currencyId'    => 'currencyId',
			'discAmountFld'	=> 'discAmount',
			'discount'	    => 'discount',
			'alwaysHideVat' => FALSE, // TRUE всичко трябва да е без ДДС
		);
	
	
	/**
     * Умно закръгляне на цена
     * 
     * @param double $price   - цена, която ще се закръгля
     * @param int $minDigits  - минимален брой значещи цифри
     * @return double $price  - закръглената цена
     */
	public static function roundPrice($price, $minDigits = 7)
	{
	    // Плаваща прецизност
	    $precision =  max(2, $minDigits - round(log10($price)));
		
	    // Изчисляваме закръглената цена
	    $price = round($price, $precision);
		
	    return $price;
	}
	
	
	/**
	 * Пресмята цена с ддс и без ддс
	 * @param double $price      - цената в основна валута без ддс
	 * @param double $vat        - процента ддс
	 * @param double $rate       - курса на валутата
	 * @return stdClass->noVat   - цената без ддс
	 * 		   stdClass->withVat - цената с ддс
	 */
	private static function calcPrice($price, $vat, $rate)
	{
		$arr = array();
        
        // Конвертиране цените във валутата
        @$arr['noVat'] = $price / $rate;
		@$arr['withVat'] = ($price * (1 + $vat)) / $rate;
		
		$arr['noVat'] = $arr['noVat'];
		$arr['withVat'] = $arr['withVat'];
		
        return (object)$arr;
	}
	
	
	/**
	 * Помощен метод използван в бизнес документите за показване на закръглени цени на редовете
	 * и за изчисляване на общата цена
	 *
	 * @param array $recs - записи от детайли на модел
	 * @param stdClass $masterRec - мастър записа
	 * @param array $map - масив с мапващи стойностите на полета от фунцкията
	 * с полета в модела, има стойности по подрабзиране (@see static::$map)
	 */
	public static function fillRecs(&$mvc, &$recs, &$masterRec, $map = array())
	{
		if(count($recs) === 0) {
			unset($mvc->_total);
			return;
		}
		
		expect(is_object($masterRec));
	
		// Комбиниране на дефолт стойнсотите с тези подадени от потребителя
		$map = array_merge(self::$map, $map);
	
		// Дали трябва винаги да не се показва ддс-то към цената
		$hasVat = ($map['alwaysHideVat']) ? FALSE : (($masterRec->$map['chargeVat'] == 'yes') ? TRUE : FALSE);
		$amountJournal = $discount = $amount = $amountVat = $amountTotal = $amountRow = 0;
		$vats = array();
		
		// Обработваме всеки запис
		foreach($recs as &$rec){
			$vat = 0;
			if ($masterRec->$map['chargeVat'] == 'yes' || $masterRec->$map['chargeVat'] == 'separate') {
				$vat = cat_Products::getVat($rec->$map['productId'], $masterRec->$map['valior']);
			}
			$vats[$vat] = $vat;
			
			// Калкулира се цената с и без ддс и се показва една от тях взависимост трябвали да се показва ддс-то
			$price = self::calcPrice($rec->$map['priceFld'], $vat, $masterRec->$map['rateFld']);
			$rec->$map['priceFld'] = ($hasVat) ? $price->withVat : $price->noVat;
			
			$noVatAmount = round($price->noVat * $rec->$map['quantityFld'], 2);
        	
			if($rec->$map['discount']){
				$withoutVatAndDisc = round($noVatAmount * (1 - $rec->$map['discount']), 2);
			} else {
				$withoutVatAndDisc = $noVatAmount;
			}
			
			$vatRow = round($withoutVatAndDisc * $vat, 2);
			
        	$rec->$map['amountFld'] = $noVatAmount;
        	if($masterRec->$map['chargeVat'] == 'yes' && !$map['alwaysHideVat']){
        		$rec->$map['amountFld'] = round($rec->$map['amountFld'] + round($noVatAmount * $vat, 2), 2);
        	}

        	if($rec->$map['discount']){
        		$discount += $rec->$map['amountFld'] * $rec->$map['discount'];
        	}
        	
        	// Ако документа е кредитно/дебитно известие сабираме само редовете с промяна
        	if($masterRec->type === 'dc_note'){
        		if($rec->changedQuantity === TRUE || $rec->changedPrice === TRUE){
        			
        			$amountRow += $rec->$map['amountFld'];
        			$amount += $noVatAmount;
        			$amountVat += $vatRow;
        			 
        			$amountJournal += $withoutVatAndDisc;
        			if($masterRec->$map['chargeVat'] == 'yes') {
        				$amountJournal += $vatRow;
        			}
        		}
        	} else {
        		
        		// За всички останали събираме нормално
        		$amountRow += $rec->$map['amountFld'];
        		$amount += $noVatAmount;
        		$amountVat += $vatRow;
        		 
        		$amountJournal += $withoutVatAndDisc;
        		if($masterRec->$map['chargeVat'] == 'yes') {
        			$amountJournal += $vatRow;
        		}
        	}
		}
		
		$mvc->_total = new stdClass();
		$mvc->_total->amount = $amountRow;
		$mvc->_total->vat = $amountVat;
		$mvc->_total->vats = $vats;
		
		if(!$map['alwaysHideVat']){
			$mvc->_total->discount = round($amountRow, 2) - round($amountJournal, 2);
		} else {
			$mvc->_total->discount = $discount;
		}
	}
	
	
	/**
	 * Подготвя данните за съмаризиране ценовата информация на един документ
	 * @param array $values - масив с стойности на сумата на всеки ред, ддс-то и отстъпката 
	 * @param date $date - дата
	 * @param doublr $currencyRate - курс
	 * @param varchar(3) $currencyId - код на валута
	 * @param enum $chargeVat - ддс режима
	 * @param boolean $invoice - дали документа е фактура
	 * 
	 * @return stdClass $arr  - Масив с нужната информация за показване:
	 * 		->value           - Стойността
	 * 		->discountValue   - Отстъпката
	 * 		->neto 		      - Нето (Стойност - отстъпка) // Показва се ако има отстъпка
	 * 		->baseAmount      - Данъчната основа // само при фактура се показва
	 * 		->vat             - % ДДС // само при фактура или ако ддс-то се начислява отделно
	 * 		->vatAmount       - Стойност на ДДС-то // само при фактура или ако ддс-то се начислява отделно
	 * 		->total           - Крайната стойност
	 * 		->sayWords        - крайната сума изписана с думи
	 * 
	 */
	public static function prepareSummary($values, $date, $currencyRate, $currencyId, $chargeVat, $invoice = FALSE, $lang = 'bg')
	{
		// Стойностите на сумата на всеки ред, ддс-то и отстъпката са във валутата на документа
		$arr = array();
		
		$values = (array)$values;
		$arr['currencyId'] = $currencyId;                          // Валута на документа
		
		$baseCurrency = acc_Periods::getBaseCurrencyCode($date);   // Основната валута
		$arr['value'] = $values['amount']; 						   // Стойноста е сумираната от показваното на всеки ред
		
		if($values['discount']){ 								// ако има отстъпка
			$arr['discountValue'] = $values['discount'];
			$arr['discountCurrencyId'] = $currencyId; 			// Валутата на отстъпката е тази на документа
			$arr['neto'] = $arr['value'] - $arr['discountValue']; 	// Стойността - отстъпката
			$arr['netoCurrencyId'] = $currencyId; 				// Валутата на нетото е тази на документа
		}
		
		// Ако има нето, крайната сума е тази на нетото, ако няма е тази на стойността
		$arr['total'] = (isset($arr['neto'])) ? $arr['neto'] : $arr['value']; 
		
		$coreConf = core_Packs::getConfig('core');
		$pointSign = $coreConf->EF_NUMBER_DEC_POINT;
		
		if($invoice){ // ако е фактура
			$arr['vatAmount'] = $values['vat'] * $currencyRate; // С-та на ддс-то в основна валута
			$arr['vatCurrencyId'] = $baseCurrency; 				// Валутата на ддс-то е основната за периода
			$arr['baseAmount'] = $arr['total'] * $currencyRate; // Данъчната основа
			$arr['baseAmount'] = ($arr['baseAmount']) ? $arr['baseAmount'] : "<span class='quiet'>0" . $pointSign . "00</span>";;
			$arr['baseCurrencyId'] = $baseCurrency; 			// Валутата на данъчната основа е тази на периода
		} else { // ако не е фактура
			$arr['vatAmount'] = $values['vat']; 		// ДДС-то
			$arr['vatCurrencyId'] = $currencyId; 		// Валутата на ддс-то е тази на документа
		}
		
		if(!$invoice && $chargeVat != 'separate'){ 				 // ако документа не е фактура и не е с отделно ддс
			unset($arr['vatAmount'], $arr['vatCurrencyId']); // не се показват данни за ддс-то
		} else { // ако е фактура или е сотделно ддс
			if($arr['total']){
				$arr['vat'] = round(($values['vat'] / $arr['total']) * 100); // % ддс
				$arr['total'] = $arr['total'] + $values['vat']; 	  // Крайното е стойноста + ддс-то
			}
		}
		
		$SpellNumber = cls::get('core_SpellNumber');
		if($arr['total'] != 0){
			$arr['sayWords'] = $SpellNumber->asCurrency($arr['total'], $lang, FALSE, $currencyId);
			$arr['sayWords'] = str::mbUcfirst($arr['sayWords']);
		}
		
		$arr['value'] = ($arr['value']) ? $arr['value'] : "<span class='quiet'>0" . $pointSign . "00</span>";
		$arr['total'] = ($arr['total']) ? $arr['total'] : "<span class='quiet'>0" . $pointSign . "00</span>";

		if(!$arr['vatAmount'] && ($invoice || $chargeVat == 'separate')){
			$arr['vatAmount'] = "<span class='quiet'>0" . $pointSign . "00</span>";
		}
		
		$Double = cls::get('type_Double');
		$Double->params['decimals'] = 2;
		
		foreach ($arr as $index => $el){
			if(is_numeric($el)){
				$arr[$index] = $Double->toVerbal($el);
			}
		}
		
		if($arr['vat']){
			$arr['vat'] .= ' %';
		}
		
		return (object)$arr;
	}
	
	
	/**
	 * Помощна ф-я обръщаща цена от от основна валута без ддс до валута
	 * 
	 * @param double $price - цена във валута
	 * @param double $vat - ддс 
	 * @param double $rate - валутен курс
	 * @param enum(yes,no,separate,exempt) $chargeVat - как се начислява ДДС-то
	 * @param int $round - до колко знака да се закръгли
	 * 
	 * @return double $price - цената във валутата
	 */
	public static function getDisplayPrice($price, $vat, $rate, $chargeVat, $round = NULL)
	{	
		// Ако няма цена, но има такъв запис се взима цената от него
	    if ($chargeVat == 'yes') {
	    	
	          // Начисляване на ДДС в/у цената
	         $price *= 1 + $vat;
	    }
	    
	    expect($rate, 'Не е подаден валутен курс');
	    
	    // Обреъщаме в валутата чийто курс е подаден
	    if($rate != 1){
	    	$price /= $rate;
	    }
	   
	    // Закръгляме при нужда
	    if($round){
	    	$price = round($price, $round);
	    } else {
	    	
	    	// Ако не е посочено закръгляне, правим машинно закръгляне
	    	$price = deals_Helper::roundPrice($price);
	    }
	    
	    // Връщаме обработената цена
	    return $price;
	}
	
	
	/**
	 * Помощна ф-я обръщаща цена от от сума във валута в основната валута
	 * това е обратната ф-я на `deals_Helper::getDisplayPrice`
	 * 
	 * @param double $price - цена във валута
	 * @param double $vat - ддс 
	 * @param double $rate - валутен курс
	 * @param enum(yes,no,separate,exempt) $chargeVat - как се начислява ддс-то
	 * 
	 * @return double $price - цената в основна валута без ддс
	 */
	public static function getPurePrice($price, $vat, $rate, $chargeVat)
	{
		// Ако няма цена, но има такъв запис се взима цената от него
	    if ($chargeVat == 'yes') {
	         
	    	 // Премахваме ДДС-то при нужда
	         $price /= 1 + $vat;
	    }
	  
	    // Обръщаме в основната валута
	    $price *= $rate;
	    
	    // Връщаме обработената цена
	    return $price;
	}
	
	
	/**
	 * Връща обект с информацията за наличното в склада к-во
	 * 
	 * @return stdClass $obj 
	 * 				->formInfo - информация за формата
	 * 				->warning - предупреждението
	 */
	public static function checkProductQuantityInStore($productId, $packagingId, $packQuantity, $storeId)
	{
		$quantity = store_Products::fetchField("#productId = {$productId} AND #storeId = {$storeId}", 'quantity');
		$quantity = ($quantity) ? $quantity : 0;
			
		$Double = cls::get('type_Double');
		$Double->params['smartRound'] = 'smartRound';
			
		$pInfo = cat_Products::getProductInfo($productId);
		$shortUom = cat_UoM::getShortName($pInfo->productRec->measureId);
		$storeName = store_Stores::getTitleById($storeId);
		$verbalQuantity = $Double->toVerbal($quantity);
		
		$info = tr("|Количество в|* <b>{$storeName}</b> : {$verbalQuantity} {$shortUom}");
		$obj = (object)array('formInfo' => $info);
		
		$quantityInPack = ($pInfo->packagings[$packagingId]) ? $pInfo->packagings[$packagingId]->quantity : 1;
		 
		// Показваме предупреждение ако наличното в склада е по-голямо от експедираното
		if($packQuantity > ($quantity / $quantityInPack)){
			$obj->warning = "Въведеното количество е по-голямо от наличното|* <b>{$verbalQuantity}</b> |в склада|*";
		}
		
		return $obj;
	}
	
	
	/**
	 * Добавя забележки към описанието на артикул
	 */
	public static function addNotesToProductRow(&$productRow, $notes)
	{
		$RichText = cls::get('type_RichText');
		if(is_string($productRow)){
			$productRow .= "<div class='small'>{$RichText->toVerbal($notes)}</div>";
		} else {
			$productRow->append("<div class='small'>{$RichText->toVerbal($notes)}</div>");
		}
	}
	
	
	/**
	 * Помощна функция за показване на пдоробната информация за опаковката при нужда
	 * 
	 * @param string $packagingRow
	 * @param int $productId
	 * @param int $packagingId
	 * @param double $quantityInPack
	 * @return void
	 */
	public static function getPackInfo(&$packagingRow, $productId, $packagingId, $quantityInPack)
	{
		if(cat_products_Packagings::getPack($productId, $packagingId)){
			if(cat_UoM::fetchField($packagingId, 'showContents') === 'yes'){
				 
				$quantityInPack = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')))->toVerbal($quantityInPack);
				
				$shortUomName = cat_UoM::getShortName(cat_Products::getProductInfo($productId)->productRec->measureId);
				$packagingRow .= ' <small class="quiet">' . $quantityInPack . ' ' . $shortUomName . '</small>';
				$packagingRow = "<span class='nowrap'>{$packagingRow}</span>";
			}
		}
	}
	
	
	/**
	 * Извлича масив с използваните артикули-документи в бизнес документа
	 *
	 * @param core_Mvc $mvc - клас на документа
	 * @param int $id - ид на документа
	 * @param string $productFld - името на полето в което е ид-то на артикула
	 * 
	 * @return array
	 */
	public static function getUsedDocs(core_Mvc $mvc, $id, $productFld = 'productId')
	{
		$res = array();
		 
		$Detail = cls::get($mvc->mainDetail);
		$dQuery = $Detail->getQuery();
		$dQuery->EXT('state', $mvc->className, "externalKey={$Detail->masterKey}");
		$dQuery->where("#{$Detail->masterKey} = '{$id}'");
		$dQuery->groupBy($productFld);
		while($dRec = $dQuery->fetch()){
		    $cid = cat_Products::fetchField($dRec->{$productFld}, 'containerId');
			$res[$cid] = $cid;
		}
		
		return $res;
	}
}