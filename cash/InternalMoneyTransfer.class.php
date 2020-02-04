<?php 

/**
 * Документ за Вътрешно Касов Трансфер
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cash_InternalMoneyTransfer extends core_Master
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=cash_transaction_InternalMoneyTransfer';
    
    
    /**
     * Дали сумата е във валута (различна от основната)
     *
     * @see acc_plg_DocumentSummary
     */
    public $amountIsInNotInBaseCurrency = true;
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Вътрешни касови трансфери';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, cash_Wrapper,acc_plg_Contable, acc_plg_DocumentSummary,
     	plg_Clone,doc_DocumentPlg, plg_Printing, deals_plg_SaveValiorOnActivation, plg_Search, bgerp_plg_Blank, doc_SharablePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, reason, folderId, currencyId=Валута, amount, state, createdOn, createdBy';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,cash';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,cash';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Вътрешен касов трансфер';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/money.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Cvt';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'cash, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo, acc, cash, bank';
    
    
    /**
     * Кой може да го прави заявка?
     */
    public $canPending = 'ceo, acc, cash, bank';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'bank/tpl/SingleInternalMoneyTransfer.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.6|Финанси';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'reason,creditCase,debitBank,debitCase';
    
    
    /**
     * Позволени операции
     */
    public $allowedOperations = array('case2case' => array('debit' => '501', 'credit' => '501'),
                                      'case2bank' => array('debit' => '503', 'credit' => '501'),
                                      'nonecash2bank' => array('debit' => '503', 'credit' => '502'),
                                      'nonecash2case' => array('debit' => '501', 'credit' => '502'),
                                      'noncash2noncash' =>array('debit' => '502', 'credit' => '502'), 
    );
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'valior,createdOn,modifiedOn';
    
    
    /**
     * Дефолтен брой копия при печат
     *
     * @var int
     */
    public $defaultCopiesOnPrint = 2;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('operationSysId', 'enum(case2case=Вътрешен касов трансфер,case2bank=Захранване на банкова сметка,nonecash2bank=Инкасиране на безналични плащания (Банка),nonecash2case=Инкасиране на безналични плащания (Каса),noncash2noncash=Вътрешна касова обмяна на безналични плащания)', 'caption=Операция,mandatory,silent');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума,mandatory,summary=amount,silent');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,silent');
        $this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор');
        $this->FLD('reason', 'richtext(rows=3)', 'caption=Основание,input,mandatory');
        $this->FLD('creditAccId', 'acc_type_Account()', 'caption=Кредит,input=none');
        $this->FLD('creditCase', 'key(mvc=cash_Cases, select=name)', 'caption=От->Каса,silent');
        $this->FLD('paymentId', 'key(mvc=cond_Payments, select=title)', 'caption=От->Безналично плащане,input=none,silent');
        $this->FLD('debitAccId', 'acc_type_Account()', 'caption=Дебит,input=none');
        $this->FLD('debitCase', 'key(mvc=cash_Cases, select=name)', 'caption=Към->Каса,input=none');
        $this->FLD('debitBank', 'key(mvc=bank_OwnAccounts, select=bankAccountId)', 'caption=Към->Банк. сметка,input=none');
        $this->FLD('paymentDebitId', 'key(mvc=cond_Payments, select=title)', 'caption=Към->Безналично плащане,input=none');
        $this->FLD('state', 'enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Контиран,stopped=Спряно, pending=Заявка)','caption=Статус, input=none');
        $this->FLD('sourceId', 'key(mvc=doc_Containers,select=id)', 'input=hidden,silent');
        
        $this->setDbIndex('sourceId');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($requiredRoles == 'no_one') {
            
            return;
        }
        
        if (isset($rec)) {
            if ($rec->operationSysId == 'case2bank') {
                if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'bank_OwnAccounts', 'debitBank')) {
                    $requiredRoles = 'no_one';
                }
            } elseif ($rec->operationSysId == 'case2case' || $rec->operationSysId == 'nonecash2case') {
                if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'cash_Cases', 'debitCase')) {
                    $requiredRoles = 'no_one';
                }
            } elseif ($rec->operationSysId == 'nonecash2bank') {
                if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'bank_OwnAccounts', 'debitBank')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме към формата за търсене търсене по Каса
        cash_Cases::prepareCaseFilter($data, array('creditCase', 'debitCase'));
    }
    
    
    /**
     *  Добавяме помощник за избиране на сч. операция
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        if ($action != 'add') {
            
            return;
        }
        
        if (!$mvc->haveRightFor($action)) {
            
            return;
        }
        
        // Има ли вече зададено основание?
        if (Request::get('operationSysId', 'varchar')) {
           
           // Има основание - не правим нищо
            return;
        }
        
        $form = static::prepareReasonForm();
        $form->input();
        $form->input(null, true);
        $form = $form->renderHtml();
        $tpl = $mvc->renderWrapping($form);
        
        return false;
    }
    
    
    /**
     * Подготвяме формата от която ще избираме посоката на движението
     */
    public static function prepareReasonForm()
    {
        $form = cls::get('core_Form');
        $form->method = 'GET';
        $form->FNC('operationSysId', 'enum(case2case=Вътрешен касов трансфер,case2bank=Захранване на банкова сметка,nonecash2bank=Инкасиране на безналични плащания (Банка),nonecash2case=Инкасиране на безналични плащания (Каса),noncash2noncash=Вътрешна касова обмяна на безналични плащания)', 'input,caption=Операция');
        $form->FNC('folderId', 'key(mvc=doc_Folders,select=title)', 'input=hidden,caption=Папка');
        $form->FNC('linkedHashKey', 'varchar', 'caption=Линк хеш, silent, input=hidden');
        $form->FNC('ret_url', 'varchar(1024)', 'input=hidden,silent');
        $form->title = 'Нов вътрешен касов трансфер';
        $form->toolbar->addSbBtn('Напред', '', 'ef_icon = img/16/move.png, title=Продължете напред');
        
        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = toUrl(array('cash_InternalMoneyTransfer', 'list'));
        }
        
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $folderId = cash_Cases::forceCoverAndFolder(cash_Cases::getCurrent());
        $folderRec = doc_Folders::fetch($folderId);
        if (!doc_Folders::haveRightToObject($folderRec)) {
            $folderId = static::getDefaultFolder(null, false);
        }
        
        $form->setDefault('folderId', $folderId);
        
        return $form;
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        
        // Очакваме и намираме коя е извършената операция
        if (!$form->rec->id) {
            expect($operationSysId = Request::get('operationSysId'));
        } else {
            $operationSysId = $form->rec->operationSysId;
        }
        
        switch ($operationSysId) {
            case 'case2case':
                $form->setField('debitCase', 'input');
                break;
            case 'nonecash2case':
                $form->setField('paymentId', 'input');
                $form->setField('debitCase', 'input');
                break;
            case 'case2bank':
                $form->setField('debitBank', 'input');
                $form->setOptions('debitBank', bank_OwnAccounts::getOwnAccounts());
                break;
            case 'nonecash2bank':
                $form->setField('paymentId', 'input');
                $form->setField('currencyId', 'input=hidden');
                
                $form->setField('debitBank', 'input');
                $form->setOptions('debitBank', bank_OwnAccounts::getOwnAccounts());
                break;
            case 'noncash2noncash':
                $form->setField('paymentId', 'input');
                $form->setField('currencyId', 'input=hidden');
                $form->setField('debitCase', 'input');
                $form->setField('paymentDebitId', 'input');
                
                break;
        }
        $form->setReadOnly('operationSysId');
        $today = dt::verbal2mysql();
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
        $form->setDefault('creditCase', cash_Cases::getCurrent());
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            $rec->debitAccId = $mvc->allowedOperations[$rec->operationSysId]['debit'];
            $rec->creditAccId = $mvc->allowedOperations[$rec->operationSysId]['credit'];
            
            // Проверяваме дали валутите на дебитната сметка съвпадат
            // с тези на кредитната
            $mvc->validateForm($form);
        }
    }
    
    
    /**
     * Ако в документа няма код, който да рутира документа до папка/тред,
     * долния код, рутира документа до "Несортирани - [заглавие на класа]"
     */
    protected static function on_BeforeRoute($mvc, &$res, $rec)
    {
        if ($rec->operationSysId == 'case2bank' || $rec->operationSysId == 'nonecash2bank') {
            $rec->folderId = bank_OwnAccounts::fetchField($rec->debitBank, 'folderId');
        } elseif ($rec->operationSysId == 'case2case') {
            $rec->folderId = cash_Cases::fetchField($rec->debitCase, 'folderId');
        }
    }
    
    
    /**
     * Валидиране на формата спрямо избраната операция
     */
    private function validateForm($form)
    {
        $rec = &$form->rec;
        
        switch ($rec->operationSysId){
            case 'case2case':
                $caseRec = cash_Cases::fetch($rec->debitCase);
                if ($caseRec->autoShare == 'yes') {
                    $rec->sharedUsers = keylist::merge($rec->sharedUsers, $caseRec->cashiers);
                    $rec->sharedUsers = keylist::removeKey($rec->sharedUsers, core_Users::getCurrent());
                }
                
                // Двете Каси трябва да са различни
                if ($rec->creditCase == $rec->debitCase) {
                    $form->setError('debitCase', 'Дестинацията е една и съща|*!');
                }
                break;
            case 'case2bank':
                $bankRec = bank_OwnAccounts::fetch($rec->debitBank);
                if ($bankRec->autoShare == 'yes') {
                    $rec->sharedUsers = keylist::removeKey($bankRec->operators, core_Users::getCurrent());
                }
                
                $debitInfo = bank_OwnAccounts::getOwnAccountInfo($rec->debitBank);
                if ($debitInfo->currencyId != $rec->currencyId) {
                    $form->setError('debitBank', 'Банковата сметка е в друга валута|*!');
                }
                break;
            case 'nonecash2bank':
                $debitInfo = bank_OwnAccounts::getOwnAccountInfo($rec->debitBank);
                if ($debitInfo->currencyId != $rec->currencyId) {
                    $form->setError('debitBank', 'Банковата сметка е в друга валута|*!');
                }
                break;
            case 'noncash2noncash':
                if ($rec->creditCase == $rec->debitCase && $rec->paymentId == $rec->paymentDebitId) {
                    $form->setError('paymentId,paymentDebitId', 'Трябва да посочите различни безналични плащания|*!');
                }
                break;
        }
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getLink($rec->id, 0);
        
        if ($fields['-single']) {
            $row->currency = currency_Currencies::getCodeById($rec->currencyId);
            
            // Изчисляваме равностойността на сумата в основната валута
            if ($rec->rate != '1') {
                $double = cls::get('type_Double');
                $double->params['decimals'] = 2;
                $equals = currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $row->currency);
                $row->equals = $mvc->getFieldType('amount')->toVerbal($equals);
                $row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->valior);
            }
            
            $row->creditCase = cash_Cases::getHyperLink($rec->creditCase, true);
            if(isset($rec->paymentId)){
                $row->creditCase .= " ({$row->paymentId})";
            }
            
            if ($rec->debitCase) {
                $row->creditCase .= " » " . cash_Cases::getHyperLink($rec->debitCase, true);
            
                if(isset($rec->paymentDebitId)){
                    $row->creditCase .= " ({$row->paymentDebitId})";
                }
            }
            
            if ($rec->debitBank) {
                $row->creditCase .= " » " . bank_OwnAccounts::getHyperLink($rec->debitBank, true);
            }
            
            if(isset($rec->sourceId)){
                $Source = doc_Containers::getDocument($rec->sourceId);
                $row->sourceId = $Source->getLink(0);
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
        // Може да създаваме документ-а само в дефолт папката му
        if ($folderId == static::getDefaultFolder(null, false) || doc_Folders::fetchCoverClassName($folderId) == 'cash_Cases') {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        $row->title = $this->singleTitle . " №{$id}";
        $row->subTitle = $this->getVerbal($rec, 'operationSysId');
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        // Споделяме текущия потребител със нишката на заданието
        $cu = core_Users::getCurrent();
        doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
    }
}
