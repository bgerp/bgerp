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
class cat_interface_PackaLabelImpl
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
		$placeholders['BARCODE']          = (object)array('type' => 'text', 'hidden' => TRUE);
		$placeholders['MATERIAL']         = (object)array('type' => 'text');
		$placeholders['SIZE_UNIT']        = (object)array('type' => 'text');
		$placeholders['SIZE']             = (object)array('type' => 'text');
		$placeholders['CATALOG_PRICE']    = (object)array('type' => 'text');
		$placeholders['CATALOG_CURRENCY'] = (object)array('type' => 'text');
		$placeholders['EAN']              = (object)array('type' => 'text');
		
		if(isset($objId)){
			$labelData = $this->getLabelData($objId, 1, TRUE);
			if(isset($labelData[0])){
				foreach ($labelData[0] as $key => $val){
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
		$res = array();
		expect($rec = cat_products_Packagings::fetchRec($id));
		$pRec = cat_Products::fetch($rec->productId, 'code,measureId');
		$quantity = $rec->quantity;
		$quantity = cat_UoM::round($rec->packagingId, $quantity);
		
		$code = (!empty($pRec->code)) ? $pRec->code : "Art{$rec->productId}";
		$name = cat_Products::getVerbal($rec->productId, 'name');
		$date = date("m/y");
		if($catalogPrice = price_ListRules::getPrice(price_ListRules::PRICE_LIST_CATALOG, $rec->productId, $rec->packagingId)){
			$catalogPrice = round($catalogPrice * $quantity, 2);
			$currencyCode = acc_Periods::getBaseCurrencyCode();
		}
		$measureId = tr(cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId')));
		
		$arr = array();
		for($i = 1; $i <= $cnt; $i++){
			$res = array('CODE' => $code, 'NAME' => $name, 'DATE' => $date, 'MEASURE_ID' => $measureId, 'QUANTITY' => $quantity);	
			if(!empty(($catalogPrice))){
				$res['CATALOG_PRICE'] = $catalogPrice;
				$res['CATALOG_CURRENCY'] = $currencyCode;
			}
				
			if($Driver = cat_Products::getDriver($rec->productId)){
				$additionalFields = $Driver->getAdditionalLabelData($rec->productId, $this->class);
				if(count($additionalFields)){
					$res = $additionalFields + $res;
				}
			
				if(isset($rec->eanCode)){
					$res['EAN'] = $rec->eanCode;
				}
				
				$res['BARCODE'] = 'EXAMPLE';
 				if($onlyPreview === FALSE){
					$res['BARCODE'] = $Driver->generateSerial($rec->productId, 'cat_products_Packagings', $rec->id);
				}
			}
				
			$arr[] = $res;
		}

		return $arr;
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
		$rec = $this->class->fetch($id);
		
		$quantity = $rec->quantity;
		
		$quantity *= 1.1;
		$res = ceil($quantity + 1);
		if($res % 2 == 1) $res++;
		
		return $res;
	}
}