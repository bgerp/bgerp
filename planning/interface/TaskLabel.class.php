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
	 * Може ли шаблона да бъде избран от класа
	 *
	 * @param int $id         - ид на обект от класа
	 * @param int $templateId - ид на шаблон
	 * @return boolean
	 */
	public function canSelectTemplate($id, $templateId)
	{
		return $this->class->canSelectTemplate($id, $templateId);
	}
	
	
	/**
	 * Връща данни за етикети
	 *
	 * @param int $id - ид на задача
	 * @param number $labelNo - номер на етикета
	 *
	 * @return array $res - данни за етикетите
	 *
	 * @see label_SequenceIntf
	 */
	public function getLabelData($id, $labelNo = 0)
	{
		$res = array();
		expect($rec = planning_Tasks::fetchRec($id));
		expect($origin = doc_Containers::getDocument($rec->originId));
		$jobRec = $origin->fetch();
	
		// Информация за артикула и заданието
		$res['JOB'] = "#" . $origin->getHandle();
		$res['NAME'] = cat_Products::getTitleById($rec->productId);
		
		$pRec = cat_Products::fetch($rec->productId, 'name,code');
		$res['SIMPLE_NAME'] = cat_Products::getVerbal($pRec, 'name');
		$res['PRODUCT_CODE'] = (!empty($pRec->code)) ? cat_Products::getVerbal($pRec, 'code') : "Art{$pRec->id}";
		
		// Генериране на баркод
		if($labelNo != 0){
			$serial = planning_TaskSerials::force($id, $labelNo);
			$paddLength = planning_Setup::get('SERIAL_STRING_PAD');
			$serial = str_pad($serial, $paddLength, '0', STR_PAD_LEFT);
			$res['BARCODE'] = $serial;
		} else {
			$res['BARCODE'] = 'BARCODE';
		}
	
		// Информация за артикула
		$measureId = cat_Products::fetchField($rec->productId, 'measureId');
		$res['MEASURE_ID'] = tr(cat_UoM::getShortName($measureId));
		$res['QUANTITY'] = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($rec->quantityInPack);
		if(isset($jobRec->saleId)){
			$res['ORDER'] =  "#" . sales_Sales::getHandle($jobRec->saleId);
			$logisticData = cls::get('sales_Sales')->getLogisticData($jobRec->saleId);
			$res['COUNTRY'] = drdata_Countries::fetchField("#commonName = '{$logisticData['toCountry']}'", 'letterCode2');
		}
	
		// Извличане на всички параметри на артикула
		Mode::push('text', 'plain');
		$params = planning_Tasks::getTaskProductParams($rec, TRUE);
		Mode::pop('text');
	
		$params = cat_Params::getParamNameArr($params, TRUE);
		$res = array_merge($res, $params);
	
		// Ако от драйвера идват още параметри, добавят се с приоритет
		if($Driver = cat_Products::getDriver($rec->productId)){
			$additionalFields = $Driver->getAdditionalLabelData($rec->productId, $this->class);
			if(count($additionalFields)){
				$res = $additionalFields + $res;
			}
		}
	
		$res['SIZE_UNIT'] = 'cm';
		$res['DATE'] = dt::mysql2verbal(dt::today(), 'm/y');
	
		// Връщане на масива, нужен за отпечатването на един етикет
		return $res;
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
	public function getEstimateCnt($id, &$allowSkip)
	{
		// Планираното количество
		$rec = planning_Tasks::fetch($id);
	
		return $rec->plannedQuantity;
	}
	
	
	/**
	 * Кои плейсхолдъри немогат да се предефинират от потребителя
	 * 
	 * @param int $id
	 * @return array
	 */
	public function getReadOnlyPlaceholders($id)
	{
		$arr = arr::make(array('BARCODE'), TRUE);
		
		return $arr;
	}
}