<?php


/**
 * Мениджър на отчети на Платежни документи в състояние "заявка"
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Платежни документи в състояние 'заявка'
 */
class deals_reports_ReportPaymentDocuments extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,bank,cash';


    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField = '';


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck = '';

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'accountId,casesId,documentType,horizon';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('accountId', 'keylist(mvc=bank_OwnAccounts,select=title,allowEmpty)', 'caption=Банкова сметка,placeholder=Всички,after=title');
        $fieldset->FLD('caseId', 'keylist(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Каса,placeholder=Всички,after=accountId');
        $fieldset->FLD('documentType', 'keylist(mvc=core_Classes,select=title)', 'caption=Документи,placeholder=Всички,after=caseId');
        $fieldset->FLD('horizon', 'time', 'caption=Хоризонт,after=documentType');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = &$data->form;

        $accounts = self::getContableAccounts($form->rec);
        $form->setSuggestions('accountId', array('' => '') + $accounts);

        $casses = self::getContableCases($form->rec);
        $form->setSuggestions('caseId', array('' => '') + $casses);

        $documents = array('cash_Pko', 'cash_Rko', 'bank_SpendingDocuments', 'bank_IncomeDocuments');

        $docOptions = array();

        foreach ($documents as $className) {
            $classId = $className::getClassId();
            $docOptions[$classId] = core_Classes::getTitleById($classId, false);
        }

        $form->setSuggestions('documentType', $docOptions);
    }


    /**
     * Връща банковите сметки, които може да контира потребителя
     *
     * @return array $res
     */
    public static function getContableAccounts($rec)
    {
        $res = array();

        $cu = (!empty($rec->createdBy)) ? $rec->createdBy : core_Users::getCurrent();

        $sQuery = bank_OwnAccounts::getQuery();
        $sQuery->where("#state != 'rejected'");

        while ($sRec = $sQuery->fetch()) {
            if (bgerp_plg_FLB::canUse('bank_OwnAccounts', $sRec, $cu, 'select')) {
                $res[$sRec->id] = bank_OwnAccounts::getTitleById($sRec->id, FALSE);
            }
        }

        return $res;
    }


    /**
     * Връща касите, които може да контира потребителя
     *
     * @return array $res
     */
    public static function getContableCases($rec)
    {
        $res = array();

        $cu = (!empty($rec->createdBy)) ? $rec->createdBy : core_Users::getCurrent();

        $sQuery = cash_Cases::getQuery();
        $sQuery->where("#state != 'rejected'");

        while ($sRec = $sQuery->fetch()) {
            if (bgerp_plg_FLB::canUse('cash_Cases', $sRec, $cu, 'select')) {
                $res[$sRec->id] = cash_Cases::getTitleById($sRec->id, false);
            }
        }

        return $res;
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
        $docClasses = $caseRecs = $bankRecs = $recs = array();

        $accountsId = isset($rec->accountId) ? keylist::toArray($rec->accountId) : array_keys(self::getContableAccounts($rec));

        $casesId = isset($rec->caseId) ? keylist::toArray($rec->caseId) : array_keys(self::getContableCases($rec));

        $documentFld = ($rec->documentType) ? 'documentType' : 'document';

        $docClasses = keylist::toArray($rec->documentType);

        $both = (!isset($rec->accountId) && !isset($rec->caseId)) || (isset($rec->accountId, $rec->caseId));

        // Банкови платежни документи
        if ($both || isset($rec->accountId)) {
            foreach (array('bank_SpendingDocuments', 'bank_IncomeDocuments') as $pDoc) {
                if (empty($docClasses) || in_array($pDoc::getClassId(), $docClasses)) {
                    $cQuery = $pDoc::getQuery();

                    $cQuery->in('ownAccount', $accountsId);

                    $cQuery->orWhere('#ownAccount IS NULL');

                    $cQuery->where("#state = 'pending'");

                    $cQuery->orderBy('termDate', 'ASC');

                    while ($cRec = $cQuery->fetch()) {
                        $payDate = ($cRec->termDate) ? $cRec->termDate : $cRec->valior;

                        if (!empty($rec->horizon)) {
                            $horizon = dt::addSecs($rec->horizon, dt::today(), false);

                            if ($payDate && ($payDate > $horizon)) {
                                unset($payDate);

                                continue;
                            }
                        }

                        $className = core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass);

                        if (core_Users::getCurrent() != $cRec->createdBy) {
                            $Document = doc_Containers::getDocument($cRec->containerId);

                            if (!$Document->haveRightFor('single', $rec->createdBy)) {
                                continue;
                            }
                        }


                        $bankRecs[$cRec->containerId] = (object)array('containerId' => $cRec->containerId,
                            'amountDeal' => $cRec->amountDeal,
                            'totalSumContr' => array(),
                            'className' => $className,
                            'payDate' => $payDate,
                            'termDate' => $cRec->termDate,
                            'valior' => $cRec->valior,
                            'currencyId' => $cRec->currencyId,
                            'documentId' => $cRec->id,
                            'folderId' => $cRec->folderId,
                            'createdOn' => $cRec->createdOn,
                            'createdBy' => $cRec->createdBy,
                            'ownAccount' => $cRec->ownAccount,
                            'peroCase' => $cRec->peroCase,
                            'contragentName' => $cRec->contragentName,
                        );
                    }
                }
            }
        }

        /*
         * Касови платежни документи
         */
        if ($both || isset($rec->caseId)) {
            foreach (array('cash_Rko', 'cash_Pko') as $pDoc) {
                if (empty($docClasses) || in_array($pDoc::getClassId(), $docClasses)) {
                    $cQuery = $pDoc::getQuery();

                    $cQuery->in('peroCase', $casesId);

                    $cQuery->orWhere('#peroCase IS NULL');

                    $cQuery->where("#state = 'pending'");

                    $cQuery->orderBy('termDate', 'ASC');

                    while ($cRec = $cQuery->fetch()) {
                        $payDate = ($cRec->termDate) ? $cRec->termDate : $cRec->valior;

                        if (!empty($rec->horizon)) {
                            $horizon = dt::addSecs($rec->horizon, dt::today(), false);

                            if ($payDate && ($payDate > $horizon)) {
                                unset($payDate);

                                continue;
                            }
                        }

                        $className = core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass);

                        if (core_Users::getCurrent() != $cRec->credatedBy) {
                            $Document = doc_Containers::getDocument($cRec->containerId);

                            if (!$Document->haveRightFor('single', $rec->createdBy)) {
                                continue;
                            }
                        }
                        $caseRecs[$cRec->containerId] = (object)array('containerId' => $cRec->containerId,
                            'amountDeal' => $cRec->amountDeal,
                            'totalSumContr' => array(),
                            'className' => $className,
                            'payDate' => $payDate,
                            'termDate' => $cRec->termDate,
                            'valior' => $cRec->valior,
                            'currencyId' => $cRec->currencyId,
                            'documentId' => $cRec->id,
                            'folderId' => $cRec->folderId,
                            'createdOn' => $cRec->createdOn,
                            'createdBy' => $cRec->createdBy,
                            'ownAccount' => $cRec->ownAccount,
                            'peroCase' => $cRec->peroCase,
                            'contragentName' => $cRec->contragentName,
                        );
                    }
                }
            }
        }

        $recs = $bankRecs + $caseRecs;

        //Извеждане на резултатните суми по контрагент
        $totalContragentaSumArr = array();
        foreach ($recs as $r) {
            $m = 1;
            $DoocClass = cls::get($r->className);
            if (!in_array($DoocClass->abbr, array('Pbd', 'Pko'))) {
                $m = -1;
            }
            if(!in_array($r->folderId,array_keys($totalContragentaSumArr))){
                $totalContragentaSumArr[$r->folderId] = $r->amountDeal*$m;
            }else{
                $totalContragentaSumArr[$r->folderId] += $r->amountDeal*$m;
            }

        }
        foreach ($recs as $r) {
            $r->totalSumContr = $totalContragentaSumArr[$r->folderId];
        }

        usort($recs, array($this, 'orderByPayDate'));

        return $recs;
    }


    public function orderByPayDate($a, $b)
    {
        return $a->payDate > $b->payDate;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec - записа
     * @param bool $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        $fld->FLD('contragentName', 'varchar', 'caption=Контрагент');
        $fld->FLD('documentId', 'varchar', 'caption=Документ');
        $fld->FLD('amountDeal', 'double(decimals=2)', 'caption=Сума,smartCenter');
        $fld->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута,smartCenter');
        $fld->FLD('payDate', 'date', 'caption=Плащане до');

        if ($export === false) {
            $fld->FLD('created', 'varchar', 'caption=Създаване,smartCenter');
        } else {
            $fld->FLD('createdOn', 'datetime', 'caption=Създаване');
            $fld->FLD('createdBy', 'key(mvc=core_Users,select=nick)', 'caption=Създал');
        }

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = cls::get('type_Int');
        $Double = core_Type::getByName('double(smartRound)');
        $Date = cls::get('type_Date');

        $row = new stdClass();

        $DoocClass = cls::get($dRec->className);
        $row->documentId = $DoocClass->getLink($dRec->documentId, 0);
        if(!$rec->data->groupByField) {
            $row->contragentName = $dRec->contragentName;
        }else{
            if($dRec->totalSumContr[$dRec->folderId] >= 0){
                $row->contragentName = $dRec->contragentName .'<span style="color: green" class="fright">' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalSumContr) . '<span class="cCode" style="position:relative; top: -2px; margin-left: 2px;">' . currency_Currencies::getCodeById($dRec->currencyId). '</span>';
            }else{
                $row->contragentName = $dRec->contragentName.'<span style="color: red" class="fright">' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalSumContr) . '<span class="cCode" style="position:relative; top: -2px; margin-left: 2px;">' . currency_Currencies::getCodeById($dRec->currencyId). '</span>';
            }
        }
        if (isset($dRec->createdBy)) {
            $row->createdBy = crm_Profiles::createLink($dRec->createdBy);
            $row->createdOn = $Date->toVerbal($dRec->createdOn);
        }

        $hint = ($dRec->ownAccount) ? bank_OwnAccounts::getTitleById($dRec->ownAccount) : cash_Cases::getTitleById($dRec->peroCase);
        $hint = $hint ? $hint : 'не посочена';

        $row->created = $row->createdOn . ' от ' . $row->createdBy;

        if (in_array($DoocClass->abbr, array('Pbd', 'Pko'))) {
            $row->amountDeal = '<span style="color: green">' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amountDeal) . '</span>';
        } else {
            $row->amountDeal = '<span style="color: red">' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amountDeal) . '</span>';
        }


        if (isset($dRec->amountDeal)) {

            $row->amountDeal = ht::createHint($row->amountDeal, "${hint}", 'notice');
            $row->payDate = ($dRec->payDate) ? $Date->toVerbal($dRec->payDate) : tr('|*<span class="quiet">|не е посочен|*</span>');
        }

        if (isset($dRec->currencyId)) {
            $row->currencyId = currency_Currencies::getCodeById($dRec->currencyId);
        }

        return $row;
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver - драйвер
     * @param stdClass $res - резултатен запис
     * @param stdClass $rec - запис на справката
     * @param stdClass $dRec - запис на реда
     * @param core_BaseClass $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->documentId = '#' . cls::get($dRec->className)->getHandle($dRec->documentId, 0);
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

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN button--><div>| |* [#button#]</div><!--ET_END button-->
                                    </div>
                                
                                 </fieldset><!--ET_END BLOCK-->"));


        $artUrl = array('deals_reports_ReportPaymentDocuments', 'contragentFilter', 'recId' => $data->rec->id, 'ret_url' => true);
        $artUrl1 = array('deals_reports_ReportPaymentDocuments', 'contragentGroup', 'recId' => $data->rec->id, 'ret_url' => true);

        $toolbar = cls::get('core_Toolbar');

        $toolbar->addBtn('Групирай по контрагент', toUrl($artUrl1));
        $toolbar->addBtn('Избери контрагент', toUrl($artUrl));

        $fieldTpl->append('<b>' . $toolbar->renderHtml() . '</b>', 'button');

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }

    /**
     * Филтриране на артикул
     */
    public static function act_ContragentFilter()
    {

        expect($recId = Request::get('recId', 'int'));

        $rec = frame2_Reports::fetch($recId);

        frame2_Reports::refresh($rec);

        $form = cls::get('core_Form');

        $form->title = "Филтър по контрагент";

        $artSuggestionsArr = array();


        if (is_array($rec->data->recs) && !empty($rec->data->recs)) {

            $prArr = arr::extractValuesFromArray($rec->data->recs, 'folderId');
            foreach (array_keys($prArr) as $val) {

                $pRec = doc_Folders::fetch($val);
                $artSuggestionsArr[$val] = $pRec->title;

            }
        }

        $form->FLD('contragentFilter', 'key(mvc=doc_Folders,select=title)', 'caption=Контрагент');

        $form->setOptions('contragentFilter', $artSuggestionsArr);

        $mRec = $form->input();

        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');

        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        if ($form->isSubmitted()) {

            foreach ($rec->data->recs as $key => $pRec) {
                if (($pRec->folderId) && ($form->rec->contragentFilter != $pRec->folderId)) {
                    unset($rec->data->recs[$key]);
                }
            }

            frame2_Reports::save($rec);
            return new Redirect(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $recId, 'contragentFilter' => $form->rec->contragentFilter, 'ret_url' => true));

        }

        return $form->renderHtml();
    }

    /**
     * Филтриране на артикул
     */
    public static function act_ContragentGroup()
    {

        expect($recId = Request::get('recId', 'int'));

        $rec = frame2_Reports::fetch($recId);

        frame2_Reports::refresh($rec);

        $rec->data->groupByField = 'contragentName';

        frame2_Reports::save($rec);

        return new Redirect(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $recId, 'contragentGroup' => $form->rec->contragentFilter, 'ret_url' => true));

        return $form->renderHtml();
    }


}
