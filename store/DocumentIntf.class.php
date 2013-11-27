<?php



/**
 * Интерфейс за скалдови документи
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_DocumentIntf
{
    
    
    /**
     * Връща теглото на всички артикули в документа
     * @param stdClass $rec - запис от модела
     * @return stdClass   			
     * 				[weight]    - тегло  
	 * 				[measureId] - мярката
     */
    function getWeight($rec)
    {
        return $this->class->getWeight($rec);
    }
    
    
	/**
     * Връща обема на всички артикули в документа
     * @param stdClass $rec - запис от модела
     * @return stdClass
	 *   			[volume]    - обем 
	 * 				[measureId] - мярката
     */
    function getVolume($rec)
    {
        return $this->class->getVolume($rec);
    }
}