<?php


/**
 * Мениджър на отчети за просрочени фактури
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
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
    protected $groupByField;
    
    
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
        $fieldset->FLD('checkDate', 'date', 'caption=Към дата,after=title,mandatory,single=none');
        $fieldset->FLD('additional', 'table(columns=limit1|limit2,captions=Праг 1|Праг 2,widths=3em|3em,btnOff,unit=дни просрочие)', 'caption=Периоди||Additional,autohide,advanced,after=checkDate,single=none');
        $fieldset->FLD('typeGrupping', 'enum(contragent=Контрагент,overduePeriod=Период на просрочие)', 'caption=Групиране,maxRadio=2,columns=2,after=additional');
        $fieldset->FLD('dealer', 'user(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Филтри->Търговец,placeholder=Всички,single=none,after=typeGrupping,input');
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Филтри->Контрагент,placeholder=Всички,single=none,after=dealer');
        $fieldset->FLD(
            'countryGroup',
            'key(mvc=drdata_CountryGroups,select=name, allowEmpty)',
            'caption=Група държави,placeholder=Всички,single=none,after=contragent'
            );
        $fieldset->FNC('salesTotalOverDue', 'double', 'caption=Общо просрочени,input=none,single=none');
        $fieldset->FNC('salesTotalPayout', 'double', 'caption=Общо плащания,input=none,single=none');
        $fieldset->FNC('salesCurrentSum', 'double', 'caption=Общо неплатени,input=none,single=none');
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
        
        $checkDate = dt::today();
        $form->setDefault('checkDate', "{$checkDate}");
        $form->setDefault('typeGrupping', 'contragent');
        
        $salesQuery = sales_Sales::getQuery();
        
        $salesQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');
        
        $salesQuery->groupBy('folderId');
        
        $salesQuery->show('folderId, contragentId, folderTitle');
        
        while ($contragent = $salesQuery->fetch()) {
            if (!is_null($contragent->contragentId)) {
                $suggestions[$contragent->folderId] = $contragent->folderTitle;
            }
        }
        
        asort($suggestions);
        
        $form->setSuggestions('contragent', $suggestions);
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
       $this->groupByField = $rec->typeGrupping;
        $recs = array();
        $isRec = array();
        
        // Масив със записи от изходящи фактури
        $sRecs = array();
        
        $salQuery = sales_Sales::getQuery();
        
        $salQuery->where("#closedDocuments != ''");
        
        //Масив с затварящи документи по обединени договори //
        $salesUN = array();
        
        while ($sale = $salQuery->fetch()) {
            foreach ((keylist::toArray($sale->closedDocuments)) as $v) {
                $salesUN[$v] = ($v);
            }
        }
        
        $salesUNList = keylist::fromArray($salesUN);
        
        
        $sQuery = sales_Invoices::getQuery();
        
        $sQuery->where("#state = 'active'");
       
        $sQuery->where(array(
            "#dueDate IS NOT NULL AND #dueDate < '[#1#]'",
            $rec->checkDate . ' 23:59:59'
        ));
        
        // Фактури ПРОДАЖБИ
        while ($saleInvoice = $sQuery->fetch()) {
            if ($rec->contragent && (!keylist::isIn($saleInvoice->folderId, $rec->contragent))) {
                continue;
            }
            
            $salesInvoicesArr[] = $saleInvoice;
        }
        
        $timeLimit = countR($salesInvoicesArr) * 0.05;
        
        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }
        
        $cQuery = crm_ext_ContragentInfo::getQuery();
        
        $cQuery->where("#overdueSales = 'yes'");
        
        while ($contragent = $cQuery->fetch()) {
        
            $contragentKey = $contragent->contragentClassId.'|'.$contragent->contragentId;
            
            $contragentsArr[$contragentKey] = $contragentKey;
        }
       
        
        if ($rec->countryGroup) {
            $countriesList = drdata_CountryGroups::fetch($rec->countryGroup)->countries;
        }
        
        if (is_array($salesInvoicesArr)){
            
            $threadsId = array();
            foreach ($salesInvoicesArr as $saleInvoice) {
            
                $saleInvoiceContragrntKey = $saleInvoice->contragentClassId.'|'.$saleInvoice->contragentId;
                
                if (!in_array($saleInvoiceContragrntKey, $contragentsArr)) {
                    continue;
                }
                
                
                if ($rec->countryGroup) {
                    if (! keylist::isIn($saleInvoice->contragentCountryId, $countriesList)) {
                        continue;
                    }
                }
                
                
                $firstDocument = doc_Threads::getFirstDocument($saleInvoice->threadId);
                
                $className = $firstDocument->className;
                
                //Филтър по дилър
                if ($rec->dealer) {
                    if ($className::fetchField($firstDocument->that, 'dealerId') != $rec->dealer) {
                        continue;
                    }
                }
                
                //Проверка дали е затворена или обединяваща
                $unitedCheck = keylist::isIn($className::fetchField($firstDocument->that), $salesUNList);
                
                if (($className::fetchField($firstDocument->that, 'state') == 'closed') && ! $unitedCheck) {
                    continue;
                }
                
                //масив с нишките за проверка
                $threadsId[$saleInvoice->threadId] = $saleInvoice->threadId;
            }
        }
        
        $salesTotalOverDue = $salesTotalPayout = 0;
        
        if (is_array($threadsId)) {
            foreach ($threadsId as $thread) {
                
                // масив от фактури в тази нишка към избраната дата
                $invoicePayments = (deals_Helper::getInvoicePayments($thread, $rec->checkDate));
                
                if (is_array($invoicePayments)) {
                    
                    // фактура от нишката и масив от платежни документи по тази фактура//
                    foreach ($invoicePayments as $inv => $paydocs) {
                        
                        if (($paydocs->payout >= $paydocs->amount - 0.01) &&
                            ($paydocs->payout <= $paydocs->amount + 0.01)) {
                            continue;
                        }
                        
                        $Invoice = doc_Containers::getDocument($inv);
                        
                        if ($Invoice->className != 'sales_Invoices') {
                            continue;
                        }
                        
                        $iRec = $Invoice->fetch(
                            'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,currencyId,date,dueDate,contragentId,contragentClassId'
                        
                            );
                        
                        $contragentClassName = core_Classes::fetch($iRec->contragentClassId)->name;
                        
                        $contragentFolderId = $contragentClassName::fetch($iRec->contragentId)->folderId;
                        
                        $overdueColor = '';
                        $limits = json_decode($rec->additional);
                        list($limit1) = $limits->limit1;
                        list($limit2) = $limits->limit2;
                        
                        if ($iRec->dueDate && ($paydocs->amount - $paydocs->payout) > 0 &&
                            $iRec->dueDate < $rec->checkDate) {
                            $overdueDays = dt::daysBetween($rec->checkDate, $iRec->dueDate);
                            
                            if ($overdueDays <= $limit1) {
                                $overduePeriod = 'до '.$limit1;
                                $overColor = 'green';
                            }
                            
                            if (($overdueDays > $limit1) && ($overdueDays <= $limit2)) {
                                $overduePeriod = $limit1.' - '.$limit2;
                                $overColor = 'orange';
                            }
                            
                            if ($overdueDays > $limit2) {
                                $overduePeriod = 'над '.$limit2;
                                $overColor = 'red';
                            }
                            
                            $invoiceCurrentSummArr[$contragentFolderId] += ($paydocs->amount - $paydocs->payout);
                        } else {
                            continue;
                        }
                        
                        $salesTotalOverDue += $paydocs->amount*$iRec->rate;      // Обща стойност на просрочените фактури преизчислени в основна валута
                        $salesTotalPayout += $paydocs->payout*$iRec->rate;       // Обща стойност на плащанията по просрочените фактури преизчислени в основна валута
                        
                        // масива с фактурите за показване
                        if (! array_key_exists($iRec->id, $sRecs)) {
                            $sRecs[$iRec->id] = (object) array(
                                'threadId' => $thread,
                                'className' => $Invoice->className,
                                'invoiceId' => $iRec->id,
                                'invoiceNo' => $iRec->number,
                                'overdueDays' => $overdueDays,
                                'overduePeriod' => $overduePeriod,
                                'overColor' => $overColor,
                                'contragentId' => $iRec->contragentId,
                                'contragentClassId' => $iRec->contragentClassId,
                                'contragent' => $contragentFolderId,
                                'invoiceDate' => $iRec->date,
                                'dueDate' => $iRec->dueDate,
                                'invoiceContainerId' => $iRec->containerId,
                                'currencyId' => $iRec->currencyId,
                                'rate' => $iRec->rate,
                                'invoiceValue' => $paydocs->amount,
                                'invoiceVAT' => $iRec->vatAmount,
                                'invoicePayout' => $paydocs->payout,
                                'invoiceCurrentSumm' => $paydocs->amount - $paydocs->payout,
                                'invoiceCurrentSummArr' => $invoiceCurrentSummArr,
                                'payDocuments' => $paydocs->used
                            );
                        }
                    }
                }
            }
        }
        
        $rec->salesTotalOverDue = $salesTotalOverDue;
        $rec->salesTotalPayout = $salesTotalPayout;
        $rec->salesCurrentSum = $salesTotalOverDue - $salesTotalPayout;
        
        if (countR($sRecs)) {
            arr::sortObjects($sRecs, 'overdueDays', 'desc');
        }
        
        $recs = $sRecs;
        
        if (is_array($invoiceCurrentSummArr)) {
            arsort($invoiceCurrentSummArr);
            
            foreach ($invoiceCurrentSummArr as $k => $v) {
                foreach ($recs as $key => $val) {
                    if ($val->contragent == $k) {
                        $val->invoiceCurrentSummArr = $invoiceCurrentSummArr;
                        
                        $rTemp[] = $val;
                    }
                }
            }
            
           
            $recs = $rTemp;
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
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент');
            $fld->FLD('invoiceDate', 'varchar', 'caption=Дата');
            $fld->FLD('dueDate', 'varchar', 'caption=Краен срок');
            $fld->FLD('overdueDays', 'varchar', 'caption=Дни,smartCenter');
            if (!is_null($rec->additional)) {
                $fld->FLD('overduePeriod', 'varchar', 'caption=Дни,smartCenter');
            }
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност,smartCenter');
            $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->сума,smartCenter');
            $fld->FLD('paidDates', 'varchar', 'caption=Платено->дата,smartCenter');
            $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Неплатено');
        } else {
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
            $fld->FLD('invoiceDate', 'date', 'caption=Дата,smartCenter');
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент');
            $fld->FLD('dueDate', 'date', 'caption=Краен срок,smartCenter');
            $fld->FLD('overdueDays', 'varchar', 'caption=Дни');
            if (!is_null($rec->additional)) {
                $fld->FLD('overduePeriod', 'varchar', 'caption=Дни,smartCenter');
            }
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
     * @param bool     $verbal
     *
     * @return mixed $paidAmount
     */
    private static function getPaidAmount($dRec, $verbal = true)
    {
        $paidAmount = $dRec->invoicePayout;
        
        return $paidAmount;
    }
    
    
    /**
     * Връща дати на плащания
     *
     * @param stdClass $dRec
     * @param bool     $verbal
     *
     * @return mixed $paidDates
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
        $row = new stdClass();
        
        $invoiceNo = str_pad($dRec->invoiceNo, 10, '0', STR_PAD_LEFT);
        
        $row->invoiceNo = ht::createLink(
            
            $invoiceNo,
            array(
                $dRec->className,
                'single',
                $dRec->invoiceId
            )
        
        );
        
        $row->invoiceDate = $Date->toVerbal($dRec->invoiceDate);
        
        $row->dueDate = self::getDueDate($dRec, true, $rec);
        
        $row->overdueDays = ($dRec->overdueDays);
        
        if ($dRec->contragent) {
            $className = core_Classes::fetchField($dRec->contragentClassId, 'name');
            
            if ($rec->data->groupByField == 'contragent') {
                $row->overduePeriod = "<span style=\"color:{$dRec->overColor}\">".$dRec->overduePeriod.'</span>';
                $row->contragent = doc_Folders::getTitleById($dRec->contragent) .
            "<span class= 'fright'><span class= 'quiet'>" . 'Общо ПРОСРОЧЕНИ фактури: ' . '</span>' .
            core_Type::getByName('double(decimals=2)')->toVerbal($dRec->invoiceCurrentSummArr[$dRec->contragent]) .
            ' ' . "{$dRec->currencyId}" . '</span>';
            } else {
                $row->overduePeriod = 'Просрочие '.$dRec->overduePeriod.' дни';
                $row->contragent = doc_Folders::getTitleById($dRec->contragent);
            }
        } else {
            $row->contragent = 'error';
        }
        
        $row->currencyId = $dRec->currencyId;
        
        $invoiceValue = $dRec->invoiceValue + $dRec->invoiceVat;
        
        $row->invoiceValue = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceValue);
        
        if ($dRec->invoiceCurrentSumm > 0) {
            if ($dRec->invoiceCurrentSumm > $dRec->invoiceValue) {
                $row->invoiceCurrentSumm = "<span class= 'red'>" . core_Type::getByName('double(decimals=2)')->toVerbal(
                    $dRec->invoiceCurrentSumm
                
                ) . '</span>';
            } else {
                $row->invoiceCurrentSumm = core_Type::getByName('double(decimals=2)')->toVerbal(
                    $dRec->invoiceCurrentSumm
                );
            }
        }
        
        if (self::getPaidAmount($dRec) == 0) {
            $row->paidAmount = "<span class= 'small quiet'>" . core_Type::getByName('double(decimals=2)')->toVerbal(
                self::getPaidAmount($dRec)
            ) . '</span>';
        } else {
            $row->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal(self::getPaidAmount($dRec));
        }
        $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, true) . '</span>';
        
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
        $Date = cls::get('type_Date');
        $fieldTpl = new core_ET(
            tr(
                "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN checkDate-->|Към дата|*: <b>[#checkDate#]</b><!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN contragent-->|Контрагент|*: <b>[#contragent#]</b><!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN dealer-->|Търговец|*: <b>[#dealer#]</b><!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN countryGroup-->|Група държави|*: <b>[#countryGroup#]</b><!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN salesTotalOverDue-->|Общо просрочени|*: <b>[#salesTotalOverDue#]</b><!--ET_END salesTotalOverDue--></div></small>
                                <small><div><!--ET_BEGIN salesTotalPayout-->|Общо платено|*: <b>[#salesTotalPayout#]</b><!--ET_END salesTotalPayout--></div></small>
                                <small><div><!--ET_BEGIN salesCurrentSum-->|Общо за плащане|*: <b>[#salesCurrentSum#]</b><!--ET_END salesCurrentSum--></div></small>
                                </fieldset><!--ET_END BLOCK-->"
            )
        );
        
        //Показва контрагента
        if (isset($data->rec->contragent)) {
            foreach (keylist::toArray($data->rec->contragent) as $v) {
                $contragents .= doc_Folders::fetchField($v, 'title').', ';
            }
            $fieldTpl->append(trim($contragents, ', '), 'contragent');
        } else {
            $fieldTpl->append('Всички', 'contragent');
        }
        
        //Показва търговеца
        if (isset($data->rec->dealer)) {
            $fieldTpl->append(core_Users::fetchField($data->rec->dealer, 'names'), 'dealer');
        } else {
            $fieldTpl->append('Всички', 'dealer');
        }
        
        //Показва групата държави
        if (isset($data->rec->countryGroup)) {
            $fieldTpl->append(drdata_CountryGroups::fetch($data->rec->countryGroup)->name, 'countryGroup');
        } else {
            $fieldTpl->append('Всички', 'countryGroup');
        }
        
        if (isset($data->rec->checkDate)) {
            $fieldTpl->append(dt::mysql2verbal($data->rec->checkDate, $mask = 'd.m.Y'), 'checkDate');
        }
        
        $baseCurrency = acc_Periods::getBaseCurrencyCode();
        
        if (isset($data->rec->salesTotalOverDue)) {
            $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesTotalOverDue)." $baseCurrency", 'salesTotalOverDue');
        }
        
        if (isset($data->rec->salesTotalPayout)) {
            $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesTotalPayout)." $baseCurrency", 'salesTotalPayout');
        }
        
        if (isset($data->rec->salesCurrentSum)) {
            $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesCurrentSum)." $baseCurrency", 'salesCurrentSum');
        }
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
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
        
        $contragentName = crm_Companies::getTitleById($dRec->contragentId);
        
        $res->contragentId = $contragentName;
    }
    
    
    /**
     * Връща следващите три дати, когато да се актуализира справката
     *
     * @param stdClass $rec
     *                      - запис
     *
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
    }
}
