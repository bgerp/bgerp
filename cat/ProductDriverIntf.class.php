<?php

/**
 * Интерфейс за създаване на отчети от различни източници в системата
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_ProductDriverIntf extends embed_DriverIntf
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
	 * Кои опаковки поддържа продукта
	 * 
	 * @param array $metas - кои са дефолтните мета данни от ембедъра
	 * @return array $metas - кои са дефолтните мета данни
	 */
	public function getDefaultMetas($metas)
	{
		return $this->class->getDefaultMetas($metas);
	}
	
	
	/**
	 * Връща счетоводните свойства на обекта
	 */
	public function getFeatures()
	{
		return $this->class->getFeatures();
	}
	
	
	/**
	 * Кои документи са използвани в полетата на драйвера
	 */
	public function getUsedDocs()
	{
		return $this->class->getUsedDocs();
	}
	
	
	/**
	 * Връща дефолтната основна мярка, специфична за технолога
	 *
	 * @param int $measureId - мярка
	 * @return int - ид на мярката
	 */
	public function getDefaultUom($measureId = NULL)
	{
		return $this->class->getDefaultUom($measureId);
	}
	
	
	/**
	 * Връща стойността на параметъра с това име
	 * 
	 * @param string $name - име на параметъра
	 * @param string $id   - ид на записа
	 * @return mixed - стойност или FALSE ако няма
	 */
	public function getParamValue($name, $id)
	{
		return $this->class->getParamValue($name, $id);
	}
	
	
	/**
	 * Подготвя данните за показване на описанието на драйвера
	 *
	 * @param stdClass $rec - запис
	 * @param enum(public,internal) $documentType - публичен или външен е документа за който ще се кешира изгледа
	 * @return stdClass - подготвените данни за описанието
	 */
	public function prepareProductDescription($rec, $documentType = 'public')
	{
		return $this->class->prepareProductDescription($rec, $documentType);
	}
	
	
	/**
	 * Рендира данните за показване на артикула
	 */
	public function renderProductDescription($data)
	{
		return $this->class->renderProductDescription($data);
	}
	
	
	/**
	 * Как да се казва дефолт папката където ще отиват заданията за артикулите с този драйвер
	 */
	public function getJobFolderName()
	{
		return $this->getJobFolderName();
	}
	
	
	/**
	 * Връща информация за какви дефолт задачи могат да се задават към заданието за производство
	 *
	 * @return array $drivers - масив с информация за драйверите, с ключ името на масива
	 * 				    -> title    - дефолт име на задачата
	 * 					-> driver   - драйвър на задача
	 * 					-> priority - приоритет (low=Нисък, normal=Нормален, high=Висок, critical)
	 */
	public function getDefaultJobTasks()
	{
		return $this->getDefaultJobTasks();
	}
	
	
	/**
	 * Връща иконата на драйвера
	 *
	 * @return string - пътя към иконата
	 */
	public function getIcon()
	{
		return $this->getIcon();
	}
}