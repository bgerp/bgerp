<?php



/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа cat_products_Packagings
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see label_SequenceIntf
 *
 */
class cat_interface_PackLabelImpl
{
	
	
	/**
	 * Инстанция на класа
	 */
	public $class;
	
	
	/**
	 * Връща наименованието на етикета
	 *
	 * @param integer $id
	 * @return string
	 */
	public function getLabelName($id)
	{
		$rec = $this->class->fetchRec($id);
		$productName = cat_Products::getTitleById($rec->productId);
		$packName = cat_UoM::getShortName($rec->packagingId);
		$labelName = "{$productName} ({$packName})";
		
		return $labelName;
	}
	
	
	/**
	 * Връща масив с данните за плейсхолдерите
	 *
	 * @return array
	 * Ключа е името на плейсхолдера и стойностите са обект:
	 * type -> text/picture - тип на данните на плейсхолдъра
	 * len -> (int) - колко символа макс. са дълги данните в този плейсхолдер
	 * readonly -> (boolean) - данните не могат да се променят от потребителя
	 * hidden -> (boolean) - данните не могат да се променят от потребителя
	 * importance -> (int|double) - тежест/важност на плейсхолдера
	 * example -> (string) - примерна стойност
	 */
	public function getLabelPlaceholders($objId = NULL)
	{
		$placeholders = array();
		$placeholders['JOB']              = (object)array('type' => 'text');
		$placeholders['CODE']             = (object)array('type' => 'text');
		$placeholders['NAME']             = (object)array('type' => 'text');
		$placeholders['DATE']             = (object)array('type' => 'text');
		$placeholders['PREVIEW']          = (object)array('type' => 'picture');
		$placeholders['MEASURE_ID']       = (object)array('type' => 'text');
		$placeholders['QUANTITY']         = (object)array('type' => 'text');
		$placeholders['ORDER']            = (object)array('type' => 'text');
		$placeholders['OTHER']            = (object)array('type' => 'text');
		$placeholders['SERIAL']           = (object)array('type' => 'text', 'hidden' => TRUE);
		$placeholders['MATERIAL']         = (object)array('type' => 'text');
		$placeholders['SIZE_UNIT']        = (object)array('type' => 'text');
		$placeholders['SIZE']             = (object)array('type' => 'text');
		$placeholders['CATALOG_PRICE']    = (object)array('type' => 'text');
		$placeholders['CATALOG_CURRENCY'] = (object)array('type' => 'text');
		$placeholders['EAN']              = (object)array('type' => 'barcode');
		
		if(isset($objId)){
			$labelData = $this->getLabelData($objId, 1, TRUE);
			if(isset($labelData[0])){
				foreach ($labelData[0] as $key => $val){
					if(!array_key_exists($key, $placeholders)){
						$placeholders[$key] = (object)array('type' => 'text');
					}
					$placeholders[$key]->example = $val;
				}
			}
		}
		
		return $placeholders;
	}
	
	
	/**
	 * Връща масив с всички данни за етикетите
	 *
	 * @param integer $id
	 * @param integer $cnt
	 * @param boolean $onlyPreview
	 *
	 * @return array - масив от масиви с ключ плейсхолдера и стойността
	 */
	public function getLabelData($id, $cnt, $onlyPreview = FALSE)
	{
	    static $resArr = array();
	    $lg = core_Lg::getCurrent();
	    
	    $key = $id . '|' . $cnt . '|' . $onlyPreview . '|' . $lg;
	    
	    if (isset($resArr[$key])) return $resArr[$key];
	    
		expect($rec = cat_products_Packagings::fetchRec($id));
		$pRec = cat_Products::fetch($rec->productId, 'code,measureId');
		$quantity = $rec->quantity;
		
		// Каква е мярката и количеството
		$measureId = $pRec->measureId;
		
		// Кое е последното задание към артикула
		$jQuery = planning_Jobs::getQuery();
		$jQuery->XPR('order', 'int', "(CASE #state WHEN 'active' THEN 1 WHEN 'wakeup' THEN 2 WHEN 'stopped' THEN 3 END)");
		$jQuery->where("#productId = {$rec->productId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup')");
		$jQuery->orderBy("#order=ASC,#id=DESC");
		$jQuery->show('id,saleId');
		if($jRec = $jQuery->fetch()){
			$jobCode = mb_strtoupper(planning_Jobs::getHandle($jRec->id));
			if($lg != 'bg' && isset($jRec->saleId)){
				$lData = cls::get('sales_Sales')->getLogisticData($jRec->saleId);
				$countryCode = drdata_Countries::fetchField(array("#commonName = '[#1#]'", $lData['toCountry']), 'letterCode2');
				$countryCode .= " " . date("m/y");
			}
		}
		
		$code = (!empty($pRec->code)) ? $pRec->code : "Art{$rec->productId}";
		$name = trim(cat_Products::getVerbal($rec->productId, 'name'));
		$date = date("m/y");
		
		// Цена по каталог с ДДС
		if($catalogPrice = price_ListRules::getPrice(price_ListRules::PRICE_LIST_CATALOG, $rec->productId, $rec->packagingId)){
			$catalogPrice *= 1 + cat_Products::getVat($rec->productId);
			
			$catalogPrice = round($catalogPrice * $quantity, 2);
			$currencyCode = acc_Periods::getBaseCurrencyCode();
			
			Mode::push('text', 'plain');
			$catalogPrice = core_Type::getByName('double(decimals=2)')->toVerbal($catalogPrice);
			Mode::pop('text', 'plain');
		}
		
		$quantity = cat_UoM::round($measureId, $quantity);
		$measureId = tr(cat_UoM::getShortName($measureId));
		
		// Продуктови параметри
		$params = cat_Products::getParams($rec->productId, NULL, TRUE);
		$params = cat_Params::getParamNameArr($params, TRUE);
		
		$additionalFields = array();
		$Driver = cat_Products::getDriver($rec->productId);
		if(is_object($Driver)){
			$additionalFields = $Driver->getAdditionalLabelData($rec->productId, $this->class);
		}
		
		$arr = array();
		for($i = 1; $i <= $cnt; $i++){
			$res = array('CODE' => $code, 'NAME' => $name, 'DATE' => $date, 'MEASURE_ID' => $measureId, 'QUANTITY' => $quantity);	
			if(!empty($catalogPrice)){
				$res['CATALOG_PRICE'] = $catalogPrice;
				$res['CATALOG_CURRENCY'] = $currencyCode;
			}
			
			if(count($params)){
				$res = array_merge($res, $params);
			}
			
			if(isset($jobCode)){
				$res['JOB'] = $jobCode;
			}
			
			if(isset($rec->eanCode)){
				$res['EAN'] = $rec->eanCode;
			}
			
			if(is_object($Driver)){
				if(count($additionalFields)){
					$res = $additionalFields + $res;
				}
				
				$res['SERIAL'] = 'EXAMPLE';
				if($onlyPreview === FALSE){
					$res['SERIAL'] = $Driver->generateSerial($rec->productId, 'cat_products_Packagings', $rec->id);
				}
			}

			if(isset($countryCode) && empty($res['OTHER'])){
				$res['OTHER'] = $countryCode;
			}
			
			$arr[] = $res;
		}
		
		$resArr[$key] = $arr;
		
		return $resArr[$key];
	}
	
	
	/**
	 * Броя на етикетите, които могат да се отпечатат
	 *
	 * @param integer $id
	 * @param string $allowSkip
	 *
	 * @return integer
	 *
	 * @see label_SequenceIntf
	 */
	public function getLabelEstimatedCnt($id)
	{
		return NULL;
	}
}