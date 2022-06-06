<?php


/**
 * Изходящи фактури
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_Invoices extends deals_InvoiceMaster
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, acc_TransactionSourceIntf=sales_transaction_Invoice, bgerp_DealIntf, deals_InvoiceSourceIntf';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Inv';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
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
    public $loadList = 'plg_RowTools2, sales_Wrapper, plg_Sorting, acc_plg_Contable, plg_Clone, plg_Printing, cond_plg_DefaultValues, doc_DocumentPlg, bgerp_plg_Export,
					doc_EmailCreatePlg, recently_Plugin,deals_plg_DpInvoice,doc_plg_Sequencer2,
                    doc_plg_HidePrices, doc_plg_TplManager, drdata_plg_Canonize, bgerp_plg_Blank, acc_plg_DocumentSummary, change_Plugin,cat_plg_AddSearchKeywords, plg_Search,plg_LastUsedKeys';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number, date, dueDate=Срок, place, folderId, currencyId=Валута, dealValue=Общо, valueNoVat=Без ДДС, vatAmount, type';
    
    
    /**
     * При създаване на имейл, дали да се използва първият имейл от списъка
     */
    public $forceFirstEmail = true;
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,salesMaster,manager';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'sales_InvoiceDetails' ;
    
    
    /**
     * Кой може да сторнира
     */
    public $canRevert = 'salesMaster, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,invoicer';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,sales,acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,invoicer';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,invoicer';
    
    
    /**
     * Кой има право да експортва?
     */
    public $canExport = 'ceo,invoicer';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo,invoicer';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number, folderId, contragentName';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/invoice.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.3|Търговия';
    
    
    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'sales_InvoiceDetails';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'place' => 'defMethod',
        'responsible' => 'lastDocUser|lastDoc',
        'contragentCountryId' => 'clientData|lastDocUser|lastDoc',
        'contragentVatNo' => 'clientData|lastDocUser|lastDoc',
        'contragentEori' => 'clientData|lastDocUser|lastDoc',
        'uicNo' => 'clientData|lastDocUser|lastDoc',
        'contragentPCode' => 'clientData|lastDocUser|lastDoc',
        'contragentPlace' => 'clientData|lastDocUser|lastDoc',
        'contragentAddress' => 'clientData|lastDocUser|lastDoc',
        'accountId' => 'lastDocUser|lastDoc',
        'template' => 'lastDocUser|lastDoc|defMethod',
    );
    
    
    /**
     * Кои полета ако не са попълнени във визитката на контрагента да се попълнят след запис
     */
    public static $updateContragentdataField = array(
        'vatId' => 'contragentVatNo',
        'uicId' => 'uicNo',
        'egn' => 'uicNo',
        'pCode' => 'contragentPCode',
        'place' => 'contragentPlace',
        'address' => 'contragentAddress',
    );
    
    
    /**
     * Кой може да променя активирани записи
     *
     * @see change_Plugin
     */
    public $canChangerec = 'accMaster, ceo, invoicer';
    
    
    /**
     * Кои полета да могат да се променят след активация
     */
    public $changableFields = 'responsible,contragentCountryId, contragentPCode, contragentPlace, contragentAddress, dueTime, dueDate, additionalInfo,accountId,paymentType,template';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'sales_InvoiceDetails';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, date,dueDate,vatDate,modifiedOn';
    
    
    /**
     * Поле за избор на диапазон на документа
     * 
     * @see doc_plg_Sequencer2
     */
    public $rangeNumFld = 'numlimit';
    
    
    /**
     * Да се добавя ли номера при генериране
     * 
     * @see doc_plg_Sequencer2
     */
    public $addNumberOnActivation = true;
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'numlimit';


    /**
     * Стратегии за добавяне на артикули след създаване от източника
     */
    protected $autoAddProductStrategies = array('onlyFromDeal' => "Всички от договора", 'shippedNotInvoiced' => 'Нефактурираните експедирани', 'none' => 'Без');


    /**
     * Описание на модела
     */
    public function description()
    {
        parent::setInvoiceFields($this);
        
        $this->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=title, allowEmpty)', 'caption=Плащане->Банкова с-ка, changable');
        $this->FLD('numlimit', "key(mvc=cond_Ranges,select=id)", 'caption=Допълнително->Диапазон, after=template,input=hidden,notNull,default=1');
        $this->FLD('number', 'bigint(21)', 'caption=Номер, after=place,input=none');
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно)', 'caption=Статус, input=none');
        $this->FLD('type', 'enum(invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие,dc_note=Известие)', 'caption=Вид, input=hidden');
        $this->FLD('template', 'key(mvc=doc_TplManager,select=name)', 'caption=Допълнително->Изглед,notChangeableByContractor,silent,removeAndRefreshForm=additionalInfo');
        $this->FNC('selectInvoiceText', 'enum(,private=Частно,public=Общо,both=Частно и общо)', 'caption=Допълнително->Други условия,removeAndRefreshForm=additionalInfo,silent,before=additionalInfo');
        $this->setField('contragentCountryId', 'removeAndRefreshForm=additionalInfo');

        $this->setDbUnique('number');
    }
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Фактура нормален изглед', 'content' => 'sales/tpl/InvoiceHeaderNormal.shtml',
            'narrowContent' => 'sales/tpl/InvoiceHeaderNormalNarrow.shtml', 'lang' => 'bg');
        $tplArr[] = array('name' => 'Фактура кратък изглед', 'content' => 'sales/tpl/InvoiceHeaderNormalShort.shtml',
            'narrowContent' => 'sales/tpl/InvoiceHeaderNormalNarrow.shtml', 'lang' => 'bg');
        $tplArr[] = array('name' => 'Фактура за факторинг', 'content' => 'sales/tpl/InvoiceFactoring.shtml',
            'narrowContent' => 'sales/tpl/InvoiceFactoringNarrow.shtml', 'lang' => 'bg');
        $tplArr[] = array('name' => 'Invoice', 'content' => 'sales/tpl/InvoiceHeaderNormalEN.shtml',
            'narrowContent' => 'sales/tpl/InvoiceHeaderNormalNarrowEN.shtml', 'lang' => 'en', 'oldName' => 'Фактура EN');
        $tplArr[] = array('name' => 'Invoice short', 'content' => 'sales/tpl/InvoiceHeaderShortEN.shtml',
            'narrowContent' => 'sales/tpl/InvoiceHeaderShortNarrowEN.shtml', 'lang' => 'en');
        $tplArr[] = array('name' => 'Фактура с цени в евро', 'content' => 'sales/tpl/InvoiceHeaderEuro.shtml', 'lang' => 'bg');
        $tplArr[] = array('name' => 'Счетоводна фактура', 'content' => 'sales/tpl/InvoiceAccView.shtml', 'lang' => 'bg');
        
        $res = '';
        $res .= doc_TplManager::addOnce($this, $tplArr);
        
        // Добавяне на първия диапазон за фактурите
        cond_Ranges::add('sales_Invoices', 1, 1999999, null, 'acc', 1, false);
        
        return $res;
    }
    
    
    /**
     * Попълва дефолт данните от проформата
     */
    private function prepareFromProforma($proformaRec, &$form)
    {
        if (isset($form->rec->id)) {
            
            return;
        }
        
        $unsetFields = array('id', 'number', 'state', 'searchKeywords', 'containerId', 'brState', 'lastUsedOn', 'createdOn', 'createdBy', 'modifiedOn', 'modifiedBy', 'dealValue', 'vatAmount', 'discountAmount', 'sourceContainerId', 'additionalInfo', 'dueDate', 'dueTime', 'template', 'activatedOn', 'activatedBy');
        foreach ($unsetFields as $fld) {
            unset($proformaRec->{$fld});
        }
        
        foreach (($proformaRec) as $k => $v) {
            $form->rec->{$k} = $v;
        }
        if ($form->rec->dpAmount) {
            $form->rec->dpAmount = abs($form->rec->dpAmount);
        }
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $defInfo = '';

        if(isset($rec->id)){
            $form->setDefault('selectInvoiceText', 'inputed');
        } else {
            $form->setDefault('selectInvoiceText', 'both');
        }

        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        $firstRec = $firstDoc->rec();

        if ($rec->sourceContainerId) {
            $Source = doc_Containers::getDocument($rec->sourceContainerId);
            if ($Source->isInstanceOf('sales_Proformas')) {
                if ($proformaRec = $Source->fetch()) {
                    $mvc->prepareFromProforma($proformaRec, $form);
                    $handle = sales_Proformas::getHandle($Source->that);
                    $mvc->pushTemplateLg($rec->template);
                    $defInfo .= (($defInfo) ? ' ' : '') . tr('По проформа|* #') . $handle . "\n";
                    core_Lg::pop();
                }
            }
        }
        
        parent::prepareInvoiceForm($mvc, $data);
        if(empty($rec->id)){
            $form->setDefault('importProducts', 'shippedNotInvoiced');
        }

        if(!empty($form->rec->contragentVatNo)){
            $Vats = cls::get('drdata_Vats');
            list(, $vies) = $Vats->check($form->rec->contragentVatNo);
            $vies = trim($vies);
            if(!empty($vies)){
                $form->info = "<b>VIES</b>: {$vies}";
            }
        }
        
        $form->setField('contragentPlace', 'mandatory');
        $form->setField('contragentAddress', 'mandatory');
        
        if ($data->aggregateInfo) {
            if ($accId = $data->aggregateInfo->get('bankAccountId')) {
                $form->setDefault('accountId', bank_OwnAccounts::fetchField("#bankAccountId = {$accId}", 'id'));
            }
        }
        
        if (empty($data->flag)) {
            if ($ownAcc = bank_OwnAccounts::getCurrent('id', false)) {
                $form->setDefault('accountId', $ownAcc);
            }
        }

        $tLang = doc_TplManager::fetchField($rec->template, 'lang');
        core_Lg::push($tLang);

        $showSale = core_Packs::getConfigValue('sales', 'SALE_INVOICES_SHOW_DEAL');

        if ($showSale == 'yes' && empty($rec->sourceContainerId)) {
            // Ако продажбата приключва други продажби също ги попълва в забележката
            if ($firstRec->closedDocuments) {
                $docs = keylist::toArray($firstRec->closedDocuments);
                $closedDocuments = '';
                foreach ($docs as $docId) {
                    $dRec = sales_Sales::fetch($docId);
                    $date = sales_Sales::getVerbal($dRec, 'valior');
                    $handle = sales_Sales::getHandle($dRec->id);
                    $closedDocuments .= " #{$handle}/{$date},";
                }
                $closedDocuments = trim($closedDocuments, ', ');
                $defInfo .= tr('|Съгласно сделки|*: ') . $closedDocuments . PHP_EOL;
            } else {
                $handle = sales_Sales::getHandle($firstRec->id);
                Mode::push('text', 'plain');
                $valior = $firstDoc->getVerbal('valior');
                Mode::pop('text');
                $defInfo .= tr('Съгласно сделка') . ": #{$handle}/{$valior}";

                // Ако продажбата има референтен номер, попълваме го в забележката
                if ($firstRec->reff) {

                    // Ако рефа е по офертата на сделката към която е фактурата
                    if (isset($firstRec->originId)) {
                        $origin = doc_Containers::getDocument($firstRec->originId);
                        if ($firstRec->reff == $origin->getHandle()) {
                            $firstRec->reff = '#' . $firstRec->reff;
                        }
                    }
                    $defInfo .= ' ' . tr("({$firstRec->reff})") . PHP_EOL;
                }
            }
        }

        core_Lg::pop();
        $invTextPrivate = cond_Parameters::getParameter($firstRec->contragentClassId, $firstRec->contragentId, 'invoiceText');
        $invTextPublic = '';
        if(isset($rec->contragentCountryId)){
            $invTextPublic = cond_Countries::getParameterByCountryId($rec->contragentCountryId, 'invoiceText');
        }
        if(!empty($invTextPrivate) && !empty($invTextPublic) && md5($invTextPrivate) != md5($invTextPublic)){
            $form->setField('selectInvoiceText', 'input');
        }

        // Ако има дефолтен текст за фактура добавяме и него
        if(in_array($rec->selectInvoiceText, array('private', 'both'))){
            if ($invTextPrivate = cond_Parameters::getParameter($firstRec->contragentClassId, $firstRec->contragentId, 'invoiceText')) {
                $defInfo .= "\n" . $invTextPrivate;
            }
        }

        // Ако има дефолтен текст за държавата и е различен, добавя се и той
        if(in_array($rec->selectInvoiceText, array('public', 'both')) && isset($rec->contragentCountryId)) {
            if ($invTextPublic = cond_Countries::getParameterByCountryId($rec->contragentCountryId, 'invoiceText')) {
                if(md5($invTextPrivate) != md5($invTextPublic)){
                    $defInfo .= "\n";
                    if(!empty($invTextPrivate)){
                        $defInfo .= "\n";
                    }
                    $defInfo .= $invTextPublic;
                }
            }
        }

        // Задаваме дефолтния текст
        $form->setDefault('additionalInfo', $defInfo);
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = $form->rec;
        parent::inputInvoiceForm($mvc, $form);
        
        if ($form->isSubmitted()) {
            
            // Валидна ли е датата (при само промяна няма да се изпълни)
            $warning = null;
            if (!$mvc->isAllowedToBePosted($rec, $warning) && $rec->__isBeingChanged !== true) {
                $form->setError('date', $warning);
            }
            
            if ($rec->type != 'dc_note' && empty($rec->accountId)) {
                if ($paymentMethodId = doc_Threads::getFirstDocument($rec->threadId)->fetchField('paymentMethodId')) {
                    $paymentPlan = cond_PaymentMethods::fetch($paymentMethodId);
                    $timeBalance = $paymentPlan->timeBalancePayment;
                    
                    if ((!empty($timeBalance) && $timeBalance > 86400) || $paymentPlan->type == 'bank' || $rec->paymentType == 'bank') {
                        $form->setWarning('accountId', 'Сигурни ли сте, че не е нужно да се посочи и банкова сметка|*?');
                    }
                }
            }
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
        
        $prevNumber = intval($rec->number) - 1;
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
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        if (!Mode::is('printing')) {
            $original = tr('ОРИГИНАЛ');
            $tpl->replace($original, 'INV_STATUS');
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        if ($rec->type == 'invoice' && $rec->state == 'active' && $rec->dpOperation != 'accrued') {
            if (dec_Declarations::haveRightFor('add', (object) array('originId' => $data->rec->containerId, 'threadId' => $data->rec->threadId))) {
                $data->toolbar->addBtn('Декларация', array('dec_Declarations', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true), 'ef_icon=img/16/declarations.png, row=2, title=Създаване на декларация за съответсвие');
            }
        }
        
        if ($rec->state == 'active') {
            $minus = ($rec->type == 'dc_note') ? 0 : 0.005;
            $amount = ($rec->dealValue - $rec->discountAmount) + $rec->vatAmount - $minus;
            $amount /= ($rec->displayRate) ? $rec->displayRate : $rec->rate;
            $amount = round($amount, 2);
            $originId = isset($rec->originId) ? $rec->originId : doc_Threads::getFirstContainerId($rec->threadId);
            
            if ($amount < 0) {
                if (cash_Rko::haveRightFor('add', (object) array('threadId' => $rec->threadId, 'fromContainerId' => $rec->containerId))) {
                    $btnRow = ($rec->paymentType != 'cash') ? 2 : 1;
                    $data->toolbar->addBtn('РКО', array('cash_Rko', 'add', 'originId' => $originId, 'fromContainerId' => $rec->containerId, 'termDate' => $rec->dueDate,'ret_url' => true), "row={$btnRow},ef_icon=img/16/money_add.png,title=Създаване на нов разходен касов ордер към документа");
                }
                if (bank_SpendingDocuments::haveRightFor('add', (object) array('threadId' => $rec->threadId, 'fromContainerId' => $rec->containerId))) {
                    $btnRow = ($rec->paymentType == 'cash') ? 2 : 1;
                    $data->toolbar->addBtn('РБД', array('bank_SpendingDocuments', 'add', 'originId' => $originId, 'amountDeal' => abs($amount), 'fromContainerId' => $rec->containerId, 'termDate' => $rec->dueDate, 'ret_url' => true), "row={$btnRow},ef_icon=img/16/bank_add.png,title=Създаване на нов разходен банков документ");
                }
            } else {
                if (cash_Pko::haveRightFor('add', (object) array('threadId' => $rec->threadId, 'fromContainerId' => $rec->containerId))) {
                    $btnRow = ($rec->paymentType != 'cash') ? 2 : 1;
                    $data->toolbar->addBtn('ПКО', array('cash_Pko', 'add', 'originId' => $originId, 'fromContainerId' => $rec->containerId, 'termDate' => $rec->dueDate, 'ret_url' => true), "row={$btnRow},ef_icon=img/16/money_add.png,title=Създаване на нов приходен касов ордер към документа");
                }
                if (bank_IncomeDocuments::haveRightFor('add', (object) array('threadId' => $rec->threadId, 'fromContainerId' => $rec->containerId))) {
                    $btnRow = ($rec->paymentType == 'cash') ? 2 : 1;
                    $data->toolbar->addBtn('ПБД', array('bank_IncomeDocuments', 'add', 'originId' => $originId, 'amountDeal' => $amount, 'fromContainerId' => $rec->containerId, 'termDate' => $rec->dueDate, 'ret_url' => true), "row={$btnRow},ef_icon=img/16/bank_add.png,title=Създаване на нов приходен банков документ");
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        parent::getVerbalInvoice($mvc, $rec, $row, $fields);
        
        if ($fields['-single']) {
            if ($rec->accountId && $rec->paymentType != 'factoring') {
                $Varchar = cls::get('type_Varchar');
                $ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->accountId);
                
                $row->accountId = cls::get('iban_Type')->toVerbal($ownAcc->iban);
                $row->bank = $Varchar->toVerbal($ownAcc->bank);
                core_Lg::push($rec->tplLang);
                $row->bank = transliterate(tr($row->bank));
                $row->place = transliterate($row->place);
                core_Lg::pop();
                
                $row->bic = $Varchar->toVerbal($ownAcc->bic);
            }
            
            if(empty($rec->number)){
                $row->number = str::removeWhiteSpace(cond_Ranges::displayRange($rec->numlimit));
                $row->number = "<span style='color:blue;'>{$row->number}</span>";
                $row->number = ht::createHint($row->number, 'При активиране номерът ще бъде в този диапазон', 'notice', false);
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
        return false;
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        // Ако резултата е 'no_one' пропускане
        if ($res == 'no_one') {
            
            return;
        }
        
        if ($action == 'add' && isset($rec->threadId)) {
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            $docState = $firstDoc->fetchField('state');
            
            if (!($firstDoc->isInstanceOf('sales_Sales') && $docState == 'active')) {
                $res = 'no_one';
            }
        }
        
        // Само ceo,sales,invoicer могат да възстановят фактура
        if ($action == 'restore' && isset($rec)) {
            if ($rec->brState == 'active') {
                if (!haveRole('ceo,sales,invoicer', $userId)) {
                    $res = 'no_one';
                }
            }
        }
        
        if ($action == 'reject' && isset($rec)) {
            if ($rec->state == 'active') {
                if (!haveRole('ceo,accMaster', $userId)) {
                    $today = dt::today();
                    $dayForInvoice = acc_Setup::get('DATE_FOR_INVOICE_DATE');
                    $monthValior = dt::mysql2verbal($rec->date, 'm.y');
                    $monthNow = dt::mysql2verbal($today, 'm.y');
                    $dateNow = dt::mysql2verbal($today, 'd');
                    $valiorMonthPlus1 = dt::mysql2verbal(dt::addMonths(1, $rec->date), 'm.y');
                    
                    if(($valiorMonthPlus1 == $monthNow && $dayForInvoice > $dateNow) || $monthNow == $monthValior) {
                        if (!haveRole('ceo,sales,invoicer', $userId)) {
                            $res = 'no_one';
                        }
                    } else {
                        $res = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * След рендиране на копия за принтиране
     *
     * @param core_Mvc $mvc     - мениджър
     * @param core_ET  $copyTpl - копие за рендиране
     * @param int      $copyNum - пореден брой на копието за принтиране
     */
    public static function on_AfterRenderPrintCopy($mvc, &$copyTpl, $copyNum, $rec)
    {
        if ($rec->tplLang == 'bg') {
            $inv_status = ($copyNum == '1') ?  'ОРИГИНАЛ' : 'КОПИЕ';
        } else {
            $inv_status = ($copyNum == '1') ?  'ORIGINAL' : 'COPY';
        }
        
        $copyTpl->replace($inv_status, 'INV_STATUS');
    }
    
    
    /**
     * Връща сумата на ддс-то на платените в брой фактури, в основната валута
     *
     * @param datetime $from - от
     * @param datetime $to   - до
     *
     * @return float $amount - сумата на ддс-то на платените в брой фактури
     */
    public static function getVatAmountInCash($from, $to = null)
    {
        if (empty($to)) {
            $to = dt::today();
        }
        
        $amount = 0;
        $query = static::getQuery();
        
        $query->where("#paymentType = 'cash' OR (#paymentType IS NULL AND #autoPaymentType = 'cash')");
        $query->where("#state = 'active'");
        $query->between('date', $from, $to);
        
        while ($rec = $query->fetch()) {
            $total = $rec->vatAmount;
            $amount += $total;
        }
        
        return round($amount, 2);
    }
    
    
    /**
     * Може ли ф-та да бъде контирана/възстановена
     *
     * @param stdClass    $rec
     * @param string|NULL $msg
     * @param bool        $restore
     *
     * @return bool
     */
    public function isAllowedToBePosted($rec, &$msg, $restore = false)
    {
        $query = $this->getQuery();
        $query->where("#state = 'active' AND #numlimit = {$rec->numlimit}");
        $query->limit(1);
        
        $rangeName = cond_Ranges::displayRange($rec->numlimit);
        
        if ($restore === false) {
            $query->orderBy('date', 'DESC');
            $newDate = $query->fetch()->date;

            if ($newDate > $rec->date) {
                $newDate = dt::mysql2verbal($newDate, 'd.m.y');
                $msg = "Не може да се запише фактура с дата по-малка от последната активна фактура в диапазона|* [<b>{$rangeName}</b>] ({$newDate})";
                
                return false;
            }
            
            return true;
        }
        
        try{
            $number = (isset($rec->number)) ? $rec->number : cond_Ranges::getNextNumber($rec->numlimit, $this, 'number');
        } catch(core_exception_Expect $e){
            $msg = $e->getMessage();
            
            return false;
        }
        
        $queryBefore = clone $query;
        $query->orderBy('number', 'DESC');
        $queryBefore->where("#date < '{$rec->date}' AND #state = 'active' AND #number > {$number} AND #id != '{$rec->id}'");
        if ($iBefore = $queryBefore->fetch()) {
            $numberB = $this->recToVerbal($iBefore, 'number')->number;
            $msg = "Фактурата не може да се възстанови|* - |фактура|* №{$numberB} |е с по-голям номер и по-малка дата в диапазона|* [<b>{$rangeName}</b>]";
            
            return false;
        }
        
        $queryAfter = clone $query;
        $query->orderBy('number', 'ASC');
        $queryAfter->where("#date > '{$rec->date}' AND #state = 'active' AND #number <= {$number} AND #id != '{$rec->id}'");
        if ($iAfter = $queryAfter->fetch()) {
            $numberA = $this->recToVerbal($iAfter, 'number')->number;
            $msg = "Фактурата не може да се възстанови|* - |фактура|* №{$numberA} |е с по-малък номер и по-голяма дата в диапазона|* [<b>{$rangeName}</b>]";
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Взимане на грешка в бутона за възстановяване
     */
    public static function on_AfterGetRestoreBtnErrStr($mvc, &$res, $rec)
    {
        $error = null;
        if (!$mvc->isAllowedToBePosted($rec, $error, true)) {
            $res = $error;
        }
    }
    
    
    /**
     * Текст за грешка при бутон за контиране
     */
    public static function on_AfterGetContoBtnErrStr($mvc, &$res, $rec)
    {
        $error = null;
        if ($rec->date > dt::today()) {
            $res = 'Фактурата е с бъдещата дата и не може да бъде контирана';
        } elseif (!$mvc->isAllowedToBePosted($rec, $error)) {
            $res = $error;
        }
    }
    
    
    /**
     * Метод по подразбиране за намиране на дефолт шаблона
     */
    public function getDefaultTemplate_($rec)
    {
        if ($rec->folderId) {
            $cData = doc_Folders::getContragentData($rec->folderId);
        }
        
        $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
        $conf = core_Packs::getConfig('sales');
        $def = (empty($cData->countryId) || $bgId === $cData->countryId) ? $conf->SALE_INVOICE_DEF_TPL_BG : $conf->SALE_INVOICE_DEF_TPL_EN;
        
        return $def;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public static function getHandle($id)
    {
        $self = cls::get(get_called_class());
        $rec = $self->fetch($id);
        
        if (!$rec->number) {
            $hnd = $self->abbr . $rec->id . doc_RichTextPlg::$identEnd;
        } else {
            $number = $self->getVerbal($rec, 'number');
            $hnd = $self->abbr . $number;
        }
        
        return $hnd;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public static function fetchByHandle($parsedHandle)
    {
        $pId = $parsedHandle['id'];
        $pLen = strlen($pId);

        $rec = null;

        if ($pLen != 10) {
            if (!$parsedHandle['endDs']) {

                return null;
            }
        }

        if ($parsedHandle['endDs'] && ($pLen != 10)) {
            $rec = static::fetch($pId);
        } else {
            $number = ltrim($pId, '0');
            if ($number) {
                $rec = static::fetch("#number = '{$number}'");
            }
        }

        return $rec;
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        $rec = $mvc->fetchRec($rec);

        if (!empty($rec->sourceContainerId)) {
            $Source = doc_Containers::getDocument($rec->sourceContainerId);
            if ($Source->isInstanceOf('store_ShipmentOrders')) {
                
                // Ако източника на ф-та е ЕН, по което няма разпределени фактури значи е то
                $sRec = $Source->fetch();
                $invArr = deals_InvoicesToDocuments::getInvoiceArr($sRec->containerId);
                if (empty($sRec->fromContainerId) && !countR($invArr)) {
                    $sRec->fromContainerId = $rec->containerId;
                    $Source->getInstance()->save_($sRec, 'fromContainerId');

                    // След създаване синхронизиране на модела
                    $amount = $Source->getPaymentData($sRec)->amount;
                    $dRec = (object)array('documentContainerId' => $sRec->containerId, 'containerId' => $sRec->fromContainerId, 'amount' => $amount);
                    deals_InvoicesToDocuments::save($dRec);
                    doc_DocumentCache::cacheInvalidation($sRec->containerId);
                }
            }
        }

        // Ако има задължителен експортен параметър - кешира се към момента на активиране
        if($exportParamId = acc_Setup::get('INVOICE_MANDATORY_EXPORT_PARAM')){
            $Details = cls::get('sales_InvoiceDetails');
            $saveDetails = array();
            $dQuery = $Details->getQuery();
            $dQuery->where("#invoiceId = {$rec->id} AND (#exportParamValue IS NULL OR #exportParamValue = '')");
            $dQuery->show('productId,exportParamValue');
            while($dRec = $dQuery->fetch()){
                $dRec->exportParamValue = cat_Products::getParams($dRec->productId, $exportParamId);
                $saveDetails[] = $dRec;
            }

            if(countR($saveDetails)){
                $Details->saveArray($saveDetails, 'id,exportParamValue');
            }
        }
    }
}
