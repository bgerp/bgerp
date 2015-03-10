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
	public $loadList = 'plg_RowTools, mp_Wrapper, acc_plg_Contable, acc_plg_DocumentSummary,
                    doc_DocumentPlg, plg_Printing, doc_plg_BusinessDoc, plg_Search';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canRead = 'ceo,mp';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canConto = 'ceo,mp';
	
	
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
		$this->FLD('useResourceAccounts', 'enum(yes=Да,no=Не)', 'caption=Влагане по ресурси->Избор,notNull,default=yes,maxRadio=2');
	}
	
	
	/**
	 * Обновява записа, за да се преизчисли полето 'isContable' (@see acc_plg_Contable)
	 */
	public function act_Resave()
	{
		$this->requireRightFor('edit');
		expect($id = Request::get('id', 'int'));
		expect($rec = $this->fetchRec($id));
		
		$this->requireRightFor('edit', $rec);
		
		$this->save($rec);
		
		redirect(array($this, 'single', $id));
	}
}