<?php



/**
 * Мениджър на документ за приключване на счетоводен период
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ClosePeriods extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'acc_TransactionSourceIntf=acc_transaction_ClosePeriod';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Приключвания на периоди";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools, acc_Wrapper, acc_plg_Contable, doc_DocumentPlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "tools=Пулт,periodId,state,createdOn,createdBy,modifiedOn,modifiedBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Можели да се контира въпреки че има приключени пера
     */
    public $canUseClosedItems = TRUE;
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    //public $rowToolsSingleField = 'reason';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Приключване на период';
    
    
    /**
     * Икона на единичния изглед
     */
    //public $singleIcon = 'img/16/blog.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Cp";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'acc,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'accMaster,ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'accMaster,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $canReject = 'accMaster,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,acc';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'acc/tpl/SingleLayoutClosePeriods.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "6.3|Счетоводни";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD("periodId", 'key(mvc=acc_Periods, select=title)', 'caption=Период,mandatory,silent');
    	$this->FLD("amountVatGroup1", 'double(decimals=2)', 'caption=Суми от ДДС групите на касовия апарат->A');
    	$this->FLD("amountVatGroup2", 'double(decimals=2)', 'caption=Суми от ДДС групите на касовия апарат->Б');
    	$this->FLD("amountVatGroup3", 'double(decimals=2)', 'caption=Суми от ДДС групите на касовия апарат->В');
    	$this->FLD("amountVatGroup4", 'double(decimals=2)', 'caption=Суми от ДДС групите на касовия апарат->Г');
    	
    	$this->FLD('state',
    			'enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Затворен)',
    			'caption=Статус, input=none'
    	);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$pQuery = acc_Periods::getQuery();
    	$pQuery->where("#state = 'pending'");
    	
    	$options = acc_Periods::makeArray4Select($select, array("#state = 'active' || #state = 'pending'", $root));
    	$data->form->setOptions('periodId', $options);
    	
    	if(empty($data->form->rec->id)){
    		$data->form->setDefault('state', 'draft');
    	}
    	
    	$data->form->setDefault('valior', dt::today());
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
    	if($fields['-single']){
    		$row->header = $mvc->singleTitle . " #<b>{$mvc->abbr}{$row->id}</b> ({$row->state})";
    		
    		$row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->valior);
    	
    		foreach (range(1, 4) as $id){
    			if(isset($row->{"amountVatGroup{$id}"})){
    				$row->{"amountVatGroup{$id}"} .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
    			}
    		}
    		
    		if($rec->state == 'active'){
    			$valior = acc_Journal::fetchByDoc($mvc->getClassId(), $rec->id)->valior;
    			$Date = cls::get('type_Date');
    			$row->valior = $Date->toVerbal($valior);
    		}
    	}
    	
    	$balanceid = acc_Balances::fetchField("#periodId = {$rec->periodId}", 'id');
    	
    	if(acc_Balances::haveRightFor('single', $balanceid)){
    		$row->periodId = ht::createLink($row->periodId, array('acc_Balances', 'single', $balanceid));
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    
    	return $folderClass == 'doc_UnsortedFolders';
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    
    	$row = new stdClass();
    
    	$row->title = tr("Приключване на период");
    
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->recTitle = $row->title;
    	$row->state = $rec->state;
    
    	return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    
    	return $self->singleTitle . " №{$rec->id}";
    }
}