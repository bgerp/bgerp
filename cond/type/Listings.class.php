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
	 * @param mixed $domainClass - клас на домейна
	 * @param mixed $domainId    - ид на домейна
	 * @param NULL|string $value - стойност
	 * @return core_Type         - готовия тип
	 */
	public function getType($rec, $domainClass, $domainId, $value = NULL)
	{
		$options = array();
		
		$lQuery = cat_Listings::getQuery();
		$lQuery->where("#state = 'active'");
		
		if(cls::haveInterface('crm_ContragentAccRegIntf', $domainClass)){
			$folderId = cls::get($domainClass)->forceCoverAndFolder($domainId);
			$lQuery->where("#isPublic = 'yes' OR #folderId = {$folderId}");
		} else {
			$lQuery->where("#isPublic = 'yes'");
		}
		
		while($rec = $lQuery->fetch()){
			$options[$rec->id] = $rec->title;
		}
		
		$Type = core_Type::getByName("key(mvc=cat_Listings,makeLink)");
		$options = count($options) ? array('' => '') + $options : $options;
		$Type->options = $options;
		
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