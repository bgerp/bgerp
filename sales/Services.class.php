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
 * @copyright 2006 - 2014 Experta OOD
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
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, bgerp_DealIntf, acc_TransactionSourceIntf=sales_transaction_Service';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, acc_plg_Contable, doc_DocumentPlg, plg_Printing,
                    plg_ExportCsv, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, cond_plg_DefaultValues, doc_plg_TplManager, doc_plg_HidePrices, plg_LastUsedKeys';

    
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
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, valior,title=Документ, folderId, amountDeliveredVat, createdOn, createdBy';


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
    					  'content' => 'sales/tpl/SingleLayoutServices.shtml', 'lang' => 'bg', 
    					  'toggleFields' => array('masterFld' => NULL, 'sales_ServicesDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Протокол за извършени услуги с цени', 
    					  'content' => 'sales/tpl/SingleLayoutServicesPrices.shtml', 'lang' => 'bg',
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
}