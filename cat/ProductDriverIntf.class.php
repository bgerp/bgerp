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
	 * Връща задължителната основна мярка
	 *
	 * @return int|NULL - ид на мярката, или NULL ако може да е всяка
	 */
	public function getDefaultUomId()
	{
		return NULL;
	}
	
	
	/**
     * Връща стойността на параметъра с това име, или
     * всички параметри с техните стойностти
     *
     * @param int $classId    - ид на клас
     * @param string $id      - ид на записа
     * @param string $name    - име на параметъра, или NULL ако искаме всички
     * @param boolean $verbal - дали да са вербални стойностите
     * @return mixed  $params - стойност или FALSE ако няма
     */
	public function getParams($classId, $id, $name = NULL, $verbal = FALSE)
	{
		return $this->class->getParams($classId, $id, $name, $verbal);
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
     * @param double $quantity - к-во
     * @return array $drivers - масив с информация за драйверите, с ключ името на масива
     * 				    -> title        - дефолт име на задачата
     * 					-> driverClass  - драйвър на задача
     * 					-> products     - масив от масиви с продуктите за влагане/произвеждане/отпадане
     * 						 - array input      - материали за влагане
     * 						 - array production - артикули за произвеждане
     * 						 - array waste      - отпадъци
     */
	public function getDefaultProductionTasks($quantity)
	{
		return $this->class->getDefaultProductionTasks($quantity);
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
	
	
	/**
	 * Връща цената за посочения продукт към посочения клиент на посочената дата
	 *
	 * @param mixed $customerClass - клас на контрагента
	 * @param int $customerId - ид на контрагента
	 * @param int $productId - ид на артикула
	 * @param int $packagingId - ид на опаковка
	 * @param double $quantity - количество
	 * @param datetime $datetime - дата
	 * @param double $rate  - валутен курс
	 * @param enum(yes=Включено,no=Без,separate=Отделно,export=Експорт) $chargeVat - начин на начисляване на ддс
	 * @return double|NULL $price  - цена
	 */
	public function getPrice($customerClass, $customerId, $productId, $packagingId = NULL, $quantity = NULL, $datetime = NULL, $rate = 1, $chargeVat = 'no')
	{
		return $this->class->getPrice($customerClass, $customerId, $productId, $packagingId, $quantity, $datetime, $rate, $chargeVat);
	}
	
	
	/**
	 * Връща дефолтната дефиниция за партида на артикула
	 * Клас имплементиращ интерфейса 'batch_BatchTypeIntf'
	 * 
	 * @param mixed $id - ид или запис на артикул
	 * @return NULL|core_BaseClass - клас за дефиниция на партида
	 */
	public function getDefaultBatchDef($id)
	{
		return $this->class->getDefaultBatchDef($id);
	}
	
	
	/**
	 * ХТМЛ представяне на артикула (img)
	 *
	 * @param int $rec - запис на артикул
	 * @param array $size - размер на картинката
	 * @param array $maxSize - макс размер на картинката
	 * @return string|NULL $preview - хтмл представянето
	 */
	public function getPreview($rec, $size = array('280', '150'), $maxSize = array('550', '550'))
	{
		return $this->class->getPreview($rec, $size, $maxSize);
	}
	
	
	/**
	 * Добавя полетата на задачата за производство на артикула
	 *
	 * @param int $id                 - ид на артикул
	 * @param core_Fieldset $fieldset - форма на задание
	 */
	public function addTaskFields($id, core_Fieldset &$fieldset)
	{
		return $this->class->addTaskFields($id, $fieldset);
	}
	
	
	/**
	 * Метод позволяващ на артикула да добавя бутони към rowtools-а на документ
	 *
	 * @param int $id - ид на артикул
	 * @param core_RowToolbar $toolbar - тулбара
	 * @param mixed $docClass - класа документа
	 * @param int $docId - ид на документа
	 * @return void
	 */
	public function addButtonsToDocToolbar($id, core_RowToolbar &$toolbar, $docClass, $docId)
	{
		return $this->class->addButtonsToDocToolbar($id, $toolbar, $docClass, $docId);
	}
}