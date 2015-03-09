<?php


/**
 * Клас 'mp_ConsumptionNotes' - Документ за Протокол за производство
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
class mp_ProductionNotes extends deals_ManifactureMaster
{
	
	
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
	public $interfaces = 'acc_TransactionSourceIntf=mp_transaction_ProductionNote';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools, mp_Wrapper, acc_plg_Contable, acc_plg_DocumentSummary,
                    doc_DocumentPlg, plg_Printing, doc_plg_BusinessDoc, plg_Search';
	
	
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
	public $canAdd = 'ceo,mp';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Протокол за производство';
	
	
	/**
	 * Файл за единичния изглед
	 */
	public $singleLayoutFile = 'mp/tpl/SingleLayoutProductionNote.shtml';
	
	 
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "3.6|Производство";
	
	
	/**
	 * Детайл
	 */
	public $details = 'mp_ProductionNoteDetails';
	
	
	/**
	 * Кой е главния детайл
	 * 
	 * @var string - име на клас
	 */
	public $mainDetail = 'mp_ProductionNoteDetails';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		parent::setDocumentFields($this);
	}
	
	
	
	/**
	 * Контиране на счетоводен документ
	 *
	 * @param core_Mvc $mvc
	 * @param mixed $res
	 * @param int|object $id първичен ключ или запис на $mvc
	 */
	public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
	{
		if($mvc->canConto($id) === FALSE){
			core_Statuses::newStatus("Документа не може да бъде контиран, защото някой от артикулите не може да генерира транзакция", 'warning');
			
			return FALSE;
		}
	}
	
	
	/**
	 * Ре-контиране на счетоводен документ
	 *
	 * @param core_Mvc $mvc
	 * @param mixed $res
	 * @param int|object $id първичен ключ или запис на $mvc
	 */
	public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
	{
		if($mvc->canConto($id) === FALSE){
			core_Statuses::newStatus("Документа не може да бъде контиран, защото някой от артикулите не може да генерира транзакция", 'warning');
			
			return FALSE;
		}
	}
	
	
	/**
	 * Можели документа да бъде контиран?
	 */
	private function canConto($id)
	{
		$rec = $this->fetchRec($id);
		
		$dQuery = mp_ProductionNoteDetails::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		
		while($dRec = $dQuery->fetch()){
			if(!mp_ProductionNoteDetails::canContoRec($dRec, $rec)){
				return FALSE;
			}
		}
		
		return TRUE;
	}
}