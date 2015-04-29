<?php 


/**
 * Детайл на разпределение на разходи за избор на
 * скалдируеми артикули, към които да се начисляват разходи
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ExpenseAllocationProducts extends doc_Detail
{
    
    
    /**
     * Заглавие
     */
    public $title = "Детайли на разпределението на разходи";
    
    
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew, plg_AlignDecimals2, doc_plg_HidePrices';
    
    
    /**
     * Кое е активното меню
     */
    public $pageMenu = "Разпределения";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'masterId';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'acc, ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'acc, ceo';
    
    
    /**
     * Кой таб да бъде отворен
     */
    public $currentTab = 'Операции->Разпределения';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Кой има право да чете?
     */
    public $listFields = 'RowNumb=№,itemId=Артикул,measureId=Мярка,quantity=К-во,weight';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('masterId', 'key(mvc=acc_ExpenseAllocations)', 'column=none,input=hidden,silent');
    	$this->FLD('itemId', 'key(mvc=acc_Items,select=title)', 'caption=Артикул,mandatory,silent');
    	$this->FLD('quantity', 'double', 'caption=К-во,mandatory');
    	$this->FLD('weight', 'percent(max=1,min=0)', 'caption=Коефициент,mandatory');
    	
    	$this->setDbUnique('masterId,itemId');
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
    	$data->form->setSuggestions('weight', array('' => '') + arr::make('5 %,10 %,15 %,20 %,25 %,30 %, 40 %, 50 %, 100%', TRUE));
    	
    	if($data->form->rec->id){
    		$data->form->setReadOnly('itemId');
    	}
    	
    	// Показваме мярката на артикула
    	if(isset($data->form->rec->itemId)){
    		$itemRec = acc_Items::fetch($data->form->rec->itemId);
    		$measureShort = cat_UoM::getShortName($itemRec->uomId);
    		$data->form->setField('quantity', "unit={$measureShort}");
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if($rec->itemId){
    		$pItemUomId = acc_Items::fetchField($rec->itemId, 'uomId');
    		$row->measureId = cat_UoM::getShortName($pItemUomId);
    	} else {
    		$row->measureId = "<span class='red'>???</span>";
    	}
    	
    	$row->itemId = acc_Items::getVerbal($rec->itemId, 'titleLink');
    	
    	if($rec->weight == '0'){
    		$row->ROW_ATTR['style'] = " background-color:#f1f1f1;color:#777";
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	// Не може да се добавят и изтриват артикули
    	if(($action == 'add' || $action == 'delete') && isset($rec)){
    		$requiredRoles = 'no_one';
    	}
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
    	if(!count($data->recs)) return;
    	 
    	// Изчисляваме колко % е общото тегло
    	$total = 0;
    	foreach ($data->recs as $rec){
    		$total += $rec->weight;
    	}
    	
    	// Ако не е между 0 и 1, показваме го червено
    	$totalRow = $mvc->getFieldType('weight')->toVerbal($total);
    	if($total != 1){
    		$hint = tr('Общото тегло трябва да e');
    		$totalRow = "<span class='red' title = '{$hint} 100%'>{$totalRow}</span>";
    	}
    	
    	// Добавяме под таблицата информация за общото тегло
    	$colspan = count($data->listFields) - 1;
    	$totalRow = "<tr style='background-color:#F0F0F0'><td colspan={$colspan}> <div style='float:right'><b>" . tr('Общо') . "</b>:</div></td><td style='text-align:right'><b>{$totalRow}</b></td></tr>";
    	$tpl->append($totalRow, 'ROW_AFTER');
    }
}