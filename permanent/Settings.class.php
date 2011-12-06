<?php
/**
 * Клас за перманентни данни
 *
 *
 * @category   bgERP 2.0
 * @package    permanent
 * @title:     Перманентни данни
 * @author     Димитър Минеков <mitko@extrapack.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class permanent_Settings
{

	/**
	 * 
	 * Извлича перманентните сетинги и ги сетва на обекта.
	 * @param object $object
	 */
	function init($object)
	{
		$key = $object->getSettingsKey();
		$data = permanent_Data::read($key);
		
		$object->setSettings($data);
		
		return $object;
	}

	/**
	 * 
	 * Изтрива перманентните сетинги за обекта.
	 * Извиква се при изтриване на обект ползващ permanent_Data
	 * @param object $object
	 */
	function purge($object)
	{
		$key = $object->getSettingsKey(); 
		permanent_Data::remove($key);
	}
	
	/**
	 * 
	 * Връща URL - входна точка за настройка на данните за този обект.
	 * Ключа в URL-то да бъде декориран с кодировка така,
	 * че да е валиден само за текущата сесия на потребителя.
	 * @param object $object
	 */
	function getUrl($object)
	{
		return array('sens_Sensors', 'Settings', $object->id);
	}
	
	/**
	 * 
	 * Връща линк с подходяща картинка към входната точка за настройка на данните за този обект
	 * @param object $object
	 */
	function getLink($object)
	{
		return ht::createLink("<img width=16 height=16 src=" . sbf('img/16/testing.png') . ">",
								array('sens_Sensors', 'Settings', $object->id)
							);
	}
	
	/**
	 * 
	 * Очаква ключ и хеш.
	 * Ключът е ключа към сетингите на обекта,
	 * а хешът е хеш сума на сесийната "сол" - Mode::getPermanentKey()
	 * и ключа.
	 */
	function act_Ajust()
	{
		
	}
}