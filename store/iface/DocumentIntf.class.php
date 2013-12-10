<?php



/**
 * Интерфейс за сладови документи
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_iface_DocumentIntf
{
    
    
    /**
	 * Изчислява обема и теглото на продуктите в документа
	 * @param array $products - продуктите в документа
	 */
	public function getMeasures($products)
	{
		$this->class->getMeasures($products);
	}
}