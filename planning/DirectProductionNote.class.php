<?php


/**
 * Клас 'planning_DirectProductionNote' - Документ за бързо производство
 *
 * 
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_DirectProductionNote extends deals_ManifactureMaster
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Протоколи за бързо производство';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Mpd';
	
	
	/**
	 * Поддържани интерфейси
	 */
	public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_DirectProductionNote';
	
	
	/**
	 * Плъгини за зареждане
	 * 
	 * , acc_plg_Contable
	 */
	public $loadList = 'plg_RowTools, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, doc_plg_BusinessDoc, plg_Search';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canConto = 'ceo,planning';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canRead = 'ceo,planning';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,planning';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,planning';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'ceo,planning';
	
	
	/**
	 * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
	 */
	public $rowToolsField = 'tools';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'ceo,planning';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Протокол за бързо производство';
	
	
	/**
	 * Файл за единичния изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutDirectProductionNote.shtml';
	
	
	/**
	 * Детайл
	 */
	public $details = 'planning_DirectProductNoteDetails';
	
	
	/**
	 * Кой е главния детайл
	 * 
	 * @var string - име на клас
	 */
	public $mainDetail = 'planning_DirectProductNoteDetails';
	
	
	/**
	 * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
	 * (@see plg_Clone)
	 */
	public $cloneDetailes = 'planning_DirectProductNoteDetails';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'title';
	
	
	/**
	 * Икона на единичния изглед
	 */
	public $singleIcon = 'img/16/page_paste.png';
	
	
	/**
	 * Опашка със заданията на които ще инвалидираме, кешираната информация
	 */
	protected $invalidateJobsCache = array();
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		parent::setDocumentFields($this);
		
		$this->setField('deadline', 'input=none');
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,after=storeId');
		$this->FLD('jobQuantity', 'double(smartRound)', 'caption=Задание,input=hidden,mandatory,after=productId');
		$this->FLD('quantity', 'double(smartRound,Min=0)', 'caption=За,mandatory,after=jobQuantity');
		$this->FLD('expenses', 'percent', 'caption=Режийни разходи,after=quantity');
		
		$this->setDbIndex('productId');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$originRec = doc_Containers::getDocument($form->rec->originId)->rec();
		$form->setReadOnly('productId', $originRec->productId);
		$shortUom = cat_UoM::getShortName(cat_Products::fetchField($originRec->productId, 'measureId'));
		$form->setField('quantity', "unit={$shortUom}");
		
		$quantity = $originRec->quantity - $originRec->quantityProduced;
		$form->setDefault('jobQuantity', $originRec->quantity);
		
		if($quantity > 0){
			$form->setDefault('quantity', $quantity);
		}
		
		$bomRec = cat_Products::getLastActiveBom($originRec->productId);
		if(isset($bomRec->expenses)){
			$form->setDefault('expenses', $bomRec->expenses);
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->productId = cat_Products::getShortHyperlink($rec->productId);
		$shortUom = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
		$row->quantity .= " {$shortUom}";
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'add'){
			if(isset($rec)){
				
				// Трябва да има ориджин
				if(empty($rec->originId)){
					$requiredRoles = 'no_one';
				} else {
					
					// Ориджина трябва да е задание за производство
					$originDoc = doc_Containers::getDocument($rec->originId);
					if(!($originDoc->getInstance() instanceof planning_Jobs)){
						$requiredRoles = 'no_one';
					} else {
						
						// Което не е чернова или оттеглено
						$state = $originDoc->fetchField('state');
						if($state == 'rejected' || $state == 'draft'){
							$requiredRoles = 'no_one';
						}
					}
				}
			}
		}
	}
	
	
	/**
	 * Извиква се след подготовката на toolbar-а за табличния изглед
	 */
	protected static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		$data->toolbar->removeBtn('btnAdd');
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	public static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = &$form->rec;
		if($form->isSubmitted()){
			
			// Ако могат да се генерират детайли от артикула да се
			$details = $mvc->getDefaultDetails($rec->productId, $rec->storeId, $rec->quantity, $rec->jobQuantity);
			
			if($details === FALSE){
				$form->setWarning('productId', 'Няма да могат да се генерират детайли от рецептата, защото на материал от нея не е обвързан с артикул');
			}
		}
	}
	
	
	/**
	 * Изпълнява се след създаване на нов запис
	 */
	public static function on_AfterCreate($mvc, $rec)
	{
		// Ако могат да се генерират детайли от артикула да се
		$details = $mvc->getDefaultDetails($rec->productId, $rec->storeId, $rec->quantity, $rec->jobQuantity);
		
		if($details !== FALSE){
			
			// Ако могат да бъдат определени дефолт детайли според артикула, записваме ги
			if(count($details)){
				foreach ($details as $dRec){
					$dRec->noteId = $rec->id;
					planning_DirectProductNoteDetails::save($dRec);
				}
			}
		}
	}
	
	
	/**
	 * Връща дефолт детайлите на документа, които съотвестват на ресурсите
	 * в последната активна рецепта за артикула
	 * 
	 * @param int $productId       - ид на артикул
	 * @param int $storeId         - ид на склад
	 * @param double $prodQuantity - количество за произвеждане
	 * @param double $jobQuantity  - количество от заданието
	 * @return array $details      - масив с дефолтните детайли
	 */
	protected function getDefaultDetails($productId, $storeId, $prodQuantity, $jobQuantity)
	{
		$details = array();
		
		// Ако артикула има активна рецепта
		$bomId = cat_Products::getLastActiveBom($productId)->id;
		
		// Ако ням рецепта, не могат да се определят дефолт детайли за влагане
		if(!$bomId) return $details;
		
		// Извличаме информацията за ресурсите в рецептата
		$bomInfo = cat_Boms::getResourceInfo($bomId);
		$productManId = cat_Products::getClassId();
		
		// За всеки ресурс
		foreach($bomInfo['resources'] as $resource){
			
			// Задаваме данните на ресурса
			$dRec = new stdClass();
			$dRec->resourceId = $resource->resourceId;
			$dRec->type = $resource->type;
			$dRec->quantityInPack = 1;
			
			// Мярката е мярката на ресурса
			$dRec->measureId = planning_Resources::fetchField($resource->resourceId, 'measureId');
			$type = planning_Resources::fetchField($resource->resourceId, 'type');
			
			// Изчисляваме к-то според наличните данни
			$dRec->quantity = $prodQuantity * ($resource->baseQuantity / $jobQuantity + ($resource->propQuantity / $bomInfo['quantity']));
			
			// Намираме всички артикули материали свързани с този ресурс
			$materialsArr = planning_ObjectResources::fetchRecsByClassAndType($resource->resourceId, $productManId, 'material');
			
			// Извличаме наличното количество в избрания клас за всеки от ресурсите
			$allProducts = $resProducts = array();
			if(count($materialsArr)){
				foreach ($materialsArr as $objRec){
					$quantity = store_Products::fetchField("#classId = {$objRec->classId} AND #productId = {$objRec->objectId} AND #storeId = {$storeId}", 'quantity');
					$resProducts[$objRec->objectId] = (isset($quantity)) ? $quantity : 0;
					$allProducts[$objRec->objectId] = $objRec;
				}
			}
			
			// Намираме този с най-голямо налично количество
			$productId = NULL;
			if(count($resProducts)) {
				$productId = array_search(max($resProducts), $resProducts);
			}
			
			if($type == 'material' && !$productId){
				
				// Ако има ресурс материал и не може да му се определи артикул, не продължаваме
				return FALSE;
			}
			
			// Избираме него към ресурса
			if($productId){
				$dRec->classId = $productManId;
				$dRec->productId = $productId;
				$dRec->measureId = cat_Products::fetchField($productId, 'measureId');
				
				// К-то на ресурса го умножаваме по конверсията на артикула към ресурса
				$dRec->quantity *= $allProducts[$productId]->conversionRate;
			}
			
			// Добавяме детайла
			$details[] = $dRec;
		}
		
		// Връщаме генерираните детайли
		return $details;
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 *
	 * @param core_Mvc $mvc
	 * @param int $id първичния ключ на направения запис
	 * @param stdClass $rec всички полета, които току-що са били записани
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
	{
		// При активиране/оттегляне
		if($rec->state == 'active' || $rec->state == 'rejected'){
			$origin = doc_Containers::getDocument($rec->originId);
			planning_Jobs::updateProducedQuantity($origin->that);
		}
	}
}