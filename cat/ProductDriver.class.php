<?php

/**
 * Базов драйвер за драйвер на артикул
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Базов драйвер за драйвер на артикул
 */
abstract class cat_ProductDriver extends core_BaseClass
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno2_SpecificationDriver';
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectSource = 'ceo, cat, techno';
	
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'cat_ProductDriverIntf';
	
	
	/**
	 * Вътрешната форма
	 *
	 * @param mixed $innerForm
	 */
	protected $innerForm;
	
	
	/**
	 * Вътрешното състояние
	 *
	 * @param mixed $innerState
	 */
	protected $innerState;

	
	/**
	 * Мета данни по подразбиране
	 * 
	 * @param strint $defaultMetaData
	 */
	protected $defaultMetaData;
	
	
	/**
	 * Параметри
	 *
	 * @param array $driverParams
	 */
	protected $driverParams;
	
	
	/**
	 * Задава параметрите на обекта
	 *
	 * @param mixed $innerForm
	 */
	public function setParams($params)
	{
		$this->driverParams = $params;
	}
	
	
	/**
	 * Задава вътрешната форма
	 *
	 * @param mixed $innerForm
	 */
	public function setInnerForm($innerForm)
	{
		$this->innerForm = $innerForm;
	}
	
	
	/**
	 * Задава вътрешното състояние
	 *
	 * @param mixed $innerState
	 */
	public function setInnerState($innerState)
	{
		$this->innerState = $innerState;
	}
	
	
	/**
	 * Подготвя формата за въвеждане на данни за вътрешния обект
	 *
	 * @param core_Form $form
	 */
	public function prepareEmbeddedForm(core_Form &$form)
	{
	
	}
	
	
	/**
	 * Проверява въведените данни
	 *
	 * @param core_Form $form
	 */
	public function checkEmbeddedForm(core_Form &$form)
	{
	
	}
	
	
	/**
	 * Подготвя вътрешното състояние, на база въведените данни
	 *
	 * @param core_Form $innerForm
	 */
	public function prepareInnerState()
	{
	
	}


	/**
	 * Можели вградения обект да се избере
	 */
	public function canSelectInnerObject($userId = NULL)
	{
		return core_Users::haveRole($this->canSelectSource, $userId);
	}


	/**
	 * Преди запис
	 */
	public static function on_BeforeSave($mvc, &$is, $filter, $rec)
	{
		if(isset($filter)){
			$is = is_object($filter) ? clone $filter : $filter;
		}
	}
	
	
	/**
	 * Променя ключовите думи от мениджъра
	 */
	public function alterSearchKeywords(&$searchKeywords)
	{
		
	}
	
	
	/**
	 * Кои документи са използвани в полетата на драйвера
	 */
	public function getUsedDocs()
	{
		return FALSE;
	}


	/**
	 * Кои опаковки поддържа продукта
	 */
	public function getDefaultMetas()
	{
		return !empty($this->defaultMetaData) ? arr::make($this->defaultMetaData, TRUE) : array();
	}
	
	
	/**
	 * Връща основната мярка, специфична за технолога
	 */
	public function getDriverUom($params)
	{
		if(empty($params['uom'])){
			 
			return cat_UoM::fetchBySysId('pcs')->id;
		}
	
		return cat_UoM::fetchBySinonim($params['uom']);
	}
}