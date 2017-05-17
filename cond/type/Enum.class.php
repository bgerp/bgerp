<?php


/**
 * Тип за параметър 'Избор'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Избор
 */
class cond_type_Enum extends cond_type_abstract_Proto
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
		$Type = cls::get('type_Enum');
        $Type->options = static::text2options($rec->options);
        
        // Ако има подадена стойност и тя не е в опциите, добавя се
        if(isset($value)){
        	$value = trim($value);
        	if(!array_key_exists($value, $Type->options)){
        		$Type->options[$value] = $value;
        	}
        }
        
		return $Type;
	}
}