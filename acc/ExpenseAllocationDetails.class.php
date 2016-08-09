<?php



/**
 * Детайл на документа за разпределяне на разходи
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ExpenseAllocationDetails extends doc_Detail
{
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "productId,packagingId,quantity,packPrice,discount";
    
    
	/**
     * Заглавие на мениджъра
     */
    public $title = 'Детайли на разпределянето на разходи';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Разпределяне на разход';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'acc, ceo, purchase';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'acc, ceo, purchase';
    
    
    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'acc, ceo, purchase';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'allocationId';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, acc_Wrapper, plg_AlignDecimals2, plg_SaveAndNew, plg_RowZebra';
    
    
    /**
     * Текущ таб
     */
    public $currentTab = 'Операции->Разходи';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'discount';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('allocationId', 'key(mvc=acc_ExpenseAllocations)', 'column=none,notNull,silent,input=hidden,mandatory');
    	$this->FLD('originRecId', 'int', 'placeholder=За разпределяне на разходи,column=none,silent,mandatory,caption=Ред, removeAndRefreshForm=productId|packagingId|packagingId|quantity|quantityInPack|expenseItemId,remember');
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,input=hidden', 'tdClass=productCell leftCol wrap,silent');
    	$this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка','tdClass=small-field nowrap,smartCenter,mandatory,input=hidden');
    	$this->FLD('quantity', 'double(Min=0)', 'caption=Количество,mandatory,smartCenter');
    	$this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
    	$this->FLD('expenseItemId', 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', 'after=quantity,caption=Разход за,mandatory');
    	$this->FNC('packPrice', 'double(minDecimals=2)');
    	$this->FNC('discount', 'percent', 'caption=Отстъпка');
    	
    	$this->setDbIndex('allocationId,originRecId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = $data->form;
    	$rec = $form->rec;
    	
    	// Редовете, по които ще се разпределя
    	$originRecs = acc_ExpenseAllocations::getRecsForAllocationFromOrigin($data->masterRec->originId);
    	$recOptions = array();
    	
    	// Създаване на хубаво представяне на реда
    	$count = 1;
    	foreach ($originRecs as $k => $product){
    		$recOptions[$k] = tr(static::getOriginRecTitle($product, $count));
    		$count++;
    	}
    	
    	// Задаване на опциите за редовете
    	if(isset($rec->id)){
    		
    		// При редакция реда не може да се сменя
    		$recOptions = array("{$rec->originRecId}" => $recOptions[$rec->originRecId]);
    		$form->setField('productId', 'input=hidden');
    	} else {
    		
    		// Ако е една опцията, не добавяме празен ред
    		if(count($recOptions) > 1){
    			$recOptions = array('' => '') + $recOptions;
    		}
    	}
    	
    	$form->setOptions('originRecId', $recOptions);
    	
    	// Ако има само една опция, задава се по дефолт
    	if(count($recOptions) == 1){
    		$form->setDefault('originRecId', key($recOptions));
    	}
    	
    	$form->setField('quantity', 'caption=К-во');
    	
    	// Ако няма избран ред, другите полета се скриват
    	if(!isset($rec->originRecId)){
    		foreach (array('productId', 'packagingId', 'quantityInPack', 'quantity', 'expenseItemId') as $fld){
    			$form->setField($fld, 'input=none');
    		}
    	} else {
    		
    		// Ако има избран ред се попълват дефолтите
    		foreach (array('productId', 'packagingId', 'quantityInPack') as $fld){
    			$form->setDefault($fld, $originRecs[$rec->originRecId]->{$fld});
    		}
    		
    		// Какво количество остава за разпределяне 
    		$allocatedQuantity = $mvc->getAllocatedInDocument($rec->allocationId, $rec->originRecId);
    		$quantityByFar = $originRecs[$rec->originRecId]->quantity - $allocatedQuantity;
    		$quantityAllocatedVerbal = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($allocatedQuantity);
    		
    		// Ако е положително, задава се по подразбиране
    		if($quantityByFar > 0){
    			$form->setDefault('quantity', $quantityByFar);
    		}
    		
    		// Показване на мярката и колко е разпределено с документа до сега
    		if(isset($rec->packagingId)){
    			$shortUom = cat_UoM::getShortName($rec->packagingId);
    			if(empty($rec->id)){
    				$shortUom .= "|* (|разпределено|* <b>{$quantityAllocatedVerbal}</b>)";
    			}
    			
    			$form->setField('quantity', "unit={$shortUom}");
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	if($form->isSubmitted()){
    		
    		// Колко ще бъде разпределено след записа
    		$allocatedQuantity = $mvc->getAllocatedInDocument($rec->allocationId, $rec->originRecId, $rec->id);
    		$allocatedQuantity += $rec->quantity;
    		
    		// Колко е максималното допустимо количество за разпределяне
    		$originRecs = acc_ExpenseAllocations::getRecsForAllocationFromOrigin(acc_ExpenseAllocations::fetchField($rec->allocationId, 'originId'));
    		$maxQuantity = $originRecs[$rec->originRecId]->quantity;
    		
    		// Проверка дали ще се разпределя повече от допустимото количество
    		if($allocatedQuantity > $maxQuantity){
    			$maxQuantity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($maxQuantity);
    			$shortUom = cat_UoM::getShortName($rec->packagingId);
    			$form->setError('quantity', "Разпределяне над допустимото количество от|* <b>{$maxQuantity}</b> {$shortUom}");
    		}
    	}
    }
    
    
    /**
     * Връща количеството разпределено за реда в документа
     * 
     * @param int $allocationId - ид на документа          
     * @param int $originRecId  - кой ред от оригиналния документ отговаря
     * @param int $id           - ид на записа ако има
     * @return double $allocatedQuantity - разпределеното досега количество
     */
    private function getAllocatedInDocument($allocationId, $originRecId, $id = NULL)
    {
    	$query = static::getQuery();
    	
    	// Сумиране на разпределените количества към реда
    	$query->where("#allocationId = {$allocationId} AND #originRecId = {$originRecId}");
    	if(isset($id)){
    		$query->where("#id != {$id}");
    	}
    	
    	$query->XPR('allocatedQuantity', 'double', 'sum(#quantity)');
    	$query->show('allocatedQuantity');
    	
    	$allocatedQuantity = $query->fetch()->allocatedQuantity;
    	$allocatedQuantity = ($allocatedQuantity) ? $allocatedQuantity : 0;
    	
    	return $allocatedQuantity;
    }
    
    
    /**
     * Как ще се показва реда от оригиналния документ
     *
     * @param stdClass $obj
     * @param int $count
     * @return string
     */
    public static function getOriginRecTitle($obj, $count)
    {
    	$Int = cls::get('type_Int');
    	$countVerbal = $Int->toVerbal($count);
    	$pVerbal = cat_Products::getTitleById($obj->productId);
    	$shortUom = cat_UoM::getShortName($obj->packagingId);
    	
    	Mode::push('text', 'plain');
    	$price = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($obj->packPrice);
    	$discount = cls::get('type_Percent')->toVerbal($obj->discount);
    	Mode::pop('text');
    	
    	$name = "|Ред|* {$countVerbal}: {$pVerbal} / |{$shortUom}|* / {$price} {$obj->currencyCode} / {$discount}";
    
    	return $name;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$originId = acc_ExpenseAllocations::fetchField($rec->allocationId, 'originId');
    	
    	$originRec = acc_ExpenseAllocations::getRecsForAllocationFromOrigin($originId, $rec->originRecId);
    	
    	// Вербално показване на данните от реда
    	if($originRec){
    		$rec->discount = $originRec->discount;
    		$rec->packPrice = $originRec->packPrice;
    		$row->discount = $mvc->getFieldType('discount')->toVerbal($originRec->discount);
    		$row->packPrice = $mvc->getFieldType('packPrice')->toVerbal($originRec->packPrice);
    	}
    	
    	$row->productId = cat_Products::getShortHyperlink($rec->productId);
    	
    	// Показваме подробната информация за опаковката при нужда
    	deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
    	
    	$eItem = acc_Items::getVerbal($rec->expenseItemId, 'titleLink');
    	$row->productId .= "<div class='small'><b class='quiet'>" . tr('Разход за') . "</b>: {$eItem}</div>";
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    
    	if(!count($recs)) return;
    	$masterRec = $data->masterData->rec;
    	
    	// В каква валута е сумата
    	$currencyCode = doc_Containers::getDocument($masterRec->originId)->fetchField('currencyId');
    	$chargeVat = doc_Containers::getDocument($masterRec->originId)->fetchField('chargeVat');
    	$chargeVat = ($chargeVat == 'yes') ? 'с ДДС' : 'без ДДС';
    	
    	$data->listFields['packPrice'] = "|Цена|* (<small>{$currencyCode}</small>), |{$chargeVat}|*";
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec->allocationId)){
    		if(acc_ExpenseAllocations::fetchField($rec->allocationId, 'state') != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}