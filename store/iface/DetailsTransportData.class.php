<?php



/**
 * Интерфейс транспортна информация в детайлите на складовите документи
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_iface_DetailsTransportData
{
    
	
	/**
	 * Клас имплементиращ интерфейса
	 */
	public $class;
	
	
	/**
	 * Какви са използваните ЛЕ
	 * 
	 * @param stdClass $masterRec - ид на мастъра
	 * @return array              - масив с ле => к-во
	 */
	public function getTransUnits($masterRec)
	{
		return $this->getTransUnits($masterRec);
	}
	
	
	/**
	 * Изчисляване на общото тегло и обем на редовете
	 * 
	 * @param stdClass $masterRec - ид на мастъра
	 * @param boolean $force
	 * @return stdClass $res
	 * 			- weight    - теглото на реда
	 * 			- volume    - теглото на реда
	 * 			- transUnits - транспортнтие еденици
	 */
	public function getTransportInfo($masterId, $force = FALSE)
	{
		return $this->class->getTransportInfo($masterId, $force);
	}
}