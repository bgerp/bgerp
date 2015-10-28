<?php


/**
 * Тип за параметър 'Метод на плащане'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Метод на плащане
 */
class cond_type_PaymentMethod extends cond_type_Proto
{
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param int $paramId - ид на параметър
	 * @return core_Type - готовия тип
	 */
	public function getType($rec)
	{
		$Type = core_Type::getByName("key(mvc=cond_paymentMethods,select=description,allowEmpty)");
		
		return $Type;
	}
}