<?php



/**
 * Фактури
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Invoices extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, 
                        acc_TransactionSourceIntf, bgerp_DealIntf';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Inv';
    
    
    /**
     * Заглавие
     */
    var $title = 'Фактури за продажби';
    
    
    /**
     * @todo Чака за документация...
     */
    var $singleTitle = 'Фактура';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, doc_DocumentPlg, plg_ExportCsv,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, doc_ActivatePlg,
                    doc_SequencerPlg, doc_plg_BusinessDoc, acc_plg_Contable';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'number, vatDate, contragentName, contragentVatNo, contragentCountryId ';
    
    
    /**
     * Колоната, в която да се появят инструментите на plg_RowTools
     */
    public $rowToolsField = 'number';
    
     
    /**
     * Детайла, на модела
     */
    var $details = 'sales_InvoiceDetails' ;
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, sales';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo, sales';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo, sales';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'sales/tpl/SingleLayoutInvoice.shtml';
    
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'number, date, contragentName';
    
    
    /**
     * Име на полето съдържащо номер на фактурата
     */
    var $sequencerField = 'number';
    
    
    /**
     * 
     * Икона за фактура
     */
    var $singleIcon = 'img/16/invoice.png';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "3.3|Търговия";
    
    
    /**
     * SystemId на номенклатура "Клиенти"
     */
    const CLIENTS_ACC_LIST = 'clients';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('date', 'date(format=d.m.Y)', 'caption=Дата,  notNull, mandatory');
        $this->FLD('place', 'varchar(64)', 'caption=Място, mandatory');
        $this->FLD('number', 'int', 'caption=Номер, export=Csv');
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        $this->FLD('contragentName', 'varchar', 'caption=Получател->Име, mandatory');
        $this->FLD('responsible', 'varchar(255)', 'caption=Получател->Отговорник');
        $this->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)', 'caption=Получател->Държава,mandatory');
        $this->FLD('contragentVatNo', 'drdata_VatType', 'caption=Получател->ЕИК/VAT №, mandatory');
        $this->FLD('contragentPCode', 'varchar(16)', 'caption=Получател->П. код,recently,class=pCode');
        $this->FLD('contragentPlace', 'varchar(64)', 'caption=Получател->Град,class=contactData');
        $this->FLD('contragentAddress', 'varchar(255)', 'caption=Получател->Адрес,class=contactData');
        $this->FLD('changeAmount', 'double(decimals=2)', 'input=none,width=10em');
        $this->FLD('paymentMethodId', 'key(mvc=salecond_PaymentMethods, select=name)', 'caption=Плащане->Начин');
        $this->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=bankAccountId, allowEmpty)', 'caption=Плащане->Банкова с-ка, width:100%, export=Csv');
		$this->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Плащане->Каса');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута->Код,width=6em');
        $this->FLD('rate', 'double(decimals=2)', 'caption=Валута->Курс,width=6em'); 
        $this->FLD('deliveryId', 'key(mvc=salecond_DeliveryTerms, select=codeName, allowEmpty)', 'caption=Доставка->Условие');
        $this->FLD('deliveryPlaceId', 'key(mvc=crm_Locations, select=title)', 'caption=Доставка->Място');
        $this->FLD('vatDate', 'date(format=d.m.Y)', 'caption=Данъци->Дата на ДС');
        $this->FLD('vatRate', 'enum(yes=с начисляване,freed=освободено,export=без начисляване)', 'caption=Данъци->ДДС %');
        $this->FLD('vatReason', 'varchar(255)', 'caption=Данъци->Основание'); // TODO plg_Recently
		$this->FLD('reason', 'text(rows=2)', 'caption=Основание, input=none');
        $this->FLD('additionalInfo', 'richtext(bucket=Notes, rows=6)', 'caption=Допълнително->Бележки,width:100%');
        $this->FLD('dealValue', 'double(decimals=2)', 'caption=Стойност, input=hidden');
        $this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
        
        $this->FLD('type', 
            'enum(invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие)', 
            'caption=Вид, input=hidden,silent'
        );
        
        $this->FLD('docType', 'class(interface=store_ShipmentIntf)', 'input=hidden,silent');
        $this->FLD('docId', 'int', 'input=hidden,silent');
        
        $this->setDbUnique('number');
    }
    
    
    /**
     * Преизчисляваме сумата на фактурата
     * @param int $id - ид на фактурата
     */
    public function updateInvoice($id)
    {
    	$rec = $this->fetch($id);
    	$rec->dealValue = 0;
    	$detaiLQuery = sales_InvoiceDetails::getQuery();
    	$detaiLQuery->where("#invoiceId = {$id}");
    	while($detail = $detaiLQuery->fetch()){
    		$rec->dealValue += $detail->amount;
    	}
    	
    	$this->save($rec);
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	if(!$type = Request::get('type')) return;
    	if($type == 'debit_note') {
    		$title = 'Дебитно известие';
    	} elseif($type == 'credit_note'){
    		$title = 'Кредитно известие';
    	} 
    	$mvc->singleTitle = $title;
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        
        if (!$form->rec->id) {
            $type = Request::get('type');
	        if(!$type){
	        	$form->setDefault('type', 'invoice');
	        }
        	// При създаване на нова ф-ра зареждаме полетата на 
            // формата с разумни стойности по подразбиране.
            $mvc::setFormDefaults($form);
            
        if($type && $type != 'invoice'){
	        	$form->setField('reason', 'input');
	        	$form->setField('changeAmount', 'input');
	        	($type == 'debit_note') ? $caption = 'Увеличение':$caption = 'Намаляване';
	        	
	        	$form->setReadOnly('currencyId');
	        	$form->setReadOnly('contragentName');
	        	$form->setReadOnly('contragentVatNo');
	        	$form->setReadOnly('contragentCountryId');
	        	$form->setField('changeAmount', "caption=Плащане->{$caption}");
	        }
        }
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
           if(!$form->rec->rate){
        		$form->rec->rate = round(currency_CurrencyRates::getRate($form->rec->date, $form->rec->currencyId, NULL), 4);
        	}
        
    		if(!currency_CurrencyRates::hasDeviation($form->rec->rate, $form->rec->date, $form->rec->currencyId, NULL)){
		    	$form->setWarning('rate', 'Въведения курс има много голяма разлика спрямо очакваната');
			}
		    	
        	$Vats = cls::get('drdata_Vats');
        	$form->rec->contragentVatNo = $Vats->canonize($form->rec->contragentVatNo);
        	return;
        }

        acc_Periods::checkDocumentDate($form);

        foreach ($mvc->fields as $fName=>$field) {
            $mvc->invoke('Validate' . ucfirst($fName), array($form->rec, $form));
        }
	}
	
	
	/**
	 * Генерира фактура от пораждащ документ: може да се породи от:
	 * 1. Продажба (@see sales_Sales)
	 * 2. POS Продажба (@see pos_Receipts)
	 * 3. Фактура (@see sales_Invoices) - тоест се прави ДИ или КИ
	 */
	public static function on_AfterCreate($mvc, $rec)
    {
    	if($rec->docType && $rec->docId) {
    		
    		// Ако се генерира от пос продажба
    		$origin = cls::get($rec->docType);
    		$products = $origin->getShipmentProducts($rec->docId);
    	} else {
    		try{
    			$origin = static::getOrigin($rec, 'store_ShipmentIntf');
    		} catch(Exception $e){
    			//Ако фактурата е начало на нишка то getOrigin  ще даде грешка
    			return;
    		}
    		if(cls::haveInterface('store_ShipmentIntf', $origin->className)){
    			$products = $origin->getShipmentProducts();
    		}
    	}
    	
    	if(count($products) != 0){
	    	
    		// Записваме информацията за продуктите в детайла
	    	foreach ($products as $product){
	    		$dRec = clone $product;
	    		$dRec->invoiceId = $rec->id;
	    		$dRec->packQuantity = $product->quantity * $product->quantityInPack;
	    		$dRec->amount = $dRec->packQuantity * $product->price;
	    		$mvc->sales_InvoiceDetails->save($dRec);
	    	}
    	}
    }

    
    /**
     * Помощна функция за прилагане на увеличение/намаляване на
     * сумата на фактурата
     * @param array $products - списък от продукти за ДИ или КИ
     * @param stdClass $rec - запис на ДИ или КИ
     */
    public function applyAmountChange($products, $rec)
    {
    	if(!$rec->dealValue) return;
    	$rec->changeAmount = (($rec->type == 'debit_note') ? 1 : -1) * $rec->changeAmount;
    	
    	foreach($products as $product){
    		$queficient = round($product->amount / $rec->dealValue, 4);
    		unset($product->id);
    		$product->invoiceId = $rec->id;
    		$product->amount = $rec->changeAmount * $queficient;
    		sales_InvoiceDetails::save($product);
    	}
    }
    
    
    /**
     * Валидиране на полето 'date' - дата на фактурата
     * 
     * Предупреждение ако има фактура с по-нова дата (само при update!)
     */
    public function on_ValidateDate(core_Mvc $mvc, $rec, core_Form $form)
    {
        if (!empty($rec->id)) {
            // Промяна на съществуваща ф-ра - не правим нищо
            return;
        }
        
        $query = $mvc->getQuery();
        $query->where("#state != 'rejected'");
        $query->orderBy('date', 'DESC');
        $query->limit(1);
        
        if (!$newestInvoiceRec = $query->fetch()) {
            // Няма ф-ри в състояние различно от rejected
            return;
        }
        
        if ($newestInvoiceRec->date > $rec->date) {
            // Най-новата валидна ф-ра в БД е по-нова от настоящата.
            $form->setWarning('date', 
                'Има фактура с по-нова дата (от|* ' . 
                    dt::mysql2verbal($newestInvoiceRec->date, 'd.m.y') .
                ')'
            );
        }
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
     * Валидиране на полето 'vatDate' - дата на данъчно събитие (ДС)
     * 
     * Грешка ако ДС е след датата на фактурата или на повече от 5 дни преди тази дата.
     */
    public function on_ValidateVatDate(core_Mvc $mvc, $rec, core_Form $form)
    {
        if (empty($rec->vatDate)) {
            return;
        }
        
        // Датата на ДС не може да бъде след датата на фактурата, нито на повече от 5 дни преди нея.
        if ($rec->vatDate > $rec->date || dt::addDays(5, $rec->vatDate) < $rec->date) {
            $form->setError('vatDate', 'Данъчното събитие трябва да е до 5 дни <b>преди</b> датата на фактурата');
        }
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
        if (empty($rec->vatDate)) {
            $rec->vatDate = $rec->date;
        }
            
        if (!empty($rec->folderId)) {
            $rec->contragentClassId  = doc_Folders::fetchCoverClassName($rec->folderId);
            $rec->contragentId     = doc_Folders::fetchCoverId($rec->folderId);
        }
    }
    
    
    /**
     * Попълване на шаблона на единичния изглед с данни на доставчика (Моята фирма)
     */
    public function on_AfterRenderSingle($mvc, core_ET $tpl)
    {
        $ownCompanyData = crm_Companies::fetchOwnCompany();

        $address = trim($ownCompanyData->place . ' ' . $ownCompanyData->pCode);
        if ($address && !empty($ownCompanyData->address)) {
            $address .= '<br/>' . $ownCompanyData->address;
        }  
        
        $tpl->replace($ownCompanyData->company, 'MyCompany');
        $tpl->replace($ownCompanyData->country, 'MyCountry');
        $tpl->replace($address, 'MyAddress');
        
        $uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if($uic != $ownCompanyData->vatNo){
    		$tpl->replace($ownCompanyData->vatNo, 'MyCompanyVatNo');
    	} 
    	$tpl->replace($uic, 'uicId');
        $tpl->push('sales/tpl/invoiceStyles.css', 'CSS');
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-single']){
    		$mvc::prepareAdditionalInfo($rec);
    	}
    }
    
    
	/**
     * Подготвя шаблона за единичния изглед
     */
    function renderSingleLayout_(&$data)
    {
    	$conf = core_Packs::getConfig('sales');
    	
    	if($conf->INV_LAYOUT){
    		return getTplFromFile($conf->INV_LAYOUT . ".shtml");
    	}
        
    	return parent::renderSingleLayout_($data);
    }
    
    
    /**
     * Помощна функция за добавяне на допълнителна информация
     * към $rec-a относно ДДС, то данъчната основа и други данни
     */
    private static function prepareAdditionalInfo(&$rec)
    {
    	if($rec->dealValue  && $rec->rate){
	    	$rec->baseAmount = $rec->dealValue;
	    	$rec->dealValue = round($rec->dealValue / $rec->rate, 2);
	    	$rec->vatPercent = $rec->vatAmount = 0;
	    	if($rec->vatRate == 'yes'){
	    		$period = acc_Periods::fetchByDate($rec->date);
	    		$rec->vatAmount = $rec->baseAmount * $period->vatRate;
				$rec->vatPercent = $period->vatRate;
			}
			$rec->total = round(($rec->baseAmount + $rec->vatAmount) / $rec->rate, 2);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($rec->docType && $rec->docId){
    		$row->POS = tr("|към ПОС продажба|* №{$rec->docId}");
    	}
    	
    	if($rec->originId && $rec->type != 'invoice'){
    		$origin = doc_Containers::getDocument($rec->originId);
    		$row->origin = $origin->getHandle();
    		$row->invDate = $origin->recToVerbal()->date;
    	}
    	
    	switch($rec->type){
    		case 'invoice':
    			$row->type .= " / <i>Invoice</i>";
    			break;
    		case 'debit_note':
    			$row->type .= " / <i>Debit Note</i>";
    			break;
    		case 'credit_note':
    			$row->type .= " / <i>Credit Note</i>";
    			break;
    	}
    	
    	$row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->date);
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	if($fields['-single']){
    		
    		// Ако е подаден Ват номер, намираме ЕИК-то от него
    		$uic = drdata_Vats::getUicByVatNo($rec->contragentVatNo);
    		if($uic == $rec->contragentVatNo){
    			unset($row->contragentVatNo);
    		}
    		$row->contragentUiC = $uic;
    		
    		// Номера се форматира в десеторазряден вид
    		$row->number = str_pad($row->number, '10', '0', STR_PAD_LEFT);
    		
	    	if($rec->dealValue){
	    		$row->baseAmount = $double->toVerbal($rec->baseAmount);
	    		
	    		$percent = cls::get('type_Percent');
	    		$parts = explode(".", $rec->vatPercent);
	    		$percent->params['decimals'] = count($parts[1]);
	    		
				$row->vatPercent = $percent->toVerbal($rec->vatPercent);
				$row->vatAmount = $double->toVerbal($rec->vatAmount);
				$row->total = $double->toVerbal($rec->total);
				
				$SpellNumber = cls::get('core_SpellNumber');
				$row->amountVerbal = $SpellNumber->asCurrency($rec->total, 'bg');
	    	}
	    	
	    	if($rec->accountId){
	    		$varchar = cls::get('type_Varchar');
	    		$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->accountId);
	    		$row->bank = $varchar->toVerbal($ownAcc->bank);
	    		$row->bic = $varchar->toVerbal($ownAcc->bic);
	    	}
	    	
	    	if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . " №<b>{$row->number}</b> ({$row->state})" ;
	    	}
	    	$username = core_Users::fetch($rec->createdBy);
			$row->username = core_Users::recToVerbal($username, 'names')->names;
    	}
    }
    
    
	/**
     * След подготовка на тулбара на единичен изглед.
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	
    	if($rec->type == 'invoice' && $rec->state == 'active' && $rec->dealValue){
    		
    		$data->toolbar->addBtn('ДИ', array($mvc, 'add', 'originId' => $rec->containerId, 'type' => 'debit_note'), 'ef_icon=img/16/layout_join_vertical.png,title=Дебитно известие');
    		$data->toolbar->addBtn('КИ', array($mvc, 'add','originId' => $rec->containerId, 'type' => 'credit_note'), 'ef_icon=img/16/layout_split_vertical.png,title=Кредитно известие');
    		$data->toolbar->addBtn('Декларация', array('dec_Declarations', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), 'ef_icon=img/16/declarations.png, row=2');
    	}
    	    	 
    }
    
    
    /**
     * Зарежда разумни начални стойности на полетата на форма за фактура.
     */
    public static function setFormDefaults(core_Form $form)
    {
        // Днешна дата в полето `date`
        if (empty($form->rec->date)) {
            $form->rec->date = dt::now();
        }
        
        if($form->rec->originId){
        	$origin = doc_Containers::getDocument($form->rec->originId);
        	if($origin->className  == 'sales_Invoices' && Request::get('type')){
        		static::populateNoteFromInvoice($form, $origin);
        		$flag = TRUE;
        	}
        }
		
        if(!$flag){
        	static::populateContragentData($form);
        }
    }
    
    
    /**
     * 
     * @param core_Form $form
     */
    protected function populateNoteFromInvoice(core_Form $form, core_ObjectReference $origin)
    {
    	$rec = $form->rec;
        if($rec->id) return;
        $invArr = (array)$origin->fetch();
        foreach(array('id', 'number', 'date', 'containerId', 'additionalInfo') as $key){
        	 unset($invArr[$key]);
        }
        
        foreach($invArr as $field => $value){
        	$form->setDefault($field, $value);
        }
    }
    
    
    /**
     * Изчислява данните на контрагента и ги зарежда във форма за създаване на нова ф-ра
     */
    protected static function populateContragentData(core_Form $form)
    {
        $rec = $form->rec;
        
        if ($rec->id) {
            // Редактираме запис - не зареждаме нищо
            return;
        }
        
        // Задължително условие е папката, в която се създава новата ф-ра да е известна
        expect($folderId = $rec->folderId);
        
        // Извличаме данните на контрагент по подразбиране
        $sourceClass    = doc_Folders::fetchCoverClassName($folderId);
        $sourceObjectId = doc_Folders::fetchCoverId($folderId);
        $contragentData = $sourceClass::getContragentData($sourceObjectId);
        
    	$contragentClass = cls::get($sourceClass);
    	if($contragentClass->shouldChargeVat($sourceObjectId)){
    		$form->setDefault('vat', 'yes');
    	} else {
    		$form->setDefault('vat', 'export');
    	}
        
        $rec->contragentCountryId = $contragentData->countryId;
        if(!$rec->contragentCountryId){
        	$myCompany = crm_Companies::fetchOwnCompany();
        	$rec->contragentCountryId = $myCompany->countryId;
        }
        
        if($contragentData->person){
        	$rec->contragentName = $contragentData->person;
        	$rec->contragentAddress = $contragentData->pAddress;
        } elseif($contragentData->company){
        	$rec->contragentName = $contragentData->company;
            $rec->contragentAddress = $contragentData->address;
            $rec->contragentVatNo = $contragentData->vatNo;
        }
        
        $rec->contragentPCode = $contragentData->pCode;
        $rec->contragentPlace = $contragentData->place;
        
        $rec->currencyId = drdata_Countries::fetchField($rec->contragentCountryId, 'currencyCode');
        if($ownAcc = bank_OwnAccounts::getCurrent('id', FALSE)){
	        $form->setDefault('accountId', $ownAcc);
	    } 
	    
	    $locations = crm_Locations::getContragentOptions($sourceClass, $sourceObjectId);
        $form->setOptions('deliveryPlaceId',  array('' => '') + $locations);
    }
    
    
    /**
     * След проверка на ролите
     */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	switch ($action) {
    		case 'edit':
	    	    // Фактурата неможе се едитва, ако е възоснова на продажба
	    		if(($rec->originId && $rec->type == 'invoice') || ($rec->docType && $rec->docId)){
	    			$requiredRoles = 'no_one';
	    		}
    			break;
           
            case 'activate':
                if (empty($rec->id)) {
                    // не се допуска активиране на незаписани фактури
                    $requiredRoles = 'no_one';
                } elseif (sales_InvoiceDetails::count("#invoiceId = {$rec->id}") == 0) {
                    // Не се допуска активирането на празни фактури без детайли
                    $requiredRoles = 'no_one';
                } elseif ($mvc->haveRightFor('conto', $rec)) {
                    // не се допуска активиране на фактура, която генерира счет. транзакция.
                    // Tакива фактури трябва да се контират, не да се активират
                    $requiredRoles = 'no_one';
                }
                break;
    	}
    }
    
    
    /**
     * Данните на най-новата активна (т.е. контирана) ф-ра в зададена папка
     *
     * @param int $folderId key(mvc=doc_Folders)
     * @return stdClass обект-данни на модела sales_Invoices; NULL ако няма такава ф-ра
     */
    protected static function getLastActiveInvoice($folderId)
    {
        $query = static::getQuery();
        $query->where("#folderId = {$folderId}");
        $query->where("#state <> 'rejected'");
        $query->orderBy('createdOn', 'DESC');
        $query->limit(1);
    
        $invoiceRec = $query->fetch();
    
        return !empty($invoiceRec) ? $invoiceRec : NULL;
    }
    
    
    /**
     * Преди извличане на записите филтър по number
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#number', 'DESC');
    }


    /**
     * Данните на контрагент, записани в съществуваща фактура.
     * 
     * Интерфейсен метод на @see doc_ContragentDataIntf.
     * 
     * @param int $id key(mvc=sales_Invoices)
     * @return stdClass @see doc_ContragentDataIntf::getContragentData()
     *  
     */
    public static function getContragentData($id)
    {
        $rec = sales_Invoices::fetch($id);
        
        $contrData = new stdClass();
        $contrData->company   = $rec->contragentName;
        $contrData->countryId = $rec->contragentCountryId;
        $contrData->country   = static::getVerbal($rec, 'contragentCountryId');
        $contrData->vatNo     = $rec->contragentVatNo;
        $contrData->address   = $rec->contragentAddress;
        
        return $contrData;
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейла по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = sales_Invoices::getHandle($id);
        $type = static::fetchField($id, 'type');
        switch($type){
        	case 'invoice':
        		$type = "приложената фактура";
        		break;
        	case 'debit_note':
        		$type = "приложеното дебитно известие";
        		break;
        	case 'credit_note':
        		$type = "приложеното кредитно известие";
        		break;
        }
        
        //Създаваме шаблона
        $tpl = new ET(tr("Моля запознайте се с") . " [#type#]:\n#[#handle#]");
        $tpl->append($handle, 'handle');
        $tpl->append($type, 'type');
        
        return $tpl->getContent();
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
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
    
        return cls::haveInterface('doc_ContragentDataIntf', $folderClass);
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
		$row = new stdClass();
        $row->title = "Фактура №{$rec->number}";
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->authorId = $rec->createdBy;
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
    }
    
    
   /**
    * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
    */
    public static function getHandle($id)
    {
        $self = cls::get(get_called_class());
        $number = $self->fetchField($id, 'number');
        return $self->abbr . $number;
    } 
    
    
   /**
    * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
    */
    public static function fetchByHandle($parsedHandle)
    {
        return static::fetch("#number = '{$parsedHandle['id']}'");
    } 

    
	/**
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function finalizeTransaction($id)
    {
        $rec = self::fetchRec($id);
        $rec->state = 'active';
                
        if (self::save($rec)) {

            // Нотификация към пораждащия документ, че нещо във веригата му от породени документи
            // се е променило.
            if ($origin = self::getOrigin($rec)) {
                $rec = new core_ObjectReference(get_called_class(), $rec);
                $origin->getInstance()->invoke('DescendantChanged', array($origin, $rec));
            }
        }
    }
    
    
    /**
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 */
    public static function getTransaction($id)
    {
       	// Извличаме записа
        expect($rec = self::fetchRec($id));
        
        if (empty($rec->folderId)) {
            return FALSE;
        }
        
        static::prepareAdditionalInfo($rec);
        
        // Създаване / обновяване на перото за контрагента
        $contragentClass = doc_Folders::fetchCoverClassName($rec->folderId);
        $contragentId    = doc_Folders::fetchCoverId($rec->folderId);
        
        $result = (object)array(
            'reason' => "Фактура №{$rec->number}", // основанието за ордера
            'valior' => $rec->date,   // датата на ордера
        );
		
        $entries = array();
        
        if($rec->vatAmount){
        	$entries[] = array(
                'amount' => $rec->vatAmount,  // равностойноста на сумата в основната валута
                
                'debit' => array(
                    '411', // дебитната сметка
                        array($contragentClass, $contragentId),
                        array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->date)),
                    'quantity' => $rec->vatAmount,
                ),
                
                'credit' => array(
                    '4532', // кредитна сметка;
                    'quantity' => $rec->vatAmount,
                )
    	    );
        }
        
      	$result->entries = $entries;
      	
      	return $result;
    }
    
    
	/**
     * Връща масив от използваните нестандартни артикули в фактурата
     * @param int $id - ид на фактура
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->sales_InvoiceDetails->getQuery();
    	$dQuery->EXT('state', 'sales_Invoices', 'externalKey=invoiceId');
    	$dQuery->where("#invoiceId = '{$id}'");
    	$dQuery->groupBy('productId,policyId');
    	while($dRec = $dQuery->fetch()){
    		$productMan = cls::get($dRec->policyId)->getProductMan();
    		if(cls::haveInterface('doc_DocumentIntf', $productMan)){
    			$res[] = (object)array('class' => $productMan, 'id' => $dRec->productId);
    		}
    	}
    	return $res;
    }
    

    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
        $rec = new sales_model_Invoice($id);
        
        $result = new bgerp_iface_DealResponse();
        
        $result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
        
        $result->invoiced->amount = $rec->dealValue;
        
        /* @var $dRec sales_model_InvoiceProduct */
        foreach ($rec->getDetails('sales_InvoiceDetails') as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId     = cls::get($dRec->policyId)->getProductMan();
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->isOptional  = FALSE;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            
            $result->invoiced->products[] = $p;
        }
        
        return $result;
    }
}
