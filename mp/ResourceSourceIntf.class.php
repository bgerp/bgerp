<?php



/**
 * Интерфейс за източници на ресурси
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за източници на ресурси
 */
class mp_ResourceSourceIntf
{
	
	
	/**
	 * Клас имплементиращ интерфейса
	 */
	protected $class;
	
	
	/**
	 * Можели обекта да се добави като ресурс?
	 *
	 * @param int $id - ид на обекта
	 * @return boolean - TRUE/FALSE
	 */
	public function canHaveResource($id)
	{
		$this->class->canHaveResource($id);
	}
}