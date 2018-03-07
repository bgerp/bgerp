<?php

/**
 * Мениджър на отчети за неплатени фактури по клиент
 *
 * @category  bgerp
 * @package   acc
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Счетоводство » Неплатени фактури по клиент
 */
class acc_reports_UnpaidInvoices extends frame2_driver_TableData
{
    
    // deals_Helper::getInvoicePayments($threadId)
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,acc';

    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;

    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    protected $filterEmptyListFields;

    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var varchar
     */
    protected $hashField;

    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck;

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'saleId';

    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields;

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset            
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('contragent', 
            'key2(mvc=doc_Folders,select=title,allowEmpty, restrictViewAccess=yes,coverInterface=crm_ContragentAccRegIntf)', 
            'caption=Контрагент,after=title');
        
        // $fieldset->FLD('dealers', 'users(rolesForAll=ceo|rep_cat, rolesForTeams=ceo|manager|rep_acc|rep_cat,allowEmpty)',
        // 'caption=Дилъри,after=contragent');
        
        $fieldset->FLD('checkDate', 'date', 'caption=Към дата,after=contragent,mandatory');
    }

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *            $Driver
     * @param embed_Manager $Embedder            
     * @param stdClass $data            
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $checkDate = dt::today();
        $form->setDefault('checkDate', "{$checkDate}");
    }

    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec            
     * @param stdClass $data            
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {
        $recs = array();
        $invoicePaidDocuments = array();
        
        $query = sales_Invoices::getQuery();
        
        $query->where("#state != 'rejected'");
        
        $query->where(array(
            "#createdOn < '[#1#]'",
            $rec->checkDate . ' 23:59:59'
        ));
        
        if ($rec->contragent) {
            
            $query->where("#folderId = {$rec->contragent}");
        }
        
        while ($invoices = $query->fetch()) {
            
            if (sales_Sales::fetch(doc_Threads::getFirstDocument($invoices->threadId)->that)->state == 'closed') {
                
                if (sales_Sales::fetch(doc_Threads::getFirstDocument($invoices->threadId)->that)->closedOn >=
                     $rec->checkDate) {
                    
                    $threadsId[$invoices->threadId] = $invoices->threadId;
                    continue;
                }
                
                continue;
            }
            
            $threadsId[$invoices->threadId] = $invoices->threadId;
        }
        
        foreach ($threadsId as $thread) {
            
            // масив от фактури в тази нишка //
            $invoicesInThread = (deals_Helper::getInvoicesInThread($thread, $rec->checkDate, TRUE, TRUE, TRUE));
            
            $invoicePayments = (deals_Helper::getInvoicePayments($thread, $rec->checkDate));
            
            if (is_array($invoicePayments)) {
                
                // if ($thread == 7201)bp($invoicePayments);
                
                // фактура от нишката и масив от платежни документи по тази фактура//
                foreach ($invoicePayments as $inv => $paydocs) {
                    
                    if ($paydocs->notPaid <= 0)
                        continue;
                    
                    $Invoice = doc_Containers::getDocument($inv);
                    
                    $iRec = $Invoice->fetch(
                        'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,currencyId,date,dueDate');
                    
                    // if ($thread == 7201)bp($inv,$paydocs,$iRec);
                    
                    // платежен документ от масива с платежни документи в нишката $thread по фактурата $inv //
                    foreach ($paydocs->payments as $onePayDoc) {
                        
                        $Document = doc_Containers::getDocument($onePayDoc->containerId);
                        
                        $payDocClass = $Document->className;
                        
                        // масива с фактурите за показване
                        if (! array_key_exists($iRec->id, $recs)) {
                            
                            $recs[$iRec->id] = (object) array(
                                'threadId' => $thread,
                                'invoiceId' => $iRec->id,
                                'invoiceNo' => $iRec->number,
                                'invoiceDate' => $iRec->date,
                                'dueDate' => $iRec->dueDate,
                                'invoiceContainerId' => $iRec->containerId,
                                'currencyId' => $iRec->currencyId,
                                'rate' => $iRec->rate,
                                'invoiceValue' => $paydocs->total,
                                'invoiceVAT' => $iRec->vatAmount,
                                'paidAmounts' => $payDocClass::fetch($Document->that)->valior,
                                'invoiceCurrentSumm' => $paydocs->notPaid,
                                'payDocuments' => $paydocs->payments
                            );
                        } else {
                            $obj = &$recs[$iRec->id];
                            $obj->paidAmounds .= ', ' . $payDocClass::fetch($Document->that)->valior;
                        }
                    }
                }
            }
        }
        
        return $recs;
    }

    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *            - записа
     * @param boolean $export
     *            - таблицата за експорт ли е
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($export === FALSE) {
            
            $fld->FLD('invoice', 'double(smartRound,decimals=2)', 'caption=Фактура No,smartCenter');
            $fld->FLD('invoiceDate', 'date', 'caption=Дата,smartCenter');
            $fld->FLD('dueDate', 'date', 'caption=Краен срок,smartCenter');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
            $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->сума');
            $fld->FLD('paidDates', 'date', 'caption=Платено->плащания,smartCenter');
            $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Остатък');
        } else {
            
            $fld->FLD('invoice', 'double(smartRound,decimals=2)', 'caption=Фактура No,smartCenter');
            $fld->FLD('invoiceDate', 'date', 'caption=Дата,smartCenter');
            $fld->FLD('dueDate', 'date', 'caption=Краен срок,smartCenter');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
            $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->сума');
            $fld->FLD('paidDates', 'date', 'caption=Платено->плащания,smartCenter');
            $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Остатък');
        }
        return $fld;
    }

    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *            - записа
     * @param stdClass $dRec
     *            - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        
        $row = new stdClass();
        
        $row->invoice = ht::createLinkRef($dRec->invoiceNo, 
            array(
                'sales_Invoices',
                'single',
                $dRec->invoiceId
            ));
        
        $row->invoiceDate = $Date->toVerbal($dRec->invoiceDate);
        
        $row->dueDate = $Date->toVerbal($dRec->dueDate);
        
        $row->currencyId = $dRec->currencyId;
        
        $invoiceValue = $dRec->invoiceValue + $dRec->invoiceVat;
        
        $row->invoiceValue = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceValue);
        
        $row->invoiceCurrentSumm = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->invoiceCurrentSumm);
        
        if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
            
            $row->dueDate .= " *";
        }
        
        foreach ($dRec->payDocuments as $v) {
            
            $paidAmount += $v->amount;
        }
        
        $row->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal($paidAmount);
        
        $amountsValiors = explode(',', $dRec->paidAmounts);
        
        foreach ($amountsValiors as $v) {
            
            $row->paidDates .= "<span class= 'small'>" . $Date->toVerbal($v) . "</span>" . "<br>";
        }
        
        return $row;
    }
}



