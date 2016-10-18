<?php


/**
 * Тип за параметър 'Изображение'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Изображение
 */
class cond_type_Image extends cond_type_Proto
{
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param stdClass $rec - запис
	 * @return core_Type - готовия тип
	 */
	public function getType($rec)
	{
		$Type = core_Type::getByName('fileman_FileType(bucket=pictures)');
	
		return $Type;
	}
}