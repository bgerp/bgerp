<?php



/**
 * Драйвер за начин на доставка 'Безплатна доставка'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Безплатна доставка
 */
class eshop_interfaces_FreeDelivery extends eshop_interfaces_ProtoDelivery
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'eshop, ceo, admin';
}