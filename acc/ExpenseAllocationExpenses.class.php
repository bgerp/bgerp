<?php 


/**
 * Детайли на фактурите
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ExpenseAllocationExpenses extends doc_Detail
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
    public $pageMenu = "Операции->Разпределения";
    
    
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
    public $currentTab = 'Разпределения';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Кой има право да чете?
     */
    public $listFields = 'RowNumb=№,itemId=Разход,amount=Сума';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('masterId', 'key(mvc=acc_ExpenseAllocations)', 'column=none,input=hidden,silent');
    	$this->FLD('itemId', 'key(mvc=acc_Items,select=title)', 'caption=Разход,mandatory,silent');
    	$this->FLD('amount', 'double', 'caption=Сума,mandatory');
    	
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
    	$baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
    	$form->setField('amount', "unit={$baseCurrencyCode}");
    	
    	// Показваме само перата на артикули от сметката за разходи 6112
    	$options = acc_Items::getItemOptionsInAccount('6112', 'cat_ProductAccRegIntf', $data->masterRec->valior);
    	$form->setOptions('itemId', array('' => '') + $options);
    	
    	if($data->form->rec->id){
    		$data->form->setReadOnly('itemId');
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if($data->toolbar->hasBtn('btnAdd')){
    		$data->toolbar->renameBtn('btnAdd', "Нов разход");
    	}
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
    	$baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
    	$data->listFields['amount'] .= ", {$baseCurrencyCode}";
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->itemId = acc_Items::getVerbal($rec->itemId, 'titleLink');
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
    	if(!count($data->recs)) return;
    	
    	// Колко е общата сума
    	$total = 0;
    	foreach ($data->recs as $rec){
    		$total += $rec->amount;
    	}
    	
    	// Показваме под таблицата ред с обобщената сума
    	$totalRow = $mvc->getFieldType('amount')->toVerbal($total);
    	$colspan = count($data->listFields) - 1;
    	$totalRow = "<tr style='background-color:#F0F0F0'><td colspan={$colspan}> <div style='float:right'><b>" . tr('Общо') . "</b>:</div></td><td style='text-align:right'><b>{$totalRow}</b></td></tr>";
    	$tpl->append($totalRow, 'ROW_AFTER');
    }
}