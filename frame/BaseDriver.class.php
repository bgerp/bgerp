<?php



/**
 * Базов клас за наследяване от другите драйвери
 *
 *
 * @category  bgerp
 * @package   frame
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class frame_BaseDriver extends core_BaseClass
{
	
	
	/**
	 * След активация на репорта
	 */
	public static function on_AfterActivation($mvc, &$is, $innerForm, &$rec)
	{
		$is = $mvc->prepareInnerState($innerForm);
		frame_Reports::save($rec);
	}
	
	
	/**
	 * След оттегляне на репорта
	 */
	public static function on_AfterReject($mvc, &$is, $innerForm, &$rec)
	{
		$is = $mvc->prepareInnerState($innerForm);
		frame_Reports::save($rec);
	}
	
	
	/**
	 * След възстановяване на репорта
	 */
	public static function on_AfterRestore($mvc, &$is, $innerForm, &$rec)
	{
		if($rec->state == 'draft'){
			unset($rec->data);
			frame_Reports::save($rec);
		}
	}
	
	
	/**
	 * Можели вградения обект да се избере
	 */
	public function canSelectInnerObject($userId = NULL)
	{
		return core_Users::haveRole($this->canSelectSource, $userId);
	}


	/**
	 * Подготвя данните необходими за показването на вградения обект
	 *
	 * @param core_Form $innerForm
	 * @param stdClass $innerState
	 */
	public function prepareEmbeddedData_($innerForm, &$innerState)
	{
		// Ако има вътрешно състояние него връщаме
		if(!empty($innerState)){
			return $innerState;
		}
		 
		return $this->prepareInnerState($innerForm);
	}
}