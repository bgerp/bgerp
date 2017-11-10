<?php



/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа planning_Tasks
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
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
	 * Връща масив с плейсхолдърите, които ще се попълват от getLabelData
	 *
	 * @param mixed $id - ид или запис
	 * @return array $fields - полета за етикети
	 */
	public function getLabelPlaceholders($id)
	{
		expect($rec = planning_Tasks::fetchRec($id));
		$fields = array('JOB', 'NAME', 'BARCODE', 'MEASURE_ID', 'QUANTITY', 'ИЗГЛЕД', 'PREVIEW', 'SIZE_UNIT', 'DATE', 'SIMPLE_NAME', 'PRODUCT_CODE');
		expect($origin = doc_Containers::getDocument($rec->originId));
		$jobRec = $origin->fetch();
		if(isset($jobRec->saleId)){
			$fields[] = 'ORDER';
			$fields[] = 'COUNTRY';
		}
	
		// Извличане на всички параметри на артикула
		$params = planning_Tasks::getTaskProductParams($rec, TRUE);
	
		$params = array_keys(cat_Params::getParamNameArr($params, TRUE));
		$fields = array_merge($fields, $params);
	
		// Добавяне на допълнителни плейсхолдъри от драйвера на артикула
		if($Driver = cat_Products::getDriver($rec->productId)){
			$additionalFields = $Driver->getAdditionalLabelData($rec->productId, $this->class);
			if(count($additionalFields)){
				$fields = array_merge($fields, array_keys($additionalFields));
			}
		}
	
		return $fields;
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
	
		// Генериране на превю на артикула за етикети
		$previewWidth = planning_Setup::get('TASK_LABEL_PREVIEW_WIDTH');
		$previewHeight = planning_Setup::get('TASK_LABEL_PREVIEW_HEIGHT');
	
		// Ако в задачата има параметър за изглед, взима се той
		$previewParamId = cat_Params::fetchIdBySysId('preview');
		if($prevValue = cat_products_Params::fetchField("#classId = {$this->class->getClassId()} AND #productId = {$rec->id} AND #paramId = {$previewParamId}", 'paramValue')){
			$Fancybox = cls::get('fancybox_Fancybox');
			$preview = $Fancybox->getImage($prevValue, array($previewWidth, $previewHeight), array('550', '550'))->getContent();
		} else {
				
			// Иначе се взима от дефолтния параметър
			$preview = cat_Products::getPreview($rec->productId, array($previewWidth, $previewHeight));
		}
	
		if(!empty($preview)){
			$res['ИЗГЛЕД'] = $preview;
			$res['PREVIEW'] = $preview;
		}
	
		$res['SIZE_UNIT'] = 'cm';
		$res['DATE'] = dt::mysql2verbal(dt::today(), 'm/y');
	
		// Ако от драйвера идват още параметри, добавят се с приоритет
		if($Driver = cat_Products::getDriver($rec->productId)){
			$additionalFields = $Driver->getAdditionalLabelData($rec->productId, $this->class);
			if(count($additionalFields)){
				$res = $additionalFields + $res;
			}
		}
	
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
}