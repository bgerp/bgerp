<?php


/**
 * Помощен клас за видове типовете документи
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_DocumentTypes
{
	
	/**
	 * Документа е вид договор
	 */
	const CONTRACT = 'contract';
	
	
	/**
	 * Документа е вид запитване
	 */
	const INQUIRY = 'inquiry';
	
	
	/**
	 * Документа е вид фактура
	 */
	const INVOICE = 'invoice';
	
	
	/**
	 * Стандартен документ
	 */
	const STANDARD = 'standard';
}