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
     * 	[products] = array(
     * 		'id'        => ид на продукт
     * 		'managerId' => ид на мениджър на продукт
     * 		'name'  	=> име
     * 		'quantity'  => к-во
     * 		'discount'  => отстъпка
     * 		'measure'   => име на мярка/опаковка
     * 		'price'		=> цена в основна валута без ДДС
     * 		'vat'		=> ДДС %
     * 		'vatGroup'	=> Група за ДДС (А, Б, В, Г)
     * );
     *  [payments] = array(
     *  	'type' => код за начина на плащане в фискалния принтер
     *  	'amount => сума в основна валута без ддс
     *  );
     *
     *
     * @param  int  $id - ид на бележка
     * @return void
     */
    public function createFile($id)
    {
        return $this->class->createFile($id);
    }
}
