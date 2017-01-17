<?php


/**
 * Тип за параметър 'Листвани артикули'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Листване
 */
class cond_type_Listings extends cond_type_Proto
{
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param stdClass $rec      - запис на параметъра
	 * @param NULL|string $value - стойност
	 * @return core_Type         - готовия тип
	 */
	public function getType($rec, $value = NULL)
	{
		$Type = core_Type::getByName("int");
		expect($this->domainObjectReference instanceof core_ObjectReference);
		$options = array();
		
		$lQuery = cat_Listings::getQuery();
		$lQuery->where("#state = 'active'");
		$lQuery->where("#isPublic = 'yes'");
		//if($this->domainObjectReference->haveInterface(st))
		
		//bp($lQuery->fetchAll());
		
		//if()
		
		
		bp($Type, $this);
		return $Type;
	}
	
	
	/**
	 * Кой може да избере драйвера
	 */
	public function canSelectDriver($userId = NULL)
	{
		return FALSE;
	}
}