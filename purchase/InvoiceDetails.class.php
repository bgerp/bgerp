<?php 


/**
 * Детайли на фактурите
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_InvoiceDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    public $title = "Детайли на фактурата";
    
    
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Старо име на класа
     */
    public $oldClassName = 'acc_InvoiceDetails';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, purchase_Wrapper, plg_RowNumbering, plg_SaveAndNew, plg_AlignDecimals2, doc_plg_HidePrices, acc_plg_DpInvoice, acc_plg_InvoiceDetail,Policy=purchase_PurchaseLastPricePolicy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Кое е активното меню
     */
    public $pageMenu = "Фактури";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'invoiceId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, quantity, packPrice, discount, amount';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'invoicer, ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'invoicer, ceo';
    
    
    /**
     * Кой таб да бъде отворен
     */
    public $currentTab = 'Фактури';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount';
    
    
    /**
     * Помощен масив за мапиране на полета изпозлвани в deals_Helper
     */
    public static $map = array('rateFld'     => 'rate', 
        			 		   'chargeVat'   => 'vatRate', 
        			 		   'quantityFld' => 'quantity', 
        			 		   'valior'      => 'date',
    						   'alwaysHideVat' => TRUE);
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('invoiceId', 'key(mvc=purchase_Invoices)', 'caption=Фактура, input=hidden, silent');
        $this->FLD('productId', 'int', 'caption=Продукт','tdClass=large-field leftCol');
        $this->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка','tdClass=small-field');
        $this->FLD('quantity', 'double(Min=0)', 'caption=К-во,mandatory','tdClass=small-field');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none');
        $this->FLD('price', 'double', 'caption=Цена, input=none');
        $this->FLD('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
		$this->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input');
		$this->FLD('discount', 'percent', 'caption=Отстъпка');
		$this->FLD('note', 'varchar(64)', 'caption=@Пояснение');
		
		$this->setDbUnique('invoiceId, productId, packagingId');
	}
    
    
	/**
     * Изчисляване на цена за опаковка на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function on_CalcPackPrice(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
    
        $rec->packPrice = $rec->price * $rec->quantityInPack;
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    $rec = &$data->form->rec;
        $masterRec = $data->masterRec;
       	$ProductManager = ($data->ProductManager) ? $data->ProductManager : cls::get($rec->classId);
       	
       	$data->form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", ";
        $data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
        
        $products = $mvc->Policy->getProducts($masterRec->contragentClassId, $masterRec->contragentId);
        expect(count($products));
        
        $data->form->setSuggestions('discount', arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
        
        if (empty($rec->id)) {
        	$data->form->addAttr('productId', array('onchange' => "addCmdRefresh(this.form);document.forms['{$data->form->formAttr['id']}'].elements['id'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['packPrice'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['discount'].value ='';this.form.submit();"));
			$data->form->setOptions('productId', array('' => ' ') + $products);
        	
        } else {
            // Нямаме зададена ценова политика. В този случай задъжително трябва да имаме
            // напълно определен продукт (клас и ид), който да не може да се променя във формата
            // и полето цена да стане задължително
            $data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
        }
        
        if (!empty($rec->packPrice)) {
        	$rec->packPrice = deals_Helper::getPriceToCurrency($rec->packPrice, 0, $masterRec->rate, $masterRec->vatRate);
        }
    }

    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, &$data)
    {
    	$masterRec = $data->masterData->rec;
    	if($masterRec->type != 'invoice'){
    		
    		// При дебитни и кредитни известия показваме основанието
    		$data->listFields = array();
    		$data->listFields['number'] = '№';
    		$data->listFields['reason'] = 'Основание';
    		$data->listFields['amount'] = 'Сума';
    		$data->rows = array();
    		
    		// Показване на сумата за промяна на известието
    		$amount = $mvc->getFieldType('amount')->toVerbal($masterRec->dealValue / $masterRec->rate); 
    		
    		$data->rows[] = (object) array('number' => 1,
    									   'reason' => $masterRec->reason,
    									   'amount' => $amount);
    	} 
    }
    
    
    /**
     * След калкулиране на общата сума
     */
    public function calculateAmount_(&$recs, &$rec)
    {
    	deals_Helper::fillRecs($this->Master, $recs, $rec, static::$map);
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
    		$masterRec = $data->masterData->rec;
    		$ProductManager = cls::get('cat_Products');
    		$products = $ProductManager::getByProperty('canBuy');
    
    		if(!count($products)){
    			$error = "error=Няма купуваеми {$ProductManager->title}";
    		}
    
    		$data->toolbar->addBtn($ProductManager->singleTitle, array($mvc, 'add', $mvc->masterKey => $masterRec->id, 'classId' => $ProductManager->getClassId(), 'ret_url' => TRUE),
    				"id=btnAdd-{$manId},{$error},order=10", 'ef_icon = img/16/shopping.png');
    		unset($error);
    
    		unset($data->toolbar->buttons['btnAdd']);
    	}
    }
}