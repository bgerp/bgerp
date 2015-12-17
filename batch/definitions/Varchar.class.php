<?php


/**
 * Базов драйвер за вид партида 'varchar'
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Символи(128)
 */
class batch_definitions_Varchar extends batch_definitions_Proto
{
	
	public function getAutoValue($class, $id)
	{
		return str::getRand() . str::getRand();
	}
}