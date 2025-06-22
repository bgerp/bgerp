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
    public function addFields(core_FieldSet &$fieldset)
    {
        // Избор на банкови сметки (ако има право потребителят)
        $fieldset->FLD(
            'accountId',
            'keylist(mvc=bank_OwnAccounts,select=title,allowEmpty)',
            'caption=Банкова сметка,placeholder=Всички,after=title'
        );

        // Избор на каси (ако има право потребителят)
        $fieldset->FLD(
            'caseId',
            'keylist(mvc=cash_Cases,select=name,allowEmpty)',
            'caption=Каса,placeholder=Всички,after=accountId'
        );

        // Филтър по тип на документите: приходи, разходи или всички
        $fieldset->FLD(
            'documentType',
            'enum(all=Всички,income=Приходни документи,expense=Разходни документи,oneRD=Всички с поне един приходен,onePd=Всички с поне един разходен)',
            'caption=Документи,placeholder=Всички,after=caseId'
        );

        // Хоризонт (краен срок за плащане, до който да влизат документите)
        $fieldset->FLD(
            'horizon',
            'time',
            'caption=Хоризонт,after=documentType'
        );

        // Сортиране по дата на плащане: възходящо или низходящо
        $fieldset->FLD(
            'sortDirection',
            'enum(desc=По-нови първо, asc=По-сатари първо)',
            'caption=Сортиране->Посока,after=horizon,maxRadio=2'
        );

        // Дали да бъде групирана справката по контрагент
        $fieldset->FLD(
            'groupBy',
            'enum(yes=Групирано,no=Без групиране)',
            'caption=Сортиране->Групиране,after=sortDirection,maxRadio=2'
        );

        // Филтър за показване: всички контрагенти или само такива с разходни документи
        $fieldset->FLD(
            'filterDisplay',
            'enum(all=Всички,filtered=Филтрирано)',
            'caption=Сортиране->Покажи,after=groupBy,maxRadio=2'
        );
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

        // Зареждаме опциите за банкови сметки, според правата на потребителя
        $accounts = self::getContableAccounts($form->rec);
        $form->setSuggestions('accountId', array('' => '') + $accounts);

        // Зареждаме опциите за каси, според правата на потребителя
        $cases = self::getContableCases($form->rec);
        $form->setSuggestions('caseId', array('' => '') + $cases);

        // Задаваме дефолтните стойности само при създаване на нова справка
        if (!$form->rec->id) {
            $form->setDefault('documentType', 'all');
            $form->setDefault('sortDirection', 'desc');
            $form->setDefault('groupBy', 'yes');
            $form->setDefault('filterDisplay', 'all');
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
     * @param stdClass $rec - запис с настройките от формата
     * @param stdClass|null &$data - обект за допълнителни данни (например groupByField)
     * @return array - масив от записи за показване
     */
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec - записът на справката (настройките от формата)
     * @param stdClass|null $data - допълнителни данни (не се използва тук)
     *
     * @return array - масив от записи за рендиране
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $docClasses = $caseRecs = $bankRecs = $recs = array();

        if ($rec->groupBy == 'yes') {
            $this->groupByField = 'contragentName';
        }

        // Ако няма групиране зададено изрично, по подразбиране включваме групиране
        if (empty($rec->data) || empty($rec->data->groupByField)) {
            $rec->data = (object)array('groupByField' => 'contragentName');
        }

        // Зареждаме банковите и касовите сметки, достъпни за потребителя
        $accountsId = isset($rec->accountId) ? keylist::toArray($rec->accountId) : array_keys(self::getContableAccounts($rec));
        $casesId = isset($rec->caseId) ? keylist::toArray($rec->caseId) : array_keys(self::getContableCases($rec));

        // Определяме филтъра по вид документ (приходни, разходни, всички)
        $docTypeFilter = $rec->documentType; // all, income, expense

        // Определяме дали филтрираме само банкови, само касови или и двете
        $both = (!isset($rec->accountId) && !isset($rec->caseId)) || (isset($rec->accountId, $rec->caseId));

        // Обработваме банковите документи
        if ($both || isset($rec->accountId)) {
            foreach (array('bank_SpendingDocuments', 'bank_IncomeDocuments') as $pDoc) {

                $isIncome = ($pDoc == 'bank_IncomeDocuments');
                $isExpense = ($pDoc == 'bank_SpendingDocuments');

                // Филтриране според избрания тип документ
                if (($docTypeFilter == 'income' && !$isIncome) || ($docTypeFilter == 'expense' && !$isExpense)) {
                    continue;
                }

                $cQuery = $pDoc::getQuery();
                $cQuery->in('ownAccount', $accountsId);
                $cQuery->orWhere('#ownAccount IS NULL');
                $cQuery->where("#state = 'pending'");
                $cQuery->orderBy('termDate', 'ASC');

                while ($cRec = $cQuery->fetch()) {

                    // Определяме крайна дата за плащане (payDate) според наличните полета
                    if (!empty($cRec->termDate)) {
                        $payDate = $cRec->termDate;
                    } elseif (!empty($cRec->valior)) {
                        $payDate = $cRec->valior;
                    } else {
                        $payDate = $cRec->createdOn;
                    }

                    $className = core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass);

                    // Проверка на правата за текущия потребител
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

        // Обработваме касовите документи
        if ($both || isset($rec->caseId)) {
            foreach (array('cash_Rko', 'cash_Pko') as $pDoc) {

                $isIncome = ($pDoc == 'cash_Pko');
                $isExpense = ($pDoc == 'cash_Rko');

                if (($docTypeFilter == 'income' && !$isIncome) || ($docTypeFilter == 'expense' && !$isExpense)) {
                    continue;
                }

                $cQuery = $pDoc::getQuery();
                $cQuery->in('peroCase', $casesId);
                $cQuery->orWhere('#peroCase IS NULL');
                $cQuery->where("#state = 'pending'");
                $cQuery->orderBy('termDate', 'ASC');

                while ($cRec = $cQuery->fetch()) {

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

        $recs = $bankRecs + $caseRecs;

        // Изчисляваме тоталните суми по контрагент и валута
        $totalContragentaSumArr = array();

        $contragentExpenseFlags = array();

        foreach ($recs as $r) {
            $m = 1;
            $DoocClass = cls::get($r->className);
            $docAbbr = $DoocClass->abbr;

            if (!in_array($docAbbr, array('Pbd', 'Pko'))) {
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

            // ТОЧНО ТУК попълваме contragentExpenseFlags:
            if (!isset($contragentExpenseFlags[$r->folderId])) {
                $contragentExpenseFlags[$r->folderId] = (in_array($docAbbr, array('Rbd', 'Rko'))) ? 1 : 0;
            } elseif ($contragentExpenseFlags[$r->folderId] == 0 && in_array($docAbbr, array('Rbd', 'Rko'))) {
                $contragentExpenseFlags[$r->folderId] = 1;
            }
        }

        // Ако е избрано филтрирано показване
        if ($rec->filterDisplay == 'filtered') {
            $recs = $this->filterRecsByFirstExpense($recs, $contragentExpenseFlags);
        }

        // Подреждаме резултатите по дата на плащане според избраната посока
        $order = ($rec->sortDirection == 'asc') ? 1 : -1;
        usort($recs, function ($a, $b) use ($order) {
            return ($a->payDate <=> $b->payDate) * $order;
        });

        return $recs;
    }

    /**
     * След като справката е подготвила записите си, прилагаме филтъра за показване
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass $data
     */
    protected static function on_AfterPrepareDetailRecs(frame2_driver_Proto $Driver, &$data)
    {
        $rec = $data->rec;

        // Проверяваме дали сме в групиращ режим и дали е избрана опция "филтрирано"
        if ($rec->groupBy == 'yes' && $rec->filterDisplay == 'filtered') {
            foreach ($data->recs as $key => $dRec) {
                // Проверяваме има ли поне един разходен документ за този контрагент
                $hasExpense = false;
                if (isset($dRec->totalSumContr[$dRec->folderId])) {
                    foreach ($dRec->totalSumContr[$dRec->folderId] as $cur => $val) {
                        if ($val < 0) {
                            $hasExpense = true;
                            break;
                        }
                    }
                }

                // Ако няма разходни документи, премахваме реда
                if (!$hasExpense) {
                    unset($data->recs[$key]);
                }
            }
        }
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
     * @param stdClass $rec - запис на справката (настройки от формата)
     * @param stdClass $dRec - редов запис от резултата
     *
     * @return stdClass $row - вербален запис за визуализиране в таблицата
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        // Зареждаме помощните типове за форматиране
        $Double = core_Type::getByName('double(smartRound)');
        $Date = cls::get('type_Date');

        $row = new stdClass();

        // Генерираме линк към конкретния документ
        $DocClass = cls::get($dRec->className);
        $row->documentId = $DocClass->getLink($dRec->documentId, 0);

        // Обработка на контрагентното име
        if ($rec->groupBy == 'yes') {
            // Групиран изглед - добавяме суми за групиращия ред
            $sums = array();

            if (isset($dRec->totalSumContr[$dRec->folderId])) {
                foreach ($dRec->totalSumContr[$dRec->folderId] as $cur => $val) {
                    $absVal = abs($val);
                    $verbalVal = $Double->toVerbal($absVal);
                    $displayVal = ($val < 0) ? '-' . $verbalVal : $verbalVal;

                    // Оцветяване според знака
                    if ($val < 0) {
                        $styledVal = "<span style='color:red'>{$displayVal}</span>";
                    } elseif ($val > 0) {
                        $styledVal = "<span style='color:green'>{$displayVal}</span>";
                    } else {
                        $styledVal = $displayVal;
                    }

                    $sums[] = "<span class='fright'>{$styledVal} <span class='cCode'>{$cur}</span></span>";
                }
            }

            $row->contragentName = $dRec->contragentName . implode('', $sums);
        } else {
            // Без групиране - само името на контрагента
            $row->contragentName = $dRec->contragentName;
        }

        // Данни за създаване
        if (isset($dRec->createdBy)) {
            $row->createdBy = crm_Profiles::createLink($dRec->createdBy);
            $row->createdOn = $Date->toVerbal($dRec->createdOn);
        }
        $row->created = $row->createdOn . ' от ' . $row->createdBy;

        // Форматиране на сумата по документа
        $val = $dRec->amountDeal;
        $absVal = abs($val);
        $verbalVal = $Double->toVerbal($absVal);
        $displayVal = ($val < 0) ? '-' . $verbalVal : $verbalVal;
        $styledAmount = ht::styleIfNegative($displayVal, $val);
        $row->amountDeal = $styledAmount;

        // Падежна дата
        $row->payDate = $Date->toVerbal($dRec->payDate);

        // Валутен код
        $row->currencyId = currency_Currencies::getCodeById($dRec->currencyId);

        return $row;
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver - драйверът
     * @param stdClass $res - вербализирания ред за експорта
     * @param stdClass $rec - записа на справката (настройки от формата)
     * @param stdClass $dRec - оригиналния запис от prepareRecs()
     * @param core_BaseClass $ExportClass - експортен клас
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        // Вместо линк, в експорта изкарваме текстовия хендъл на документа
        $DocClass = cls::get($dRec->className);
        $handle = $DocClass->getHandle($dRec->documentId, 0);
        $res->documentId = '#' . $handle;
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
        // Създаваме шаблон за вмъкване на бутоните във филтър панела
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
        <fieldset class='detail-info'>
            <legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
            <div class='small'>
                <!--ET_BEGIN button--><div>[#button#]</div><!--ET_END button-->
            </div>
        </fieldset><!--ET_END BLOCK-->"));

        // URL за екшъна "Избор на контрагент"
        $filterUrl = array('deals_reports_ReportPaymentDocuments', 'contragentFilter', 'recId' => $data->rec->id, 'ret_url' => true);

        // Добавяме бутона "Избери контрагент"
        $toolbar = cls::get('core_Toolbar');
        $toolbar->addBtn('Избери контрагент', toUrl($filterUrl));

        // Вмъкваме бутона в шаблона
        $fieldTpl->append('<b>' . $toolbar->renderHtml() . '</b>', 'button');

        // Добавяме панела към основния шаблон
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

    /**
     * Връща класовете на документите според избрания тип (приходни или разходни)
     *
     * @param string $type - 'income' (приходни), 'expense' (разходни), 'all' (всички)
     * @return array - масив с classId на съответните документи
     */
    protected function getDocumentClassesByType($type)
    {
        $docClasses = array();

        // Всички документи (ако не е избрано нищо или е избрано 'all')
        if (empty($type) || $type == 'all') {
            $docClasses[] = bank_SpendingDocuments::getClassId();
            $docClasses[] = bank_IncomeDocuments::getClassId();
            $docClasses[] = cash_Pko::getClassId();
            $docClasses[] = cash_Rko::getClassId();
        }

        // Само приходни
        if ($type == 'income') {
            $docClasses[] = bank_IncomeDocuments::getClassId();
            $docClasses[] = cash_Pko::getClassId();
        }

        // Само разходни
        if ($type == 'expense') {
            $docClasses[] = bank_SpendingDocuments::getClassId();
            $docClasses[] = cash_Rko::getClassId();
        }

        return $docClasses;
    }

    protected function filterRecsByFirstExpense($recs, $contragentExpenseFlags)
    {
        $filtered = array();

        foreach ($recs as $r) {
            $folderId = $r->folderId;

            // Ако има разходни документи (стойност 1), запазваме реда
            if (isset($contragentExpenseFlags[$folderId]) && $contragentExpenseFlags[$folderId] == '1') {
                $filtered[] = $r;
            }

            // Ако няма никакъв запис в масива за този folderId — няма да го показваме
        }

        return $filtered;
    }

}
