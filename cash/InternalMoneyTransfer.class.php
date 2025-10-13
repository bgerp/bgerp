<?php 

/**
 * Документ за Вътрешно Касов Трансфер
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
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
    public $canList = 'ceo,cash, cashAll';
    
    
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
     * Детайли
     */
    public $details = 'cash_InternalMoneyTransferDetails';


    /**
     * В кои детайли да не се изисква да има запис за активиране
     */
    public $ignoreDetailsToCheckWhenTryingToPost = 'cash_InternalMoneyTransferDetails';


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
    public $searchFields = 'operationSysId,reason,creditCase,debitBank,debitCase';


    /**
     * Кой може да налива чакащите плащания за инкасиране
     */
    public $canCollectnoncashpayments = 'ceo, acc, cash, bank';


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
     * Поле на ориджина за което да се направи линка
     */
    public $addLinkedOriginFieldName = 'sourceId';


    /**
     * Дали да се добави документа като линк към оридижина си
     */
    public $addLinkedDocumentToOriginId = true;


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('operationSysId', 'enum(case2case=Вътрешен касов трансфер,case2bank=Захранване на банкова сметка,nonecash2bank=Инкасиране на безналични плащания (Банка),nonecash2case=Инкасиране на безналични плащания (Каса),noncash2noncash=Вътрешна касова обмяна на безналични плащания)', 'caption=Операция,mandatory,silent');
        $this->FLD('amount', 'double(decimals=2,maxAllowedDecimals=2)', 'caption=Сума,summary=amount,silent,mandatory');
        $this->FLD('amountDetails', 'double(decimals=2,maxAllowedDecimals=2)', 'caption=Сума (Детайли),input=none');

        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,silent');
        $this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор');
        $this->FLD('reason', 'richtext(rows=3)', 'caption=Основание,input,mandatory');
        $this->FLD('creditAccId', 'acc_type_Account()', 'caption=Кредит,input=none');
        $this->FLD('creditCase', 'key(mvc=cash_Cases, select=name)', 'caption=От->Каса,silent');
        $this->FLD('paymentId', 'key(mvc=cond_Payments, select=title)', 'caption=От->Безналично плащане,input=none,silent');
        $this->FLD('debitAccId', 'acc_type_Account()', 'caption=Дебит,input=none');
        $this->FLD('debitCase', 'key(mvc=cash_Cases, select=name)', 'caption=Към->Каса,input=none');
        $this->FLD('debitBank', 'key(mvc=bank_OwnAccounts, select=bankAccountId)', 'caption=Към->Банк. сметка,input=none,silent');
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

        if($action == 'collectnoncashpayments' && isset($rec)) {
            if($rec->state != 'draft' || $rec->operationSysId != 'nonecash2bank') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме към формата за търсене търсене по Каса
        cash_Cases::prepareCaseFilter($data, array('creditCase', 'debitCase'), 'operationSysId');
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
        $form->FNC('folderId', 'key(mvc=doc_Folders,select=title)', 'input=hidden,caption=Папка,silent');
        $form->FNC('linkedHashKey', 'varchar', 'caption=Линк хеш, silent, input=hidden');
        $form->FNC('ret_url', 'varchar(1024)', 'input=hidden,silent');
        $form->input(null, 'silent');
        $form->title = 'Нов вътрешен касов трансфер';
        $form->toolbar->addSbBtn('Напред', '', 'ef_icon = img/16/move.png, title=Продължете напред');

        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = toUrl(array('cash_InternalMoneyTransfer', 'list'));
        }
        
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        $folderId = null;
        $cFolderId = $form->rec->folderId;
        if(isset($cFolderId)){
            $Cover = doc_Folders::getCover($cFolderId);
            if($Cover->isInstanceOf('cash_Cases')){
                if (doc_Folders::haveRightToObject($cFolderId)) {
                    $folderId = $cFolderId;
                }
            }
        }

        if(!isset($folderId)){
            $cFolderId = cash_Cases::forceCoverAndFolder(cash_Cases::getCurrent());
            $cFolderRec = doc_Folders::fetch($cFolderId);
            if (doc_Folders::haveRightToObject($cFolderRec)) {
                $folderId = $cFolderId;
            }
        }

        $folderId = $folderId ?? static::getDefaultFolder(null, false);
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

        $Cover = doc_Folders::getCover($form->rec->folderId);
        if($Cover->isInstanceOf('cash_Cases')){
            if(bgerp_plg_FLB::canUse($Cover->getInstance(), $Cover->fetch(), null, 'select')){
                $form->setDefault('creditCase', $Cover->that);
            }
        }

        switch ($operationSysId) {
            case 'case2case':
                $form->setField('debitCase', 'input');
                $form->setDefault('debitCase', cash_Cases::getCurrent());

                break;
            case 'nonecash2case':
                $form->setField('paymentId', 'input');
                $form->setFieldTypeParams('paymentId', array('allowEmpty' => 'allowEmpty'));
                $form->setField('paymentId', 'mandatory');
                $form->setField('debitCase', 'input');
                $form->setDefault('debitCase', cash_Cases::getCurrent());

                break;
            case 'case2bank':
                $form->setField('debitBank', 'input,mandatory');
                $form->setOptions('debitBank', bank_OwnAccounts::getOwnAccounts());
                break;
            case 'nonecash2bank':
                $form->setField('paymentId', 'input');
                $form->setFieldTypeParams('paymentId', array('allowEmpty' => 'allowEmpty'));
                $form->setField('paymentId', 'mandatory');
                $form->setField('debitBank', 'input,mandatory');
                $form->input('debitBank', 'silent');
                $form->setOptions('debitBank', bank_OwnAccounts::getOwnAccounts());

                if(isset($form->rec->id)){
                    if(cash_InternalMoneyTransferDetails::count("#transferId = '{$form->rec->id}'")){
                        $form->setReadOnly('creditCase');
                        $form->setReadOnly('debitBank');
                        $form->setReadOnly('paymentId');
                    }
                }

                break;
            case 'noncash2noncash':
                $form->setField('amount', 'mandatory');
                $form->setField('paymentId', 'input');
                $form->setField('currencyId', 'input=hidden');
                $form->setField('debitCase', 'input');
                $form->setField('paymentDebitId', 'input');
                $form->setDefault('debitCase', cash_Cases::getCurrent());

                break;
        }
        $form->setReadOnly('operationSysId');
        $today = dt::verbal2mysql();
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
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
            
            // Проверяваме дали валутите на дебитната сметка съвпадат с тези на кредитната
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
                    $rec->sharedUsers = keylist::merge($rec->sharedUsers, keylist::removeKey($caseRec->cashiers, core_Users::getCurrent()));
                }
                
                // Двете Каси трябва да са различни
                if ($rec->creditCase == $rec->debitCase) {
                    $form->setError('debitCase', 'Дестинацията е една и съща|*!');
                }
                break;
            case 'case2bank':
                $bankRec = bank_OwnAccounts::fetch($rec->debitBank);
                if ($bankRec->autoShare == 'yes') {
                    $rec->sharedUsers = keylist::merge($rec->sharedUsers, keylist::removeKey($bankRec->operators, core_Users::getCurrent()));
                }

                $currencyError = null;
                if(!bank_OwnAccounts::canAcceptCurrency($rec->debitBank, $rec->valior, $rec->currencyId, $currencyError)){
                    $form->setError('valior,currencyId,debitBank', $currencyError);
                }
                break;
            case 'nonecash2bank':
                $currencyError = null;
                if(!bank_OwnAccounts::canAcceptCurrency($rec->debitBank, $rec->valior, $rec->currencyId, $currencyError)){
                    $form->setError('valior,currencyId,debitBank', $currencyError);
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

            if(!empty($rec->amountDetails)){
                if($rec->operationSysId == 'nonecash2bank'){
                    if(round($rec->amount, 2) != round($rec->amountDetails, 2)){
                        $row->amount = ht::createHint($row->amount, "Сумата се различава от сумарното по детайли|*: {$row->amountDetails}", 'warning', false);
                    }
                }
            }

            $row->creditCase = tr('Каса|*: ') . cash_Cases::getHyperLink($rec->creditCase);
            if(isset($rec->paymentId)){
                $row->creditCase .= " ({$row->paymentId})";
            }
            
            if ($rec->debitCase) {
                $row->creditCase .= " <span class='quiet'>»</span> " . tr('Каса|*: ') . cash_Cases::getHyperLink($rec->debitCase);

                if(isset($rec->paymentDebitId)){
                    $row->creditCase .= " ({$row->paymentDebitId})";
                }
            }
            
            if ($rec->debitBank) {
                $row->creditCase .= " <span class='quiet'>»</span> " . tr('Банкова сметка|*: ') . bank_OwnAccounts::getHyperLink($rec->debitBank);
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
    public function getDocumentRow_($id)
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

        if($rec->operationSysId == 'nonecash2bank'){
            $count = $mvc->syncNotCollectedRecs($rec);
            if($count){
                core_Statuses::newStatus("Събрани записи за инкасиране|*: {$count}");
            }
        }
    }


    /**
     * Изпълнява се след края на изпълнението на скрипта
     */
    public function getLinkedDocCommentToOrigin_($rec)
    {
        return $this->getVerbal($rec, 'operationSysId');
    }


    /**
     * Изпълнява се преди контиране на документа
     */
    protected static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if($rec->operationSysId == 'nonecash2bank' && !empty($rec->amountDetails)){
            if(round($rec->amount, 2) != round($rec->amountDetails, 2)){
                core_Statuses::newStatus('Въведената сума се различава от очакваната за инкасиране - трябва да се уеднаквят|*!', 'warning');

                return false;
            }
        }
    }


    /**
     * Наливане на всички чакащи плащания за инкасиране
     */
    function act_collectnoncashpayments()
    {
        $this->requireRightFor('collectnoncashpayments');
        expect($id = Request::get('id', 'int'));
        expect($rec = self::fetch($id));
        $this->requireRightFor('collectnoncashpayments', $rec);

        $count = $this->syncNotCollectedRecs($rec);
        $msg = $count ? "Добавени нови плащания за инкасиране|*: {$count}" : "Няма нови плащания за инкасиране за тази сметка";

        followRetUrl(null, $msg);
    }


    /**
     * Кои са чакащите безналични плащания
     *
     * @param stdClass $rec
     * @return int
     */
    private function syncNotCollectedRecs($rec)
    {
        // Извличане на необратните плащания - чакащи за инкасиране
        $dQuery = cash_InternalMoneyTransferDetails::getQuery();
        $dQuery->where("#transferId = {$rec->id}");
        $alreadyCollected = arr::extractValuesFromArray($dQuery->fetchAll(), 'recId');
        $notCollected = cash_NonCashPaymentDetails::getNotCollectedRecs($rec->paymentId, $rec->creditCase, $rec->debitBank, $rec->sourceId);
        $toAdd = array_diff_key($notCollected, $alreadyCollected);

        // Записват се в модела
        foreach ($toAdd as $nonCashRec) {
            $dRec = (object) array('transferId' => $rec->id, 'recId' => $nonCashRec->id);
            cash_InternalMoneyTransferDetails::save($dRec);
            $nonCashRec->transferredContainerId = $rec->containerId;
            cash_NonCashPaymentDetails::save($nonCashRec, 'transferredContainerId');
        }

        return countR($toAdd);
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = &$data->rec;

        if ($mvc->haveRightFor('collectnoncashpayments', $rec)) {
            $data->toolbar->addBtn('За инкасиране', array($mvc, 'collectnoncashpayments', 'id' => $rec->id, 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png,title=Добавяне на чакащите за инкасиране плащания,rows=2');
        }
    }


    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);

        // Изчисляване на сумата на база събраните плащания за инкасиране
        $amount = null;
        $dQuery = cash_InternalMoneyTransferDetails::getQuery();
        $dQuery->EXT('paymentId', 'cash_NonCashPaymentDetails', 'externalName=paymentId,externalKey=recId');
        $dQuery->where("#transferId = {$rec->id}");
        while($dRec = $dQuery->fetch()) {
            $amount += cond_Payments::toBaseCurrency($dRec->paymentId, $dRec->amount, $rec->valior);
        }
        $rec->amountDetails = $amount;

        $this->save($rec, 'amountDetails');
    }


    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);

        if($rec->operationSysId == 'nonecash2bank'){
            cash_InternalMoneyTransferDetails::delete("#transferId = {$rec->id}");
        }
    }
}
