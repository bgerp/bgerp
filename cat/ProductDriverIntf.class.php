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
	public $EmbedderRec;
	
	
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
	 * Коя е основната мярка на драйвера
	 */
	public function getDriverUom($params = array())
	{
		return $this->class->getDriverUom($params);
	}
	
	
	/**
	 * Задава параметрите на обекта
	 *
	 * @param mixed $params
	 */
	public function setDriverParams($params)
	{
		return $this->class->setDriverParams($params);
	}
	
	
	/**
	 * Връща параметрите на артикула
	 * @param mixed $id - ид или запис на артикул
	 *
	 * @return array $res - параметрите на артикула
	 * 					['weight']          -  Тегло
	 * 					['width']           -  Широчина
	 * 					['volume']          -  Обем
	 * 					['thickness']       -  Дебелина
	 * 					['length']          -  Дължина
	 * 					['height']          -  Височина
	 * 					['tolerance']       -  Толеранс
	 * 					['transportWeight'] -  Транспортно тегло
	 * 					['transportVolume'] -  Транспортен обем
	 * 					['term']            -  Срок
	 */
	public function getParams()
	{
		return $this->class->getParams();
	}
	
	
	/**
	 * Връща параметрите на драйвера
	 */
	public function getDriverParams()
	{
		return $this->class->getDriverParams();
	}
	
	
	/**
	 * Връща хендлъра на изображението представящо артикула, ако има такова
	 *
	 * @param mixed $id - ид или запис
	 * @return fileman_FileType $hnd - файлов хендлър на изображението
	 */
	public static function getProductImage($id)
	{
		return $this->class->getProductImage($id);
	}
	
	
	/**
	 * Подготвя данните за показване на описанието на драйвера
	 * 
	 * @param enum(public,internal) $documentType - публичен или външен е документа за който ще се кешира изгледа
	 */
	public function prepareProductDescription($documentType = 'public')
	{
		return $this->class->prepareProductDescription($documentType);
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
}