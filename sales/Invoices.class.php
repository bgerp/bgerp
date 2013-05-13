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
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, acc_TransactionSourceIntf';
    
    
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
    var $singleTitle = 'Фактура за продажба';
    
    
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
    var $canRead = 'admin, sales';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, sales';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, sales';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'sales/tpl/SingleLayoutInvoice2.shtml';
    
    
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
        $this->FLD('contragentAccItemId', 
            'acc_type_Item(lists=' . self::CLIENTS_ACC_LIST . ')', 'notNull,input=none,column=none');
        $this->FLD('contragentName', 'varchar', 'caption=Получател->Име, mandatory');
        $this->FLD('responsible', 'varchar(255)', 'caption=Получател->Отговорник');
        $this->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)', 'caption=Получател->Държава,mandatory');
        $this->FLD('contragentVatNo', 'drdata_VatType', 'caption=Получател->ЕИК/VAT №, mandatory');
        $this->FLD('contragentPCode', 'varchar(16)', 'caption=Получател->П. код,recently,class=pCode');
        $this->FLD('contragentPlace', 'varchar(64)', 'caption=Получател->Град,class=contactData');
        $this->FLD('contragentAddress', 'varchar(255)', 'caption=Получател->Адрес,class=contactData');
        $this->FLD('paymentMethodId', 'key(mvc=salecond_PaymentMethods, select=name)', 'caption=Плащане->Начин');
        $this->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=bankAccountId, allowEmpty)', 'caption=Плащане->Банкова с-ка, width:100%, export=Csv');
		$this->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Плащане->Каса');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута->Код,width=6em');
        $this->FLD('rate', 'double(decimals=2)', 'caption=Валута->Курс,width=6em'); 
        $this->FLD('deliveryId', 'key(mvc=salecond_DeliveryTerms, select=codeName, allowEmpty)', 'caption=Доставка->Условие');
        $this->FLD('deliveryPlace', 'varchar', 'caption=Доставка->Място');
        $this->FLD('vatDate', 'date(format=d.m.Y)', 'caption=Данъци->Дата на ДС');
        $this->FLD('vatRate', 'enum(yes=с начисляване,freed=освободено,export=без начисляване)', 'caption=Данъци->ДДС %');
        $this->FLD('vatReason', 'varchar(255)', 'caption=Данъци->Основание'); // TODO plg_Recently
		$this->FLD('additionalInfo', 'richtext(rows=6)', 'caption=Допълнително->Бележки,width:100%');
        $this->FLD('dealValue', 'double(decimals=2)', 'caption=Стойност, input=none');
		$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
        
        $this->FLD('type', 
            'enum(invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие)', 
            'caption=Вид, input=none'
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
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        
        if (!$form->rec->id) {
            
        	// При създаване на нова ф-ра зареждаме полетата на 
            // формата с разумни стойности по подразбиране.
            $mvc::setFormDefaults($form);
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
	 * Генерира фактура ако идва от продажба или пос продажба
	 */
	public static function on_AfterCreate($mvc, $rec)
    {
    	if(!empty($rec->originId)){
    		
    		// Ако се генерира от продажба
    		$origin = doc_Containers::getDocument($rec->originId, 'store_ShipmentIntf');
        	$products = $origin->getShipmentProducts();
    	} elseif($rec->docType && $rec->docId) {
    		
    		// Ако се генерира от пос продажба
    		$origin = cls::get($rec->docType);
    		$products = $origin->getShipmentProducts($rec->docId);
    	}
    	
    	if(isset($products) && count($products) != 0){
	    	
    		// Записваме информацията за продуктите в детайла
	    	foreach ($products as $product){
	    		$dRec = new stdClass();
	    		$dRec->invoiceId = $rec->id;
	    		$dRec->productId = $product->productId;
	    		$dRec->packagingId = $product->packagingId;
	    		$dRec->policyId = $product->policyId;
	    		$dRec->price = $product->price;
	    		$dRec->quantityInPack = $product->quantityInPack;
	    		$dRec->quantity = $product->quantity;
	    		$dRec->packQuantity = $product->quantity * $product->quantityInPack;
	    		$dRec->amount = $dRec->packQuantity * $product->price;
	    		$mvc->sales_InvoiceDetails->save($dRec);
	    	}
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
            // Създаване / обновяване на перото за контрагента
            $coverClass = doc_Folders::fetchCoverClassName($rec->folderId);
            $coverId    = doc_Folders::fetchCoverId($rec->folderId);
            
            expect($clientsListRec = acc_Lists::fetchBySystemId(self::CLIENTS_ACC_LIST),
                "Липсва номенклатура за клиенти (systemId: " . self::CLIENTS_ACC_LIST . ")"
            );
            
            $rec->contragentAccItemId = acc_Lists::updateItem($coverClass, $coverId, $clientsListRec->id);
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
        $tpl->replace($ownCompanyData->vatNo, 'MyCompanyVatNo');
        
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
    	
    	$row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->date);
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	if($fields['-single']){
	    	if($rec->dealValue){
	    		$row->baseAmount = $double->toVerbal($rec->baseAmount);
	    		
	    		$percent = cls::get('type_Percent');
	    		$parts = explode(".", $rec->vatPercent);
	    		$percent->params['decimals'] = count($parts[1]);
	    		
				$row->vatPercent = $percent->toVerbal($rec->vatPercent);
				$row->vatAmount = $double->toVerbal($rec->vatAmount);
				$row->total = $double->toVerbal($rec->total);
				
				$SpellNumber = cls::get('core_SpellNumber');
				$row->amountVerbal = $SpellNumber->asCurrency($rec->total, 'bg', FALSE);
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
     * Зарежда разумни начални стойности на полетата на форма за фактура.
     */
    public static function setFormDefaults(core_Form $form)
    {
        // Днешна дата в полето `date`
        if (empty($form->rec->date)) {
            $form->rec->date = dt::now();
        }

        // Данни за контрагент
        static::populateContragentData($form);
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
        
        if (!empty($contragentData->company)) {
            // Случай 1 или 2: има данни за фирма
            $rec->contragentName    = $contragentData->company;
            $rec->contragentAddress = trim(
                sprintf("%s %s\n%s", 
                    $contragentData->place,
                    $contragentData->pCode,
                    $contragentData->address
                )
            );
            $rec->contragentVatNo = $contragentData->vatNo;
        } elseif (!empty($contragentData->person)) {
            // Случай 3: само данни за физическо лице
            $rec->contragentName    = $contragentData->person;
            $rec->contragentAddress = $contragentData->pAddress;
        }
        
        $rec->currencyId = drdata_Countries::fetchField($rec->contragentCountryId, 'currencyCode');
        if($ownAcc = bank_OwnAccounts::getCurrent('id', FALSE)){
	        	$form->setDefault('accountId', $ownAcc);
	        } 
    }
    
    
    /**
     * След проверка на ролите
     */
	public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	switch ($action) {
    		case 'edit':
	    	    // Фактурата неможе се едитва, ако е възоснова на продажба
	    		if($rec->originId || ($rec->docType && $rec->docId)){
	    			$res = 'no_one';
	    		}
    			break;
           
            case 'conto':
            case 'activate':
               if (empty($rec->id) || $rec->state != 'draft') {
                    // Незаписаните продажби не могат нито да се контират, нито да се активират
                    $res = 'no_one';
                    break;
                } 
               
                if (($transaction = $mvc->getValidatedTransaction($rec)) === FALSE) {
                    // Невъзможно е да се генерира транзакция
                    $res = 'no_one';
                    break;
                }
                
                // Активиране е позволено само за продажби, които не генерират транзакции
                // Контиране е позволено само за продажби, които генерират транзакции
                $deniedAction = ($transaction->isEmpty() ? 'conto' : 'activate');
               
                if ($action == $deniedAction) {
                    $res = 'no_one';
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
        
        //Създаваме шаблона
        $tpl = new ET(tr("Моля запознайте се с приложената фактура:") . "\n#[#handle#]");
        
        //Заместваме хендъра в шаблона
        $tpl->append($handle, 'handle');
        
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
        $rec = (object)array(
            'id' => $id,
            'state' => 'active'
        );
        
        return self::save($rec);
    }
    
    
    /**
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 */
    public static function getTransaction($id)
    {
       	// Извличаме записа
        expect($rec = self::fetchRec($id));
        static::prepareAdditionalInfo($rec);
        $contragentItem = acc_Items::fetch($rec->contragentAccItemId);
        
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
                           array($contragentItem->classId, $contragentItem->objectId),
                            array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->date)),
                        'quantity' => $rec->vatAmount,
                    ),
                    
                    'credit' => array(
                        '4532', // кредитна сметка
                        'quantity' => $rec->vatAmount,
                    ));
        }
        
      	$result->entries = $entries;
      	return $result;
    }
    
    
    /**
     * @see acc_TransactionSourceIntf::rejectTransaction
     */
    public static function rejectTransaction($id)
    {
        $rec = self::fetch($id, 'id,state,valior');
        
        if ($rec) {
            static::reject($id);
        }
    }
}
