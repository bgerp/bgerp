<?php


/**
 * Тип за параметър 'Символи'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Символи
 */
class cond_type_Varchar extends cond_type_Proto
{
	
	
	/**
	 * Кой базов тип наследява
	 */
	protected $baseType = 'type_Varchar';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('lenght', 'int', 'caption=Конкретизиране->Дължина,before=default');
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
	
		if(isset($rec->lenght)){
			$Type = cls::get($Type, array('params' => array('size' => $rec->lenght)));
		}
	
		return $Type;
	}
}