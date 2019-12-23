<?php


/**
 * Входящи фактури към покупки
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class purchase_Invoices extends deals_InvoiceMaster
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, acc_TransactionSourceIntf=purchase_transaction_Invoice, bgerp_DealIntf, deals_InvoiceSourceIntf, fileman_FileActionsIntf';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ini';
    
    
    /**
     * Заглавие
     */
    public $title = 'Входящи фактури';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Входяща фактура';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, purchase_Wrapper, doc_plg_TplManager, plg_Sorting, acc_plg_Contable,plg_Clone, doc_DocumentPlg,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, cond_plg_DefaultValues,deals_plg_DpInvoice,
                    doc_plg_HidePrices, acc_plg_DocumentSummary,cat_plg_AddSearchKeywords, plg_Search,change_Plugin';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number, date, dueDate=Срок, place, folderId, currencyId=Валута, dealValue=Стойност, valueNoVat=Без ДДС, vatAmount, type';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'purchase_InvoiceDetails';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,invoicer';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,purchase,acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,purchase,acc';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,invoicer';
    
    
    /**
     * Кой има право да създава от файл?
     */
    public $canCreatefromfile = 'ceo,invoicer';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo,invoicer';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number, folderId, contragentName';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutInvoice.shtml';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/invoice.png';
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,purchaseMaster,manager';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.3|Търговия';
    
    
    /**
     * Кой може да променя активирани записи
     *
     * @see change_Plugin
     */
    public $canChangerec = 'acc, ceo';
    
    
    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'purchase_InvoiceDetails';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'purchase_InvoiceDetails';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn,date,dueDate,journalDate';
    
    
    /**
     * Кой има право да експортва?
     */
    public $canExport = 'ceo,invoicer';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'place' => 'lastDocUser|lastDoc',
        'responsible' => 'lastDocUser|lastDoc',
        'contragentCountryId' => 'clientData|lastDocUser|lastDoc',
        'contragentVatNo' => 'clientData|lastDocUser|lastDoc',
        'uicNo' => 'clientData|lastDocUser|lastDoc',
        'contragentPCode' => 'clientData|lastDocUser|lastDoc',
        'contragentPlace' => 'clientData|lastDocUser|lastDoc',
        'contragentAddress' => 'clientData|lastDocUser|lastDoc',
        'accountId' => 'lastDocUser|lastDoc',
        'template' => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * Кои полета да могат да се променят след активация
     */
    public $changableFields = 'journalDate,number,fileHnd,responsible,contragentCountryId, contragentPCode, contragentPlace, contragentAddress, dueTime, dueDate, additionalInfo,accountId,paymentType,template';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        parent::setInvoiceFields($this);
        $this->FLD('journalDate', 'date', 'caption=Данъчни параметри->Сч. дата,after=vatReason');
        $this->FLD('number', 'varchar', 'caption=Номер, export=Csv,hint=Номера с който идва фактурата,after=place');
        $this->FLD('fileHnd', 'fileman_FileType(bucket=Documents)', 'caption=Документ,after=number');
        
        $this->FLD('accountId', 'key(mvc=bank_Accounts,select=iban, allowEmpty)', 'caption=Плащане->Банкова с-ка, export=Csv');
        $this->FLD('state', 'enum(draft=Чернова, active=Контирана, rejected=Оттеглен,stopped=Спряно)', 'caption=Статус, input=none,export=Csv');
        $this->FLD('type', 'enum(invoice=Входяща фактура, credit_note=Входящо кредитно известие, debit_note=Входящо дебитно известие, dc_note=Известие)', 'caption=Вид, input=hidden');
    }
    
    
    /**
     * Връща асоциираната форма към MVC-обекта
     */
    public static function on_AfterGetForm($mvc, &$form, $params = array())
    {
        $form->FLD('contragentSource', 'enum(company=Фирми,newContragent=Нов доставчик)', 'input,silent,removeAndRefreshForm=selectedContragentId,caption=Контрагент->Източник,before=contragentName');
        $form->setDefault('contragentSource', 'company');
        $form->FLD('selectedContragentId', 'int', 'input=none,silent,removeAndRefreshForm,caption=Контрагент->Избор,after=contragentSource');
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        $origin = $mvc->getOrigin($form->rec);
        
        if ($origin->isInstanceOf('findeals_AdvanceReports')) {
            $form->setDefault('vatRate', $origin->fetchField('chargeVat'));
            $form->setDefault('currencyId', $origin->fetchField('currencyId'));
            $form->setDefault('rate', $origin->fetchField('currencyRate'));
            
            $additionalInfo = tr('|Към авансов отчет|*: #') . $origin->getHandle() . PHP_EOL;
            $form->setDefault('additionalInfo', $additionalInfo);
        }
        
        // Ако ф-та не е към служебен аванс не искаме да се сменя контрагента
        $firstDocument = doc_Threads::getFirstDocument($form->rec->threadId);
        if (!$firstDocument->isInstanceOf('findeals_AdvanceDeals')) {
            $form->setField('contragentSource', 'input=none');
            unset($form->rec->contragentSource);
        }
        
        // Ако има избрано поле за източник на контрагента
        if (isset($rec->contragentSource)) {
            if ($rec->contragentSource == 'company') {
                $form->setField('selectedContragentId', 'input');
                $form->setFieldType('selectedContragentId', core_Type::getByName('key(mvc=crm_Companies,select=name,allowEmpty)'));
            }
        }
        
        parent::prepareInvoiceForm($mvc, $data);
      
        if ($data->aggregateInfo) {
            if ($data->aggregateInfo->get('bankAccountId')) {
                $form->rec->accountId = $data->aggregateInfo->get('bankAccountId');
            }
        }
        
        $coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
        $coverId = doc_Folders::fetchCoverId($form->rec->folderId);
        $form->setOptions('accountId', bank_Accounts::getContragentIbans($coverId, $coverClass, true));
        
        if ($form->rec->vatRate != 'yes' && $form->rec->vatRate != 'separate') {
            $form->setField('vatReason', 'mandatory');
        }
        
        $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
        if ($rec->contragentCountryId == $bgId) {
            $form->setFieldType('number', core_Type::getByName('bigint(size=10)'));
        }
        
        $clonedFh = $form->rec->fileHnd;
        
        if (!$clonedFh) {
            $clonedFh = Mode::get('invOriginFh');
        }
        
        if ($clonedFh) {
            $form->setDefault('fileHnd', $clonedFh);
            
            $fRec = fileman::fetchByFh($clonedFh);
            doc_DocumentPlg::showOriginalFile($fRec, $form);
        }
        
    }
    
    
    /**
     *
     *
     * @param purchase_Invoices $mvc
     * @param stdClass          $data
     */
    public function on_BeforePrepareEditForm($mvc, &$data)
    {
        $oId = Request::get('originId');
        if ($oId) {
            $origin = doc_Containers::getDocument($oId);
            
            if ($origin->isInstanceOf('purchase_Purchases')) {
                $oRec = $origin->fetch();
                $clonedFromId = $mvc->getClonedFromId($oRec);
                
                if ($clonedFromId) {
                    $clonedFh = Mode::get('clonedPurFh|' . $clonedFromId);
                    Mode::set('invOriginFh', $clonedFh);
                    
                    // Да не се рендира оригиналния документ
                    Mode::set('stopRenderOrigin', true);
                }
            }
        }
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        
        $unsetFields = false;
        
        // Махане на дефолтните данни при нужда
        if ((empty($rec->id) && $form->cmd != 'save' && isset($rec->contragentSource) && $rec->contragentSource != 'newContragent' && empty($rec->selectedContragentId))) {
            $unsetFields = true;
        }
        
        if ($form->cmd == 'refresh') {
            if ($rec->contragentSource == 'newContragent') {
                $unsetFields = true;
            }
            
            $arr = array();
            
            // Ако е избран контрагент замества ме му данните
            if (isset($rec->selectedContragentId)) {
                if ($rec->contragentSource == 'company') {
                    $cData = crm_Companies::getContragentData($rec->selectedContragentId);
                    foreach (array('contragentName' => 'company', 'contragentCountryId' => 'countryId', 'contragentVatNo' => 'vatNo', 'uicNo' => 'uicId', 'contragentPCode' => 'pCode', 'contragentPlace' => 'place', 'contragentAddress' => 'address') as $k => $v) {
                        $arr[$k] = $cData->{$v};
                    }
                    $arr['contragentClassId'] = crm_Companies::getClassId();
                    $arr['contragentId'] = $rec->selectedContragentId;
                } else {
                    $arr['contragentClassId'] = null;
                    $arr['contragentId'] = null;
                }
                
                if (count($arr)) {
                    foreach (array('contragentName', 'contragentClassId', 'contragentId', 'contragentCountryId', 'contragentVatNo', 'uicNo', 'contragentPCode', 'contragentPlace', 'contragentAddress')  as $fld) {
                        $form->rec->{$fld} = $arr[$fld];
                    }
                }
            }
        }
        
        // Ако е указано да махнем записаните данни, правим го
        if ($unsetFields === true) {
            foreach (array('contragentName', 'contragentClassId', 'contragentId', 'contragentCountryId', 'contragentVatNo', 'uicNo', 'contragentPCode', 'contragentPlace', 'contragentAddress')  as $fld) {
                unset($rec->{$fld});
            }
            $rec->contragentCountryId = crm_Companies::fetchOurCompany()->country;
        }
        
        if ($rec->type != 'dc_note') {
            // Ако източника е фирма и не е избрана фирма, забраняваме определени полета
            if ($rec->contragentSource == 'company' && empty($rec->selectedContragentId)) {
                foreach (array('contragentName', 'contragentCountryId', 'contragentVatNo', 'uicNo', 'contragentPCode', 'contragentPlace', 'contragentAddress')  as $fld) {
                    $form->setReadOnly($fld);
                }
            }
        }
        
        parent::inputInvoiceForm($mvc, $form);
        
        if ($form->isSubmitted()) {
            
            // Ако има въведена сч. дата тя се проверява
            if (isset($rec->journalDate) && core_Request::get('Act') == 'changefields') {
                $periodState = acc_Periods::fetchByDate($rec->journalDate)->state;
                if ($periodState == 'closed' || $periodState == 'draft' || is_null($periodState)) {
                    $form->setError('journalDate', 'Сч. дата е в затворен, бъдещ или несъществуващ период');
                }
            }
            
            if ($rec->contragentSource == 'newContragent') {
                $cRec = self::getContragentRec($rec);
                
                // Проверяваме да няма дублиране на записи
                $resStr = crm_Companies::getSimilarWarningStr($cRec);
                if ($resStr) {
                    $form->setWarning('contragentName,contragentCountryId,contragentVatNo,uicNo,contragentPCode,contragentPlace,contragentAddress', $resStr);
                }
            }
            
            if (empty($rec->number)) {
                $rec->number = null;
            }
            
            $foundInvoiceId = null;
            $checkRec = clone $rec;
            if($form->_cloneForm === true){
                unset($checkRec->id);
            }
            
            if (!$mvc->isNumberFree($checkRec, $foundInvoiceId)) {
                $foundInvoiceId = purchase_Invoices::getLink($foundInvoiceId, 0);
                $form->setError("{$fld},number", "Има вече входяща фактура с този номер, за този контрагент|*: <b>{$foundInvoiceId}</b>");
            }
        }
    }
    
    
    /**
     * Връща запис с данните на контрагента
     *
     * @param stdClass $rec
     *
     * @return stdClass $cRec
     */
    private static function getContragentRec($rec)
    {
        $cRec = (object) array('name' => $rec->contragentName, 'country' => $rec->contragentCountryId, 'vatId' => $rec->contragentVatNo, 'uicId' => $rec->uicNo, 'pCode' => $rec->contragentPCode, 'place' => $rec->contragentPlace, 'address' => $rec->contragentAddress);
        
        return $cRec;
    }
    
    
    /**
     * Преди възстановяване, ако има затворени пера в транзакцията, не може да се възстановява
     */
    protected static function on_BeforeRestore($mvc, &$res, $id)
    {
        // Ако има фактура с този номер, не възстановяваме
        if (!$mvc->isNumberFree($id)) {
            core_Statuses::newStatus('Има вече входяща фактура с този номер, за този контрагент', 'error');
            
            return false;
        }
    }
    
    
    /**
     * Проверява дали номера е свободен
     *
     * @param stdClass $rec
     * @param string|null $foundInvoiceId
     *
     * @return bool
     */
    private function isNumberFree($rec, &$foundInvoiceId = null)
    {
        $rec = $this->fetchRec($rec);
        
        if (empty($rec->number)) {
            
            return true;
        }
        
        // Проверяваме дали за този контрагент има друга фактура със същия номер, която не е оттеглена
        foreach (array('contragentVatNo', 'uicNo') as $fld) {
            if (!empty($rec->{$fld})) {
                if ($invRec = $this->fetchField("#{$fld}='{$rec->{$fld}}' AND #number='{$rec->number}' AND #id != '{$rec->id}' AND #state != 'rejected'")) {
                    $foundInvoiceId = $invRec;
                    
                    return false;
                }
            }
        }
        
        return true;
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
        parent::beforeInvoiceSave($rec);
        
        // Форсиране на нова фирма, ако е указано
        if ($rec->state == 'draft') {
            if ($rec->contragentSource == 'newContragent') {
                $cRec = self::getContragentRec($rec);
                $rec->contragentId = crm_Companies::save($cRec);
                $rec->contragentClassId = crm_Companies::getClassId();
                core_Statuses::newStatus("Добавена е нова фирма|* '{$rec->contragentName}'");
            }
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        $tpl->push('purchase/tpl/invoiceStyles.css', 'CSS');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        parent::getVerbalInvoice($mvc, $rec, $row, $fields);
        
        if (isset($fields['-single'])) {
            if (!empty($rec->accountId)) {
                $Varchar = cls::get('type_Varchar');
                $ownAcc = bank_Accounts::fetch($rec->accountId);
                $row->bank = $Varchar->toVerbal($ownAcc->bank);
                $row->bic = $Varchar->toVerbal($ownAcc->bic);
            }
            
            if (isset($rec->journalDate) && $rec->journalDate != $rec->date) {
                $msg = 'Датата на счетоводната операция е|*: ' . $mvc->getFieldType('date')->toVerbal($rec->journalDate);
                $row->date = ht::createHint($row->date, $msg);
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
            
            if (!(($firstDoc->isInstanceOf('purchase_Purchases') || $firstDoc->isInstanceOf('findeals_AdvanceDeals')) && $docState == 'active')) {
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Входяща фактура нормален изглед', 'content' => 'purchase/tpl/InvoiceHeaderNormal.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/InvoiceNarrow.shtml');
        $tplArr[] = array('name' => 'Входяща фактура изглед за писмо', 'content' => 'purchase/tpl/InvoiceHeaderLetter.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/InvoiceNarrow.shtml');
        
        $res = '';
        $res .= doc_TplManager::addOnce($this, $tplArr);
        
        return $res;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if ($rec->state == 'active') {
            $amount = ($rec->dealValue - $rec->discountAmount) + $rec->vatAmount - 0.005;
            $amount /= ($rec->displayRate) ? $rec->displayRate : $rec->rate;
            $amount = round($amount, 2);
            
            if ($amount < 0) {
                if (cash_Pko::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                    $data->toolbar->addBtn('ПКО', array('cash_Pko', 'add', 'originId' => $rec->containerId, 'fromContainerId' => $rec->containerId, 'termDate' => $rec->dueDate, 'ret_url' => true), 'ef_icon=img/16/money_delete.png,title=Създаване на нов приходен касов ордер към документа');
                }
                
                if (bank_IncomeDocuments::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                    $data->toolbar->addBtn('ПБД', array('bank_IncomeDocuments', 'add', 'originId' => $rec->containerId, 'amountDeal' => abs($amount), 'fromContainerId' => $rec->containerId, 'termDate' => $rec->dueDate, 'ret_url' => true), 'ef_icon=img/16/bank_rem.png,title=Създаване на нов приходен банков документ');
                }
            } else {
                if (cash_Rko::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                    $data->toolbar->addBtn('РКО', array('cash_Rko', 'add', 'originId' => $rec->containerId, 'fromContainerId' => $rec->containerId, 'termDate' => $rec->dueDate, 'ret_url' => true), 'ef_icon=img/16/money_delete.png,title=Създаване на нов разходен касов ордер към документа');
                }
                
                if (bank_SpendingDocuments::haveRightFor('add', (object) array('threadId' => $rec->threadId))) {
                    $data->toolbar->addBtn('РБД', array('bank_SpendingDocuments', 'add', 'originId' => $rec->containerId, 'amountDeal' => $amount, 'fromContainerId' => $rec->containerId, 'termDate' => $rec->dueDate, 'ret_url' => true), 'ef_icon=img/16/bank_rem.png,title=Създаване на нов разходен банков документ');
                }
            }
            
            if (purchase_Vops::haveRightFor('add', (object) array('invoiceId' => $rec->id))) {
                $rowNumber = (drdata_Countries::isEu($rec->contragentCountryId)) ? 1 : 2;
                $data->toolbar->addBtn('ВОП', array('purchase_Vops', 'add', 'invoiceId' => $rec->id), "ef_icon=img/16/page_2.png,title=Създаване на нов протокол за вътреобщностно придобиване, row={$rowNumber}");
            }
            
            if ($vopId = purchase_Vops::fetchField("#invoiceId = {$rec->id}")) {
                if (purchase_Vops::haveRightFor('print', $vopId)) {
                    $data->toolbar->addBtn('ВОП', array('purchase_Vops', 'print', $vopId, 'Printing' => 'yes'), 'ef_icon=img/16/print_go.png,title=Разпечатване на нов протокол за вътреобщностно придобиване,target=_blank');
                }
            }
        }
    }
    
    
    /**
     * Интерфейсен метод на fileman_FileActionsIntf
     *
     * Връща масив с действия, които могат да се извършат с дадения файл
     *
     * @param stdClass $fRec - Обект са данни от модела
     *
     * @return array $arr - Масив с данните
     *               $arr['url'] - array URL на действието
     *               $arr['title'] - Заглавието на бутона
     *               $arr['icon'] - Иконата
     */
    public static function getActionsForFile($fRec)
    {
        if (self::haveRightFor('createfromfile') && self::canKeepDoc($fRec->name, $fRec->fileLen)) {
            
            // Създаваме масива за съзване на визитка
            $arr = array();
            
            $me = cls::get(get_called_class());
            
            $arr['incomingInv']['url'] = array($me, 'createFromFile', 'fh' => $fRec->fileHnd, 'ret_url' => true);
            $arr['incomingInv']['title'] = 'Входяща фактура';
            $arr['incomingInv']['icon'] = $me->getIcon();
            
            if (doc_Files::getCidWithFile($fRec->dataId, purchase_Invoices::getClassId(), 1, 100, false)) {
                $arr['incomingInv']['btnParams'] = 'warning=Има създадена фактура от файла';
            }
        }
        
        return $arr;
    }
    
    
    /**
     * Преценява дали файла с посоченото име и дължина може да съдържа документ
     *
     * @param string $fileName
     * @param int    $fileLen
     *
     * @return bool
     */
    public static function canKeepDoc($fileName, $fileLen)
    {
        // От кои документи и над какъв размер може да се създават документ
        static $typeToLen = array();
        if (empty($typeToLen)) {
            $typeToLen = arr::make('pdf=10,doc=10,docx=10,odt=10,xls=10,zip=10,rar=10,txt=1,rtf=2,tiff=20,tff=20,jpg=20,jpeg=20,png=20,bmp=50,csv=1', true);
        }
        
        $ext = fileman_Files::getExt($fileName);
        
        if (($minLen = $typeToLen[$ext]) && ($minLen <= $fileLen)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Екшън за създаване на входяща фактура (и услуга и/или стока) от файл
     */
    public function act_Createfromfile()
    {
        $this->requireRightFor('createfromfile');
        
        $fileHnd = Request::get('fh');
        
        expect($fileHnd);
        
        expect($fRec = fileman::fetchByFh($fileHnd));
        
        expect($this->canKeepDoc($fRec->name, $fRec->fileLen));
        
        $form = cls::get('core_Form');
        
        if (Mode::is('screenMode', 'wide')) {
            $form->class .= ' floatedElement ';
        }
        
        $showClosedLimit = 3;
        $maxLimitForShow = 300;
        
        $bestPosArr = doc_Files::getBestContainer($fileHnd, 'crm_ContragentAccRegIntf');
        
        $form->FNC('folderId', 'key2(mvc=doc_Folders,select=title,allowEmpty,coverInterface=crm_ContragentAccRegIntf)', 'caption=Контрагент, input, removeAndRefreshForm=acceptance|purId');
        $form->FNC('purId', 'key(mvc=purchase_Purchases,allowEmpty)', 'caption=Покупка, input, removeAndRefreshForm=acceptance, mandatory');
        $form->FNC('invDate', 'date(format=d.m.Y)', 'caption=Фактура->Дата,  notNull, mandatory, input');
        $form->FNC('invNum', 'varchar', 'caption=Фактура->Номер, input, class=w50');
        $form->FNC('acceptance', 'set(store=Стоки, service=Услуги)', 'caption=Приемане, input');
        
        $form->setDefault('invDate', dt::today());
        
        $form->input('folderId, purId');
        
        if ($form->cmd != 'refresh') {
            if ($bestPosArr['folderId']) {
                $form->setDefault('folderId', $bestPosArr['folderId']);
            }
        }
        
        // Намираме всчики достъп покупки
        $purArr = array();
        $pQuery = purchase_Purchases::getQuery();
        
        doc_Threads::restrictAccess($pQuery);
        
        if ($form->rec->folderId) {
            $pQuery->where(array('#folderId = [#1#]', $form->rec->folderId));
        }
        
        $cPQuery = clone $pQuery;
        
        $pQuery->where("#state = 'active'");
        $pQuery->orWhere("#state = 'pending'");
        $pQuery->where("#makeInvoice != 'no'");
        $pQuery->XPR('amountToInvoice', 'double', '#amountDelivered - #amountInvoiced');
        $tolerance = acc_Setup::get('MONEY_TOLERANCE');
        $pQuery->where(array('#amountToInvoice NOT BETWEEN -[#1#] AND [#1#]', $tolerance));
        $pQuery->orWhere('#amountToInvoice IS NULL');
        
        $pQuery->limit($maxLimitForShow);
        $pQuery->orderBy('state', 'DESC');
        $pQuery->orderBy('valior', 'DESC');
        $pQuery->orderBy('activatedOn', 'DESC');
        
        $group = '';
        while ($pRec = $pQuery->fetch()) {
            if ($group != $pRec->state) {
                $group = $pRec->state;
                
                $verGroup = ($group == 'pending') ? 'Заявка' : 'Активни';
                $purArr[$pRec->state] = (object) array('title' => tr($verGroup), 'group' => true);
            }
            
            $purArr[$pRec->id] = purchase_Purchases::getTitleWithAmount($pRec->id);
        }
        
        // Вземаме последните 3 покукпи
        $cPQuery->where("#state = 'closed'");
        $cPQuery->limit($showClosedLimit);
        
        $cPQuery->orderBy('closedOn', 'DESC');
        $cPQuery->orderBy('valior', 'DESC');
        
        $group = false;
        while ($pRec = $cPQuery->fetch()) {
            if (!$group) {
                $group = true;
                
                $purArr['closed'] = (object) array('title' => tr('Затворени'), 'group' => true);
            }
            $purArr[$pRec->id] = purchase_Purchases::getTitleWithAmount($pRec->id);
        }
        
        if (empty($purArr)) {
            $purArr[''] = '';
        }
        
        $form->setOptions('purId', $purArr);
        
        // Улесняваме избора на потребителя, като избираме покупката или поне папката
        if ($form->cmd != 'refresh') {
            if ($bestPosArr['threadId']) {
                $fContainerId = doc_Threads::getFirstContainerId($bestPosArr['threadId']);
                
                $doc = doc_Containers::fetch($fContainerId);
                
                if (($doc->docClass == purchase_Purchases::getClassId()) && ($purArr[$doc->docId])) {
                    $form->setDefault('purId', $doc->docId);
                }
            }
        }
        
        // Ако има само една опция - тя да е избрана по подразбиране
        if ((count($purArr) == 1) && !isset($purArr[''])) {
            $form->setDefault('purId', key($purArr));
        }
        
        $pRec = false;
        
        if ($form->rec->purId) {
            $pRec = purchase_Purchases::fetch($form->rec->purId);
            
            if ($pRec->state != 'closed') {
                if (($pRec->chargeVat == 'exempt') || ($pRec->chargeVat == 'no')) {
                    $form->FNC('invVatReason', 'varchar(255)', 'caption=Данъчни параметри->Основание,recently,Основание за размера на ДДС, input, before=acceptance, mandatory');
                    
                    $noReason1 = acc_Setup::get('VAT_REASON_OUTSIDE_EU');
                    $noReason2 = acc_Setup::get('VAT_REASON_IN_EU');
                    $suggestions = array('' => '', $noReason1 => $noReason1, $noReason2 => $noReason2);
                    $form->setSuggestions('invVatReason', $suggestions);
                }
            }
            
            $aSet = array();
            
            $createdInvArr = doc_Files::getCidWithFile($fRec->dataId, purchase_Invoices::getClassId());
            
            if (!empty($createdInvArr)) {
                $wMsg = 'Вече има създадена фактура от файла|*';
                foreach ($createdInvArr as $cId) {
                    $doc = doc_Containers::getDocument($cId);
                    $wMsg .= '<br>' . $doc->getLinkToSingle();
                }
                $form->setWarning('fileHnd', $wMsg);
            }
            
            if ($pRec->threadId) {
                
                $canStore = false;
                
                $dQuery = purchase_PurchasesDetails::getQuery();
                $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
                $dQuery->where(array("#requestId = '[#1#]'", $pRec->id));
                $dQuery->where("#canStore = 'yes'");
                $dQuery->limit(1);
                $dQuery->show('id');
                if ($dQuery->fetch()) {
                    $canStore = true;
                }
                
                // Ако няма създадена складова разписка - да е избрано във формата
                $rClsId = store_Receipts::getClassId();
                $sClsId = purchase_Services::getClassId();
                if (!doc_Containers::fetch(array("#threadId = [#1#] AND #state != 'rejected' AND #docClass = '[#2#]'", $pRec->threadId, $rClsId))) {
                    
                    if ($canStore) {
                        $aSet['store'] = 'store';
                    }
                }
                
                // Ако няма създаден приемателен протокол - да е избрано във формата
                if (!$canStore && !doc_Containers::fetch(array("#threadId = [#1#] AND #state != 'rejected' AND #docClass = '[#2#]'", $pRec->threadId, $sClsId))) {
                    $aSet['service'] = 'service';
                }
            }
            
            $detFieldPref = '_pDet_';
            
            // Показваме полета и за попълване/промяна на детайлите от покупката
            $dQuery = purchase_PurchasesDetails::getQuery();
            $dQuery->where(array("#requestId = '[#1#]'", $pRec->id));
            while ($dRec = $dQuery->fetch()) {
                $productName = cat_Products::getTitleById($dRec->productId);
                
                $productName = str_replace('->', '-', $productName);
                
                $vat = cat_Products::getVat($dRec->productId, $pRec->valior);
                $price = deals_Helper::getDisplayPrice($dRec->price, $vat, $pRec->currencyRate, $pRec->chargeVat, 3);
                
                $unit = $price . ' ' . $pRec->currencyId;
                
                if ($dRec->discount) {
                    $discount = $dRec->discount * 100;
                    $unit .= ', ' . tr('ТО') . ': ' . $discount . '%';
                }
                
                $productName .= '->' . cat_UoM::getTitleById($dRec->packagingId);
                
                $fncName = $detFieldPref . $dRec->id;
                
                $form->FNC($fncName, 'varchar', array('caption' => '|*' . $productName, 'input', 'unit' => '|*' . $unit, 'class' => 'w50'));
                
                $form->setDefault($fncName, $dRec->quantity);
                
                if ($pRec->state == 'closed') {
                    $form->setReadonly($fncName);
                }
            }
            
            $pAct = type_Set::toArray($pRec->contoActions);
            
            // Ако няма да се клонира или не е бърза
            if ($pRec->state == 'closed') {
                $form->setField('acceptance', 'input=none');
                $form->setField('invNum', 'input=none');
                $form->setField('invDate', 'input=none');
            } elseif ($pAct['ship']) {
                $form->setField('acceptance', 'input=none');
            } else {
                $form->setDefault('acceptance', $aSet);
            }
        }
        
        // Вече инпутваме формата и създаваме необходимите документи
        $form->input();
        $createDocArr = array();
        if ($form->isSubmitted()) {
            
            // Ако ще се клонира покупката - пращаме директно към съответната форма
            if ($pRec->state == 'closed') {
                Mode::setPermanent('clonedPurFh|' . $pRec->id, $fileHnd);
                
                return new Redirect(array('purchase_Purchases', 'clonefields', $pRec->id, 'ret_url' => true));
            }
            
            $recArr = (array) $form->rec;
            
            // Кои документи и в каква последователност да се създадат
            if ($form->rec->acceptance) {
                $acceptanceArr = type_Set::toArray($form->rec->acceptance);
                
                if ($acceptanceArr['service']) {
                    $createDocArr['purchase_Services'] = array('details' => 'purchase_ServicesDetails', 'masterKey' => 'shipmentId');
                }
                
                if ($acceptanceArr['store']) {
                    $createDocArr['store_Receipts'] = array('details' => 'store_ReceiptDetails', 'masterKey' => 'receiptId');
                }
            }
            $createDocArr['purchase_Invoices'] = array('details' => 'purchase_InvoiceDetails', 'masterKey' => 'invoiceId');
            
            foreach ($createDocArr as $clsName => $detArr) {
                $detailsArr = arr::make($detArr['details']);
                
                $masterKey = $detArr['masterKey'];
                
                $errMsg = '';
                
                $clsInst = cls::get($clsName);
                
                $singleTitle = $clsInst->singleTitle;
                $singleTitle = mb_strtolower($singleTitle);
                
                // Емулираме създаване от форма на съответния документ
                
                $invForm = $clsInst->getForm();
                $invForm->method = 'POST';
                $invForm->rec->_isClone = true;
                $invForm->rec->threadId = $pRec->threadId;
                $invForm->rec->originId = $pRec->containerId;
                
                if ($clsName == 'purchase_Invoices') {
                    $invForm->rec->fileHnd = $fileHnd;
                    $invForm->rec->vatReason = $form->rec->invVatReason;
                    $invForm->rec->number = $form->rec->invNum;
                    $invForm->rec->date = $form->rec->invDate;
                    $invForm->rec->type = 'invoice';
                }
                
                // Полето за ид не е тихо за да не се обърка и да инпутва ид-то на крон процеса
                $idField = $invForm->getField('id');
                unset($idField->silent);
                
                $data = (object) array('form' => &$invForm);
                $clsInst->invoke('AfterPrepareEditForm', array($data, $data));
                
                $pArr = array('Ignore' => 1);
                
                $cRec = clone $invForm->rec;
                
                foreach ((array) $cRec as $f => $v) {
                    $pArr[$f] = $v;
                }
                
                Request::push($pArr);
                $invForm->cmd = 'save';
                
                // Ид-то не трябва да се инпутва
                $fields = $invForm->selectFields();
                unset($fields['id']);
                $invForm->input(implode(',', array_keys($fields)));
                
                $clsInst->invoke('AfterInputEditForm', array($invForm));
                
                // Инпутваме емулираната форма и ако няма грешки, записваме
                if ($invForm->isSubmitted()) {
                    $rec = $invForm->rec;
                    $savedId = $clsInst->save($rec);
                    if ($savedId) {
                        $clsInst->logInAct('Създаване от файл', $savedId);
                        
                        // След създаване на документа създаваме и детайлите
                        foreach ($recArr as $f => $val) {
                            if (stripos($f, $detFieldPref) === false) {
                                continue;
                            }
                            $dId = str_replace($detFieldPref, '', $f);
                            
                            if (!is_numeric($dId)) {
                                continue;
                            }
                            
                            $dRec = purchase_PurchasesDetails::fetch($dId);
                            
                            // Правилно определяне на артикулите в кой документ да може да се създават
                            if ($dRec->productId) {
                                if ($clsInst instanceof store_Receipts || $clsInst instanceof purchase_Services) {
                                    $pRecStoreAndBuy = cat_Products::fetch($dRec->productId, 'canStore, canBuy');
                                    
                                    if (!$pRecStoreAndBuy->canBuy == 'no') {
                                        continue;
                                    }
                                    
                                    if ($pRecStoreAndBuy->canStore == 'yes') {
                                        if (!($clsInst instanceof store_Receipts)) {
                                            continue;
                                        }
                                    } elseif ($pRecStoreAndBuy->canStore == 'no') {
                                        if (!($clsInst instanceof purchase_Services)) {
                                            continue;
                                        }
                                    }
                                }
                            }
                            
                            $pDetRec = new stdClass();
                            $pDetRec->{$masterKey} = $savedId;
                            $pDetRec->productId = $dRec->productId;
                            $pDetRec->packagingId = $dRec->packagingId;
                            $pDetRec->quantity = $val;
                            $pDetRec->quantityInPack = $dRec->quantityInPack;
                            $pDetRec->price = $dRec->price;
                            $pDetRec->amount = $dRec->amount;
                            $pDetRec->discount = $dRec->discount;
                            $pDetRec->notes = $dRec->notes;
                            $pDetRec->packPrice = $dRec->packPrice;
                            
                            foreach ($detailsArr as $detailName) {
                                $detailName::save($pDetRec);
                            }
                        }
                        
                        status_Messages::newStatus('|Създаден документ|* ' . $clsInst->getLinkToSingle($savedId));
                        
                        if ($clsName == 'purchase_Invoices') {
                            $invId = $savedId;
                        }
                    } else {
                        $errMsg .= ' |Грешка при записване';
                    }
                } else {
                    
                    // Ако има грешки, показваме ги
                    foreach ($invForm->errors as $key => $errObj) {
                        if ($errObj->ignorable) {
                            continue;
                        }
                        
                        // Ако грешката е в номера
                        if ($clsName == 'purchase_Invoices') {
                            if ($key == 'number') {
                                $form->setError('invNum', $errObj->msg);
                                
                                continue;
                            } elseif ($key == 'date') {
                                $form->setError('invDate', $errObj->msg);
                                
                                continue;
                            } elseif ($key == 'vatReason') {
                                $form->setError('invVatReason', $errObj->msg);
                                
                                continue;
                            }
                        }
                        
                        $errMsg .= '<br>' . $errObj->msg;
                    }
                }
                
                // Попваме всички пушнати стойности от формата
                foreach ($pArr as $pArrKey => $pVal) {
                    Request::pop($pArrKey);
                }
                
                if ($errMsg) {
                    status_Messages::newStatus("|Не може да се създаде документ|* |{$singleTitle}|*. |Опитайте ръчно|*:" . $errMsg, 'error');
                }
                
                // Редиктваме към фактурата, ако успешно е създаден документа
                if ($invId) {
                    
                    return new Redirect(purchase_Invoices::getSingleUrlArray($invId));
                }
            }
        }
        
        $form->title = 'Създаване на входяща фактура от файл|* ' . fileman::getLinkToSingle($fileHnd);
        
        $sbTitle = 'Създаване';
        
        // Ако е избран затворен документ - тогава клонираме
        if ($pRec && ($pRec->state == 'closed')) {
            $sbTitle = 'Клониране';
            $form->title = 'Клониране на|* ' . purchase_Purchases::getLinkToSingle($pRec->id);
        }
        
        $form->toolbar->addSbBtn($sbTitle, 'save', 'id=save, ef_icon = img/16/disk.png', 'title=Изпращане на имейл за регистрация на парньори');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
        
        $form->layout = $form->renderLayout();
        
        // Показваме превю на файла
        
        if ($form->cmd != 'refresh') {
            doc_DocumentPlg::showOriginalFile($fRec, $form);
        }
        
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Прихваща извикването на AfterSaveLogChange в change_Plugin
     * Добавя нотификация след промяна на документа
     *
     * @param core_MVc $mvc
     * @param array    $recsArr - Масив със записаните данни
     */
    protected static function on_AfterSaveLogChange($mvc, $recsArr)
    {
        if (is_array($recsArr)) {
            expect($fRec = $recsArr[0]);
            $containerId = $mvc->fetchField($fRec->docId, 'containerId');
            acc_Journal::reconto($containerId);
        }
    }
    
    
    /**
     * Връща вальора на документа по подразбиране
     *
     * @param core_Mvc $mvc
     * @param datetime     $res
     * @param mixed    $rec
     */
    public static function getValiorValue($rec)
    {
        return (!empty($rec->journalDate)) ? $rec->journalDate : $rec->date;
    }
    
    
    /**
     * Връща сч. дата по подразбиране спрямо, датата на входящата фактура
     *
     * @param datetime $date - дата
     *
     * @return datetime
     */
    public function getDefaultAccDate($date)
    {
        $today = dt::today();
        $cLastDay = dt::getLastDayOfMonth($today);
        $prevLastDay = dt::getLastDayOfMonth($today, -1);
        $day = dt::getLastDayOfMonth($date);
        $numOfDay = dt::mysql2verbal($today, 'd');
        
        // Ако датата на фактурата (ДФ) е в текущия месец - СД = ДФ
        if ($day == $cLastDay) {
            
            return $date;
        }
        $nDay = acc_Setup::get('DATE_FOR_INVOICE_DATE');
        
        // Ако ДФ е от предходния месец:
        if ($day == $prevLastDay) {
            
            // Ако текущата дата е ДО $nDay-о число включително - СД = ДФ;
            // Ако текущата дата е СЛЕД $nDay-о число - СД е първо число на текущия месец
            return ($numOfDay <= $nDay) ? $date : dt::mysql2verbal($today, 'Y-m-01');
        }
        
        // Ако ДФ е по-назад (т.е. не е в текущия или предходния месец):
        // Ако текущата дата е ДО 12-о число включително - СД е първо число на предходния месец;
        // Ако текущата дата е СЛЕД 12-о число - СД е първо число на текущия месец
        return ($numOfDay <= $nDay) ? dt::mysql2verbal($prevLastDay, 'Y-m-01') : dt::mysql2verbal($today, 'Y-m-01');
    }
}
