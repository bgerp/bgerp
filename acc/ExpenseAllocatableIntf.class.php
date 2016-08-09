<?php



/**
 * Интерфейс за документи, към които може да се пуска документ за разпределяне на разходи
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за документи, към които може да се пуска документ за разпределяне на разходи
 */
class acc_ExpenseAllocatableIntf extends doc_DocumentIntf
{
    
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Връща нескладируемите артикули върху, които
     * не са разпределени разходи от документа
     *
     * @param int $id       - ид
     * @param string $limit - брой записи, NULL за всички
     * @return array $res   - масив със всички записи
     *
     * 				o originRecId    - към кой ред от детайла е записа
     * 				o productId      - ид на артикула
     * 				o packagingId    - ид на опаковката/мярката
     * 				o quantityInPack - к-во в опаковка, ако е основната е 1
     * 				o quantity       - чисто количество (брой в опаковка по брой опаковки)
     * 				o discount       - отстъпката върху цената
     * 				o packPrice      - цената във валутата с която се показва в документа
     */
    function getRecsForAllocation($id, $limit = NULL)
    {
    	$this->class->getRecsForAllocation($id, $limit);
    }
}