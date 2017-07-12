<?php
/**
 * Клас 'sales_Services'
 *
 * Мениджър на Предавателен протокол
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Services extends deals_ServiceMaster
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Предавателни протоколи';


    /**
     * Абревиатура
     */
    public $abbr = 'Pss';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, bgerp_DealIntf, acc_TransactionSourceIntf=sales_transaction_Service,deals_InvoiceSourceIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, sales_Wrapper, sales_plg_CalcPriceDelta,plg_Sorting, acc_plg_Contable, doc_DocumentPlg, plg_Printing,
                    acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, cond_plg_DefaultValues, doc_plg_TplManager, doc_plg_HidePrices, doc_SharablePlg';

    
    /**
     * До потребители с кои роли може да се споделя документа
     *
     * @var string
     * @see doc_SharablePlg
     */
    public $shareUserRoles = 'ceo, sales';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кои роли могат да филтрират потребителите по екип в листовия изглед
	 */
	public $filterRolesForTeam = 'ceo,salesMaster,manager';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior,title=Документ, folderId, amountDeliveredVat, createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'sales_ServicesDetails';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Предавателен протокол';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutServices.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.81|Търговия";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDelivered';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Кой може да сторнира
     */
    public $canRevert = 'salesMaster, ceo';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'locationId, note, id';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'sales_ServicesDetails';
    
    
    /**
     * Основна операция
     */
    protected static $defOperationSysId = 'deliveryService';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    		'received' => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,deliveryTime,modifiedOn';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setServiceFields($this);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$data->form->setDefault('delivered', core_Users::getCurrent('names'));
    }
    
    
	/**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Протокол за извършени услуги', 
    					  'content' => 'sales/tpl/SingleLayoutServices.shtml', 'lang' => 'bg', 'narrowContent' =>  'sales/tpl/SingleLayoutServicesNarrow.shtml',
    					  'toggleFields' => array('masterFld' => NULL, 'sales_ServicesDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Протокол за извършени услуги с цени', 
    					  'content' => 'sales/tpl/SingleLayoutServicesPrices.shtml', 'lang' => 'bg', 'narrowContent' =>  'sales/tpl/SingleLayoutServicesPricesNarrow.shtml',
    					  'toggleFields' => array('masterFld' => NULL, 'sales_ServicesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	$tplArr[] = array('name' => 'Delivery protocol',
		    			  'content' => 'sales/tpl/SingleLayoutServicesEN.shtml', 'lang' => 'en', 'narrowContent' =>  'sales/tpl/SingleLayoutServicesNarrowEN.shtml',
		    			  'toggleFields' => array('masterFld' => NULL, 'sales_ServicesDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Delivery protocol with prices',
		    			  'content' => 'sales/tpl/SingleLayoutServicesPricesEN.shtml', 'lang' => 'en', 'narrowContent' =>  'sales/tpl/SingleLayoutServicesPricesNarrowEN.shtml',
		    			  'toggleFields' => array('masterFld' => NULL, 'sales_ServicesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
       
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
     
     
	/**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("|Предавателен протокол|* №") . $rec->id;
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
    		$operation = $operations[$mvc::$defOperationSysId];
    		$rec->accountId = $operation['debit'];
    		$rec->isReverse = (isset($operation['reverse'])) ? 'yes' : 'no';
    	}
    }
    
    
    /**
     * Артикули които да се заредят във фактурата/проформата, когато е създадена от
     * определен документ
     *
     * @param mixed $id - ид или запис на документа
     * @param deals_InvoiceMaster $forMvc - клас наследник на deals_InvoiceMaster в който ще наливаме детайлите
     * @return array $details - масив с артикули готови за запис
     * 				  o productId      - ид на артикул
     * 				  o packagingId    - ид на опаковка/основна мярка
     * 				  o quantity       - количество опаковка
     * 				  o quantityInPack - количество в опаковката
     * 				  o discount       - отстъпка
     * 				  o price          - цена за единица от основната мярка
     */
    public function getDetailsFromSource($id, deals_InvoiceMaster $forMvc)
    {
    	$details = array();
    	$rec = static::fetchRec($id);
    	
    	$query = sales_ServicesDetails::getQuery();
    	$query->where("#shipmentId = {$rec->id}");
    	while($dRec = $query->fetch()){
    		$dRec->quantity /= $dRec->quantityInPack;
    		if(!($forMvc instanceof sales_Proformas)){
    			$dRec->price -= $dRec->price * $dRec->discount;
    			unset($dRec->discount);
    		}
    		
    		unset($dRec->id);
    		unset($dRec->shipmentId);
    		unset($dRec->createdOn);
    		unset($dRec->createdBy);
    		$details[] = $dRec;
    	}
    	
    	return $details;
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
    		
    		// Към чернова може да се генерират проформи, а към контиран фактури
    		if($rec->state == 'draft'){
    			
    			// Ако има проформа към протокола, правим линк към нея, иначе бутон за създаване на нова
    			if($iRec = sales_Proformas::fetch("#sourceContainerId = {$rec->containerId} AND #state != 'rejected'")){
    				if(sales_Proformas::haveRightFor('single', $iRec)){
    					$arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
    					$data->toolbar->addBtn("Проформа|* {$arrow}", array('sales_Proformas', 'single', $iRec->id, 'ret_url' => TRUE), 'title=Отваряне на проформа фактура издадена към предавателния протокол,ef_icon=img/16/proforma.png');
    				}
    			} else {
    				if(sales_Proformas::haveRightFor('add', (object)array('threadId' => $rec->threadId, 'sourceContainerId' => $rec->containerId))){
    					$data->toolbar->addBtn('Проформа', array('sales_Proformas', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'ret_url' => TRUE), 'title=Създаване на проформа фактура към предавателния протокол,ef_icon=img/16/proforma.png');
    				}
    			}
    		} elseif($rec->state == 'active'){
    			
    			// Ако има фактура към протокола, правим линк към нея, иначе бутон за създаване на нова
    			if($iRec = sales_Invoices::fetch("#sourceContainerId = {$rec->containerId} AND #state != 'rejected'")){
    				if(sales_Invoices::haveRightFor('single', $iRec)){
    					$arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
    					$data->toolbar->addBtn("Фактура|* {$arrow}", array('sales_Invoices', 'single', $iRec->id, 'ret_url' => TRUE), 'title=Отваряне на фактурата издадена към предавателния протокол,ef_icon=img/16/invoice.png');
    				}
    			} else {
    				if(sales_Invoices::haveRightFor('add', (object)array('threadId' => $rec->threadId, 'sourceContainerId' => $rec->containerId))){
    					$data->toolbar->addBtn('Фактура', array('sales_Invoices', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'ret_url' => TRUE), 'title=Създаване на фактура към предавателния протокол,ef_icon=img/16/invoice.png');
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Преди запис на документ
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(empty($rec->originId)){
    		$rec->originId = doc_Threads::getFirstContainerId($rec->threadId);
    	}
    }
}
