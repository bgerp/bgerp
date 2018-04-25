<?php

/**
 * Мениджър на отчети за просрочени фактури
 *
 * @category  bgerp
 * @package   sales
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продажби » Просрочени фактури
 */
class sales_reports_OverdueInvoices extends frame2_driver_TableData
{

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,salesMaster,acc';

    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'contragentId';

    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'countryGroup,checkDate,';

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset            
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('checkDate', 'date', 'caption=Към дата,after=contragent,mandatory,single=none');
        $fieldset->FLD('countryGroup', 'key(mvc=drdata_CountryGroups,select=name, allowEmpty)', 
            'caption=Група държави,after=checkDate');
        $fieldset->FLD('salesTotalOverDue', 'double', 'input=none,single=none');
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
        $isRec = array();
        
        // Масив със записи от изходящи фактури
        $sRecs = array();
        
        $sQuery = sales_Invoices::getQuery();
        
        $sQuery->where("#state = 'active'");
        
        $sQuery->where(array(
            "#createdOn < '[#1#]'",
            $rec->checkDate . ' 23:59:59'
        ));
        
        // Фактури ПРОДАЖБИ
        while ($salesAllInvoices = $sQuery->fetch()) {
            
            $salesInvoicesArr[] = $salesAllInvoices;
        }
        
        foreach ($salesInvoicesArr as $salesInvoices) {
            
            $timeLimit = count($salesInvoicesArr) * 0.05;
            
            if ($timeLimit >= 30) {
                core_App::setTimeLimit($timeLimit);
            }
            
            $cQuery = crm_ext_ContragentInfo::getQuery();
            
            $cQuery->where("#contragentId = {$salesInvoices->contragentId}");
            
            if ($rec->countryGroup) {
                
                $countriesArr = drdata_CountryGroups::fetch($rec->countryGroup)->countries;
                
                if (! keylist::isIn($salesInvoices->contragentCountryId, $countriesArr))
                    continue;
            }
            
            if ($cQuery->fetch()->overdueSales != 'yes')
                continue;
            
            if (sales_Sales::fetch(doc_Threads::getFirstDocument($salesInvoices->threadId)->that)->state == 'closed') {
                
                if (sales_Sales::fetch(doc_Threads::getFirstDocument($salesInvoices->threadId)->that)->closedOn >=
                     $rec->checkDate) {
                    
                    $threadsId[$salesInvoices->threadId] = $salesInvoices->threadId;
                    continue;
                }
                
                continue;
            }
            
            $threadsId[$salesInvoices->threadId] = $salesInvoices->threadId;
        }
        
        $salesTotalOverDue = 0;
        
        if (is_array($threadsId)) {
            foreach ($threadsId as $thread) {
                
                // масив от фактури в тази нишка //
                // $invoicesInThread = (deals_Helper::getInvoicesInThread ( $thread, $rec->checkDate, TRUE, TRUE, TRUE ));
                
                $invoicePayments = (deals_Helper::getInvoicePayments($thread, $rec->checkDate));
                
                if (is_array($invoicePayments)) {
                    
                    // фактура от нишката и масив от платежни документи по тази фактура//
                    foreach ($invoicePayments as $inv => $paydocs) {
                        
                        if (($paydocs->payout > $paydocs->amount - 0.01) && ($paydocs->payout < $paydocs->amount + 0.01))
                            continue;
                        
                        $Invoice = doc_Containers::getDocument($inv);
                        
                        if ($Invoice->className != 'sales_Invoices')
                            continue;
                        
                        $iRec = $Invoice->fetch(
                            'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,currencyId,date,dueDate,contragentId');
                        
                        if ($iRec->dueDate && ($paydocs->amount - $paydocs->payout) > 0 &&
                             $iRec->dueDate < $rec->checkDate) {
                            
                            $salesTotalOverDue += ($paydocs->amount - $paydocs->payout);
                        } else
                            continue;
                            // масива с фактурите за показване
                        if (! array_key_exists($iRec->id, $sRecs)) {
                            
                            $sRecs[$iRec->id] = (object) array(
                                'threadId' => $thread,
                                'className' => $Invoice->className,
                                'invoiceId' => $iRec->id,
                                'invoiceNo' => $iRec->number,
                                'contragentId' => $iRec->contragentId,
                                'invoiceDate' => $iRec->date,
                                'dueDate' => $iRec->dueDate,
                                'invoiceContainerId' => $iRec->containerId,
                                'currencyId' => $iRec->currencyId,
                                'rate' => $iRec->rate,
                                'invoiceValue' => $paydocs->amount,
                                'invoiceVAT' => $iRec->vatAmount,
                                'invoicePayout' => $paydocs->payout,
                                'invoiceCurrentSumm' => $paydocs->amount - $paydocs->payout,
                                'payDocuments' => $paydocs->used
                            );
                        }
                    }
                }
            }
        }
        
