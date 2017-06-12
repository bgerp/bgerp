<?php


/**
 * Клас 'planning_ReturnNotes' - Документ за Протокол за връщане
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
class planning_ReturnNotes extends deals_ManifactureMaster
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Протоколи за връщане от производство';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Mrn';
	
	
	/**
	 * Поддържани интерфейси
	 */
	public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_ReturnNote';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2, store_plg_StoreFilter, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, doc_plg_BusinessDoc, plg_Search, bgerp_plg_Blank';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'storeId,note';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canConto = 'ceo,planning,store';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,planning,store';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,planning,store';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'ceo,planning,store';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'ceo,planning,store';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Протокол за връщане от производство';
	
	
	/**
	 * Файл за единичния изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutReturnNote.shtml';
	
	 
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "3.51|Производство";
	
	
	/**
	 * Детайл
	 */
	public $details = 'planning_ReturnNoteDetails';
	
	
	/**
	 * Кой е главния детайл
	 * 
	 * @var string - име на клас
	 */
	public $mainDetail = 'planning_ReturnNoteDetails';
	
	
	/**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     * 
     * @see plg_Clone
     */
	public $cloneDetails = 'planning_ReturnNoteDetails';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'title';
	
	
	/**
	 * Икона на единичния изглед
	 */
	public $singleIcon = 'img/16/produce_out.png';
	
	
	/**
	 * Кой може да го прави документа чакащ/чернова?
	 */
	public $canPending = 'ceo,planning,store';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		parent::setDocumentFields($this);
		$this->FLD('departmentId', 'key(mvc=hr_Departments,select=name,allowEmpty)', 'caption=Департамент,before=note');
		$this->FLD('useResourceAccounts', 'enum(yes=Да,no=Не)', 'caption=Детайлно връщане->Избор,notNull,default=yes,maxRadio=2,before=note');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$folderCover = doc_Folders::getCover($data->form->rec->folderId);
		if($folderCover->isInstanceOf('hr_Departments')){
			$data->form->setReadOnly('departmentId', $folderCover->that);
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->useResourceAccounts = ($rec->useResourceAccounts == 'yes') ? 'Артикулите ще бъдат изписани от незавършеното производство един по един' : 'Артикулите ще бъдат изписани от незавършеното производството сумарно';
		$row->useResourceAccounts = tr($row->useResourceAccounts);
		
		if(isset($rec->departmentId)){
			$row->departmentId = hr_Departments::getHyperlink($rec->departmentId, TRUE);
		}
	}
}