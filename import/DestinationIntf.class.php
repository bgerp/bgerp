<?php



/**
 * Интерфейс за класове, в които ще се импортират данни
 *
 *
 * @category  bgerp
 * @package   import
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за класове, в които ще се импортират данни
 */
class import_DestinationIntf
{
	
	
	/**
	 * Инстанция на класа
	 */
	public $class;
	
	
	/**
	 * Импортиране на вече подготвените записи за импорт
	 * 
	 * @see import_DriverIntf
	 * @param array $recs
	 * @return void
	 */
	public function importRecs($recs)
	{
		$this->class->importRecs($recs);
	}
}