<?php
/**
 * Клас 'store_ShipmentOrders'
 *
 * Мениджър на експедиционни нареждания. Само складируеми продукти могат да се експедират
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ShipmentOrders extends store_DocumentMaster
{
    
    
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Експедиционни нареждания';


    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Exp';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, store_iface_DocumentIntf,
                          acc_TransactionSourceIntf=store_transaction_ShipmentOrder, bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, store_Wrapper, plg_Sorting, acc_plg_Contable, cond_plg_DefaultValues,
                    doc_DocumentPlg, plg_Printing, trans_plg_LinesPlugin, acc_plg_DocumentSummary, plg_Search, doc_plg_TplManager,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices';

    
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
     * Кой има право да променя?
     */
    public $canChangeline = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store';


    /**
     * Кой може да го види?
     */
    public $canViewprices = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, valior, title=Документ, folderId, currencyId, amountDelivered, amountDeliveredVat, weight, volume, createdOn, createdBy';

    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_ShipmentOrderDetails' ;
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Експедиционно нареждане';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/SingleLayoutShipmentOrder.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.3|Логистика";
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'store_ShipmentOrderDetails';
    
    
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
    	
    	$this->FLD('company', 'varchar', 'caption=Адрес за доставка->Фирма');
        $this->FLD('person', 'varchar', 'caption=Адрес за доставка->Име, changable, class=contactData');
        $this->FLD('tel', 'varchar', 'caption=Адрес за доставка->Тел., changable, class=contactData');
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Адрес за доставка->Държава, class=contactData');
        $this->FLD('pCode', 'varchar', 'caption=Адрес за доставка->П. код, changable, class=contactData');
        $this->FLD('place', 'varchar', 'caption=Адрес за доставка->Град/с, changable, class=contactData');
        $this->FLD('address', 'varchar', 'caption=Адрес за доставка->Адрес, changable, class=contactData');
    }
    
    
    /**
     * След рендиране на сингъла
     */
    public static function on_AfterRenderSingle($mvc, $tpl, $data)
    {
    	$tpl->append(sbf('img/16/plus.png', "'"), 'iconPlus');
    	if($data->rec->country){
    		$deliveryAddress = "{$data->row->country} <br/> {$data->row->pCode} {$data->row->place} <br /> {$data->row->address}";
    	} else {
    		$deliveryAddress = $data->row->contragentAddress;
    	}
    	
    	core_Lg::push($data->rec->tplLang);
    	$deliveryAddress = core_Lg::transliterate($deliveryAddress);
    	
    	$tpl->replace($deliveryAddress, 'deliveryAddress');
    	core_Lg::pop();
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = NULL)
    {
    	core_Lg::push($rec->tplLang);
    	
    	if($row->pCode){
    		$row->deliveryTo .= $row->pCode;
    	}
    	
    	if($row->pCode){
    		$row->deliveryTo .= " " . core_Lg::transliterate($row->place);
    	}
    	
    	foreach(array('address', 'company', 'person', 'tel') as $fld){
    		if(!empty($rec->$fld)){
    			if($fld == 'address'){
    				$row->$fld = core_Lg::transliterate($row->$fld);
    			}
    			
    			$row->deliveryTo .= ", {$row->$fld}";
    		}
    	}
    	
    	if(isset($rec->locationId)){
    		$row->locationId = crm_Locations::getHyperLink($rec->locationId);
    	}
    	
    	core_Lg::pop();
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
        	$operation = $operations['delivery'];
        	$rec->accountId = $operation['debit'];
        	$rec->isReverse = (isset($operation['reverse'])) ? 'yes' : 'no';
        	
        	if($rec->locationId){
        		foreach (array('company','person','tel','country','pCode','place','address',) as $del){
        			 if($rec->$del){
        			 	$form->setError("locationId,{$del}", 'Не може да има избрана локация и въведени адресни данни');
        			 	break;
        			 }
        		}
        	}
        	
        	if((!empty($rec->tel) || !empty($rec->country)|| !empty($rec->pCode)|| !empty($rec->place)|| !empty($rec->address)) && (empty($rec->tel) || empty($rec->country)|| empty($rec->pCode)|| empty($rec->place)|| empty($rec->address))){
        		$form->setError('tel,country,pCode,place,address', 'Трябва или да са попълнени всички полета за адрес или нито едно');
        	}
        }
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function prepareShipments($data)
    {
    	$data->shipmentOrders = parent::prepareLineDetail($data->masterData);
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function renderShipments($data)
    {
    	if(count($data->shipmentOrders)){
    		$table = cls::get('core_TableView');
    		$fields = "rowNumb=№,docId=Документ,storeId=Склад,weight=Тегло,volume=Обем,palletCount=Палети,collection=Инкасиране,address=@Адрес";
    		 
    		return $table->get($data->shipmentOrders, $fields);
    	}
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашето експедиционно нареждане") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
	/**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Експедиционно нареждане', 
    					  'content' => 'store/tpl/SingleLayoutShipmentOrder.shtml', 'lang' => 'bg', 
    					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Експедиционно нареждане с цени', 
    					  'content' => 'store/tpl/SingleLayoutShipmentOrderPrices.shtml', 'lang' => 'bg',
    					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	$tplArr[] = array('name' => 'Packing list', 
    					  'content' => 'store/tpl/SingleLayoutPackagingList.shtml', 'lang' => 'en', 'oldName' => 'Packaging list',
    					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Експедиционно нареждане с декларация',
    					  'content' => 'store/tpl/SingleLayoutShipmentOrderDec.shtml', 'lang' => 'bg',
    					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Packing list with Declaration',
    					  'content' => 'store/tpl/SingleLayoutPackagingListDec.shtml', 'lang' => 'en', 'oldName' => 'Packaging list',
    					  'toggleFields' => array('masterFld' => NULL, 'store_ShipmentOrderDetails' => 'info,packagingId,packQuantity,weight,volume'));
    	
    	$res .= doc_TplManager::addOnce($this, $tplArr);
    }
     
     
	/**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("|Експедиционно нареждане|* №") . $rec->id;
    }
}