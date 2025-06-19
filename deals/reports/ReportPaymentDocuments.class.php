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
     * Добавя полетата на драйвера към формата за справката
     *
     * @param core_Fieldset $fieldset - обектът на формата, към който се добавят полета
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        // Поле за избор на банкови сметки
        $fieldset->FLD('accountId', 'keylist(mvc=bank_OwnAccounts,select=title,allowEmpty)',
            'caption=Банкова сметка,placeholder=Всички,after=title');

        // Поле за избор на каси
        $fieldset->FLD('caseId', 'keylist(mvc=cash_Cases,select=name,allowEmpty)',
            'caption=Каса,placeholder=Всички,after=accountId');

        // Поле за избор на типове документи
        $fieldset->FLD('documentType', 'keylist(mvc=core_Classes,select=title)',
            'caption=Документи,placeholder=Всички,after=caseId');

        // Поле за хоризонт
        $fieldset->FLD('horizon', 'time',
            'caption=Хоризонт,after=documentType');

        // Поле за посока на сортиране
        $fieldset->FLD('sortDirection', 'enum(desc=Низходящо,asc=Възходящо)',
            'caption=Сортиране->Подреждане,after=horizon,maxRadio=2');

        // Поле за групиране
        $fieldset->FLD('groupBy', 'enum(yes=Групирано,no=Без групиране)',
            'caption=Сортиране->Групиране,after=sortDirection,maxRadio=2');
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

        // Зареждаме сметките, касите и документите както досега
        $accounts = self::getContableAccounts($form->rec);
        $form->setSuggestions('accountId', array('' => '') + $accounts);

        $cases = self::getContableCases($form->rec);
        $form->setSuggestions('caseId', array('' => '') + $cases);

        $documents = array('cash_Pko', 'cash_Rko', 'bank_SpendingDocuments', 'bank_IncomeDocuments');
        $docOptions = array();
        foreach ($documents as $className) {
            $classId = $className::getClassId();
            $docOptions[$classId] = core_Classes::getTitleById($classId, false);
        }
        $form->setSuggestions('documentType', $docOptions);

        // Дефолтни стойности при първоначално създаване
        if (!$form->rec->id) {
            $form->setDefault('sortDirection', 'desc');
            $form->setDefault('groupBy', 'yes');
        }
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
     * Подготвя записите за показване в таблицата на справката
     *
     * @param stdClass $rec  - запис с настройките от формата
     * @param stdClass|null &$data - обект за допълнителни данни (например groupByField)
     * @return array - масив от записи за показване
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $docClasses = $caseRecs = $bankRecs = $recs = array();

        // Управление на групирането
        if ($rec->groupBy == 'yes') {
            $this->groupByField = 'contragentName';
        } else {
            $this->groupByField = null;
        }

        $accountsId = isset($rec->accountId) ? keylist::toArray($rec->accountId) : array_keys(self::getContableAccounts($rec));
        $casesId = isset($rec->caseId) ? keylist::toArray($rec->caseId) : array_keys(self::getContableCases($rec));

        $docClasses = keylist::toArray($rec->documentType);
        $both = (!isset($rec->accountId) && !isset($rec->caseId)) || (isset($rec->accountId, $rec->caseId));

        // Банкови документи
        if ($both || isset($rec->accountId)) {
            foreach (array('bank_SpendingDocuments', 'bank_IncomeDocuments') as $pDoc) {
                if (empty($docClasses) || in_array($pDoc::getClassId(), $docClasses)) {
                    $cQuery = $pDoc::getQuery();
                    $cQuery->in('ownAccount', $accountsId);
                    $cQuery->orWhere('#ownAccount IS NULL');
                    $cQuery->where("#state = 'pending'");
                    $cQuery->orderBy('termDate', 'ASC');

                    while ($cRec = $cQuery->fetch()) {

                        // Определяме payDate според зададената нова логика:
                        $payDate = null;

                        if (!empty($cRec->termDate)) {
                            $payDate = $cRec->termDate; // 1. Ако има срок (дата на плащане)
                        } elseif (!empty($cRec->valior)) {
                            $payDate = $cRec->valior;   // 2. Ако има валидностен валор
                        } else {
                            $payDate = $cRec->createdOn; // 3. В краен случай дата на създаване
                        }

                        $className = core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass);

                        if (core_Users::getCurrent() != $cRec->createdBy) {
                            $Document = doc_Containers::getDocument($cRec->containerId);
                            if (!$Document->haveRightFor('single', $rec->createdBy)) {
                                continue;
                            }
                        }

                        $bankRecs[$cRec->containerId] = (object)array(
                            'containerId' => $cRec->containerId,
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

        // Касови документи
        if ($both || isset($rec->caseId)) {
            foreach (array('cash_Rko', 'cash_Pko') as $pDoc) {
                if (empty($docClasses) || in_array($pDoc::getClassId(), $docClasses)) {
                    $cQuery = $pDoc::getQuery();
                    $cQuery->in('peroCase', $casesId);
                    $cQuery->orWhere('#peroCase IS NULL');
                    $cQuery->where("#state = 'pending'");
                    $cQuery->orderBy('termDate', 'ASC');

                    while ($cRec = $cQuery->fetch()) {

                        // Определяме payDate според същата логика:
                        $payDate = null;

                        if (!empty($cRec->termDate)) {
                            $payDate = $cRec->termDate;
                        } elseif (!empty($cRec->valior)) {
                            $payDate = $cRec->valior;
                        } else {
                            $payDate = $cRec->createdOn;
                        }

                        $className = core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass);

                        if (core_Users::getCurrent() != $cRec->createdBy) {
                            $Document = doc_Containers::getDocument($cRec->containerId);
                            if (!$Document->haveRightFor('single', $rec->createdBy)) {
                                continue;
                            }
                        }

                        $caseRecs[$cRec->containerId] = (object)array(
                            'containerId' => $cRec->containerId,
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

        // Сумиране по контрагент и валута
        $totalContragentaSumArr = array();
        foreach ($recs as $r) {
            $m = 1;
            $DoocClass = cls::get($r->className);
            if (!in_array($DoocClass->abbr, array('Pbd', 'Pko'))) {
                $m = -1;
            }
            $curCode = currency_Currencies::getCodeById($r->currencyId);
            if (!isset($totalContragentaSumArr[$r->folderId])) {
                $totalContragentaSumArr[$r->folderId] = array();
            }
            if (!isset($totalContragentaSumArr[$r->folderId][$curCode])) {
                $totalContragentaSumArr[$r->folderId][$curCode] = 0;
            }
            $totalContragentaSumArr[$r->folderId][$curCode] += $r->amountDeal * $m;

            $r->totalSumContr = $totalContragentaSumArr;
        }

        // Сортиране според избраната посока
        $order = ($rec->sortDirection == 'asc') ? 1 : -1;
        usort($recs, function ($a, $b) use ($order) {
            return ($a->payDate <=> $b->payDate) * $order;
        });

        return $recs;
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
     * @param stdClass $rec   - запис на справката (настройки от формата)
     * @param stdClass $dRec  - редов запис от резултата
     *
     * @return stdClass $row  - вербален (четим) запис за визуализиране в таблицата
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        // Зареждаме типове за форматиране
        $Int = cls::get('type_Int');
        $Double = core_Type::getByName('double(smartRound)');
        $Date = cls::get('type_Date');

        $row = new stdClass();

        // Връзка към документа
        $DoocClass = cls::get($dRec->className);
        $row->documentId = $DoocClass->getLink($dRec->documentId, 0);

        // Контрагент (групиране или не)
        if (!$rec->data->groupByField) {
            // Без групиране – просто името
            $row->contragentName = $dRec->contragentName;
        } else {
            // С групиране – показване на сумите във всички валути
            $sums = array();

            if (isset($dRec->totalSumContr[$dRec->folderId])) {
                foreach ($dRec->totalSumContr[$dRec->folderId] as $cur => $val) {
                    $absVal = abs($val);
                    $verbalVal = $Double->toVerbal($absVal);
                    $displayVal = ($val < 0) ? '-' . $verbalVal : $verbalVal;

                    // Стил според знака
                    if ($val < 0) {
                        $styledVal = "<span style='color: red'>{$displayVal}</span>";
                    } else {
                        $styledVal = "<span style='color: green'>{$displayVal}</span>";
                    }

                    $sums[] = "<span class='fright'>{$styledVal} <span class='cCode' style='position:relative; top: -2px; margin-left: 2px;'>{$cur}</span></span>";
                }
            }

            // Добавяне на името и сборовете
            $row->contragentName = $dRec->contragentName . implode('', $sums);
        }

        // Данни за създаване
        if (isset($dRec->createdBy)) {
            $row->createdBy = crm_Profiles::createLink($dRec->createdBy);
            $row->createdOn = $Date->toVerbal($dRec->createdOn);
        }

        $row->created = $row->createdOn . ' от ' . $row->createdBy;

        // Сума на документа (оцветена и с tooltip)
        $val = $dRec->amountDeal;
        $absVal = abs($val);
        $verbalVal = $Double->toVerbal($absVal);
        $displayVal = ($val < 0) ? '-' . $verbalVal : $verbalVal;

        // Оцветяване на документа
        if ($val < 0) {
            $styledAmount = "<span style='color: red'>{$displayVal}</span>";
        } else {
            $styledAmount = "<span style='color: green'>{$displayVal}</span>";
        }

        // Tooltip със сметка/каса
        $hint = ($dRec->ownAccount)
            ? bank_OwnAccounts::getTitleById($dRec->ownAccount)
            : cash_Cases::getTitleById($dRec->peroCase);
        $hint = $hint ?: 'не посочена';

        $row->amountDeal = ht::createHint($styledAmount, $hint, 'notice');

        // Дата на плащане
        $row->payDate = ($dRec->payDate)
            ? $Date->toVerbal($dRec->payDate)
            : tr('|*<span class="quiet">|не е посочен|*</span>');

        // Валутен код
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
                                        <!--ET_BEGIN button--><div>|Филтри |*: [#button#]</div><!--ET_END button-->
                                    </div>
                                
                                 </fieldset><!--ET_END BLOCK-->"));


        $artUrl = array('deals_reports_ReportPaymentDocuments', 'contragentFilter', 'recId' => $data->rec->id, 'ret_url' => true);
        $artUrl1 = array('deals_reports_ReportPaymentDocuments', 'contragentGroup', 'recId' => $data->rec->id, 'ret_url' => true);

        $toolbar = cls::get('core_Toolbar');

       // $toolbar->addBtn('Групирай по контрагент', toUrl($artUrl1));
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

            $rec->data->groupByField = 'contragentName';
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
