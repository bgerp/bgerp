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
class findeals_AdvanceReportDetails extends deals_DeliveryDocumentDetail
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
    public $loadList = 'plg_RowTools2, findeals_Wrapper, plg_AlignDecimals2, doc_plg_HidePrices, plg_SaveAndNew,plg_RowNumbering,acc_plg_ExpenseAllocation';
    
    
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
    public $listFields = 'productId, packagingId=Мярка, packQuantity, packPrice, discount, amount, quantityInPack';
    
    
	/**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('reportId', 'key(mvc=findeals_AdvanceReports)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('expenseItemId', 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', 'input=none,after=productId,caption=Разход за');
    	parent::setDocumentFields($this);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	// Ако е избран артикул и той е невложим и имаме разходни пера,
    	// показваме полето за избор на разход
    	if(isset($rec->productId)){
    		$pRec = cat_Products::fetch($rec->productId, 'canConvert,fixedAsset');
    		if($pRec->canConvert == 'no' && $pRec->fixedAsset == 'no' && $data->masterRec->isReverse != 'yes' && acc_Lists::getItemsCountInList('costObjects') > 1){
    			$form->setField('expenseItemId', 'input');
    		}
    	}
    	
    	$form->setField('packPrice', 'mandatory');
    	$form->setField('discount', 'input=none');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    {
    	parent::inputDocForm($mvc, $form);
    }
    
    
    /**
     * Достъпните продукти
     */
    protected function getProducts($masterRec)
    {
    	$property = ($masterRec->isReverse == 'yes') ? 'canSell' : 'canBuy';
    
    	// Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
    	$products = cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date, $property, 'canStore');
    	 
    	return $products;
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$masterRec = findeals_AdvanceReports::fetch($rec->reportId);
    	$date = ($masterRec->state == 'draft') ? NULL : $masterRec->modifiedOn;
    	$row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, 'title', 'public', $data->masterData->rec->tplLang);
    			
    	if($rec->notes){
    		$row->productId .= "<div class='small'>{$mvc->getFieldType('notes')->toVerbal($rec->notes)}</div>";
    	}
    }
}