        $rec->salesTotalOverDue = $salesTotalOverDue;
        
        if (count($sRecs)) {
            
            arr::natOrder($sRecs, 'invoiceDate');
        }
        
        $recs = $sRecs;
        
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
            
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
            $fld->FLD('contragentId', 'varchar', 'caption=Контрагент');
            $fld->FLD('invoiceDate', 'varchar', 'caption=Дата');
            $fld->FLD('dueDate', 'varchar', 'caption=Краен срок');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност,smartCenter');
            $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->сума,smartCenter');
            $fld->FLD('paidDates', 'varchar', 'caption=Платено->дата,smartCenter');
            $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Неплатено');
        } else {
            
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
            $fld->FLD('invoiceDate', 'date', 'caption=Дата,smartCenter');
            $fld->FLD('contragentId', 'varchar', 'caption=Контрагент');
            $fld->FLD('dueDate', 'date', 'caption=Краен срок,smartCenter');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
            $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платена сума');
            $fld->FLD('paidDates', 'varchar', 'caption=Плащания,smartCenter');
            $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Неплатено');
        }
        return $fld;
    }

    /**
     * Връща платена сума
     *
     * @param stdClass $dRec            
     * @param boolean $verbal            
     * @return mixed $paidAmount
     */
    private static function getPaidAmount($dRec, $verbal = TRUE)
    {
        $paidAmount = $dRec->invoicePayout;
        
        return $paidAmount;
    }

    /**
     * Връща дати на плащания
     *
     * @param stdClass $dRec            
     * @param boolean $verbal            
     * @return mixed $paidDates
     */
    private static function getPaidDates($dRec, $verbal = TRUE)
    {
        if (is_array($dRec->payDocuments)) {
            
            foreach ($dRec->payDocuments as $onePayDoc) {
                $containerArr[] = $onePayDoc->containerId;
                $Document = doc_Containers::getDocument($onePayDoc->containerId);
                
                $payDocClass = $Document->className;
                
                $paidDatesList .= "," . $payDocClass::fetch($Document->that)->valior;
            }
        }
        if ($verbal === TRUE) {
            
            $amountsValiors = explode(",", trim($paidDatesList, ','));
            
            foreach ($amountsValiors as $v) {
                
                $paidDate = dt::mysql2verbal($v, $mask = "d.m.y");
                
                $paidDates .= "$paidDate" . "<br>";
            }
        } else {
            $amountsValiors = explode(",", trim($paidDatesList, ','));
            
            foreach ($amountsValiors as $v) {
                
                $paidDate = dt::mysql2verbal($v, $mask = "d.m.y");
                
                $paidDates .= "$paidDate" . "\n\r";
            }
        }
        return $paidDates;
    }

    /**
     * Връща просрочие на плащане
     *
     * @param stdClass $dRec            
     * @param boolean $verbal            
     * @return mixed $dueDate
     */
    private static function getDueDate($dRec, $verbal = TRUE, $rec)
    {
        if ($verbal === TRUE) {
            
            if ($dRec->dueDate) {
                $dueDate = dt::mysql2verbal($dRec->dueDate, $mask = "d.m.y");
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
        
        $invoiceNo = str_pad($dRec->invoiceNo, 10, "0", STR_PAD_LEFT);
        
        $row->invoiceNo = ht::createLink($invoiceNo, 
            array(
                $dRec->className,
                'single',
                $dRec->invoiceId
            ));
        
        $row->invoiceDate = $Date->toVerbal($dRec->invoiceDate);
        
        $row->dueDate = self::getDueDate($dRec, TRUE, $rec);
        
        $row->contragentId = crm_Companies::getTitleById($dRec->contragentId);
        
        $row->currencyId = $dRec->currencyId;
        
        $invoiceValue = $dRec->invoiceValue + $dRec->invoiceVat;
        
        $row->invoiceValue = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceValue);
        
        if ($dRec->invoiceCurrentSumm > 0) {
            $row->invoiceCurrentSumm = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->invoiceCurrentSumm);
        }
        
        if ($dRec->invoiceCurrentSumm < 0) {
            $invoiceOverSumm = - 1 * $dRec->invoiceCurrentSumm;
            $row->invoiceOverSumm = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceOverSumm);
        }
        $row->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal(self::getPaidAmount($dRec));
        
        $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, TRUE) . "</span>";
        
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
        $Date = cls::get('type_Date');
        $fieldTpl = new core_ET(
            tr(
                "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN contragent-->|Контрагент|*: <b>[#contragent#]</b><!--ET_END to--></div></small>
        		                <small><div><!--ET_BEGIN contragent-->|Към дата|*: <b>[#checkDate#]</b><!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN salesTotalOverDue-->|фактури ПРОДАЖБИ »   ПРОСРОЧЕНИ|*: <b>[#salesTotalOverDue#]</b><!--ET_END to--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->contragent)) {
            $fieldTpl->append(doc_Folders::fetch($data->rec->contragent)->title, 'contragent');
        } else {
            $fieldTpl->append('Всички', 'contragent');
        }
        
        if (isset($data->rec->checkDate)) {
            $fieldTpl->append(dt::mysql2verbal($data->rec->checkDate, $mask = "d.m.Y"), 'checkDate');
        }
        
        if (isset($data->rec->salesTotalOverDue)) {
            $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesTotalOverDue), 
                'salesTotalOverDue');
        }
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
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
        $res->paidAmount = (self::getPaidAmount($dRec));
        
        $res->paidDates = self::getPaidDates($dRec, FALSE);
        
        $res->dueDate = self::getDueDate($dRec, FALSE, $rec);
        
        if ($dRec->invoiceCurrentSumm < 0) {
            $invoiceOverSumm = - 1 * $dRec->invoiceCurrentSumm;
            $res->invoiceCurrentSumm = '';
            $res->invoiceOverSumm = ($invoiceOverSumm);
        }
        
        if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
            
            $res->dueDateStatus = 'Просрочен';
        }
        
        $invoiceNo = str_pad($dRec->invoiceNo, 10, "0", STR_PAD_LEFT);
        
        $res->invoiceNo = $invoiceNo;
        
        $contragentName = crm_Companies::getTitleById($dRec->contragentId);
        
        $res->contragentId = $contragentName;
    }

    /**
     * Връща следващите три дати, когато да се актуализира справката
     *
     * @param stdClass $rec
     *            - запис
     * @return array|FALSE - масив с три дати или FALSE ако не може да се обновява
     */
    public function getNextRefreshDates($rec)
    {
        $date = new DateTime(dt::now());
        $toAdd = 25 - $date->format(H);
        $interval = 'PT' . $toAdd . 'H';
        $date->add(new DateInterval($interval));
        $d1 = $date->format('Y-m-d H:i:s');
        $date->add(new DateInterval($interval));
        $d2 = $date->format('Y-m-d H:i:s');
        $date->add(new DateInterval($interval));
        $d3 = $date->format('Y-m-d H:i:s');
        
        return array(
            $d1,
            $d2,
            $d3
        );
        
        bp($d1);
    }
}



