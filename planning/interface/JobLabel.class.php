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
class planning_interface_JobLabel
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
	 * Коя е дефолтната опаковка за етикетите на заданията
	 * 
	 * @param int $productId
	 * @param array $selectedPackagingArr
	 * @return stdClass|NULL
	 */
	private static function getDefaultPackRec($productId, &$selectedPackagingArr)
	{
		$selectedPackagings = planning_Setup::get('LABEL_DEFAULT_PACKAGINGS');
		$selectedPackagingArr = keylist::toArray($selectedPackagings);
		if(!count($selectedPackagingArr)) return NULL;
		
		$query = cat_products_Packagings::getQuery();
		$query->where("#productId = {$productId}");
		$query->in("packagingId", $selectedPackagingArr);
		$query->limit(1);
		
		return $query->fetch();
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
		expect($rec = planning_Jobs::fetchRec($id));
		$pRec = cat_Products::fetch($rec->productId, 'code,measureId');
		
		$res['JOB'] = $rec->id;
		$res['CODE'] = (!empty($pRec->code)) ? $pRec->code : "Art{$rec->productId}";
		
		$packRec = self::getDefaultPackRec($rec->productId, $selectedPackagingArr);
		if(!Mode::is('prepareLabel') && count($selectedPackagingArr)){
			$msg = 'Артикулът трябва да поддържа някоя от опаковките|*: ';
			$msg .= core_Type::getByName('keylist(mvc=cat_UoM,select=name)')->toVerbal(keylist::fromArray($selectedPackagingArr));
			label_exception_Redirect::expect($packRec, $msg);
		}
		
		if(empty($packRec)){
			$res['MEASURE_ID'] = tr(cat_UoM::getShortName($pRec->measureId));
			$res['QUANTITY'] = $rec->quantity;
		} else {
			$res['QUANTITY'] = $packRec->quantity;
			$res['MEASURE_ID'] =  tr(cat_UoM::getShortName($packRec->packagingId));
		}
		
		if(isset($rec->saleId)){
			$res['ORDER'] = $rec->saleId;
			
			$lg = core_Lg::getCurrent();
			if($lg != 'bg'){
				$sRec = sales_Sales::fetch($rec->saleId);
				$countryId = cls::get($sRec->contragentClassId)->fetchField($sRec->contragentId, 'country');
				$res['OTHER'] = drdata_Countries::fetchField($countryId, 'letterCode2') . " " . date("m/y");
			}
		}
		
		// Ако от драйвера идват още параметри, добавят се с приоритет
		if($Driver = cat_Products::getDriver($rec->productId)){
			$additionalFields = $Driver->getAdditionalLabelData($rec->productId, $this->class);
			if(count($additionalFields)){
				$res = $additionalFields + $res;
			}
		}
		
		return $res;
	}
	
	
	/**
	 * Кои плейсхолдъри немогат да се предефинират от потребителя
	 * 
	 * @param int $id
	 * @return array
	 */
	public function getReadOnlyPlaceholders($id)
	{
		return array();
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
		$allowSkip = TRUE;
		$rec = $this->class->fetch($id);
		
		$packRec = self::getDefaultPackRec($rec->productId, $selectedPackagingArr);
		$res = (empty($packRec)) ? $rec->quantity : round($rec->quantity / $packRec->quantity, 2);
		
		return $res;
	}
}