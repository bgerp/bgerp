<?php 

/**
 * Разходен банков документ
 *
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bank_SpendingDocuments extends bank_Document
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=bank_transaction_SpendingDocument, bgerp_DealIntf, email_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Разходни банкови документи';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Разходен банков документ';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/bank_rem.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Rbd';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'bank/tpl/SingleCostDocument.shtml';
    
    
    /**
     * Файл с шаблон за единичен изглед в мобилен
     */
    public $singleLayoutFileNarrow = 'bank/tpl/SingleCostDocumentNarrow.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.4|Финанси';
    
    
    /**
     * Права за плъгин-а bgerp_plg_Export
     */
    public $canExport = 'ceo, invoicerSale, invoicerPurchase, invoicerFindeal';


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn,termDateCalc,valior,modifiedOn,activatedOn';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'termDateCalc, valior=Вальор ,title=Документ, ownAccount=Сметка, invoices=Фактури, folderId, amount, currencyId=Валута, state, createdOn, createdBy';


    /**
     * Описание на модела
     */
    public function description()
    {
        parent::getFields($this);
        $this->setField('termDate', 'caption=Срок');
        $this->FLD('earlyPaymentUntil', 'date', 'caption=Отстъпка за предсрочно плащане->Краен срок,input=none,autohide');
        $this->FLD('earlyPaymentPercent', 'percent(Min=0)', 'caption=Отстъпка за предсрочно плащане->Отстъпка,input=none,autohide');
        $this->XPR('termDateCalc', 'date', 'IF(#earlyPaymentUntil IS NOT NULL AND #earlyPaymentUntil >= CURDATE(), #earlyPaymentUntil, #termDate)', 'caption=Срок');
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $today = dt::verbal2mysql();
        
        $contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
        $contragentClassId = doc_Folders::fetchField($form->rec->folderId, 'coverClass');
        $form->setDefault('contragentId', $contragentId);
        $form->setDefault('contragentClassId', $contragentClassId);
        
        expect($origin = $mvc->getOrigin($form->rec), $form->rec);
        $accountOptions = $mvc->getOwnAccountOptions($form->rec->ownAccount);
        $mvc->invoke('AfterGetOwnAccountOptions', array($form, &$accountOptions));
        $form->setOptions('ownAccount', $accountOptions);

        $options = array();
        $mvc->setDefaultsFromOrigin($origin, $form, $options);
        $form->setSuggestions('contragentIban', bank_Accounts::getContragentIbans($form->rec->contragentId, $form->rec->contragentClassId));
        
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
        $form->setOptions('operationSysId', $options);
        
        if (isset($form->defaultOperation) && array_key_exists($form->defaultOperation, $options)) {
            $form->setDefault('operationSysId', $form->defaultOperation);
        }
        
        $cData = cls::get($contragentClassId)->getContragentData($contragentId);
        $form->setReadOnly('contragentName', ($cData->person) ? $cData->person : $cData->company);

        $form->setField('ownAccount', 'caption=От->Сметка,after=reason');
        $form->setField('currencyId', 'caption=От->Валута,after=ownAccount');
        $form->setField('amount', 'caption=От->Заверени,after=reason');
        $form->setField('contragentName', 'caption=Към->Контрагент,after=reason');
        $form->setField('contragentIban', 'caption=Към->Сметка,after=reason');

        // Ако документа е към покупка
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        if($firstDoc->isInstanceOf('purchase_Purchases')){

            // И нейния метод за плащане е с отстъпка за предсрочно плащане
            if($paymentMethodId = $firstDoc->fetchField('paymentMethodId')){
                $paymentRec = cond_PaymentMethods::fetch($paymentMethodId);
                if(!empty($paymentRec->discountPercent) && !empty($paymentRec->discountPeriod)){
                    $form->setField('earlyPaymentUntil', 'input');
                    $form->setField('earlyPaymentPercent', 'input');

                    // Ако е към входяща фактура да излизат попълнени данните за плащането
                    if(isset($rec->originId)) {
                        $originDoc = doc_Containers::getDocument($rec->originId);
                        if($originDoc->isInstanceOf('purchase_Invoices')){
                            $form->setDefault('earlyPaymentPercent', $paymentRec->discountPercent);
                            $form->setDefault('earlyPaymentUntil', dt::addSecs($paymentRec->discountPeriod, $originDoc->fetchField('date')));
                        } elseif($originDoc->isInstanceOf('purchase_Purchases')){
                            $form->setDefault('earlyPaymentPercent', $paymentRec->discountPercent);
                        }
                    } else {
                        $form->setDefault('earlyPaymentPercent', $paymentRec->discountPercent);
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща платежните операции
     */
    protected static function getOperations($operations)
    {
        $options = array();
        
        // Оставяме само тези операции, в които се дебитира основната сметка на документа
        foreach ($operations as $sysId => $op) {
            if ($op['credit'] == static::$baseAccountSysId) {
                $options[$sysId] = $op['title'];
            }
        }
        
        return $options;
    }
    
    
    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова".
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if ($rec->state == 'draft') {
            if (bank_PaymentOrders::haveRightFor('add', (object) array('originId' => $rec->containerId, 'folderId' => $rec->folderId))) {
                $data->toolbar->addBtn('Платежно нареждане', array('bank_PaymentOrders', 'add', 'originId' => $rec->containerId, 'ret_url' => true, ''), null, 'ef_icon=img/16/pln.png,title=Създаване на ново платежно нареждане');
            }
            
            if (bank_CashWithdrawOrders::haveRightFor('add', (object) array('originId' => $rec->containerId, 'folderId' => $rec->folderId))) {
                $data->toolbar->addBtn('Нареждане разписка', array('bank_CashWithdrawOrders', 'add', 'originId' => $rec->containerId, 'ret_url' => true, ''), null, 'ef_icon=img/16/nrrz.png,title=Създаване на ново нареждане разписка');
            }
        }
    }


    /**
     * Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(!empty($rec->earlyPaymentUntil) && !empty($rec->earlyPaymentPercent)){
            $row->earlyPaymentPercent = $row->earlyPaymentPercent ?? $mvc->getFieldType('earlyPaymentPercent')->toVerbal($rec->earlyPaymentPercent);
            $row->earlyPaymentUntil = $row->earlyPaymentUntil ?? $mvc->getFieldType('earlyPaymentUntil')->toVerbal($rec->earlyPaymentUntil);

            if(isset($fields['-single'])) {
                $row->earlyPaymentInfo = tr("|*<b>{$row->earlyPaymentPercent}</b> |отстъпка при плащане до|* <b>{$row->earlyPaymentUntil}</b>");
            }

            $valior = $rec->valior ?? dt::today();
            if($valior > $rec->earlyPaymentUntil){
                $row->earlyPaymentClass = 'quiet';
                $row->earlyPaymentInfo .= " (" . tr('изтекло') . ")";
            } else {
                $row->earlyPaymentClass = 'earlyPaymentDiscountActive';

                // Ако вальора е в срока на предсрочно плащане да се показва с каква сума е намалена
                $amountWithoutDiscount = round($rec->amount * (1 - $rec->earlyPaymentPercent), 2);
                $amountWithoutDiscountVerbal = $mvc->getFieldType('amount')->toVerbal($amountWithoutDiscount);
                if(in_array($rec->state, array('draft', 'pending'))){
                    $icon = isset($fields['-list']) ? 'notice' : 'noicon';
                    $hintColor = '#3939ef;';
                    $infoSuffix = " |при плащане до|* {$row->earlyPaymentUntil}";
                } else {
                    $hintColor = 'black';
                    $icon = 'noicon';
                    $infoSuffix = '';
                }

                $row->amount = ht::createHint("<span style='color:{$hintColor}'>{$amountWithoutDiscountVerbal}</span>", "Намалена с|* {$row->earlyPaymentPercent} |от|* " . currency_Currencies::decorate($row->amount, $rec->currencyId, true) . $infoSuffix, $icon, false);
                if(in_array($rec->state, array('draft', 'pending')) && isset($fields['-list'])){
                    $row->amount = ht::createElement('div', array('class' => 'amountBadge'), $row->amount, true);
                }
                // Ако сме в сингъла да се показва и намалената сума
                if(isset($fields['-single'])) {
                    if(!empty($row->amountDeal)){
                        $amountDealWithoutDiscount = round($rec->amountDeal * (1 - $rec->earlyPaymentPercent), 2);
                        $amountDealWithoutDiscountVerbal = $mvc->getFieldType('amountDeal')->toVerbal($amountDealWithoutDiscount);
                        $row->amountDeal = ht::createHint("<span style='color:{$hintColor}'>{$amountDealWithoutDiscountVerbal}</span>", "Намалена с|* {$row->earlyPaymentPercent} |от|* " . currency_Currencies::decorate($row->amountDeal, $rec->dealCurrencyId, true) . $infoSuffix, 'noicon');
                    }
                }
            }

            if(Mode::isReadOnly()){
                unset($row->earlyPaymentClass);
            }
        }
    }


    /**
     * Проверка след изпращането на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = &$form->rec;

        if($form->isSubmitted()){
            if((!empty($rec->earlyPaymentUntil) && empty($rec->earlyPaymentPercent)) || (empty($rec->earlyPaymentUntil) && !empty($rec->earlyPaymentPercent))){
                $form->setError('earlyPaymentUntil,earlyPaymentPercent', 'Трябва и двете полета за отстъпка при предсрочно плащане да са попълнени');
            }
        }
    }


    /**
     * Метод по подразбиране допълващ полетата за филтриране в съмърито в лист изгледа
     * @see acc_plg_DocumentSummary
     */
    public function fillSummaryRec(&$rec, &$summaryFields)
    {
        if(!empty($rec->earlyPaymentUntil)){
            $valior = $rec->valior ?? dt::today();
            if($valior <= $rec->earlyPaymentUntil){
                $rec->amount = round($rec->amount * (1 - $rec->earlyPaymentPercent), 2);
            }
        }
    }
}
