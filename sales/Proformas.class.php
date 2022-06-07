<?php


/**
 * Документ "Проформа фактура"
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
class sales_Proformas extends deals_InvoiceMaster
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf,deals_InvoiceSourceIntf';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
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
     * При създаване на имейл, дали да се използва първият имейл от списъка
     */
    public $forceFirstEmail = true;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, sales_Wrapper, cond_plg_DefaultValues, plg_Sorting, doc_DocumentPlg, acc_plg_DocumentSummary,
					doc_EmailCreatePlg, plg_Printing,
                    doc_plg_HidePrices, doc_plg_TplManager, bgerp_plg_Blank, deals_plg_DpInvoice, doc_ActivatePlg, plg_Clone,cat_plg_AddSearchKeywords, plg_Search';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'sales_ProformaDetails' ;
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'sales_ProformaDetails';
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,salesMaster,manager';
    
    
    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'sales_ProformaDetails';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,sales,acc';
    
    
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
    public $searchFields = 'number, folderId, contragentName';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutProforma.shtml';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/proforma.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.8|Търговия';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number, date, place, folderId, dealValue, vatAmount';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'place' => 'defMethod',
        'responsible' => 'lastDocUser|lastDoc',
        'contragentCountryId' => 'clientData|lastDocUser|lastDoc',
        'contragentVatNo' => 'clientData|lastDocUser|lastDoc',
        'uicNo' => 'clientData|lastDocUser|lastDoc',
        'contragentPCode' => 'clientData|lastDocUser|lastDoc',
        'contragentPlace' => 'clientData|lastDocUser|lastDoc',
        'contragentAddress' => 'clientData|lastDocUser|lastDoc',
        'template' => 'lastDocUser|lastDoc|defMethod',
    );


    /**
     * Стратегии за добавяне на артикули след създаване от източника
     */
    protected $autoAddProductStrategies = array('onlyFromDeal' => "Всички артикули от договора", 'onlyShipped' => 'Експедираните артикули по договора', 'none' => 'Без');


    /**
     * Кои полета ако не са попълнени във визитката на контрагента да се попълнят след запис
     */
    public static $updateContragentdataField = array('vatId' => 'contragentVatNo',
        'uicId' => 'uicNo',
        'egn' => 'uicNo',
    );
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, date,dueDate,vatDate,modifiedOn';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        parent::setInvoiceFields($this);
        
        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'caption=Продажба,input=none');
        $this->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=title, allowEmpty)', 'caption=Плащане->Банкова с-ка');
        $this->FLD('state', 'enum(draft=Чернова, active=Активиран, rejected=Оттеглен)', 'caption=Статус, input=none');
        $this->FLD('number', 'int', 'caption=Номер, export=Csv,after=reff');
        $this->FLD('reff', 'varchar(255,nullIfEmpty)', 'caption=Ваш реф.,class=contactData,after=place');
        
        $this->setDbUnique('number');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
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
        $form = &$data->form;
        parent::prepareInvoiceForm($mvc, $data);
        if(empty($rec->id)){
            $form->setDefault('importProducts', 'onlyFromDeal');
        }

        $form->setField('paymentType', 'input=none');
        foreach (array('deliveryPlaceId', 'vatDate') as $fld) {
            $form->setField($fld, 'input=hidden');
        }
        
        if (!haveRole('ceo,acc')) {
            $form->setField('number', 'input=none');
        }
        
        if ($data->aggregateInfo) {
            $form->setDefault('reff', $data->aggregateInfo->get('reff'));
            if ($accId = $data->aggregateInfo->get('bankAccountId')) {
                $form->setDefault('accountId', bank_OwnAccounts::fetchField("#bankAccountId = {$accId}", 'id'));
            }
        }
        
        if (empty($data->flag)) {
            if ($ownAcc = bank_OwnAccounts::getCurrent('id', false)) {
                $form->setDefault('accountId', $ownAcc);
            }
        }
        
        if ($form->rec->vatRate != 'yes' && $form->rec->vatRate != 'separate') {
            if ($form->rec->contragentCountryId == drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id')) {
                $form->setField('vatReason', 'input,mandatory');
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
        if (isset($rec->id)) {
            if (empty($rec->folderId)) {
                $rec->folderId = $mvc->fetchField($rec->id, 'folderId');
            }
            
            if (empty($rec->dueDate) && $rec->state == 'active') {
                $rec->dueDate = $mvc->fetchField($rec->id, 'dueDate');
            }
        }
        
        parent::beforeInvoiceSave($rec);
        
        // Кой е следващия най-голям номер
        $number = (isset($rec->number)) ? $rec->number : ((isset($rec->id) ? $mvc->fetchField($rec->id, 'number') : 0));
        if (empty($number)) {
            $query = $mvc->getQuery();
            $query->XPR('maxNumber', 'int', 'MAX(#number)');
            $number = $query->fetch()->maxNumber;
            $number += 1;
            
            while(self::fetchField("#number = '{$number}'")){
                $number += 1;
            }
            
            $rec->number = $number;
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        // Добавяне на кода към ключовите думи
        $number = !empty($rec->number) ? $rec->number : (isset($rec->id) ? $mvc->fetchField($rec->id, 'number') : null);
        
        if(!empty($number)){
            $res .= ' ' . plg_Search::normalizeText($number);
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
        if ($fields['-single']) {
            if(isset($rec->paymentMethodId)){
                $rec->paymentType = cond_PaymentMethods::fetchField($rec->paymentMethodId, 'type');
            }
        }

        parent::getVerbalInvoice($mvc, $rec, $row, $fields);
        
        if ($fields['-single']) {

            if (isset($rec->accountId)) {
                $Varchar = cls::get('type_Varchar');
                $ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->accountId);
                $row->accountId = cls::get('iban_Type')->toVerbal($ownAcc->iban);
                
                core_Lg::push($rec->tplLang);
                $row->bank = transliterate(tr($Varchar->toVerbal($ownAcc->bank)));
                core_Lg::pop();
                $row->bic = $Varchar->toVerbal($ownAcc->bic);
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
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
        $firstDocument = doc_Threads::getFirstDocument($threadId);
        if (!$firstDocument) {
            
            return false;
        }
        
        // Може да се добавя само към активна продажба
        if ($firstDocument->isInstanceOf('sales_Sales') && $firstDocument->fetchField('state') == 'active') {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if (deals_Helper::showInvoiceBtn($rec->threadId) && sales_Invoices::haveRightFor('add', (object) array('originId' => $rec->originId, 'sourceContainerId' => $rec->containerId))) {
            $data->toolbar->addBtn('Фактура', array('sales_Invoices', 'add', 'originId' => $rec->originId, 'sourceContainerId' => $rec->containerId, 'threadId' => $rec->threadId, 'ret_url' => true), 'title=Създаване на фактура от проформа фактура,ef_icon=img/16/invoice.png,row=2');
        }
        
        if ($rec->state == 'active') {
            $amount = ($rec->dealValue - $rec->discountAmount) + $rec->vatAmount;
            $amount /= $rec->rate;
            $amount = round($amount, 2);
            $originId = isset($rec->originId) ? $rec->originId : doc_Threads::getFirstContainerId($rec->threadId);
            
            if (cash_Pko::haveRightFor('add', (object) array('threadId' => $rec->threadId, 'fromContainerId' => $rec->containerId))) {
                $data->toolbar->addBtn('ПКО', array('cash_Pko', 'add', 'originId' => $originId, 'fromContainerId' => $rec->containerId, 'termDate' => $rec->dueDate, 'ret_url' => true), 'ef_icon=img/16/money_add.png,title=Създаване на нов приходен касов ордер към проформата');
            }
            
            if (bank_IncomeDocuments::haveRightFor('add', (object) array('threadId' => $rec->threadId, 'fromContainerId' => $rec->containerId))) {
                $data->toolbar->addBtn('ПБД', array('bank_IncomeDocuments', 'add', 'originId' => $originId, 'amountDeal' => $amount, 'fromContainerId' => $rec->containerId, 'termDate' => $rec->dueDate, 'ret_url' => true), 'ef_icon=img/16/bank_add.png,title=Създаване на нов приходен банков документ към проформата');
            }
        }
    }
    
    
    /**
     * Намира очаквания аванс по проформа, ако има
     * Връща начисления аванс от последната проформа за начисляване на аванс,
     * ако има платежни документи след нея не връщаме сумата (не очакваме аванс)
     *
     * @param mixed $saleId - ид или запис на продажба
     *
     * @return NULL|float - очаквано авансово плащане
     */
    public static function getExpectedDownpayment($saleId)
    {
        $saleRec = sales_Sales::fetchRec($saleId);
        
        $expectedDownpayment = null;
        
        // Намираме последната проформа към продажбата (ако има)
        $pQuery = self::getQuery();
        $pQuery->where("#originId = {$saleRec->containerId}");
        $pQuery->where("#state = 'active'");
        $pQuery->where("#dpAmount IS NOT NULL AND #dpOperation = 'accrued'");
        $pQuery->orderBy('id', 'DESC');
        
        // Ако има намерена проформа
        if ($profRec = $pQuery->fetch()) {
            
            // Ако има приходен касов ордер с вальор по-голям не намираме очакван аванс
            if (cash_Pko::fetchField("#threadId = {$saleRec->threadId} AND #state = 'active' AND #valior > '{$profRec->date}'")) {
                
                return $expectedDownpayment;
            }
            
            // Ако има приходен банков ордер с вальор по-голям не намираме очакван аванс
            if (bank_IncomeDocuments::fetchField("#threadId = {$saleRec->threadId} AND #state = 'active' AND #valior > '{$profRec->date}'")) {
                
                return $expectedDownpayment;
            }
            
            // Ако няма платежен документ след проформата намираме очаквания и аванс
            $expectedDownpayment += $profRec->dealValue + $profRec->vatAmount;
            $expectedDownpayment = round($expectedDownpayment, 2);
        }
        
        // Връщаме очаквания аванс
        return $expectedDownpayment;
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

        if (trim($parsedHandle['endDs']) && ($pLen != 10)) {
            $rec = static::fetch($pId);
        } else {
            $number = ltrim($pId, '0');
            if ($number) {
                $rec = static::fetch("#number = '{$number}'");
            }
        }

        return $rec;
    }
}
