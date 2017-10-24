<?php


/**
 * Тип за параметър 'Число'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Число
 */
class cond_type_Double extends cond_type_abstract_Proto
{
	
	
	/**
	 * Кой базов тип наследява
	 */
	protected $baseType = 'type_Double';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('round', 'int', 'caption=Конкретизиране->Закръгляне,before=default');
		$fieldset->FLD('min', 'double', 'caption=Конкретизиране->Минимум,after=round');
		$fieldset->FLD('max', 'double', 'caption=Конкретизиране->Максимум,after=min');
	}
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param stdClass $rec      - запис на параметъра
	 * @param mixed $domainClass - клас на домейна
	 * @param mixed $domainId    - ид на домейна
	 * @param NULL|string $value - стойност
	 * @return core_Type         - готовия тип
	 */
	public function getType($rec, $domainClass = NULL, $domainId = NULL, $value = NULL)
	{
		$Type = parent::getType($rec, $domainClass, $domainId, $value);
	
		$params = array();
		
		if(isset($rec->round)){
			$params['decimals'] = $rec->round;
		} else {
			$params['smartRound'] = smartRound;
		}
		
		if(isset($rec->min)){
			$params['min'] = $rec->min;
		}
		
		if(isset($rec->max)){
			$params['max'] = $rec->max;
		}
		
		if(count($params)){
			$Type = cls::get($Type, array('params' => $params));
		}
		
		return $Type;
	}
}