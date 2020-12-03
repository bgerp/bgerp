<?php


/**
 * Мениджър на отчети за Фактури по контрагент
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
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,placeholder=Всички,single=none,after=title');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Контрагенти->Група контрагенти,placeholder=Всички,after=contragent,single=none');
        
        $fieldset->FLD('typeOfInvoice', 'enum(out=Изходящи,in=Входящи)', 'caption=Фактури,after=crmGroup,maxRadio=2,mandatory,single=none');
        $fieldset->FLD('unpaid', 'enum(all=Всички,unpaid=Неплатени)', 'caption=Плащане,after=typeOfInvoice,removeAndRefreshForm,single=none,mandatory,silent');
        
        $fieldset->FLD('fromDate', 'date', 'caption=От дата,after=unpaid, placeholder=от началото');
        $fieldset->FLD('checkDate', 'date', 'caption=До дата,after=fromDate,mandatory');
        
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
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if ($rec->unpaid == 'unpaid') {
            unset($rec->fromDate);
            $form->setField('fromDate', 'input=none');
        }
        
        if ($rec->unpaid == 'all') {
            $form->setDefault('fromDate', null);
        }
        $checkDate = dt::today();
        $form->setDefault('checkDate', "{$checkDate}");
        
        $form->setDefault('typeOfInvoice', 'out');
        
        $form->setDefault('unpaid', 'all');
        
        $salesQuery = sales_Sales::getQuery();
        
        $salesQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');
        
        $salesQuery->groupBy('folderId');
        
        $salesQuery->show('folderId, contragentId, folderTitle');
        
        $purchQuery = purchase_Purchases::getQuery();
        
        $purchQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');
        
        $purchQuery->groupBy('folderId');
        
        $purchQuery->show('folderId, contragentId, folderTitle');
        
        while ($purContragent = $purchQuery->fetch()) {
            if (!is_null($purContragent->contragentId)) {
                $purSuggestions[$purContragent->folderId] = $purContragent->folderTitle;
            }
        }
        
        while ($contragent = $salesQuery->fetch()) {
            if (!is_null($contragent->contragentId)) {
                $suggestions[$contragent->folderId] = $contragent->folderTitle;
            }
        }
        
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
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
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
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $recs = array();
        
        // Фактури ПРОДАЖБИ
        if ($rec->typeOfInvoice == 'out') {
            
            $sRecs = array();
            $sRecsAll = array();
            $isRec = array();
            $totalInvoiceContragent = $totalInvoiceContragentAll = array();
            
            $invQuery = sales_Invoices::getQuery();
            
            $invQuery->where("#state != 'rejected' AND #number IS NOT NULL");
            
            //При избрани НЕПЛАТЕНИ махаме дебитните и кредитните известия
            if ($rec->unpaid == 'unpaid') {
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
                $rec->checkDate
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
                    $foldersInGroups = self::getFoldersInGroups($rec);
                    
                    $invQuery->in('folderId', $foldersInGroups);
                }
                
                if ($rec->crmGroup && $rec->contragent) {
                    $contragentsArr = keylist::toArray($rec->contragent);
                    
                    $invQuery->in('folderId', $contragentsArr);
                    
                    $foldersInGroups = self::getFoldersInGroups($rec);
                    
                    $invQuery->in('folderId', $foldersInGroups);
                }
            }
            
            // Обединени продажби
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
                if (strpos($sale->contoActions, 'pay')) {
                    $fastSales[$sale->id] = ($sale->amountPaid - $sale->amountVat);
                }
            }
            
            
            //Изваждаме нишките за проверка  са избрани НЕПЛАТЕНИ
            //Ако са избрани ВСИЧКИ записваме масив $allInvoices със всички фактури
            $threadsId = array();
            
            // Синхронизира таймлимита с броя записи //
            $maxTimeLimit = $invQuery->count() * 5;
            $maxTimeLimit = max(array($maxTimeLimit, 300));
            if ($maxTimeLimit > 300) {
                core_App::setTimeLimit($maxTimeLimit);
            }
            
            while ($salesInvoice = $invQuery->fetch()) {
                
                
                $firstDocument = doc_Threads::getFirstDocument($salesInvoice->threadId);
                
                $firstDocumentArr[$salesInvoice->threadId] = $firstDocument->that;
                
                $className = $firstDocument->className;
                
                // Ако са избрани само неплатените фактури
                if ($rec->unpaid == 'unpaid') {
                    $unitedCheck = false;
                    
                    if (is_array($salesUN)) {
                        $unitedCheck = in_array($className::fetchField($firstDocument->that), $salesUN);
                    }
                    
                    
                    //Ако продажбата е приключена с друг договор фактурите от тази сделка остават в справката, ако е приключена
                    //по друг начин сделката се прескача.
                    if (($className::fetchField($firstDocument->that, 'state') == 'closed') &&
                        ($className::fetchField($firstDocument->that, 'closedOn') <= $rec->checkDate) &&
                        ! $unitedCheck) {
                        continue;
                    }
                }
                
                $threadsId[$salesInvoice->threadId] = $salesInvoice->threadId;
               
                // Когато е избрано ВСИЧКИ в полето плащане
                if ($rec->unpaid == 'all') {
                    
                    // масив от фактури в тази нишка //
                    $invoicePayments = (deals_Helper::getInvoicePayments($salesInvoice->threadId, $rec->checkDate));
                    
                    $paydocs = $invoicePayments[$salesInvoice->containerId];
                    
                    $invoiceValue = ($salesInvoice->dealValue - $salesInvoice->discountAmount) / $salesInvoice->rate + $salesInvoice->vatAmount;
                    $Invoice = doc_Containers::getDocument($salesInvoice->containerId);
                    
                    // масива с фактурите за показване
                    if (! array_key_exists($salesInvoice->id, $sRecsAll)) {
                        $sRecsAll[$salesInvoice->id] = (object) array(
                            
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
                            'invoicePayout' => $paydocs->payout,
                        );
                    }
                    
                    // Масив с данни за сумите от фактурите  обединени по контрагенти
                    if (! array_key_exists($salesInvoice->contragentName, $totalInvoiceContragentAll)) {
                        $totalInvoiceContragentAll[$salesInvoice->contragentName] = (object) array(
                            'totalInvoiceValue' => $invoiceValue,                                        //общо стойност на фактурите за контрагента
                            'totalInvoiceVAT' => $salesInvoice->vatAmount,                               //общо стойност на ДДС по фактурите за контрагента
                        
                        );
                    } else {
                        $obj = &$totalInvoiceContragentAll[$salesInvoice->contragentName];
                        
                        $obj->totalInvoiceValue += $invoiceValue;
                        $obj->totalInvoiceVAT += $salesInvoice->vatAmount;
                    }
                    continue;
                }
            }
            
            if (is_array($threadsId)) {
                foreach ($threadsId as $thread) {
                    $salesInvoiceNotPaid = 0;
                    $salesInvoiceOverPaid = 0;
                    $salesInvoiceOverDue = 0;
                    
                    // масив от фактури в тази нишка //
                    $invoicePayments = (deals_Helper::getInvoicePayments($thread, $rec->checkDate));
                    
                    if (is_array($invoicePayments)) {
                        
                        // фактура от нишката и масив от платежни документи по тази фактура//
                        foreach ($invoicePayments as $inv => $paydocs) {
                            
                            //Разлика между стойност и платено по фактурата
                            $invDiff = $paydocs->amount - $paydocs->payout;
                            
                            // Ако продажбата е бърза, фактурата се счита за платена
                            //Когато се коригира функцията за разпределение на плащанията това да се премахне !!!
                            $invDiff = in_array($firstDocumentArr[$thread], array_keys($fastSales)) ? 0 :$invDiff ;
                            
                            // Ако са избрани само неплатените фактури пропускаме тези с отклонение под 0.01
                            if ($rec->unpaid == 'unpaid') {
                                if (($invDiff >= - 0.01) &&
                                    ($invDiff <= + 0.01)) {
                                    continue;
                                }
                            }
                            
                            //Тази фактура
                            $Invoice = doc_Containers::getDocument($inv);
                            
                            if ($Invoice->className != 'sales_Invoices') {
                                continue;
                            }
                            
                            //Данни по тази фактура
                            $iRec = $Invoice->fetch(
                                'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,
                                     currencyId,date,dueDate,contragentName'
                                );
                            
                            //Ако датата на фактурата е по голяма от избраната "към дата" не влиза в масива
                            if ($rec->checkDate < $iRec->date) {
                                continue;
                            }
                            
                            if (($invDiff) > 0) {
                                $salesInvoiceNotPaid = $invDiff;
                            }
                            
                            if (($invAmount - $invPayout) < 0) {
                                $salesInvoiceOverPaid = -1 * $invDiff;
                            }
                            
                            if ($iRec->dueDate && $invDiff > 0 &&
                                $iRec->dueDate < $rec->checkDate) {
                                $salesInvoiceOverDue = $invDiff;
                            }
                            
                            // Масив с данни за сумите от фактурите  обединени по контрагенти
                            if (! array_key_exists($iRec->id, $sRecs)) {
                            if (! array_key_exists($iRec->contragentName, $totalInvoiceContragent)) {
                                $totalInvoiceContragent[$iRec->contragentName] = (object) array(
                                    'totalInvoiceValue' => $paydocs->amount,                            //общо стойност на фактурите за контрагента
                                    'totalInvoicePayout' => $paydocs->payout,                           //плащания по фактурите за контрагента
                                    'totalInvoiceNotPaid' => $salesInvoiceNotPaid,                      //стойност на НЕДОплатените суми по фактурите за контрагента
                                    'totalInvoiceOverPaid' => $salesInvoiceOverPaid,                    //стойност на НАДплатените суми по фактурите за контрагента
                                    'totalInvoiceOverDue' => $salesInvoiceOverDue,                      //стойност за плащане по просрочените фактури за контрагента
                                );
                            } else {
                                $obj = &$totalInvoiceContragent[$iRec->contragentName];
                                
                                $obj->totalInvoiceValue += $paydocs->amount;
                                $obj->totalInvoicePayout += $paydocs->payout;
                                $obj->totalInvoiceNotPaid += $salesInvoiceNotPaid;
                                $obj->totalInvoiceOverPaid += $salesInvoiceOverPaid;
                                $obj->totalInvoiceOverDue += $salesInvoiceOverDue;
                            }
                            }
                            
                            // масива с фактурите за показване
                            if (! array_key_exists($iRec->id, $sRecs)) {
                                $sRecs[$iRec->id] = (object) array(
                                    'threadId' => $thread,
                                    'className' => $Invoice->className,
                                    'invoiceId' => $iRec->id,
                                    'invoiceNo' => $iRec->number,
                                    'invoiceDate' => $iRec->date,
                                    'dueDate' => $iRec->dueDate,
                                    'invoiceContainerId' => $iRec->containerId,
                                    'currencyId' => $iRec->currencyId,
                                    'rate' => $iRec->rate,
                                    'invoiceValue' => $paydocs->amount,
                                    'invoiceVAT' => $iRec->vatAmount,
                                    'invoicePayout' => $paydocs->payout,
                                    'invoiceCurrentSumm' => $invDiff,
                                    'payDocuments' => $paydocs->used,
                                    'contragent' => $iRec->contragentName
                                );
                            }
                        }
                    }
                }
            }
        }
        
        // ВХОДЯЩИ ФАКТУРИ
        if ($rec->typeOfInvoice == 'in') {
            $pRecs = $pRecsAll = array();
            $isRec = array();
            $totalInvoiceContragent = $totalInvoiceContragentAll = array();
            
            $pQuery = purchase_Invoices::getQuery();
            
            $pQuery->where("#state != 'rejected' AND #number IS NOT NULL");
           
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
                $rec->checkDate
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
                if (strpos($purchase->contoActions, 'pay')) {
                    $fastPur[$purchase->id] = ($purchase->amountPaid - $purchase->amountVat);
                }
            }
            
            // Синхронизира таймлимита с броя записи //
            $maxTimeLimit = $pQuery->count() * 5;
            $maxTimeLimit = max(array($maxTimeLimit, 300));
            if ($maxTimeLimit > 300) {
                core_App::setTimeLimit($maxTimeLimit);
            }
            
            // Фактури ПОКУПКИ
            while ($purchaseInvoices = $pQuery->fetch()) {
                
                // Когато е избрано ВСИЧКИ в полето плащане
                if ($rec->unpaid == 'all') {
                    $invoiceValue = ($purchaseInvoices->dealValue - $purchaseInvoices->discountAmount) + $purchaseInvoices->vatAmount;
                    $Invoice = doc_Containers::getDocument($purchaseInvoices->containerId);
                    
                    // масив от фактури в тази нишка //
                    $invoicePayments = (deals_Helper::getInvoicePayments($purchaseInvoices->threadId, $rec->checkDate));
                    
                    $paydocs = $invoicePayments[$purchaseInvoices->containerId];
                    
                    // масива с фактурите за показване
                    if (! array_key_exists($purchaseInvoices->id, $pRecsAll)) {
                        $pRecsAll[$purchaseInvoices->id] = (object) array(
                            
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
                            'invoicePayout' => $paydocs->payout,
                        );
                    }
                    
                    // Масив с данни за сумите от фактурите  обединени по контрагенти
                    if (! array_key_exists($purchaseInvoices->contragentName, $totalInvoiceContragentAll)) {
                        $totalInvoiceContragentAll[$purchaseInvoices->contragentName] = (object) array(
                            'totalInvoiceValue' => $invoiceValue, //общо стойност на фактурите за контрагента
                            'totalInvoiceVAT' => $purchaseInvoices->vatAmount,//общо стойност на ДДС по фактурите за контрагента
                        
                        );
                    } else {
                        $obj = &$totalInvoiceContragentAll[$purchaseInvoices->contragentName];
                        
                        $obj->totalInvoiceValue += $invoiceValue;
                        $obj->totalInvoiceVAT += $purchaseInvoices->vatAmount;
                    }
                    continue;
                }
                
                ///////////////////
                
                
                
                $firstDocument = doc_Threads::getFirstDocument($purchaseInvoices->threadId);
                
                $firstDocumentArr[$purchaseInvoices->threadId] = $firstDocument->that;
                
                $className = $firstDocument->className;
                
                // Ако са избрани само неплатените фактури
                if ($rec->unpaid == 'unpaid') {
                    $purUnitedCheck = false;
                    
                    if (is_array($purchasesUN)) {
                        $purUnitedCheck = in_array($className::fetchField($firstDocument->that), $purchasesUN);
                    }
                    
                    if (($className::fetchField($firstDocument->that, 'state') == 'closed') &&
                        ($className::fetchField($firstDocument->that, 'closedOn') <= $rec->checkDate) &&
                        ! $purUnitedCheck) {
                        continue;
                    }
                }
                
                $pThreadsId[$purchaseInvoices->threadId] = $purchaseInvoices->threadId;
            }
            
            if (is_array($pThreadsId)) {
                foreach ($pThreadsId as $pThread) {
                    $purchaseInvoiceNotPaid = 0;
                    $purchaseInvoiceOverDue = 0;
                    
                    // масив от фактури в тази нишка //
                    $pInvoicePayments = (deals_Helper::getInvoicePayments($pThread, $rec->checkDate));
                    
                    if ((is_array($pInvoicePayments))) {
                        
                        // фактура от нишката и масив от платежни документи по тази фактура//
                        foreach ($pInvoicePayments as $pInv => $paydocs) {
                            
                            //Разлика между стойност и платено по фактурата
                            $invDiff = $paydocs->amount - $paydocs->payout;
                            
                            // Ако покупката е бърза, фактурата се счита за платена
                            //Когато се коригира функцията за разпределение на плащанията това да се премахне !!!
                            $invDiff = in_array($firstDocumentArr[$pThread], array_keys($fastPur)) ? 0 :$invDiff ;
                            
                            // Ако са избрани само неплатените фактури
                            if ($rec->unpaid == 'unpaid') {
                                if (($invDiff >= - 0.01) &&
                                    ($invDiff <= + 0.01)) {
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
                            if ($rec->checkDate < $iRec->date) {
                                continue;
                            }
                            
                            if (($invDiff) > 0) {
                                $purchaseInvoiceNotPaid = ($invDiff);
                            }
                            
                            if ($iRec->dueDate && ($invDiff) > 0 &&
                                $iRec->dueDate < $rec->checkDate) {
                                $purchaseInvoiceOverDue = ($invDiff);
                            }
                            
                            // Масив с данни за сумите от фактурите  обединени по контрагенти
                            if (! array_key_exists($iRec->contragentName, $totalInvoiceContragent)) {
                                $totalInvoiceContragent[$iRec->contragentName] = (object) array(
                                    'totalInvoiceValue' => $paydocs->amount, //общо стойност на фактурите за контрагента
                                    'totalInvoicePayout' => $paydocs->payout,//плащания по фактурите за контрагента
                                    'totalInvoiceNotPaid' => $purchaseInvoiceNotPaid * $iRec->rate,//стойност за плащане по фактурите за контрагента
                                    'totalInvoiceOverDue' => $purchaseInvoiceOverDue * $iRec->rate,//стойност за плащане по просрочените фактури за контрагента
                                ) ;
                            } else {
                                $obj = &$totalInvoiceContragent[$iRec->contragentName];
                                
                                $obj->totalInvoiceValue += $paydocs->amount;
                                $obj->totalInvoicePayout += $paydocs->payout;
                                $obj->totalInvoiceNotPaid += $purchaseInvoiceNotPaid * $iRec->rate;
                                $obj->totalInvoiceOverDue += $purchaseInvoiceOverDue * $iRec->rate;
                            }
                            
                            
                            // масива с фактурите за показване
                            if (! array_key_exists($iRec->id, $pRecs)) {
                                $pRecs[$iRec->id] = (object) array(
                                    'threadId' => $pThread,
                                    'className' => $Invoice->className,
                                    'invoiceId' => $iRec->id,
                                    'invoiceNo' => $iRec->number,
                                    'invoiceDate' => $iRec->date,
                                    'dueDate' => $iRec->dueDate,
                                    'invoiceContainerId' => $iRec->containerId,
                                    'currencyId' => $iRec->currencyId,
                                    'rate' => $iRec->rate,
                                    'invoiceValue' => $paydocs->amount,
                                    'invoiceVAT' => $iRec->vatAmount,
                                    'invoicePayout' => $paydocs->payout,
                                    'invoiceCurrentSumm' => $invDiff,
                                    'payDocuments' => $paydocs->used,
                                    'contragent' => $iRec->contragentName
                                );
                            }
                        }
                    }
                }
            }
        }
        
        //Ако е избрано плащане ВСИЧКИ заместваме масива sRecs с sRecsAll
        if ($rec->unpaid == 'all') {
            if ($rec->typeOfInvoice == 'out') {
                $sRecs = array();
                $sRecs += $sRecsAll;
            }
            
            if ($rec->typeOfInvoice == 'in') {
                $pRecs = array();
                $pRecs += $pRecsAll;
            }
        }
        
        
        //Подрежда се по дата на фактура
        if (countR($sRecs)) {
            arr::sortObjects($sRecs, 'invoiceDate', 'asc', 'stri');
        }
        
        if (countR($pRecs)) {
            arr::sortObjects($pRecs, 'invoiceDate', 'asc', 'stri');
        }
        
        $recs = $rec->typeOfInvoice == 'out' ? $sRecs : $pRecs;
        
        unset(
            $rec->totalInvoiceValueAll,
            $rec->totalInvoicePayoutAll,
            $rec->totalInvoiceNotPaydAll,
            $rec->totalInvoiceOverPaidAll,
            $rec->totalInvoiceOverDueAll
            );
        
        $contragentCurrency = array();
        $flagAll = false;
        
        // обработка и добавяне на сумите по контрагент и общо
        foreach ($recs as $key => $val) {
            
            //Проверка за различни валути във фактурите на контрагент(вдига flag ако са различни)
            if (! array_key_exists($val->contragent, $contragentCurrency)) {
                $contragentCurrency[$val->contragent] = (object) array(
                    'currency' => null,
                    'flag' => false,
                    'contragent' => false,
                );
            } else {
                if (($contragentCurrency[$val->contragent]->currency != $val->currencyId) &&
                    !is_null($contragentCurrency[$val->contragent]->currency)) {
                    $contragentCurrency[$val->contragent]->flag = true ;
                }
                $contragentCurrency[$val->contragent]->currency = $val->currencyId;
            }
            
            if ($rec->unpaid == 'all') {
                $totalInvoiceContragent = $totalInvoiceContragentAll;
            }
            
            
            //Сумира фактурите по контрагент ако са в една валута
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
            $rec->totalInvoiceValueAll += $v -> totalInvoiceValue;
            $rec->totalInvoicePayoutAll += $v -> totalInvoicePayout;
            $rec->totalInvoiceNotPaydAll += $v -> totalInvoiceNotPaid;
            $rec->totalInvoiceOverPaidAll += $v -> totalInvoiceOverPaid;
            $rec->totalInvoiceOverDueAll += $v -> totalInvoiceOverDue;
        }
       
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($export === false) {
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,smartCenter');
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
            
            $fld->FLD('invoiceDate', 'varchar', 'caption=Дата');
            $fld->FLD('dueDate', 'varchar', 'caption=Краен срок');
            
            
            if ($rec->unpaid == 'all') {
                $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
                $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
                $fld->FLD('invoiceValueBaseCurr', 'double(smartRound,decimals=2)', 'caption=Стойност BGN');
                $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->Сума->лв.,smartCenter');
                $fld->FLD('paidDates', 'varchar', 'caption=Платено->Плащания->дата,smartCenter');
            }
            
            if ($rec->unpaid == 'unpaid') {
                $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
                $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност-> Сума->валута,smartCenter');
                $fld->FLD('invoiceValueBaseCurr', 'double(smartRound,decimals=2)', 'caption=Стойност-> Сума-> лв.,smartCenter');
                $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->Сума->лв.,smartCenter');
                $fld->FLD('paidDates', 'varchar', 'caption=Платено->Плащания->дата,smartCenter');
                $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Състояние->Неплатено->лв.,smartCenter');
                $fld->FLD('invoiceOverSumm', 'double(smartRound,decimals=2)', 'caption=Състояние->Надплатено-> лв.,smartCenter');
            }
        } else {
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,smartCenter');
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
            $fld->FLD('invoiceDate', 'date', 'caption=Дата,smartCenter');
            $fld->FLD('dueDate', 'date', 'caption=Краен срок,smartCenter');
            $fld->FLD('dueDateStatus', 'varchar', 'caption=Състояние,smartCenter');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
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
     * @param bool     $verbal
     *
     * @return mixed $paidAmount
     */
    private static function getPaidAmount($dRec, $verbal = true)
    {
        $paidAmount = $dRec->invoicePayout * $dRec->rate;
        
        return $paidAmount;
    }
    
    
    /**
     * Връща дати на плащания
     *
     * @param stdClass $dRec
     * @param bool     $verbal
     *
     * @return mixed $paidDates$data->rec->salesTotalNotPaid
     */
    private static function getPaidDates($dRec, $verbal = true)
    {
        if (is_array($dRec->payDocuments)) {
            foreach ($dRec->payDocuments as $onePayDoc) {
                if (! is_null($onePayDoc->containerId)) {
                    $Document = doc_Containers::getDocument($onePayDoc->containerId);
                } else {
                    continue;
                }
                $payDocClass = $Document->className;
                
                $paidDatesList .= ',' . $payDocClass::fetch($Document->that)->valior;
            }
        }
        if ($verbal === true) {
            $amountsValiors = explode(',', trim($paidDatesList, ','));
            
            foreach ($amountsValiors as $v) {
                $paidDate = dt::mysql2verbal($v, $mask = 'd.m.y');
                
                $paidDates .= "${paidDate}" . '<br>';
            }
        } else {
            $amountsValiors = explode(',', trim($paidDatesList, ','));
            
            foreach ($amountsValiors as $v) {
                $paidDate = dt::mysql2verbal($v, $mask = 'd.m.y');
                
                $paidDates .= "${paidDate}" . "\n\r";
            }
        }
        
        return $paidDates;
    }
    
    
    /**
     * Връща просрочие на плащане
     *
     * @param stdClass $dRec
     * @param bool     $verbal
     *
     * @return mixed $dueDate
     */
    private static function getDueDate($dRec, $verbal = true, $rec)
    {
        if ($verbal === true) {
            if ($dRec->dueDate) {
                $dueDate = dt::mysql2verbal($dRec->dueDate, $mask = 'd.m.Y');
                
                if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
                    $dueDate = ht::createHint($dueDate, 'фактурата е просрочена', 'warning');
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
        $Date = cls::get('type_Date');
        $Double = core_Type::getByName('double(decimals=2)');
        
        $row = new stdClass();
        
        $invoiceNo = str_pad($dRec->invoiceNo, 10, '0', STR_PAD_LEFT);
        
        $row->invoiceNo = ht::createLinkRef(
            
            $invoiceNo,
            array(
                $dRec->className,
                'single',
                $dRec->invoiceId
            )
            
            );
        if ($rec->unpaid == 'all') {
            if ($dRec->type != 'invoice') {
                $type = $dRec->invoiceValue < 0 ? 'Кредитно известие': 'Дебитно известие';
                
                $row->invoiceNo .= "<span class='quiet'>".'<br>'.$type.'</span>';
            }
            
            
            $row->contragent = $dRec->contragent.' »  '."<span class= 'quiet'>".' Общо стойност: '.'</span>'.core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalInvoiceValue * $dRec->rate).' лв.';
            if ($dRec->totalInvoiceOverPaid > 0.01) {
                $row->contragent .= ' »  '."<span class= 'quiet'>" . 'Надплатено:' . '</span>'.$dRec->totalInvoiceOverPaid * $dRec->rate;
            }
        }
        
        if ($rec->unpaid == 'unpaid') {
            $row->contragent = $dRec->contragent.'</br>'."<span class= 'quiet'>" . ' Общо фактури: ' . '</span>'.$Double->toVerbal($dRec->totalInvoiceValue * $dRec->rate)
            .' »  '."<span class= 'quiet'>" . ' Платено: ' . '</span>'.$Double->toVerbal($dRec->totalInvoicePayout * $dRec->rate)
            .' »  '."<span class= 'quiet'>" . 'Недоплатено:' . '</span>'.$Double->toVerbal($dRec->totalInvoiceNotPayd * $dRec->rate);
            
            
            if ($dRec->totalInvoiceOverPaid > 0.01) {
                $row->contragent .= ' »  '."<span class= 'quiet'>" . 'Надплатено:' . '</span>'.$dRec->totalInvoiceOverPaid * $dRec->rate;
            }
        }
        
        $row->invoiceDate = $Date->toVerbal($dRec->invoiceDate);
        
        $row->dueDate = self::getDueDate($dRec, true, $rec);
        
        $row->currencyId = $dRec->currencyId;
        
        $invoiceValue = $rec->unpaid == 'all' ? $dRec->invoiceValue  :$dRec->invoiceValue;
        
        if ($dRec->currencyId != 'BGN') {
            $row->invoiceValue = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceValue);
        }
        
        $row->invoiceValueBaseCurr = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceValue * $dRec->rate);
        
        if ($dRec->invoiceCurrentSumm > 0) {
            $row->invoiceCurrentSumm = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->invoiceCurrentSumm * $dRec->rate);
        }
        
        if ($dRec->invoiceCurrentSumm < 0) {
            $invoiceOverSumm = - 1 * $dRec->invoiceCurrentSumm;
            $row->invoiceOverSumm = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceOverSumm * $dRec->rate);
        }
        $row->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal(self::getPaidAmount($dRec));
        
        ///////      if ()bp($dRec);
        $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, true) . '</span>';
        
        $cond = $rec->unpaid == 'unpaid' ?$dRec->dueDate && $dRec->invoiceCurrentSumm > 0 :$dRec->invoiceCurrentSumm > 0 ;
        
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
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(
            tr(
                "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN contragent-->|Контрагент|*: <b>[#contragent#]</b><!--ET_END contragent--></div></small>
                
                                <small><div><!--ET_BEGIN typeOfInvoice-->|Фактури|*: <b>[#typeOfInvoice#]</b><!--ET_END typeOfInvoice--></div></small>
                                <small><div><!--ET_BEGIN unpaid-->|Плащане|*: <b>[#unpaid#]</b><!--ET_END unpaid--></div></small>
                
                                <small><div><!--ET_BEGIN totalInvoiceValueAll-->|Стойност|*: <b>[#totalInvoiceValueAll#] лв.</b><!--ET_END totalInvoiceValueAll--></div></small>
                                <small><div><!--ET_BEGIN totalInvoicePayoutAll-->|Общо ПЛАТЕНА СУМА|*: <b>[#totalInvoicePayoutAll#] лв.</b><!--ET_END totalInvoicePayoutAll--></div></small>
                
                                <small><div><!--ET_BEGIN totalInvoiceNotPaydAll-->|Общо НЕПЛАТЕНА СУМА|*: <b>[#totalInvoiceNotPaydAll#] лв.</b><!--ET_END totalInvoiceNotPaydAll--></div></small>
                                <small><div><!--ET_BEGIN totalInvoiceOverPaidAll-->|Общо НАДПЛАТЕНА СУМА|*: <b>[#totalInvoiceOverPaidAll#] лв.</b><!--ET_END totalInvoiceOverPaidAll--></div></small>
                                <small><div><!--ET_BEGIN totalInvoiceOverDueAll-->|Общо ПРОСРОЧЕНА СУМА|*: <b>[#totalInvoiceOverDueAll#] лв.</b><!--ET_END totalInvoiceOverDueAll--></div></small>
                
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
        
        if (isset($data->rec->unpaid)) {
            $paid = $data->rec->unpaid == 'unpaid' ? 'НЕПЛАТЕНИ' : 'ВСИЧКИ';
            $fieldTpl->append(
                $paid,
                'unpaid'
                );
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
        if ($data->rec->unpaid == 'unpaid') {
            
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
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->paidAmount = (self::getPaidAmount($dRec));
        
        $res->paidDates = self::getPaidDates($dRec, false);
        
        $res->dueDate = self::getDueDate($dRec, false, $rec);
        
        if ($dRec->invoiceCurrentSumm < 0) {
            $invoiceOverSumm = - 1 * $dRec->invoiceCurrentSumm;
            $res->invoiceCurrentSumm = '';
            $res->invoiceOverSumm = ($invoiceOverSumm);
        }
        
        if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
            $res->dueDateStatus = 'Просрочен';
        }
        
        $invoiceNo = str_pad($dRec->invoiceNo, 10, '0', STR_PAD_LEFT);
        
        $res->invoiceNo = $invoiceNo;
    }
}
