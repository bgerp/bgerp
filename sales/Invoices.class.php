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
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
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
					doc_EmailCreatePlg, doc_ActivatePlg, bgerp_plg_Blank, plg_Printing,
                    doc_SequencerPlg, doc_plg_BusinessDoc';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'number, vatDate, contragentName, contragentVatNo, contragentCountryId ';
    
    
    /**
     * Колоната, в която да се появят инструментите на plg_RowTools
     * 
     * @var string
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
    var $singleLayoutFile = 'sales/tpl/SingleLayoutInvoice.shtml';
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'number, date, contragentName';
    
    /**
     * Име на полето съдържащо номер на фактурата
     * 
     * @var int
     * @see doc_SequencerPlg
     */
    var $sequencerField = 'number';
    
    
    /**
     * SystemId на номенклатура "Клиенти"
     * 
     * @var string
     */
    const CLIENTS_ACC_LIST = 'clients';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // Дата на фактурата
        $this->FLD('date', 'date(format=d.m.Y)', 'caption=Дата,  notNull, mandatory');
        
        // Място на сделката
        $this->FLD('place', 'varchar(64)', 'caption=Място, mandatory');
        
        // Номер на фактурата
        $this->FLD('number', 'int', 'caption=Номер, export=Csv');

        // Съставил фактурата
        $this->FLD('creatorName', 'varchar(255)', 'caption=Съставил, input=none');
        
        /*
         * Данни за контрагента - получател на фактурата
         */
        // Перо в номенклатурата с клиенти съответстващо на контрагента
        $this->FLD('contragentAccItemId', 
            'acc_type_Item(lists=' . self::CLIENTS_ACC_LIST . ')', 'notNull,input=none,column=none');
        $this->FLD('contragentName', 'varchar', 'caption=Получател->Име, mandatory,width=100%');
        $this->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName)', 'caption=Получател->Държава,mandatory,width=100%');
        $this->FLD('contragentVatNo', 'drdata_VatType', 'caption=Получател->ЕИК/VAT №, mandatory');
        $this->FLD('contragentPCode', 'varchar(16)', 'caption=Получател->П. код,recently,class=pCode');
        $this->FLD('contragentPlace', 'varchar(64)', 'caption=Получател->Град,class=contactData');
        $this->FLD('contragentAddress', 'varchar(255)', 'caption=Получател->Адрес,class=contactData');
        
        // TODO да се мине през функцията за канонизиране от drdata_Vats 
        $this->FLD('vatCanonized', 'drdata_VatType', 'caption=Получател->Vat Canonized, input=none');

        // Плащане
        $this->FLD('paymentMethodId', 'key(mvc=bank_PaymentMethods, select=name)', 'caption=Плащане->Начин');
                
        // Наша банкова сметка (при начин на плащане по банков път)
        $this->FLD('accountId', 'key(mvc=bank_Accounts, select=iban)', 'caption=Плащане->Банкова с-ка, width:100%, export=Csv');

        // Валута
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code, allowEmpty)', 'caption=Валута->Код');
        $this->FLD('currencyRate', 'double', 'caption=Валута->Курс');  
        
        // Доставка
        $this->FLD('deliveryId', 'key(mvc=trans_DeliveryTerms, select=name, allowEmpty)', 'caption=Доставка->Условие');
        $this->FLD('deliveryPlace', 'varchar', 'caption=Доставка->Място');
        
        // Данъци
        $this->FLD('vatDate', 'date(format=d.m.Y)', 'caption=Данъци->Дата на ДС');
        $this->FLD('vatRate', 'percent', 'caption=Данъци->ДДС %');
        $this->FLD('vatReason', 'varchar(255)', 'caption=Данъци->Основание'); // TODO plg_Recently

        // Допълнителна информация
        $this->FLD('additionalInfo', 'richtext(rows=6)', 'caption=Допълнително->Бележки,width:100%');
        
        // Скрити полета
        $this->FLD('dealValue', 'double(decimals=2)', 'caption=Стойност, input=none');

        $this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
        
        $this->FLD('type', 
            'enum(invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие)', 
            'caption=Вид, input=none'
        );
         
        
        $this->setDbUnique('number');
    }
    
    
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        /* @var $form core_Form */
        $form = $data->form;
        
        if (!$form->rec->id) {
            /*
             * При създаване на нова ф-ра зареждаме полетата на формата с разумни стойности по 
             * подразбиране.
             */
            $mvc::setFormDefaults($form);
        }
        
        $mvc::populateContragentData($form);
    }
    
    
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if (!$form->isSubmitted()) {
            return;
        }

        //
        // Идея за шаблон за валидация на данни от потребителя. Предимства:
        //
        //  * Кратки и добре обособени методи за валидация - по един за поле;
        //  * възможност за добавяне нови на валидационни правила в плъгини.
        //

        // Не е добра идея, защото:
        // 1. 90% от полетата се валидират чрез параметрите в описанията си.
        //    Това тук ще генерира много излишни събития
        // 2. Идеята на този метод е да се правят валидации на няколко полета едновременно.
        //    За повечето полета в описанието на типа можем да сложим valid=Class::Method, 
        //    който да валидира полето, при положение, че неговата валидност не зависи от др. полета
        // 3. По-доброто тук е да се извикат директно и последователно функциите за валидиране. 
        //    Плъгините пак са възможни, защото действието на 'setError' и 'setWarning' е монотонно

        acc_Periods::checkDocumentDate($form);

        foreach ($mvc->fields as $fName=>$field) {
            $mvc->invoke('Validate' . ucfirst($fName), array($form->rec, $form));
        }

       
    }
    
    
    /**
     * Валидиране на полето 'date' - дата на фактурата
     * 
     * Предупреждение ако има фактура с по-нова дата (само при update!)
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param core_Form $form
     */
    public function on_ValidateDate(core_Mvc $mvc, $rec, core_Form $form)
    {
        if (empty($rec->date)) {
            return;
        }
        
        if (!empty($rec->id)) {
            // Промяна на съществуваща ф-ра - не правим нищо
            return;
        }
        
        /* @var $query core_Query */
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
                    dt::mysql2verbal($newestInvoiceRec->date, 'd-m-y') .
                ')'
            );
        }
    }
    
    
    /**
     * Валидиране на полето 'number' - номер на фактурата
     * 
     * Предупреждение при липса на ф-ра с номер едно по-малко от въведения.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param core_Form $form
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
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param core_Form $form
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
     * 
     * @param core_Mvc $mvc
     * @param core_ET $tpl
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
    }
    
    
    /**
     * Зарежда разумни начални стойности на полетата на форма за фактура.
     * 
     * @param core_Form $form
     */
    public static function setFormDefaults(core_Form $form)
    {
        // Днешна дата в полето `date`
        if (empty($form->rec->date)) {
            $form->rec->date = dt::now();
        }
        
        // ДДС % по-подразбиране - от периода към датата на ф-рата
        $periodRec = acc_Periods::fetchByDate($form->rec->date);
        if ($periodRec) {
            $form->rec->vatRate = $periodRec->params->vatRate;
        }

        // Данни за контрагент
        static::populateContragentData($form);
    }
    
    
    /**
     * Изчислява данните на контрагента и ги зарежда във форма за създаване на нова ф-ра
     * 
     * По дефиниция, данните за контрагента се вземат от:
     * 
     *  * най-новата активна ф-ра в папката, в която се създава новата
     *  * ако няма такава - от корицата на тази папка
     * 
     * @param core_Form $form форма, в чиито полета да се заредят данните за контрагента
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
        $contragentData = static::getDefaultContragentData($folderId);
        
        /*
         * Разглеждаме четири случая според данните в $contragentData
         * 
         *  1. Има данни за фирма и данни за лице
         *  2. Има само данни за фирма
         *  3. Има само данни за лице
         *  4. Нито едно от горните не е вярно
         */
        
        if (empty($contragentData->company) && empty($contragentData->name)) {
            // Случай 4: нито фирма, нито лице
            // TODO доколко допустимо е да се стигне до тук?
            expect(FALSE, 'Проблем с данните за контрагент по подразбиране');
            return;
        }
        
        $rec->contragentCountryId = $contragentData->countryId;
        
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
            
            if (!empty($contragentData->name)) {
                // Случай 1: данни за фирма + данни за лице
                
                // TODO за сега не правим нищо допълнително
            }
        } elseif (!empty($contragentData->name)) {
            // Случай 3: само данни за физическо лице
            $rec->contragentName    = $contragentData->name;
            $rec->contragentAddress = $contragentData->pAddress;
        }
        
        if (!empty($rec->contragentCountryId)) {
            $currencyCode    = drdata_Countries::fetchField($rec->contragentCountryId, 'currencyCode');
            $rec->currencyId = currency_Currencies::fetchField("#code = '{$currencyCode}'", 'id');
            
            if ($rec->currencyId) {
                // Задаване на избор за банкова сметка.
                $ownBankAccounts = bank_Accounts::makeArray4Select('iban',
                    "#contragentCls = " . crm_Companies::getClassId() . " AND " .
                    "#contragentId  = " . BGERP_OWN_COMPANY_ID
                );
                
                $form->getField('accountId')->type->options = $ownBankAccounts;
            }
        }
    }


    /**
     * Данни за контрагент подразбиране при създаване на нова фактура.
     *
     * По дефиниция, данните за контрагента се вземат от:
     *
     *  * най-новата активна (т.е. контирана) ф-ра в папката, в която се създава новата
     *  * ако няма такава - от корицата на тази папка; класът на тази корица задължително трябва
     *                      да поддържа интерфейса doc_ContragentDataIntf
     *
     * @param int $folderId key(mvc=doc_Folders)
     * @return stdClass @see doc_ContragentDataIntf::getContragentData()
     */
    protected static function getDefaultContragentData($folderId)
    {
        if ($lastInvoiceRec = static::getLastActiveInvoice($folderId)) {
            $sourceClass    = __CLASS__;
            $sourceObjectId = $lastInvoiceRec->id;
        } else {
            $sourceClass    = doc_Folders::fetchCoverClassName($folderId);
            $sourceObjectId = doc_Folders::fetchCoverId($folderId);
        }
    
        if (!cls::haveInterface('doc_ContragentDataIntf', $sourceClass)) {
            // Намерения клас-източник на данни за контрагент не поддържа doc_ContragentDataIntf
            return;
        }
    
        $contragentData = $sourceClass::getContragentData($sourceObjectId);
    
        return $contragentData;
    }
    
    
    /**
     * Данните на най-новата активна (т.е. контирана) ф-ра в зададена папка
     *
     * @param int $folderId key(mvc=doc_Folders)
     * @return stdClass обект-данни на модела sales_Invoices; NULL ако няма такава ф-ра
     */
    protected static function getLastActiveInvoice($folderId)
    {
        /* @var $query core_Query */
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
     * Връща тялото наимей по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = sales_Invoices::getHandle($id);
        
        //Създаваме шаблона
        $tpl = new ET(tr("Моля запознайте се с приложената фактура:") . "\n#[#handle#]");
        
        //Заместваме датата в шаблона
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
     * @param $firstClass string класът на корицата на папката
     */
    public static function canAddToFolder($folderId, $folderClass)
    {
        if (empty($folderClass)) {
            $folderClass = doc_Folders::fetchCoverClassName($folderId);
        }
    
        return $folderClass == 'crm_Companies' || $folderClass == 'crm_Persons';
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
		$row = new stdClass();

        //$row->title = $this->getHandle($rec->id);   //TODO може да се премени
        $row->title = "Фактура №{$rec->number}";
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;
        
        $row->recTitle = $row->title;
        
        return $row;
    }
    
    
    public static function getHandle($id)
    {
        $self = cls::get(get_called_class());
        
        $number = $self->fetchField($id, 'number');
        
        return $self->abbr . $number;
    } 
    
    
    public static function fetchByHandle($parsedHandle)
    {
        return static::fetch("#number = '{$parsedHandle['id']}'");
    } 
}
