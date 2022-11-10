<?php

/**
 * Детайл за фактури към документи
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
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
        $paymentData = $Document->getPaymentData($rec);

        $form = cls::get('core_Form');
        $form->title = "Избор на фактури към|* " . cls::get($Document)->getFormTitleLink($documentId);

        $tVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($paymentData->amount);
        $currencyCode = currency_Currencies::getCodeById($paymentData->currencyId);
        $form->info = tr("За разпределяне:") . " <b>{$tVerbal} {$currencyCode}</b>";

        $onlyOneAllowedInvoice = $Document->canBeOnlyToOneInvoice($rec);

        // Задаване на наличните фактури за избор
        $invoices = $Document->getReasonContainerOptions($rec);

        if($onlyOneAllowedInvoice){
            $form->FLD('fromContainerId', "int", "caption=Избор");
            $form->setOptions('fromContainerId', array('' => '') + $invoices);
            $form->setDefault('fromContainerId', $rec->fromContainerId);
        } else {
            $form->FLD('invoices', "table(columns=containerId|amount,captions=Документ|Сума ({$currencyCode}),validate=deals_InvoicesToDocuments::validateTable)", "caption=Избор");
            $form->setFieldTypeParams('invoices', array('amount_sgt' => array('' => '', "{$paymentData->amount}" => $paymentData->amount), 'containerId_opt' => array('' => '') + $invoices, 'totalAmount' => $paymentData->amount, 'currencyId' => $paymentData->currencyId));
            $curInvoiceArr = static::getInvoicesTableArr($rec->containerId);
            $form->setDefault('invoices', $curInvoiceArr);
        }

        $form->input();
        if($form->isSubmitted()){
            $fRec = $form->rec;

            $invArr = array();
            $paymentCurrencyCode = currency_Currencies::getCodeById($paymentData->currencyId);

            if(!empty($fRec->invoices)){
                $iData =  @json_decode($fRec->invoices, true);
                foreach ($iData['amount'] as &$a){
                    $a = core_Type::getByName('double')->fromVerbal($a);
                }

                foreach ($iData['containerId'] as $k => $v){
                    if(empty($iData['amount'][$k])){
                        $iRec = doc_Containers::getDocument($iData['containerId'][$k])->fetch();

                        $expectedAmountToPayData = static::getExpectedAmountToPay($iRec->containerId, $rec->containerId);
                        if($iRec->type == 'dc_note' && $iRec->totalValue < 0){
                            $expectedAmountToPayData->amount = -1 * $expectedAmountToPayData->amount;
                        }

                        $vAmount = currency_CurrencyRates::convertAmount($expectedAmountToPayData->amount, null, $expectedAmountToPayData->currencyCode, $paymentCurrencyCode);
                        $vAmount = round($vAmount, 2);

                        $defAmount = min($paymentData->amount, $vAmount);
                        $iData['amount'][$k] = $defAmount;
                    }
                }

                $fRec->invoices = @json_encode($iData);
                $invArr = type_Table::toArray($form->rec->invoices);
            } elseif(!empty($fRec->fromContainerId)){
                $iRec = doc_Containers::getDocument($fRec->fromContainerId)->fetch();

                $expectedAmountToPayData = static::getExpectedAmountToPay($iRec->containerId, $rec->containerId);
                $vAmount = currency_CurrencyRates::convertAmount($expectedAmountToPayData->amount, null, $expectedAmountToPayData->currencyCode, $paymentCurrencyCode);
                $vAmount = round($vAmount, 2);
                $defAmount = min($paymentData->amount, $vAmount);
                if($defAmount){
                    $invArr = array('0' => (object)array('containerId' => $fRec->fromContainerId, 'amount' => $defAmount));
                } else {
                    if($Document instanceof deals_PaymentDocument){
                        $form->setWarning('fromContainerId', 'По фактурата не се очаква плащане');
                    }
                    $invArr = array('0' => (object)array('containerId' => $fRec->fromContainerId, 'amount' => $paymentData->amount));
                }
            }

            if($Document instanceof deals_PaymentDocument){
                $amountWarnings = $amountErrors = array();
                foreach ($invArr as $iRec){
                    $expectedAmountToPayData = static::getExpectedAmountToPay($iRec->containerId, $rec->containerId);
                    $eAmount = round(currency_CurrencyRates::convertAmount($expectedAmountToPayData->amount, null, $expectedAmountToPayData->currencyCode, $paymentCurrencyCode), 2);
                    $Invoice = doc_Containers::getDocument($iRec->containerId);
                    $iInst = $Invoice->getInstance();

                    if(abs($iRec->amount) > abs($eAmount)){
                        if ($iInst->fields['number']) {
                            $number = $iInst->getVerbal($Invoice->fetch(), 'number');
                        } else {
                            $number = "#" . $Invoice->getHandle();
                        }

                        $expectedAmountVerbal = core_Type::getByName('double(smartRound)')->toVerbal($eAmount);
                        $amountWarnings[] = "Над очакваното плащане по|* {$number} - {$expectedAmountVerbal} {$paymentCurrencyCode}";
                    } elseif($iRec->amount < 0){
                        $invRec = $Invoice->fetch('type,dealValue');
                        if($invRec->type == 'invoice' || $invRec->dealValue > 0){
                            $amountErrors[] = "Към фактура или дебитно разпределената сума, трябва да е положителна";
                        }
                    }
                }

                if(countR($amountWarnings)){
                    $form->setWarning('invoices,fromContainerId', implode("<li>", $amountWarnings));
                }
                if(countR($amountErrors)){
                    $form->setError('invoices,fromContainerId', implode("<li>", $amountErrors));
                }

                $summed = arr::sumValuesArray($invArr, 'amount');

                if(isset($paymentData->amount)){
                    if($summed < 0){
                        $form->setError('invoices,fromContainerId', "Общата сума не може да е отрицателна");
                    } elseif($summed > $paymentData->amount){
                        $tVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($paymentData->amount);
                        $currencyCode = currency_Currencies::getCodeById($form->getFieldTypeParam('invoices', 'currencyId'));
                        $form->setError('invoices,fromContainerId', "Общата сума не трябва да е повече от:|* <b>{$tVerbal}</b> {$currencyCode}");
                    }
                }
            }

            if(!$form->gotErrors()){

                $newArr = array();
                foreach ($invArr as $obj){
                    $newArr[] = (object)array('documentContainerId' => $rec->containerId, 'containerId' => $obj->containerId, 'amount' => $obj->amount);
                }

                $logMsg = false;
                $exRecs = static::getInvoiceArr($rec->containerId);
                $syncedArr = arr::syncArrays($newArr, $exRecs, 'containerId,amount', 'containerId,amount');

                if(countR($syncedArr['insert'])){
                    $this->saveArray($syncedArr['insert']);
                    $logMsg = true;
                }

                if(countR($syncedArr['update'])){
                    $this->saveArray($syncedArr['update'], 'id,containerId,amount');
                    $logMsg = true;
                }

                if(countR($syncedArr['delete'])){
                    $inStr = implode(',', $syncedArr['delete']);
                    $this->delete("#id IN ({$inStr})");
                    $logMsg = true;
                }
                plg_Search::forceUpdateKeywords($Document, $rec);

                if ($Document instanceof deals_PaymentDocument) {
                    deals_Helper::updateAutoPaymentTypeInThread($rec->threadId);
                    doc_DocumentCache::cacheInvalidation($rec->containerId);
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
                    $Document->logWrite("Отнасяне към документ", $rec->id);
                }

                followRetUrl(null, 'Промяната е записана успешно');
            }
        }

        // Добавяне на тулбар
        $form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Импорт');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        // Рендиране на опаковката
        $tpl = $Document->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);

        return $tpl;
    }


    /**
     * Колко е платено досега по ф-та
     *
     * @param $invoiceContainerId
     * @param $ignoreDocumentContainerId
     * @return float|int
     */
    public static function getExpectedAmountToPay($invoiceContainerId, $ignoreDocumentContainerId)
    {
        $Document = doc_Containers::getDocument($invoiceContainerId);
        $iRec = doc_Containers::getDocument($invoiceContainerId)->fetch();

        if($Document->isInstanceOf('deals_InvoiceMaster')){
            $dRate = $iRec->displayRate ? $iRec->displayRate : $iRec->rate;
            $vAmount = abs(($iRec->dealValue + $iRec->vatAmount - $iRec->discountAmount) / $dRate);
        } else {
            $vAmount = abs($iRec->amountDelivered / $iRec->currencyRate);
        }

        $exceptClassIds = array(store_ShipmentOrders::getClassId(), store_Receipts::getClassId(), sales_Proformas::getClassId());
        $query = static::getQuery();
        $query->EXT('docClass', 'doc_Containers', 'externalKey=documentContainerId');
        $query->where("#containerId = {$invoiceContainerId} AND #documentContainerId != {$ignoreDocumentContainerId}" );
        $query->notIn('docClass', $exceptClassIds);

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

        $toPay = $vAmount - $paidByNow;
        if($toPay < 0){
            $toPay = 0;
        }

        $arr = (object)array('amount' => $toPay, 'currencyCode' => $iRec->currencyId, 'rate' => $iRec->displayRate);

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

        foreach ($tableData['containerId'] as $key => $containerId) {
            if (array_key_exists($containerId, $containers)) {
                $error[] = 'Повтарящ се документ';
                $errorFields['containerId'][$key] = 'Повтарящ се документ';
            } else {
                $containers[$containerId] = $containerId;
            }
        }

        $totalAmount = 0;
        foreach ($tableData['amount'] as $key => $amount) {
            if (!empty($amount) && empty($tableData['containerId'][$key])) {
                $error[] = 'Зададенa сума без посочен документ';
                $errorFields['amount'][$key] = 'Зададенa сума без посочен документ';
            }

            if(!empty($amount)){
                $Double = core_Type::getByName('double');
                $q2 = $Double->fromVerbal($amount);
                if (!$q2) {
                    $error[] = 'Невалидна сума';
                    $errorFields['amount'][$key] = 'Невалидна сума';
                }

                if(!isset($errorFields['amount'][$key])){
                    $totalAmount += $amount;
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
        $masterRec = $data->masterData->rec;
        $paymentData = $data->masterMvc->getPaymentData($data->masterId);
        $data->recs = static::getInvoiceArr($masterRec->containerId);
        $currencyCode = currency_Currencies::getCodeById($paymentData->currencyId);
        $unallocated = $paymentData->amount;

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
                $data->rows[$key]->documentName = tr("Kъм {$data->rows[$key]->documentName}");

                if(!Mode::isReadOnly()){
                    $data->rows[$key]->currencyId = $currencyCode;
                } else {
                    unset($data->rows[$key]->amount);
                }

                if($count == 1 && isset($data->btn)){
                    $data->rows[$key]->invoiceBtn = $data->btn;
                }
                if(!doc_plg_HidePrices::canSeePriceFields($this, $rec)) {
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
            $invoiceType = $Document->fetchField('type');
            $row->documentName = ($invoiceType == 'invoice') ? tr('фактура') : (($invoiceType == 'dc_note' && $rec->amount <= 0) ? 'к-но известие' : 'д-но известие');
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

        if (countR($data->rows)) {
            foreach ($data->rows as $row) {
                $clone = clone $block;
                $clone->placeObject($row);
                $tpl->append($clone);
            }
        } elseif(isset($data->btn)) {
            $block->replace(tr('Към фактура'), 'documentName');
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
}
