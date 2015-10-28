<?php


/**
 * Тип за параметър 'Условие на доставка'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Условие на доставка
 */
class cond_type_Delivery extends cond_type_Proto
{
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param int $paramId - ид на параметър
	 * @return core_Type - готовия тип
	 */
	public function getType($rec)
	{
		$Type = core_Type::getByName("key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)");
		
		return $Type;
	}
}