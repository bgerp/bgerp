<?php



/**
 * Базов драйвер за плащания в онлайн магазина
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Базов драйвер за плащания
 */
abstract class eshop_interfaces_ProtoPayment extends core_BaseClass
{

	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'eshop_PaymentIntf';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
	
	}
	
	
	/**
	 * Може ли вградения обект да се избере
	 */
	public function canSelectDriver($userId = NULL)
	{
		return haveRole($this->canSelectDriver);
	}
}