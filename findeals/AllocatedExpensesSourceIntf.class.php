<?php



/**
 * Интерфейс за регистри на пера, които задължително трябва да имат цена при контировката
 *
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за регистри на пера, които задължително трябва да имат цена при контировката
 */
class findeals_AllocatedExpensesSourceIntf
{
    
	
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Върху кои артикули ще се коригират стойностите
     * 
     * @param int $id - ид на обекта
     * @return array $products - масис с ид-та на продукти
     */
    function getProductsForAllocation($id)
    {
    	$this->class->getProductsForAllocation($id);
    }
    
    
    /**
     * Дали може да се генерира документ корекция на стойностите от документа
     * 
     * @param int $id
     */
    function canAddAllocatedExpensesDocument($id)
    {
    	$this->class->getProductsForAllocation($id);
    }
}