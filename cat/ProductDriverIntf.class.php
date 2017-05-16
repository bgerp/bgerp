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
	 * Връща свойствата на артикула според драйвера
	 * 
	 * @return array $metas - кои са дефолтните мета данни
	 */
	public function getDefaultMetas()
	{
		return $this->class->getDefaultMetas();
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
	 * @param mixed $productId     - ид на артикул
	 * @param int $quantity        - к-во
	 * @param double $minDelta     - минималната отстъпка
	 * @param double $maxDelta     - максималната надценка
	 * @param datetime $datetime   - дата
	 * @param double $rate  - валутен курс
     * @param enum(yes=Включено,no=Без,separate=Отделно,export=Експорт) $chargeVat - начин на начисляване на ддс
	 * @return double|NULL $price  - цена
	 */
	public function getPrice($productId, $quantity, $minDelta, $maxDelta, $datetime = NULL, $rate = 1, $chargeVat = 'no')
	{
		return $this->class->getPrice($productId, $quantity, $minDelta, $maxDelta, $datetime, $rate, $chargeVat);
	}
	
	
	/**
	 * Може ли драйвера автоматично да си изчисли себестойността
	 * 
	 * @param mixed $productId - запис или ид
	 * @return boolean
	 */
	public function canAutoCalcPrimeCost($productId)
	{
		return $this->class->canAutoCalcPrimeCost($productId);
	}
	
	
	/**
	 * Връща дефолтната дефиниция за шаблон на партидна дефиниция
	 * 
	 * @param mixed $id - ид или запис на артикул
	 * @return int - ид към batch_Templates
	 */
	public function getDefaultBatchTemplate($id)
	{
		return $this->class->getDefaultBatchTemplate($id);
	}
	
	
	/**
	 * ХТМЛ представяне на артикула (img)
	 *
	 * @param int $rec - запис на артикул
	 * @param array $size - размер на картинката
	 * @param array $maxSize - макс размер на картинката
	 * @param embed_Manager $Embedder
	 * @return string|NULL $preview - хтмл представянето
	 */
	public function getPreview($rec, embed_Manager $Embedder, $size = array('280', '150'), $maxSize = array('550', '550'))
	{
		return $this->class->getPreview($rec, $Embedder, $size, $maxSize);
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
	 * @param mixed $detailClass - класа на детаила в документа
	 * @param int $detailId - ид на реда от документа
	 * @return void
	 */
	public function addButtonsToDocToolbar($id, core_RowToolbar &$toolbar, $detailClass, $detailId)
	{
		return $this->class->addButtonsToDocToolbar($id, $toolbar, $detailClass, $detailId);
	}
	
	
	/**
	 * Връща минималното количество за поръчка
	 * 
	 * @param int|NULL $id - ид на артикул
	 * @return double|NULL - минималното количество в основна мярка, или NULL ако няма
	 */
	public static function getMoq($id = NULL)
	{
		return $this->class->getMoq($id);
	}
	
	
	/**
	 * Връща дефолтните опаковки за артикула
	 *
	 * @param mixed $rec - запис на артикула
	 * @return array     - масив с дефолтни опаковки
	 * 		o packagingId - ид на мярка/опаковка
	 * 		o quantity    - к-во в опаковката
	 */
	public static function getDefaultPackagings($rec)
	{
		return $this->class->getDefaultPackagings($rec);
	}
	
	
	/**
     * Допълнителните условия за дадения продукт,
     * които автоматично се добавят към условията на договора
     *
     * @param mixed $rec       - ид или запис на артикул
     * @param double $quantity - к-во
     * @return array           - Допълнителните условия за дадения продукт
     */
	public function getConditions($rec, $quantity)
	{
		return $this->class->getConditions($rec, $quantity);
	}
	
	
	/**
	 * Връща хеша на артикула (стойност която показва дали е уникален)
	 *
	 * @param embed_Manager $Embedder - Ембедър
	 * @param mixed $rec              - Ид или запис на артикул
	 * @return NULL|varchar           - Допълнителните условия за дадения продукт
	 */
	public function getHash(embed_Manager $Embedder, $rec)
	{
		return $this->class->getHash($Embedder, $rec);
	}
	
	
	/**
	 * Връща масив с допълнителните плейсхолдъри при печат на етикети
	 *
	 * @param mixed $rec              - ид или запис на артикул
	 * @param mixed $labelSourceClass - клас източник на етикета
	 * @return array                  - Допълнителните полета при печат на етикети
	 * 		[Плейсхолдър] => [Стойност]
	 */
	public function getAdditionalLabelData($rec, $labelSourceClass = NULL)
	{
		return $this->class->getAdditionalLabelData($rec, $labelSourceClass);
	}
}