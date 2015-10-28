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
class cond_type_Double extends cond_type_Proto
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
	 * @param int $paramId - ид на параметър
	 * @return core_Type - готовия тип
	 */
	public function getType($rec)
	{
		$Type = parent::getType($rec);
	
		$params = array();
		
		if(isset($rec->round)){
			$params['decimals'] = $rec->round;
		} else {
			$params['smartRound'] = smartRound;
		}
		
		if(isset($rec->min)){
			$params['Min'] = $rec->min;
		}
		
		if(isset($rec->max)){
			$params['Max'] = $rec->max;
		}
		
		if(count($params)){
			$Type = cls::get($Type, array('params' => $params));
		}
		
		return $Type;
	}
}