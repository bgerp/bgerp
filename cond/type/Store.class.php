<?php


/**
 * Тип за параметър 'Склад'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Склад
 */
class cond_type_Store extends cond_type_abstract_Proto
{
	
	
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
		$Type = core_Type::getByName("key(mvc=store_Stores,select=name,allowEmpty,makeLink)");
		
		$sQuery = store_Stores::getQuery();
		$sQuery->where("#state != 'rejected'");
		$sQuery->show('name');
		
		if(!haveRole('ceo')){
			bgerp_plg_FLB::addUserFilterToQuery('store_Stores', $sQuery);
		}
		
		$options = $sQuery->fetchAll();
		
		// Ако я има стойноста но я няма в опциите, добавя се
		if(isset($value)){
			if(!array_key_exists($value, $options)){
				$options[$value] = $value;
			}
		}
		
		if(is_array($options)){
			foreach ($options as $id => &$opt){
				$opt = store_Stores::getVerbal($id, 'name');
			}
		}
		
		$Type->options = $options;
		
		return $Type;
	}
}