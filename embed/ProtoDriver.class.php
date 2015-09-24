<?php



/**
 * Базов драйвер за наследяване на други драйвери.
 * Трябва да имплементира интерфейс наследяващ 'embed_DriverIntf'
 *
 *
 * @category  bgerp
 * @package   embed
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class embed_ProtoDriver extends core_BaseClass
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'user';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		
	}
	
	
	/**
	 * Кой може да избере драйвера
	 */
	public function canSelectDriver($userId = NULL)
	{
		return core_Users::haveRole($this->canSelectDriver, $userId);
	}
}