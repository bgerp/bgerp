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
 * @title     Листване на купуваеми артикули
 */
class cond_type_PurchaseListings extends cond_type_abstract_Listings
{
	
	
	/**
	 * Мета свойства
	 * 
	 * @string canBuy|canSell
	 */
	protected $meta = 'canBuy';
}