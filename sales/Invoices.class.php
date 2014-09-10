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
class sales_Invoices extends core_Master
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
	 * Поле за единичния изглед
	 */
	public $rowToolsSingleField = 'number';
	
	
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
     * Полета свързани с цени
     */
    public $priceFields = 'dealValue,vatAmount,baseAmount,total,vatPercent,discountAmount';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'date';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'place'               => 'lastDocUser|lastDoc',
    	'responsible'         => 'lastDocUser|lastDoc',
    	'contragentCountryId' => 'lastDocUser|lastDoc|clientData',
    	'contragentVatNo'     => 'lastDocUser|lastDoc|clientData',
    	'uicNo'     		  => 'lastDocUser|lastDoc|clientData',
		'contragentPCode'     => 'lastDocUser|lastDoc|clientData',
    	'contragentPlace'     => 'lastDocUser|lastDoc|clientData',
        'contragentAddress'   => 'lastDocUser|lastDoc|clientData',
        'accountId'           => 'lastDocUser|lastDoc',
    	'template' 			  => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('date', 'date(format=d.m.Y)', 'caption=Дата,  notNull, mandatory, export=Csv');
        $this->FLD('place', 'varchar(64)', 'caption=Място, class=contactData');
        $this->FLD('number', 'int', 'caption=Номер, export=Csv');
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        $this->FLD('contragentName', 'varchar', 'caption=Получател->Име, mandatory, class=contactData, export=Csv');
        $this->FLD('responsible', 'varchar(255)', 'caption=Получател->Отговорник, class=contactData');
        $this->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)', 'caption=Получател->Държава,mandatory,contragentDataField=countryId');
        $this->FLD('contragentVatNo', 'drdata_VatType', 'caption=Получател->VAT №,contragentDataField=vatNo, export=Csv');
        $this->FLD('uicNo', 'type_Varchar', 'caption=Получател->Национален №,contragentDataField=uicId');
        $this->FLD('contragentPCode', 'varchar(16)', 'caption=Получател->П. код,recently,class=pCode,contragentDataField=pCode');
        $this->FLD('contragentPlace', 'varchar(64)', 'caption=Получател->Град,class=contactData,contragentDataField=place');
        $this->FLD('contragentAddress', 'varchar(255)', 'caption=Получател->Адрес,class=contactData,contragentDataField=address');
        $this->FLD('changeAmount', 'double(decimals=2)', 'input=none');
        $this->FLD('reason', 'text(rows=2)', 'caption=Плащане->Основание, input=none');
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods, select=description)', 'caption=Плащане->Начин, export=Csv');
        $this->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=bankAccountId, allowEmpty)', 'caption=Плащане->Банкова с-ка');
		$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута->Код,input=hidden');
        $this->FLD('rate', 'double(decimals=2)', 'caption=Валута->Курс,input=hidden'); 
        $this->FLD('deliveryId', 'key(mvc=cond_DeliveryTerms, select=codeName, allowEmpty)', 'caption=Доставка->Условие,input=hidden');
        $this->FLD('deliveryPlaceId', 'key(mvc=crm_Locations, select=title)', 'caption=Доставка->Място');
        $this->FLD('vatDate', 'date(format=d.m.Y)', 'caption=Данъци->Дата на ДС');
        $this->FLD('vatRate', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=Данъци->ДДС,input=hidden');
        $this->FLD('vatReason', 'varchar(255)', 'caption=Данъци->Основание'); 
		$this->FLD('additionalInfo', 'richtext(bucket=Notes, rows=6)', 'caption=Допълнително->Бележки');
        $this->FLD('dealValue', 'double(decimals=2)', 'caption=Стойност, input=hidden,summary=amount, export=Csv');
        $this->FLD('vatAmount', 'double(decimals=2)', 'caption=ДДС, input=none,summary=amount');
        $this->FLD('discountAmount', 'double(decimals=2)', 'caption=Отстъпка->Обща, input=none,summary=amount');
        $this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none,export=Csv'
        );
        
        $this->FLD('type', 
            'enum(invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие)', 
            'caption=Вид, input=hidden'
        );
        
        $this->FLD('docType', 'class(interface=bgerp_DealAggregatorIntf)', 'input=hidden,silent');
        $this->FLD('docId', 'int', 'input=hidden,silent');
        
        $this->setDbUnique('number');
    }
    
    
    /**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->FNC('invType', 'enum(all=Всички, invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие)', 'caption=Вид,input,silent');
		$data->listFilter->showFields .= ',invType';
		$data->listFilter->input();
		
		if($type = $data->listFilter->rec->invType){
			if($type != 'all'){
				$data->query->where("#type = '{$type}'");
			}
		}
	}
	
	
	/**
	 * След като се поготви заявката за модела
	 */
	public static function on_AfterGetQuery(core_Mvc $mvc, &$query)
	{
		// Сортираме низходящо по номер
		$query->orderBy('#number', 'DESC');
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
     * След промяна в детайлите на обект от този клас
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
        // Запомняне кои документи трябва да се обновят
    	$mvc->updated[$id] = $id;
    }
    
    
	/**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
        if(count($mvc->updated)){
        	foreach ($mvc->updated as $id) {
	        	$mvc->updateMaster($id);
	        }
        }
    }
    
    
	/**
     * Обновява информацията на документа
     * @param int $id - ид на документа
     */
    public function updateMaster($id)
    {
    	$rec = $this->fetchRec($id);
    	$query = $this->sales_InvoiceDetails->getQuery();
        $query->where("#invoiceId = '{$id}'");
        $recs = $query->fetchAll();
        if(count($recs)){
	        foreach ($recs as &$dRec){
	        	$dRec->price = $dRec->price * $dRec->quantityInPack;
	        }
        }
    	
        $this->sales_InvoiceDetails->calculateAmount($recs, $rec);
       
        $rec->dealValue = $this->_total->amount * $rec->rate;
        $rec->vatAmount = $this->_total->vat * $rec->rate;
        $rec->discountAmount = $this->_total->discount * $rec->rate;
    	
        $this->save($rec);
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	$type = Request::get('type');
    	if(!$type || $type == 'invoice') return;
    	
    	$title = ($type == 'debit_note') ? 'Дебитно известие' : 'Кредитно известие';
    	$mvc->singleTitle = $title;
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $form->setDefault('date', dt::today());
        if(!haveRole('ceo,acc')){
        	$form->setField('number', 'input=none');
        }
        
        $coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
        $coverId = doc_Folders::fetchCoverId($form->rec->folderId);
        $form->rec->contragentName = $coverClass::fetchField($coverId, 'name');
        
        $className = doc_Folders::fetchCoverClassName($form->rec->folderId);
        if($className == 'crm_Persons'){
        	$numType = 'bglocal_EgnType';
        	$form->setField('uicNo', 'caption=Получател->ЕГН');
        	$form->getField('uicNo')->type = cls::get($numType);
        }
        
        $type = Request::get('type');
        if(empty($type)){
        	$type = 'invoice';
        }
        $form->rec->type = $type;
	        
        // При създаване на нова ф-ра зареждаме полетата на 
        // формата с разумни стойности по подразбиране.
        expect($origin = static::getOrigin($form->rec));
        if($origin->haveInterface('bgerp_DealAggregatorIntf')){
        	$aggregateInfo         = $origin->getAggregateDealInfo();
        	
        	$form->rec->vatRate    = $aggregateInfo->get('vatType');
        	$form->rec->currencyId = $aggregateInfo->get('currency');
        	$form->rec->rate       = $aggregateInfo->get('rate');
        	
        	if($aggregateInfo->get('paymentMethodId')){
        		$form->rec->paymentMethodId = $aggregateInfo->get('paymentMethodId');
        		$form->setField('paymentMethodId', 'input=hidden');
        	}
        	
        	$form->rec->deliveryId = $aggregateInfo->get('deliveryTerm');
        	if($aggregateInfo->get('deliveryLocation')){
        		$form->rec->deliveryPlaceId = $aggregateInfo->get('deliveryLocation');
        		$form->setField('deliveryPlaceId', 'input=hidden');
        	}
        	
        	if($accId = $aggregateInfo->get('bankAccountId')){
        		$form->rec->accountId = bank_OwnAccounts::fetchField("#bankAccountId = {$accId}", 'id');
        	}
        }
	        
	    if($origin->className  == 'sales_Invoices'){
	        $mvc->populateNoteFromInvoice($form, $origin);
	        $flag = TRUE;
	    }
        	
	    if(empty($flag)){
	        $form->setDefault('currencyId', drdata_Countries::fetchField($form->rec->contragentCountryId, 'currencyCode'));
			if($ownAcc = bank_OwnAccounts::getCurrent('id', FALSE)){
				$form->setDefault('accountId', $ownAcc);
			}
			$locations = crm_Locations::getContragentOptions($coverClass, $coverId);
			$form->setOptions('deliveryPlaceId',  array('' => '') + $locations);
	    }
	   	
	   	$form->setReadOnly('vatRate');
	   	
	   	// Метод който да бъде прихванат от acc_plg_DpInvoice
	   	$mvc->prepareDpInvoicePlg($data);
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
        
    		if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->date, $rec->currencyId, NULL)){
		    	$form->setWarning('rate', $msg);
			}
		    	
        	$Vats = cls::get('drdata_Vats');
        	$rec->contragentVatNo = $Vats->canonize($rec->contragentVatNo);
        	
	        foreach ($mvc->fields as $fName => $field) {
	            $mvc->invoke('Validate' . ucfirst($fName), array($rec, $form));
	        }
	        
	        if(strlen($rec->contragentVatNo) && !strlen($rec->uicNo)){
	            $rec->uicNo = drdata_Vats::getUicByVatNo($rec->contragentVatNo);
	        } elseif(!strlen($rec->contragentVatNo) && !strlen($rec->uicNo)){
	        	$form->setError('contragentVatNo,uicNo', 'Трябва да е въведен поне един от номерата');
	        }
        	
        	// Ако е ДИ или КИ
	    	if($rec->type != 'invoice'){
	    		
	    		// Изчисляване на стойността на ддс-то
	        	$vat = acc_Periods::fetchByDate()->vatRate;
	        	$rec->vatAmount = $rec->changeAmount * $vat;
	        	$rec->vatAmount *= $rec->rate;
	        	
	        	// Стойността е променената сума
	        	$rec->dealValue = $rec->changeAmount;
	        	$rec->dealValue *= $rec->rate;
	        }
	        
	        if($rec->number){
		        if(!$mvc->isNumberInRange($rec->number)){
					$form->setError('number', "Номер '{$rec->number}' е извън позволения интервал");
				}
	        }
        }

        acc_Periods::checkDocumentDate($form);
        
        // Метод който да бъде прихванат от acc_plg_DpInvoice
        $mvc->inputDpInvoice($form);
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
    		$products = $info->get('shippedProducts');
    		$invoiced = $info->get('invoicedProducts');
    		$packs = $info->get('shippedPacks');
    		
    		if(count($products) != 0){
    			
	    		// Записваме информацията за продуктите в детайла
		    	foreach ($products as $product){
		    		$continue = FALSE;
		    		$diff = $product->quantity;
		    		if(count($invoiced)){
		    			foreach ($invoiced as $inv){
		    				if($inv->classId == $product->classId && $inv->productId == $product->productId){
		    					$diff = $product->quantity - $inv->quantity;
		    					if($diff <= 0){
		    						$continue = TRUE;
		    					}
		    					break;
		    				}
		    			}
		    		}
		    		
		    		if($continue) continue;
		    		
		    		$dRec = clone $product;
		    		$index = $product->classId . "|" . $product->productId;
		    		if($packs[$index]){
		    			$packQuantity = $packs[$index]->inPack;
		    			$dRec->packagingId = $packs[$index]->packagingId;
		    		} else {
		    			$packQuantity = 1;
		    			$dRec->packagingId = NULL;
		    		}
		    		
		    		$dRec->invoiceId      = $rec->id;
		    		$dRec->classId        = $product->classId;
		    		$dRec->price 		  = ($product->amount) ? ($product->amount / $product->quantity) : $product->price;
		    		$dRec->quantityInPack = $packQuantity;
		    		$dRec->quantity       = $diff / $packQuantity;
		    		
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
     * Валидиране на полето 'date' - дата на фактурата
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
            $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
            $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
        }
        
        if($rec->state == 'active'){
        	if(empty($rec->number)){
        		$rec->number = static::getNexNumber();
        		$rec->searchKeywords .= " " . plg_Search::normalizeText($rec->number);
        	}
        	
	        if(empty($rec->place) && $rec->state == 'active'){
	        	$inCharge = cls::get($rec->contragentClassId)->fetchField($rec->contragentId, 'inCharge');
	        	$inChargeRec = crm_Profiles::getProfile($inCharge);
	        	$myCompany = crm_Companies::fetchOwnCompany();
	        	$place = empty($inChargeRec->place) ? $myCompany->place : $inChargeRec->place;
	        	$countryId = empty($inChargeRec->country) ? $myCompany->countryId : $inChargeRec->country;
	        	
	        	$rec->place = $place;
	        	if($rec->contragentCountryId != $countryId){
	        		$cCountry = drdata_Countries::fetchField($countryId, 'commonNameBg');
	        		$rec->place .= (($place) ? ", " : "") . $cCountry;
	        	}
	        }
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    	
    	if(!Mode::is('printing')){
    		$tpl->replace(tr('ОРИГИНАЛ') . "/<i>ORIGINAL</i>", 'INV_STATUS');
    	}
    	
    	$tpl->push('sales/tpl/invoiceStyles.css', 'CSS');
    	
    	if($data->paymentPlan){
    		$tpl->placeObject($data->paymentPlan);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($rec->number){
    		$row->number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
    	}
    	
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		if($rec->number){
    			$row->number = ht::createLink($row->number, array($mvc, 'single', $rec->id),NULL, 'ef_icon=img/16/invoice.png');
    		}
    		
    		$total = $rec->dealValue + $rec->vatAmount - $rec->discountAmount;
    		@$row->dealValue = $mvc->getFieldType('dealValue')->toVerbal($total / $rec->rate);
    		$row->dealValue = "<span class='cCode' style='float:left'>{$rec->currencyId}</span>&nbsp;" . $row->dealValue;
    		
    		$baseCode = acc_Periods::getBaseCurrencyCode($rec->date);
    		$row->vatAmount = "<span class='cCode' style='float:left'>{$baseCode}</span>&nbsp;" . $row->vatAmount;
    	}
    	
    	if($fields['-single']){
    		
	    	if($rec->docType && $rec->docId){
	    		$row->POS = tr("|към ПОС продажба|* №{$rec->docId}");
	    	}
	    	
	    	if($rec->originId && $rec->type != 'invoice'){
	    		unset($row->deliveryPlaceId, $row->deliveryId);
	    	}
    		
	    	if(doc_Folders::fetchCoverClassName($rec->folderId) == 'crm_Persons'){
    			$row->cNum = tr('|ЕГН|* / <i>Personal №</i>');
    		} else {
	    		$row->cNum = tr('|ЕИК|* / <i>UIC</i>');
    		}
	    	
	    	if($rec->accountId){
	    		$Varchar = cls::get('type_Varchar');
	    		$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->accountId);
	    		$row->bank = $Varchar->toVerbal($ownAcc->bank);
	    		$row->bic = $Varchar->toVerbal($ownAcc->bic);
	    	}
	    	
	    	$row->header = "{$row->type} #<b>{$mvc->getHandle($rec->id)}</b> ({$row->state})" ;
	    	$userRec = core_Users::fetch($rec->createdBy);
			$row->username = core_Users::recToVerbal($userRec, 'names')->names;
    		
			$row->type .= " / <i>" . str_replace('_', " ", $rec->type) . "</i>";
			
			if($rec->type != 'invoice'){
				$originRec = $mvc->getOrigin($rec)->fetch();
				$originRow = $mvc->recToVerbal($originRec, 'number,date');
				$row->originInv = $originRow->number;
				$row->originInvDate = $originRow->date;
			}
			
			if($rec->rate == 1){
				unset($row->rate);
			}
			
    		$mvc->prepareMyCompanyInfo($row);
		}
    }
    
    
    /**
     * Подготвя вербалните данни на моята фирма
     */
    private function prepareMyCompanyInfo(&$row)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
        $Companies = cls::get('crm_Companies');
        $row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
        $row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId);
   
        $uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if($uic != $ownCompanyData->vatNo){
    		$row->MyCompanyVatNo = $ownCompanyData->vatNo;
    	}
    	 
    	$row->uicId = $uic;
    }
    
    
	/**
     * След подготовка на тулбара на единичен изглед.
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	
    	// Ако има бутон за принтиране, слагаме го да е първия бутон
    	if(!empty($data->toolbar->buttons['btnPrint'])){
    		$printUrl = array($mvc, 'single', $rec->id, 'Printing' => 'yes');
    		$data->toolbar->removeBtn('btnPrint');
    		$data->toolbar->addBtn('Печат', $printUrl, 'id=btnPrint,target=_blank,order=1', 'ef_icon = img/16/printer.png,title=Печат на страницата');
    	}
    	
    	if($rec->type == 'invoice' && $rec->state == 'active' && $rec->dealValue){
    		
    		if($mvc->haveRightFor('add', (object)array('type' => 'debit_note')) && $mvc->canAddToThread($rec->threadId)){
    			$data->toolbar->addBtn('ДИ', array($mvc, 'add', 'originId' => $rec->containerId, 'type' => 'debit_note', 'ret_url' => TRUE), 'ef_icon=img/16/layout_join_vertical.png,title=Дебитно известие');
    			$data->toolbar->addBtn('КИ', array($mvc, 'add','originId' => $rec->containerId, 'type' => 'credit_note', 'ret_url' => TRUE), 'ef_icon=img/16/layout_split_vertical.png,title=Кредитно известие');
    		}
    		
    		if(dec_Declarations::haveRightFor('add')){
    			$data->toolbar->addBtn('Декларация', array('dec_Declarations', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/declarations.png, row=2');
    		}
    	}
    }
    
    
	/**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
    	parent::prepareSingle_($data);
    	$rec = &$data->rec;
    	
    	if(empty($data->noTotal)){
    		if($rec->type != 'invoice'){
    			$this->_total = new stdClass();
    			$this->_total->amount = $rec->dealValue / $rec->rate;
    			$this->_total->vat = $rec->vatAmount / $rec->rate;
    		}
    		
    		$data->summary = deals_Helper::prepareSummary($this->_total, $rec->date, $rec->rate, $rec->currencyId, $rec->vatRate, TRUE, 'bg', $rec->id);
    		$data->row = (object)((array)$data->row + (array)$data->summary);
    		
    	 	if($rec->paymentMethodId && $rec->type == 'invoice' && $rec->dpOperation != 'accrued') {
    	 		$total = $this->_total->amount + $this->_total->vat - $this->_total->discount;
                cond_PaymentMethods::preparePaymentPlan($data, $rec->paymentMethodId, $total, $rec->date, $rec->currencyId);
            }
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	$rec = &$data->rec;
    	
    	$myCompany = crm_Companies::fetchOwnCompany();
    	if($rec->contragentCountryId != $myCompany->countryId){
    		$data->row->place = str::utf2ascii($data->row->place);
    	}
    }
    
    
    /**
     * Попълва дефолтите на Дебитното / Кредитното известие
     */
    private function populateNoteFromInvoice(core_Form &$form, core_ObjectReference $origin)
    {
    	$caption = ($form->rec->type == 'debit_note') ? 'Увеличение' : 'Намаление';
        
    	$invArr = (array)$origin->fetch();
    	$number = $origin->instance->recToVerbal((object)$invArr)->number;
    	
    	$invDate = dt::mysql2verbal($invArr['date'], 'd.m.Y');
    	$invArr['reason'] = tr("|{$caption} към фактура|* №{$number} |издадена на|* {$invDate}");
        
    	foreach(array('id', 'number', 'date', 'containerId', 'additionalInfo', 'dealValue', 'vatAmount', 'state', 'discountAmount') as $key){
        	 unset($invArr[$key]);
        }
        
        // Копиране на повечето от полетата на фактурата
        foreach($invArr as $field => $value){
        	$form->setDefault($field, $value);
        }
       
        $form->setDefault('date', dt::today());
        $form->setField('reason', 'input');
		$form->setField('changeAmount', 'input');
		$form->setField('changeAmount', "unit={$form->rec->currencyId} без ДДС");
		$form->setField('vatRate', 'input=hidden');
		$form->setField('reason', 'input,mandatory');
		$form->setField('deliveryId', 'input=none');
		$form->setField('deliveryPlaceId', 'input=none');
		
		foreach(array('rate', 'currencyId', 'contragentName', 'contragentVatNo', 'uicNo', 'contragentCountryId') as $name){
			if($form->rec->$name){
				$form->setReadOnly($name);
			}
		}
		
		$form->setField('changeAmount', "caption=Плащане->{$caption},mandatory");
	}

	
    /**
     * Данните на контрагент, записани в съществуваща фактура
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
        if(Request::get('docType', 'int') && Request::get('docId', 'int')){
        	return TRUE;
        }
        
    	return FALSE;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
		$row = new stdClass();
        $row->title = static::getRecTitle($rec);
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
     * @return bgerp_iface_DealAggregator
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
        $rec = $this->fetchRec($id);
        $total = $rec->dealValue + $rec->vatAmount - $rec->discountAmount;
        $total = ($rec->type == 'credit_note') ? -1 * $total : $total;
        
        $aggregator->sum('invoicedAmount', $total);
        $aggregator->setIfNot('invoicedValior', $rec->date);
        $aggregator->setIfNot('paymentMethodId', $rec->paymentMethodId);
        
        if(isset($rec->dpAmount)){
        	if($rec->dpOperation == 'accrued'){
        		$aggregator->sum('downpaymentInvoiced', $total);
        	} elseif($rec->dpOperation == 'deducted') {
        		$aggregator->sum('downpaymentDeducted', $total);
        	}
        }
        
        $dQuery = sales_InvoiceDetails::getQuery();
        $dQuery->where("#invoiceId = {$rec->id}");
        
        // Намираме всички фактурирани досега продукти
        $invoiced = $aggregator->get('invoicedProducts');
        while ($dRec = $dQuery->fetch()) {
            $p = new stdClass();
            $p->classId     = $dRec->classId;
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->quantity    = $dRec->quantity * $dRec->quantityInPack;
            
            // Добавяме към фактурираните продукти
            $update = FALSE;
            if(count($invoiced)){
            	foreach ($invoiced as &$inv){
            		if($inv->classId == $p->classId && $inv->productId == $p->productId){
            			$inv->quantity += $p->quantity;
            			$update = TRUE;
            			break;
            		}
            	}
            }
           
            if(!$update){
            	$invoiced[] = $p;
            }
        }
        
        $aggregator->set('invoicedProducts', $invoiced);
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
    
    
    /**
     * Дали подадения номер е в позволения диапазон за номера на фактури
     * @param $number - номера на фактурата
     */
    private static function isNumberInRange($number)
    {
    	expect($number);
    	$conf = core_Packs::getConfig('sales');
    	
    	return ($conf->SALE_INV_MIN_NUMBER <= $number && $number <= $conf->SALE_INV_MAX_NUMBER);
    }
    
    
    /**
     * Ф-я връщаща следващия номер на фактурата, ако той е в границите
     * @return int - следващия номер на фактура
     */
    private static function getNexNumber()
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
     * Документа не може да се активира ако има детайл с количество 0
     */
    public static function on_AfterCanActivate($mvc, &$res, $rec)
    {
    	// ДИ и КИ могат да се активират винаги
    	if($rec->type != 'invoice' && isset($rec->changeAmount)){
    		$res = ($rec->changeAmount >= 0) ? TRUE : FALSE;
    		return;
    	}
    	
    	// Ако няма ид, не може да се активира документа
    	if(empty($rec->id) && !isset($rec->dpAmount)) return $res = FALSE;
    	
    	// Ако има Авансово плащане може да се активира
    	if(isset($rec->dpAmount)){
    		$res = (round($rec->dealValue, 2) < 0 || is_null($rec->dealValue)) ? FALSE : TRUE;
    		
    		return;
    	}
    	
    	$dQuery = $mvc->sales_InvoiceDetails->getQuery();
    	$dQuery->where("#invoiceId = {$rec->id}");
    	$dQuery->where("#quantity = 0");
    	
    	// Ако има поне едно 0-во к-во документа, не може да се активира
    	if($dQuery->fetch()){
    		$res = FALSE;
    	}
    }
    
    
     /**
      * Добавя ключови думи за пълнотекстово търсене, това са името на
      * документа или папката
      */
     function on_AfterGetSearchKeywords($mvc, &$res, $rec)
     {
     	// Тук ще генерираме всички ключови думи
     	$detailsKeywords = '';

     	// заявка към детайлите
     	$query = sales_InvoiceDetails::getQuery();
     	// точно на тази фактура детайлите търсим
     	$query->where("#invoiceId = '{$rec->id}'");
     	
	    while ($recDetails = $query->fetch()){
	        // взимаме заглавията на продуктите
	        $productTitle = cls::get($recDetails->classId)->getTitleById($recDetails->productId);
	        // и ги нормализираме
	        $detailsKeywords .= " " . plg_Search::normalizeText($productTitle);
	    }
	    
    	// добавяме новите ключови думи към основните
    	$res = " " . $res . " " . $detailsKeywords;
     }
     
     
	/**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        $row = static::recToVerbal($rec, 'type,number,-list');
        $row->number = strip_tags($row->number);
        $num = ($row->number) ? $row->number : $rec->id;
        
    	return tr("|{$row->type}|* №{$num}");
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