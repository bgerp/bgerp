<?php


/**
 * Клас 'findeals_AdvanceReportDetail'
 *
 * Детайли на мениджър на авансови отчети (@see findeals_AdvanceReports)
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_AdvanceReportDetails extends doc_Detail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на авансовия отчет';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'reportId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, findeals_Wrapper, plg_AlignDecimals2, doc_plg_HidePrices, plg_SaveAndNew,plg_RowNumbering';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Финанси:Сделки';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, pettyCashReport';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, pettyCashReport';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, pettyCashReport';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, pettyCashReport';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId,measureId=Мярка,quantity,description,amount=Сума';
    
    
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
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Артикул,mandatory,removeAndRefreshForm=amount|quantity|vat|expenseItemId,silent,tdClass=productCell leftCol wrap');
    	$this->FLD('expenseItemId', 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', 'input=none,after=productId,caption=Разход за');
    	$this->FLD('amount', 'double(minDecimals=2)', 'caption=Крайна сума,mandatory');
    	$this->FLD('quantity', 'double(minDecimals=0)', 'caption=Количество,smartCenter');
    	$this->FLD('vat', 'percent', 'caption=ДДС,smartCenter');
    	$this->FLD('description', 'richtext(bucket=Notes,rows=3)', 'caption=Описание');
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
    	$form->setSuggestions('vat', ',0 %,9 %,20 %');
    	
    	if(isset($rec->id)){
    		$rec->amount /= $masterRec->rate;
    		$rec->amount *= 1 + $rec->vat;
    		$rec->amount = deals_Helper::roundPrice($rec->amount);
    	}
    	
    	// Ако има избран артикул, извличаме му мярката и я показваме
    	if(isset($rec->productId)){
    		$measureId = cat_Products::fetchField($rec->productId, 'measureId');
    		$shortUom = cat_UoM::getShortName($measureId);
    		$form->setField('quantity', "unit={$shortUom}");
    	}
    	
    	// Ако е избран артикул и той е невложим и имаме разходни пера,
    	// показваме полето за избор на разход
    	if(isset($rec->productId)){
    		$pRec = cat_Products::fetch($rec->productId, 'canConvert,fixedAsset');
    		if($pRec->canConvert == 'no' && $pRec->fixedAsset == 'no' && acc_Lists::getItemsCountInList('costObjects')){
    			$form->setField('expenseItemId', 'input,mandatory');
    		}
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->productId = cat_Products::getShortHyperlink($rec->productId);
    	
    	$measureId = cat_Products::getProductInfo($rec->productId)->productRec->measureId;
    	$row->measureId = cat_UoM::getShortName($measureId);
    	
    	$masterRec = $mvc->Master->fetch($rec->reportId);
    	$rec->amount /= $masterRec->rate;
    	$rec->amount *= 1 + $rec->vat;
    	
    	if(isset($rec->expenseItemId)){
    		$eItem = acc_Items::getVerbal($rec->expenseItemId, 'titleLink');
    		$row->productId .= "<div class='small'><b>" . tr('Разход за') . "</b>: {$eItem}</div>";
    	}
    	
    	if(!empty($rec->description)){
    		$row->productId .= "<div class='small'>{$row->description}</div>";
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
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
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
    		$masterRec = $data->masterData->rec;
    		
    		if(!count(cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, 'canBuy', 'canStore', 1))){
                $error = "error=Няма купуваеми нескладируеми артикули, ";
            }
            
            $data->toolbar->addBtn('Артикул', array($mvc, 'add', "{$mvc->masterKey}" => $masterRec->id, 'ret_url' => TRUE),
            "id=btnAdd-{$masterRec->id},{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, &$data)
    {
    	$data->listTableMvc->FLD('measureId', 'varchar', 'smartCenter');
    }
}
