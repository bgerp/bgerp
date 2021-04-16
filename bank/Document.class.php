<?php


/**
 * Документ за наследяване от банковите документи
 *
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class bank_Document extends deals_PaymentDocument
{
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * Дали сумата е във валута (различна от основната)
     *
     * @see acc_plg_DocumentSummary
     */
    public $amountIsInNotInBaseCurrency = true;
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, bank_Wrapper, acc_plg_RejectContoDocuments, acc_plg_Contable,
         plg_Sorting, plg_Clone, doc_DocumentPlg, plg_Printing,deals_plg_SelectInvoice, acc_plg_DocumentSummary,doc_plg_HidePrices,
         plg_Search, bgerp_plg_Blank, doc_EmailCreatePlg, doc_SharablePlg, deals_plg_SetTermDate,deals_plg_SaveValiorOnActivation,bgerp_plg_Export';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amount,amountVerbal';
    
    
    /**
     * Кой може да избира ф-ра по документа?
     */
    public $canSelectinvoice = 'cash, ceo, purchase, sales, acc';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, reason, fromContainerId, folderId, currencyId, amount, state, createdOn, createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'bank, ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'bank, ceo, purchase, sales';
    
    
    /**
     * Кой може да създава?
     */
    public $canAdd = 'bank, ceo, purchase, sales';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'bank, ceo, purchase, sales';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'bank, ceo, purchase, sales';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'bank, ceo';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'reason, contragentName, amount';
    
    
    /**
     * Основна сч. сметка
     */
    public static $baseAccountSysId = '503';
    
    
    /**
     * До потребители с кои роли може да се споделя документа
     *
     * @var string
     *
     * @see doc_SharablePlg
     */
    public $shareUserRoles = 'ceo, bank';
    
    
    /**
     * Дата на очакване
     */
    public $termDateFld = 'termDate';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'amountDeal,termDate,amount,valior';
    
    
    /**
     * Добавяне на дефолтни полета
     *
     * @param core_Mvc $mvc
     *
     * @return void
     */
    protected function getFields(core_Mvc &$mvc)
    {
        $mvc->FLD('operationSysId', 'varchar', 'caption=Операция,mandatory');
        $mvc->FLD('amountDeal', 'double(decimals=2,max=2000000000,min=0)', 'caption=Платени,mandatory,silent');
        $mvc->FLD('dealCurrencyId', 'key(mvc=currency_Currencies, select=code)', 'input=hidden');
        $mvc->FLD('termDate', 'date(format=d.m.Y)', 'caption=Очаквано на,silent');
        
        $mvc->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,input=hidden');
        $mvc->FLD('rate', 'double(decimals=5)', 'caption=Курс,input=none');
        $mvc->FLD('reason', 'richtext(bucket=Notes,rows=6)', 'caption=Основание');
        $mvc->FLD('contragentName', 'varchar(255)', 'caption=От->Контрагент,mandatory');
        $mvc->FLD('contragentIban', 'iban_Type(64)', 'caption=От->Сметка');
        $mvc->FLD('ownAccount', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)', 'caption=В->Сметка,silent,removeAndRefreshForm=currencyId|amount');
        $mvc->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,summary=amount,input=hidden');
        $mvc->FLD('valior', 'date(format=d.m.Y)', 'caption=Допълнително->Вальор,autohide');
        
        $mvc->FLD('contragentId', 'int', 'input=hidden,notNull');
        $mvc->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
        $mvc->FLD('debitAccId', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'caption=debit,input=none');
        $mvc->FLD('creditAccId', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'caption=Кредит,input=none');
        $mvc->FLD(
            'state',
                'enum(draft=Чернова, active=Активиран, rejected=Оттеглен,stopped=Спряно, pending=Заявка)',
                'caption=Статус, input=none'
        );
        $mvc->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
    }
    
    
    /**
     * Метод за бързо създаване на чернова сделка към контрагент
     *
     * @param mixed $contragentClass - ид/инстанция/име на класа на котрагента
     * @param int   $contragentId    - ид на контрагента
     * @param array $fields          - стойности на полетата на сделката
     * 		o $fields['valior']              - вальор
     *      o $fields['operation']           - операция
     *  	o $fields['termDate']            - очаквана дата
     *   	o $fields['reason']              - основание
     *    	o $fields['ownAccountId']        - ид на наша сметка
     *     	o $fields['contragentIban']      - IBAN на контрагента
     *      o $fields['amountDeal']          - сума, която ще бъде платена по сделката, във валутата на сделката
     *      o $fields['amountFromAccountId'] - сума, която ще бъде заверена, във валутата на сметката на контрагента
     *      o $fields['valior']              - вальор (ако няма е текущата дата)
     * @param boolean $pending               - да се създаде ли директно като заявка
     *        
     * @return mixed $stdClass/FALSE - Запис или FALSE
     */
    public static function create($threadId, $fields = array(), $pending = false)
    {
        // Може ли документа да се добави към нишката
        expect(doc_Threads::fetch($threadId), 'Невалиден тред');
        expect(static::canAddToThread($threadId), 'Документа не може да бъде добавен в нишката');
        expect(isset($fields['operation']), 'Няма systemId на операция');
        
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        expect($dealInfo = $firstDoc->getAggregateDealInfo());
        $firstRec = $firstDoc->fetch();
        
        // Допустима ли е операцията
        $operations = $firstDoc->getPaymentOperations();
        $options = static::getOperations($operations);
        expect(array_key_exists($fields['operation'], $options), "Недопустима операция {$fields['operation']}");
        if(isset($fields['termDate'])){
            $fields['termDate'] = core_Type::getByName('date')->fromVerbal($fields['termDate']);
        }
        if(isset($fields['valior'])){
            $fields['valior'] = core_Type::getByName('date')->fromVerbal($fields['valior']);
        }
        
        if(isset($fields['reason'])){
            $fields['reason'] = core_Type::getByName('varchar')->fromVerbal(trim($fields['reason']));
        }
        
        $dealCurrencyId = currency_Currencies::getIdByCode($dealInfo->currency);
        $currencyId = $dealCurrencyId;
        if(isset($fields['ownAccountId'])){
            $ownAccountInfo = bank_OwnAccounts::getOwnAccountInfo($fields['ownAccountId']);
            expect($ownAccountInfo, "Няма наша сметка с ид|* {$fields['ownAccountId']}");
            $currencyId = $ownAccountInfo->currencyId;
        }
        
        if(isset($fields['contragentIban'])){
            $Iban = core_Type::getByName('iban_Type(64)');
            $fields['contragentIban'] = $Iban->fromVerbal($fields['contragentIban']);
            $checkArr = $Iban->isValid($fields['contragentIban']);
            expect(empty($checkArr['error']), $checkArr['error']);
        }
        
        // Подготвяне на записа
        $rec = (object)array('operationSysId' =>$fields['operation'], 
                             'threadId' => $threadId, 
                             'termDate' => $fields['termDate'],
                             'valior' => $fields['valior'],
                             'contragentClassId' => $firstRec->contragentClassId,
                             'contragentId' => $firstRec->contragentId,
                             'state' => 'draft',
                             'reason' => $fields['reason'],
                             'currencyId' => $currencyId,
                             'contragentIban' => $fields['contragentIban'],
                             'ownAccount' => $fields['ownAccountId'],
                             'dealCurrencyId' => $dealCurrencyId,
        );
        
        if(isset($fields['contragentName'])){
            $fields['contragentName'] = core_Type::getByName('varchar')->fromVerbal($fields['contragentName']);
        } else {
            $cData = cls::get($rec->contragentClassId)->getContragentData($rec->contragentId);
            $fields['contragentName'] = ($cData->person) ? $cData->person : $cData->company;
        }
        $rec->contragentName = $fields['contragentName'];
        
        $operation = $dealInfo->allowedPaymentOperations[$rec->operationSysId];
        $debitAcc = empty($operation['reverse']) ? $operation['debit'] : $operation['credit'];
        expect($debitAcc);
        $creditAcc = empty($operation['reverse']) ? $operation['credit'] : $operation['debit'];
        expect($creditAcc);
        
        $rec->debitAccId = $debitAcc;
        $rec->creditAccId = $creditAcc;
        $rec->isReverse = empty($operation['reverse']) ? 'no' : 'yes';
        
        if(!isset($fields['amountDeal'])){
            $fields['amountDeal'] = ($dealInfo->amount / $dealInfo->rate);
        } else {
            $fields['amountDeal'] = core_Type::getByName('double')->fromVerbal($fields['amountDeal']);
            expect(!is_null($fields['amountDeal']) && $fields['amountDeal'] != false, "Невалидна сума {$fields['amountDeal']}");
        }
        $rec->amountDeal = $fields['amountDeal'];
        $from = currency_Currencies::getCodeById($rec->dealCurrencyId);
        $to = currency_Currencies::getCodeById($rec->currencyId);
        
        if(empty($fields['amountFromAccountId'])){
            if($rec->dealCurrencyId == $rec->currencyId){
                $rec->amount = $rec->amountDeal;
            } else {
                $rec->amount = currency_CurrencyRates::convertAmount($rec->amountDeal, $rec->valior, $from, $to);
            }
        } else {
            if($rec->dealCurrencyId == $rec->currencyId){
                expect(round($rec->amountDeal, 4) == round($fields['amountFromAccountId'], 4));
            } else {
                if ($msg = currency_CurrencyRates::checkAmounts($fields['amountFromAccountId'], $rec->amountDeal, $rec->valior, $from, $to)) {
                    expect(false, $msg);
                }
            }
            $rec->amount = $fields['amountFromAccountId'];
        }
        
        // Ако се създава като заявка
        if($pending === true){
            $rec->state = 'pending';
            $rec->brState = 'draft';
            $rec->pendingSaved = true;
        }
        
        self::save($rec);
        
        return $rec;
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = &$form->rec;
        
        if ($rec->currencyId != $rec->dealCurrencyId) {
            if (isset($rec->ownAccount)) {
                $ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->ownAccount);
                if (isset($ownAcc->currencyId)) {
                    $code = currency_Currencies::getCodeById($ownAcc->currencyId);
                    $form->setField('amount', "unit={$code}");
                }
                
                $caption = ($mvc instanceof bank_SpendingDocuments) ? 'От' : 'В';
                $form->setField('amount', "input,caption={$caption}->Заверени");
            }
        }
        
        if ($form->isSubmitted()) {
            if (!isset($rec->amount) && $rec->currencyId != $rec->dealCurrencyId) {
                $form->setField('amount', 'input');
                $form->setError('amount', 'Когато сметката е във валута - различна от тази на сделката, сумата трябва да е попълнена');
                
                return;
            }
            
            $origin = $mvc->getOrigin($form->rec);
            $dealInfo = $origin->getAggregateDealInfo();
            
            // Коя е дебитната и кредитната сметка
            $operations = $dealInfo->get('allowedPaymentOperations');
            $operation = $operations[$rec->operationSysId];
            $debitAcc = empty($operation['reverse']) ? $operation['debit'] : $operation['credit'];
            $creditAcc = empty($operation['reverse']) ? $operation['credit'] : $operation['debit'];
            $rec->debitAccId = $debitAcc;
            $rec->creditAccId = $creditAcc;
            $rec->isReverse = empty($operation['reverse']) ? 'no' : 'yes';
            
            $currencyCode = currency_Currencies::getCodeById($rec->currencyId);
            $rec->rate = currency_CurrencyRates::getRate($rec->valior, $currencyCode, null);
            
            if ($rec->currencyId == $rec->dealCurrencyId) {
                $rec->amount = $rec->amountDeal;
            }
            
            $dealCurrencyCode = currency_Currencies::getCodeById($rec->dealCurrencyId);
            if ($msg = currency_CurrencyRates::checkAmounts($rec->amount, $rec->amountDeal, $rec->valior, $currencyCode, $dealCurrencyCode)) {
                $form->setError('amountDeal', $msg);
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // Ако няма такава банкова сметка, тя автоматично се записва
        if ($rec->contragentIban) {
            bank_Accounts::add($rec->contragentIban, $rec->currencyId, $rec->contragentClassId, $rec->contragentId);
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме към формата за търсене търсене по Каса
        bank_OwnAccounts::prepareBankFilter($data, array('ownAccount'));
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
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        $docState = $firstDoc->fetchField('state');
        
        if (!empty($firstDoc) && ($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')) {
            
            // Ако няма позволени операции за документа не може да се създава
            $operations = $firstDoc->getPaymentOperations();
            $options = static::getOperations($operations);
            
            return countR($options) ? true : false;
        }
        
        return false;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     *
     * @return bgerp_iface_DealAggregator
     *
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
        $rec = static::fetchRec($id);
        if ($rec->ownAccount) {
            $aggregator->setIfNot('bankAccountId', bank_OwnAccounts::fetchField($rec->ownAccount, 'bankAccountId'));
        }
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        $singleTitle = mb_strtolower($this->singleTitle);
        $tpl = new ET(tr("Моля запознайте се с нашия {$singleTitle}") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // Документа не може да се създава  в нова нишка, ако е възоснова на друг
        if (!empty($data->form->toolbar->buttons['btnNewThread'])) {
            $data->form->toolbar->removeBtn('btnNewThread');
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        // Ако не е избрана сметка, показваме бутона за контиране но с грешка
        if (($rec->state == 'draft' || $rec->state == 'pending') && !isset($rec->ownAccount) && $mvc->haveRightFor('conto')) {
            $data->toolbar->addBtn('Контиране', array(), array('id' => 'btnConto', 'error' => 'Документът не може да бъде контиран, докато няма посочена банкова сметка|*!'), 'ef_icon = img/16/tick-circle-frame.png,title=Контиране на документа');
        }
    }
    
    
    /**
     * Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getLink($rec->id, 0);
        
        if ($fields['-single']) {
            if ($rec->dealCurrencyId != $rec->currencyId) {
                $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
                
                if ($rec->dealCurrencyId == $baseCurrencyId) {
                    $rate = $rec->amountDeal / $rec->amount;
                    $rateFromCurrencyId = $rec->dealCurrencyId;
                    $rateToCurrencyId = $rec->currencyId;
                } else {
                    $rate = $rec->amount / $rec->amountDeal;
                    $rateFromCurrencyId = $rec->currencyId;
                    $rateToCurrencyId = $rec->dealCurrencyId;
                }
                $row->rate = cls::get('type_Double', array('params' => array('decimals' => 5)))->toVerbal($rate);
                $row->rateFromCurrencyId = currency_Currencies::getCodeById($rateFromCurrencyId);
                $row->rateToCurrencyId = currency_Currencies::getCodeById($rateToCurrencyId);
            } else {
                unset($row->dealCurrencyId);
                unset($row->amountDeal);
                unset($row->rate);
            }
            
            // Вземаме данните за нашата фирма
            $headerInfo = deals_Helper::getDocumentHeaderInfo($rec->contragentClassId, $rec->contragentId, $row->contragentName);
            foreach (array('MyCompany', 'MyAddress', 'contragentName', 'contragentAddress') as $fld) {
                $row->{$fld} = $headerInfo[$fld];
            }
            
            if (isset($rec->ownAccount)) {
                $row->ownAccount = bank_OwnAccounts::getHyperlink($rec->ownAccount);
            } else {
                $row->ownAccount = tr('Предстои да бъде уточнена');
                $row->ownAccount = "<span class='red'><small><i>{$row->ownAccount}</i></small></span>";
            }
            
            if ($origin = $mvc->getOrigin($rec)) {
                $options = $origin->allowedPaymentOperations;
                $row->operationSysId = $options[$rec->operationSysId]['title'];
            }
        }
    }
    
    
    /**
     * Задава стойности по подразбиране от продажба/покупка
     *
     * @param core_ObjectReference $origin  - ориджин на документа
     * @param core_Form            $form    - формата
     * @param array                $options - масив с сч. операции
     *
     * @return void
     */
    protected function setDefaultsFromOrigin(core_ObjectReference $origin, core_Form &$form, &$options)
    {
        $dealInfo = $origin->getAggregateDealInfo();
        
        $cId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
        $form->setDefault('dealCurrencyId', $cId);
        $form->setDefault('rate', $dealInfo->get('rate'));

        $exOptions = $form->getField('ownAccount')->options;

        if(isset($form->rec->fromContainerId)){
            $FromContainer = doc_Containers::getDocument($form->rec->fromContainerId);
            if($FromContainer->isInstanceOf('deals_InvoiceMaster')){
                if($bankId = $FromContainer->fetchField('accountId')){
                    if($FromContainer->isInstanceOf('purchase_Invoices')){
                        $iban = bank_Accounts::fetchField($bankId, 'iban');
                        $form->setDefault('contragentIban', $iban);
                    } else {
                        if(array_key_exists($bankId, $exOptions)){
                            $form->setDefault('ownAccount', $bankId);
                        }
                    }
                }
            }
        }
        
        if (empty($form->rec->id) && $form->cmd != 'refresh') {
            if($dealInfo->get('bankAccountId')){
                $bankId = bank_OwnAccounts::fetchField("#bankAccountId = {$dealInfo->get('bankAccountId')}", 'id');
                if(array_key_exists($bankId, $exOptions)){
                    $form->setDefault('ownAccount', $bankId);
                }
            }

            $form->setDefault('ownAccount', bank_OwnAccounts::getCurrent('id', false));
        }
        
        if (isset($form->rec->ownAccount)) {
            $ownAcc = bank_OwnAccounts::getOwnAccountInfo($form->rec->ownAccount);
            $form->setDefault('currencyId', $ownAcc->currencyId);
        } else {
            $form->setDefault('currencyId', $form->rec->dealCurrencyId);
        }
        
        $pOperations = $dealInfo->get('allowedPaymentOperations');
        $defaultOperation = $dealInfo->get('defaultBankOperation');
        $options = static::getOperations($pOperations);
        expect(countR($options));
        
        if ($expectedPayment = $dealInfo->get('expectedPayment')) {
            if (isset($form->rec->originId, $form->rec->amountDeal)) {
                $expectedPayment = $form->rec->amountDeal * $dealInfo->get('rate');
            }
            
            $amount = core_Math::roundNumber($expectedPayment / $dealInfo->get('rate'));
            
            if ($form->rec->currencyId == $form->rec->dealCurrencyId) {
                $form->setDefault('amount', $amount);
            }
        }
        
        if (isset($defaultOperation) && array_key_exists($defaultOperation, $options)) {
            $form->setDefault('operationSysId', $defaultOperation);
            
            $dAmount = currency_Currencies::round($amount, $dealInfo->get('currency'));
            if ($dAmount != 0) {
                $form->setDefault('amountDeal', $dAmount);
            }
        }
        
        $form->setField('amountDeal', array('unit' => "|*{$dealInfo->get('currency')} |по сделката|*"));
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($requiredRoles == 'no_one') {
            
            return;
        }
        if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'bank_OwnAccounts', 'ownAccount')) {
            $requiredRoles = 'no_one';
        }
    }
}
