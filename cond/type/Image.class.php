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
class cond_type_Image extends cond_type_abstract_Proto
{
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param stdClass $rec      - запис на параметъра
	 * @param mixed $domainClass - клас на домейна
	 * @param mixed $domainId    - ид на домейна
	 * @param NULL|string $value - стойност
	 * @return core_Type         - готовия тип
	 */
	public function getType($rec, $domainClass = NULL, $domainId = NULL, $value = NULL)
	{
		$Type = core_Type::getByName('fileman_FileType(bucket=pictures)');
	
		return $Type;
	}
}