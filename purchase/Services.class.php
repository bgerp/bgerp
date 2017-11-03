<?php



/**
 * Клас 'purchase_Services'
 *
 * Мениджър на Приемателен протокол
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Services extends deals_ServiceMaster
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Приемателни протоколи';


    /**
     * Абревиатура
     */
    public $abbr = 'Pps';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, bgerp_DealIntf,acc_TransactionSourceIntf=purchase_transaction_Service';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, purchase_Wrapper, sales_plg_CalcPriceDelta, acc_plg_Contable, plg_Sorting,plg_Clone, doc_DocumentPlg, plg_Printing,
                    acc_plg_DocumentSummary,doc_EmailCreatePlg, bgerp_plg_Blank, cond_plg_DefaultValues, doc_plg_TplManager, doc_plg_HidePrices, doc_SharablePlg,cat_plg_AddSearchKeywords,plg_Search';

    
    /**
     * До потребители с кои роли може да се споделя документа
     *
     * @var string
     * @see doc_SharablePlg
     */
    public $shareUserRoles = 'ceo, purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, purchase';


    /**
	* Кой може да разглежда сингъла на документите?
	*/
    public $canSingle = 'ceo, purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo, purchase';
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo, purchaseMaster, manager';

       
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior,title=Документ, folderId, amountDelivered, createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'purchase_ServicesDetails';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приемателен протокол';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutServices.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.5|Логистика";
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior, contragentClassId, contragentId, locationId, deliveryTime, folderId, id';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'purchase_ServicesDetails';
    

    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'purchase_ServicesDetails';
    
    
    /**
     * Основна операция
     */
    protected static $defOperationSysId = 'buyServices';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array('delivered' => 'lastDocUser|lastDoc', 'template' => 'lastDocUser|lastDoc|defMethod');
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setServiceFields($this);
    }
    
    
	/**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
    	$tplArr[] = array('name' => 'Приемателен протокол за услуги', 
    					  'content' => 'purchase/tpl/SingleLayoutServices.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/SingleLayoutServicesNarrow.shtml',
    					  'toggleFields' => array('masterFld' => NULL, 'purchase_ServicesDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Приемателен протокол за услуги с цени', 
    					  'content' => 'purchase/tpl/SingleLayoutServicesPrices.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/SingleLayoutServicesPricesNarrow.shtml',
    					  'toggleFields' => array('masterFld' => NULL, 'purchase_ServicesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	$tplArr[] = array('name' => 'Acceptance protocol',
		    			'content' => 'purchase/tpl/SingleLayoutServicesEN.shtml', 'lang' => 'en',  'narrowContent' => 'purchase/tpl/SingleLayoutServicesNarrowEN.shtml',
		    			'toggleFields' => array('masterFld' => NULL, 'purchase_ServicesDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Acceptance protocol with prices',
		    			'content' => 'purchase/tpl/SingleLayoutServicesPricesEN.shtml', 'lang' => 'en',  'narrowContent' => 'purchase/tpl/SingleLayoutServicesPricesNarrowEN.shtml',
		    			'toggleFields' => array('masterFld' => NULL, 'purchase_ServicesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	 
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$dealInfo = static::getOrigin($data->form->rec)->getAggregateDealInfo();
    	$data->form->dealInfo = $dealInfo;
    	$data->form->setDefault('received', core_Users::getCurrent('names'));
    }
    
    
    /**
     * След изпращане на формата
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	if ($form->isSubmitted()) {
    		$rec = &$form->rec;
    		$dealInfo = $form->dealInfo;
    		$operations = $dealInfo->get('allowedShipmentOperations');
    		$operation = $operations[$mvc::$defOperationSysId];
    		
    		$rec->accountId = $operation['credit'];
    		$rec->isReverse = (isset($operation['reverse'])) ? 'yes' : 'no';
    	}
    }
}
