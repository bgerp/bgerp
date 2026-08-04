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
        $fieldset->FLD('dealer', 'user(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Филтри->Търговец,placeholderType=all,single=none,after=typeGrupping,input');
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Филтри->Контрагент,placeholderType=all,single=none,after=dealer');
        $fieldset->FLD('countryGroup', 'key(mvc=drdata_CountryGroups,select=name,allowEmpty)', 'caption=Филтри->Група държави,single=none,placeholderType=all,after=contragent'
        );

        //Праг за минимална просрочена сума за показване
        $fieldset->FLD('minOverdueLevel', 'double', 'caption=Филтри->Без просрочените под,unit= евро,after=countryGroup,placeholder=0.00,silent,single=none');


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

        $suggestions = array();

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

        if (empty($rec->checkDate)) {
            $checkDate = dt::now();
        } else {
            $checkDate = $rec->checkDate . ' 23:59:59';
        }

        $this->groupByField = $rec->typeGrupping ?? 'contragent';
        $recs = array();

        // Старите записи на справката може да нямат попълнени периоди.
        // При липсващ/невалиден JSON използваме стандартните граници.
        $limit1 = 30;
        $limit2 = 60;
        $limits = json_decode($rec->additional ?? '');
        if (is_object($limits)) {
            $limit1 = (int) ($limits->limit1[0] ?? $limit1);
            $limit2 = (int) ($limits->limit2[0] ?? $limit2);
        }
        if ($limit1 > $limit2) {
            list($limit1, $limit2) = array($limit2, $limit1);
        }

        // Масив със записи от изходящи фактури
        $sRecs = array();

        $salQuery = sales_Sales::getQuery();

        $salQuery->in('state', array('rejected', 'draft', 'pending'), true);

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

                        $Invoice = doc_Containers::getDocument($inv);

                        if (!$Invoice || $Invoice->className != 'sales_Invoices') {
                            continue;
                        }

                        $iRec = $Invoice->fetch(
                            'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,currencyId,date,dueDate,
                                   contragentId,contragentClassId, contragentCountryId'
                        );

                        if (!$iRec) {
                            continue;
                        }

                        $contragentClassName = core_Classes::fetchField($iRec->contragentClassId, 'name');
                        if (!$contragentClassName) {
                            continue;
                        }

                        $contragentRec = $contragentClassName::fetch($iRec->contragentId);

                        if (!$contragentRec || empty($contragentRec->folderId)) {
                            continue;
                        }

                        $contragentFolderId = $contragentRec->folderId;

                        //Филтър по контрагент
                        if (!empty($rec->contragent) && (!in_array($contragentFolderId, keylist::toArray($rec->contragent)))) continue;

                        //Филтър по дилър
                        if (!empty($rec->dealer) && ($rec->dealer != ($fDocRec->dealerId ?? null))) continue;

                        //Филтър по група държави
                        if (!empty($rec->countryGroup)) {
                            $countriesList = drdata_CountryGroups::fetchField($rec->countryGroup, 'countries');

                            if (!$countriesList || !keylist::isIn($iRec->contragentCountryId, $countriesList)) {
                                continue;
                            }
                        }

                        //Превалутиране на сумите

                        $amount = (float) ($paydocs->amount ?? 0);
                        $payout = (float) ($paydocs->payout ?? 0);
                        $rate = (float) ($iRec->rate ?? 1);
                        $payDate = $paydocs->date ?? $iRec->date;
                        $paydocsAmountBaseCurr = $amount * $rate;
                        $paydocspayOutBaseCurr = $payout * $rate;

                      //  $paydocs->payout = deals_Helper::getSmartBaseCurrency($paydocs->payout, $paydocs->date, $rec->checkDate);
                      //  $paydocs->amount = deals_Helper::getSmartBaseCurrency( $paydocs->amount, $paydocs->date, $rec->checkDate);

                        $paydocspayOutBaseCurr = deals_Helper::getSmartBaseCurrency($paydocspayOutBaseCurr, $payDate, $rec->checkDate ?? null);
                        $paydocsAmountBaseCurr = deals_Helper::getSmartBaseCurrency($paydocsAmountBaseCurr, $payDate, $rec->checkDate ?? null);

                        if (($payout >= $amount - 0.01) && ($payout <= $amount + 0.01)) {
                            continue;
                        }



                        $overdueColor = '';
                        if ($iRec->dueDate && ($amount - $payout) > ($rec->minOverdueLevel ?? 0) &&
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

                            $invoiceCurrentSummArr[$contragentFolderId] = ($invoiceCurrentSummArr[$contragentFolderId] ?? 0) + $paydocsAmountBaseCurr - $paydocspayOutBaseCurr; //Обща сума за контрагента в основна валута

                        } else {
                            continue;
                        }

                        $salesTotalOverDue += $paydocsAmountBaseCurr ;      // Обща стойност на просрочените фактури преизчислени в основна валута
                        $salesTotalPayout += $paydocspayOutBaseCurr ;       // Обща стойност на плащанията по просрочените фактури преизчислени в основна валута

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
                                'invoiceValue' => $amount,
                                'invoiceVAT' => $iRec->vatAmount ?? 0,
                                'invoicePayout' => $payout,
                                'invoiceCurrentSumm' => $amount - $payout,
                                'invoiceCurrentSummArr' => $invoiceCurrentSummArr,
                                'payDocuments' => $paydocs->used ?? array()
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

        $rTemp = array();
        if (!empty($invoiceCurrentSummArr)) {
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
            if (!empty($rec->additional)) {
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
            if (!empty($rec->additional)) {
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
        $paidAmount = $dRec->invoicePayout ?? 0;

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
        $paidDatesList = array();
        if (is_array($dRec->payDocuments ?? null)) {
            foreach ($dRec->payDocuments as $onePayDoc) {
                if (!empty($onePayDoc->containerId)) {
                    $Document = doc_Containers::getDocument($onePayDoc->containerId);
                } else {
                    continue;
                }
                if (!$Document) {
                    continue;
                }
                $payDocClass = $Document->className;

                $valior = $payDocClass::fetchField($Document->that, 'valior');
                if ($valior) {
                    $paidDatesList[] = $valior;
                }
            }
        }

        $paidDates = array();
        foreach ($paidDatesList as $v) {
            $paidDates[] = dt::mysql2verbal($v, 'd.m.y');
        }

        return implode($verbal ? '<br>' : "\n", $paidDates);
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
        if ($verbal === true) {
            if (!empty($dRec->dueDate)) {
                $dueDate = dt::mysql2verbal($dRec->dueDate, 'd.m.Y');
            } else {
                $dueDate = '';
            }
        } else {
            if (!empty($dRec->dueDate)) {
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

        $euroZoneDate = acc_Setup::getEurozoneDate();

        $invoiceNo = str_pad($dRec->invoiceNo ?? '', 10, '0', STR_PAD_LEFT);

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

            if (($rec->data->groupByField ?? null) == 'contragent') {
                $row->overduePeriod = "<span style=\"color:{$dRec->overColor}\">" . $dRec->overduePeriod . '</span>';
                if (($rec->checkDate ?? dt::today()) < $euroZoneDate) {

                    $invoiceCurrentSumm = ($dRec->invoiceCurrentSummArr[$dRec->contragent] ?? 0) / 1.95583;

                }else{
                    $invoiceCurrentSumm = $dRec->invoiceCurrentSummArr[$dRec->contragent] ?? 0;
                }

                $row->contragent = doc_Folders::getTitleById($dRec->contragent) .
                    "<span class= 'fright'><span class= 'quiet'>" . 'Общо ПРОСРОЧЕНИ фактури: ' . '</span>' .
                    core_Type::getByName('double(decimals=2)')->toVerbal($invoiceCurrentSumm) .
                    ' ' . " €" . '</span>';
            } else {
                $row->overduePeriod = 'Просрочие ' . $dRec->overduePeriod . ' дни';
                $row->contragent = doc_Folders::getTitleById($dRec->contragent);
            }
        } else {
            $row->contragent = 'error';
        }

        $row->currencyId = $dRec->currencyId;

        $invoiceValue = ($dRec->invoiceValue ?? 0) + ($dRec->invoiceVAT ?? 0);

        $row->invoiceValue = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceValue);

        if (($dRec->invoiceCurrentSumm ?? 0) > 0) {
            if ($dRec->invoiceCurrentSumm > ($dRec->invoiceValue ?? 0)) {
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
        if (empty($data->rec->checkDate)) {
            $checkDate = dt::now();
        } else {
            $checkDate = $data->rec->checkDate;
        }

        $contragents = $exludedContragents = $unsentEmails = '';

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

        $euroZoneDate = acc_Setup::getEurozoneDate();
        //Показва контрагента
        if (!empty($data->rec->contragent)) {
            foreach (keylist::toArray($data->rec->contragent) as $v) {
                $contragents .= doc_Folders::fetchField($v, 'title') . ', ';
            }
            $fieldTpl->append(trim($contragents, ', '), 'contragent');
        } else {
            $fieldTpl->append('Всички', 'contragent');
        }

        //Показва търговеца
        if (!empty($data->rec->dealer)) {
            $fieldTpl->append(core_Users::fetchField($data->rec->dealer, 'names'), 'dealer');
        } else {
            $fieldTpl->append('Всички', 'dealer');
        }

        //Показва групата държави
        if (!empty($data->rec->countryGroup)) {
            $countryGroupName = drdata_CountryGroups::fetchField($data->rec->countryGroup, 'name');
            $fieldTpl->append($countryGroupName ?: 'Всички', 'countryGroup');
        } else {
            $fieldTpl->append('Всички', 'countryGroup');
        }


        $fieldTpl->append($Date->toVerbal($checkDate), 'checkDate');

        if ($checkDate < $euroZoneDate) {

            $salesTotalOverDue = ($data->rec->salesTotalOverDue ?? 0) / 1.95583;
            $salesTotalPayout = ($data->rec->salesTotalPayout ?? 0) / 1.95583;
            $salesCurrentSum = ($data->rec->salesCurrentSum ?? 0) / 1.95583;

        }else{
            $salesTotalOverDue = $data->rec->salesTotalOverDue ?? 0;
            $salesTotalPayout = $data->rec->salesTotalPayout ?? 0;
            $salesCurrentSum = $data->rec->salesCurrentSum ?? 0;
        }


        if (isset($data->rec->salesTotalOverDue)) {



            $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($salesTotalOverDue) . " €", 'salesTotalOverDue');
        }

        if (isset($data->rec->salesTotalPayout)) {
            $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($salesTotalPayout) . " €", 'salesTotalPayout');
        }

        if (isset($data->rec->salesCurrentSum)) {
            $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($salesCurrentSum) . " €", 'salesCurrentSum');
        }

        $exportUrl = array('sales_reports_OverdueInvoices', 'excludCompanies', 'recId' => $data->rec->id ?? null, 'ret_url' => true);
        $lastRefreshed = $data->rec->lastRefreshed ?? $checkDate;
        if (dt::secsBetween(dt::now(), $lastRefreshed) > 3600) {
            $worning = "warning='Справката е обновена преди повече от 1 час. Да продължи ли без обновяване?'";
        } else {
            $worning = null;
        }


        $toolbar = cls::get('core_Toolbar');

        if (blast_Emails::haveRightFor('add')) {

            //Изключените контрагенти от имейла
            if (!empty($data->rec->excludedFromEmail)) {
                foreach (keylist::toArray($data->rec->excludedFromEmail) as $v) {
                    $exludedContragents .= doc_Folders::fetchField($v, 'title') . ', ';
                }
                $fieldTpl->append(trim($exludedContragents, ', '), 'excludedFromEmail');
            } else {
                $fieldTpl->append('Няма', 'excludedFromEmail');
            }

            //Неизпратени имейли
            if (!empty($data->rec->unsentEmails)) {
                foreach (keylist::toArray($data->rec->unsentEmails) as $v) {
                    $unsentEmails .= doc_Folders::fetchField($v, 'title') . ', ';
                }
                $fieldTpl->append(trim($unsentEmails, ', '), 'unsentEmails');
            } else {
                $fieldTpl->append('Няма', 'unsentEmails');
            }

            if (!empty($data->rec->blastId)) {
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

        if (($dRec->invoiceCurrentSumm ?? 0) < 0) {
            $invoiceOverSumm = -1 * $dRec->invoiceCurrentSumm;
            $res->invoiceCurrentSumm = '';
            $res->invoiceOverSumm = ($invoiceOverSumm);
        }

        if (!empty($dRec->dueDate) && ($dRec->invoiceCurrentSumm ?? 0) > 0 && $dRec->dueDate < ($rec->checkDate ?? dt::today())) {
            $res->dueDateStatus = 'Просрочен';
        }

        $invoiceNo = str_pad($dRec->invoiceNo ?? '', 10, '0', STR_PAD_LEFT);

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
        expect($rec);

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

            $companyName = doc_Folders::fetchField($val['folder'] ?? null, 'title');

            if ($companyName) {
                $cSuggestionsArr[$val['folder']] = $companyName;
            }

        }

        $form->FLD('companyFilter', 'keylist(mvc=doc_Folders, select=title)', 'caption=Изключени контрагенти,placeholder = Няма,silent');
        $form->FLD('minSumForEmail', 'double(decimals=2)', 'caption=Минимална сума на задължението,placeholder = Няма,silent');

        $form->setSuggestions('companyFilter', $cSuggestionsArr);
        $form->setDefault('minSumForEmail', 0.05);

        $form->rec->companyFilter = $rec->excludedFromEmail ?? null;
        $form->rec->minSumForEmail = $rec->minSumForEmail ?? 0.05;

        $mRec = $form->input();

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        if ($form->isSubmitted()) {

            foreach ($rec->listForEmail as $key => $val) {


                if (in_array($val['folder'] ?? null, keylist::toArray($form->rec->companyFilter ?? null))) {

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
        if (!empty($rec->listForEmail) && is_array($rec->listForEmail)) {
            $oldListForEmail = $rec->listForEmail;
        } else {
            $oldListForEmail = array();
        }


        if (empty($rec->countryGroup)) {
            $emailLanguage = 'bg';
        } else {
            $emailLanguage = (drdata_CountryGroups::fetchField($rec->countryGroup, 'name') == 'България') ? 'bg' : 'en';
        }


        foreach ($rec->data->recs as $dRec) {

            $contragentClassName = core_Classes::fetchField($dRec->contragentClassId ?? null, 'name');
            if (!$contragentClassName) {
                continue;
            }

            $contragentRec = $contragentClassName::fetch($dRec->contragentId ?? null);
            if (!$contragentRec) {
                continue;
            }

            $countryName = drdata_Countries::fetchField($contragentRec->country ?? null, 'commonName');

            foreach (explode(',', $contragentRec->email ?? '') as $email) {
                $email = trim($email);

                //Ако има контрагенти без имейл ги изключва и ги записва в полето $rec->unsentEmails
                if ($email == '') {
                    if (!empty($contragentRec->folderId)) {
                        $unsentEmails[$contragentRec->folderId] = $contragentRec->folderId;
                    }
                    continue;
                }

                $inv = '#' . sales_Invoices::getHandle($dRec->invoiceId);

                $oldEntry = $oldListForEmail[$email] ?? array();
                $excludе = $oldEntry['excludе'] ?? ($oldEntry['exclude'] ?? 'no');

                if (!in_array($email, array_keys($listForEmail))) {

                    $listForEmail[$email] = array('email' => $email,
                        'company' => $contragentRec->name,
                        'folder' => $contragentRec->folderId,
                        'country' => $countryName,
                        'date' => dt::mysql2verbal($rec->lastRefreshed ?? dt::now(), 'd.m.Y'),
                        'docs' => $inv,
                        'sum' => $dRec->invoiceCurrentSummArr[$dRec->contragent] ?? 0,
                        'currency' => $dRec->currencyId ?? null,
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
        expect($rec);

        $listForSend = array();

        foreach (($rec->listForEmail ?? array()) as $key => $val) {

            if (($val['excludе'] ?? ($val['exclude'] ?? 'no')) == 'yes' || ($val['sum'] ?? 0) <= ($rec->minSumForEmail ?? 0.05)) continue;

            $listForSend[$key] = array('email' => $val['email'] ?? $key,
                'company' => $val['company'] ?? '',
                'country' => $val['country'] ?? '',
                'date' => $val['date'] ?? '',
                'docs' => $val['docs'] ?? '',
                'sum' => round($val['sum'] ?? 0, 2),
                'currency' => $val['currency'] ?? '',
            );
        }

        if (empty($listForSend)) {
            return new Redirect(array('frame2_Reports', 'single', $rec->id), 'Липсват контрагенти, на които да се изпратят имейли', 'warning');
        }

        if (empty($rec->countryGroup)) {
            $emailLanguage = 'bg';
        } else {
            $emailLanguage = (drdata_CountryGroups::fetchField($rec->countryGroup, 'name') == 'България') ? 'bg' : 'en';
        }


        $reportDocument = doc_Containers::getDocument($rec->containerId ?? null);
        $handle = $reportDocument ? $reportDocument->getHandle() : '';

        $listArr = array('title' => 'Справка' . ' ' . ($rec->title ?? '') . ' ' . $handle,
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

        $blastId = $res['blastId'] ?? null;
        expect($blastId);

        $rec->blastId = $blastId;
        if (countR($rec->unsentEmails ?? array())) {
            status_Messages::newStatus('На ' . countR($rec->unsentEmails) . ' контрагента няма да бъдат изпратени имейли. Виж :' . frame2_Reports::getLinkToSingle($rec->id), 'warning');
        }

        frame2_Reports::save($rec, 'blastId');

        if (blast_Emails::haveRightFor('single', $blastId)) {
            return new Redirect(array('blast_Emails', 'single', $blastId));
        }
    }

}
