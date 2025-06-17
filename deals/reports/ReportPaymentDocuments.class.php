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
     * Добавя полетата на драйвъра към формата на справката
     *
     * @param core_Fieldset $fieldset - обект за добавяне на формови полета
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        // ===============================
        // Поле: Банкова сметка
        // Тип: keylist към модел bank_OwnAccounts
        // Позволява избор на една или повече банкови сметки
        // ===============================
        $fieldset->FLD(
            'accountId',
            'keylist(mvc=bank_OwnAccounts,select=title,allowEmpty)',
            'caption=Банкова сметка,placeholder=Всички,after=title'
        );

        // ===============================
        // Поле: Каса
        // Тип: keylist към модел cash_Cases
        // Позволява избор на една или повече каси
        // ===============================
        $fieldset->FLD(
            'caseId',
            'keylist(mvc=cash_Cases,select=name,allowEmpty)',
            'caption=Каса,placeholder=Всички,after=accountId'
        );

        // ===============================
        // Поле: Вид на документа
        // Тип: keylist към core_Classes
        // Позволява избор на вид документ (напр. приходен, разходен)
        // ===============================
        $fieldset->FLD(
            'documentType',
            'keylist(mvc=core_Classes,select=title)',
            'caption=Документи,placeholder=Всички,after=caseId'
        );

        // ===============================
        // Поле: Хоризонт
        // Тип: време (брой секунди)
        // Определя времевия период, в който да се търсят документи
        // ===============================
        $fieldset->FLD(
            'horizon',
            'time',
            'caption=Хоризонт,after=documentType'
        );

        // ===============================
        // Поле: Групиране по контрагент
        // Тип: enum със стойности "yes" и "no"
        // Представено като радио бутон (maxRadio=2)
        // По подразбиране: да
        // ===============================
        $fieldset->FLD(
            'groupByContragent',
            'enum(yes=Да,no=Не)',
            'caption=Групиране->по контрагент,input=radio,maxRadio=2,after=horizon'
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

        if (!isset($form->rec->groupByContragent)) {
            $form->rec->groupByContragent = 'yes';
        }

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
     * Подготвя записите за показване в таблицата на справката
     *
     * @param stdClass $rec  - запис с настройките от формата
     * @param stdClass|null &$data - обект за допълнителни данни (например groupByField)
     * @return array - масив от записи за показване
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $docClasses = $caseRecs = $bankRecs = $recs = array();

        // Взимаме избраните банкови сметки или всички, ако не са избрани
        $accountsId = isset($rec->accountId)
            ? keylist::toArray($rec->accountId)
            : array_keys(self::getContableAccounts($rec));

        // Взимаме избраните каси или всички, ако не са избрани
        $casesId = isset($rec->caseId)
            ? keylist::toArray($rec->caseId)
            : array_keys(self::getContableCases($rec));

        // Поле за филтриране по вид документ
        $documentFld = ($rec->documentType) ? 'documentType' : 'document';
        $docClasses = keylist::toArray($rec->documentType);

        // Дали са избрани и каса, и сметка
        $both = (!isset($rec->accountId) && !isset($rec->caseId)) || (isset($rec->accountId, $rec->caseId));

        // ➕ Активиране на групиране по контрагент, ако е избрано от формата
        if ($rec->groupByContragent === 'yes') {
            if (!is_object($data)) {
                $data = (object)[];
            }
            $data->groupByField = 'contragentName';
        }

        // ======================
        // Обработка на банкови документи
        // ======================
        if ($both || isset($rec->accountId)) {
            foreach (array('bank_SpendingDocuments', 'bank_IncomeDocuments') as $pDoc) {
                // Филтриране по вид документ (ако е избран)
                if (empty($docClasses) || in_array($pDoc::getClassId(), $docClasses)) {
                    $cQuery = $pDoc::getQuery();
                    $cQuery->in('ownAccount', $accountsId);
                    $cQuery->orWhere('#ownAccount IS NULL');
                    $cQuery->where("#state = 'pending'");
                    $cQuery->orderBy('termDate', 'ASC');

                    while ($cRec = $cQuery->fetch()) {
                        // Проверка за хоризонт – пропускаме записи след хоризонта
                        $payDate = ($cRec->termDate) ? $cRec->termDate : $cRec->valior;

                        if (!empty($rec->horizon)) {
                            $horizon = dt::addSecs($rec->horizon, dt::today(), false);
                            if ($payDate && ($payDate > $horizon)) {
                                continue;
                            }
                        }

                        // Проверка на права за достъп до документа
                        $className = core_Classes::getName(doc_Containers::fetch($cRec->containerId)->docClass);

                        if (core_Users::getCurrent() != $cRec->createdBy) {
                            $Document = doc_Containers::getDocument($cRec->containerId);
                            if (!$Document->haveRightFor('single', $rec->createdBy)) {
                                continue;
                            }
                        }

                        // Събиране на резултат
                        $bankRecs[$cRec->containerId] = (object)[
                            'containerId'     => $cRec->containerId,
                            'amountDeal'      => $cRec->amountDeal,
                            'totalSumContr'   => array(),
                            'className'       => $className,
                            'payDate'         => $payDate,
                            'termDate'        => $cRec->termDate,
                            'valior'          => $cRec->valior,
                            'currencyId'      => $cRec->currencyId,
                            'documentId'      => $cRec->id,
                            'folderId'        => $cRec->folderId,
                            'createdOn'       => $cRec->createdOn,
                            'createdBy'       => $cRec->createdBy,
                            'ownAccount'      => $cRec->ownAccount,
                            'peroCase'        => $cRec->peroCase,
                            'contragentName'  => $cRec->contragentName,
                        ];
                    }
                }
            }
        }

        // ======================
        // Обработка на касови документи
        // ======================
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

                        $caseRecs[$cRec->containerId] = (object)[
                            'containerId'     => $cRec->containerId,
                            'amountDeal'      => $cRec->amountDeal,
                            'totalSumContr'   => array(),
                            'className'       => $className,
                            'payDate'         => $payDate,
                            'termDate'        => $cRec->termDate,
                            'valior'          => $cRec->valior,
                            'currencyId'      => $cRec->currencyId,
                            'documentId'      => $cRec->id,
                            'folderId'        => $cRec->folderId,
                            'createdOn'       => $cRec->createdOn,
                            'createdBy'       => $cRec->createdBy,
                            'ownAccount'      => $cRec->ownAccount,
                            'peroCase'        => $cRec->peroCase,
                            'contragentName'  => $cRec->contragentName,
                        ];
                    }
                }
            }
        }

        // Обединяване на резултатите от двете групи документи
        $recs = $bankRecs + $caseRecs;

        // ======================
        // Групиране по контрагент (обща сума по folderId)
        // ======================
        $totalContragentaSumArr = [];

        foreach ($recs as $r) {
            $m = 1;
            $DoocClass = cls::get($r->className);

            // Приходни документи: положителна сума
            if (!in_array($DoocClass->abbr, ['Pbd', 'Pko'])) {
                $m = -1; // разход → отрицателна
            }

            // Сумиране по folderId (контрагент)
            if (!array_key_exists($r->folderId, $totalContragentaSumArr)) {
                $totalContragentaSumArr[$r->folderId] = $r->amountDeal * $m;
            } else {
                $totalContragentaSumArr[$r->folderId] += $r->amountDeal * $m;
            }

            // Запазваме референция към сумата, за визуализиране
            $r->totalSumContr = $totalContragentaSumArr;
        }

        // Финално сортиране по дата на плащане
        usort($recs, [$this, 'orderByPayDate']);

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
     * @param stdClass $rec   - запис на справката (настройки от формата)
     * @param stdClass $dRec  - редов запис от резултата
     *
     * @return stdClass $row  - вербален (четим) запис за визуализиране в таблицата
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        // Зареждане на типове за форматиране
        $Int = cls::get('type_Int');
        $Double = core_Type::getByName('double(smartRound)');
        $Date = cls::get('type_Date');

        // Инициализиране на резултатния обект
        $row = new stdClass();

        // Визуализиране на документа (линк към него)
        $DoocClass = cls::get($dRec->className);
        $row->documentId = $DoocClass->getLink($dRec->documentId, 0);

        // --- ПОЛЕ: Контрагент (възможно групиране) ---

        // Проверка дали е групирано по контрагент чрез наличието на totalSumContr
        if (!isset($dRec->totalSumContr) || empty($dRec->totalSumContr)) {
            // Няма групиране → показваме само името на контрагента
            $row->contragentName = $dRec->contragentName;
        } else {
            // Групирано → добавяме обща сума с оцветяване

            $sum = $dRec->totalSumContr[$dRec->folderId];
            $verbalSum = core_Type::getByName('double(decimals=2)')->toVerbal($sum);
            $currencyCode = currency_Currencies::getCodeById($dRec->currencyId);

            if ($sum >= 0) {
                // Положителна сума → зелено
                $row->contragentName = $dRec->contragentName .
                    '<span style="color: green" class="fright">' .
                    $verbalSum .
                    '<span class="cCode" style="position:relative; top: -2px; margin-left: 2px;">' .
                    $currencyCode . '</span>';
            } else {
                // Отрицателна сума → червено
                $row->contragentName = $dRec->contragentName .
                    '<span style="color: red" class="fright">' .
                    $verbalSum .
                    '<span class="cCode" style="position:relative; top: -2px; margin-left: 2px;">' .
                    $currencyCode . '</span>';
            }
        }

        // --- ПОЛЕ: Автор и дата на създаване ---

        if (isset($dRec->createdBy)) {
            // Създал документ – показваме като линк
            $row->createdBy = crm_Profiles::createLink($dRec->createdBy);

            // Дата на създаване
            $row->createdOn = $Date->toVerbal($dRec->createdOn);
        }

        // Сглобяване на поле "Създадено"
        $row->created = $row->createdOn . ' от ' . $row->createdBy;

        // --- ПОЛЕ: Сума ---

        // Подсказка с банковата сметка или каса
        $hint = ($dRec->ownAccount)
            ? bank_OwnAccounts::getTitleById($dRec->ownAccount)
            : cash_Cases::getTitleById($dRec->peroCase);

        $hint = $hint ?: 'не посочена'; // ако няма стойност

        // Цветово разграничение: зелено за приход, червено за разход
        if (in_array($DoocClass->abbr, array('Pbd', 'Pko'))) {
            // Приход
            $amountHtml = '<span style="color: green">' .
                core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amountDeal) .
                '</span>';
        } else {
            // Разход
            $amountHtml = '<span style="color: red">' .
                core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amountDeal) .
                '</span>';
        }

        // Добавяне на hint
        $row->amountDeal = ht::createHint($amountHtml, $hint, 'notice');

        // --- ПОЛЕ: Дата за плащане ---
        $row->payDate = ($dRec->payDate)
            ? $Date->toVerbal($dRec->payDate)
            : tr('|*<span class="quiet">|не е посочен|*</span>');

        // --- ПОЛЕ: Валута ---
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
