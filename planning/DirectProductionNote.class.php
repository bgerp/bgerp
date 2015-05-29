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
	//public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_ProductionNote';
	
	
	/**
	 * Плъгини за зареждане
	 * 
	 * , acc_plg_Contable
	 */
	public $loadList = 'plg_RowTools, planning_Wrapper, acc_plg_DocumentSummary,
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
		
		$this->setField('activityCenterId', 'input=none');
		$this->setField('deadline', 'input=none');
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,after=storeId');
		$this->FLD('quantity', 'double(smartRound)', 'caption=За,mandatory,after=productId');
		
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
		$form->setDefault('quantity', $quantity);
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
		if($action == 'add'){
			if(isset($rec)){
				if(empty($rec->originId)){
					$requiredRoles = 'no_one';
				} else {
					$originDoc = doc_Containers::getDocument($rec->originId);
					if(!($originDoc->getInstance() instanceof planning_Jobs)){
						$requiredRoles = 'no_one';
					} else {
						$state = $originDoc->fetchField('state');
						if($state == 'rejeced' || $state == 'draft'){
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
}