<?php


/**
 * Клас 'planning_ProductionNotes' - Документ за Протокол за групово производство
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
 * @deprecated
 */
class planning_ProductionNotes extends planning_ProductionDocument
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_ProductionNotes';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Протоколи за групово производство';
	
	
	/**
	 * Име на документа в бързия бутон за добавяне в папката
	 */
	public $buttonInFolderTitle = 'Произвеждане';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Mpd';
	
	
	/**
	 * Поддържани интерфейси
	 */
	public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_ProductionNote';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, doc_plg_BusinessDoc, plg_Search, bgerp_plg_Blank';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'storeId,note';
	
	
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
	public $canAdd = 'no_one';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Протокол за групово производство';
	
	
	/**
	 * Файл за единичния изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutProductionNote.shtml';
	
	
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
     * 
     * @see plg_Clone
     */
	public $cloneDetails = 'planning_ProductionNoteDetails';
	
	
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
	 * Изпълнява се след създаване на нов запис
	 */
	public static function on_AfterCreate($mvc, $rec)
	{
		// Ако е към задание
		$firstDocumentInThread = doc_Threads::getFirstDocument($rec->threadId);
		if(!isset($firstDocumentInThread)) return;
		if(!$firstDocumentInThread->isInstanceOf('planning_Jobs')) return;
			
		// Добавяме информацията за артикула от заданието
		$originRec = $firstDocumentInThread->rec();
		$productRec = cat_Products::fetch($originRec->productId);
		$toProduce = $originRec->quantity - $originRec->quantityProduced;
		if($toProduce <= 0) return;
			
		// Ако артикула не е производим, не го добавяме
		if($productRec->canManifacture != 'yes') return;
		$bomRec = cat_Products::getLastActiveBom($productRec, 'production');
		if(!$bomRec){
			$bomRec = cat_Products::getLastActiveBom($productRec, 'sales');
		}
			
		// Ако има рецепта, добавяме артикула от заданието като първи детайл
		if($bomRec){
			$dRec = (object)array('noteId'         => $rec->id,
								  'productId'      => $originRec->productId,
								  'quantity'       => $toProduce,
								  'jobId'          => $originRec->id,
								  'packagingId'    => $productRec->measureId,
								  'quantityInPack' => 1,
								  'bomId'          => $bomRec->id,
				);
					
			// Добавяме артикула от заданието в протокола, с количеството оставащо за производство
			planning_ProductionNoteDetails::save($dRec);
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