<?php

/**
 * Интерфейс за създаване на драйвери на производствени задачи
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за драйвери на производствени задачи
 */
class planning_DriverIntf extends tasks_DriverIntf
{
	
	
	/**
	 * Информация за произведения артикул по задачатаз
	 *
	 * @param stdClass $rec
	 * @return stdClass $arr
	 * 			  o productId       - ид на артикула
	 * 			  o packagingId     - ид на опаковката
	 * 			  o quantityInPack  - количество в опаковка
	 * 			  o plannedQuantity - планирано количество
	 * 			  o wastedQuantity  - бракувано количество
	 * 			  o totalQuantity   - прозведено количество
	 */
	public function getProductDriverInfo($id)
	{
		$this->class->getProductDriverInfo($id);
	}
}