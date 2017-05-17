<?php


/**
 * Тип за параметър 'Цяло число'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Цяло число
 */
class cond_type_Int extends cond_type_abstract_Proto
{
	
	
	/**
	 * Кой базов тип наследява
	 */
	protected $baseType = 'type_Int';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('min', 'double', 'caption=Конкретизиране->Минимум,after=default');
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