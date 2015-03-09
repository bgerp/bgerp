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
}