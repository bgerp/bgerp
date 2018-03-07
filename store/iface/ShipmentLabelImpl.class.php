<?php



/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа store_ShipmentOrders
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see label_SequenceIntf
 *
 */
class store_iface_ShipmentLabelImpl
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
		$placeholders['NOMER']        = (object)array('type' => 'text');
		$placeholders['Текущ_етикет'] = (object)array('type' => 'text', 'input=hidden');
		$placeholders['Общо_етикети'] = (object)array('type' => 'text');
		$placeholders['DESTINATION']  = (object)array('type' => 'text');
		$placeholders['SPEDITOR']     = (object)array('type' => 'text');
		$placeholders['DATE']         = (object)array('type' => 'text');
		
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
		$rec = $this->class->fetchRec($id);
    	$count = ($rec->palletCountInput) ? $rec->palletCountInput : store_ShipmentOrders::countCollets($rec->id);
    	$count = (empty($count)) ? NULL : $count;
    	
    	if(isset($count)){
    		$count = ceil($count);
    		if($count % 2 == 1) $count++;
    	}
    	
    	return $count;
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
		$rec = $this->class->fetchRec($id);
		$logisticData = $this->class->getLogisticData($rec);
		$destination = "{$logisticData['toPCode']} {$logisticData['toPlace']}, {$logisticData['toCountry']}";
		$date = dt::mysql2verbal(dt::today(), 'd/m/y');
		
		$arr = array();
		for($i = 1; $i <= $cnt; $i++){
			$res = array('NOMER' => $rec->id, 'Текущ_етикет' => $i, 'DESTINATION' => $destination, 'DATE' => $date);
			if(isset($rec->lineId)){
				$res['SPEDITOR'] = trans_Lines::getTitleById($rec->lineId);
			
				if($count = $this->getLabelEstimatedCnt($id)){
					$res['Общо_етикети'] = $count;
				}
			}
			
			$arr[] = $res;
		}
		
		return $arr;
	}
}