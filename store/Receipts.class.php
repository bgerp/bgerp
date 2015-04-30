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
 * @copyright 2006 - 2014 Experta OOD
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
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, store_iface_DocumentIntf,
                          acc_TransactionSourceIntf=store_transaction_Receipt, bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, store_Wrapper, plg_Sorting, acc_plg_Contable, cond_plg_DefaultValues,
                    doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, plg_Search, doc_plg_TplManager,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices, store_plg_Document';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,store';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,store';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, valior, title=Документ, folderId, amountDelivered, weight, volume, createdOn, createdBy';


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
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutReceipt.shtml';

   
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
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    		'template' => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setDocFields($this);
        $this->setField('storeId', 'caption=От склад');
    }
    
    
	/**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	$dealInfo = static::getOrigin($rec)->getAggregateDealInfo();
        	
        	$operations = $dealInfo->get('allowedShipmentOperations');
        	$operation = $operations['stowage'];
        	$rec->accountId = $operation['credit'];
        	$rec->isReverse = (isset($operation['reverse'])) ? 'yes' : 'no';
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$data->form->setField('locationId', 'caption=Обект от');
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function prepareReceipts($data)
    {
    	$data->receipts = parent::prepareLineDetail($data->masterData->rec);
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function renderReceipts($data)
    {
    	if(count($data->receipts)){
    		$table = cls::get('core_TableView');
    		$fields = "rowNumb=№,docId=Документ,storeId=Склад,weight=Тегло,volume=Обем,collection=Инкасиране,address=@Адрес";
    		 
    		return $table->get($data->receipts, $fields);
    	}
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата складова разписка") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
     
     
	/**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("|Складова разписка|* №") . $rec->id;
    }
    
    
	/**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Складова разписка', 
    					  'content' => 'store/tpl/SingleLayoutReceipt.shtml', 'lang' => 'bg', 
    					  'toggleFields' => array('masterFld' => NULL, 'store_ReceiptDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Складова разписка с цени', 
    					  'content' => 'store/tpl/SingleLayoutReceiptPrices.shtml', 'lang' => 'bg',
    					  'toggleFields' => array('masterFld' => NULL, 'store_ReceiptDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	 
    	if($rec->state == 'active'){
    		if(acc_ExpenseAllocations::haveRightFor('add', (object)array('threadId' => $rec->threadId, 'originId' => $rec->containerId))){
    			$data->toolbar->addBtn("Разходи", array('acc_ExpenseAllocations', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/view.png,title=Създаване на документ за разпределяне на разходи,row=2');
    		}
    	}
    }
    
    
    /**
     * Връща масив със складируемите артикули, върху които ще се разпределят разходи
     *
     * @param int $id - ид на документа
     * @param int $limit - ограничение
     * @return array $products - намерените артикули
     */
    public function getStorableProducts($id, $limit = NULL)
    {
    	$products = array();
    	$rec = $this->fetchRec($id);
    	if($rec->state != 'active') return $products;
    	
    	// Кои артикули са експедирани
    	$dQuery = store_ReceiptDetails::getQuery();
    	$dQuery->where("#receiptId = {$id}");
    	if($limit){
    		$dQuery->limit($limit);
    	}
    	
    	while($dRec = $dQuery->fetch()){
    		$products[] = $dRec;
    	}
    	
    	return $products;
    }
    
    
    /**
     * Връща ид-то на склада към артикулите в него, на които ще се разпределят разходите
     *
     * @param int $id
     * @return int $storeId - ид на скалда
     */
    function getStoreId($id)
    {
    	return $this->fetchField($id, 'storeId');
    }
}