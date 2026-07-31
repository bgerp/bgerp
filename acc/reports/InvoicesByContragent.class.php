<?php


/**
 * Справка „Фактури по контрагент“ — изходящи (продажби) или входящи (покупки).
 *
 * Основен поток на изпълнение:
 * 1. addFields()              — дефинира филтрите на формата
 * 2. on_AfterPrepareEditForm() — начални стойности и suggestions за контрагенти
 * 3. prepareRecs()            — извлича фактури, плащания и остатъци към дата
 * 4. detailRecToVerbal()      — форматира редовете за екран (вкл. EUR/BGN)
 * 5. on_AfterRenderSingle()   — блок с обобщение на филтрите и тоталите
 *
 * Режим unpaid=all  — всички фактури в периода с платена сума.
 * Режим unpaid=unpaid — само фактури с остатък извън прага sill.
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Счетоводство » Фактури по контрагент
 */
class acc_reports_InvoicesByContragent extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,acc,sales,purchase';


    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'contragent';


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'contragent,checkDate,crmGroup,typeOfInvoice,unpaid';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        // --- Филтри по контрагент ---
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,placeholder=Всички,single=none,after=title');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Контрагенти->Група контрагенти,placeholder=Всички,after=contragent,single=none');

        // --- Тип документи и режим на плащане ---
        $fieldset->FLD('typeOfInvoice', 'enum(out=Изходящи,in=Входящи)', 'caption=Фактури,after=crmGroup,maxRadio=2,mandatory,single=none');
        $fieldset->FLD('unpaid', 'enum(all=Всички,unpaid=Неплатени)', 'caption=Плащане,after=typeOfInvoice,removeAndRefreshForm,single=none,mandatory,silent');

        // --- Период и допълнителни филтри ---
        $fieldset->FLD('fromDate', 'date', 'caption=От дата,after=unpaid, placeholder=от началото,silent');
        $fieldset->FLD('checkDate', 'date', 'caption=До дата,after=fromDate, placeholder=текуща,silent');

        $fieldset->FLD('paymentType', 'enum( ,cash=В брой,bank=По банков път,intercept=С прихващане,card=С карта,factoring=Факторинг,postal=Пощенски паричен превод)', 'caption=Начин на плащане, placeholder=Всички,after=checkDate,input=none,single=none');

        $fieldset->FLD('sill', 'double', 'caption=Да не се показват фактури по приключени сделки при разлика под->Неплатено/Надплатено,unit= €.,input=hidden,after=paymentType,placeholder=0.00,silent,single=none');

        $fieldset->FLD('seeProformаs', 'set(yes = )', 'caption=Покажи проформа фактурите,after=sill,input,single=none');

        // --- Обобщаващи суми (попълват се в prepareRecs, показват се в on_AfterRenderSingle) ---
        $fieldset->FNC('totalInvoiceValueAll', 'double', 'input=none,single=none');
        $fieldset->FNC('totalInvoicePayoutAll', 'double', 'input=none,single=none');
        $fieldset->FNC('totalInvoiceNotPaydAll', 'double', 'input=none,single=none');
        $fieldset->FNC('totalInvoiceOverPaidAll', 'double', 'input=none,single=none');
        $fieldset->FNC('totalInvoiceOverDueAll', 'double', 'input=none,single=none');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        // Начални стойности и видимост на полетата според избрания режим (всички / неплатени).
        $form->setDefault('seeProformаs', null);

        $form->setDefault('unpaid', 'all');

        $form->input('unpaid', 'silent');

        // При „неплатени“ се показват праг sill и начин на плащане; проформите се скриват.
        if (($rec->unpaid ?? 'all') == 'unpaid') {
            $form->setField('sill', 'input');
            $form->setField('seeProformаs', 'input=none');
            $form->setField('paymentType', 'input');
        }


        // При „всички“ checkDate е днес; fromDate и paymentType не се ползват.
        if (($rec->unpaid ?? 'all') == 'all') {
            $form->setDefault('fromDate', null);
            unset($rec->paymentType);
            $checkDate = dt::today(false);
            $form->setDefault('checkDate', $checkDate);
        }


        $form->setDefault('typeOfInvoice', 'out');


        // Подготвят се предложенията за контрагенти от продажбите и покупките.
        $salesQuery = sales_Sales::getQuery();

        $salesQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');

        $salesQuery->groupBy('folderId');

        $salesQuery->show('folderId, contragentId, folderTitle');

        $purchQuery = purchase_Purchases::getQuery();

        $purchQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');

        $purchQuery->groupBy('folderId');

        $purchQuery->show('folderId, contragentId, folderTitle');

        $purSuggestions = $suggestions = array();

        while ($purContragent = $purchQuery->fetch()) {
            if (!is_null($purContragent->contragentId)) {
                $purSuggestions[$purContragent->folderId] = $purContragent->folderTitle ?? null;
            }
        }

        while ($contragent = $salesQuery->fetch()) {
            if (!is_null($contragent->contragentId)) {
                $suggestions[$contragent->folderId] = $contragent->folderTitle ?? null;
            }
        }

        // Обединяват се предложенията от покупки със списъка от продажби.
        foreach ($purSuggestions as $k => $v) {
            if (!in_array($k, array_keys($suggestions))) {
                $suggestions[$k] = $v;
            }
        }

        asort($suggestions);

        $form->setSuggestions('contragent', $suggestions);
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {

            // Валидира се избраният период.
            if (isset($form->rec->fromDate, $form->rec->checkDate) && ($form->rec->fromDate > $form->rec->checkDate)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }
        }
    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     *
     * Ключови работни масиви (продажби):
     *   $sRecsAll — всички фактури при режим „всички“
     *   $sRecs     — филтрирани редове при режим „неплатени“
     *   $totalInvoiceContragent(All) — агрегати по контрагент
     *
     * Алгоритъм: (1) query на фактури → (2) обхождане по нишки с getInvoicePayments
     * → (3) агрегиране → (4) сортиране и тотали.
     */
    protected function prepareRecs($rec, &$data = null)
    {
        // Стари записи на справката може да нямат полетата, добавени по-късно
        $rec->unpaid = $rec->unpaid ?? 'all';
        $rec->typeOfInvoice = $rec->typeOfInvoice ?? 'out';
        $rec->checkDate = $rec->checkDate ?? dt::today(false);
        $rec->fromDate = $rec->fromDate ?? null;
        $rec->contragent = $rec->contragent ?? null;
        $rec->crmGroup = $rec->crmGroup ?? null;
        $rec->paymentType = $rec->paymentType ?? null;
        $rec->sill = $rec->sill ?? 0;
        $rec->seeProformаs = $rec->seeProformаs ?? null;

        $sRecs = $sRecsAll = $pRecs = $pRecsAll = array();
        $invAdjustmentArr = $firstDocumentArr = array();
        $totalInvoiceContragent = $totalInvoiceContragentAll = array();

        // Дата „към“, към която се смятат плащанията и остатъците.
        if ($rec->unpaid == 'unpaid' && !$rec->checkDate) {
            $checkDate = dt::now();
        } else {
            $checkDate = $rec->checkDate;
        }

        $recs = array();

        // ===================================================================
        // ИЗХОДЯЩИ ФАКТУРИ (продажби)
        // ===================================================================
        if ($rec->typeOfInvoice == 'out') {

            // $sRecs / $sRecsAll — редове за таблицата; $totalInvoiceContragent* — суми по контрагент
            $sRecs = array();
            $sRecsAll = array();
            $invAdjustmentArr = array();
            $isRec = array();
            $totalInvoiceContragent = $totalInvoiceContragentAll = array();

            $docsArr = array('sales_Invoices');

            //Когато се търсят неплатените фактури, и има избор за проформи, се гледат и проформите
            //към които има изрично насочени плащания
            if ($rec->seeProformаs == 'yes' && $rec->unpaid == 'all') {

                array_push($docsArr, 'sales_Proformas');

                $proformsWithPayDocArr = self::getProformsWithPaymant($rec);
                $proformWithPayDocArr = array_keys($proformsWithPayDocArr); //Масив с всички проформи, към които има насочени плащания

            }

            foreach ($docsArr as $InvDoc) {

                // --- Query: активни фактури/проформи в периода, с филтър по контрагент ---
                $invQuery = $InvDoc::getQuery();

                $invQuery->where("#number IS NOT NULL");

                $invQuery->in('state', 'rejected, draft', true);

                //При избрани НЕПЛАТЕНИ махаме дебитните и кредитните известия
                if ($rec->unpaid == 'unpaid' && $InvDoc == 'sales_Invoices') {
                    $invQuery->where("#type = 'invoice'");
                }

                // Ако е посочена начална дата на период
                if ($rec->fromDate) {
                    $invQuery->where(array(
                        "#date >= '[#1#]'",
                        $rec->fromDate
                    ));
                }

                //Крайна дата / 'към дата'
                $invQuery->where(array(
                    "#date <= '[#1#]'",
                    $checkDate
                ));

                //Филтър за КОНТРАГЕНТ и ГРУПИ КОНТРАГЕНТИ
                if ($rec->contragent || $rec->crmGroup) {
                    $contragentsArr = array();
                    $contragentsId = array();

                    if (!$rec->crmGroup && $rec->contragent) {
                        $contragentsArr = keylist::toArray($rec->contragent);

                        $invQuery->in('folderId', $contragentsArr);
                    }

                    if ($rec->crmGroup && !$rec->contragent) {
                        $foldersInGroups = self:: getFoldersInGroups($rec);

                        if (empty($foldersInGroups)) {
                            return $recs;
                        }

                        $invQuery->in('folderId', $foldersInGroups);
                    }

                    if ($rec->crmGroup && $rec->contragent) {
                        $contragentsArr = keylist::toArray($rec->contragent);

                        $invQuery->in('folderId', $contragentsArr);

                        $foldersInGroups = self::getFoldersInGroups($rec);

                        if (empty($foldersInGroups)) {
                            return $recs;
                        }

                        $invQuery->in('folderId', $foldersInGroups);
                    }
                }

                // --- Подготовка: обединени договори и „бързи“ продажби (платени на място) ---
                $salesQuery = sales_Sales::getQuery();

                $salesQuery->where("#closedDocuments != '' OR #contoActions IS NOT NULL");

                //Масив със затварящи документи по обединени договори и масив с бързи продажби
                $salesUN = array();
                $fastSales = array();

                while ($sale = $salesQuery->fetch()) {
                    if ($sale->closedDocuments != '') {

                        //Масив със затворени договори чрез обединяване
                        foreach ((keylist::toArray($sale->closedDocuments)) as $v) {
                            $salesUN[$v] = ($v);
                        }
                    }

                    //Масив с бързи продажби
                    if (strpos((string)$sale->contoActions, 'pay') !== false) {
                        //$fastSales[$sale->id] = ($sale->amountPaid - $sale->amountVat);

                        // Превалутиране за ЕЗ
                        $sale->amountPaid = deals_Helper::getSmartBaseCurrency($sale->amountPaid, $sale->valior, $rec->checkDate);
                        $sale->amountVat = deals_Helper::getSmartBaseCurrency($sale->amountVat, $sale->valior, $rec->checkDate);

                        $fastSales[$sale->id] = $sale->amountPaid - $sale->amountVat;

                    }
                }

                //Изваждаме нишките за проверка  са избрани НЕПЛАТЕНИ
                //Ако са избрани ВСИЧКИ записваме масив $allInvoices със всички фактури
                $threadsId = array();

                // Синхронизира таймлимита с броя записи //
                $maxTimeLimit = $invQuery->count() * 10;
                $maxTimeLimit = max(array($maxTimeLimit, 300));
                if ($maxTimeLimit > 300) {
                    core_App::setTimeLimit($maxTimeLimit);
                }

                while ($salesInvoice = $invQuery->fetch()) {

                    // Проформа без насочено плащане не влиза в справката.
                    if (($rec->seeProformаs == 'yes') && ($InvDoc == 'sales_Proformas') && (!in_array($salesInvoice->id, $proformWithPayDocArr))) continue;

                    $firstDocument = doc_Threads::getFirstDocument($salesInvoice->threadId);
                    if (!$firstDocument) {
                        continue;
                    }

                    $firstDocumentArr[$salesInvoice->threadId] = $firstDocument->that;


                    $fastMarker = in_array($firstDocumentArr[$salesInvoice->threadId], array_keys($fastSales)) ? 0 : 1;

                    $className = $firstDocument->className;

                    // При „неплатени“: пропуска фактури от приключени (не обединени) сделки.
                    if ($rec->unpaid == 'unpaid') {
                        $unitedCheck = false;

                        if (is_array($salesUN)) {
                            $unitedCheck = in_array($className::fetchField($firstDocument->that), $salesUN);
                        }


                        //Ако продажбата е приключена с друг договор фактурите от тази сделка остават в справката, ако е приключена
                        //по друг начин сделката се прескача.
                        if (($className::fetchField($firstDocument->that, 'state') == 'closed') &&
                            ($className::fetchField($firstDocument->that, 'closedOn') <= $checkDate) &&
                            !$unitedCheck) {
                            continue;
                        }
                    }

                    //Масив от нишки в които има фактури
                    $threadsId[$salesInvoice->threadId] = $salesInvoice->threadId;


                    // Режим „всички“: записва всяка фактура с платена сума към checkDate.
                    if ($rec->unpaid == 'all') {

                        // масив от фактури в тази нишка //
                        $invoicePayments = deals_Helper::getInvoicePayments($salesInvoice->threadId, $checkDate, false, false);

                        $paydocs = $invoicePayments[$salesInvoice->containerId] ?? (object)array(
                            'payout' => 0,
                            'date' => null,
                            'used' => array(),
                        );

                        // За проформите плащанията се взимат от изрично насочените платежни документи.
                        if ($InvDoc == 'sales_Proformas') {
                            $paydocs = new stdClass();
                            // Подготвя се структура, съвместима с данните от getInvoicePayments().
                            $paydocs->payout = 0;
                            $paydocs->date = null;
                            $paydocs->used = array();

                            if (isset($proformsWithPayDocArr[$salesInvoice->id])) {
                                // Сумират се всички платежни документи, насочени към текущата проформа.
                                foreach ($proformsWithPayDocArr[$salesInvoice->id]->documents as $pDocId) {
                                    $payDocClassId = $proformsWithPayDocArr[$salesInvoice->id]->docClassId;
                                    $payDocClass = cls::get($payDocClassId);

                                    $pDocRec = $payDocClass->fetch($pDocId);

                                    if (!$pDocRec || $pDocRec->state != 'active') {
                                        continue;
                                    }

                                    $paydocs->payout += $pDocRec->amount;
                                    $paydocs->date = $pDocRec->valior;

                                    $paydocs->used[] = (object)array(
                                        'containerId' => $pDocRec->containerId,
                                    );
                                }
                            }
                        }

                        // Разделя ключовете на фактури и проформи, за да не се припокриват еднакви id-та.
                        $subKey = ($InvDoc == 'sales_Proformas') ? 'P' : 'S';
                        $key = $salesInvoice->id . $subKey;

                        // Превалутиране за ЕЗ
                        $salesInvoice->dealValue = deals_Helper::getSmartBaseCurrency($salesInvoice->dealValue, $salesInvoice->date, $rec->checkDate);
                        $salesInvoice->discountAmount = deals_Helper::getSmartBaseCurrency($salesInvoice->discountAmount, $salesInvoice->date, $rec->checkDate);
                        $salesInvoice->vatAmount = deals_Helper::getSmartBaseCurrency($salesInvoice->vatAmount, $salesInvoice->date, $rec->checkDate);


                        $invoiceValue = ($salesInvoice->dealValue - $salesInvoice->discountAmount) + $salesInvoice->vatAmount;

                        $Invoice = doc_Containers::getDocument($salesInvoice->containerId);

                        // масива с фактурите за показване
                        if (!array_key_exists($key, $sRecsAll)) {
                            $sRecsAll[$key] = (object)array(

                                'threadId' => $salesInvoice->threadId,
                                'className' => $Invoice->className,
                                'invoiceId' => $salesInvoice->id,
                                'invoiceNo' => $salesInvoice->number,
                                'invoiceDate' => $salesInvoice->date,
                                'dueDate' => $salesInvoice->dueDate,
                                'invoiceContainerId' => $salesInvoice->containerId,
                                'currencyId' => $salesInvoice->currencyId,
                                'rate' => $salesInvoice->rate,
                                'invoiceValue' => $invoiceValue,
                                'invoiceVAT' => $salesInvoice->vatAmount,
                                'contragent' => $salesInvoice->contragentName,
                                'type' => $salesInvoice->type,
                                'payDocuments' => $paydocs->used,
                                'fastMarker' => $fastMarker,
                                'invoicePayout' => deals_Helper::getSmartBaseCurrency($paydocs->payout, $paydocs->date, $rec->checkDate),
                                'invoiceCurrentSumm' => 0,
                                // 'dcPay' => $dcPay
                            );
                        }

                        // Масив с данни за сумите от фактурите  обединени по контрагенти


                        if ($InvDoc == 'sales_Invoices') {  //Да не влизат сумите на проформите в общата стойност по контрагент

                            if (!array_key_exists($salesInvoice->contragentName, $totalInvoiceContragentAll)) {

                                $totalInvoiceContragentAll[$salesInvoice->contragentName] = (object)array(
                                    'totalInvoiceValue' => $invoiceValue,                                        //общо стойност на фактурите за контрагента
                                    'totalInvoiceVAT' => $salesInvoice->vatAmount,                               //общо стойност на ДДС по фактурите за контрагента
                                    'totalInvoicePayout' => 0,
                                    'totalInvoiceNotPaid' => 0,
                                    'totalInvoiceOverPaid' => 0,
                                    'totalInvoiceOverDue' => 0,

                                );
                            } else {
                                $obj = &$totalInvoiceContragentAll[$salesInvoice->contragentName];

                                $obj->totalInvoiceValue += $invoiceValue;
                                $obj->totalInvoiceVAT += $salesInvoice->vatAmount;
                            }
                        }
                    }
                }
            }

            // --- Втори проход по нишки: остатъци (неплатено / надплатено / просрочено) ---
            if (is_array($threadsId)) {

                $checkedSInvoices = array();

                foreach ($threadsId as $thread) {

                    $salesInvoiceNotPaid = 0;


                    // масив от фактури в тази нишка //
                    $invoicePayments = (deals_Helper::getInvoicePayments($thread, $checkDate, false, false));

                    if (is_array($invoicePayments)) {

                        // фактура от нишката и масив от платежни документи по тази фактура//
                        foreach ($invoicePayments as $inv => $paydocs) {
                            $salesInvoiceNotPaid = 0;
                            $salesInvoiceOverPaid = 0;
                            $salesInvoiceOverDue = 0;

                            //Проверка дали отчетена вече фактура не се повтаря
                            if (in_array($inv, $checkedSInvoices)) continue;

                            // Превалутиране за ЕЗ
                            $paydocs->amount = deals_Helper::getSmartBaseCurrency($paydocs->amount, $paydocs->date, $rec->checkDate);
                            $paydocs->payout = deals_Helper::getSmartBaseCurrency($paydocs->payout, $paydocs->date, $rec->checkDate);


                            // Разлика стойност − платено; при бърза продажба се счита за нула.
                            $invDiff = $paydocs->amount - $paydocs->payout;

                            // TODO: премахни след корекция на deals_Helper::getInvoicePayments за бързи продажби
                            $invDiff = in_array($firstDocumentArr[$thread] ?? null, array_keys($fastSales)) ? 0 : $invDiff;

                            $fastMarker = in_array($firstDocumentArr[$thread] ?? null, array_keys($fastSales)) ? 0 : 1;

                            //Тази фактура
                            $Invoice = doc_Containers::getDocument($inv);

                            if ($Invoice->className != 'sales_Invoices') {
                                continue;
                            }

                            //Данни по тази фактура
                            $iRec = $Invoice->fetch(
                                'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,
                                     currencyId,date,dueDate,contragentName,paymentType,autoPaymentType'
                            );

                            // Ако са избрани само неплатените фактури пропускаме тези с отклонение под зададения минимум
                            if (($rec->unpaid ?? null) == 'unpaid') {
                                if (($invDiff >= (-1) * $rec->sill) &&
                                    ($invDiff <= $rec->sill)) {
                                    continue;
                                }

                                //Ако е избран филтър по начин на плащане
                                $paymentType = ($iRec->paymentType) ?: $iRec->autoPaymentType;
                                if ($rec->paymentType && $rec->paymentType != $paymentType) continue;

                            }

                            //Ако датата на фактурата е по голяма от избраната "към дата" не влиза в масива
                            if ($checkDate < $iRec->date) {
                                continue;
                            }

                            if (($invDiff) > 0) {
                                $salesInvoiceNotPaid = $invDiff;
                            }

                            if ($invDiff < 0) {
                                $salesInvoiceOverPaid = $invDiff;
                            }

                            if ($iRec->dueDate && $invDiff > 0 &&
                                $iRec->dueDate < $checkDate) {
                                $salesInvoiceOverDue = $invDiff;
                            }

                            // Масив с данни за сумите от фактурите  обединени по контрагенти

                            if (!array_key_exists($iRec->id, $sRecs)) {
                                if (!array_key_exists($iRec->contragentName, $totalInvoiceContragent)) {
                                    $totalInvoiceContragent[$iRec->contragentName] = (object)array(
                                        'totalInvoiceValue' => $paydocs->amount * $iRec->rate,                            //общо стойност на фактурите за контрагента
                                        'totalInvoicePayout' => $paydocs->payout * $iRec->rate,                           //плащания по фактурите за контрагента
                                        'totalInvoiceNotPaid' => $salesInvoiceNotPaid * $iRec->rate,                      //стойност на НЕДОплатените суми по фактурите за контрагента
                                        'totalInvoiceOverPaid' => $salesInvoiceOverPaid * $iRec->rate,                    //стойност на НАДплатените суми по фактурите за контрагента
                                        'totalInvoiceOverDue' => $salesInvoiceOverDue * $iRec->rate,                      //стойност за плащане по просрочените фактури за контрагента
                                    );
                                } else {
                                    $obj = &$totalInvoiceContragent[$iRec->contragentName];

                                    $obj->totalInvoiceValue += $paydocs->amount * $iRec->rate;
                                    $obj->totalInvoicePayout += $paydocs->payout * $iRec->rate;
                                    $obj->totalInvoiceNotPaid += $salesInvoiceNotPaid * $iRec->rate;
                                    $obj->totalInvoiceOverPaid += $salesInvoiceOverPaid * $iRec->rate;
                                    $obj->totalInvoiceOverDue += $salesInvoiceOverDue * $iRec->rate;
                                }
                            }

                            //Ако са избрани само НЕплатени и има посочена начална дата,
                            // то тези преди тази дата не ги записва в масива
                            if ($rec->unpaid == 'unpaid' && !is_null($rec->fromDate)) {

                                if ($rec->fromDate > $iRec->date) continue;
                            }

                            // масива с фактурите за показване
                            if (!array_key_exists($iRec->id, $sRecs)) {
                                $sRecs[$iRec->id] = (object)array(
                                    'threadId' => $thread,
                                    'className' => $Invoice->className,
                                    'invoiceId' => $iRec->id,
                                    'invoiceNo' => $iRec->number,
                                    'invoiceDate' => $iRec->date,
                                    'dueDate' => $iRec->dueDate,
                                    'invoiceContainerId' => $iRec->containerId,
                                    'currencyId' => $iRec->currencyId,
                                    'rate' => $iRec->rate,
                                    'invoiceValue' => $paydocs->amount * $iRec->rate,
                                    'invoiceVAT' => deals_Helper::getSmartBaseCurrency($iRec->vatAmount, $iRec->date, $rec->checkDate),
                                    'invoicePayout' => $paydocs->payout,
                                    'type' => $iRec->type,
                                    'fastMarker' => $fastMarker,
                                    'invoiceCurrentSumm' => $invDiff,
                                    'payDocuments' => $paydocs->used,
                                    'contragent' => $iRec->contragentName
                                );
                            }
                            $checkedSInvoices[$inv] = $inv;
                        }
                    }
                }
            }
        }

        // ===================================================================
        // ВХОДЯЩИ ФАКТУРИ (покупки) — същата логика като при продажбите
        // ===================================================================
        if ($rec->typeOfInvoice == 'in') {
            // $pRecs / $pRecsAll — редове; $totalInvoiceContragent* — агрегати по контрагент
            $pRecs = $pRecsAll = $invAdjustmentArr = array();
            $isRec = array();
            $totalInvoiceContragent = $totalInvoiceContragentAll = array();

            $pQuery = purchase_Invoices::getQuery();

            $pQuery->where("#number IS NOT NULL");

            $pQuery->in('state', 'rejected, draft', true);

            //При избрани НЕПЛАТЕНИ махаме дебитните и кредитните известия
            if ($rec->unpaid == 'unpaid') {
                $pQuery->where("#type = 'invoice'");
            }

            // Ако е посочена начална дата на период
            if ($rec->fromDate) {
                $pQuery->where(array(
                    "#date >= '[#1#]'",
                    $rec->fromDate
                ));
            }

            $pQuery->where(array(
                "#date <= '[#1#]'",
                $checkDate
            ));

            //Филтър за КОНТРАГЕНТ и ГРУПИ КОНТРАГЕНТИ
            if ($rec->contragent || $rec->crmGroup) {
                $contragentsArr = array();
                $contragentsId = array();
                if (!$rec->crmGroup && $rec->contragent) {
                    $contragentsArr = keylist::toArray($rec->contragent);

                    $pQuery->in('folderId', $contragentsArr);
                }

                if ($rec->crmGroup && !$rec->contragent) {
                    $foldersInGroups = self::getFoldersInGroups($rec);

                    $pQuery->in('folderId', $foldersInGroups);
                }

                if ($rec->crmGroup && $rec->contragent) {
                    $contragentsArr = keylist::toArray($rec->contragent);

                    $pQuery->in('folderId', $contragentsArr);

                    $foldersInGroups = self::getFoldersInGroups($rec);

                    $pQuery->in('folderId', $foldersInGroups);
                }
            }

            //Обединени покупки
            $purchasesQuery = purchase_Purchases::getQuery();

            $purchasesQuery->where("#closedDocuments != '' OR #contoActions IS NOT NULL");

            //Масив с затварящи документи по обединени покупки  и масив с бързи покупки
            $purchasesUN = array();
            $fastPur = array();

            while ($purchase = $purchasesQuery->fetch()) {
                foreach ((keylist::toArray($purchase->closedDocuments)) as $v) {
                    $purchasesUN[$v] = ($v);
                }


                //Масив с бързи покупки
                if (strpos((string)$purchase->contoActions, 'pay') !== false) {
                    $fastPur[$purchase->id] = ($purchase->amountPaid - $purchase->amountVat);
                }
            }

            // Синхронизира таймлимита с броя записи //
            $maxTimeLimit = $pQuery->count() * 5;
            $maxTimeLimit = max(array($maxTimeLimit, 300));
            if ($maxTimeLimit > 300) {
                core_App::setTimeLimit($maxTimeLimit);
            }

            $pThreadsId = array();

            // Фактури ПОКУПКИ
            while ($purchaseInvoices = $pQuery->fetch()) {

                $firstDocument = doc_Threads::getFirstDocument($purchaseInvoices->threadId);
                if (!$firstDocument) {
                    continue;
                }

                $firstDocumentArr[$purchaseInvoices->threadId] = $firstDocument->that;

                //НАЛОЖИТЕЛНА КОРЕКЦИЯ ЗА БЪРЗИ ПОКУПКИ
                //КОГАТО СЕ ОПРАВИ ФУНКЦИЯТА ЗА РАЗПРЕДЕЛЕНИЕ НА ПЛАЩАНИЯТА
                //ТОВА ДА СЕ МАХНЕ
                $fastMarker = in_array($purchaseInvoices->id, array_keys($fastPur)) ? 0 : 1;

                // Когато е избрано ВСИЧКИ в полето плащане
                if ($rec->unpaid == 'all') {

                    // Превалутиране за ЕЗ
                    $purchaseInvoices->dealValue = deals_Helper::getSmartBaseCurrency($purchaseInvoices->dealValue, $purchaseInvoices->date, $rec->checkDate);
                    $purchaseInvoices->discountAmount = deals_Helper::getSmartBaseCurrency($purchaseInvoices->discountAmount, $purchaseInvoices->date, $rec->checkDate);
                    $purchaseInvoices->vatAmount = deals_Helper::getSmartBaseCurrency($purchaseInvoices->vatAmount, $purchaseInvoices->date, $rec->checkDate);

                    $invoiceValue = (($purchaseInvoices->dealValue - $purchaseInvoices->discountAmount) + $purchaseInvoices->vatAmount);
                    $Invoice = doc_Containers::getDocument($purchaseInvoices->containerId);

                    // масив от фактури в тази нишка //
                    $invoicePayments = (deals_Helper::getInvoicePayments($purchaseInvoices->threadId, $checkDate, false, false));

                    $paydocs = $invoicePayments[$purchaseInvoices->containerId] ?? (object)array(
                        'payout' => 0,
                        'date' => null,
                        'used' => array(),
                    );

                    // масива с фактурите за показване
                    if (!array_key_exists($purchaseInvoices->id, $pRecsAll)) {
                        $pRecsAll[$purchaseInvoices->id] = (object)array(

                            'threadId' => $purchaseInvoices->threadId,
                            'className' => $Invoice->className,
                            'invoiceId' => $purchaseInvoices->id,
                            'invoiceNo' => $purchaseInvoices->number,
                            'invoiceDate' => $purchaseInvoices->date,
                            'dueDate' => $purchaseInvoices->dueDate,
                            'invoiceContainerId' => $purchaseInvoices->containerId,
                            'currencyId' => $purchaseInvoices->currencyId,
                            'rate' => $purchaseInvoices->rate,
                            'invoiceValue' => $invoiceValue,
                            'invoiceVAT' => $purchaseInvoices->vatAmount,
                            'contragent' => $purchaseInvoices->contragentName,
                            'type' => $purchaseInvoices->type,
                            'payDocuments' => $paydocs->used,
                            'invoicePayout' => deals_Helper::getSmartBaseCurrency($paydocs->payout, $paydocs->date, $rec->checkDate),
                            'invoiceCurrentSumm' => 0,
                            'fastMarker' => $fastMarker,
                            //'dcPay' => $dcPay
                        );
                    }

                    // Масив с данни за сумите от фактурите  обединени по контрагенти
                    if (!array_key_exists($purchaseInvoices->contragentName, $totalInvoiceContragentAll)) {
                        $totalInvoiceContragentAll[$purchaseInvoices->contragentName] = (object)array(
                            'totalInvoiceValue' => $invoiceValue, //общо стойност на фактурите за контрагента
                            'totalInvoiceVAT' => $purchaseInvoices->vatAmount,//общо стойност на ДДС по фактурите за контрагента
                            'totalInvoicePayout' => 0,
                            'totalInvoiceNotPaid' => 0,
                            'totalInvoiceOverPaid' => 0,
                            'totalInvoiceOverDue' => 0,

                        );
                    } else {
                        $obj = &$totalInvoiceContragentAll[$purchaseInvoices->contragentName];

                        $obj->totalInvoiceValue += $invoiceValue;
                        $obj->totalInvoiceVAT += $purchaseInvoices->vatAmount;
                    }
                    continue;
                }


                $className = $firstDocument->className;

                // Ако са избрани само неплатените фактури
                if ($rec->unpaid == 'unpaid') {
                    $purUnitedCheck = false;

                    if (is_array($purchasesUN)) {
                        $purUnitedCheck = in_array($className::fetchField($firstDocument->that), $purchasesUN);
                    }

                    if (($className::fetchField($firstDocument->that, 'state') == 'closed') &&
                        ($className::fetchField($firstDocument->that, 'closedOn') <= $checkDate) &&
                        !$purUnitedCheck) {
                        continue;
                    }
                }

                if (!in_array($purchaseInvoices->threadId, $pThreadsId)) {
                    $pThreadsId[$purchaseInvoices->threadId] = $purchaseInvoices->threadId;
                }

            }

            // --- Втори проход по нишки (покупки): остатъци по фактура ---
            if (is_array($pThreadsId)) {
                $checkedPInvoices = array();

                foreach ($pThreadsId as $pThread) {

                    $purchaseInvoiceNotPaid = 0;


                    // масив от фактури в тази нишка //
                    $pInvoicePayments = (deals_Helper::getInvoicePayments($pThread, $checkDate, false, false));

                    if ((is_array($pInvoicePayments))) {

                        // фактура от нишката и масив от платежни документи по тази фактура//
                        foreach ($pInvoicePayments as $pInv => $paydocs) {

                            $purchaseInvoiceNotPaid = 0;
                            $purchaseInvoiceOverDue = 0;
                            $purchaseInvoiceOverPaid = 0;

                            //Проверка дали отчетена вече фактура не се повтаря
                            if (in_array($pInv, $checkedPInvoices)) continue;

                            // Превалутиране за ЕЗ
                            $paydocs->amount = deals_Helper::getSmartBaseCurrency($paydocs->amount, $paydocs->date, $rec->checkDate);
                            $paydocs->payout = deals_Helper::getSmartBaseCurrency($paydocs->payout, $paydocs->date, $rec->checkDate);

                            //Разлика между стойност и платено по фактурата
                            $invDiff = $paydocs->amount - $paydocs->payout;

                            // Ако покупката е бърза, фактурата се счита за платена
                            //Когато се коригира функцията за разпределение на плащанията това да се премахне !!!
                            $invDiff = in_array($firstDocumentArr[$pThread] ?? null, array_keys($fastPur)) ? 0 : $invDiff;

                            $fastMarker = in_array($firstDocumentArr[$pThread] ?? null, array_keys($fastPur)) ? 0 : 1;

                            // Ако са избрани само неплатените фактури пропускаме тези с отклонение под зададения минимум
                            if ($rec->unpaid == 'unpaid') {
                                if (($invDiff >= (-1) * $rec->sill) &&
                                    ($invDiff <= $rec->sill)) {
                                    continue;
                                }
                            }

                            //Тази фактура
                            $Invoice = doc_Containers::getDocument($pInv);

                            if ($Invoice->className != 'purchase_Invoices') {
                                continue;
                            }

                            //Данни по тази фактура
                            $iRec = $Invoice->fetch(
                                'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,
                                     currencyId,date,dueDate,contragentName'

                            );

                            //Ако датата на фактурата е по голяма от избраната "към дата" не влиза в масива
                            if ($checkDate < $iRec->date) {
                                continue;
                            }

                            if (($invDiff) > 0) {
                                $purchaseInvoiceNotPaid = ($invDiff);
                            }

                            if ($invDiff < 0) {
                                $purchaseInvoiceOverPaid = $invDiff;
                            }

                            if ($iRec->dueDate && ($invDiff) > 0 &&
                                $iRec->dueDate < $checkDate) {
                                $purchaseInvoiceOverDue = ($invDiff);
                            }

                            // Масив с данни за сумите от фактурите  обединени по контрагенти
                            if (!array_key_exists($iRec->contragentName, $totalInvoiceContragent)) {
                                $totalInvoiceContragent[$iRec->contragentName] = (object)array(
                                    'totalInvoiceValue' => $paydocs->amount * $iRec->rate,                               //общо стойност на фактурите за контрагента
                                    'totalInvoicePayout' => $paydocs->payout * $iRec->rate,                              //плащания по фактурите за контрагента
                                    'totalInvoiceNotPaid' => $purchaseInvoiceNotPaid * $iRec->rate,                      //стойност за плащане по фактурите за контрагента
                                    'totalInvoiceOverPaid' => $purchaseInvoiceOverPaid * $iRec->rate,                    //стойност на НАДплатените суми по фактурите за контрагента
                                    'totalInvoiceOverDue' => $purchaseInvoiceOverDue * $iRec->rate,                      //стойност за плащане по просрочените фактури за контрагента
                                );
                            } else {
                                $obj = &$totalInvoiceContragent[$iRec->contragentName];

                                $obj->totalInvoiceValue += $paydocs->amount * $iRec->rate;
                                $obj->totalInvoicePayout += $paydocs->payout * $iRec->rate;
                                $obj->totalInvoiceNotPaid += $purchaseInvoiceNotPaid * $iRec->rate;
                                $obj->totalInvoiceOverPaid += $purchaseInvoiceOverPaid * $iRec->rate;
                                $obj->totalInvoiceOverDue += $purchaseInvoiceOverDue * $iRec->rate;
                            }

                            //Ако са избрани само НЕплатени и има посочена начална дата,
                            // то тези преди тази дата не ги записва в масива
                            if ($rec->unpaid == 'unpaid' && !is_null($rec->fromDate)) {

                                if ($rec->fromDate > $iRec->date) continue;
                            }


                            // масива с фактурите за показване
                            if (!array_key_exists($iRec->id, $pRecs)) {
                                $pRecs[$iRec->id] = (object)array(
                                    'threadId' => $pThread,
                                    'className' => $Invoice->className,
                                    'invoiceId' => $iRec->id,
                                    'invoiceNo' => $iRec->number,
                                    'invoiceDate' => $iRec->date,
                                    'dueDate' => $iRec->dueDate,
                                    'invoiceContainerId' => $iRec->containerId,
                                    'currencyId' => $iRec->currencyId,
                                    'rate' => $iRec->rate,
                                    'invoiceValue' => $paydocs->amount * $iRec->rate,
                                    'invoiceVAT' => deals_Helper::getSmartBaseCurrency($iRec->vatAmount, $iRec->date, $rec->checkDate),
                                    'invoicePayout' => $paydocs->payout,
                                    'fastMarker' => $fastMarker,
                                    'invoiceCurrentSumm' => $invDiff,
                                    'payDocuments' => $paydocs->used,
                                    'contragent' => $iRec->contragentName,
                                    'type' => $iRec->type,
                                );
                            }
                            $checkedPInvoices[$pInv] = $pInv;
                        }

                    }
                }
            }
        }

        // При режим „всички“ се ползва пълният масив ($sRecsAll / $pRecsAll).
        if ($rec->unpaid == 'all') {
            if ($rec->typeOfInvoice == 'out') {

                foreach ($sRecsAll as $v) {

                    if ($v->type == 'invoice') {

                        //   $v->invoicePayout += $invAdjustmentArr[$v->invoiceId];


                    }

                };
                $sRecs = array();
                $sRecs += $sRecsAll;
            }

            if ($rec->typeOfInvoice == 'in') {
                foreach ($pRecsAll as $v) {
                    if ($v->type == 'invoice') {

                        $v->invoicePayout -= $invAdjustmentArr[$v->invoiceId] ?? 0;


                    }

                }
                $pRecs = array();
                $pRecs += $pRecsAll;
            }
        }

        //Подрежда се по дата на фактура
        $sRecs = $sRecs ?? array();
        $pRecs = $pRecs ?? array();

        if (countR($sRecs)) {
            arr::sortObjects($sRecs, 'invoiceDate', 'asc', 'stri');
        }

        if (countR($pRecs)) {
            arr::sortObjects($pRecs, 'invoiceDate', 'asc', 'stri');
        }

        // Избира се крайният масив според типа фактури във филтъра.
        $recs = $rec->typeOfInvoice == 'out' ? $sRecs : $pRecs;

        unset(
            $rec->totalInvoiceValueAll,
            $rec->totalInvoicePayoutAll,
            $rec->totalInvoiceNotPaydAll,
            $rec->totalInvoiceOverPaidAll,
            $rec->totalInvoiceOverDueAll
        );
        $rec->totalInvoiceValueAll = 0;
        $rec->totalInvoicePayoutAll = 0;
        $rec->totalInvoiceNotPaydAll = 0;
        $rec->totalInvoiceOverPaidAll = 0;
        $rec->totalInvoiceOverDueAll = 0;

        $contragentCurrency = array();
        $flagAll = false;

        // --- Финална обработка: тотали по контрагент, проверка за смесени валути, сортиране ---
        foreach ($recs as $key => $val) {

            // Маркира контрагенти с фактури в различни валути (не се сумират в една).
            if (!array_key_exists($val->contragent, $contragentCurrency)) {
                $contragentCurrency[$val->contragent] = (object)array(
                    'currency' => $val->currencyId,
                    'flag' => false,
                    'contragent' => false,
                );
            } else {
                if (($contragentCurrency[$val->contragent]->currency != $val->currencyId) &&
                    !is_null($contragentCurrency[$val->contragent]->currency)) {
                    $contragentCurrency[$val->contragent]->flag = true;
                }
                $contragentCurrency[$val->contragent]->currency = $val->currencyId;
            }

            if ($rec->unpaid == 'all') {
                $totalInvoiceContragent = $totalInvoiceContragentAll;
            }


            // Копира агрегираните суми от $totalInvoiceContragent върху всеки ред.
            foreach ($totalInvoiceContragent as $k => $v) {
                if ($k == $val->contragent) {
                    $recs[$key]->totalInvoiceValue = $v->totalInvoiceValue;
                    $recs[$key]->totalInvoicePayout = $v->totalInvoicePayout;
                    $recs[$key]->totalInvoiceNotPayd = $v->totalInvoiceNotPaid;
                    $recs[$key]->totalInvoiceOverPaid = $v->totalInvoiceOverPaid;
                    $recs[$key]->totalInvoiceOverDue = $v->totalInvoiceOverDue;
                }
            }
        }


        //Проверка за различни валути във фактурите на избраните контрагенти(вдига flagAll ако има различни)
        $flagAll = $test = false;
        foreach ($contragentCurrency as $val) {
            if (($test != $val->currency) && $test != false) {
                $flagAll = true;
                break;
            }
            $test = $val->currency;
        }

        //Сумира стойностите на всички избрани контрагенти, ако са в една валута

        foreach ($totalInvoiceContragent as $k => $v) {
            $rec->totalInvoiceValueAll += $v->totalInvoiceValue;
            $rec->totalInvoicePayoutAll += $v->totalInvoicePayout;
            $rec->totalInvoiceNotPaydAll += $v->totalInvoiceNotPaid;
            $rec->totalInvoiceOverPaidAll += $v->totalInvoiceOverPaid;
            $rec->totalInvoiceOverDueAll += $v->totalInvoiceOverDue;
        }

        $rec->totalInvoiceValueAll = 0;


        if ($rec->unpaid == 'all') {
            $cArr = array();
            foreach ($recs as $key => $val) {

                if (!in_array($val->contragent, $cArr)) {
                    $rec->totalInvoiceValueAll += $val->totalInvoiceValue;
                    array_push($cArr, $val->contragent);

                }

            }

            if (countR($recs)) {
                arr::sortObjects($recs, 'className', 'ASC', 'stri');
            }

        }

        if (countR($recs)) {
            arr::sortObjects($recs, 'invoiceDate', 'asc', 'stri');
        }

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        // Дефинира се структурата на колоните за изглед и експорт.
        $fld = cls::get('core_FieldSet');

        if ($export === false) {
            // Колони за екран — различни според режима all/unpaid.
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,smartCenter');
            // Добавяме нова колона "Документ" преди колоната за номера
            $fld->FLD('documentType', 'varchar', 'caption=Документ,after=contragentId,tdClass=centered');
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');

            $fld->FLD('invoiceDate', 'varchar', 'caption=Дата');
            $fld->FLD('dueDate', 'varchar', 'caption=Краен срок');

            if ($rec->unpaid == 'all') {
                $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
                //if (countR($rec->data->recs) != arr::sumValuesArray($rec->data->recs, 'rate')) {
                $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
                // }

                $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate);
                $fld->FLD('invoiceValueBaseCurr', 'double(smartRound,decimals=2)', "caption=Стойност $baseCurrency");
                $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', "caption=Платено->Сума->$baseCurrency,smartCenter");
                $fld->FLD('paidDates', 'varchar', 'caption=Платено->Плащания->дата,smartCenter');
            }

            if ($rec->unpaid == 'unpaid') {
                $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate);

                $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
                //if (countR($rec->data->recs) != arr::sumValuesArray($rec->data->recs, 'rate')) {
                $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност-> Сума->валута,smartCenter');
                // }
                $fld->FLD('invoiceValueBaseCurr', 'double(decimals=2)', "caption=Стойност-> Сума-> $baseCurrency,smartCenter");
                $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', "caption=Платено->Сума->$baseCurrency,smartCenter");
                $fld->FLD('paidDates', 'varchar', 'caption=Платено->Плащания->дата,smartCenter');
                $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', "caption=Състояние->Неплатено->$baseCurrency,smartCenter");
                $fld->FLD('invoiceOverSumm', 'double(smartRound,decimals=2)', "caption=Състояние->Надплатено-> $baseCurrency,smartCenter");
            }
        } else {
            // Колони за CSV/Excel експорт.
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,smartCenter');
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
            $fld->FLD('invoiceDate', 'date', 'caption=Дата,smartCenter');
            $fld->FLD('dueDate', 'date', 'caption=Краен срок,smartCenter');
            $fld->FLD('dueDateStatus', 'varchar', 'caption=Състояние,smartCenter');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
            $fld->FLD('invoiceValueBaseCurr', 'double(decimals=2)', 'caption=Стойност-> Сума-> лв.,smartCenter');
            $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->сума');
            $fld->FLD('paidDates', 'varchar', 'caption=Платено->Плащания,smartCenter');
            if ($rec->unpaid == 'unpaid') {
                $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Състояние->Неплатено');
                $fld->FLD('invoiceOverSumm', 'double(smartRound,decimals=2)', 'caption=Състояние->Надплатено');
            }
        }

        return $fld;
    }


    /**
     * Връща платена сума
     *
     * @param stdClass $dRec
     * @param bool $verbal
     *
     * @return mixed $paidAmount
     */
    private static function getPaidAmount($dRec, $verbal = true)
    {
        $invoicePayout = $dRec->invoicePayout ?? 0;
        $rate = $dRec->rate ?? 1;

        // fastMarker=1 — обикновена продажба: сума × курс; fastMarker=0 — бърза продажба: вече в основна валута.
        if (($dRec->fastMarker ?? 1) == 1) {

            $paidAmount = $invoicePayout * $rate;

        } else {
            $paidAmount = $invoicePayout;
        }


        return $paidAmount;
    }


    /**
     * Връща дати на плащания
     *
     * @param stdClass $dRec
     * @param bool $verbal
     *
     * @return mixed $paidDates$data->rec->salesTotalNotPaid
     */
    private static function getPaidDates($dRec, $verbal = true)
    {
        $paidDatesList = '';
        $paidDates = '';

        // Обхождат се платежните документи, записани към реда на фактурата/проформата.
        if (is_array($dRec->payDocuments ?? null)) {
            foreach ($dRec->payDocuments as $onePayDoc) {
                if (!empty($onePayDoc->containerId)) {
                    $Document = doc_Containers::getDocument($onePayDoc->containerId);
                } else {
                    continue;
                }
                $payDocClass = $Document->className;

                $payDocumentRec = $payDocClass::fetch($Document->that);

                if (!$payDocumentRec || $payDocumentRec->state != 'active') {
                    continue;
                }

                //if ($dRec->type != 'invoice') continue;

                // При директно насочено плащане се проверява дали сочи към текущия документ.
                if ($payDocumentRec->fromContainerId) {
                    if ($dRec->invoiceContainerId != $payDocumentRec->fromContainerId) {
                        continue;
                    }

                    $paidDatesList .= ',' . $payDocumentRec->valior;
                } else {
                    // При разпределено плащане датата се взима от връзката между плащане и документ.
                    if (is_array(deals_InvoicesToDocuments::getInvoiceArr($payDocumentRec->containerId))) {
                        foreach (deals_InvoicesToDocuments::getInvoiceArr($payDocumentRec->containerId) as $val) {

                            $pDocumnt = doc_Containers::getDocument($val->documentContainerId);
                            $linkedPayRec = $payDocClass::fetch($pDocumnt->that);
                            if ($linkedPayRec) {
                                $paidDatesList .= ',' . $linkedPayRec->valior;
                            }
                            break;
                        }
                    }
                }
            }
        }

        // Форматира се списъкът с дати според режима на показване или експорт.
        if ($verbal === true) {
            $amountsValiors = explode(',', trim($paidDatesList, ','));

            foreach ($amountsValiors as $v) {
                $paidDate = dt::mysql2verbal($v, $mask = 'd.m.Y');

                $paidDates .= "$paidDate" . '<br>';
            }
        } else {
            $amountsValiors = explode(',', trim($paidDatesList, ','));

            foreach ($amountsValiors as $v) {
                $paidDate = dt::mysql2verbal($v, $mask = 'd.m.Y');

                $paidDates .= "$paidDate" . "\n\r";
            }

        }

        return $paidDates;
    }


    /**
     * Връща просрочие на плащане
     *
     * @param stdClass $dRec
     * @param bool $verbal
     *
     * @return mixed $dueDate
     */
    private static function getDueDate($dRec, $verbal = true, $rec = null)
    {
        // Вербален формат + warning hint, ако фактурата е просрочена и има неплатен остатък.
        if ($rec->unpaid == 'unpaid' && !$rec->checkDate) {
            $checkDate = dt::now();
        } else {
            $checkDate = $rec->checkDate;
        }
        if ($verbal === true) {
            if ($dRec->dueDate) {
                $dueDate = dt::mysql2verbal($dRec->dueDate, $mask = 'd.m.Y');

                if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $checkDate) {
                    $dueDate = "<span class='smallHintHolder'>" . ht::createHint($dueDate, 'фактурата е просрочена', 'warning') . "</span>";
                }
            } else {
                $dueDate = '';
            }
        } else {
            if ($dRec->dueDate) {
                $dueDate = $dRec->dueDate;
            } else {
                $dueDate = '';
            }
        }

        return $dueDate;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        // Типове за форматиране на дати и суми в таблицата.
        $Date = cls::get('type_Date');
        $Double = core_Type::getByName('double(decimals=2)');

        $euroZoneDate = acc_Setup::getEurozoneDate();

        $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate);

        $row = new stdClass();

        // Линк към документа и означение: Ф=фактура, ПФ=проформа, ДИ/КИ=известие.
        $invoiceNo = str_pad($dRec->invoiceNo, 10, '0', STR_PAD_LEFT);

        $row->invoiceNo = ht::createLinkRef(

            $invoiceNo,
            array(
                $dRec->className,
                'single',
                $dRec->invoiceId
            )

        );
        $type = 'Ф';
        $row->documentType = $type;

        if ($dRec->type != 'invoice') {

            if ($dRec->className == 'sales_Proformas') {
                $type = 'ПФ';
            } else {
                if ($dRec->invoiceValue < 0) {
                    $type = 'КИ';
                    $statecolor = 'credit';
                } else {
                    $type = 'ДИ';
                    $statecolor = 'debit';
                }
            }

            // Определя се знакът за дебитни/кредитни документи при експорт.
            $dcMark = $dRec->invoiceValue < 0 ? -1 : 1;

            $row->documentType = $type;
            if (isset($statecolor)) {
                $row->ROW_ATTR['class'] = "state-{$statecolor}";
            }

        }

        // --- Превалутиране и показване: период преди въвеждане на EUR (основна валута BGN) ---
        if ($rec->checkDate < $euroZoneDate) {

            //режим ВСИЧКИ  дата на справката преди ЕВРОЗОНАТА
            if ($rec->unpaid == 'all') {

                $allCurrency = ($dRec->totalInvoiceValue) ? $baseCurrency : '';

                //$div = $dRec->rate;

                $row->contragent = $dRec->contragent . ' »  ' . "<span class= 'quiet'>" . ' Общо стойност: ' . '</span>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalInvoiceValue) . ' ' . $allCurrency;
                if ($dRec->totalInvoiceOverPaid > 0.01) {
                    $row->contragent = ($row->contragent ?? '') . ' »  ' . "<span class= 'quiet'>" . 'Надплатено:' . '</span>' . $dRec->totalInvoiceOverPaid;
                }

                $row->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal(self::getPaidAmount($dRec));

                $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, true) . '</span>';

            }

            //режим НЕПЛАТЕНИ  дата на справката преди ЕВРОЗОНАТА
            if ($rec->unpaid == 'unpaid') {

                $row->contragent = $dRec->contragent . '</br>' . "<span class= 'quiet'>" . ' Общо фактури: ' . '</span>' . $Double->toVerbal($dRec->totalInvoiceValue)
                    . ' »  ' . "<span class= 'quiet'>" . ' Платено: ' . '</span>' . $Double->toVerbal($dRec->totalInvoicePayout)
                    . ' »  ' . "<span class= 'quiet'>" . 'Недоплатено:' . '</span>' . $Double->toVerbal($dRec->totalInvoiceNotPayd);

                if ($dRec->totalInvoiceOverPaid > 0.01) {
                    $row->contragent = ($row->contragent ?? '') . ' »  ' . "<span class= 'quiet'>" . 'Надплатено:' . '</span>' . $dRec->totalInvoiceOverPaid;
                }

                $row->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal(self::getPaidAmount($dRec));


                $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, true) . '</span>';
            }

            //СПОРЕД ДАТАТА НА ИЗДАВАНЕ НА ФАКТУРАТА
            //ФАКТУРА ИЗДАДЕНА ПРЕДИ ЕВРОЗОНАТА
            if ($dRec->invoiceDate < $euroZoneDate) {
                if ($dRec->currencyId == 'BGN' && $baseCurrency == 'BGN') {
                    $row->invoiceValue = $Double->toVerbal($dRec->invoiceValue);
                } elseif ($dRec->currencyId == 'EUR' && $baseCurrency == 'BGN') {
                    $row->invoiceValue = $Double->toVerbal($dRec->invoiceValue / 1.95583);
                } elseif ($dRec->currencyId != 'EUR' && $dRec->currencyId != 'BGN' && $baseCurrency == 'BGN') {
                    $row->invoiceValue = $Double->toVerbal($dRec->invoiceValue / $dRec->rate);
                }
            }

            //Стойност на фактурата в основна валута
            $row->invoiceValueBaseCurr = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->invoiceValue);

            //Остатък за плащане в основна валута
            if ($dRec->invoiceCurrentSumm > 0) {

                $row->invoiceCurrentSumm = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->invoiceCurrentSumm * $dRec->rate);
            }

            if ($dRec->invoiceCurrentSumm < 0) {
                $invoiceOverSumm = -1 * $dRec->invoiceCurrentSumm;
                $row->invoiceOverSumm = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceOverSumm * $dRec->rate);
            }


        }

        // --- Превалутиране и показване: период от въвеждане на EUR (основна валута EUR) ---
        if ($rec->checkDate >= $euroZoneDate) {

            //режим ВСИЧКИ ВЪВ ЕВРОЗОНАТА
            if ($rec->unpaid == 'all') {

                $allCurrency = ($dRec->totalInvoiceValue) ? $baseCurrency : '';

                $row->contragent = $dRec->contragent . ' »  ' . "<span class= 'quiet'>" . ' Общо стойност: ' . '</span>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalInvoiceValue) . ' ' . $allCurrency;
                if ($dRec->totalInvoiceOverPaid > 0.01) {
                    $row->contragent = ($row->contragent ?? '') . ' »  ' . "<span class= 'quiet'>" . 'Надплатено:' . '</span>' . $dRec->totalInvoiceOverPaid;
                }

                $row->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal(self::getPaidAmount($dRec));

                $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, true) . '</span>';

            }

            //режим НЕПЛАТЕНИ  дата на справката ВЪВ ЕВРОЗОНАТА
            if ($rec->unpaid == 'unpaid') {

                $row->contragent = $dRec->contragent . '</br>' . "<span class= 'quiet'>" . ' Общо фактури: ' . '</span>' . $Double->toVerbal($dRec->totalInvoiceValue)
                    . ' »  ' . "<span class= 'quiet'>" . ' Платено: ' . '</span>' . $Double->toVerbal($dRec->totalInvoicePayout)
                    . ' »  ' . "<span class= 'quiet'>" . 'Недоплатено:' . '</span>' . $Double->toVerbal($dRec->totalInvoiceNotPayd);


                if ($dRec->totalInvoiceOverPaid > 0.01) {
                    $row->contragent = ($row->contragent ?? '') . ' »  ' . "<span class= 'quiet'>" . 'Надплатено:' . '</span>' . $dRec->totalInvoiceOverPaid;
                }

                $row->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal(self::getPaidAmount($dRec));

                $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, true) . '</span>';
            }

            //Стойност на фактурата в основна валута
            $row->invoiceValueBaseCurr = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->invoiceValue);

            //Остатък за плащане в основна валута
            if ($dRec->invoiceCurrentSumm > 0) {

                $row->invoiceCurrentSumm = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->invoiceCurrentSumm * $dRec->rate);
            }

            if ($dRec->invoiceCurrentSumm < 0) {
                $invoiceOverSumm = -1 * $dRec->invoiceCurrentSumm;
                $row->invoiceOverSumm = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceOverSumm * $dRec->rate);
            }

            //Стойност на фактурата във валутата на издаване
            if ($dRec->invoiceDate >= $euroZoneDate) {
                if ($dRec->currencyId == 'EUR' && $baseCurrency == 'EUR') {
                    $row->invoiceValue = $Double->toVerbal($dRec->invoiceValue);
                } elseif ($dRec->currencyId != 'EUR' && $baseCurrency == 'EUR') {
                    $row->invoiceValue = $Double->toVerbal($dRec->invoiceValue / $dRec->rate);
                }
            }

            //Стойност на фактурата във валутата на издаване
            if ($dRec->invoiceDate < $euroZoneDate) {
                if ($dRec->currencyId == 'BGN' && $baseCurrency == 'EUR') {
                    $row->invoiceValue = $Double->toVerbal($dRec->invoiceValue * 1.95583);
                } elseif ($dRec->currencyId != 'EUR' && $dRec->currencyId != 'BGN' && $baseCurrency == 'EUR') {
                    $row->invoiceValue = $Double->toVerbal($dRec->invoiceValue / ($dRec->rate / 1.95583));
                } elseif ($dRec->currencyId == 'EUR' && $baseCurrency == 'EUR') {
                    $row->invoiceValue = $Double->toVerbal($dRec->invoiceValue);
                }
            }
        }

        $row->invoiceDate = $Date->toVerbal($dRec->invoiceDate);

        $row->dueDate = self::getDueDate($dRec, true, $rec);

        $row->currencyId = $dRec->currencyId;

        $invoiceValue = $rec->unpaid == 'all' ? $dRec->invoiceValue : $dRec->invoiceValue;

        $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate);

        // Червен ред при просрочен неплатен остатък.
        $cond = $rec->unpaid == 'unpaid' ? $dRec->dueDate && $dRec->invoiceCurrentSumm > 0 : $dRec->invoiceCurrentSumm > 0;

        if ($cond) {
            $row->ROW_ATTR['class'] = 'bold red';
        }

        if ($dRec->className == 'sales_Invoices') {
            $row->className = 'Фактури ПРОДАЖБИ';
        } else {
            $row->className = 'Фактури ПОКУПКИ';
        }

        return $row;
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {

        $Enum = cls::get('type_Enum', array('options' => array('cash' => 'В брой', 'bank' => 'По банков път', 'intercept' => 'С прихващане', 'card' => 'С карта', 'factoring' => 'Факторинг', 'postal' => 'Пощенски паричен превод')));

        $baseCurrency = acc_Periods::getBaseCurrencyCode($data->rec->checkDate);

        // Блок „Филтър“ над таблицата: избрани критерии и обобщени суми.
        $fieldTpl = new core_ET(
            tr(
                "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN contragent--><div>|Контрагент|*: <b>[#contragent#]</b></div><!--ET_END contragent-->
                                        <!--ET_BEGIN crmGroup--><div>|Група контрагенти|*: [#crmGroup#]</div><!--ET_END crmGroup-->
                                        <!--ET_BEGIN typeOfInvoice--> <div>|Фактури|*: <b>[#typeOfInvoice#]</b></div><!--ET_END typeOfInvoice-->
                                        <!--ET_BEGIN unpaid--><div>|Плащане|*: <b>[#unpaid#]</b></div><!--ET_END unpaid-->
                                        <!--ET_BEGIN paymentType--><div>|Начин на плащане|*: <b>[#paymentType#]</b></div><!--ET_END paymentType-->
                                        <!--ET_BEGIN totalInvoiceValueAll--><div>|Стойност|*: <b>[#totalInvoiceValueAll#] $baseCurrency</b></div><!--ET_END totalInvoiceValueAll-->
                                        <!--ET_BEGIN totalInvoicePayoutAll--><div>|Общо ПЛАТЕНА СУМА|*: <b>[#totalInvoicePayoutAll#] $baseCurrency</b></div><!--ET_END totalInvoicePayoutAll-->
                                        <!--ET_BEGIN totalInvoiceNotPaydAll--><div>|Общо НЕПЛАТЕНА СУМА|*: <b>[#totalInvoiceNotPaydAll#] $baseCurrency</b></div><!--ET_END totalInvoiceNotPaydAll-->
                                        <!--ET_BEGIN totalInvoiceOverPaidAll--><div>|Общо НАДПЛАТЕНА СУМА|*: <b>[#totalInvoiceOverPaidAll#] $baseCurrency</b></div><!--ET_END totalInvoiceOverPaidAll-->
                                        <!--ET_BEGIN totalInvoiceOverDueAll--><div>|Общо ПРОСРОЧЕНА СУМА|*: <b>[#totalInvoiceOverDueAll#] $baseCurrency</b></div><!--ET_END totalInvoiceOverDueAll-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"
            )
        );

        if (isset($data->rec->typeOfInvoice)) {
            $inv = $data->rec->typeOfInvoice == 'out' ? 'ИЗХОДЯЩИ' : 'ВХОДЯЩИ';

            $fieldTpl->append(
                $inv,
                'typeOfInvoice'
            );
        }

        if (isset($data->rec->contragent) || isset($data->rec->crmGroup)) {
            $marker = 0;
            $groupVerb = '';
            if (isset($data->rec->crmGroup)) {
                foreach (type_Keylist::toArray($data->rec->crmGroup) as $group) {
                    $marker++;

                    $groupVerb .= (crm_Groups::getTitleById($group));

                    if ((countR((type_Keylist::toArray($data->rec->crmGroup))) - $marker) != 0) {
                        $groupVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $groupVerb . '</b>', 'crmGroup');
            }
        }

        if (isset($data->rec->unpaid)) {
            $paid = $data->rec->unpaid == 'unpaid' ? 'НЕПЛАТЕНИ' : 'ВСИЧКИ';
            $fieldTpl->append(
                $paid,
                'unpaid'
            );
        }

        if (isset($data->rec->paymentType)) {

            $fieldTpl->append($Enum->toVerbal($data->rec->paymentType), 'paymentType');

        }

        //Всички фактури
        if (isset($data->rec->totalInvoiceValueAll)) {
            if (is_numeric($data->rec->totalInvoiceValueAll)) {
                $fieldTpl->append(
                    core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->totalInvoiceValueAll),
                    'totalInvoiceValueAll'
                );
            } else {
                $fieldTpl->append(
                    ($data->rec->totalInvoiceValueAll),
                    'totalInvoiceValueAll'
                );
            }
        }


        //Само когато е избрано 'НЕПЛАТЕНИ' фактури
        if (($data->rec->unpaid ?? 'all') == 'unpaid') {

            //Платено по фактури
            if (isset($data->rec->totalInvoicePayoutAll)) {
                if (is_numeric($data->rec->totalInvoicePayoutAll)) {
                    $fieldTpl->append(
                        core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->totalInvoicePayoutAll),
                        'totalInvoicePayoutAll'
                    );
                } else {
                    $fieldTpl->append(
                        ($data->rec->totalInvoicePayoutAll),
                        'totalInvoicePayoutAll'
                    );
                }
            }

            //НЕДОплатено по фактури
            if (isset($data->rec->totalInvoiceNotPaydAll)) {
                if (is_numeric($data->rec->totalInvoiceNotPaydAll)) {
                    $fieldTpl->append(
                        core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->totalInvoiceNotPaydAll),
                        'totalInvoiceNotPaydAll'
                    );
                } else {
                    $fieldTpl->append(
                        ($data->rec->totalInvoiceNotPaydAll),
                        'totalInvoiceNotPaydAll'
                    );
                }
            }

            //НАДплатено по фактури
            if (isset($data->rec->totalInvoiceOverPaidAll)) {
                if (is_numeric($data->rec->totalInvoiceOverPaidAll)) {
                    $fieldTpl->append(
                        core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->totalInvoiceOverPaidAll),
                        'totalInvoiceOverPaidAll'
                    );
                } else {
                    $fieldTpl->append(
                        ($data->rec->totalInvoiceOverPaidAll),
                        'totalInvoiceOverPaidAll'
                    );
                }
            }

            //Просрочено по фактури
            if (isset($data->rec->totalInvoiceOverDueAll)) {
                if (is_numeric($data->rec->totalInvoiceOverDueAll)) {
                    $fieldTpl->append(
                        core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->totalInvoiceOverDueAll),
                        'totalInvoiceOverDueAll'
                    );
                } else {
                    $fieldTpl->append(
                        ($data->rec->totalInvoiceOverDueAll),
                        'totalInvoiceOverDueAll'
                    );
                }
            }
        }
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * Връща папките на контрагентите от избраните групи
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public static function getFoldersInGroups($rec)
    {
        $foldersInGroups = array();

        // Търсят се папките както на фирми, така и на лица в избраните CRM групи.
        foreach (array('crm_Companies', 'crm_Persons') as $clsName) {
            $q = $clsName::getQuery();

            $q->LikeKeylist('groupList', $rec->crmGroup);

            $q->where('#folderId IS NOT NULL');

            $q->show('folderId');

            $foldersInGroups = array_merge($foldersInGroups, arr::extractValuesFromArray($q->fetchAll(), 'folderId'));
        }

        return $foldersInGroups;
    }

    /**
     * Връща проформите към които има насочени плащания
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public static function getProformsWithPaymant($rec)
    {

        // Избират се активните проформи според зададените филтри.
        $proformInvQuery = sales_Proformas::getQuery();
        $proformInvQuery->where("#state = 'active'");

        if ($rec->contragent) {
            $contragentsArr = keylist::toArray($rec->contragent);
            $proformInvQuery->in('folderId', $contragentsArr);
        }

        $profomInvArr = arr::extractValuesFromArray($proformInvQuery->fetchAll(), 'threadId');

        $proformWithPayDocArr = array();

        // Проверяват се касовите и банковите приходни документи за връзка към проформи.
        foreach (array('cash_Pko', 'bank_IncomeDocuments') as $payDocs) {
            $payDocsClassId = $payDocs::getClassId();
            $payDocQuery = $payDocs::getQuery();
            $payDocQuery->in('threadId', $profomInvArr);
            $payDocQuery->where("#state = 'active'");
            while ($pDocRec = $payDocQuery->fetch()) {

                // Връзките показват към кои документи е насочено конкретното плащане.
                $invArr = deals_InvoicesToDocuments::getInvoiceArr($pDocRec->containerId);
                if (!empty($invArr)) {
                    foreach ($invArr as $key => $val) {

                        $pDocoment = doc_Containers::getDocument($val->containerId);

                        if ($pDocoment->className != 'sales_Proformas') {
                            continue;
                        } else {

                            if (!array_key_exists($pDocoment->that, $proformWithPayDocArr)) {
                                $proformWithPayDocArr[$pDocoment->that] = (object)array(

                                    'documents' => array($pDocRec->id),
                                    'docClassId' => $payDocsClassId,
                                    'date' => $pDocRec->valior,

                                );
                            } else {
                                $obj = &$proformWithPayDocArr[$pDocoment->that];

                                array_push($obj->documents, $pDocRec->id);

                            }
                        }
                    }
                }
            }
        }

        return $proformWithPayDocArr;
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass $res
     * @param stdClass $rec
     * @param stdClass $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        // Форматира дати, суми и статус „просрочен“ за CSV/Excel експорт.
        $Date = cls::get('type_Date');

        if (($rec->unpaid ?? 'all') == 'unpaid' && empty($rec->checkDate)) {
            $checkDate = dt::now();
        } else {
            $checkDate = $rec->checkDate ?? dt::today(false);
        }
        $invoiceValue = $dRec->invoiceValue ?? 0;
        $invoiceCurrentSumm = $dRec->invoiceCurrentSumm ?? 0;
        $dcMark = $invoiceValue < 0 ? -1 : 1;
        if (($dRec->type ?? 'invoice') != 'invoice') {
            foreach ((array)($dRec->dcPay ?? array()) as $k => $val) {
                $res->paidAmount = ($res->paidAmount ?? '') . $val->amount * $dcMark;
            }
        } else {
            $res->paidAmount = self::getPaidAmount($dRec);
        }

        if (($dRec->type ?? 'invoice') != 'invoice') {
            foreach ((array)($dRec->dcPay ?? array()) as $k => $val) {
                $res->paidDates = ($res->paidDates ?? '') . $Date->toVerbal($val->payDate) . "\n\r";
            }
        } else {
            $res->paidDates = self::getPaidDates($dRec, false);
        }

        $res->dueDate = self::getDueDate($dRec, false, $rec);

        if ($invoiceCurrentSumm < 0) {
            $invoiceOverSumm = -1 * $invoiceCurrentSumm;
            $res->invoiceCurrentSumm = '';
            $res->invoiceOverSumm = ($invoiceOverSumm);
        }

        if (!empty($dRec->dueDate) && $invoiceCurrentSumm > 0 && $dRec->dueDate < $checkDate) {
            $res->dueDateStatus = 'Просрочен';
        }

        $invoiceNo = str_pad($dRec->invoiceNo ?? '', 10, '0', STR_PAD_LEFT);

        $res->invoiceNo = $invoiceNo;

        $res->invoiceValueBaseCurr = $invoiceValue * ($dRec->rate ?? 1);
    }

}
