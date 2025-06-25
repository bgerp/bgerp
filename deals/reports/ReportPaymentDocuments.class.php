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
            'enum(all=Всички,income=Приходни документи,expense=Разходни документи,oneRD=Всички с поне един разходен,onePD=Всички с поне един приходен)',
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
            'enum(desc=По-нови първо, asc=По-стари първо)',
            'caption=Сортиране->Посока,after=horizon,maxRadio=2'
        );

        // Дали да бъде групирана справката по контрагент
        $fieldset->FLD(
            'groupBy',
            'enum(yes=Групирано,no=Без групиране)',
            'caption=Сортиране->По контрагент,after=sortDirection,maxRadio=2'
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
    /**
     * Подготвя записите, които ще се визуализират в справката
     *
     * @param stdClass $rec - Настройките от формата (филтри, сортиране и групиране)
     * @param stdClass|null $data - Допълнителни данни (не се използва тук)
     *
     * @return array - Масив с обработените записи за визуализация
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $docClasses = $caseRecs = $bankRecs = $recs = array();

        // ЗАДАВАМЕ ГРУПИРАНЕТО СПОРЕД ИЗБОРА ОТ ФОРМАТА
        if ($rec->groupBy == 'yes') {
            $this->groupByField = 'contragentName';
        }

        $accountsId = isset($rec->accountId) ? keylist::toArray($rec->accountId) : array_keys(self::getContableAccounts($rec));
        $casesId = isset($rec->caseId) ? keylist::toArray($rec->caseId) : array_keys(self::getContableCases($rec));

        // Пълним отделни флагове по контрагент за филтриране по "поне един приходен/разходен"
        $contragentExpenseFlags = $contragentIncomeFlags = array();

        // --- БАНКОВИ документи ---
        foreach (array('bank_SpendingDocuments', 'bank_IncomeDocuments') as $pDoc) {

            $cQuery = $pDoc::getQuery();
            $cQuery->in('ownAccount', $accountsId);
            $cQuery->orWhere('#ownAccount IS NULL');
            $cQuery->where("#state = 'pending'");
            $cQuery->orderBy('termDate', 'ASC');

            while ($cRec = $cQuery->fetch()) {

                $docAbbr = cls::get(core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass))->abbr;

                // ТУК ФИЛТРИРАМЕ НА МОМЕНТА според избрания documentType
                if ($rec->documentType == 'income' && !in_array($docAbbr, array('Pbd', 'Pko'))) continue;
                if ($rec->documentType == 'expense' && !in_array($docAbbr, array('Rbd', 'Rko'))) continue;

                $folderId = $cRec->folderId;
                // Попълваме флаговете
                if (!isset($contragentExpenseFlags[$folderId])) {
                    $contragentExpenseFlags[$folderId] = in_array($docAbbr, array('Rbd', 'Rko')) ? 1 : 0;
                }
                if (!isset($contragentIncomeFlags[$folderId])) {
                    $contragentIncomeFlags[$folderId] = in_array($docAbbr, array('Pbd', 'Pko')) ? 1 : 0;
                }

                $payDate = $cRec->termDate ?? $cRec->valior ?? $cRec->createdOn;

                $bankRecs[$cRec->containerId] = (object)[
                    'containerId' => $cRec->containerId,
                    'amountDeal' => $cRec->amountDeal,
                    'totalSumContr' => array(),
                    'className' => core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass),
                    'payDate' => $payDate,
                    'currencyId' => $cRec->currencyId,
                    'documentId' => $cRec->id,
                    'folderId' => $cRec->folderId,
                    'createdOn' => $cRec->createdOn,
                    'createdBy' => $cRec->createdBy,
                    'ownAccount' => $cRec->ownAccount,
                    'peroCase' => $cRec->peroCase,
                    'contragentName' => $cRec->contragentName,
                ];
            }
        }

        // --- КАСОВИ документи ---
        foreach (array('cash_Rko', 'cash_Pko') as $pDoc) {

            $cQuery = $pDoc::getQuery();
            $cQuery->in('peroCase', $casesId);
            $cQuery->orWhere('#peroCase IS NULL');
            $cQuery->where("#state = 'pending'");
            $cQuery->orderBy('termDate', 'ASC');

            while ($cRec = $cQuery->fetch()) {

                $docAbbr = cls::get(core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass))->abbr;

                // ТУК ФИЛТРИРАМЕ НА МОМЕНТА според избрания documentType
                if ($rec->documentType == 'income' && !in_array($docAbbr, array('Pbd', 'Pko'))) continue;
                if ($rec->documentType == 'expense' && !in_array($docAbbr, array('Rbd', 'Rko'))) continue;

                $folderId = $cRec->folderId;
                // Попълваме флаговете
                if (!isset($contragentExpenseFlags[$folderId])) {
                    $contragentExpenseFlags[$folderId] = in_array($docAbbr, array('Rbd', 'Rko')) ? 1 : 0;
                }
                if (!isset($contragentIncomeFlags[$folderId])) {
                    $contragentIncomeFlags[$folderId] = in_array($docAbbr, array('Pbd', 'Pko')) ? 1 : 0;
                }

                $payDate = $cRec->termDate ?? $cRec->valior ?? $cRec->createdOn;

                $caseRecs[$cRec->containerId] = (object)[
                    'containerId' => $cRec->containerId,
                    'amountDeal' => $cRec->amountDeal,
                    'totalSumContr' => array(),
                    'className' => core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass),
                    'payDate' => $payDate,
                    'currencyId' => $cRec->currencyId,
                    'documentId' => $cRec->id,
                    'folderId' => $cRec->folderId,
                    'createdOn' => $cRec->createdOn,
                    'createdBy' => $cRec->createdBy,
                    'ownAccount' => $cRec->ownAccount,
                    'peroCase' => $cRec->peroCase,
                    'contragentName' => $cRec->contragentName,
                ];
            }
        }

        // Събираме банковите и касовите
        $recs = $bankRecs + $caseRecs;

        // Сумиране по контрагент и валута
        $totalContragentaSumArr = [];
        foreach ($recs as $r) {
            $docAbbr = cls::get($r->className)->abbr;
            $m = in_array($docAbbr, array('Pbd', 'Pko')) ? 1 : -1;
            $curCode = currency_Currencies::getCodeById($r->currencyId);
            if (!isset($totalContragentaSumArr[$r->folderId])) {
                $totalContragentaSumArr[$r->folderId] = array();
            }
            if (!isset($totalContragentaSumArr[$r->folderId][$curCode])) {
                $totalContragentaSumArr[$r->folderId][$curCode] = 0;
            }
            $totalContragentaSumArr[$r->folderId][$curCode] += $r->amountDeal * $m;
        }
        // След сумирането записваме към всеки ред неговата текуща totalSumContr:
        foreach ($recs as $r) {
            $r->totalSumContr = $totalContragentaSumArr[$r->folderId];
        }

        // Тук вече при нужда активираме контрагентното филтриране
        if (in_array($rec->documentType, array('oneRD', 'onePD'))) {
            $recs = $this->filterRecsByContragentFlags($recs, $contragentExpenseFlags, $contragentIncomeFlags, $rec->documentType);
        }

        // Подреждане по дата
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
     * @param stdClass $rec - запис на справката (настройки от формата)
     * @param stdClass $dRec - редов запис от резултата
     *
     * @return stdClass $row - вербален запис за визуализиране в таблицата
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Double = core_Type::getByName('double(smartRound)');
        $Date = cls::get('type_Date');
        $row = new stdClass();

        // Линк към документа
        $DoocClass = cls::get($dRec->className);
        $row->documentId = $DoocClass->getLink($dRec->documentId, 0);

        // Групиращ ред (ако сме в групиране)
        if ($rec->groupBy == 'yes') {
            $sums = array();
            foreach ($dRec->totalSumContr as $cur => $val) {
                $absVal = abs($val);
                $verbalVal = $Double->toVerbal($absVal);
                $displayVal = ($val < 0) ? '-' . $verbalVal : $verbalVal;

                $styledVal = ($val < 0)
                    ? "<span style='color:red'>{$displayVal}</span>"
                    : "<span style='color:green'>{$displayVal}</span>";

                $sums[] = "<span class='fright'>{$styledVal} <span class='cCode'>{$cur}</span></span>";
            }
            $row->contragentName = $dRec->contragentName . implode('', $sums);
        } else {
            $row->contragentName = $dRec->contragentName;
        }

        // Форматиране на amountDeal с цвят според типа на документа
        $docAbbr = cls::get($dRec->className)->abbr;

        $val = $dRec->amountDeal;
        $absVal = abs($val);
        $verbalVal = $Double->toVerbal($absVal);
        $displayVal = ($val < 0) ? '-' . $verbalVal : $verbalVal;

        if (in_array($docAbbr, array('Pbd', 'Pko'))) {
            // Приходен документ → зелено
            $styledAmount = "<span style='color:green'>{$displayVal}</span>";
        } elseif (in_array($docAbbr, array('Rbd', 'Rko'))) {
            // Разходен документ → червено
            $styledAmount = "<span style='color:red'>{$displayVal}</span>";
        } else {
            // За всеки случай - стандартно оцветяване
            $styledAmount = ht::styleIfNegative($displayVal, $val);
        }

        $row->amountDeal = $styledAmount;

        // Другите полета
        $row->payDate = $Date->toVerbal($dRec->payDate);
        $row->currencyId = currency_Currencies::getCodeById($dRec->currencyId);
        $row->created = $Date->toVerbal($dRec->createdOn) . ' от ' . crm_Profiles::createLink($dRec->createdBy);

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


    /**
     * Обновява флаговете дали контрагентът има приходи и/или разходи
     *
     * @param array &$contragentFlags - референтен масив с текущите флагове
     * @param int $folderId - идентификатор на контрагента
     * @param string $docAbbr - съкращението на текущия документ
     */
    // За отбелязване дали контрагента има разходни или приходни документи
    protected function updateContragentFlags(&$expenseFlags, &$incomeFlags, $r)
    {
        $docAbbr = cls::get($r->className)->abbr;
        $folderId = $r->folderId;

        if (in_array($docAbbr, array('Rbd', 'Rko'))) {
            $expenseFlags[$folderId] = 1;
        }
        if (in_array($docAbbr, array('Pbd', 'Pko'))) {
            $incomeFlags[$folderId] = 1;
        }
    }

// Филтриране на масива recs според съответните флагове
    protected function filterRecsByFlags($recs, $flags, $requireFlag)
    {
        $filtered = array();

        foreach ($recs as $id => $r) {
            $folderId = $r->folderId;
            if (isset($flags[$folderId]) && $flags[$folderId] == 1) {
                $filtered[$id] = $r;
            }
        }

        return $filtered;
    }

    /**
     * Филтрира записите  в зависимост от избора в documentType
     *
     * @param array $recs - текущите записи
     * @param string $documentType - избрания тип в справката
     * @param array $contragentFlags - масив със статуси приходи/разходи по контрагент
     * @return array - отфилтрирани записи
     */
    protected function filterRecsByDocumentType($recs, $documentType, $contragentFlags)
    {
        if (in_array($documentType, ['oneRD', 'onePD'])) {
            foreach ($recs as $key => $r) {
                $folderId = $r->folderId;

                if ($documentType == 'oneRD' && empty($contragentFlags[$folderId]['expense'])) {
                    unset($recs[$key]);
                }

                if ($documentType == 'onePD' && empty($contragentFlags[$folderId]['income'])) {
                    unset($recs[$key]);
                }
            }
        }

        return $recs;
    }

    /**
     * Филтрира масива $recs според избраната стойност на documentType и флаговете на контрагентите.
     *
     * @param array $recs                   - масива със събрани записи (документите)
     * @param array $contragentExpenseFlags - масив с флагове (1 => има разходен документ) по folderId
     * @param array $contragentIncomeFlags  - масив с флагове (1 => има приходен документ) по folderId
     * @param string $documentType          - избраната опция от полето 'documentType' във формата
     *
     * @return array - филтрирания масив $recs
     */
    protected function filterRecsByContragentFlags($recs, $contragentExpenseFlags, $contragentIncomeFlags, $documentType)
    {
        foreach ($recs as $k => $r) {
            if ($documentType == 'oneRD' && empty($contragentExpenseFlags[$r->folderId])) {
                unset($recs[$k]);
            }
            if ($documentType == 'onePD' && empty($contragentIncomeFlags[$r->folderId])) {
                unset($recs[$k]);
            }
        }
        return $recs;
    }

}
