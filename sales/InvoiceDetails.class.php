<?php 

/**
 * Детайли на фактурите
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_InvoiceDetails extends deals_InvoiceDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на фактурата';
    
    
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Sorting, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew, plg_AlignDecimals2, doc_plg_HidePrices, deals_plg_DpInvoice,Policy=price_ListToCustomers, 
                        LastPricePolicy=sales_SalesLastPricePolicy, plg_PrevAndNext,cat_plg_ShowCodes, import2_Plugin';


    /**
     * Интерфейс на драйверите за импортиране
     */
    public $importInterface = 'store_iface_ImportDetailIntf';


    /**
     * Кой може да импортира
     */
    public $canImport = 'powerUser';


    /**
     * Кое е активното меню
     */
    public $pageMenu = 'Фактури';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'invoiceId';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'powerUser';
    
    
    /**
     * Кой таб да бъде отворен
     */
    public $currentTab = 'Фактури';
    
    
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canSell';
    
    
    /**
     * Полета, които се експортват
     */
    public $exportToMaster = 'quantity, productId=code|name';


    /**
     * Параметър на артикула за показване във фактурата
     */
    public $productInvoiceInfoParamName = 'invoiceInfo';


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'exportParamValue';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('invoiceId', 'key(mvc=sales_Invoices)', 'caption=Фактура, input=hidden, silent');
        parent::setInvoiceDetailFields($this);
        $this->FLD('batches', 'text(rows=1)', 'caption=Допълнително->Партиди, input=none, before=notes');
        $this->FLD('exportParamValue', 'varchar', 'caption=Счетоводен параметър, input=none');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        
        if (core_Packs::isInstalled('batch')) {
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
        if (core_Packs::isInstalled('batch') && $rec->_importBatches != 'no') {
            $cQuery = doc_Containers::getQuery();
            $cQuery->where("#threadId = {$containerId} AND #state != 'draft' AND #state != 'rejected'");
            $cQuery->show('id');
            $ids = arr::extractValuesFromArray($cQuery->fetchAll(), 'id');
            if (!countR($ids)) {
                return;
            }
            
            // Намират се всички партиди в документите от нишката на фактурата
            $bQuery = batch_BatchesInDocuments::getQuery();
            $bQuery->in('containerId', $ids);
            $bQuery->where("#productId = {$rec->productId}");
            if(isset($rec-> _batches)){
                $bQuery->in("id", $rec-> _batches);
            }
            
            $bQuery->show('batch');
            $batches = arr::extractValuesFromArray($bQuery->fetchAll(), 'batch');
            
            // И се попълват
            if (countR($batches)) {
                $rec->batches = implode(', ', $batches);
                $mvc->save_($rec, 'batches');
            }
        }
    }


    /**
     * Екшън за добавяне на артикулите от ЕН-то към фактурата
     *
     * @return void
     * @throws core_exception_Expect
     */
    function act_addFromShipmentDocument()
    {
        $this->requireRightFor('add');
        expect($invoiceId = Request::get('invoiceId', 'int'));
        expect($invoiceRec = sales_Invoices::fetch($invoiceId));
        expect($originId = Request::get('originId', 'int'));
        expect($origin  = doc_Containers::getDocument($originId));
        expect($origin->isInstanceOf('store_ShipmentOrders'));
        $this->requireRightFor('add', (object)array('invoiceId' => $invoiceId));
        $added = $updated = 0;

        // Прехвърляне на детайлите на ЕН-то към фактурата
        $OriginDetail = cls::get($origin->mainDetail);
        $odQuery = $OriginDetail->getQuery();
        $odQuery->where("#{$OriginDetail->masterKey} = {$origin->that}");
        while($oRec = $odQuery->fetch()){
            unset($oRec->id);
            $oRec->price = $oRec->price * (1 - $oRec->discount);
            unset($oRec->discount);

            $oRec->invoiceId = $invoiceId;
            $exRec = deals_Helper::fetchExistingDetail($this, $oRec->invoiceId, $oRec->id, $oRec->productId, $oRec->packagingId, $oRec->price, $oRec->discount, null, null, $oRec->batch, $oRec->expenseItemId, $oRec->notes);
            $oRec->quantity = $oRec->packQuantity;
            if ($exRec) {
                $exRec->quantity += $oRec->quantity;
                $this->save($exRec, 'quantity');
                $updated++;
            } else {
                $this->save($oRec);
                $added++;
            }
        }

        $msg = "Добавени|*: {$added}. |Обновени|*: {$updated}!";
        $handle = "#{$origin->getHandle()}";

        // Добавяне в забележките
        if(strpos($invoiceRec->additionalInfo, $handle) === false){
            $invoiceRec->additionalInfo .= "\n" . $handle;
            $this->Master->save_($invoiceRec, 'additionalInfo');
        }

        followRetUrl(null, $msg);
    }
}
