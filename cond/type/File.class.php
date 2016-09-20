<?php


/**
 * Тип за параметър 'Файл'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Файл
 */
class cond_type_File extends cond_type_Proto
{
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param int $paramId - ид на параметър
	 * @return core_Type - готовия тип
	 */
	public function getType($rec)
	{
		$Type = core_Type::getByName('fileman_FileType(bucket=paramFiles)');
	
		return $Type;
	}
}