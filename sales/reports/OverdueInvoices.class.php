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
    public $canSelectDriver = 'ceo,sales,acc';


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
        $fieldset->FLD('checkDate', 'date', 'caption=Към дата,after=title,single=none');
        $fieldset->FLD('additional', 'table(columns=limit1|limit2,captions=Праг 1|Праг 2,widths=3em|3em,btnOff,unit=дни просрочие)', 'caption=Периоди||Additional,autohide,advanced,after=checkDate,single=none');
        $fieldset->FLD('typeGrupping', 'enum(contragent=Контрагент,overduePeriod=Период на просрочие)', 'caption=Групиране,maxRadio=2,columns=2,after=additional');
        $fieldset->FLD('dealer', 'user(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Филтри->Търговец,placeholder=Всички,single=none,after=typeGrupping,input');
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Филтри->Контрагент,placeholder=Всички,single=none,after=dealer');
        $fieldset->FLD('countryGroup', 'key(mvc=drdata_CountryGroups,select=name,allowEmpty)', 'caption=Филтри->Група държави,single=none,placeholder=Всички,after=contragent'
        );

        //Праг за минимална просрочена сума за показване
        $fieldset->FLD('minOverdueLevev', 'double', 'caption=Филтри->Без просрочените под,unit=лв,after=countryGroup,placeholder=0.00,silent,single=none');


        $fieldset->FLD('listForEmail', 'blob', 'caption=Списък за имейл,single=none,after=countryGroup,input=hidden');
        $fieldset->FLD('excludedFromEmail', 'text', 'caption=Изключени за имейл фирми,single=none,after=listForEmail,input=hidden');
        $fieldset->FLD('unsentEmails', 'blob', 'caption=Неизпратени имейли,single=none,after=listForEmail,input=hidden');
        $fieldset->FLD('blastId', 'int', 'caption=Последен документ,single=none,after=unsentEmails,input=hidden');
        $fieldset->FLD('minSumForEmail', 'double', 'caption=Минимално задължение за имейл,single=none,after=blastId,input=hidden');

        $fieldset->FNC('salesTotalOverDue', 'double', 'caption=Общо просрочени,input=none,single=none');
        $fieldset->FNC('salesTotalPayout', 'double', 'caption=Общо плащания,input=none,single=none');
        $fieldset->FNC('salesCurrentSum', 'double', 'caption=Общо неплатени,input=none,single=none');
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

        $form->setDefault('typeGrupping', 'contragent');
        $form->setDefault('minSumForEmail', 0.05);

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
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {

        $rec = $form->rec;
        if ($form->isSubmitted()) {

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
        core_App::setTimeLimit(100);

        if (!$rec->checkDate) {
            $checkDate = dt::now();
        } else {
            $checkDate = $rec->checkDate . ' 23:59:59';
        }

        $this->groupByField = $rec->typeGrupping;
        $recs = array();
        $isRec = array();

        // Масив със записи от изходящи фактури
        $sRecs = array();

        $salQuery = sales_Sales::getQuery();

        $salQuery->in('state', array('rejected', 'draft'), true);

        $salQuery->where("#closedOn IS NULL OR #closedOn > '$checkDate'");

        //нишки на активни договори
        $threadsActivSalesArr = arr::extractValuesFromArray($salQuery->fetchAll(), 'threadId');

        $salesTotalOverDue = $salesTotalPayout = 0;
        $invoiceCurrentSummArr = array();




        if (is_array($threadsActivSalesArr)) {

            // Синхронизира таймлимита с броя записи //
            $maxTimeLimit = countR($threadsActivSalesArr) * 5;
            $maxTimeLimit = max(array($maxTimeLimit, 300));
            core_App::setTimeLimit($maxTimeLimit);


            foreach ($threadsActivSalesArr as $thread) {

                //Договора за продажба
                $FirstDoc = doc_Threads::getFirstDocument($thread);
                if($FirstDoc && isset($FirstDoc) && is_object($FirstDoc)){
                    $fDocRec = $FirstDoc->fetch();                       // Rec-a на договора
                }else continue;


                // масив от фактури в тази нишка към избраната дата
                $invoicePayments = (deals_Helper::getInvoicePayments($thread, $checkDate));

                if (is_array($invoicePayments) && !empty($invoicePayments)) {


                    // фактура от нишката и масив от платежни документи по тази фактура//
                    foreach ($invoicePayments as $inv => $paydocs) {

                        $invoiceCurrentSumm = 0;

                        if (($paydocs->payout >= $paydocs->amount - 0.01) &&
                            ($paydocs->payout <= $paydocs->amount + 0.01)) {
                            continue;
                        }

                        $Invoice = doc_Containers::getDocument($inv);

                        if ($Invoice->className != 'sales_Invoices') {
                            continue;
                        }

                        $iRec = $Invoice->fetch(
                            'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,currencyId,date,dueDate,
                                   contragentId,contragentClassId, contragentCountryId'
                        );

                        $contragentClassName = core_Classes::fetch($iRec->contragentClassId)->name;

                        $contragentRec = $contragentClassName::fetch($iRec->contragentId);

                        $contragentFolderId = $contragentRec->folderId;

                        //Филтър по контрагент
                        if ($rec->contragent && (!in_array($contragentFolderId, keylist::toArray($rec->contragent)))) continue;

                        //Филтър по дилър
                        if ($rec->dealer && ($rec->dealer != $fDocRec->dealerId)) continue;

                        //Филтър по група държави
                        if ($rec->countryGroup) {
                            $countriesList = drdata_CountryGroups::fetch($rec->countryGroup)->countries;

                            if (!keylist::isIn($iRec->contragentCountryId, $countriesList)) {
                                continue;
                            }
                        }

                        $overdueColor = '';
                        $limits = json_decode($rec->additional);
                        list($limit1) = $limits->limit1;
                        list($limit2) = $limits->limit2;

                        if ($iRec->dueDate && ($paydocs->amount - $paydocs->payout) > $rec->minOverdueLevev &&
                            $iRec->dueDate < $checkDate) {
                            $overdueDays = dt::daysBetween($checkDate, $iRec->dueDate);

                            if ($overdueDays <= $limit1) {
                                $overduePeriod = 'до ' . $limit1;
                                $overColor = 'green';
                            }

                            if (($overdueDays > $limit1) && ($overdueDays <= $limit2)) {
                                $overduePeriod = $limit1 . ' - ' . $limit2;
                                $overColor = 'orange';
                            }

                            if ($overdueDays > $limit2) {
                                $overduePeriod = 'над ' . $limit2;
                                $overColor = 'red';
                            }

                            $invoiceCurrentSumm = $paydocs->amount - $paydocs->payout;

                            $invoiceCurrentSummArr[$contragentFolderId] += $invoiceCurrentSumm;
                        } else {
                            continue;
                        }

                        $salesTotalOverDue += $paydocs->amount * $iRec->rate;      // Обща стойност на просрочените фактури преизчислени в основна валута
                        $salesTotalPayout += $paydocs->payout * $iRec->rate;       // Обща стойност на плащанията по просрочените фактури преизчислени в основна валута

                        // масива с фактурите за показване
                        if (!array_key_exists($iRec->id, $sRecs)) {
                            $sRecs[$iRec->id] = (object)array(
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
     * @param bool $export
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
            $fld->FLD('invoiceValue', 'double(decimals=2)', 'caption=Стойност,smartCenter');
            $fld->FLD('paidAmount', 'double(decimals=2)', 'caption=Платено->сума,smartCenter');
            $fld->FLD('paidDates', 'varchar', 'caption=Платено->дата,smartCenter');
            $fld->FLD('invoiceCurrentSumm', 'double(decimals=2)', 'caption=Неплатено');
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
            $fld->FLD('invoiceValue', 'double(decimals=2)', 'caption=Стойност');
            $fld->FLD('paidAmount', 'double(decimals=2)', 'caption=Платена сума');
            $fld->FLD('paidDates', 'varchar', 'caption=Плащания,smartCenter');
            $fld->FLD('invoiceCurrentSumm', 'double(decimals=2)', 'caption=Неплатено');
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
        $paidAmount = $dRec->invoicePayout;

        return $paidAmount;
    }


    /**
     * Връща дати на плащания
     *
     * @param stdClass $dRec
     * @param bool $verbal
     *
     * @return mixed $paidDates
     */
    private static function getPaidDates($dRec, $verbal = true)
    {
        if (is_array($dRec->payDocuments)) {
            foreach ($dRec->payDocuments as $onePayDoc) {
                if (!is_null($onePayDoc->containerId)) {
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
     * @param bool $verbal
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
                $row->overduePeriod = "<span style=\"color:{$dRec->overColor}\">" . $dRec->overduePeriod . '</span>';
                $row->contragent = doc_Folders::getTitleById($dRec->contragent) .
                    "<span class= 'fright'><span class= 'quiet'>" . 'Общо ПРОСРОЧЕНИ фактури: ' . '</span>' .
                    core_Type::getByName('double(decimals=2)')->toVerbal($dRec->invoiceCurrentSummArr[$dRec->contragent]) .
                    ' ' . "{$dRec->currencyId}" . '</span>';
            } else {
                $row->overduePeriod = 'Просрочие ' . $dRec->overduePeriod . ' дни';
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
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        if (!$data->rec->checkDate) {
            $checkDate = dt::now();
        } else {
            $checkDate = $data->rec->checkDate;
        }

        $Date = cls::get('type_Date');
        $fieldTpl = new core_ET(
            tr(
                "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN checkDate--><div>|Към дата|*: <b>[#checkDate#]</b></div><!--ET_END checkDate-->
                                        <!--ET_BEGIN contragent--><div>|Контрагент|*: <b>[#contragent#]</b></div><!--ET_END to-->
                                        <!--ET_BEGIN dealer--><div>|Търговец|*: <b>[#dealer#]</b></div><!--ET_END to-->
                                        <!--ET_BEGIN countryGroup--><div>|Група държави|*: <b>[#countryGroup#]</b></div><!--ET_END to-->
                                        <!--ET_BEGIN salesTotalOverDue--><div>|Общо просрочени|*: <b>[#salesTotalOverDue#]</b></div><!--ET_END salesTotalOverDue-->
                                        <!--ET_BEGIN salesTotalPayout--><div>|Общо платено|*: <b>[#salesTotalPayout#]</b></div><!--ET_END salesTotalPayout-->
                                        <!--ET_BEGIN salesCurrentSum--><div>|Общо за плащане|*: <b>[#salesCurrentSum#]</b></div><!--ET_END salesCurrentSum-->
                                        <!--ET_BEGIN excludedFromEmail--><div>|Изключени от имейла|*: <b>[#excludedFromEmail#]</b></div><!--ET_END excludedFromEmail-->
                                        <!--ET_BEGIN unsentEmails--><div>|Неизпратени имейли|*: <b>[#unsentEmails#]</b></div><!--ET_END unsentEmails-->
                                        <!--ET_BEGIN blastId--><div>|Последен документ|*: <b>[#blastId#]</b></div><!--ET_END blastId-->
                                        <!--ET_BEGIN button--><div>| |* [#button#]</div><!--ET_END button-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"
            )
        );

        //Показва контрагента
        if (isset($data->rec->contragent)) {
            foreach (keylist::toArray($data->rec->contragent) as $v) {
                $contragents .= doc_Folders::fetchField($v, 'title') . ', ';
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


        $fieldTpl->append($Date->toVerbal($checkDate), 'checkDate');


        $baseCurrency = acc_Periods::getBaseCurrencyCode();

        if (isset($data->rec->salesTotalOverDue)) {
            $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesTotalOverDue) . " $baseCurrency", 'salesTotalOverDue');
        }

        if (isset($data->rec->salesTotalPayout)) {
            $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesTotalPayout) . " $baseCurrency", 'salesTotalPayout');
        }

        if (isset($data->rec->salesCurrentSum)) {
            $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesCurrentSum) . " $baseCurrency", 'salesCurrentSum');
        }

        $exportUrl = array('sales_reports_OverdueInvoices', 'excludCompanies', 'recId' => $data->rec->id, 'ret_url' => true);
        if (dt::secsBetween(dt::now(), $data->rec->lastRefreshed) > 3600) {
            $worning = "warning='Справката е обновена преди повече от 1 час. Да продължи ли без обновяване?'";
        } else {
            $worning = null;
        }


        $toolbar = cls::get('core_Toolbar');

        if (blast_Emails::haveRightFor('add')) {

            //Изключените контрагенти от имейла
            if (isset($data->rec->excludedFromEmail)) {
                foreach (keylist::toArray($data->rec->excludedFromEmail) as $v) {
                    $exludedContragents .= doc_Folders::fetchField($v, 'title') . ', ';
                }
                $fieldTpl->append(trim($exludedContragents, ', '), 'excludedFromEmail');
            } else {
                $fieldTpl->append('Няма', 'excludedFromEmail');
            }

            //Неизпратени имейли
            if (isset($data->rec->unsentEmails)) {
                foreach (keylist::toArray($data->rec->unsentEmails) as $v) {
                    $unsentEmails .= doc_Folders::fetchField($v, 'title') . ', ';
                }
                $fieldTpl->append(trim($unsentEmails, ', '), 'unsentEmails');
            } else {
                $fieldTpl->append('Няма', 'unsentEmails');
            }

            if (isset($data->rec->blastId)) {
                $link = blast_Emails::getHyperlink($data->rec->blastId);
                $fieldTpl->append(trim($link, ', '), 'blastId');
            }

            $toolbar->addBtn('Циркулярно писмо', toUrl($exportUrl), null, $worning);
        }

        $fieldTpl->append('<b>' . $toolbar->renderHtml() . '</b>', 'button');

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

        $res->paidDates = self::getPaidDates($dRec, false);

        $res->dueDate = self::getDueDate($dRec, false, $rec);

        if ($dRec->invoiceCurrentSumm < 0) {
            $invoiceOverSumm = -1 * $dRec->invoiceCurrentSumm;
            $res->invoiceCurrentSumm = '';
            $res->invoiceOverSumm = ($invoiceOverSumm);
        }

        if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
            $res->dueDateStatus = 'Просрочен';
        }

        $invoiceNo = str_pad($dRec->invoiceNo, 10, '0', STR_PAD_LEFT);

        $res->invoiceNo = $invoiceNo;

        $res->contragent = doc_Folders::getTitleById($dRec->contragent);
    }

    /**
     * Изключване на получатели
     */
    public static function act_ExcludCompanies()
    {
        requireRole('admin,blast');

        expect($recId = Request::get('recId', 'int'));

        $rec = frame2_Reports::fetch($recId);

        $listForEmail = self::createListForEmail($rec);

        if (empty($listForEmail)) {
            return new Redirect(array('frame2_Reports', 'single', $rec->id), 'Липсват контрагенти, на които да се изпратят имейли', 'warning');
        }

        $rec->listForEmail = $listForEmail;

        frame2_Reports::save($rec);


        $form = cls::get('core_Form');

        $form->title = "Подготовка на списък за циркулярен имейл";

        $cSuggestionsArr = array('' => '');

        foreach ($rec->listForEmail as $key => $val) {

            $companyName = doc_Folders::fetch($val['folder'])->title;

            $cSuggestionsArr[$val['folder']] = $companyName;

        }

        $form->FLD('companyFilter', 'keylist(mvc=doc_Folders, select=title)', 'caption=Изключени контрагенти,placeholder = Няма,silent');
        $form->FLD('minSumForEmail', 'double(decimals=2)', 'caption=Минимална сума на задължението,placeholder = Няма,silent');

        $form->setSuggestions('companyFilter', $cSuggestionsArr);
        $form->setDefault('minSumForEmail', 0.05);

        $form->rec->companyFilter = $rec->excludedFromEmail;
        $form->rec->minSumForEmail = $rec->minSumForEmail;

        $mRec = $form->input();

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        if ($form->isSubmitted()) {

            foreach ($rec->listForEmail as $key => $val) {


                if (in_array($val['folder'], keylist::toArray($form->rec->companyFilter))) {

                    $rec->listForEmail[$key]['excludе'] = 'yes';

                } else {

                    $rec->listForEmail[$key]['excludе'] = 'no';
                }
            }


            $rec->excludedFromEmail = $form->rec->companyFilter;
            $rec->minSumForEmail = $form->rec->minSumForEmail;
            frame2_Reports::save($rec);

            $exportUrl = array('sales_reports_OverdueInvoices', 'blast', 'recId' => $rec->id, 'ret_url' => true);
            return new Redirect($exportUrl);
        }
        return $form->renderHtml();

    }

    /**
     * Създаване на списък за циркулярно писмо
     */
    public static function createListForEmail($rec)
    {
        $listForEmail = array();
        $unsentEmails = array();

        if (empty($rec->data->recs)) {
            return $listForEmail;
        }
        //Добавяне в blob полето
        if ($rec->listForEmail) {
            $oldListForEmail = $rec->listForEmail;
        } else {
            $oldListForEmail = array();
        }


        if (!$rec->countryGroup) {
            $emailLanguage = 'bg';
        } else {
            $emailLanguage = (drdata_CountryGroups::fetch($rec->countryGroup)->name == 'България') ? 'bg' : 'en';
        }


        foreach ($rec->data->recs as $dRec) {

            $contragentClassName = core_Classes::fetch($dRec->contragentClassId)->name;

            $contragentRec = $contragentClassName::fetch($dRec->contragentId);

            $countryName = drdata_Countries::fetch($contragentRec->country)->commonName;

            foreach (explode(',', $contragentRec->email) as $email) {

                //Ако има контрагенти без имейл ги изключва и ги записва в полето $rec->unsentEmails
                if ($email == '') {
                    $unsentEmails[$contragentRec->folderId] = $contragentRec->folderId;
                    continue;
                }

                $inv = '#' . sales_Invoices::getHandle($dRec->invoiceId);

                if (!empty($oldListForEmail) && array_key_exists('exclude', $oldListForEmail)) {

                    $excludе = $oldListForEmail[$email]['exclude'];
                } else {
                    $excludе = 'no';
                }

                if (!in_array($email, array_keys($listForEmail))) {

                    $listForEmail[$email] = array('email' => $email,
                        'company' => $contragentRec->name,
                        'folder' => $contragentRec->folderId,
                        'country' => $countryName,
                        'date' => dt::mysql2verbal($rec->lastRefreshed, 'd.m.Y'),
                        'docs' => $inv,
                        'sum' => $dRec->invoiceCurrentSummArr[$dRec->contragent],
                        'currency' => $dRec->currencyId,
                        'excludе' => $excludе,
                    );

                } else {
                    $listForEmail[$email]['docs'] .= ', ' . $inv;

                }
            }
        }

        $rec->unsentEmails = $unsentEmails;
        frame2_Reports::save($rec, 'unsentEmails');

        return $listForEmail;
    }

    function act_Blast()
    {

        requireRole('admin,blast');

        expect($recId = Request::get('recId', 'int'));

        $rec = frame2_Reports::fetch($recId);

        $listForSend = array();

        foreach ($rec->listForEmail as $key => $val) {

            if (($val['excludе'] == 'yes') || ($val['sum'] <= $rec->minSumForEmail)) continue;

            $listForSend[$key] = array('email' => $val['email'],
                'company' => $val['company'],
                'country' => $val['country'],
                'date' => $val['date'],
                'docs' => $val['docs'],
                'sum' => round($val['sum'], 2),
                'currency' => $val['currency'],
            );
        }

        if (empty($listForSend)) {
            return new Redirect(array('frame2_Reports', 'single', $rec->id), 'Липсват контрагенти, на които да се изпратят имейли', 'warning');
        }

        if (!$rec->countryGroup) {
            $emailLanguage = 'bg';
        } else {
            $emailLanguage = (drdata_CountryGroups::fetch($rec->countryGroup)->name == 'България') ? 'bg' : 'en';
        }


        $handle = doc_Containers::getDocument($rec->containerId)->getHandle();

        $listArr = array('title' => 'Справка' . ' ' . $rec->title . ' ' . $handle,
            'ifExist' => 'truncateAndUpdate',
            'keyField' => 'email',
            'fieldsArr' => array('company' => 'Име', 'country' => 'Държава', 'docs' => 'Документи', 'sum' => 'Стойност', 'currency' => 'Валута', 'date' => 'Дата'),
            'state' => 'closed',
            'lg' => $emailLanguage,
            'folderId' => blast_Lists::getDefaultFolder(),
            'sharedUser' => array(core_Users::getCurrent() => core_Users::getCurrent()),
            'listFieldsDetArr' => $listForSend,
        );

        if ($emailLanguage == 'bg') {
            $body = sales_Setup::get('DEFAULT_BLAST_BODY_BG');
            $subject = sales_Setup::get('DEFAULT_BLAST_SUBJECT_BG');
        } else {
            $body = sales_Setup::get('DEFAULT_BLAST_BODY_EN');
            $subject = sales_Setup::get('DEFAULT_BLAST_SUBJECT_EN');
        }


        $blastArr = array('sharedUser' => array(core_Users::getCurrent() => core_Users::getCurrent()),
            'text' => $body,
            'subject' => $subject,
            'canUnsubscribe' => 'no',
            'lg' => core_Lg::getCurrent(),
            'folderId' => blast_Emails::getDefaultFolder(),
            'fields' => array('recipient' => '[#company#]', 'email' => '[#email#]'));

        $res = blast_Emails::createListAndEmail($listArr, $blastArr);

        expect($res['blastId']);

        $rec->blastId = $res['blastId'];
        if (countR($rec->unsentEmails)) {
            status_Messages::newStatus('На ' . countR($rec->unsentEmails) . ' контрагента няма да бъдат изпратени имейли. Виж :' . frame2_Reports::getLinkToSingle($rec->id), 'warning');
        }

        frame2_Reports::save($rec, 'blastId');

        if (blast_Emails::haveRightFor('single', $res['blastId'])) {
            return new Redirect(array('blast_Emails', 'single', $res['blastId']));
        }
    }

}
