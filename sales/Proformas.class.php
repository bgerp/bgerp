<?php



/**
 * Документ "Проформа фактура"
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Proformas extends deals_InvoiceMaster
{
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Prf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Проформа фактури';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Проформа фактура';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, cond_plg_DefaultValues, plg_Sorting, doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, Sale=sales_Sales,
                    doc_plg_HidePrices, doc_plg_TplManager, doc_ActivatePlg';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'sales_ProformaDetails' ;
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,salesMaster,manager';
    
    
    /**
     * Кой е основния детайл
     */
    protected $mainDetail = 'sales_ProformaDetails';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    
    
	/**
	 * Поле за единичния изглед
	 */
	public $rowToolsSingleField = 'number';
	
	
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number, folderId, id, contragentName';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutProforma.shtml';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'sales_Proforma';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/invoice.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.8|Търговия";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, number, date, place, folderId, dealValue, vatAmount';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    	'place'               => 'lastDocUser|lastDoc',
    	'responsible'         => 'lastDocUser|lastDoc',
    	'contragentCountryId' => 'lastDocUser|lastDoc|clientData',
    	'contragentVatNo'     => 'lastDocUser|lastDoc|clientData',
    	'uicNo' 			  => 'lastDocUser|lastDoc|clientData',
    	'contragentPCode'     => 'lastDocUser|lastDoc|clientData',
    	'contragentPlace'     => 'lastDocUser|lastDoc|clientData',
    	'contragentAddress'   => 'lastDocUser|lastDoc|clientData',
    	'accountId' 		  => 'lastDocUser|lastDoc',
    	'template' 		      => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	parent::setInvoiceFields($this);
    	 
    	$this->FLD('saleId', 'key(mvc=sales_Sales)', 'caption=Продажба,input=none');
    	$this->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=bankAccountId, allowEmpty)', 'caption=Плащане->Банкова с-ка,after=paymentMethodId');
    	$this->FLD('state', 'enum(draft=Чернова, active=Активиран, rejected=Оттеглен)', 'caption=Статус, input=none');
    	$this->FLD('number', 'int', 'caption=Номер, export=Csv, after=place');
    
    	$this->setDbUnique('number');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Проформа', 'content' => 'sales/tpl/SingleLayoutProforma.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Pro forma', 'content' => 'sales/tpl/SingleLayoutProformaEn.shtml', 'lang' => 'en');
    	
    	$res = '';
    	$res .= doc_TplManager::addOnce($this, $tplArr);
    
    	return $res;
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	parent::prepareInvoiceForm($mvc, $data);
    	
    	foreach (array('responsible', 'contragentPCode', 'contragentPlace', 'contragentAddress', 'paymentMethodId', 'deliveryPlaceId', 'vatDate', 'vatReason', 'contragentCountryId', 'contragentName') as $fld){
    		$data->form->setField($fld, 'input=hidden');
    	}
    	
    	if(!haveRole('ceo,acc')){
    		$data->form->setField('number', 'input=none');
    	}
    	 
    	if($data->aggregateInfo){
    		if($accId = $data->aggregateInfo->get('bankAccountId')){
    			$data->form->rec->accountId = bank_OwnAccounts::fetchField("#bankAccountId = {$accId}", 'id');
    		}
    	}
    	
    	if(empty($data->flag)){
    		if($ownAcc = bank_OwnAccounts::getCurrent('id', FALSE)){
    			$data->form->setDefault('accountId', $ownAcc);
    		}
    	}
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	parent::inputInvoiceForm($mvc, $form);
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
    	if(isset($rec->id) && empty($rec->folderId)){
    		$rec->folderId = $mvc->fetchField($rec->id, 'folderId');
    	}
    	
    	parent::beforeInvoiceSave($rec);
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if(empty($rec->number)){
    		$rec->number = $rec->id;
    		$mvc->save($rec, 'number');
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    public static function on_AfterPrepareEditToolbar($mvc, $data)
    {
    	if (!empty($data->form->toolbar->buttons['activate'])) {
    		$data->form->toolbar->removeBtn('activate');
    	}
    	
    	if (!empty($data->form->toolbar->buttons['btnNewThread'])) {
    		$data->form->toolbar->removeBtn('btnNewThread');
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	parent::getVerbalInvoice($mvc, $rec, $row, $fields);

    	if($fields['-single']){
    
    		if($rec->accountId){
    			$Varchar = cls::get('type_Varchar');
    			$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->accountId);
    			$row->bank = $Varchar->toVerbal($ownAcc->bank);
    			$row->bic = $Varchar->toVerbal($ownAcc->bic);
    		}
    	}
    }
    
    
    /**
     * Подготвя продуктите от ориджина за запис в детайла на модела
     */
    protected static function prepareProductFromOrigin($mvc, $rec, $agreed, $products, $invoiced, $packs)
    {
    	if(count($agreed)){
    		
    		// Записваме информацията за продуктите в детайла
    		foreach ($agreed as $product){
    			
    			$diff = $product->quantity;
    			$product->price *= 1 - $product->discount;
    			unset($product->discount);
    			$mvc::saveProductFromOrigin($mvc, $rec, $product, $packs, $diff);
    		}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	return FALSE;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
    	$firstDocument = doc_Threads::getFirstDocument($threadId);
    	
    	if(!$firstDocument) return FALSE;
    	
    	// Може да се добавя само към активна продажба
    	if($firstDocument->getInstance() instanceof sales_Sales && $firstDocument->fetchField('state') == 'active'){
    		
    		return TRUE;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tpl->push('sales/tpl/invoiceStyles.css', 'CSS');
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	return tr("|Проформа фактура|* №") . $rec->id;
    }
}