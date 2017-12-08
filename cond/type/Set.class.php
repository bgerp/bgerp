<?php


/**
 * Тип за параметър 'Множество'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Множество
 */
class cond_type_Set extends cond_type_abstract_Proto
{
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('options', 'text', 'caption=Конкретизиране->Опции,before=default,mandatory');
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
		$options = static::text2options($rec->options);
		
		// Ако има подадена стойност и тя не е в опциите, добавя се
		if(isset($value)){
			$value = trim($value);
			if(!array_key_exists($value, $options)){
				$options[$value] = $value;
			}
		}
		
		$options = arr::fromArray($options);
		
		$Type = core_Type::getByName("set($options)");
		
		return $Type;
	}
}