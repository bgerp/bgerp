<?php



/**
 * Фактури
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Invoices extends acc_InvoiceMaster
{
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, acc_TransactionSourceIntf=sales_transaction_Invoice, bgerp_DealIntf';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Inv';
    
    
    /**
     * Заглавие
     */
    public $title = 'Фактури за продажби';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Фактура';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, acc_plg_Contable, plg_ExportCsv, doc_DocumentPlg, bgerp_plg_Export,
					doc_EmailCreatePlg, doc_plg_MultiPrint, bgerp_plg_Blank, plg_Printing, cond_plg_DefaultValues,acc_plg_DpInvoice,
                    doc_plg_HidePrices, doc_plg_TplManager, acc_plg_DocumentSummary, plg_Search';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, number, date, place, folderId, dealValue, vatAmount, type';
    
    
    /**
     * Колоната, в която да се появят инструментите на plg_RowTools
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'sales_InvoiceDetails' ;
    
    
    /**
     * Старо име на класа
     */
    public $oldClassName = 'acc_Invoices';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,invoicer';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,invoicer';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,invoicer';
	
	
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,invoicer';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canExport = 'ceo,salesMaster';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo,invoicer';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number, folderId, id, id';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/invoice.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.3|Търговия";
    
    
    /**
     * Кой е основния детайл
     */
    protected $mainDetail = 'sales_InvoiceDetails';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	parent::setInvoiceFields($this);
    	
    	$this->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=bankAccountId, allowEmpty)', 'caption=Плащане->Банкова с-ка,after=paymentMethodId');
    	
    	$this->FLD('number', 'int', 'caption=Номер, export=Csv, after=place');
    	$this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 'caption=Статус, input=none,export=Csv');
        $this->FLD('type', 'enum(invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие)', 'caption=Вид, input=hidden');
        
        $this->FLD('docType', 'class(interface=bgerp_DealAggregatorIntf)', 'input=hidden,silent');
        $this->FLD('docId', 'int', 'input=hidden,silent');
        
        $this->setDbUnique('number');
    }
	
	
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Фактура нормален изглед', 'content' => 'sales/tpl/InvoiceHeaderNormal.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Фактура изглед за писмо', 'content' => 'sales/tpl/InvoiceHeaderLetter.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Фактура кратък изглед', 'content' => 'sales/tpl/InvoiceHeaderNormalShort.shtml', 'lang' => 'bg');
        
        $res .= doc_TplManager::addOnce($mvc, $tplArr);
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	parent::prepareInvoiceForm($mvc, $data);
    	
    	if(!haveRole('ceo,acc')){
    		$form->setField('number', 'input=none');
    	}
    	
    	if($data->aggregateInfo){
    		if($accId = $data->aggregateInfo->get('bankAccountId')){
    			$form->rec->accountId = bank_OwnAccounts::fetchField("#bankAccountId = {$accId}", 'id');
    		}
    	}
    	 
    	if(empty($data->flag)){
    		if($ownAcc = bank_OwnAccounts::getCurrent('id', FALSE)){
    			$form->setDefault('accountId', $ownAcc);
    		}
    	}
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	parent::inputInvoiceForm($mvc, $form);
    	
    	if ($form->isSubmitted()) {
        	$rec = &$form->rec;
	        
	        if($rec->number){
		        if(!$mvc->isNumberInRange($rec->number)){
					$form->setError('number', "Номер '{$rec->number}' е извън позволения интервал");
				}
	        }
        }
	}
    
    
    /**
     * Намира ориджина на фактурата (ако има)
     */
    public static function getOrigin($rec)
    {
    	$origin = NULL;
    	$rec = static::fetchRec($rec);
    	
    	if($rec->originId) {
    		return doc_Containers::getDocument($rec->originId);
    	}
    	
    	if($rec->docType && $rec->docId) {
    		// Ако се генерира от пос продажба
    		return new core_ObjectReference($rec->docType, $rec->docId);
    	}
    	
    	if($rec->threadId){
    		return doc_Threads::getFirstDocument($rec->threadId);
	    }
    	
    	return $origin;
    }
    
    
    /**
     * Валидиране на полето 'number' - номер на фактурата
     * 
     * Предупреждение при липса на ф-ра с номер едно по-малко от въведения.
     */
    public function on_ValidateNumber(core_Mvc $mvc, $rec, core_Form $form)
    {
        if (empty($rec->number)) {
            return;
        }
        
        $prevNumber = intval($rec->number)-1;
        if (!$mvc->fetchField("#number = {$prevNumber}")) {
            $form->setWarning('number', 'Липсва фактура с предходния номер!');
        }
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
        parent::beforeInvoiceSave($rec);
    	
        if($rec->state == 'active'){
        	if(empty($rec->number)){
        		$rec->number = self::getNexNumber();
        		$rec->searchKeywords .= " " . plg_Search::normalizeText($rec->number);
        	}
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	if(!Mode::is('printing')){
    		$tpl->replace(tr('ОРИГИНАЛ') . "/<i>ORIGINAL</i>", 'INV_STATUS');
    	}
    	 
    	$tpl->push('sales/tpl/invoiceStyles.css', 'CSS');
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	if($rec->type == 'invoice' && $rec->state == 'active' && $rec->dpOperation != 'accrued'){
    		
    		if(dec_Declarations::haveRightFor('add')){
    			$data->toolbar->addBtn('Декларация', array('dec_Declarations', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/declarations.png, row=2');
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	parent::getVerbalInvoice($mvc, $rec, $row, $fields);
    	
    	if($fields['-single']){
			$row->type .= " / <i>" . str_replace('_', " ", $rec->type) . "</i>";
    		
    		if($rec->docType && $rec->docId){
    			$row->POS = tr("|към ПОС продажба|* №{$rec->docId}");
    		}
    		
    		if($rec->accountId){
    			$Varchar = cls::get('type_Varchar');
    			$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->accountId);
    			$row->bank = $Varchar->toVerbal($ownAcc->bank);
    			$row->bic = $Varchar->toVerbal($ownAcc->bic);
    		}
    	}
    }


    /*
     * Реализация на интерфейса doc_DocumentIntf
     */
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        if(Request::get('docType', 'int') && Request::get('docId', 'int')){
        	return TRUE;
        }
        
    	return FALSE;
    }
    
    
   /**
    * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
    */
    public static function getHandle($id)
    {
        $self = cls::get(get_called_class());
        $number = static::fetchField($id, 'number');
        $number = str_pad($number, '10', '0', STR_PAD_LEFT);
        
        return $self->abbr . $number;
    } 
    
    
   /**
    * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
    */
    public static function fetchByHandle($parsedHandle)
    {
    	$number = ltrim($parsedHandle['id'], '0');
    	
        return static::fetch("#number = '{$number}'");
    }
    
    
    /**
     * Дали подадения номер е в позволения диапазон за номера на фактури
     * @param $number - номера на фактурата
     */
    private function isNumberInRange($number)
    {
    	expect($number);
    	$conf = core_Packs::getConfig('sales');
    	
    	return ($conf->SALE_INV_MIN_NUMBER <= $number && $number <= $conf->SALE_INV_MAX_NUMBER);
    }
    
    
    /**
     * Ф-я връщаща следващия номер на фактурата, ако той е в границите
     * @return int - следващия номер на фактура
     */
    protected static function getNexNumber()
    {
    	$conf = core_Packs::getConfig('sales');
    	
    	$query = static::getQuery();
    	$query->XPR('maxNum', 'int', 'MAX(#number)');
    	if(!$maxNum = $query->fetch()->maxNum){
    		$maxNum = $conf->SALE_INV_MIN_NUMBER;
    	}
    	$nextNum = $maxNum + 1;
    	
    	if($nextNum > $conf->SALE_INV_MAX_NUMBER) return NULL;
    	
    	return $nextNum;
    }
    
    
	/**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        // Ако резултата е 'no_one' пропускане
    	if($res == 'no_one') return;
    	
    	if($action == 'add' && isset($rec->threadId)){
    		 $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    		 $docState = $firstDoc->fetchField('state');
    		 if(!($firstDoc->instance instanceof sales_Sales && $docState == 'active')){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След рендиране на копия за принтиране
     * @see doc_plg_MultiPrint
     * 
     * @param core_Mvc $mvc - мениджър
     * @param core_ET $copyTpl - копие за рендиране
     * @param int $copyNum - пореден брой на копието за принтиране
     */
    public static function on_AfterRenderPrintCopy($mvc, &$copyTpl, $copyNum)
    {
    	$inv_status = ($copyNum == '1') ? tr('ОРИГИНАЛ') . "/<i>ORIGINAL</i>" : tr('КОПИЕ') . "/<i>COPY</i>";
    	$copyTpl->replace($inv_status, 'INV_STATUS');
    }
    
    
    /**
     * Преди експортиране като CSV
     */
   	public static function on_BeforeExportCsv($mvc, &$rec)
   	{
   		$rec->number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
   		$rec->dealValue = round($rec->dealValue + $rec->vatAmount - $rec->discountAmount, 2);
   		$rec->state = $mvc->getVerbal($rec, 'state');
   	}
   	
   	
   	/**
   	 * След подготвяне на заявката за експорт
   	 */
   	public static function on_AfterPrepareExportQuery($mvc, &$query)
   	{
   		$query->orWhere("#state = 'rejected' AND #brState = 'active'");
   		$query->where("#state != 'draft'");
   	}
}