<?php 


/**
 * Детайли на фактурите
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_InvoiceDetails extends deals_InvoiceDetail
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
    public $loadList = 'plg_RowTools2, plg_Created, plg_Sorting, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew, plg_AlignDecimals2, doc_plg_HidePrices, deals_plg_DpInvoice,Policy=price_ListToCustomers, 
                        LastPricePolicy=sales_SalesLastPricePolicy, plg_PrevAndNext';
    
    
    /**
     * Кое е активното меню
     */
    public $pageMenu = "Фактури";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'invoiceId';
    
    
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
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canSell';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('invoiceId', 'key(mvc=sales_Invoices)', 'caption=Фактура, input=hidden, silent');
        parent::setInvoiceDetailFields($this);
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// Добавяне на бутон за импортиране на артикулите директно от договора
    	if($mvc->haveRightFor('importfromsale', (object)array("{$mvc->masterKey}" => $data->masterId))){
    		$data->toolbar->addBtn('От договора', array($mvc, 'importfromsale', "{$mvc->masterKey}" => $data->masterId, 'ret_url' => TRUE),
    		"id=btnImportFromSale-{$masterRec->id},{$error} order=10,title=Импортиране на артикулите от договора", array('warning' => 'Редовете на фактурата, ще копират точно тези от договора|*!', 'ef_icon' => 'img/16/shopping.png'));
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'importfromsale'){
    		$requiredRoles = $mvc->getRequiredRoles('add', $rec, $userId);
    	}
    }
    
    
    /**
     * Импортиране на артикулите от договора във фактурата
     */
    function act_Importfromsale()
    {
    	// Проверки
    	$this->requireRightFor('importfromsale');
    	expect($id = Request::get("{$this->masterKey}", 'int'));
    	expect($invoiceRec = $this->Master->fetch($id));
    	$this->requireRightFor('importfromsale', (object)array("{$this->masterKey}" => $id));
    	
    	// Извличане на дийл интерфейса от договора-начало на нишка
    	$this->delete("#{$this->masterKey} = {$id}");
    	$firstDoc = doc_Threads::getFirstDocument($invoiceRec->threadId);
    	$dealInfo = $firstDoc->getAggregateDealInfo();
    	
    	// За всеки артикул от договора, копира се 1:1
    	$productsToSave =  $dealInfo->dealProducts;
    	if(is_array($dealInfo->dealProducts)){
    		foreach ($dealInfo->dealProducts as $det){
    			$det->{$this->masterKey} = $id;
    			$det->quantity /= $det->quantityInPack;
    			$this->save($det);
    		}
    	}
    	
    	// Редирект обратно към фактурата
    	return followRetUrl(NULL, 'Артикулите от сделката са копирани успешно');
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// Наблюдаване ако има несъответствия
    	// @TODO да се махне след време
    	$amount1Compare = round($rec->price * $rec->quantity * $rec->quantityInPack, 2);
    	$amount2Compare = round($rec->amount, 2);
    	if($amount1Compare != $amount2Compare){
    		wp($rec,$amount1Compare,$amount2Compare);
    	}
    }
}