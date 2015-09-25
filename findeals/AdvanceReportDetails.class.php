<?php


/**
 * Клас 'findeals_AdvanceReportDetail'
 *
 * Детайли на мениджър на авансови отчети (@see findeals_AdvanceReports)
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_AdvanceReportDetails extends doc_Detail
{
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'deals_AdvanceReportDetails';
	
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на АО';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'reportId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, findeals_Wrapper, plg_AlignDecimals2, doc_plg_HidePrices, plg_SaveAndNew,plg_RowNumbering';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Финанси:Сделки';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, findeals';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, findeals';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, findeals';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo, findeals';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, findeals';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'RowNumb=Пулт,productId,activityCenterId,measureId=Мярка,quantity,description,amount=Сума';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
	/**
     * Полета свързани с цени
     */
    public $priceFields = 'amount';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('reportId', 'key(mvc=findeals_AdvanceReports)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Продукт,mandatory,refreshForm,silent');
    	$this->FLD('amount', 'double(minDecimals=2)', 'caption=Крайна сума,mandatory');
    	$this->FLD('quantity', 'double(minDecimals=0)', 'caption=К-во');
    	$this->FLD('vat', 'percent()', 'caption=ДДС');
    	$this->FLD('description', 'richtext(bucket=Notes,rows=3)', 'caption=Описание');
    	$this->FLD('activityCenterId', 'key(mvc=hr_Departments, select=name, allowEmpty)', 'caption=Център,mandatory,remember');
    }
    
    
    /**
     * Обработка на формата за редакция и добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	$masterRec = $mvc->Master->fetch($form->rec->reportId);
    	$cCode = currency_Currencies::getCodeById($masterRec->currencyId);
    	$form->setField('amount', "unit={$cCode}|* |с ДДС|*");
    	$cover = doc_Folders::getCover($masterRec->folderId);
    	
    	// Взимаме всички продаваеми продукти и махаме складируемите от тях
    	$products = cat_Products::getProducts($cover->getClassId(), $cover->that, $masterRec->valior, 'canBuy', 'canStore');
    	expect(count($products));
        
    	$form->setOptions('productId', $products);
    	$form->setDefault('quantity', 1);
    	$form->setSuggestions('vat', ',0 %,7 %,20 %');
    	
    	if(isset($rec->id)){
    		$rec->amount /= $masterRec->rate;
    		$rec->amount *= 1 + $rec->vat;
    		$rec->amount = deals_Helper::roundPrice($rec->amount);
    	}
    	
    	// Ако има избран артикул, извличаме му мярката и я показваме
    	if(isset($rec->productId)){
    		$measureId = cat_Products::getProductInfo($rec->productId)->productRec->measureId;
    		$shortUom = cat_UoM::getShortName($measureId);
    		$form->setField('quantity', "unit={$shortUom}");
    	}
    	
    	// По дефолт е избран неопределения център
    	$defCenterId = hr_Departments::fetchField("#systemId = 'emptyCenter'", 'id');
    	$form->setDefault('activityCenterId', $defCenterId);
    	
    	// Ако има само един център на дейност, скриваме полето
    	if(hr_Departments::count() === 1){
    		$form->setReadOnly('activityCenterId');
    	}
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	if ($form->isSubmitted()){
    		if(!isset($rec->vat)){
    			$rec->vat = cat_Products::getVat($rec->productId, $masterRec->valior);
    		}
    		
    		$masterRec = $mvc->Master->fetch($rec->reportId);
    		$rec->amount /= 1 + $rec->vat;
    		$rec->amount *= $masterRec->rate;
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->productId = cat_Products::getShortHyperlink($rec->productId);
    	
    	$measureId = cat_Products::getProductInfo($rec->productId)->productRec->measureId;
    	$row->measureId = cat_UoM::getShortName($measureId);
    	
    	$row->activityCenterId = hr_Departments::getShortHyperlink($rec->activityCenterId);
    	
    	$masterRec = $mvc->Master->fetch($rec->reportId);
    	$rec->amount /= $masterRec->rate;
    	$rec->amount *= 1 + $rec->vat;
    	
    	if($rec->description){
    		$row->productId .= "<div class='quiet'>{$row->description}</div>";
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
    		if($mvc->Master->fetchField($rec->reportId, 'state') != 'draft'){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$res, &$data)
    {
    	unset($data->listFields['description']);
    }
}
