<?php

/**
 * Интерфейс за класовете ползващи перманентни данни
 *
 *
 * @category   bgERP 2.0
 * @package    permanent
 * @title:     Интерфейс перманентни данни
 * @author     Димитър Минеков <mitko@extrapack.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class permanent_SettingsIntf
{
    /**
     * Връща ключ под който ще се запишат данните
     */
	function getSettingsKey()
	{
		return $this->class->getSettingsKey();
	}
	
	/**
	 * 
	 * Подготвя празна форма, така че да показва полетата
	 * за настройките на обекта, заедно с текущите данни.
	 * 
	 * @param object $form
	 */
	function prepareSettingsForm($form)
	{
		return $this->class->prepareSettingsForm($form);
	}
	
	/**
	 * 
	 * Извлича данните от формата със заредени от Request данни,
	 * като може да им направи специализирана проверка коректност.
	 * Ако след извикването на този метод $form->getErrors() връща TRUE,
	 * то означава че данните не са коректни.
	 * От формата данните попадат в тази част от вътрешното състояние на обекта,
	 * която определя неговите settings
	 * 
	 * @param object $form
	 */
	function setSettingsFromForm($form)
	{
		return $this->class->setSettingsFromForm($form);
	}
	
	/**
	 * 
	 * Връща текущите настройки на обекта
	 */
	function getSettings()
	{
		$this->class->getSettings();
	}
	
	/**
	 * 
	 * Задава вътрешните настройки на обекта
	 * @param object $data
	 */
	function setSettings($data)
	{
		$this->class->setSettings($data);
	}
}