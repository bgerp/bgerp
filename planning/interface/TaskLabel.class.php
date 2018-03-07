<?php



/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа planning_Tasks
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
class planning_interface_TaskLabel
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
	
		return "#" . $this->class->getHandle($rec);
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
		// Планираното количество
		$rec = planning_Tasks::fetch($id);
	
		return $rec->plannedQuantity;
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
		$placeholders['JOB']          = (object)array('type' => 'text');
		$placeholders['NAME']         = (object)array('type' => 'text');
		$placeholders['PRODUCT_CODE'] = (object)array('type' => 'text');
		$placeholders['BARCODE']      = (object)array('type' => 'text', 'hidden' => TRUE);
		$placeholders['MEASURE_ID']   = (object)array('type' => 'text');
		$placeholders['QUANTITY']     = (object)array('type' => 'text');
		$placeholders['ORDER']        = (object)array('type' => 'text');
		$placeholders['COUNTRY']      = (object)array('type' => 'text');
		$placeholders['SIZE_UNIT']    = (object)array('type' => 'text');
		$placeholders['SIZE']         = (object)array('type' => 'text');
		$placeholders['MATERIAL']     = (object)array('type' => 'text');
		$placeholders['OTHER']        = (object)array('type' => 'text');
		$placeholders['DATE']         = (object)array('type' => 'text');
		$placeholders['PREVIEW']      = (object)array('type' => 'picture');
		
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
		expect($rec = planning_Tasks::fetchRec($id));
		expect($origin = doc_Containers::getDocument($rec->originId));
		$jobRec = $origin->fetch();
		
		$pRec = cat_Products::fetch($rec->productId, 'name,code,measureId');
		$name = trim(cat_Products::getVerbal($pRec, 'name'));
		
		$code = (!empty($pRec->code)) ? cat_Products::getVerbal($pRec, 'code') : "Art{$pRec->id}";
		$date = dt::mysql2verbal(dt::today(), 'm/y');
		
		$measureId = $pRec->measureId;
		$quantity = cat_UoM::round($measureId, $rec->quantityInPack);
		$measureId = tr(cat_UoM::getShortName($measureId));
		
		if(isset($jobRec->saleId)){
			$orderId = "#" . sales_Sales::getHandle($jobRec->saleId);
			$logisticData = cls::get('sales_Sales')->getLogisticData($jobRec->saleId);
			$country = drdata_Countries::fetchField("#commonName = '{$logisticData['toCountry']}'", 'letterCode2');
		}
		
		// Извличане на всички параметри на артикула
		Mode::push('text', 'plain');
		$params = planning_Tasks::getTaskProductParams($rec, TRUE);
		Mode::pop('text');
		
		$params = cat_Params::getParamNameArr($params, TRUE);
		
		// Ако от драйвера идват още параметри, добавят се с приоритет
		$additionalLabelData = array();
		if($Driver = cat_Products::getDriver($rec->productId)){
			$additionalLabelData = $Driver->getAdditionalLabelData($rec->productId, $this->class);
		}
		
		$previewParamId = cat_Params::fetchIdBySysId('preview');
		$prevValue = cat_products_Params::fetchField("#classId = {$this->class->getClassId()} AND #productId = {$rec->id} AND #paramId = {$previewParamId}", 'paramValue');
		
		$arr = array();
		for($i = 1; $i <= $cnt; $i++){
			$res = array('JOB'          => planning_Jobs::getHandle($jobRec->id), 
					     'NAME'         => $name, 
					     'DATE'         => $date,
						 'CODE'         => $code,
					     'MEASURE_ID'   => $measureId, 
					     'QUANTITY'     => $quantity, 
						 'PRODUCT_CODE' => $code,
						 'SIZE_UNIT'    => 'cm',
						 'DATE'         => $date,
			);
		
			if(isset($jobRec->saleId)){
				$res['ORDER'] = $orderId;
				$res['COUNTRY'] = $country;
			}
			
			$res['BARCODE'] = 'EXAMPLE';
			
			if($Driver){
				if($onlyPreview === FALSE){
					$res['BARCODE'] = $Driver->generateSerial($rec->productId, 'planning_Tasks', $rec->id);
				}
				
				if(count($params)){
					$res = array_merge($res, $params);
				}
				
				if(count($additionalLabelData)){
					$res = $additionalLabelData + $res;
				}
				
				if(isset($prevValue)){
					$res['PREVIEW'] = $prevValue;
				}
			}
			
			$arr[] = $res;
		}
		
		return $arr;
	}
}