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
		$pRec = cat_Products::fetch($rec->productId, 'code');
		
		$res['JOB'] = $rec->id;
		$res['CODE'] = (!empty($pRec->code)) ? $pRec->code : "Art{$rec->productId}";
		$res['QUANTITY'] = $rec->quantity;
		if(isset($rec->saleId)){
			$res['ORDER'] = $rec->saleId;
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
		
		return $rec->packQuantity;
	}
}