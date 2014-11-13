<?php

/**
 * Интерфейс за създаване на отчети от различни източници в системата
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_ProductDriverIntf extends core_InnerObjectIntf
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno2_SpecificationDriverIntf';
	
	
	/**
	 * Инстанция на класа имплементиращ интерфейса
	 */
	public $class;
	
	
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
	 * В кой документ е инстанциран драйвера
	 *
	 * @param core_ObjectReference
	 */
	public $ProductRec;
	
	
	/**
	 * Рендиране на параметрите
	 *
	 * @param данни за параметрите $paramData
	 * @param core_ET $tpl - шаблон
	 */
	public function renderParams($paramData, &$tpl, $short = FALSE)
	{
		return $this->class->renderParams($paramData, $tpl, $short);
	}
	
	
	/**
	 * Връща информацията за продукта от драйвера
	 *
	 * @param stdClass $innerState
	 * @param int $packagingId
	 * @return stdClass $res
	 */
	public function getProductInfo($innerState, $packagingId = NULL)
	{
		return $this->class->getProductInfo($innerState, $packagingId);
	}
	
	
	/**
	 * Кои опаковки поддържа продукта
	 */
	public function getPacks($innerState)
	{
		return $this->class->getPacks($innerState);
	}
	
	
	/**
	 * Как да се рендира изгледа в друг документ
	 *
	 * @param stdClass $data - дата
	 * @return core_ET $tpl - шаблон
	 */
	public function renderDescription($data)
	{
		return $this->class->renderDescription($data);
	}
	
	
	/**
	 * Връща масив с мета данните които ще се форсират на продукта
	 */
	public function getDefaultMetas($innerState)
	{
		return $this->class->getDefaultMetas($innerState);
	}
	
	
	/**
	 * Връща счетоводните свойства на обекта
	 */
	public function getFeatures()
	{
		return $this->class->getFeatures();
	}
	
	
	/**
	 * Връща стойността на продукта отговаряща на параметъра
	 *
	 * @param string $sysId - систем ид на параметър (@see cat_Params)
	 * @return mixed - стойността на параметъра за продукта
	 */
	public function getParamValue($sysId)
	{
		return $this->class->getParamValue($sysId);
	}
}