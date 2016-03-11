<?php


/**
 * Тип за параметър 'Текст'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Текст
 */
class cond_type_Text extends cond_type_Proto
{
	
	
	/**
	 * Кой базов тип наследява
	 */
	protected $baseType = 'type_Text';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('rows', 'int', 'caption=Конкретизиране->Редове,before=default');
	}
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param int $paramId - ид на параметър
	 * @return core_Type - готовия тип
	 */
	public function getType($rec)
	{
		$Type = parent::getType($rec);
		
		if(isset($rec->rows)){
			$Type = cls::get($Type, array('params' => array('rows' => $rec->rows)));
		}
		
		return $Type;
	}
}