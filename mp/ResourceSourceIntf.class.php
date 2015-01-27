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
	
	
	/**
	 * Връща дефолт информация от източника на ресурса
	 *
	 * @param int $id - ид на обекта
	 * @return stdClass $res  - обект с информация
	 * 		o $res->name      - име
	 * 		o $res->measureId - име мярка на ресурса (@see cat_UoM)
	 * 		o $res->type      -  тип на ресурса (material,labor,equipment)
	 */
	public function getResourceSourceInfo($id)
	{
		$this->class->getResourceSourceInfo($id);
	}
}