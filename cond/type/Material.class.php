<?php


/**
 * Тип за параметър 'Материал'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Материал
 */
class cond_type_Material extends cond_type_Proto
{
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param int $paramId - ид на параметър
	 * @return core_Type - готовия тип
	 */
	public function getType($rec)
	{
		$Type = core_Type::getByName("key(mvc=cat_Products,select=name,allowEmpty)");
		
		$convertable = array('' => '') + cat_Products::getByProperty('canConvert');
		$Type->options = $convertable;
		
		return $Type;
	}
}