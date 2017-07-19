<?php 



/**
 * Детайли на фактурите
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
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
                        LastPricePolicy=sales_SalesLastPricePolicy, plg_PrevAndNext,cat_plg_ShowCodes';
    
    
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
        $this->FLD('batches', 'text(rows=1)', 'caption=Допълнително->Партиди, input=none, before=notes');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$form = &$data->form;
    	$rec = &$data->form->rec;
    	
    	if(core_Packs::isInstalled('batch')){
    		$form->setField('batches', 'input');
    	}
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	$containerId = sales_Invoices::fetchField($rec->invoiceId, 'threadId');
    	
    	// Ако е инсталиран пакета за партиди
    	if(core_Packs::isInstalled('batch')){
    		
    		$cQuery = doc_Containers::getQuery();
    		$cQuery->where("#threadId = {$containerId} AND #state != 'draft' AND #state != 'rejected'");
    		$cQuery->show("id");
    		$ids = arr::extractValuesFromArray($cQuery->fetchAll(), 'id');
    		if(!count($ids)) retun;
    		
    		// Намират се всички партиди в документите от нишката на фактурата
    		$bQuery = batch_BatchesInDocuments::getQuery();
    		$bQuery->in("containerId", $ids);
    		$bQuery->where("#productId = {$rec->productId}");
    		$bQuery->show("batch");
    		$batches = arr::extractValuesFromArray($bQuery->fetchAll(), 'batch');
    		
    		// И се попълват
    		if(count($batches)){
    			$rec->batches = implode(', ', $batches);
    			$mvc->save_($rec, 'batches');
    		}
    	}
    }
}