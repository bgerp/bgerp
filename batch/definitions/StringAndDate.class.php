<?php


/**
 * Драйвер за партиди на Дени
 *
 *
 * @category  denny
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Номер + Срок на годност
 */
class batch_definitions_StringAndDate extends batch_definitions_Varchar
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'batch_definitions_Deni';
	
	
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    	$fieldset->FLD('format', "enum(m.Y=|*11.1999,d.m.Y=|*22.11.1999, d-m-Y=|*22-11-1999, d/m/Y=|*22/11/1999, m.d.Y=|*11.22.1999, m-d-Y=|*11-22-1999, m/d/Y=|*11/22/1999, d.m.y=|*22.11.99, d-m-y=|*22-11-99, d/m/y=|*22/11/99, m.d.y=|*11.22.99, m-d-y=|*11-22-99, m/d/y=|*11/22/99)'", 'caption=Маска');
    	$fieldset->FLD('length', 'int', 'caption=Дължина');
    	$fieldset->FLD('delimiter', 'enum(&#x20;=Интервал,.=Точка,&#44;=Запетая,/=Наклонена,&dash;=Тире)', 'caption=Разделител');
    	$fieldset->FLD('prefix', 'varchar(3)', 'caption=Префикс');
    }
    
    
    /**
     * Проверява дали стойността е невалидна
     *
     * @param string $value - стойноста, която ще проверяваме
     * @param quantity $quantity - количеството
     * @param string &$msg -текста на грешката ако има
     * @return boolean - валиден ли е кода на партидата според дефиницията или не
     */
    public function isValid($value, $quantity, &$msg)
    {
    	// Ако артикула вече има партидаза този артикул с тази стойност, се приема че е валидна
    	if(batch_Items::fetchField(array("#productId = {$this->rec->productId} AND #batch = '[#1#]'", $value))){
    		return TRUE;
    	}
    	
    	$delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');
    	if(strpos($value, $delimiter) === FALSE){
    		$msg = "В партидата трябва да има|* " . $delimiter;
    		
    		return FALSE;
    	}
    	
    	list($string, $date) = explode($delimiter, $value);
    	if(isset($this->rec->length)){
    		if(mb_strlen($string) > $this->rec->length){
    			$msg = "|*{$string} |е над допустимата дължина от|* <b>{$this->rec->length}</b>";
    			return FALSE;
    		}
    	}
    	
    	if(!dt::checkByMask($date, $this->rec->format)){
    		$f = dt::mysql2verbal(dt::today(), $this->rec->format);
    		$msg = "|Срока на годност трябва да е във формата|* <b>{$f}</b>";
    		return FALSE;
    	}
    	
    	if(isset($this->rec->prefix)){
    		$substring = substr($string, 0, mb_strlen($this->rec->prefix));
    		if($substring != $this->rec->prefix){
    			$msg = "|Партидата трябва да започва с|* <b>{$this->rec->prefix}</b>";
    			return FALSE;
    		}
    	}
    	
    	return TRUE;
    }
    
    
    /**
     * Проверява дали стойността е невалидна
     *
     * @return core_Type - инстанция на тип
     */
    public function getBatchClassType()
    {
    	$Type = core_Type::getByName('varchar');
    
    	return $Type;
    }
    
    
    /**
     * Какви са свойствата на партидата
     * 
     * @param varchar $value - номер на партидара
     * @return array - свойства на партидата
     * 	масив с ключ ид на партидна дефиниция и стойност свойството
     */
    public function getFeatures($value)
    {
    	list($string, $date) = explode('|', $value);
    	
    	$varcharClassId = batch_definitions_Varchar::getClassId();
    	$dateClassId = batch_definitions_ExpirationDate::getClassId();
    	$date = dt::getMysqlFromMask($date, $this->rec->format);
    	
    	$res[] = (object)array('name' => 'Номер', 'classId' => $varcharClassId, 'value' => $string);
    	$res[] = (object)array('name' => 'Срок на годност', 'classId' => $dateClassId, 'value' => $date);
    	
    	return $res;
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function toVerbal($value)
    {
    	$value = parent::toVerbal($value);
    	$delimiter = html_entity_decode($this->rec->delimiter, ENT_COMPAT, 'UTF-8');
    	
    	$value = str_replace('|', $delimiter, $value);
    	
    	return $value;
    }
    
    
    /**
     * Подрежда подадените партиди
     *
     * @param array $batches - наличните партиди
     * 		['batch_name'] => ['quantity']
     * @param date|NULL $date
     * return void
     */
    public function orderBatchesInStore(&$batches, $storeId, $date = NULL)
    {
    	$dates = array_keys($batches);
    
    	if(is_array($dates)){
    		usort($dates, function($a, $b) {
    			list($aLeft, $aDate) = explode('|', $a);
    			list($bLeft, $bDate) = explode('|', $b);
    			
    			$aString = dt::getMysqlFromMask($aDate, $this->rec->format);
    			$bString = dt::getMysqlFromMask($bDate, $this->rec->format);
    			$aTime = strtotime($aString);
    			$bTime = strtotime($bString);
    			
    			if($aTime == $bTime){
    				$cmp  = (strcasecmp($aLeft, $bLeft) < 0) ? -1 : 1;
    				
    				return $cmp;
    			}
    			
    			return ($aTime < $bTime) ? -1 : 1;
    		});
    		
    		$sorted = array();
    		foreach ($dates as $date){
    			$sorted[$date] = $batches[$date];
    		}
    			
    		$batches = $sorted;
    	}
    }
}