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
	    $p = 0;
	    if ($price) {
	        $p = round(log10($price));
	    }
	    
	    // Плаваща прецизност
	    $precision =  max(2, $minDigits - $p);
		
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
        if(!empty($rate)){
        	$arr['noVat'] = $price / $rate;
        	$arr['withVat'] = ($price * (1 + $vat)) / $rate;
        } else {
        	$arr['noVat'] = $price;
        	$arr['withVat'] = ($price * (1 + $vat));
        }
		
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
		$hasVat = ($map['alwaysHideVat']) ? FALSE : (($masterRec->{$map['chargeVat']} == 'yes') ? TRUE : FALSE);
		$amountJournal = $discount = $amount = $amountVat = $amountTotal = $amountRow = 0;
		$vats = array();
		
		// Обработваме всеки запис
		foreach($recs as &$rec){
			$vat = 0;
			if ($masterRec->{$map['chargeVat']} == 'yes' || $masterRec->{$map['chargeVat']} == 'separate') {
				$vat = cat_Products::getVat($rec->{$map['productId']}, $masterRec->{$map['valior']});
			}
			
			// Калкулира се цената с и без ддс и се показва една от тях взависимост трябвали да се показва ддс-то
			$price = self::calcPrice($rec->{$map['priceFld']}, $vat, $masterRec->{$map['rateFld']});
			$rec->{$map['priceFld']} = ($hasVat) ? $price->withVat : $price->noVat;
			
			$noVatAmount = round($price->noVat * $rec->{$map['quantityFld']}, 2);
        	
			if($rec->{$map['discount']}){
				$withoutVatAndDisc = round($noVatAmount * (1 - $rec->{$map['discount']}), 2);
			} else {
				$withoutVatAndDisc = $noVatAmount;
			}
			
			$vatRow = round($withoutVatAndDisc * $vat, 2);
			
        	$rec->{$map['amountFld']} = $noVatAmount;
        	if($masterRec->{$map['chargeVat']} == 'yes' && !$map['alwaysHideVat']){
        		$rec->{$map['amountFld']} = round($rec->{$map['amountFld']} + round($noVatAmount * $vat, 2), 2);
        	}

        	if($rec->{$map['discount']}){
        		if(!($masterRec->type === 'dc_note' && $rec->changedQuantity !== TRUE && $rec->changedPrice !== TRUE)){
        			$discount += $rec->{$map['amountFld']} * $rec->{$map['discount']};
        		}
        	}
        	
        	// Ако документа е кредитно/дебитно известие сабираме само редовете с промяна
        	if($masterRec->type === 'dc_note'){
        		if($rec->changedQuantity === TRUE || $rec->changedPrice === TRUE){
        			
        			$amountRow += $rec->{$map['amountFld']};
        			$amount += $noVatAmount;
        			$amountVat += $vatRow;
        			 
        			$amountJournal += $withoutVatAndDisc;
        			if($masterRec->{$map['chargeVat']} == 'yes') {
        				$amountJournal += $vatRow;
        			}
        		}
        	} else {
        		
        		// За всички останали събираме нормално
        		$amountRow += $rec->{$map['amountFld']};
        		$amount += $noVatAmount;
        		$amountVat += $vatRow;
        		 
        		$amountJournal += $withoutVatAndDisc;
        		if($masterRec->{$map['chargeVat']} == 'yes') {
        			$amountJournal += $vatRow;
        		}
        	}
        	
        	if(!($masterRec->type === 'dc_note' && ($rec->changedQuantity !== TRUE && $rec->changedPrice !== TRUE))){
        		if(!array_key_exists($vat, $vats)){
        			$vats[$vat] = (object)array('amount' => 0, 'sum' => 0);
        		}
        		 
        		$vats[$vat]->amount += $vatRow;
        		$vats[$vat]->sum += $withoutVatAndDisc;
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
			
			$arr['neto'] = $arr['value'] - round($arr['discountValue'], 2); 	// Стойността - отстъпката
			$arr['netoCurrencyId'] = $currencyId; 				// Валутата на нетото е тази на документа
		}
		
		
		
		// Ако има нето, крайната сума е тази на нетото, ако няма е тази на стойността
		$arr['total'] = (isset($arr['neto'])) ? $arr['neto'] : $arr['value']; 
		
		$coreConf = core_Packs::getConfig('core');
		$pointSign = $coreConf->EF_NUMBER_DEC_POINT;
		
		if($invoice || $chargeVat == 'separate'){
			if(is_array($values['vats'])){
				foreach ($values['vats'] as $percent => $vi){
					if(is_object($vi)){
						$index = str_replace('.', '', $percent);
						$arr["vat{$index}"] = $percent * 100 . "%";
						$arr["vat{$index}Amount"] = $vi->amount * (($invoice) ? $currencyRate : 1);
						$arr["vat{$index}AmountCurrencyId"] = ($invoice) ? $baseCurrency : $currencyId;
							
						if($invoice){
							$arr["vat{$index}Base"] = $arr["vat{$index}"];
							$arr["vat{$index}BaseAmount"] = $vi->sum * (($invoice) ? $currencyRate : 1);
							$arr["vat{$index}BaseCurrencyId"] = ($invoice) ? $baseCurrency : $currencyId;
						}
					}
				}
			} else {
				$arr['vat02Amount'] = 0;
				$arr['vat02AmountCurrencyId'] = ($invoice) ? $baseCurrency : $currencyId;
			}
		}
		
		if($invoice){ // ако е фактура
			//$arr['vatAmount'] = $values['vat'] * $currencyRate; // С-та на ддс-то в основна валута
			//$arr['vatCurrencyId'] = $baseCurrency; 				// Валутата на ддс-то е основната за периода
			$arr['baseAmount'] = $arr['total'] * $currencyRate; // Данъчната основа
			$arr['baseAmount'] = ($arr['baseAmount']) ? $arr['baseAmount'] : "<span class='quiet'>0" . $pointSign . "00</span>";;
			$arr['baseCurrencyId'] = $baseCurrency; 			// Валутата на данъчната основа е тази на периода
		} else { // ако не е фактура
			//$arr['vatAmount'] = $values['vat']; 		// ДДС-то
			//$arr['vatCurrencyId'] = $currencyId; 		// Валутата на ддс-то е тази на документа
		}
		
		if(!$invoice && $chargeVat != 'separate'){ 				 // ако документа не е фактура и не е с отделно ддс
			//unset($arr['vatAmount'], $arr['vatCurrencyId']); // не се показват данни за ддс-то
		} else { // ако е фактура или е сотделно ддс
			if($arr['total']){
				//$arr['vat'] = round(($values['vat'] / $arr['total']) * 100); // % ддс
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
			//$arr['vatAmount'] = "<span class='quiet'>0" . $pointSign . "00</span>";
		}
		
		$Double = cls::get('type_Double');
		$Double->params['decimals'] = 2;
		
		foreach ($arr as $index => $el){
			if(is_numeric($el)){
				$arr[$index] = $Double->toVerbal($el);
			}
		}
		
		// Дефолтни стойности ако няма записи
		if($invoice && empty($values)){
			$arr['vat02BaseAmount'] = "0.00";
			$arr['vat02BaseCurrencyId'] = $baseCurrency;
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
	    
	    // Обръщаме във валутата, чийто курс е подаден
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
		if(empty($packQuantity)){
			$packQuantity = 1;
		}
		
		$stRec = store_Products::fetch("#productId = {$productId} AND #storeId = {$storeId}", 'quantity,reservedQuantity');
		
		$quantity = $stRec->quantity - $stRec->reservedQuantity;
		
		$Double = cls::get('type_Double');
		$Double->params['smartRound'] = 'smartRound';
			
		$pInfo = cat_Products::getProductInfo($productId);
		$shortUom = cat_UoM::getShortName($pInfo->productRec->measureId);
		$storeName = store_Stores::getTitleById($storeId);
		$verbalQuantity = $Double->toVerbal($quantity);
		$verbalQuantity = ht::styleIfNegative($verbalQuantity, $quantity);
		
		$text = "|Разполагаемо в|* <b>{$storeName}</b> : {$verbalQuantity} {$shortUom}";
		if(!empty($stRec->reservedQuantity)){
			$verbalReserved = $Double->toVerbal($stRec->reservedQuantity);
			$text .= " " . "|*( |Запазено|* {$verbalReserved} {$shortUom} )";
		}
		
		$info = tr($text);
		$obj = (object)array('formInfo' => $info);
		
		$quantityInPack = ($pInfo->packagings[$packagingId]) ? $pInfo->packagings[$packagingId]->quantity : 1;
		
		// Показваме предупреждение ако наличното в склада е по-голямо от експедираното
		if($packQuantity > ($quantity / $quantityInPack)){
			$obj->warning = "Въведеното количество е по-голямо от разполагаемо|* <b>{$verbalQuantity}</b> |в склада|*";
		}
		
		return $obj;
	}
	
	
	/**
	 * Добавя забележки към описанието на артикул
	 */
	public static function addNotesToProductRow(&$productRow, $notes)
	{
		if(!$notes) return;
		
		$RichText = cls::get('type_Richtext');
		$notes = $RichText->toVerbal($notes);
		if(is_string($productRow)){
			$productRow .= "<div class='small'>{$notes}</div>";
		} else {
			$productRow->append(new core_ET("<div class='small'>[#NOTES#]</div>"));
			$productRow->replace($notes, 'NOTES');
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
			if(cat_UoM::fetchField($packagingId, 'showContents') !== 'no'){
				$measureId = cat_Products::fetchField($productId, 'measureId');
                $packagingRow .= ' ' . self::getPackMeasure($measureId, $quantityInPack);
			}
		}
	}


    /**
     * Връща описание на опаковка, заедно с количеството в нея
     */
    public static function getPackMeasure($measureId, $quantityInPack)
    {
        if($quantityInPack < 1 && ($downMeasureId = cat_UoM::getMeasureByRatio($measureId, 0.001))){
			$quantityInPack *= 1000;
			$measureId = $downMeasureId;
		} elseif($quantityInPack > 1000 && ($downMeasureId = cat_UoM::getMeasureByRatio($measureId, 1000))){
			$quantityInPack /= 1000;
			$measureId = $downMeasureId;
		}
		
        if($quantityInPack == 1) {
		    $quantityInPack = '';
        } else {
		    $quantityInPack = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')))->toVerbal($quantityInPack) . ' ';
        }
		
		$shortUomName = cat_UoM::getShortName($measureId);
		$res = ' <small class="quiet">' . $quantityInPack . tr($shortUomName) . '</small>';
		$res = "<span class='nowrap'>{$res}</span>";

        return $res;
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
	
	
	/**
	 * Проверява имали такъв запис 
	 * 
	 * @param core_Detail $mvc
	 * @param int $masterId
	 * @param int $id
	 * @param int $productId
	 * @param int $packagingId
	 * @param double $price
	 * @param NULL|double $discount
	 * @param NULL|double $tolerance
	 * @param NULL|int $term
	 * @param NULL|varchar $batch
	 * @return FALSE|stdClass
	 */
	public static function fetchExistingDetail(core_Detail $mvc, $masterId, $id, $productId, $packagingId, $price, $discount, $tolerance = NULL, $term = NULL, $batch = NULL, $expenseItemId = NULL, $notes = NULL)
	{
		$cond = "#{$mvc->masterKey} = $masterId";
		$vars = array('productId' => $productId, 'packagingId' => $packagingId, 'price' => $price, 'discount' => $discount);
		
		if($mvc->getField('tolerance', FALSE)){
			$vars['tolerance'] = $tolerance;
		}
		if($mvc->getField('term', FALSE)){
			$vars['term'] = $term;
		}
		
		if($mvc->getField('batch', FALSE)){
			$vars['batch'] = $batch;
		}
		
		foreach ($vars as $key => $var){
			if(isset($var)){
				$cond .= " AND #{$key} = '{$var}'";
			} else {
				$cond .= " AND #{$key} IS NULL";
			}
		}
		
		if($id){
			$cond .= " AND #id != {$id}";
		}
		
		if($mvc->getField('expenseItemId', FALSE)){
			if(isset($expenseItemId)){
				$cond .= " AND #expenseItemId = {$expenseItemId}";
			} else {
				$cond .= " AND #expenseItemId IS NULL";
			}
		}
		
		// Ако има забележки
		if(!empty($notes)){
			
			// Сравняване на хеша на забележките с този на новата забележка
			$query = $mvc->getQuery();
			$query->XPR('hashNotes', 'double', 'MD5(#notes)');
			$notes = md5(gzcompress($notes));
			$cond .= " AND #hashNotes = '{$notes}'";
			$query->where($cond);
			
			return $query->fetch();
		} else {
			$cond .= " AND (#notes = '' OR #notes IS NULL)";
		}
		
		return $mvc->fetch($cond);
	}
	
	
	/**
	 * Сумиране на записи от бизнес документи по артикули
	 * 
	 * @param $arrays - масив от масиви със детайли на бизнес документи
	 * @return array
	 */
	public static function normalizeProducts($arrays, $subtractArrs = array())
	{
		$combined = array();
		$indexArr = arr::make($indexArr);
		
		foreach (array('arrays', 'subtractArrs') as $parameter){
			$var = ${$parameter};
			
			if(is_array($var)){
				foreach ($var as $arr){
					if(is_array($arr)){
						foreach ($arr as $p){
							$index = $p->productId;
							
							if(!empty($p->notes)){
								$index .= "|" . serialize($p->notes) . "|";
							}
							
							if(!isset($combined[$index])){
								$combined[$index] = new stdClass();
								$combined[$index]->productId = $p->productId;
								
								if(!empty($p->notes)){
									$combined[$index]->notes = $p->notes;
								}
							}
								
							$d = &$combined[$index];
							if($p->discount != 1){
								$d->discount = max($d->discount, $p->discount);
							}
			
							$sign = ($parameter == 'arrays') ? 1 : -1;
							
							//@TODO да може да е -
							$d->quantity += $sign * $p->quantity;
							$d->sumAmounts += $sign * ($p->quantity * $p->price * (1 - $p->discount));
			
							if(empty($d->packagingId)){
								$d->packagingId = $p->packagingId;
								$d->quantityInPack = $p->quantityInPack;
							} else {
								if($p->quantityInPack < $d->quantityInPack){
									$d->packagingId = $p->packagingId;
									$d->quantityInPack = $p->quantityInPack;
								}
							}
						}
					}
				}
			}
		}
		
		if(count($combined)){
			foreach ($combined as &$det){
				$delimiter = ($det->quantity * (1 - $det->discount));
				if(!empty($delimiter)){
					$det->price = $det->sumAmounts / $delimiter;
					
					if($det->price < 0){
						$det->price = 0;
					}
				} else {
					$det->price = 0;
				}
			}
		}
		
		return $combined;
	}
	
	
	/**
	 * Връща хинт с количеството в склада
	 * 
	 * @param int $productId
	 * @param int $storeId
	 * @param double $quantity
	 * @return string $hint
	 */
	public static function getQuantityHint($productId, $storeId, $quantity)
	{
		$hint = '';
		$stRec = store_Products::fetch("#productId = {$productId} AND #storeId = {$storeId}", 'quantity,reservedQuantity');
		$quantityInStore = $stRec->quantity - $stRec->reservedQuantity;
		
		if(is_null($quantityInStore)){
			$hint = 'Разполагаемо количество в склада: н.д.';
		} elseif($quantityInStore < 0 || ($quantityInStore - $quantity) < 0) {
			$quantityInStore = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')))->toVerbal($quantityInStore);
			$measureName = cat_UoM::getShortName(cat_Products::fetchField($productId, 'measureId'));
			$hint = "Разполагаемо количество в склада|*: {$quantityInStore} {$measureName}";
		}
		
		return $hint;
	}
	
	
	/**
	 * Помощна ф-я обръщащи намерените к-ва и суми върнати от acc_Balances::getBlQuantities
	 *  от една валута в друга подадена
	 * 
	 * @see acc_Balances::getBlQuantities
	 * @param array $array - масив от обекти с ключ ид на перо на валута и полета amount и quantity
	 * @param varchar $currencyCode - към коя валута да се конвертират
	 * @param date $date - дата
	 * @return array $res
	 * 					->quantity - Количество във подадената валута
	 * 					->amount   - Сума в основната валута
	 */
	public static function convertJournalCurrencies($array, $currencyCode, $date)
	{
		$res = (object)array('quantity' => 0, 'amount' => 0);
		
		// Ако е масив
		if (is_array($array) && !empty($array)){
			$currencyItemId = $currencyItemId = acc_Items::fetchItem('currency_Currencies', currency_Currencies::getIdByCode($currencyCode))->id;
			$currencyListId = acc_Lists::fetchBySystemId('currencies')->id;
			
			// За всеки обект от него
			foreach ($array as $itemId => $obj){
				
				// Подсигуряваме се че ключа е перо от номенклатура валута
				$itemRec = acc_Items::fetch($itemId);
				$cCode = currency_Currencies::getCodeById($itemRec->objectId);
				expect(keylist::isIn($currencyListId, $itemRec->lists));
				
				// Ако ключа е търсената валута просто събираме
				if($currencyItemId == $itemId){
					$quantity = $obj->quantity;
				} else {
					if($obj->amount){
						
						// Ако има сума обръщаме сумата в количеството на основната валута чрез основния курс
						$rate = currency_CurrencyRates::getRate($date, $currencyCode, NULL);
						$quantity = $obj->amount / $rate;
					} else {
						// Ако не е конвертираме количеството във търсената валута
						$quantity = currency_CurrencyRates::convertAmount($obj->quantity, $date, $cCode, $currencyCode);
					}
				}
				
				// Ако няма сума я изчисляваме възоснова на основния курс
				if($obj->amount){
					$amount = $obj->amount;
				} else {
					$rate = currency_CurrencyRates::getRate($date, $cCode, NULL);
					$amount = $rate * $quantity;
				}
				
				// Сумираме к-та и сумите към търсената валута
				$res->quantity += $quantity;
				$res->amount += $amount;
			}
		}
		
		return $res;
	}
	
	
	/**
	 * Помощен метод връщащ дали не може да бъде избран документ от посочения вид
	 * използва се за проверка дали при контиране/възстановяване/оттегляне дали потребителя
	 * може да избере посочения обект: каса/б. сметка/склад
	 * 
	 * @param string $action        - действие с документа
	 * @param stdClass $rec         - запис на документа
	 * @param string $ObjectManager - мениджър на обекта, който ще проверяваме можели да се избере
	 * @param string $objectIdField - поле на ид-то на обекта, който ще проверяваме можели да се избере
	 * @return boolean              - можели да се избере обекта или не
	 */
	public static function canSelectObjectInDocument($action, $rec, $ObjectManager, $objectIdField)
	{
		// Ако действието е контиране/възстановяване/оттегляне
		if(($action == 'conto' || $action == 'restore' || $action == 'reject') && isset($rec)){
			
			// Ако документа е чернова не проверяваме дали потребителя може да избере обекта
			if($action == 'reject' && $rec->state == 'draft') return TRUE;
			
			// Ако документа е бил чернова не проверяваме дали потребителя може да избере обекта
			if($action == 'restore' && $rec->brState == 'draft') return TRUE;
			
			// Ако има избран обект и потребителя не може да го избере връщаме FALSE
			if(isset($rec->{$objectIdField}) && !$ObjectManager::haveRightFor('select', $rec->{$objectIdField})){
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	
	/**
	 * Помощна ф-я връщаща подходящо представяне на клиентсктие данни и тези на моята фирма
	 * в бизнес документите
	 * 
	 * @param mixed $contragentClass - клас на контрагента
	 * @param int $contragentId      - ид на контрагента
	 * @param int $contragentName    - името на контрагента, ако е предварително известно
	 * @return array $res
	 * 				['MyCompany']         - Името на моята фирма
	 * 				['MyAddress']         - Адреса на моята фирма
	 * 				['MyCompanyVatNo']    - ДДС номера на моята фирма
	 * 				['uicId']             - Националния номер на моята фирма
	 *  			['contragentName']    - Името на контрагента
	 *   			['contragentAddress'] - Адреса на контрагента
	 *              ['vatNo']             - ДДС номера на контрагента
	 */
	public static function getDocumentHeaderInfo($contragentClass, $contragentId, $contragentName = NULL)
	{
		$res = array();
		
		// Данните на 'Моята фирма'
		$ownCompanyData = crm_Companies::fetchOwnCompany();
		
		// Името и адреса на 'Моята фирма'
		$Companies = cls::get('crm_Companies');
		$res['MyCompany'] = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
		$res['MyCompany'] = transliterate(tr($res['MyCompany']));
		
		// ДДС и националния номер на 'Моята фирма'
		$uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
		if($uic != $ownCompanyData->vatNo){
			$res['MyCompanyVatNo'] = $ownCompanyData->vatNo;
		}
		$res['uicId'] = $uic;
			
		// името, адреса и ДДС номера на контрагента
		if(isset($contragentClass) && isset($contragentId)){
			$ContragentClass = cls::get($contragentClass);
			$cData = $ContragentClass->getContragentData($contragentId);
			$res['contragentName'] = isset($contragentName) ? $contragentName : cls::get('type_Varchar')->toVerbal(($cData->person) ? $cData->person : $cData->company);
			$res['inlineContragentName'] = $res['contragentName'];
			
			$res['vatNo'] = $cData->vatNo;
		} elseif(isset($contragentName)){
			$res['contragentName'] = $contragentName;
		}
		
		$makeLink = (!Mode::is('pdf') && !Mode::is('text', 'xhtml') && !Mode::is('text', 'plain'));
		
		// Имената на 'Моята фирма' и контрагента са линкове към тях, ако потребителя има права
		if($makeLink === TRUE){
			$res['MyCompany'] = ht::createLink($res['MyCompany'], crm_Companies::getSingleUrlArray($ownCompanyData->companyId));
			$res['MyCompany'] = $res['MyCompany']->getContent();
			
			if(isset($contragentClass) && isset($contragentId)){
				$res['contragentName'] = ht::createLink($res['contragentName'], $ContragentClass::getSingleUrlArray($contragentId));
				$res['contragentName'] = $res['contragentName']->getContent();
			}
		}
		
		$showCountries = ($ownCompanyData->countryId == $cData->countryId) ? FALSE : TRUE;
		
		if(isset($contragentClass) && isset($contragentId)){
			$res['contragentAddress'] = $ContragentClass->getFullAdress($contragentId, FALSE, $showCountries)->getContent();
			$res['inlineContragentAddress'] = $ContragentClass->getFullAdress($contragentId, FALSE, $showCountries)->getContent();
			$res['inlineContragentAddress'] = str_replace('<br>', ',', $res['inlineContragentAddress']);
		}
		
		$res['MyAddress'] = $Companies->getFullAdress($ownCompanyData->companyId, TRUE, $showCountries)->getContent();
		
		return $res;
	}
	
	
	/**
	 * Помощна ф-я проверяваща дали подаденото к-во може да се зададе за опаковката
	 * 
	 * @param int $packagingId - ид на мярка/опаковка
	 * @param double $packQuantity - к-во опаковка
	 * @param string $warning - предупреждение, ако има
	 * @return boolean - дали к-то е допустимо или не
	 */
	public static function checkQuantity($packagingId, $packQuantity, &$warning = NULL)
	{
		$decLenght = strlen(substr(strrchr($packQuantity, "."), 1));
		$decimals = cat_UoM::fetchField($packagingId, 'round');
		 
		if(isset($decimals) && $decLenght > $decimals){
			if($decimals == 0){
				$warning = "Количеството трябва да е цяло число";
			} else {
				$decimals = cls::get('type_Int')->toVerbal($decimals);
				$warning = "Количеството трябва да е с точност до|* <b>{$decimals}</b> |цифри след десетичния знак|*";
			}
	
			return FALSE;
		}
		 
		return TRUE;
	}
	
	
	/**
	 * Помощна ф-я проверяваща дали цената не е много малка
	 * 
	 * @param double|NULL $price - цена
	 * @param double $quantity   - количество
	 * @param boolean $autoPrice - дали е автоматично изчислена
	 * @param string|NULL $msg   - съобщение за грешка ако има
	 * @return boolean           - дали цената е под допустимото
	 */
	public static function isPriceAllowed($price, $quantity, $autoPrice = FALSE, &$msg = NULL)
	{
		if(!$price) return TRUE;
		if($quantity == 0) return TRUE;
		
		$amount = $price * $quantity;
		
		$round = round($amount, 2);
		$res =((double)$round >= 0.01);
		
		if($res === FALSE){
			if($autoPrice === TRUE){
			$msg = "Сумата на реда не може да бъде под|* <b>0.01</b>! |Моля увеличете количеството, защото цената по политика е много ниска|*";
			} else {
				$msg = "Сумата на реда не може да бъде под|* <b>0.01</b>! |Моля променете количеството и/или цената|*";
			}
		}
		
		return $res;
	}
	
	
	/**
	 * Връща динамично изчисления толеранс
	 * 
	 * @param int $tolerance
	 * @param int $productId
	 * @param double $quantity
	 * @return mixed
	 */
	public static function getToleranceRow($tolerance, $productId, $quantity)
	{
		$hint = FALSE;
		
		if(!isset($tolerance)){
			$tolerance = cat_Products::getTolerance($productId, $quantity);
			if($tolerance){
				$hint = TRUE;
			}
		}
		
		if(isset($tolerance)) {
			$toleranceRow = core_Type::getByName('percent(smartRound)')->toVerbal($tolerance);
			if($hint === TRUE){
				$toleranceRow = ht::createHint($toleranceRow, 'Толерансът е изчислен автоматично на база количеството и параметрите на артикула');
			}
			
			return $toleranceRow;
		}
		
		return NULL;
	}
	
	
	/**
	 * Проверка дали к-то е под МКП-то на артикула
	 * 
	 * @param core_Form $form
	 * @param int $productId
	 * @param double $quantity
	 * @param double $quantityInPack
	 * @param string $quantityField
	 * @return void
	 */
	public static function isQuantityBellowMoq(&$form, $productId, $quantity, $quantityInPack, $quantityField = 'packQuantity')
	{
		$moq = cat_Products::getMoq($productId);
		
		if(isset($moq) && $quantity < $moq){
			$moq /= $quantityInPack;
			$verbal = core_Type::getByName('double(smartRound)')->toVerbal($moq);
			if(haveRole('powerUser')){
				$form->setWarning($quantityField, "Минималното количество за поръчка в избраната мярка/опаковка e|*: <b>{$verbal}</b>");
			} else {
				$form->setError($quantityField, "Минималното количество за поръчка в избраната мярка/опаковка e|*: <b>{$verbal}</b>");
			}
		}
	}
	
	
	/**
	 * Помощна ф-я за показване на всички условия идващи от артикулите на един детайл
	 * 
	 * @param core_Detail $Detail
	 * @param int $masterId
	 * @param core_Master $Master
	 * @param string|NULL $lg
	 * @return array $res
	 */
	public static function getConditionsFromProducts($Detail, $Master, $masterId, $lg)
	{
		$res = array();
		
		// Намиране на детайлите
		$Detail = cls::get($Detail);
		$dQuery = $Detail->getQuery();
		$dQuery->where("#{$Detail->masterKey} = {$masterId}");
		$dQuery->show("productId,quantity");
		$type = ($Master instanceof purchase_Purchases) ? 'purchase' : (($Master instanceof sales_Quotations) ? 'quotation' : 'sale');
		$allProducts = $productConditions = array();
		
		while($dRec = $dQuery->fetch()){
			
			// Опит за намиране на условията
			$conditions = cat_Products::getConditions($dRec->productId, $type, $lg);
			$allProducts[$dRec->productId] = $dRec->productId;
			
            if(is_array($conditions)) {
                foreach ($conditions as $t){
                    
                    // Нормализиране на условието
                    $key = md5(strtolower(str::utf2ascii(trim($t))));
                    $value = preg_replace('!\s+!', ' ', str::mbUcfirst($t));
                    $res[$key] = $value;
                    
                    $productConditions[$key] = is_array($productConditions[$key]) ? $productConditions[$key] : array();
                    
                    // Запомня се кои артикули подават същото условие
                    if(!array_key_exists($dRec->productId, $productConditions[$key])){
                          $code = cat_Products::fetchField($dRec->productId, 'code');
                          $code = (!empty($code)) ? $code : "Art{$dRec->productId}";
                          $productConditions[$key][$dRec->productId] = $code;
                    }
                }
            }
		}
		
		foreach ($res as $key => &$val){
			if(is_array($productConditions[$key]) && count($productConditions[$key]) != count($allProducts)){
				$valSuffix = new core_ET(tr("За|* [#Articles#]"));
				$valSuffix->replace(implode(',', $productConditions[$key]), 'Articles');
				$valSuffix = " <i>(" . $valSuffix->getContent() . ")</i>";
				
				$bold = FALSE;
				foreach (array('strong', 'b') as $tag){
					if(preg_match("/<{$tag}>(.*)<\/{$tag}>/", $val, $m)){
						$bold = $tag;
						break;
					}
				}
				
				if($bold !== FALSE){
					$valSuffix = "<{$bold}>{$valSuffix}</{$bold}>";
				}
				$val .= $valSuffix; 
			}
		}
		
		return $res;
	}
	
	
	/**
	 * Помощна ф-я връщаща дефолтното количество за артикула в бизнес документ
	 * 
	 * @param int $productId
	 * @param int $packagingId
	 * @return double|NULL $defQuantity
	 */
	public static function getDefaultPackQuantity($productId, $packagingId)
	{
		$defQuantity = cat_Products::getMoq($productId);
		$defQuantity = !empty($defQuantity) ? $defQuantity : cat_UoM::fetchField($packagingId, 'defQuantity');
	
		return ($defQuantity) ? $defQuantity : NULL;
	}
	
	
	/**
	 * Помощна ф-я за рекалкулиране на курса на бизнес документ
	 * 
	 * @param mixed $masterMvc
	 * @param int $masterId
	 * @param double $newRate
	 * @param string $priceFld
	 * @param string $rateFld
	 */
	public static function recalcRate($masterMvc, $masterId, $newRate, $priceFld = 'price', $rateFld = 'currencyRate')
	{
		$rec = $masterMvc->fetchRec($masterId);
		$Detail = cls::get($masterMvc->mainDetail);
    	$dQuery = $Detail->getQuery();
    		
    	$dQuery->where("#{$Detail->masterKey} = {$rec->id}");
    	while($dRec = $dQuery->fetch()){
    		if($masterMvc instanceof deals_InvoiceMaster){
    			$rateFld = 'rate';
    		}
    		
    		$dRec->{$priceFld} = ($dRec->{$priceFld} / $rec->{$rateFld}) * $newRate;
    		
    		if($masterMvc instanceof deals_InvoiceMaster){
    			$dRec->packPrice = $dRec->{$priceFld} * $dRec->quantityInPack;
    			$dRec->amount = $dRec->packPrice * $dRec->quantity;
    		}
    		
    		$Detail->save($dRec);
    	}
    	
    	$rec->{$rateFld} = $newRate;
    	if($masterMvc instanceof deals_InvoiceMaster){
    		$rec->displayRate = $newRate;
    	}
    	
    	$masterMvc->save($rec);
    	$masterMvc->updateMaster_($rec->id);
    	
    	if($rec->state == 'active'){
    		acc_Journal::deleteTransaction($masterMvc->getClassId(), $rec->id);
    		acc_Journal::saveTransaction($masterMvc->getClassId(), $rec->id, FALSE);
    	}
	}
	
	
	/**
	 * Помощна ф-я за намиране на транспортното тегло/обем
	 */
	private static function getMeasureRow($productId, $packagingId, $quantity, $type, $value = NULL)
	{
		expect(in_array($type, array('volume', 'weight')));
		$hint = FALSE;
		
		// Ако артикула не е складируем не му се изчислява транспортно тегло
		$isStorable = cat_products::fetchField($productId, 'canStore');
		if($isStorable != 'yes') return NULL;
		
		// Ако няма тегло взима се 'live'
		if(!isset($value)){
			if($type == 'weight'){
				$value = cat_Products::getWeight($productId, $packagingId, $quantity);
			} else {
				$value = cat_Products::getVolume($productId, $packagingId, $quantity);
			}
				
			if(!isset($value)){
				$hint = TRUE;
			}
		}
		
		// Ако няма тегло не се прави нищо
		if(!isset($value)) return NULL;
		
		$valueType = ($type == 'weight') ? 'cat_type_Weight(decimals=2)' : 'cat_type_Volume';
		$value = round($value, 2);
		
		// Вербализиране на теглото
		$valueRow = core_Type::getByName($valueType)->toVerbal($value);
		if($hint === TRUE){
			$hintType = ($type == 'weight') ? 'Транспортното тегло e прогнозно' : 'Транспортният обем е прогнозен';
			$valueRow = ht::createHint($valueRow, "{$hintType} на база количеството");
		}
		
		return $valueRow;
	}
	
	
	/**
	 * Връща реда за транспортният обем на артикула
	 *
	 * @param int $productId      - артикул
	 * @param int $packagingId    - ид на опаковка
	 * @param int $quantity       - общо количество
	 * @param double|NULL $weight - обем на артикула (ако няма се взима 'live')
	 * @return core_ET|NULL       - шаблона за показване
	 */
	public static function getVolumeRow($productId, $packagingId, $quantity, $volume = NULL)
	{
		return self::getMeasureRow($productId, $packagingId, $quantity, 'volume', $volume);
	}
	
	
	/**
	 * Връща реда за транспортното тегло на артикула
	 * 
	 * @param int $productId      - артикул
	 * @param int $packagingId    - ид на опаковка
	 * @param int $quantity       - общо количество
	 * @param double|NULL $weight - тегло на артикула (ако няма се взима 'live')
	 * @return core_ET|NULL       - шаблона за показване
	 */
	public static function getWeightRow($productId, $packagingId, $quantity, $weight = NULL)
	{
		return self::getMeasureRow($productId, $packagingId, $quantity, 'weight', $weight);
	}
	
	
	/**
	 * Връща масив с фактурите в треда
	 * 
	 * @param int $threadId           - ид на нишка
	 * @param boolean $onlyCreditNote - дали да са само КИ
	 * @return array $invoices    - масив с ф-ри или броя намерени фактури
	 */
	public static function getInvoicesInThread($threadId, $onlyCreditNote = FALSE)
	{
		$invoices = array();
		foreach (array('sales_Invoices', 'purchase_Invoices') as $class){
			$Cls = cls::get($class);
			$iQuery = $Cls->getQuery();
			$iQuery->where("#threadId = {$threadId} AND #state = 'active'");
			$iQuery->orderBy('date,number,type,dealValue', 'ASC');
			$iQuery->show('number,containerId');
			
			if($onlyCreditNote === TRUE){
				$iQuery->where("#type = 'dc_note' && #dealValue < 0");
			} else {
				$iQuery->where("#type = 'invoice' || (#type = 'dc_note' && #dealValue >= 0)");
			}
			
			while($iRec = $iQuery->fetch()){
				$Document = doc_Containers::getDocument($iRec->containerId);
				$number = str_pad($Document->fetchField('number'), '10', '0', STR_PAD_LEFT);
				$invoices[$iRec->containerId] = "#{$Document->abbr}{$number}";
			}
		}
		
		return $invoices;
	}
	
	
	/**
	 * Помощен метод връщащ разпределението на плащанията по фактури
	 * 
	 * @param int $threadId - ид на тред
	 * @return array $paid  - масив с разпределените плащания
	 */
	public static function getInvoicePayments($threadId)
	{
		expect($threadId);
		
		$invoicesArr = self::getInvoicesInThread($threadId);
		if(!count($invoicesArr)) return array();
	
		$paid = $invoices = $payDocuments = array();
		foreach (array('cash_Pko', 'cash_Rko', 'bank_IncomeDocuments', 'bank_SpendingDocuments') as $Pay){
			$Pdoc = cls::get($Pay);
			$pQuery = $Pdoc->getQuery();
			$pQuery->where("#threadId = {$threadId} AND #state = 'active'");
			$pQuery->show('containerId,amountDeal,fromContainerId,isReverse,activatedOn,valior');
			
			while($pRec = $pQuery->fetch()){
				$type = ($Pay == 'cash_Pko' || $Pay == 'cash_Rko') ? 'cash' : 'bank';
				$sign = ($pRec->isReverse == 'yes') ? -1 : 1;
				$payDocuments[$pRec->containerId] = (object)array('valior' => $pRec->valior, 'activatedOn' => $pRec->activatedOn, 'amount' => $sign * round($pRec->amountDeal, 2), 'type' => $type, 'toInvoice' => $pRec->fromContainerId, 'isReverse' => ($pRec->isReverse == 'yes'));
			}
		}
	
		uasort($payDocuments, function($a, $b){ if($a->valior == $b->valior) {return ($a->activatedOn < $b->activatedOn) ? -1 : 1;} return ($a->valior < $b->valior) ? -1 : 1;});
		$notAllocated = array_filter($payDocuments, function($a){return empty($a->toInvoice);});
		
		$cache = array();
		$newInvoiceArr = array();
		foreach ($invoicesArr as $containerId => $hnd){
			$Document = doc_Containers::getDocument($containerId);
			$iRec = $Document->fetch('dealValue,discountAmount,vatAmount,rate,type,originId,containerId');
			$cache[$containerId] = $iRec;
			$amount = round((($iRec->dealValue - $iRec->discountAmount) + $iRec->vatAmount) / $iRec->rate, 2);
			
			$key = ($iRec->type != 'dc_note') ? $containerId : $iRec->originId;
			$newInvoiceArr[$key]['total'] += $amount;
			$newInvoiceArr[$key]['current'] += $amount;
		}
		
		foreach ($newInvoiceArr as $k => $o){
			$found = array_filter($payDocuments, function($a) use ($k, $cache){return $a->toInvoice == $k || $cache[$a->toInvoice]->originId == $k;});
			$totalPercent = 1;
			
			if(count($found)){
				foreach ($found as $fId => $o){
					$newInvoiceArr[$k]['current'] -= $o->amount;
					$percent = min(round($o->amount / $newInvoiceArr[$k]['total'], 2), 1);
					$totalPercent -= $percent;
				
					$paid[$k][$fId] = (object)array('containerId' => $fId, 'percent' => $percent, 'type' => $o->type, 'isReverse' => $o->isReverse);
				}
			}
			
			if($newInvoiceArr[$k]['current'] <= 0) continue;
			
			if(count($notAllocated)){
				foreach ($notAllocated as $nId => &$o1){
					
					$unset = FALSE;
					if($o1->amount > $newInvoiceArr[$k]['current']){
						$percent = $totalPercent;
						$o1->amount -= $newInvoiceArr[$k]['current'];
						$newInvoiceArr[$k]['current'] = 0;
					} else {
						$percent = min(round($o1->amount / $newInvoiceArr[$k]['total'], 2), 1);
						$totalPercent -= $percent;
						$newInvoiceArr[$k]['current'] -= $o1->amount;
						$unset = TRUE;
					}
						
					$paid[$k][$nId] = (object)array('containerId' => $nId, 'percent' => $percent, 'type' => $o1->type, 'isReverse' => $o1->isReverse);
					if($unset === TRUE){
						unset($notAllocated[$nId]);
					}
				}
			}
		}
		
		return $paid;
	}
	
	
	/**
	 * Ъпдейтва начина на плащане на фактурите в нишката
	 *
	 * @param int $threadId - ид на крака
	 * @return void
	 */
	public static function updateAutoPaymentTypeInThread($threadId)
	{
		// Разпределените начини на плащане
		core_Cache::remove('threadInvoices', "t{$threadId}");
		$invoicePayments = deals_Helper::getInvoicePayments($threadId);
		core_Cache::set('threadInvoices', "t{$threadId}", $invoicePayments, 1440);
	
		// Всички ф-ри в нишката
		$invoices = self::getInvoicesInThread($threadId);
		if(!count($invoices)) return;
		
		foreach ($invoices as $containerId => $hnd){
			$Doc = doc_Containers::getDocument($containerId);
			$rec = $Doc->fetch();
			$rec->autoPaymentType = $Doc->getAutoPaymentType();
			
			$Doc->getInstance()->save_($rec, 'autoPaymentType');
			doc_DocumentCache::cacheInvalidation($rec->containerId);
		}
	}
}
