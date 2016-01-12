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
	 * @param string $measureName - име на мярка
	 * @return FALSE|int - ид на мярката
	 */
	public function getDefaultUom($measureName = NULL)
	{
		return $this->class->getDefaultUom($measureName);
	}
	
	
	/**
	 * Връща стойността на параметъра с това име, или
	 * всички параметри с техните стойностти
	 * 
	 * @param string $classId - ид на ембедъра
	 * @param string $id   - ид на записа
	 * @param string $name - име на параметъра, или NULL ако искаме всички
	 * @return mixed - стойност или FALSE ако няма
	 */
	public function getParams($classId, $id, $name = NULL)
	{
		return $this->class->getParams($classId, $id, $name);
	}
	
	
	/**
	 * Подготвя данните за показване на описанието на драйвера
	 *
	 * @param stdClass $data
	 * @return void
	 */
	public function prepareProductDescription(&$data)
	{
		return $this->class->prepareProductDescription($data);
	}
	
	
	/**
	 * Рендира данните за показване на артикула
	 * 
	 * @param stdClass $data
	 * @return core_ET
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
		return $this->class->getJobFolderName();
	}
	
	
	/**
     * Връща информация за какви дефолт задачи за производство могат да се създават по артикула
     *
     * @return array $drivers - масив с информация за драйверите, с ключ името на масива
     * 				    -> title        - дефолт име на задачата
     * 					-> driverClass  - драйвър на задача
     * 					-> products     - масив от масиви с продуктите за влагане/произвеждане/отпадане
     * 						 - array input      - материали за влагане
     * 						 - array production - артикули за произвеждане
     * 						 - array waste      - отпадъци
     */
	public function getDefaultTasks()
	{
		return $this->class->getDefaultTasks();
	}
	
	
	/**
	 * Връща иконата на драйвера
	 *
	 * @return string - пътя към иконата
	 */
	public function getIcon()
	{
		return $this->class->getIcon();
	}
	
	
	/**
	 * Рендиране на описанието на драйвера в еденичния изглед на артикула
	 *
	 * @param stdClass $data
	 * @return core_ET $tpl
	 */
	public function renderSingleDescription($data)
	{
		return $this->class->renderSingleDescription($data);
	}
	
	
	/**
	 * Връща дефолтното име на артикула
	 * 
	 * @param stdClass $rec
	 * @return NULL|string
	 */
	public function getProductTitle($rec)
	{
		return $this->class->getProductTitle($rec);
	}
	
	
	/**
	 * Връща данни за дефолтната рецепта за артикула
	 *
	 * @param stdClass $rec - запис
	 * @return FALSE|array
	 * 			['quantity'] - К-во за което е рецептата
	 * 			['expenses'] - % режийни разходи
	 * 			['materials'] array
	 * 				 ['code']         string  - Код на материала
	 * 				 ['baseQuantity'] double  - Начално количество на вложения материал
	 * 				 ['propQuantity'] double  - Пропорционално количество на вложения материал
	 * 				 ['waste']        boolean - Дали материала е отпадък
	 * 				 ['stageName']    string  - Име на производствения етап
	 *
	 */
	public function getDefaultBom($rec)
	{
		return $this->class->getDefaultBom($rec);
	}
}