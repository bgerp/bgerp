<?php



/**
 * Интерфейс за драйвър POS-а за фискален принтер
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_FiscPrinterIntf
{
	
    /**
     * Форсира изтегляне на файла за фискалния принтер
     *  
     * @param int $id - ид на бележка
     * @return void
     */
    function createFile($id)
    {
    	return $this->class->createFile($id);
    }
}