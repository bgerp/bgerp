<?php



/**
 * Клас 'store_Receipts'
 *
 * Мениджър на Складовите разписки, Само складируеми продукти могат да се заприхождават в склада
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Receipts extends store_DocumentMaster
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Складови разписки';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Sr';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleStoreDocument.shtml';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, store_iface_DocumentIntf,
                          acc_TransactionSourceIntf=store_transaction_Receipt, bgerp_DealIntf,trans_LogisticDataIntf,deals_InvoiceSourceIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, store_Wrapper, sales_plg_CalcPriceDelta, plg_Sorting, acc_plg_Contable, cond_plg_DefaultValues,
                    plg_Clone,doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, doc_plg_TplManager,
					doc_EmailCreatePlg, bgerp_plg_Blank, trans_plg_LinesPlugin, doc_plg_HidePrices, doc_SharablePlg,deals_plg_SetTermDate,deals_plg_EditClonedDetails,cat_plg_AddSearchKeywords, plg_Search';

    
    /**
     * До потребители с кои роли може да се споделя документа
     *
     * @var string
     * @see doc_SharablePlg
     */
    public $shareUserRoles = 'ceo, store';
    
    
    /**
     * Кой може да сторнира
     */
    public $canRevert = 'storeMaster, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canChangeline = 'ceo,store,trans';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,store';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'deliveryTime,valior, title=Документ, folderId, amountDelivered, weight, volume, createdOn, createdBy';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId,storeId,note';

    
    /**
     * Детайла, на модела
     */
    public $details = 'store_ReceiptDetails' ;
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Складова разписка';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/store-receipt.png';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.4|Логистика";
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'store_ReceiptDetails';
    
    
    /**
     * Основна операция
     */
    protected static $defOperationSysId = 'delivery';
    
	
    /**
     * Показва броя на записите в лога за съответното действие в документа
     */
    public $showLogTimeInHead = 'Документът се връща в чернова=3';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'store_ReceiptDetails';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setDocFields($this);
        $this->setField('storeId', 'caption=В склад');
        $this->setField('deliveryTime', 'caption=Разтоварване');
    }
    
    
	/**
     * След изпращане на формата
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	expect($origin = static::getOrigin($rec), $rec);
        	$dealInfo = $origin->getAggregateDealInfo();
        	
        	$operations = $dealInfo->get('allowedShipmentOperations');
        	$operation = $operations['stowage'];
        	$rec->accountId = $operation['credit'];
        	$rec->isReverse = (isset($operation['reverse'])) ? 'yes' : 'no';
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$data->form->setField('locationId', 'caption=Обект от');
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function prepareReceipts($data)
    {
    	$data->receipts = parent::prepareLineDetail($data->masterData);
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function renderReceipts($data)
    {
    	if(count($data->receipts)){
    		$table = cls::get('core_TableView');
    		$fields = "rowNumb=№,docId=Документ,storeId=Склад,weight=Тегло,volume=Обем,palletCount=Палети,collection=Инкасиране,address=@Адрес";
    		$fields = core_TableView::filterEmptyColumns($data->shipmentOrders, $fields, 'collection,palletCount');
    		
    		return $table->get($data->receipts, $fields);
    	}
    }
    
    
	/**
     * Връща тялото на имейла генериран от документа
     * 
     * @see email_DocumentIntf
     * @param int $id - ид на документа
     * @param boolean $forward
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = FALSE)
    {
        $handle = $this->getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата складова разписка") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
	/**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Складова разписка', 
    					  'content' => 'store/tpl/SingleLayoutReceipt.shtml', 'lang' => 'bg', 'narrowContent' => 'store/tpl/SingleLayoutReceiptNarrow.shtml',
    					  'toggleFields' => array('masterFld' => NULL, 'store_ReceiptDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Складова разписка с цени', 
    					  'content' => 'store/tpl/SingleLayoutReceiptPrices.shtml', 'lang' => 'bg', 'narrowContent' => 'store/tpl/SingleLayoutReceiptPricesNarrow.shtml',
    					  'toggleFields' => array('masterFld' => NULL, 'store_ReceiptDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	 
    	if($rec->isReverse == 'no'){
    		if($rec->state == 'active' || $rec->state == 'draft' || $rec->state == 'pending'){
    			 
    			// Ако има фактура към протокола, правим линк към нея, иначе бутон за създаване на нова
    			if($iRec = purchase_Invoices::fetch("#sourceContainerId = {$rec->containerId} AND #state != 'rejected'")){
    				if(purchase_Invoices::haveRightFor('single', $iRec)){
    					$arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
    					$data->toolbar->addBtn("Вх. фактура|* {$arrow}", array('purchase_Invoices', 'single', $iRec->id, 'ret_url' => TRUE), 'title=Отваряне на входящата фактура издадена към складова разписка,ef_icon=img/16/invoice.png');
    				}
    			} else {
    				if(purchase_Invoices::haveRightFor('add', (object)array('threadId' => $rec->threadId, 'sourceContainerId' => $rec->containerId))){
    					$data->toolbar->addBtn('Вх. фактура', array('purchase_Invoices', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'ret_url' => TRUE), 'title=Създаване на входяща фактура към складова разписка,ef_icon=img/16/invoice.png,row=2');
    				}
    			}
    		}
    	}
    }
}
