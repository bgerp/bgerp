<?php



/**
 * Фактури
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
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
     * Единично заглавие
     */
    var $singleTitle = 'Фактура';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, doc_DocumentPlg, plg_ExportCsv,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, doc_ActivatePlg, cond_plg_DefaultValues,
                    doc_SequencerPlg, doc_plg_BusinessDoc2, acc_plg_Contable, doc_plg_HidePrices';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, number, date, folderId, type';
    
    
    /**
     * Колоната, в която да се появят инструментите на plg_RowTools
     */
    public $rowToolsField = 'tools';
    
     
    /**
     * Поле за хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'number';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'sales_InvoiceDetails' ;
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,sales';
    
    
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
    var $canAdd = 'ceo,sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,sales';
    
    
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
     * Икона за фактура
     */
    var $singleIcon = 'img/16/invoice.png';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "3.3|Търговия";
    
    
    /**
     * Полета свързани с цени
     */
    var $priceFields = 'dealValue,vatAmount,baseAmount,total,vatPercent';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'contragentName'      => 'lastDocUser|lastDoc|defMethod',
    	'place'               => 'lastDocUser|lastDoc',
    	'responsible'         => 'lastDocUser|lastDoc',
    	'contragentCountryId' => 'lastDocUser|lastDoc|clientData',
    	'contragentVatNo'     => 'lastDocUser|lastDoc|clientData',
		'contragentPCode'     => 'lastDocUser|lastDoc|clientData',
    	'contragentPlace'     => 'lastDocUser|lastDoc|clientData',
        'contragentAddress'   => 'lastDocUser|lastDoc|clientData',
        'accountId'           => 'lastDocUser|lastDoc',
    	'caseId'              => 'lastDocUser|lastDoc',
        'currencyId'          => 'lastDocUser|lastDoc',
        'deliveryPlaceId'     => 'lastDocUser|lastDoc',
        'deliveryId'          => 'lastDocUser|lastDoc|clientCondition',
    	'paymentMethodId' 	  => 'lastDocUser|lastDoc|clientCondition',
    	'vatRate' 			  => 'lastDocUser|lastDoc|defMethod',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('date', 'date(format=d.m.Y)', 'caption=Дата,  notNull, mandatory');
        $this->FLD('place', 'varchar(64)', 'caption=Място, mandatory');
        $this->FLD('number', 'varchar', 'caption=Номер, export=Csv');
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        $this->FLD('contragentName', 'varchar', 'caption=Получател->Име, mandatory');
        $this->FLD('responsible', 'varchar(255)', 'caption=Получател->Отговорник');
        $this->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)', 'caption=Получател->Държава,mandatory,contragentDataField=countryId');
        $this->FLD('contragentVatNo', 'drdata_VatType', 'caption=Получател->ЕИК/VAT №, mandatory,contragentDataField=vatNo');
        $this->FLD('contragentPCode', 'varchar(16)', 'caption=Получател->П. код,recently,class=pCode,contragentDataField=pCode');
        $this->FLD('contragentPlace', 'varchar(64)', 'caption=Получател->Град,class=contactData,contragentDataField=place');
        $this->FLD('contragentAddress', 'varchar(255)', 'caption=Получател->Адрес,class=contactData,contragentDataField=address');
        $this->FLD('changeAmount', 'double(decimals=2)', 'input=none,width=10em');
        $this->FLD('reason', 'text(rows=2)', 'caption=Плащане->Основание, input=none');
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods, select=name)', 'caption=Плащане->Начин,salecondSysId=paymentMethod');
        $this->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=bankAccountId, allowEmpty)', 'caption=Плащане->Банкова с-ка, width:100%, export=Csv');
		$this->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Плащане->Каса');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута->Код,width=6em');
        $this->FLD('rate', 'double(decimals=2)', 'caption=Валута->Курс,width=6em'); 
        $this->FLD('deliveryId', 'key(mvc=cond_DeliveryTerms, select=codeName, allowEmpty)', 'caption=Доставка->Условие,salecondSysId=deliveryTerm');
        $this->FLD('deliveryPlaceId', 'key(mvc=crm_Locations, select=title)', 'caption=Доставка->Място');
        $this->FLD('vatDate', 'date(format=d.m.Y)', 'caption=Данъци->Дата на ДС');
        $this->FLD('vatRate', 'enum(yes=Включено, no=Отделно, freed=Oсвободено,export=Без начисляване)', 'caption=Данъци->ДДС %');
        $this->FLD('vatReason', 'varchar(255)', 'caption=Данъци->Основание'); 
		$this->FLD('additionalInfo', 'richtext(bucket=Notes, rows=6)', 'caption=Допълнително->Бележки,width:100%');
        $this->FLD('dealValue', 'double(decimals=2)', 'caption=Стойност, input=hidden');
        $this->FLD('vatAmount', 'double(decimals=2)', 'caption=Стойност ДДС, input=none');
        $this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
        
        $this->FLD('type', 
            'enum(invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие)', 
            'caption=Вид, input=hidden,silent'
        );
        
        $this->FLD('docType', 'class(interface=bgerp_DealAggregatorIntf)', 'input=hidden,silent');
        $this->FLD('docId', 'int', 'input=hidden,silent');
        
        $this->setDbUnique('number');
    }
    
    
     /**
     * След промяна в детайлите на обект от този клас
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
        $rec = $mvc->fetchRec($id);
        $query = $detailMvc->getQuery();
        $query->where("#{$detailMvc->masterKey} = '{$id}'");
    
        $rec->dealValue = $rec->vatAmount = 0;
    
        while ($detailRec = $query->fetch()) {
        	$vat = 0;
        	if($rec->vatRate == 'yes' || $rec->vatRate == 'no'){
        		$ProductManager = cls::get($detailRec->classId);
    			$vat = $ProductManager->getVat($detailRec->productId, $rec->valior);
        	}
        	
        	$rec->dealValue += $detailRec->amount;
        	$rec->vatAmount += $detailRec->amount * $vat;
        }
        
        $mvc->save($rec);
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	if(!$type = Request::get('type')) return;
    	$title = ($type == 'debit_note') ? 'Дебитно известие' : 'Кредитно известие';
    	$mvc->singleTitle = $title;
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $form->rec->date = dt::today();
        
        if (!$form->rec->id) {
            $type = Request::get('type');
	        if(!$type){
	        	$form->setDefault('type', 'invoice');
	        }
	        
        	// При създаване на нова ф-ра зареждаме полетата на 
            // формата с разумни стойности по подразбиране.
        	if($form->rec->originId){
	        	$origin = doc_Containers::getDocument($form->rec->originId);
	        	if($origin->className  == 'sales_Invoices' && Request::get('type')){
	        		$mvc->populateNoteFromInvoice($form, $origin);
	        		$flag = TRUE;
	        	}
        	}
        	
	        if(empty($flag)){
	        	$form->rec->currencyId = drdata_Countries::fetchField($form->rec->contragentCountryId, 'currencyCode');
				if($ownAcc = bank_OwnAccounts::getCurrent('id', FALSE)){
					$form->setDefault('accountId', $ownAcc);
				} 

				$coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
        		$coverId = doc_Folders::fetchCoverId($form->rec->folderId);
				$locations = crm_Locations::getContragentOptions($coverClass, $coverId);
				$form->setOptions('deliveryPlaceId',  array('' => '') + $locations);
	        }
	   	} else {
	   		// Неможе да се сменя ДДС-то ако има вече детайли
        	$dQuery = $mvc->sales_InvoiceDetails->getQuery();
        	$dQuery->where("#invoiceId = {$data->form->rec->id}");
        	if($dQuery->count()){
        		$data->form->setReadOnly('vatRate');
        	}
	   	}
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	
           	if(!$rec->rate){
        		$rec->rate = round(currency_CurrencyRates::getRate($rec->date, $rec->currencyId, NULL), 4);
        	}
        
    		if(!currency_CurrencyRates::hasDeviation($rec->rate, $rec->date, $rec->currencyId, NULL)){
		    	$form->setWarning('rate', 'Въведения курс има много голяма разлика спрямо очакваната');
			}
		    	
        	$Vats = cls::get('drdata_Vats');
        	$rec->contragentVatNo = $Vats->canonize($rec->contragentVatNo);
        	
	        foreach ($mvc->fields as $fName => $field) {
	            $mvc->invoke('Validate' . ucfirst($fName), array($rec, $form));
	        }
        }

        acc_Periods::checkDocumentDate($form);
	}
	
	
	/**
	 * Генерира фактура от пораждащ документ: може да се породи от:
	 * 1. Продажба (@see sales_Sales)
	 * 2. POS Продажба (@see pos_Receipts)
	 * 3. Фактура (@see sales_Invoices) - тоест се прави ДИ или КИ
	 */
	public static function on_AfterCreate($mvc, $rec)
    {
    	expect($origin = static::getOrigin($rec));
    	
    	if ($origin->haveInterface('bgerp_DealAggregatorIntf')) {
    		$info = $origin->getAggregateDealInfo();
    		$products = $info->shipped->products;
    		
    		if(count($products) != 0){
	    		$productMans = array();
    			
	    		// Записваме информацията за продуктите в детайла
		    	foreach ($products as $product){
		    		if(!$productMans[$product->classId]){
		    			$productMans[$product->classId] = cls::get($product->classId);
		    		}
		    		$pInfo = $productMans[$product->classId]->getProductInfo($product->productId, $product->packagingId);
		    		$packQuantity = ($pInfo->packagingRec) ? $pInfo->packagingRec->quantity : 1;
		    		
		    		$dRec = clone $product;
		    		$dRec->invoiceId = $rec->id;
		    		$dRec->classId = $product->classId;
		    		$dRec->packQuantity = $product->quantity * $packQuantity;
		    		$dRec->amount = $dRec->packQuantity * $product->price;
		    		$dRec->quantityInPack = $dRec->packQuantity;
		    		
		    		$mvc->sales_InvoiceDetails->save($dRec);
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
    	
    	if($rec->docType && $rec->docId) {
    		// Ако се генерира от пос продажба
    		return new core_ObjectReference($rec->docType, $rec->docId);
    	}
    	
    	if($rec->originId) {
    		$origin = doc_Containers::getDocument($rec->originId);
    	} elseif($rec->threadId){
    		$origin = doc_Threads::getFirstDocument($rec->threadId);
	    }
    	
    	return $origin;
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
            $form->setError('vatDate', '|Данъчното събитие трябва да е до 5 дни|* <b>|преди|*</b> |датата на фактурата|*');
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
            $rec->contragentClassId  = doc_Folders::fetchCoverClassId($rec->folderId);
            $rec->contragentId     = doc_Folders::fetchCoverId($rec->folderId);
        }
        
        if($rec->type != 'invoice'){
        	$rec->dealValue = currency_CurrencyRates::convertAmount($rec->changeAmount, dt::now(), $rec->currencyId, NULL);
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
    static function on_BeforeRecToVerbal($mvc, &$row, &$rec, $fields = array())
    {
    	if(!is_object($rec)){
    		$rec = new stdClass();
    	}
    	
    	// Номера се форматира в десеторазряден вид
    	$rec->number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
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
	    	if($rec->vatRate == 'yes' || $rec->vatRate == 'no'){
				$rec->vatPercent = $rec->vatAmount / $rec->baseAmount;
			}
			$rec->total = round(($rec->baseAmount + $rec->vatAmount) / $rec->rate, 2);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    	
    	if($fields['-single']){
	    	if($rec->docType && $rec->docId){
	    		$row->POS = tr("|към ПОС продажба|* №{$rec->docId}");
	    	}
	    	
	    	if($rec->originId && $rec->type != 'invoice'){
	    		unset($row->deliveryPlaceId, $row->deliveryId);
	    	}
    	
    		$row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->date);
    		$Double = cls::get('type_Double');
    		$Double->params['decimals'] = 2;
    	
    		$row->type .= " <br /> <i>" . str_replace('_', " ", $rec->type) . "</i>";
    		
    		// Ако е подаден Ват номер, намираме ЕИК-то от него
    		$uic = drdata_Vats::getUicByVatNo($rec->contragentVatNo);
    		if($uic == $rec->contragentVatNo){
    			unset($row->contragentVatNo);
    		}
    		$row->contragentUiC = $uic;
    		
	    	if($rec->dealValue){
	    		$row->baseAmount = $Double->toVerbal($rec->baseAmount);
	    		
	    		$Percent = cls::get('type_Percent');
	    		$parts = explode(".", $rec->vatPercent);
	    		$Percent->params['decimals'] = count($parts[1]);
	    		
				$row->vatPercent = $Percent->toVerbal($rec->vatPercent);
				$row->vatAmount = $Double->toVerbal($rec->vatAmount);
				$row->total = $Double->toVerbal($rec->total);
				
				$SpellNumber = cls::get('core_SpellNumber');
				$row->amountVerbal = $SpellNumber->asCurrency($rec->total, 'bg', FALSE);
	    	}
	    	
	    	if($rec->accountId){
	    		$Varchar = cls::get('type_Varchar');
	    		$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->accountId);
	    		$row->bank = $Varchar->toVerbal($ownAcc->bank);
	    		$row->bic = $Varchar->toVerbal($ownAcc->bic);
	    	}
	    	
	    	if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . " №<b>{$row->number}</b> ({$row->state})" ;
	    	}
	    	
	    	$userRec = core_Users::fetch($rec->createdBy);
			$row->username = core_Users::recToVerbal($userRec, 'names')->names;
    	}
    }
    
    
	/**
     * След подготовка на тулбара на единичен изглед.
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	
    	if($rec->type == 'invoice' && $rec->state == 'active' && $rec->dealValue){
    		if($mvc->haveRightFor('add')){
    			$data->toolbar->addBtn('ДИ', array($mvc, 'add', 'originId' => $rec->containerId, 'type' => 'debit_note'), 'ef_icon=img/16/layout_join_vertical.png,title=Дебитно известие');
    			$data->toolbar->addBtn('КИ', array($mvc, 'add','originId' => $rec->containerId, 'type' => 'credit_note'), 'ef_icon=img/16/layout_split_vertical.png,title=Кредитно известие');
    		}
    		
    		if(dec_Declarations::haveRightFor('add')){
    			$data->toolbar->addBtn('Декларация', array('dec_Declarations', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), 'ef_icon=img/16/declarations.png, row=2');
    		}
    	}	 
    }
    
    
    /**
     * Попълва дефолтите на Дебитното / Кредитното известие
     */
    private function populateNoteFromInvoice(core_Form $form, core_ObjectReference $origin)
    {
    	$caption = ($form->rec->type == 'debit_note') ? 'Увеличение': 'Намаление';
        
    	$invArr = (array)$origin->fetch();
    	$invHandle = $origin->getHandle();
    	$invDate = $origin->recToVerbal()->date;
        $invArr['reason'] = "{$caption} към фактура #{$invHandle} от {$invDate}";
        
    	foreach(array('id', 'number', 'date', 'containerId', 'additionalInfo', 'dealValue') as $key){
        	 unset($invArr[$key]);
        }
        
        // Копиране на повечето от полетата на фактурата
        foreach($invArr as $field => $value){
        	$form->setDefault($field, $value);
        }
       
        $form->setDefault('date', dt::today());
        $form->setField('reason', 'input');
		$form->setField('changeAmount', 'input');
		$form->setField('reason', 'input,mandatory');
		$form->setField('deliveryId', 'input=none');
		$form->setField('deliveryPlaceId', 'input=none');
		
		foreach(array('rate', 'currencyId', 'contragentName', 'contragentVatNo', 'contragentCountryId') as $name){
			$form->setReadOnly($name);
		}
		
		$form->setField('changeAmount', "caption=Плащане->{$caption},mandatory");
    }
    
    
    /**
     * Определяне на името на контрагента по подразбиране
     */
    public function getDefaultContragentName($rec)
    {
    	// Извличаме данните на контрагент по подразбиране
        $coverClass = doc_Folders::fetchCoverClassName($rec->folderId);
        $coverId = doc_Folders::fetchCoverId($rec->folderId);
        $contragentData = $coverClass::getContragentData($coverId);
        
        return ($coverClass == 'crm_Companies') ? $contragentData->company : $contragentData->person;
    }
    
    
    /**
     * Дали да се начислява ДДС
     */
    public function getDefaultVatRate($rec)
    {
        $coverId = doc_Folders::fetchCoverId($rec->folderId);
    	$Class = cls::get(doc_Folders::fetchCoverClassName($rec->folderId));
    	
    	return ($Class->shouldChargeVat($coverId)) ? 'yes' : 'export';
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
     * Преди извличане на записите филтър по number
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
        $rec = static::fetch($id);
        
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
        $handle = static::getHandle($id);
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
        
        // Създаване на шаблона
        $tpl = new ET(tr("Моля запознайте се с") . " [#type#]:\n#[#handle#]");
        $tpl->append($handle, 'handle');
        $tpl->append(tr($type), 'type');
        
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
     * Дали документа може да се добави към нишката
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
    	$coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);
    	
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    	
    	$res = cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    	if($res){
    		if(($firstDoc->haveInterface('bgerp_DealIntf') && $docState != 'active')){
    			$res = FALSE;
    		}
    	}
		
    	return $res;
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
    	$dQuery->groupBy('productId,classId');
    	while($dRec = $dQuery->fetch()){
    		$productMan = cls::get($dRec->classId);
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
        static::prepareAdditionalInfo($rec);
        
        $result = new bgerp_iface_DealResponse();
        $result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
        $result->invoiced->amount = $rec->total;
        
        /* @var $dRec sales_model_InvoiceProduct */
        foreach ($rec->getDetails('sales_InvoiceDetails') as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId     = $dRec->classId;
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->isOptional  = FALSE;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            
            $result->invoiced->products[] = $p;
        }
        
        return $result;
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интефейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
    
    
	/**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
}