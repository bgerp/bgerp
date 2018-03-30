<?php



/**
 * Драйвер за начин на плащане в е-магазина
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Плащане при получаване
 */
class eshop_interfaces_Cod extends eshop_interfaces_ProtoPayment
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'eshop, ceo, admin';
}