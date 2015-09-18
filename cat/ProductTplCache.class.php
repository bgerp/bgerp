<?php

/**
 * Кеш на изгледа на частните артикули
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_ProductTplCache extends core_Master
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno2_SpecTplCache';
	
	
	/**
	 * Необходими плъгини
	 */
	public $loadList = 'plg_RowTools, cat_Wrapper';
	 
	
	/**
	 * Заглавие на мениджъра
	 */
	public $title = "Кеш на изгледа на артикулите";
	
	
	/**
	 * Права за писане
	 */
	public $canWrite = 'no_one';
	
	
	/**
	 * Права за запис
	 */
	public $canRead = 'ceo, cat';
	
	
	/**
	 * Права за запис
	 */
	public $canDelete = 'ceo, cat';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, cat';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, cat';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'id, productId, time';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'productId';
	
	
	/**
	 * Файл с шаблон за единичен изглед на статия
	 */
	public $singleLayoutFile = 'cat/tpl/SingleLayoutTplCache.shtml';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD("productId", "key(mvc=cat_Products,select=name)", "input=none,caption=Артикул");
		$this->FLD("cache", "blob(1000000, serialize, compress)", "input=none,caption=Html,column=none");
		$this->FLD("time", "datetime", "input=none,caption=Дата");
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if(isset($fields['-single'])){
			$Driver = cls::get('cat_Products')->getDriver($rec->productId);
			$row->cache = $Driver->renderProductDescription($rec->cache);
		}
	}


	/**
	 * Подготовка на филтър формата
	 */
	public static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->listFilter->FLD("docId", "key(mvc=cat_Products,select=name,allowEmpty)", "input,caption=Артикул");
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->view = 'horizontal';
		$data->listFilter->showFields = 'docId';
		
		$data->listFilter->input(NULL, 'silent');
		
		if(isset($data->listFilter->rec->docId)){
			$data->query->where("#productId = '{$data->listFilter->rec->docId}'");
		}
	}
	
	
	/**
	 * След подготовка на туклбара на списъчния изглед
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if(haveRole('admin,debug,ceo')){
			$data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искатели да изчистите таблицата,ef_icon=img/16/sport_shuttlecock.png');
		}
	}
	
	
	/**
	 * Изчиства записите в балансите
	 */
	public function act_Truncate()
	{
		requireRole('admin,debug,ceo');
		 
		// Изчистваме записите от моделите
		self::truncate();
		 
		// Записваме, че потребителя е разглеждал този списък
		$this->logInfo("Изтриване на кеша на изгледите на артикула");
		
		Redirect(array($this, 'list'), FALSE, 'Записите са изчистени успешно');
	}
	
	
	/**
	 * Кеширане на изгледа на спецификацията
	 *
	 * @param mixed $id - ид/запис
	 * @param datetime $time - време
	 * @param enum(public,internal) $documentType - публичен или външен е документа за който ще се кешира изгледа
	 * @return core_ET - кеширания шаблон
	 */
	public static function cacheTpl($productId, $time, $documentType = 'public')
	{
		$pRec = cat_Products::fetchRec($productId);
		$cache = self::fetchField("#productId = {$pRec->id} AND #time = '{$time}'", 'cache');
		$Driver = cls::get('cat_Products')->getDriver($productId);
		
		// Ако има кеширан изглед за тази дата връщаме го
		if(!$cache){
	
			// Ако няма генерираме наново и го кешираме
			$cacheRec = new stdClass();
			$cacheRec->time = $time;
			$cacheRec->productId = $productId;
			$cacheRec->cache = $Driver->prepareProductDescription($pRec, $documentType);
			self::save($cacheRec);
	
			$cache = $cacheRec->cache;
		}
		
		if($Driver){
			$tpl = $Driver->renderProductDescription($cache);
			$tpl->removeBlocks();
		} else {
			$tpl = new ET(tr("<span class='red'>|Проблем с показването|*</span>"));
		}
		
		// Връщаме намерения изглед
		return $tpl;
	}
}