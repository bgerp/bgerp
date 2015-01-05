<?php


/**
 * Клас 'mp_ConsumptionNotes' - Документ за Протокол за влагане
 *
 * 
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_ConsumptionNotes extends deals_ManifactureMaster
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Протоколи за влагане';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Mcn';
	
	
	/**
	 * Поддържани интерфейси
	 */
	public $interfaces = 'acc_TransactionSourceIntf=mp_transaction_ConsumptionNote';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools, mp_Wrapper, plg_Printing, acc_plg_Contable, acc_plg_DocumentSummary,
                    doc_DocumentPlg, doc_plg_BusinessDoc, plg_Search';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canRead = 'ceo,mp';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,mp';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,mp';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'ceo,mp';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'debug';//@TODO временно ceo,mo
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Протокол за влагане';
	
	
	/**
	 * Файл за единичния изглед
	 */
	public $singleLayoutFile = 'mp/tpl/SingleLayoutConsumptionNote.shtml';
	
	 
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "3.5|Производство";
	
	
	/**
	 * Детайл
	 */
	public $details = 'mp_ConsumptionNoteDetails';
	
	
	/**
	 * Кой е главния детайл
	 * 
	 * @var string - име на клас
	 */
	public $mainDetail = 'mp_ConsumptionNoteDetails';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		parent::setDocumentFields($this);
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 *
	 * @param core_Mvc $mvc
	 * @param string $requiredRoles
	 * @param string $action
	 * @param stdClass $rec
	 * @param int $userId
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($requiredRoles == 'no_one') return;
		
		if(($action == 'conto' || $action == 'activate') && isset($rec)){
			$dQuery = mp_ConsumptionNoteDetails::getQuery();
			$dQuery->where("#noteId = {$rec->id}");
			
			if(!$dQuery->count()) {
				$requiredRoles = 'no_one';
			} else {
				while($dRec = $dQuery->fetch()){
					if(!mp_ObjectResources::getResource($dRec->classId, $dRec->productId)){
						$requiredRoles = 'no_one';
						break;
					}
				}
			}
		}
	}
}