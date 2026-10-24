<?php

/**
 * Детайл за фактури към документи
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_InvoicesToDocuments extends core_Manager
{
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой може да създава?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * Заглавие
     */
    public $title = 'Фактури към документи';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('documentContainerId', 'key(mvc=doc_Containers, select=id)', 'input=hidden,mandatory,silent,caption=Документ');
        $this->FLD('containerId', 'key(mvc=doc_Containers, select=id)', 'caption=Фактура');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума,mandatory');

        $this->setDbIndex('documentContainerId');
        $this->setDbUnique('documentContainerId,containerId');
    }


    /**
     * Екшън за избор на ф-ри към документ
     */
    function act_SelectInvoice()
    {
        expect($documentId = Request::get('documentId', 'int'));
        expect($documentClassId = Request::get('documentClassId', 'int'));
        $Document = cls::get($documentClassId);
        expect($rec = $Document->fetch($documentId));
        $Document->requireRightFor('selectinvoice');
        $Document->requireRightFor('selectinvoice', $rec);
        $ctx = static::getSelectionContext($Document, $rec);
        $paymentData = $ctx->paymentData;

        $form = cls::get('core_Form');
        $form->title = "Избор на фактури към|* " . cls::get($Document)->getFormTitleLink($documentId);

        $tVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($paymentData->amount);
        $currencyCode = $ctx->currencyCode;
        $form->info = tr("За разпределяне:") . " <b>{$tVerbal} {$currencyCode}</b>";

        // Задаване на наличните фактури за избор
        $invoices = $ctx->options;

        // Ако има сконто фактурите ще се показват като списък
        if($ctx->mode == 'list'){
            $form->FLD('invoicesList', "keylist(mvc=doc_Containers,select=id)", "caption=Документи");
            $form->setSuggestions('invoicesList', array('' => '') + $invoices);
            $curInvoiceArr = static::getInvoicesTableArr($rec->containerId);
            if(countR($curInvoiceArr)){
                $selectedContainerIds = array_combine($curInvoiceArr['containerId'], $curInvoiceArr['containerId']);
                $form->setDefault('invoicesList', keylist::fromArray($selectedContainerIds));
            }
        } elseif($ctx->mode == 'one'){

            // Ако ще е само една ф-ра ще се показва като избор
            $form->FLD('fromContainerId', "int", "caption=Избор");
            $form->setOptions('fromContainerId', array('' => '') + $invoices);
            $form->setDefault('fromContainerId', $rec->fromContainerId ?? null);
        } else {

            // Ако може да са няколко ф-ри и няма сконто се показва таблица, потребителя да си разписва свободно
            $form->FLD('invoices', "table(columns=containerId|amount,captions=Документ|Сума ({$currencyCode}),validate=deals_InvoicesToDocuments::validateTable)", "caption=Избор");
            $form->setFieldTypeParams('invoices', array('amount_sgt' => array('' => '', "{$paymentData->amount}" => $paymentData->amount), 'containerId_opt' => array('' => '') + $invoices, 'totalAmount' => $paymentData->amount, 'currencyId' => $paymentData->currencyId));
            $curInvoiceArr = static::getInvoicesTableArr($rec->containerId);
            $form->setDefault('invoices', $curInvoiceArr);
        }

        $form->input();
        if($form->isSubmitted()){
            // Разпределянията в една сделка се записват едно след друго и върху прочетеното под заключването
            $lockKey = static::getSelectionLockKey($rec);
            if(!core_Locks::obtain($lockKey, 60, 10, 1)){
                $form->setError('invoices,fromContainerId,invoicesList', 'Разпределението по фактури в момента се променя. Опитайте отново|*!');
            } else {
                try {
                    $rec = $Document->fetch($documentId, '*', false);
                    $ctx = static::getSelectionContext($Document, $rec);
                    $fRec = $form->rec;

                    $invArr = $fullRecs = array();
                    if($form->cmd == 'reset'){
                        $fRec->invoices = null;
                        $fRec->fromContainerId = null;
                        $fRec->invoicesList = null;
                    }

                    // Ако е избрана таблица със фактури
                    if(!empty($fRec->invoices)){
                        $iData = json_decode($fRec->invoices, true);
                        list($iData, $fullRecs) = static::fillTableSelection($rec, $ctx, is_array($iData) ? $iData : array());

                        $fRec->invoices = @json_encode($iData);
                        $invArr = type_Table::toArray($form->rec->invoices);
                    } elseif(!empty($fRec->fromContainerId)){

                        // Ако е избрана конкретна ф-ра
                        list($invArr, $fullRecs, $warning) = static::prepareOneSelection($Document, $rec, $ctx, $fRec->fromContainerId);
                        if($warning){
                            $form->setWarning('fromContainerId', $warning);
                        }
                    } elseif(!empty($fRec->invoicesList)){
                        list($invArr, $fullRecs) = static::prepareListSelection(keylist::toArray($fRec->invoicesList));
                    }

                    foreach (static::checkSelection($Document, $rec, $ctx, $invArr, $fullRecs) as $check){
                        if($check->type == 'error'){
                            $form->setError($check->fields, $check->msg);
                        } else {
                            $form->setWarning($check->fields, $check->msg);
                        }
                    }

                    if(!$form->gotErrors()){
                        $res = static::saveSelection($Document, $rec, $invArr);
                        $msg = $res->count ? 'Разпределението по фактури е записано|*!' : 'Разпределението по фактури е премахнато|*!';
                        followRetUrl(null, $msg);
                    }
                } finally {
                    core_Locks::release($lockKey);
                }
            }
        }

        // Добавяне на тулбар
        $form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Импорт,order=9.8');
        $form->toolbar->addSbBtn('Изчистване', 'reset', 'ef_icon = img/16/delete.png, title = Импорт,warning=Наистина ли желаете да изтриете разпределянията към фактури?,order=9.99');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията,order=9.9999');

        // Рендиране на опаковката
        $tpl = $Document->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);

        return $tpl;
    }


    /**
     * Данни за избора на ф-ри към документ: сума, валута, режим на избора и възможните ф-ри
     *
     * @param core_Mvc $Document
     * @param stdClass $rec
     * @return stdClass
     */
    public static function getSelectionContext($Document, $rec)
    {
        $paymentData = $Document->getPaymentData($rec);
        $operationSysId = $paymentData->operationSysId ?? null;
        $isReverse = $paymentData->isReverse ?? false;
        $isTransfer = in_array($operationSysId, array('case2customer', 'bank2customer', 'caseAdvance2customer', 'bankAdvance2customer', 'supplier2case', 'supplier2bank', 'supplierAdvance2case', 'supplierAdvance2bank'));

        $onlyOneAllowedInvoice = $Document->canBeOnlyToOneInvoice($rec);
        $options = $Document->getReasonContainerOptions($rec);

        // Има ли сконто в документа - ако има ограничаваме избора до наличните фактури
        $haveCashDiscount = false;
        if($Document instanceof bank_SpendingDocuments) {
            $valior = $rec->{$Document->valiorFld} ?? dt::today();
            if (!empty($rec->earlyPaymentUntil) && !empty($rec->earlyPaymentPercent) && $valior <= $rec->earlyPaymentUntil) {
                $haveCashDiscount = true;
            }
        }

        // list - цели ф-ри (сконто), one - само една ф-ра, table - свободно разписване на суми
        $mode = $haveCashDiscount ? 'list' : ($onlyOneAllowedInvoice ? 'one' : 'table');

        return (object)array(
            'paymentData'           => $paymentData,
            'currencyCode'          => currency_Currencies::getCodeById($paymentData->currencyId),
            'isReverse'             => $isReverse,
            'isReverseWithTransfer' => ($isTransfer && $isReverse),
            'haveCashDiscount'      => $haveCashDiscount,
            'mode'                  => $mode,
            'options'               => is_array($options) ? $options : array(),

            // При разпределяне на ф-ри към складови документи да се игнорират разпределените плащания
            'subtractPayedByNow'    => !(($Document instanceof store_DocumentMaster) || ($Document instanceof deals_ServiceMaster)),
        );
    }


    /**
     * Ключ за заключване на разпределенията по ф-ри в една сделка (с обединените в нея)
     *
     * @param stdClass $rec - платежният документ
     * @return string
     */
    public static function getSelectionLockKey($rec)
    {
        $threads = deals_Helper::getCombinedThreads($rec->threadId);
        $threadId = countR($threads) ? reset($threads) : $rec->threadId;

        return "deals_InvoicesToDocuments_{$threadId}";
    }


    /**
     * Попълва празните суми в таблицата с очакваното плащане по ф-та, без да надвишават остатъка
     *
     * @param stdClass $rec
     * @param stdClass $ctx - от getSelectionContext()
     * @param array $iData  - таблицата като колони containerId и amount
     * @return array [таблицата с попълнени суми, записите на ф-те по контейнер]
     */
    public static function fillTableSelection($rec, $ctx, $iData)
    {
        $fullRecs = array();
        $paymentData = $ctx->paymentData;
        $paymentCurrencyCode = $ctx->currencyCode;

        $iData['amount'] = is_array($iData['amount'] ?? null) ? $iData['amount'] : array();
        $iData['containerId'] = is_array($iData['containerId'] ?? null) ? $iData['containerId'] : array();
        $Double = core_Type::getByName('double');
        foreach ($iData['amount'] as &$a){
            $amount = $Double->fromVerbal($a);
            $a = isset($amount) ? round($amount, 2) : null;
        }
        unset($a);

        $remainingAmount = $paymentData->amount - array_sum(array_filter($iData['amount']));
        foreach ($iData['containerId'] as $k => $v){
            $iDoc = doc_Containers::getDocument($iData['containerId'][$k]);
            $iRec = $iDoc->fetch();
            $fullRecs[$iRec->containerId] = $iRec;
            if(empty($iData['amount'][$k])){
                $expectedAmountToPayData = static::getExpectedAmountToPay($iRec->containerId, $rec->containerId, $ctx->subtractPayedByNow);
                $totalValue = $iRec->dealValue - $iRec->discountAmount + $iRec->vatAmount;
                if($iRec->type == 'dc_note' && $totalValue < 0){
                    $expectedAmountToPayData->amount = -1 * $expectedAmountToPayData->amount;
                }
                $vAmount = currency_CurrencyRates::convertAmount($expectedAmountToPayData->amount, null, $expectedAmountToPayData->currencyCode, $paymentCurrencyCode);
                $vAmount = round($vAmount, 2);
                if($ctx->isReverse){
                    $vAmount = abs($vAmount);
                }
                if($iDoc->getInstance()->fetch("#type = 'dc_note' AND #originId = {$iData['containerId'][$k]} AND #dealValue < 0 AND #state = 'active'")){
                    $defAmount = $vAmount;
                } else {
                    $defAmount = min($remainingAmount, $vAmount);
                }

                $iData['amount'][$k] = $defAmount;
                $remainingAmount -= $defAmount;
            }
        }

        return array($iData, $fullRecs);
    }


    /**
     * Разпределение към една конкретна ф-ра: очакваното по нея, но не повече от сумата на документа
     *
     * @param core_Mvc $Document
     * @param stdClass $rec
     * @param stdClass $ctx - от getSelectionContext()
     * @param int $fromContainerId
     * @return array [разпределението, записите на ф-те по контейнер, предупреждение или null]
     */
    public static function prepareOneSelection($Document, $rec, $ctx, $fromContainerId)
    {
        $warning = null;
        $paymentData = $ctx->paymentData;
        $iRec = doc_Containers::getDocument($fromContainerId)->fetch();
        $fullRecs = array($iRec->containerId => $iRec);
        $expectedAmountToPayData = static::getExpectedAmountToPay($iRec->containerId, $rec->containerId, $ctx->subtractPayedByNow);

        $vAmount = currency_CurrencyRates::convertAmount($expectedAmountToPayData->amount, null, $expectedAmountToPayData->currencyCode, $ctx->currencyCode);
        $vAmount = round($vAmount, 2);
        $defAmount = min($paymentData->amount, $vAmount);
        if($defAmount){
            $invArr = array('0' => (object)array('containerId' => $fromContainerId, 'amount' => $defAmount));
        } else {
            if($Document instanceof deals_PaymentDocument){
                $warning = 'По фактурата не се очаква плащане';
            }
            $invArr = array('0' => (object)array('containerId' => $fromContainerId, 'amount' => $paymentData->amount));
        }

        return array($invArr, $fullRecs, $warning);
    }


    /**
     * Разпределение към цели ф-ри (при сконто) - всяка за своята стойност
     *
     * @param array $containerIds - контейнери на ф-те
     * @return array [разпределението, записите на ф-те по контейнер]
     */
    public static function prepareListSelection($containerIds)
    {
        $invArr = $fullRecs = array();
        foreach (array_keys($containerIds) as $iContainerId){
            $iDoc = doc_Containers::getDocument($iContainerId);
            $iRec = $iDoc->fetch();
            $iTotalValue = round($iRec->dealValue - $iRec->discountAmount + $iRec->vatAmount, 2);
            $invArr[] = (object)array('containerId' => $iContainerId, 'amount' => $iTotalValue);
            $fullRecs[$iRec->containerId] = $iRec;
        }

        return array($invArr, $fullRecs);
    }


    /**
     * Проверява разпределението както формата за избор на ф-ри
     *
     * @param core_Mvc $Document
     * @param stdClass $rec
     * @param stdClass $ctx      - от getSelectionContext()
     * @param array    $invArr   - обекти с containerId и amount
     * @param array    $fullRecs - записите на ф-те по контейнер
     * @return array - обекти с type (error/warning), fields (полета на формата) и msg
     */
    public static function checkSelection($Document, $rec, $ctx, $invArr, $fullRecs)
    {
        $res = array();
        $paymentData = $ctx->paymentData;
        $paymentCurrencyCode = $currencyCode = $ctx->currencyCode;
        $isReverse = $ctx->isReverse;
        $haveCashDiscount = $ctx->haveCashDiscount;
        $subtractPayedByNow = $ctx->subtractPayedByNow;
        $allFields = 'invoices,fromContainerId,invoicesList';

        // Ако е към платежен документ проверка дали са допустими
        if($Document instanceof deals_PaymentDocument){
            $amountWarnings = $amountErrors = array();
            foreach ($invArr as $iRec){
                $expectedAmountToPayData = static::getExpectedAmountToPay($iRec->containerId, $rec->containerId, $subtractPayedByNow);
                $eAmount = round(currency_CurrencyRates::convertAmount($expectedAmountToPayData->amount, null, $expectedAmountToPayData->currencyCode, $paymentCurrencyCode), 2);

                $invRec = $fullRecs[$iRec->containerId];
                $Invoice = doc_Containers::getDocument($iRec->containerId);
                $iInst = $Invoice->getInstance();

                if(abs(round($iRec->amount, 2)) > abs(round($eAmount, 2))){
                    if ($iInst->fields['number']) {
                        $number = $iInst->getVerbal($invRec, 'number');
                    } else {
                        $number = "#" . $Invoice->getHandle();
                    }
                    $expectedAmountVerbal = core_Type::getByName('double(smartRound)')->toVerbal($eAmount);
                    $amountWarnings[] = "Над очакваното плащане по|* {$number} - {$expectedAmountVerbal} {$paymentCurrencyCode}";
                }

                // Знакът се проверява независимо от надвишаването - иначе отрицателна сума минава само с предупреждение
                if(round($iRec->amount, 2) < 0){
                    if($invRec->type == 'invoice' || $invRec->dealValue > 0){
                        if(!$ctx->isReverseWithTransfer){
                            $amountErrors[] = "Към фактура или дебитно разпределената сума, трябва да е положителна";
                        }
                    }
                }
            }

            if(countR($amountWarnings)){
                $res[] = (object)array('type' => 'warning', 'fields' => $allFields, 'msg' => implode("<li>", $amountWarnings));
            }
            if(countR($amountErrors)){
                $res[] = (object)array('type' => 'error', 'fields' => $allFields, 'msg' => implode("<li>", $amountErrors));
            }

            $summed = arr::sumValuesArray($invArr, 'amount') ?? 0;
            if(isset($paymentData->amount)){
                if($summed < 0){
                    $res[] = (object)array('type' => 'error', 'fields' => $allFields, 'msg' => "Общата сума не може да е отрицателна");
                } elseif(round($summed, 2) > round($paymentData->amount, 2)){
                    $summedVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($summed);
                    $tVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($paymentData->amount);
                    $res[] = (object)array('type' => 'error', 'fields' => $allFields, 'msg' => "Общата сума не трябва да е повече от:|* <b>{$tVerbal}</b> (|Избрани са фактури за|*: {$summedVerbal}) {$currencyCode}");
                }
            }

            // Ако не е обратен документа и е към РБД със сконто
            if(!$isReverse && $haveCashDiscount){
                $countInvoices = $countCreditNotesWithQuantity = $amountInvoice = $amountCreditNotes = 0;

                foreach ($fullRecs as $iRec1){
                    $totalValue = round($iRec1->dealValue - $iRec1->discountAmount + $iRec1->vatAmount, 2);
                    if($totalValue > 0){
                        $countInvoices++;
                        $amountInvoice += $totalValue;
                    } else {
                        if(empty($iRec1->changeAmount)){

                            // Ако има кредитни се изисква да са само за сума (КИ за к-во не допускаме)
                            $dQuery = purchase_InvoiceDetails::getQuery();
                            $dQuery->where("#invoiceId = {$iRec1->id}");
                            $details = $dQuery->fetchAll();
                            purchase_InvoiceDetails::modifyDcDetails($details, $iRec1, cls::get('purchase_InvoiceDetails'));
                            foreach ($details as $detail) {
                                if (!empty($detail->changedQuantity)) {
                                    $countCreditNotesWithQuantity++;
                                }
                            }
                        }
                        $amountCreditNotes += abs($totalValue);
                    }
                }

                if($countCreditNotesWithQuantity) {
                    $res[] = (object)array('type' => 'error', 'fields' => 'invoicesList', 'msg' => "Не може да са избрани КИ за количество");
                }

                if($countInvoices > 1) {
                    $res[] = (object)array('type' => 'error', 'fields' => 'invoicesList', 'msg' => 'РБД със сконто, може да е само към една ф-ра и кредитни за сума към нея');
                }

                if($amountInvoice && round($amountInvoice, 2) != round($paymentData->amount, 2)){
                    $amountVerbal = core_Type::getByName('double(smartRound)')->toVerbal($paymentData->amount);
                    $res[] = (object)array('type' => 'error', 'fields' => 'invoicesList', 'msg' => "Сумата на ф-та трябва да е точно толкова колкото е сумата на РБД-то без сконто|*: <b>{$amountVerbal} {$currencyCode}</b>");
                }

                if($amountCreditNotes && round($amountCreditNotes, 2) != round($paymentData->cashDiscount, 2)){
                    $amountCashDiscount = core_Type::getByName('double(smartRound)')->toVerbal($paymentData->cashDiscount);
                    $res[] = (object)array('type' => 'error', 'fields' => 'invoicesList', 'msg' => "Сумата на КИ трябва да е точно толкова колкото е сумата на сконтото на РБД-то|*: <b>{$amountCashDiscount} {$currencyCode}</b>");
                }
            }
        } elseif($Document instanceof store_ShipmentOrders){

            // Предупреждение ако обвързаната по ЕН-та (текущото + вече съществуващите от други ЕН/Приемания/Проформи) сума надвишава стойността на ф-та
            $amountWarnings = array();
            foreach ($invArr as $iRec){
                if(empty($iRec->amount)) continue;

                $expectedAmountToPayData = static::getExpectedAmountToPay($iRec->containerId, $rec->containerId, true, true);
                $eAmount = round(currency_CurrencyRates::convertAmount($expectedAmountToPayData->amount, null, $expectedAmountToPayData->currencyCode, $paymentCurrencyCode), 2);

                if(round($iRec->amount, 2) > $eAmount){
                    $invRec = $fullRecs[$iRec->containerId];
                    $Invoice = doc_Containers::getDocument($iRec->containerId);
                    $iInst = $Invoice->getInstance();
                    $number = $iInst->getField('number', false) ? $iInst->getVerbal($invRec, 'number') : ('#' . $Invoice->getHandle());
                    $expectedAmountVerbal = core_Type::getByName('double(smartRound)')->toVerbal($eAmount);
                    $amountWarnings[] = "Над наличната сума на фактурата по|* {$number} - {$expectedAmountVerbal} {$paymentCurrencyCode}";
                }
            }

            if(countR($amountWarnings)){
                $res[] = (object)array('type' => 'warning', 'fields' => $allFields, 'msg' => implode("<li>", $amountWarnings));
            }
        }

        return $res;
    }


    /**
     * Записва разпределението по ф-ри и обновява документа и сделката, както формата за избор
     *
     * @param core_Mvc $Document
     * @param stdClass $rec
     * @param array    $invArr - обекти с containerId и amount; празен масив маха разпределението
     * @return stdClass - changed (има ли промяна) и count (записани ф-ри)
     */
    public static function saveSelection($Document, $rec, $invArr)
    {
        $me = cls::get(get_called_class());
        $newArr = array();
        foreach ($invArr as $obj){
            if(!$obj->amount) continue;
            $newArr[] = (object)array('documentContainerId' => $rec->containerId, 'containerId' => $obj->containerId, 'amount' => $obj->amount);
        }

        $logMsg = false;
        $exRecs = static::getInvoiceArr($rec->containerId);
        $syncedArr = arr::syncArrays($newArr, $exRecs, 'containerId,amount', 'containerId,amount');

        if(countR($syncedArr['insert'])){
            $me->saveArray($syncedArr['insert']);
            $logMsg = true;
        }

        if(countR($syncedArr['update'])){
            $me->saveArray($syncedArr['update'], 'id,containerId,amount');
            $logMsg = true;
        }

        if(countR($syncedArr['delete'])){
            $inStr = implode(',', $syncedArr['delete']);
            $me->delete("#id IN ({$inStr})");
            $logMsg = true;
        }
        plg_Search::forceUpdateKeywords($Document, $rec);

        if ($Document instanceof deals_PaymentDocument) {
            deals_Helper::updateAutoPaymentTypeInThread($rec->threadId);
            doc_DocumentCache::cacheInvalidation($rec->containerId);
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);

            // Преизчисляване на посрочията по сделката след преразпределянето
            if($firstDoc->isInstanceOf('deals_DealMaster')){
                $firstRec = $firstDoc->fetch();
                $firstRec->paymentState = $firstDoc->getInstance()->getPaymentState($firstRec);
                $firstDoc->getInstance()->save_($firstRec, 'paymentState,overdueAmountPerDays,overdueAmount');
            }
        }

        $count = countR($invArr);
        if($count == 1){
            if($rec->fromContainerId != $invArr[0]->containerId){
                $rec->fromContainerId = $invArr[0]->containerId;
                $Document->save($rec, 'fromContainerId');
                $logMsg = true;
            }
        } elseif(isset($rec->fromContainerId)) {
            $rec->fromContainerId = null;
            $Document->save($rec, 'fromContainerId');
            $logMsg = true;
        } else {
            $Document->touchRec($rec);
        }

        if($logMsg){
            $Document->logWrite("Отнасяне към ф-ри", $rec->id);
        }

        // Реконтиране на документа при промяна на ф-те ако се иска
        $reconto = false;
        if(!empty($Document->recontoWhenChange)){
            if($rec->state == 'active' && $Document->hasPlugin('acc_plg_Contable')){
                if(acc_Journal::reconto($rec->containerId)){
                    $Document->logWrite('Ре-контиране на документа', $rec->id);
                    $reconto = true;
                }
            }
        }

        return (object)array('changed' => $logMsg, 'count' => countR($newArr), 'reconto' => $reconto);
    }


    /**
     * Колко е платено досега по ф-та
     *
     * @param $invoiceContainerId
     * @param $ignoreDocumentContainerId
     * @param boolean $subtractPayedByNow
     * @return array
     */
    public static function getExpectedAmountToPay($invoiceContainerId, $ignoreDocumentContainerId, $subtractPayedByNow = true, $onlyExceptClasses = false)
    {
        $Document = doc_Containers::getDocument($invoiceContainerId);
        $iRec = doc_Containers::getDocument($invoiceContainerId)->fetch();

        if($Document->isInstanceOf('deals_InvoiceMaster')){
            $dRate = !empty($iRec->displayRate) ? $iRec->displayRate : $iRec->rate;
            $vAmount = abs(($iRec->dealValue + $iRec->vatAmount - $iRec->discountAmount) / $dRate);
        } else {
            $vAmount = abs($iRec->amountDelivered / $iRec->currencyRate);
        }

        $exceptClassIds = array(store_ShipmentOrders::getClassId(), store_Receipts::getClassId(), sales_Proformas::getClassId());
        $query = static::getQuery();
        $query->EXT('docClass', 'doc_Containers', 'externalKey=documentContainerId');
        $query->where("#containerId = {$invoiceContainerId} AND #documentContainerId != {$ignoreDocumentContainerId}" );
        if($onlyExceptClasses){

            // Само вече обвързаните суми от ЕН/Приемания/Проформи към тази ф-ра (те не се броят за "платено" по-долу за другите класове)
            $query->in('docClass', $exceptClassIds);
        } else {
            $query->notIn('docClass', $exceptClassIds);
        }

        $toPay = $vAmount;
        if($subtractPayedByNow){
            $paidByNow = 0;
            while($rec = $query->fetch()){
                $Document = doc_Containers::getDocument($rec->documentContainerId);
                $state = $Document->fetchField('state');
                if($state != 'active') continue;

                $pData = $Document->getPaymentData();
                if(!empty($pData->amountDeal)){
                    $rate = $pData->amount / $pData->amountDeal;
                    $amountPaid  = $rec->amount / $rate;
                } else {
                    $amountPaid = $rec->amount;
                }

                $paidByNow += $amountPaid;
            }

            $toPay -= $paidByNow;
        }

        if($toPay < 0){
            $toPay = 0;
        }

        $arr = (object)array('amount' => $toPay, 'currencyCode' => $iRec->currencyId, 'rate' => $iRec->displayRate ?? null);

        return $arr;
    }


    /**
     * Валидира таблицата с плащания
     *
     * @param mixed $tableData
     * @param core_Type $Type
     * @return void|string|array
     */
    public static function validateTable($tableData, $Type)
    {
        $tableData = (array) $tableData;
        if (empty($tableData)) {

            return;
        }

        $res = $containers = $error = $errorFields = array();

        $containerIds = is_array($tableData['containerId'] ?? null) ? $tableData['containerId'] : array();
        $amounts = is_array($tableData['amount'] ?? null) ? $tableData['amount'] : array();

        foreach ($containerIds as $key => $containerId) {
            if (array_key_exists($containerId, $containers)) {
                $error[] = 'Повтарящ се документ';
                $errorFields['containerId'][$key] = 'Повтарящ се документ';
            } else {
                $containers[$containerId] = $containerId;
            }
        }

        foreach ($amounts as $key => $amount) {
            if (!empty($amount) && empty($containerIds[$key])) {
                $error[] = 'Зададенa сума без посочен документ';
                $errorFields['amount'][$key] = 'Зададенa сума без посочен документ';
            }

            if(!empty($amount)){
                $Double = core_Type::getByName('double(decimals=2)');
                $q2 = $Double->fromVerbal($amount);
                if (empty($q2)) {
                    $error[] = 'Невалидна сума';
                    $errorFields['amount'][$key] = 'Невалидна сума';
                }
            }
        }

        if (countR($error)) {
            $error = implode('|*<li>|', $error);
            $res['error'] = $error;
        }

        if (countR($errorFields)) {
            $res['errorFields'] = $errorFields;
        }

        return $res;
    }


    /**
     * Подготовка на детайла
     *
     * @param stdClass $data
     */
    public function prepareInvoicesToDocuments($data)
    {
        // В линията фактурите се показват в реда, а краткият шаблон няма място за детайла
        if (Mode::is('renderHtmlInLine')) {
            $data->Order = -1;

            return;
        }

        $masterRec = $data->masterData->rec;
        $paymentData = $data->masterMvc->getPaymentData($data->masterId);
        $data->recs = static::getInvoiceArr($masterRec->containerId);
        $currencyCode = currency_Currencies::getCodeById($paymentData->currencyId);
        $unallocated = $paymentData->amount;

        // Ако има сконто приспада се
        $valior = $masterRec->{$data->masterMvc->valiorFld} ?? dt::today();

        // Ако има сконто то няма да участва като неразпределени
        if(($data->masterMvc ?? null) instanceof bank_SpendingDocuments){
            if(!empty($masterRec->earlyPaymentUntil) && !empty($masterRec->earlyPaymentPercent) && $valior <= $masterRec->earlyPaymentUntil){
                $unallocated *= 1 - $masterRec->earlyPaymentPercent;
            }
        }

        // Бутон за редакция
        if ($data->masterMvc->haveRightFor('selectinvoice', $data->masterData->rec) && !Mode::isReadOnly()) {
            $onlyOneInvoice = $data->masterMvc->canBeOnlyToOneInvoice($data->masterData->rec);
            $title = ($onlyOneInvoice) ? "Избор на фактура към която е документа" : "Избор на фактури към които е документа";
            $data->btn = ht::createLink('', array('deals_InvoicesToDocuments', 'selectinvoice', 'documentId' => $data->masterId, 'documentClassId' => $data->masterMvc->getClassId(), 'ret_url' => true), false, "ef_icon=img/16/edit.png,title={$title}");
        }

        $data->rows = array();
        $count = 0;

        if(countR($data->recs)){
            if(isset($data->masterData->rec->tplLang)){
                core_Lg::push($data->masterData->rec->tplLang);
            }

            foreach ($data->recs as $key => $rec) {
                $count++;
                $unallocated -= $rec->amount;
                $data->rows[$key] = $this->recToVerbal($rec);
                $data->rows[$key]->documentName = tr("Към {$data->rows[$key]->documentName}");

                if(!Mode::isReadOnly()){
                    $data->rows[$key]->currencyId = $currencyCode;
                } else {
                    unset($data->rows[$key]->amount);
                }

                if($count == 1 && isset($data->btn)){
                    $data->rows[$key]->invoiceBtn = $data->btn;
                }
                if(!doc_plg_HidePrices::canSeePriceFields($data->masterMvc, $data->masterData->rec)) {
                    $data->rows[$key]->amount = doc_plg_HidePrices::getBuriedElement();
                }
            }

            if(round($unallocated, 2) > 0 && !Mode::isReadOnly()){
                $data->rows['u'] = (object)array('documentName' => tr('Неразпределени'), 'currencyId' => $currencyCode, 'amount' => core_Type::getByName('double(decimals=2)')->toVerbal($unallocated));
                if(!doc_plg_HidePrices::canSeePriceFields($data->masterMvc, $data->masterData->rec)) {
                    $data->rows['u']->amount = doc_plg_HidePrices::getBuriedElement();
                }
            }

            if(isset($data->masterData->rec->tplLang)){
                core_Lg::pop();
            }
        }

        return $data;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $Document = doc_Containers::getDocument($rec->containerId);

        $row->documentName = mb_strtolower($Document->singleTitle);
        if($Document->isInstanceOf('sales_Proformas')){
            $row->documentName = tr('проформа фактура');
        } elseif($Document->isInstanceOf('deals_InvoiceMaster')){
            $row->documentName = tr('фактура/известие');
        }

        if ($Document->getInstance()->getField('number', false)) {
            $row->containerId = $Document->getInstance()->getVerbal($Document->fetch(), 'number');
            if (!Mode::isReadOnly()) {
                $row->containerId = ht::createLink($row->containerId, $Document->getSingleurlArray());
            }
        } else {
            $row->containerId = $Document->getLink(0);
        }

        $row->documentContainerId = doc_Containers::getDocument($rec->documentContainerId)->getLink(0);
        $row->amount = ht::styleNumber($row->amount, $rec->amount);
    }


    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderInvoicesToDocuments($data)
    {
        $tpl = new core_ET("");
        $block = getTplFromFile('deals/tpl/InvoicesToDocuments.shtml');
        if(!(($data->masterMvc ?? null) instanceof deals_Document)){
            $block->replace('no-border', 'INV_CLASS');
        }

        if (countR($data->rows)) {
            foreach ($data->rows as $row) {
                $clone = clone $block;
                $clone->placeObject($row);
                $tpl->append($clone);
            }
        } elseif(isset($data->btn)) {
            $block->replace(tr('Към фактура/известие'), 'documentName');
            $block->append("<div class='border-field'></div>", 'amount');
            $block->append($data->btn, 'amount');
            $tpl->append($block);
        }

        $tpl->removeBlocksAndPlaces();

        return $tpl;
    }


    /**
     * Връща масив с ф-те към документа
     *
     * @param int $documentContainerId
     * @return array
     */
    public static function getInvoiceArr($documentContainerId, $skipClasses = array(), $verbal = false)
    {
        $query = static::getQuery();
        $query->where("#documentContainerId = {$documentContainerId}");
        $query->orderBy('id', 'ASC');

        $skipClasses = arr::make($skipClasses, true);
        if(countR($skipClasses)){
            $classIds = array();
            array_walk($skipClasses, function ($a) use (&$classIds) {$classIds[] = cls::get($a)->getClassId();});
            $classIds = implode(',', $classIds);
            $query->EXT('docClass', 'doc_Containers', 'externalKey=containerId');
            $query->where("#docClass NOT IN ({$classIds})");
        }

        $res = array();
        while ($rec = $query->fetch()){
            if($verbal){
                $Document = doc_Containers::getDocument($rec->containerId);

                if ($Document->getInstance()->getField('number', false)) {
                    $res[$rec->id] = $Document->getInstance()->getVerbal($Document->fetch(), 'number');
                    if (!Mode::isReadOnly() && !Mode::is('text', 'plain')) {
                        $res[$rec->id] = ht::createLink($res[$rec->id], $Document->getSingleurlArray())->getContent();
                    }
                } else {
                    $res[$rec->id] = $Document->getLink(0);
                }
            } else{
                $res[$rec->id] = $rec;
            }
        }

        return $res;
    }


    /**
     * Връща разрешените методи за плащане
     *
     * @param int
     *
     * @return array $res
     */
    private static function getInvoicesTableArr($documentContainerId)
    {
        $res = array();
        $exRecs = static::getInvoiceArr($documentContainerId, "sales_Proformas");

        foreach ($exRecs as $rec) {
            $res['containerId'][] = $rec->containerId;
            $res['amount'][] = $rec->amount;
        }

        return $res;
    }


    /**
     * Мограционна функция за същесъвуващите документи
     * @todo да се махне след рилийза
     *
     * @param mixed $mvc
     */
    public static function migrateContainerIds($mvc)
    {
        $mvc = cls::get($mvc);
        $me = cls::get(get_called_class());
        $me->setupMvc();

        $res = array();
        $query = $mvc->getQuery();
        $query->where("#fromContainerId IS NOT NULL");
        $recs = $query->fetchAll();

        $count = countR($recs);

        if(empty($count)) return;

        core_App::setTimeLimit($count * 0.4, false, 200);

        foreach($recs as $rec){
            static::delete("#documentContainerId = {$rec->containerId}");
            $paymentData = $mvc->getPaymentData($rec->id);
            $rec = (object)array('documentContainerId' => $rec->containerId, 'containerId' => $rec->fromContainerId, 'amount' => $paymentData->amount);
            $res[] = $rec;
        }

        $me->saveArray($res);
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('documentId', 'varchar', 'caption=Документ, silent');
        $data->listFilter->showFields = 'documentId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input(null, 'silent');
        $data->listFilter->input();
        $data->query->orderBy('id', 'DESC');

        if ($rec = $data->listFilter->rec) {
            if (!empty($rec->documentId)) {
                if(type_Int::isInt($rec->documentId)){
                    $containerId = $rec->documentId;
                } elseif($document = doc_Containers::getDocumentByHandle($rec->documentId)){
                    $containerId = $document->fetchField('containerId');
                }

                if (isset($containerId)) {
                    $data->query->where("#documentContainerId = {$containerId} OR #containerId = {$containerId}");
                }
            }
        }
    }


    /**
     * Кои са свързаните документи към фактурата
     *
     * @param int $invoiceContainerId - ид на контейнера на фактурата
     * @param mixed $documentClasses - изброени класове
     * @return array
     */
    public static function getDocumentsToInvoices($invoiceContainerId, $documentClasses = array())
    {
        $query = static::getQuery();
        $query->EXT('docClass', 'doc_Containers', 'externalName=docClass,externalKey=documentContainerId');
        $query->EXT('state', 'doc_Containers', 'externalName=state,externalKey=documentContainerId');
        $query->where("#containerId = {$invoiceContainerId} AND #state != 'rejected'");
        if(isset($documentClasses)){
            $inClassArr = array();
            $documentClasses = arr::make($documentClasses, true);
            foreach ($documentClasses as $classId){
                $inClassArr[] = cls::get($classId)->getClassId();
            }
            $query->in('docClass', $inClassArr);
        }

        return arr::extractValuesFromArray($query->fetchAll(), 'documentContainerId');
    }
}
