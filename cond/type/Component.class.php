<?php


/**
 * Тип за параметър 'Компонент'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Компонент
 */
class cond_type_Component extends cond_type_abstract_Proto
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'cond_type_Material';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('products', 'keylist(mvc=cat_Products,select=name)', 'caption=Конкретизиране->Вложими,before=default,mandatory');
		$fieldset->setSuggestions('products', cat_Products::getByProperty('canConvert'));
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
		$Type = core_Type::getByName("key(mvc=cat_Products,select=name,allowEmpty)");
		$options = keylist::toArray($rec->products);
		
		if(isset($value)){
			if(!array_key_exists($value, $options)){
				$options[$value] = $value;
			}
		}
		
		if(is_array($options)){
			foreach ($options as $id => &$opt){
				$opt = cat_Products::getTitleById($id, FALSE);
			}
		}
		
		$Type->options = array('' => '') + $options;
		
		return $Type;
	}
}