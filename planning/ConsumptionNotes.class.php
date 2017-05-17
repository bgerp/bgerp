<?php


/**
 * Клас 'planning_ConsumptionNotes' - Документ за Протокол за влагане
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
class planning_ConsumptionNotes extends deals_ManifactureMaster
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_ConsumptionNotes';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Протоколи за влагане в производство';
	
	
	/**
	 * Име на документа в бързия бутон за добавяне в папката
	 */
	public $buttonInFolderTitle = 'Влагане';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Mcn';
	
	
	/**
	 * Поддържани интерфейси
	 */
	public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_ConsumptionNote';
	
	
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
	 * Кой може да го прави документа чакащ/чернова?
	 */
	public $canPending = 'ceo,planning,store';
	
	
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
	public $singleTitle = 'Протокол за влагане в производство';
	
	
	/**
	 * Файл за единичния изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutConsumptionNote.shtml';
	
	
	/**
	 * Файл за единичния изглед в мобилен
	 */
	public $singleLayoutFileNarrow = 'planning/tpl/SingleLayoutConsumptionNoteNarrow.shtml';
	
	
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "3.5|Производство";
	
	
	/**
	 * Детайл
	 */
	public $details = 'planning_ConsumptionNoteDetails';
	
	
	/**
	 * Кой е главния детайл
	 * 
	 * @var string - име на клас
	 */
	public $mainDetail = 'planning_ConsumptionNoteDetails';
	
	
	/**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     * 
     * @see plg_Clone
     */
	public $cloneDetails = 'planning_ConsumptionNoteDetails';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'title';
	
	
	/**
	 * Икона на единичния изглед
	 */
	public $singleIcon = 'img/16/produce_in.png';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		parent::setDocumentFields($this);
		$this->FLD('useResourceAccounts', 'enum(yes=Да,no=Не)', 'caption=Детайлно влагане->Избор,notNull,default=yes,maxRadio=2,before=note');
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
		$row->useResourceAccounts = ($rec->useResourceAccounts == 'yes') ? 'Артикулите ще бъдат вкарани в производството по артикули' : 'Артикулите ще бъдат вложени в производството сумарно';
		$row->useResourceAccounts = tr($row->useResourceAccounts);
	}
}
