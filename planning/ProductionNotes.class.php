<?php


/**
 * Клас 'planning_ConsumptionNotes' - Документ за Протокол за производство
 *
 * 
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_ProductionNotes extends deals_ManifactureMaster
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_ProductionNotes';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Протоколи за производство';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Mpn';
	
	
	/**
	 * Поддържани интерфейси
	 */
	public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_ProductionNote';
	
	
	/**
	 * Плъгини за зареждане
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
	public $singleTitle = 'Протокол за производство';
	
	
	/**
	 * Файл за единичния изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutProductionNote.shtml';
	
	 
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "3.6|Производство";
	
	
	/**
	 * Детайл
	 */
	public $details = 'planning_ProductionNoteDetails';
	
	
	/**
	 * Кой е главния детайл
	 * 
	 * @var string - име на клас
	 */
	public $mainDetail = 'planning_ProductionNoteDetails';
	
	
	/**
	 * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
	 * (@see plg_Clone)
	 */
	public $cloneDetailes = 'planning_ProductionNoteDetails';
	
	
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
		
		if($form->rec->originId){
			
			// Ако се създава към задание
			$originRec = doc_Containers::getDocument($form->rec->originId)->rec();
			$bomRec = cat_Products::getLastActiveBom($originRec->productId);
			if(!$bomRec){
				$title = cat_Products::getTitleById($originRec->productId);
				$caption = str_replace(',', '.', $title);
				
				// И няма рецепта, показваме полето за себестойност
				$form->FNC('selfValue', 'double', "input,after=note,mandatory,caption=|Производство на|* {$caption}->|Ед. ст-ст|*");
			} else {
				$form->rec->bomId = $bomRec->id;
			}
		}
	}
	
	
	/**
	 * Изпълнява се след създаване на нов запис
	 */
	public static function on_AfterCreate($mvc, $rec)
	{
		// Ако е към задания
		if($rec->originId){
			
			// Добавяме информацията за артикула от заданието
			$originRec = doc_Containers::getDocument($rec->originId)->rec();
			$Products = cls::get('cat_Products');
			$pInfo = $Products->getProductInfo($originRec->productId);
			
			// Ако артикула не е производим, не го добавяме
			if(empty($pInfo->meta['canManifacture'])) return;
			
			$dRec = (object)array('noteId'    => $rec->id, 
								  'productId' => $originRec->productId, 
								  'quantity'  => $originRec->quantity, 
								  'jobId'     => $originRec->id,
								  'measureId' => $Products->fetchField($originRec->productId, 'measureId'),
								  'classId'   => $Products->getClassId(),
								  'selfValue' => $rec->selfValue,
								  'bomId'     => $rec->bomId,
								);
			
			// Запис на детайла
			planning_ProductionNoteDetails::save($dRec);
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'add' && isset($rec->originId)){
			
			// Ако добавяме към източник, трябва да е не оттеглено и чернова задание
			$origin = doc_Containers::getDocument($rec->originId);
			if(!($origin->instance() instanceof planning_Jobs)){
				$requiredRoles = 'no_one';
			}
			
			$state = $origin->fetchField('state');
			if($state == 'rejected' || $state == 'draft'){
				$requiredRoles = 'no_one';
			}
		}
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
			
			// Обикаляме детайла на произведените артикули
			$dQuery = planning_ProductionNoteDetails::getQuery();
			$dQuery->where("#noteId = {$rec->id}");
			
			// Запомняме заданията по които са произведени, че трябва кешираните им данни да се инвалидират
			while($dRec = $dQuery->fetch()){
				if($dRec->jobId){
					$mvc->invalidateJobsCache[$dRec->jobId] = $dRec->jobId;
				}
			}
		}
	}
	
	
	/**
	 * Изчиства записите, заопашени за запис
	 */
	public static function on_Shutdown($mvc)
	{
		if(count($mvc->invalidateJobsCache)){
			foreach ($mvc->invalidateJobsCache as $jobId){
				
				// Във заопашените задания обновяваме произведените артикули
				planning_Jobs::updateProducedQuantity($jobId);
			}
		}
	}
}