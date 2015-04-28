<?php



/**
 * Интерфейс за документи - към които ще се добавя документ за разпределение на разходи
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за документи, към които ще се добавя документ за разпределение на разходи
 */
class acc_ExpenseAllocationSourceIntf
{
	
	/**
	 * Инстанция на мениджъра имащ интерфейса
	 */
	public $class;
	
	
	/**
	 * Връща масив със складируемите артикули, върху които ще се разпределят разходи
     *
     * @param int $id - ид на документа
     * @param int $limit - ограничение
     * @return array $products - намерените артикули
	 */
	function getStorableProducts($id, $limit)
	{
		$this->class->getStorableProducts($id, $limit);
	}
	
	
	/**
	 * Връща ид-то на склада към артикулите в него, на които ще се разпределят разходите
	 * 
	 * @param int $id
	 * @return int $storeId - ид на скалда
	 */
	function getStoreId($id)
	{
		$this->class->getStoreId($id);
	}
}