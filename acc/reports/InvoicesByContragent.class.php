<?php


/**
 * Справка „Фактури по контрагент“ — изходящи, входящи или двата вида.
 *
 * Основен поток на изпълнение:
 * 1. addFields()              — дефинира филтрите на формата
 * 2. on_AfterPrepareEditForm() — начални стойности и suggestions за контрагенти
 * 3. prepareRecs()            — извлича фактури, плащания и остатъци към дата
 * 4. detailRecToVerbal()      — форматира редовете за екран (вкл. EUR/BGN)
 * 5. on_AfterRenderSingle()   — блок с обобщение на филтрите и тоталите
 *
 * Режим unpaid=all  — всички фактури в периода с платена сума.
 * Режим unpaid=unpaid — само фактури с остатък извън прага sill.
 * Режим unpaid=overdue — неплатени фактури с падеж преди днес.
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Счетоводство » Фактури по контрагент
 */
class acc_reports_InvoicesByContragent extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,acc,sales,purchase';


    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'contragent';


    /**
     * Кои полета от таблицата са цени/суми
     */
    protected $priceListFields = 'invoiceValue,invoiceValueBaseCurr,paidAmount,invoiceCurrentSumm,invoiceOverSumm,accountingCorrection';


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'contragent,checkDate,crmGroup,typeOfInvoice,unpaid,accountingCorrections';


    /**
     * Показателите на справката, в реда на обработката
     */
    protected static $statCaptions = array(
        'params' => 'Филтър',
        'folders' => 'Папки във филтъра',
        'deals' => 'Обединени/бързи сделки',
        'unitedDeals' => 'Затворени с обединяване',
        'fastDeals' => 'Бързи сделки',
        'proformas' => 'Проформи с насочени плащания',
        'sales_Invoices' => 'Изходящи фактури',
        'sales_Proformas' => 'Проформи',
        'purchase_Invoices' => 'Входящи фактури',
        'noFirstDocument' => 'Пропуснати: без първи документ в нишката',
        'closedDeal' => 'Пропуснати: приключена сделка',
        'closedDealThreads' => 'Нишки на пропуснатите приключени сделки',
        'sales_InvoicesFetch' => 'Четене на изходящите фактури',
        'sales_ProformasFetch' => 'Четене на проформите',
        'purchase_InvoicesFetch' => 'Четене на входящите фактури',
        'threads' => 'Нишки за остатъци',
        'payments' => 'Разпределени плащания по нишки',
        'repeated' => 'Повторени в друга нишка',
        'missingInvoice' => 'Пропуснати: липсваща фактура (контейнер)',
        'belowSill' => 'Пропуснати: остатък в прага',
        'notOverdue' => 'Пропуснати: без просрочен неплатен остатък',
        'otherPaymentType' => 'Пропуснати: друг начин на плащане',
        'paymentTypeThreads' => 'Нишки с търсения начин на плащане',
        'otherPaymentTypeThread' => 'Пропуснати преди плащанията: друг начин на плащане',
        'afterCheckDate' => 'Пропуснати: след датата „към“',
        'beforeFromDate' => 'Пропуснати: преди началната дата',
        'rows' => 'Редове в справката',
        'accounting' => 'Счетоводни корекции',
        'contragents' => 'Контрагенти',
        'mixedCurrency' => 'Контрагенти с различни валути',
    );


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        // --- Филтри по контрагент ---
        $fieldset->FLD('contragent', 'keylist2(mvc=doc_Folders,select=title,allowEmpty,selectSourceArr=acc_reports_InvoicesByContragent::getContragentFolderOptions,maxSuggestions=200)', 'caption=Контрагенти->Конкретни,placeholderType=all,single=none,after=title');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Контрагенти->Групи,placeholderType=all,after=contragent,single=none');

        // --- Тип документи и режим на плащане ---
        $fieldset->FLD('typeOfInvoice', 'enum(out=Изходящи,in=Входящи,all=Всички)', 'caption=Фактури,after=crmGroup,maxRadio=3,columns=3,mandatory,single=none');
        $fieldset->FLD('unpaid', 'enum(all=Всички,unpaid=Неплатени,overdue=Просрочени)', 'caption=Платено,after=typeOfInvoice,removeAndRefreshForm,single=none,mandatory,silent');

        // --- Период и допълнителни филтри ---
        $fieldset->FLD('fromDate', 'date', 'caption=От дата,after=unpaid, placeholder=от началото,silent');
        $fieldset->FLD('checkDate', 'date', 'caption=До дата,after=fromDate, placeholder=текуща,silent');

        $fieldset->FLD('paymentType', 'enum( ,cash=В брой,bank=По банков път,intercept=С прихващане,card=С карта,factoring=Факторинг,postal=Пощенски паричен превод)', 'caption=Плащане, placeholderType=all,after=checkDate,single=none');

        $fieldset->FLD('sill', 'double', 'caption=Не се показват фактури по приключени сделки с Неплатено/Надплатено->По-малко от,unit= €,input=hidden,after=paymentType,placeholder=0.00,silent,single=none');

        $fieldset->FLD('seeProformаs', 'set(yes=показват се и проформа фактурите)', 'caption=Разширена,after=sill,input,single=none');
        
        $fieldset->FLD('accountingCorrections', 'enum(no=Не,yes=Да)', 'caption=Отчитане на корекции с Мемориален ордер по сметки 401 и 411->Избор,after=seeProformаs,maxRadio=2,columns=2,single=none');

        // --- Обобщаващи суми (попълват се в prepareRecs, показват се в on_AfterRenderSingle) ---
        $fieldset->FNC('totalInvoiceValueAll', 'double', 'input=none,single=none');
        $fieldset->FNC('totalInvoicePayoutAll', 'double', 'input=none,single=none');
        $fieldset->FNC('totalInvoiceNotPaydAll', 'double', 'input=none,single=none');
        $fieldset->FNC('totalInvoiceOverPaidAll', 'double', 'input=none,single=none');
        $fieldset->FNC('totalInvoiceOverDueAll', 'double', 'input=none,single=none');
        $fieldset->FNC('totalAccountingCorrectionAll', 'double', 'input=none,single=none');
        $fieldset->FNC('accountingIncompleteGroups', 'int', 'input=none,single=none');
        $fieldset->FNC('accountingUnallocatedGroups', 'int', 'input=none,single=none');
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

        $form->layout = $form->renderLayout();
        $form->layout->append(' invoicesByContragentForm', 'CLASS');
        $form->layout->push('acc/css/InvoicesByContragent.css', 'CSS');

        // Начални стойности и видимост на полетата според избрания режим (всички / неплатени).
        $form->setDefault('seeProformаs', null);

        $form->setDefault('unpaid', 'all');
        $form->setDefault('accountingCorrections', 'no');

        $form->input('unpaid', 'silent');

        // При „неплатени“ се показва праг sill; проформите се скриват.
        if (($rec->unpaid ?? 'all') != 'all') {
            $form->setField('sill', 'input');
            $form->setField('seeProformаs', 'input=none');
        }


        // При „всички“ крайната дата по подразбиране е днес.
        if (($rec->unpaid ?? 'all') == 'all') {
            $form->setDefault('fromDate', null);
            $checkDate = dt::today(false);
            $form->setDefault('checkDate', $checkDate);
        }


        $form->setDefault('typeOfInvoice', 'out');
    }


    /**
     * Опции за полето „Контрагент“ - само папките, в които има продажби или покупки.
     *
     * @see doc_Folders::getSelectArr()
     */
    public static function getContragentFolderOptions($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        // Вече избраните папки се показват винаги, дори без сделки
        if (isset($onlyIds)) {

            // type_Keylist2 подава избраните като "1,2,3", а getSelectArr приема масив или едно id
            if (is_string($onlyIds)) {
                $onlyIds = explode(',', trim($onlyIds, ','));
            }

            return doc_Folders::getSelectArr($params, $limit, $q, $onlyIds, $includeHiddens);
        }

        $folderIds = core_Cache::get('acc_reports_InvoicesByContragent', 'dealFolders');
        if (!is_array($folderIds)) {

            // Само по индекса на folderId, без JOIN към папките - иначе се сканират всички сделки
            $folderIds = array();
            foreach (array('sales_Sales', 'purchase_Purchases') as $dealClass) {
                $dealQuery = $dealClass::getQuery();
                $dealQuery->groupBy('folderId');
                $dealQuery->show('folderId');
                while ($dRec = $dealQuery->fetch()) {
                    $folderIds[$dRec->folderId] = $dRec->folderId;
                }
            }
            core_Cache::set('acc_reports_InvoicesByContragent', 'dealFolders', $folderIds, 10, array('sales_Sales', 'purchase_Purchases'));
        }

        if (!countR($folderIds)) {

            return array();
        }

        // Затворените папки остават, защото по тях може да има стари фактури
        $where = '#id IN (' . implode(',', $folderIds) . ") AND #state != 'rejected'";
        if (!empty($params['where'])) {
            $where = "({$params['where']}) AND {$where}";
        }
        $params['where'] = $where;

        return doc_Folders::getSelectArr($params, $limit, $q, null, true);
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {

            // Валидира се избраният период.
            if (isset($form->rec->fromDate, $form->rec->checkDate) && ($form->rec->fromDate > $form->rec->checkDate)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }
        }
    }


    /**
     * Изпълнява само SELECT на репликата; обработката остава на основната БД.
     *
     * @param core_Query $query
     * @param int $secondsPerRow
     */
    private static function selectReportQuery($query, $secondsPerRow)
    {
        core_App::setTimeLimit(300);
        $query->selectOnReplica();
        core_App::setTimeLimit(max(300, $query->numRec() * $secondsPerRow));
    }


    /**
     * Нишките, в които има фактура с дадения начин на плащане (изричен или автоматичен)
     *
     * @param string $className
     * @param string $paymentType
     * @param string $checkDate
     * @param array|null $folderIds
     *
     * @return array [threadId => threadId]
     */
    private static function getThreadsWithPaymentType($className, $paymentType, $checkDate, $folderIds)
    {
        /** @var core_Query $query */
        $query = $className::getQuery();
        $query->where("#number IS NOT NULL");
        $query->in('state', 'rejected, draft', true);
        $query->where(array("#date <= '[#1#]'", $checkDate));
        $query->where(array("IF(#paymentType IS NULL OR #paymentType = '', #autoPaymentType, #paymentType) = '[#1#]'", $paymentType));
        if (isset($folderIds)) {
            $query->in('folderId', $folderIds);
        }
        $query->show('threadId');
        $query->groupBy('threadId');
        $query->selectOnReplica();

        $threads = array();
        while ($iRec = $query->fetch()) {
            $threads[$iRec->threadId] = $iRec->threadId;
        }

        return $threads;
    }


    /**
     * Зарежда на партиди първите документи на нишките и при нужда състоянието на сделките
     *
     * @param array $invoices
     * @param array $firstDocuments - [threadId => (className, that)]
     * @param array $dealStates - [className|id => rec]
     * @param bool $withDealStates - дали да се заредят и сделките
     */
    private static function prefetchFirstDocuments($invoices, &$firstDocuments, &$dealStates, $withDealStates)
    {
        $threadIds = array();
        foreach ($invoices as $invoice) {
            if (!empty($invoice->threadId) && !array_key_exists($invoice->threadId, $firstDocuments)) {
                $threadIds[$invoice->threadId] = $invoice->threadId;
            }
        }

        // Класът и ид-то на първия документ се пазят в нишката - контейнерът не трябва
        $dealIds = array();
        foreach (array_chunk($threadIds, 1000) as $chunk) {
            $tQuery = doc_Threads::getQuery();
            $tQuery->in('id', $chunk);
            $tQuery->show('id,firstDocClass,firstDocId');
            while ($tRec = $tQuery->fetch()) {
                if (empty($tRec->firstDocClass) || empty($tRec->firstDocId)) continue;
                $className = core_Classes::getName($tRec->firstDocClass);
                if (empty($className)) continue;
                $firstDocuments[$tRec->id] = (object) array('className' => $className, 'that' => $tRec->firstDocId);
                $dealIds[$className][$tRec->firstDocId] = $tRec->firstDocId;
            }
        }

        if (!$withDealStates) return;

        foreach ($dealIds as $className => $ids) {
            if (!cls::load($className, true)) continue;
            foreach (array_chunk($ids, 1000) as $chunk) {
                /** @var core_Query $dQuery */
                $dQuery = $className::getQuery();
                $dQuery->in('id', $chunk);
                $dQuery->show('id,state,closedOn');
                while ($dRec = $dQuery->fetch()) {
                    $dealStates[$className . '|' . $dRec->id] = $dRec;
                }
            }
        }
    }


    /**
     * Сглобява групите на deals_Helper::getCombinedThreads() за продажби и покупки.
     *
     * @param array $threadIds
     * @param array $firstDocuments - [threadId => (className, that)]
     * @param array $combiners - обединяващите сделки [id => rec(id,threadId,folderId,closedDocuments)]
     * @param string $dealClass
     * @return array [threadId => представителна нишка за разпределението]
     */
    private static function primeCombinedThreads($threadIds, $firstDocuments, $combiners, $dealClass = 'sales_Sales')
    {
        self::primeOtherDealThreads($threadIds, $firstDocuments, $dealClass);

        $dealIds = array();
        foreach ($threadIds as $threadId) {
            $document = $firstDocuments[$threadId] ?? null;
            if (is_object($document) && $document->className == $dealClass) {
                $dealIds[$threadId] = $document->that;
            }
        }
        if (!countR($dealIds)) return array();

        $deals = $fastThreads = array();
        foreach (array_chunk(array_unique($dealIds), 1000) as $chunk) {
            /** @var core_Query $dQuery */
            $dQuery = $dealClass::getQuery();
            $dQuery->in('id', $chunk);
            $dQuery->show('id,state,folderId,threadId,closedDocuments,contoActions');
            $dQuery->selectOnReplica();
            while ($dRec = $dQuery->fetch()) {
                $deals[$dRec->id] = $dRec;
                if (strpos($dRec->contoActions ?? '', 'pay') !== false) {
                    $fastThreads[$dRec->threadId] = true;
                }
            }
        }

        $baseOfThread = array();
        $groups = self::buildDealGroups($dealClass, $dealIds, $deals, $combiners, $baseOfThread);

        // Една група се изгражда веднъж и се споделя между нейните нишки.
        $representatives = $paymentThreads = array();
        foreach ($baseOfThread as $threadId => $baseThread) {
            $representatives[$baseThread] = $representatives[$baseThread] ?? $threadId;
            // Бърза сделка занулява остатъците; небързата запазва общия резултат.
            if (isset($fastThreads[$representatives[$baseThread]]) && !isset($fastThreads[$threadId])) {
                $representatives[$baseThread] = $threadId;
            }
        }
        foreach ($baseOfThread as $threadId => $baseThread) {
            $paymentThreads[$threadId] = $representatives[$baseThread];
        }
        deals_Helper::setCombinedThreads($groups);

        return $paymentThreads;
    }


    /**
     * Групите на deals_Helper::getCombinedThreads() за нишките, чийто първи документ не е $dealClass
     *
     * Иначе getCombinedThreads() ги сглобява поотделно с по четири заявки на нишка.
     *
     * @param array $threadIds
     * @param array $firstDocuments - [threadId => (className, that)]
     * @param string $dealClass
     */
    private static function primeOtherDealThreads($threadIds, $firstDocuments, $dealClass)
    {
        $groups = $dealIds = array();
        foreach ($threadIds as $threadId) {
            $document = $firstDocuments[$threadId] ?? null;
            if (!is_object($document) || $document->className == $dealClass) continue;

            if (cls::load($document->className, true) && is_a($document->className, 'deals_DealBase', true)) {
                $dealIds[$document->className][$threadId] = $document->that;
            } else {
                $groups[$threadId] = array();
            }
        }

        foreach ($dealIds as $className => $ids) {
            $deals = $closedFolders = array();
            foreach (array_chunk(array_unique($ids), 1000) as $chunk) {
                /** @var core_Query $dQuery */
                $dQuery = $className::getQuery();
                $dQuery->in('id', $chunk);
                $dQuery->show('id,state,folderId,threadId,closedDocuments');
                $dQuery->selectOnReplica();
                while ($dRec = $dQuery->fetch()) {
                    $deals[$dRec->id] = $dRec;
                    if ($dRec->state == 'closed') {
                        $closedFolders[$dRec->folderId] = $dRec->folderId;
                    }
                }
            }

            // Обединяващите сделки трябват само в папките на приключените
            $combiners = array();
            foreach (array_chunk($closedFolders, 1000) as $chunk) {
                /** @var core_Query $cQuery */
                $cQuery = $className::getQuery();
                $cQuery->in('folderId', $chunk);
                $cQuery->where("#closedDocuments IS NOT NULL AND #closedDocuments != ''");
                $cQuery->show('id,folderId,threadId,closedDocuments');
                $cQuery->selectOnReplica();
                while ($cRec = $cQuery->fetch()) {
                    $combiners[$cRec->id] = $cRec;
                }
            }

            $baseOfThread = array();
            $groups = self::buildDealGroups($className, $ids, $deals, $combiners, $baseOfThread) + $groups;
        }

        deals_Helper::setCombinedThreads($groups);
    }


    /**
     * Групите на getCombinedThreads() за сделките от един клас
     *
     * @param string $className
     * @param array $dealIds - [threadId => id на сделката]
     * @param array $deals - [id => rec(id,state,folderId,threadId,closedDocuments)]
     * @param array $combiners - обединяващите сделки [id => rec(id,folderId,threadId,closedDocuments)]
     * @param array $baseOfThread - [threadId => нишката на основата на групата]
     * @return array [threadId => [threadId => threadId]]
     */
    private static function buildDealGroups($className, $dealIds, $deals, $combiners, &$baseOfThread)
    {
        // Като LOCATE в getCombinedThreads(): обединяващата в същата папка, с най-малко id
        ksort($combiners);
        $combinerOf = array();
        $dealThreads = array();
        foreach ($combiners as $cRec) {
            $dealThreads[$cRec->id] = $cRec->threadId;
            foreach (keylist::toArray($cRec->closedDocuments ?? '') as $closedId) {
                if (!isset($combinerOf[$closedId][$cRec->folderId])) {
                    $combinerOf[$closedId][$cRec->folderId] = $cRec;
                }
            }
        }

        // Основата на групата е обединяващата сделка, ако приключената е обединена с нея
        $closedOfBase = array();
        foreach ($dealIds as $threadId => $dealId) {
            $dRec = $deals[$dealId] ?? null;
            if (!$dRec) continue;

            $base = $dRec;
            $baseThread = $threadId;
            $cRec = ($dRec->state == 'closed') ? ($combinerOf[$dealId][$dRec->folderId] ?? null) : null;
            if (is_object($cRec) && !empty($cRec->threadId)) {
                $base = $cRec;
                $baseThread = $cRec->threadId;
            }
            $closedOfBase[$baseThread] = keylist::toArray($base->closedDocuments ?? '');
            $baseOfThread[$threadId] = $baseThread;
            $dealThreads[$dRec->id] = $dRec->threadId;
        }

        // Нишките на обединените сделки, които още не са заредени
        $missing = array();
        foreach ($closedOfBase as $closedIds) {
            foreach ($closedIds as $closedId) {
                if (!array_key_exists($closedId, $dealThreads)) {
                    $missing[$closedId] = $closedId;
                }
            }
        }
        foreach (array_chunk($missing, 1000) as $chunk) {
            /** @var core_Query $mQuery */
            $mQuery = $className::getQuery();
            $mQuery->in('id', $chunk);
            $mQuery->show('id,threadId');
            $mQuery->selectOnReplica();
            while ($mRec = $mQuery->fetch()) {
                $dealThreads[$mRec->id] = $mRec->threadId;
            }
        }

        $groupsByBase = array();
        foreach ($closedOfBase as $baseThread => $closedIds) {
            $groupsByBase[$baseThread] = array($baseThread => $baseThread);
            foreach ($closedIds as $closedId) {
                if (!empty($dealThreads[$closedId])) {
                    $groupsByBase[$baseThread][$dealThreads[$closedId]] = $dealThreads[$closedId];
                }
            }
        }

        $groups = array();
        foreach ($baseOfThread as $threadId => $baseThread) {
            $groups[$threadId] = $groupsByBase[$baseThread];
        }

        return $groups;
    }


    /**
     * Изключва още в заявката фактурите от приключени, необединени сделки
     *
     * Същото условие се проверява и после по фактура; тук само спестява четенето им.
     *
     * @param core_Query $query
     * @param string $dealClass
     * @param string $checkDate
     * @param array $united - [id на обединена сделка => ...]
     */
    private static function excludeClosedDealThreads($query, $dealClass, $checkDate, $united)
    {
        $Deal = cls::get($dealClass);
        $col = function ($field) {
            return '`' . str::phpToMysqlName($field) . '`';
        };

        // Като PHP сравнението на низове: приключената в деня на дата без час не се изключва
        $op = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $checkDate) ? '<' : '<=';

        // NULL в NOT IN би изключил всичко, затова нишката е задължителна
        $cond = "SELECT {$col('threadId')} FROM `{$Deal->dbTableName}` WHERE {$col('threadId')} IS NOT NULL"
            . " AND {$col('state')} = 'closed' AND {$col('closedOn')} {$op} '" . $Deal->db->escape($checkDate) . "'";
        $unitedIds = array_filter(array_map('intval', array_keys($united)));
        if (countR($unitedIds)) {
            $cond .= " AND {$col('id')} NOT IN (" . implode(',', $unitedIds) . ')';
        }
        $query->where("#threadId NOT IN ({$cond})");
    }


    /**
     * Допълва фактурите от обединени нишки и известията с общи заявки.
     *
     * @param class-string<core_Mvc> $className
     */
    private static function loadInvoiceRecords($className, $payments, &$records, $fields)
    {
        $missing = array_diff_key($payments, $records);
        foreach (array_chunk(array_keys($missing), 500) as $containerIds) {
            /** @var core_Query $query */
            $query = $className::getQuery();
            $query->in('containerId', $containerIds);
            $query->show($fields);
            foreach ($containerIds as $id) {
                $records[$id] = null;
            }
            while ($invoice = $query->fetch()) {
                if (isset($invoice->containerId)) {
                    $records[$invoice->containerId] = $invoice;
                }
            }
        }
    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     *
     * Ключови работни масиви (продажби):
     *   $sRecsAll — всички фактури при режим „всички“
     *   $sRecs     — филтрирани редове при режим „неплатени“
     *   $totalInvoiceContragent(All) — агрегати по контрагент
     *
     * Алгоритъм: (1) query на фактури → (2) обхождане по нишки с getInvoicePayments
     * → (3) агрегиране → (4) сортиране и тотали.
     */
    protected function prepareRecs($rec, &$data = null)
    {
        // Сглобените групи нишки не трябва да остават в кеша и при изключение
        try {
            if (($rec->typeOfInvoice ?? 'out') == 'all') {
                return $this->prepareCombinedRecs($rec, $data);
            }

            if (($rec->accountingCorrections ?? 'no') == 'yes') {
                $startedOn = microtime(true);
                // Запазва избрания алгоритъм и обединените сделки; остатъкът се филтрира след МО.
                $sourceRec = clone $rec;
                $recs = $this->prepareInvoiceRecs($sourceRec, $data, true);

                $recs = $this->prepareAccountingCorrections($rec, $recs, $sourceRec->accountingThreadGroups ?? array());
                self::addReportStat($data, 'rows', 0, countR($recs));
                self::addReportStat($data, 'accounting', microtime(true) - $startedOn, $rec->totalAccountingCorrectionAll ?? 0);
                $this->logReportStats($data, $startedOn);

                return $recs;
            }

            return $this->prepareInvoiceRecs($rec, $data);
        } finally {
            deals_Helper::setCombinedThreads(null);
        }
    }


    /**
     * Запазва отделните изчисления и групи за продажби и покупки.
     */
    private function prepareCombinedRecs($rec, &$data)
    {
        $rec->checkDate = $rec->checkDate ?? dt::today(false);
        $data = $data ?? new stdClass();
        $data->groupByField = 'contragent';
        $data->invoiceTotals = array();
        $groups = $summaries = array();
        $totalFields = array('totalInvoiceValueAll', 'totalInvoicePayoutAll', 'totalInvoiceNotPaydAll', 'totalInvoiceOverPaidAll', 'totalInvoiceOverDueAll');
        $summaryFields = array('totalInvoiceValue', 'totalInvoicePayout', 'totalInvoiceNotPayd', 'totalInvoiceOverPaid', 'totalInvoiceOverDue');
        if (($rec->accountingCorrections ?? 'no') == 'yes') {
            $totalFields[] = 'totalAccountingCorrectionAll';
            $summaryFields[] = 'totalAccountingCorrection';
            $rec->accountingIncompleteGroups = $rec->accountingUnallocatedGroups = 0;
        }
        foreach ($totalFields as $field) {
            $rec->{$field} = 0;
        }

        foreach (array('out', 'in') as $type) {
            $sign = $type == 'out' ? 1 : -1;
            $typeRec = clone $rec;
            $typeRec->typeOfInvoice = $type;
            // Кешът за обединени сделки се освобождава и между двата вида.
            $typeRecs = $this->prepareRecs($typeRec, $data);
            if (($rec->accountingCorrections ?? 'no') == 'yes') {
                $rec->accountingIncompleteGroups += $typeRec->accountingIncompleteGroups ?? 0;
                $rec->accountingUnallocatedGroups += $typeRec->accountingUnallocatedGroups ?? 0;
            }
            $data->invoiceTotals[$type] = $typeRec->totalInvoiceValueAll ?? 0;
            $seen = array();
            foreach ($typeRecs as $key => $dRec) {
                $contragent = $dRec->contragent ?? '';
                $dRec->invoiceGroup = $type . '|' . $contragent;
                $groups[$contragent][$type . '|' . $key] = $dRec;
                if (isset($seen[$contragent])) continue;
                $seen[$contragent] = true;
                if (!isset($summaries[$contragent])) {
                    $summaries[$contragent] = (object)array('contragent' => $contragent, 'invoiceTotals' => array());
                }
                $summaries[$contragent]->invoiceTotals[$type] = $dRec->totalInvoiceValue ?? 0;
                foreach ($summaryFields as $field) {
                    $summaries[$contragent]->{$field} = ($summaries[$contragent]->{$field} ?? 0) + $sign * ($dRec->{$field} ?? 0);
                }
            }
            foreach ($totalFields as $field) {
                $rec->{$field} += $sign * ($typeRec->{$field} ?? 0);
            }
        }

        // Продажбите и покупките на една фирма остават последователни и при странициране.
        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);
        $recs = array();
        foreach ($groups as $group) {
            $recs += $group;
        }
        foreach ($recs as $dRec) {
            $dRec->combinedSummary = $summaries[$dRec->contragent ?? ''];
        }

        return $recs;
    }


    /**
     * Чете само контирани МО по аналитичните пера на избраните сделки.
     */
    private static function loadAccountingCorrections($rec, $recs, $paymentGroups)
    {
        $incoming = ($rec->typeOfInvoice ?? 'out') == 'in';
        $invoiceClass = $incoming ? purchase_Invoices::class : sales_Invoices::class;
        $dealClass = $incoming ? purchase_Purchases::class : sales_Sales::class;
        $account = $incoming ? '401' : '411';
        $groups = $threadGroups = $threads = array();
        foreach ($recs as $dRec) {
            $threadId = $dRec->threadId ?? null;
            if (($dRec->className ?? null) != $invoiceClass || !$threadId || isset($threadGroups[$threadId])) continue;
            $combined = $paymentGroups[$threadId] ?? deals_Helper::getCombinedThreads($threadId);
            ksort($combined);
            $key = implode('|', $combined);
            $groups[$key] = array('invoiceIds' => array(), 'documents' => array(), 'dealId' => null);
            foreach ($combined as $id) {
                $threadGroups[$id] = $key;
                $threads[$id] = $id;
            }
        }
        if (!countR($threads)) return array();

        // Броят се и фактурите извън избрания период/начин на плащане.
        $dealGroups = array();
        foreach (array_chunk($threads, 1000) as $chunk) {
            $iQuery = $invoiceClass::getQuery();
            $iQuery->in('threadId', $chunk);
            $iQuery->where("#state = 'active'");
            $iQuery->where(array("#date <= '[#1#]'", $rec->checkDate ?? dt::today()));
            $iQuery->show('containerId,threadId');
            while ($invoice = $iQuery->fetch()) {
                $key = $threadGroups[$invoice->threadId ?? 0] ?? null;
                if (isset($key, $invoice->containerId)) $groups[$key]['invoiceIds'][$invoice->containerId] = $invoice->containerId;
            }
            $tQuery = doc_Threads::getQuery();
            $tQuery->in('id', $chunk);
            $tQuery->show('id,firstDocClass,firstDocId');
            while ($thread = $tQuery->fetch()) {
                if (($thread->firstDocClass ?? null) != $dealClass::getClassId()) continue;
                $key = $threadGroups[$thread->id ?? 0] ?? null;
                $dealId = $thread->firstDocId ?? null;
                if (!isset($key) || !$dealId) continue;
                $dealGroups[$dealId] = $key;
                $groups[$key]['dealId'] = $dealId;
            }
        }

        $itemGroups = array();
        foreach (array_chunk(array_keys($dealGroups), 1000) as $chunk) {
            $query = acc_Items::getQuery();
            $query->where(array("#classId = '[#1#]'", $dealClass::getClassId()));
            $query->in('objectId', $chunk);
            $query->show('id,objectId');
            while ($item = $query->fetch()) {
                if (isset($dealGroups[$item->objectId ?? 0])) $itemGroups[$item->id ?? 0] = $dealGroups[$item->objectId ?? 0];
            }
        }
        if (!countR($itemGroups)) return $groups;

        $position = acc_Lists::getPosition($account, 'deals_DealsAccRegIntf');
        if (!$position) return $groups;
        $accountRec = acc_Accounts::getRecBySystemId($account);
        $accountId = $accountRec->id ?? null;
        $entries = $articleIds = array();
        foreach (array_chunk(array_keys($itemGroups), 500) as $chunk) {
            $query = acc_JournalDetails::getQuery();
            $items = array(1 => null, 2 => null, 3 => null);
            $items[$position] = $chunk;
            acc_JournalDetails::filterQuery($query, null, null, $account, null, $items[1], $items[2], $items[3], false, array('acc_Articles'));
            $query->where(array("#valior <= '[#1#]'", $rec->checkDate ?? dt::today()));
            $query->show("id,docId,valior,amount,debitAccId,creditAccId,debitItem{$position},creditItem{$position}");
            while ($entry = $query->fetch()) {
                $entries[$entry->id ?? 0] = $entry;
                $articleIds[$entry->docId ?? 0] = $entry->docId ?? 0;
            }
        }
        $articles = array();
        foreach (array_chunk($articleIds, 1000) as $chunk) {
            $query = acc_Articles::getQuery();
            $query->in('id', $chunk);
            $query->where("#state = 'active'");
            $query->show('id');
            while ($article = $query->fetch()) $articles[$article->id ?? 0] = $article;
        }
        foreach ($entries as $entry) {
            $articleId = $entry->docId ?? 0;
            if (!isset($articles[$articleId])) continue;
            $amount = deals_Helper::getSmartBaseCurrency($entry->amount ?? 0, $entry->valior ?? null, $rec->checkDate ?? null);
            foreach (array('debit', 'credit') as $side) {
                if (($entry->{$side . 'AccId'} ?? null) != $accountId) continue;
                $key = $itemGroups[$entry->{$side . 'Item' . $position} ?? 0] ?? null;
                if (!isset($key)) continue;
                if (!isset($groups[$key]['documents'][$articleId])) {
                    $groups[$key]['documents'][$articleId] = (object)array('id' => $articleId, 'date' => $entry->valior ?? null, 'amount' => 0);
                }
                $sign = ($side == 'debit') == $incoming ? 1 : -1;
                $groups[$key]['documents'][$articleId]->amount += $sign * $amount;
            }
        }

        return $groups;
    }


    /**
     * Платеното остава отделно; само еднозначните корекции променят остатъка на фактура.
     */
    private function prepareAccountingCorrections($rec, $recs, $paymentGroups)
    {
        $rec->checkDate = $rec->checkDate ?? dt::today();
        $rec->unpaid = $rec->unpaid ?? 'all';
        $incoming = ($rec->typeOfInvoice ?? 'out') == 'in';
        $invoiceClass = $incoming ? purchase_Invoices::class : sales_Invoices::class;
        $dealClass = $incoming ? purchase_Purchases::class : sales_Sales::class;
        $rowsByContainer = array();
        foreach ($recs as $key => $dRec) {
            $dRec->accountingCorrection = 0;
            $dRec->accountingDocuments = array();
            if (($dRec->className ?? null) == $invoiceClass) $rowsByContainer[$dRec->invoiceContainerId ?? 0] = $key;
        }
        $rec->accountingIncompleteGroups = $rec->accountingUnallocatedGroups = 0;
        foreach (self::loadAccountingCorrections($rec, $recs, $paymentGroups) as $group) {
            $documents = array_filter($group['documents'], function ($doc) { return abs(round($doc->amount ?? 0, 2)) > 0; });
            if (!countR($documents)) continue;
            $invoiceIds = $group['invoiceIds'];
            if (countR($invoiceIds) == 1 && isset($rowsByContainer[reset($invoiceIds)])) {
                $key = $rowsByContainer[reset($invoiceIds)];
                $recs[$key]->accountingDocuments = $documents;
                foreach ($documents as $doc) $recs[$key]->accountingCorrection += $doc->amount ?? 0;
            } elseif (countR($invoiceIds) && !countR(array_diff_key($invoiceIds, $rowsByContainer))) {
                $rec->accountingUnallocatedGroups++;
                foreach ($documents as $doc) {
                    $template = $recs[$rowsByContainer[reset($invoiceIds)]];
                    $recs['correction|' . ($group['dealId'] ?? 0) . '|' . ($doc->id ?? 0)] = (object)array(
                        'className' => $invoiceClass, 'threadId' => $template->threadId ?? null,
                        'invoiceId' => null, 'invoiceNo' => '', 'invoiceContainerId' => null,
                        'invoiceDate' => $doc->date ?? null, 'dueDate' => null,
                        'currencyId' => acc_Periods::getBaseCurrencyCode($rec->checkDate), 'rate' => 1,
                        'invoiceValue' => 0, 'invoicePayout' => 0, 'invoiceVAT' => 0, 'type' => 'invoice',
                        'contragent' => $template->contragent ?? '', 'payDocuments' => array(),
                        'isAccountingCorrection' => true, 'accountingDealClass' => $dealClass,
                        'accountingDealId' => $group['dealId'] ?? null,
                        'accountingCorrection' => $doc->amount ?? 0, 'accountingDocuments' => array($doc),
                    );
                }
            } else {
                // Салдо на цяла сделка не се приспада от произволно избрана част от фактурите ѝ.
                $rec->accountingIncompleteGroups++;
            }
        }
        $totals = array();
        $fields = array('totalInvoiceValue', 'totalInvoicePayout', 'totalInvoiceNotPayd', 'totalInvoiceOverPaid', 'totalInvoiceOverDue', 'totalAccountingCorrection');
        foreach ($fields as $field) $rec->{$field . 'All'} = 0;
        foreach ($recs as $key => $dRec) {
            $rate = ($dRec->rate ?? 1) ?: 1;
            $paid = self::getPaidAmount($dRec);
            $value = $dRec->invoiceValue ?? 0;
            // При Неплатени/Просрочени това е остатъкът от общото разпределение, включително КИ/ДИ.
            $balance = $rec->unpaid != 'all' && empty($dRec->isAccountingCorrection)
                ? ($dRec->invoiceCurrentSumm ?? 0) * $rate
                : (($dRec->fastMarker ?? 1) ? $value - $paid : 0);
            if ($dRec->accountingCorrection != 0) $balance = round($balance - $dRec->accountingCorrection, 2);
            $dRec->invoiceCurrentSumm = $balance / $rate;
            $overdue = !empty($dRec->dueDate) && $dRec->dueDate < dt::today() && round($balance / $rate, 2) > 0;
            if (($rec->unpaid == 'overdue' && !$overdue) || ($rec->unpaid != 'all' && abs($balance / $rate) <= ($rec->sill ?? 0))) {
                unset($recs[$key]);
                continue;
            }
            if (($dRec->className ?? null) == sales_Proformas::class) continue;
            $name = $dRec->contragent ?? '';
            $overdueAmount = !empty($dRec->dueDate) && $dRec->dueDate < $rec->checkDate && $balance > 0 ? $balance : 0;
            $amounts = array($value, $paid, max(0, $balance), min(0, $balance), $overdueAmount, $dRec->accountingCorrection ?? 0);
            foreach ($fields as $i => $field) {
                $totals[$name][$field] = ($totals[$name][$field] ?? 0) + $amounts[$i];
                $rec->{$field . 'All'} += $amounts[$i];
            }
        }
        foreach ($recs as $dRec) {
            foreach ($fields as $field) $dRec->{$field} = $totals[$dRec->contragent ?? ''][$field] ?? 0;
        }
        uasort($recs, function ($a, $b) {
            $name = strnatcasecmp($a->contragent ?? '', $b->contragent ?? '');

            return $name ?: strcmp($a->invoiceDate ?? '', $b->invoiceDate ?? '');
        });

        return $recs;
    }


    /**
     * Подготвя записите на справката (вж. prepareRecs)
     *
     * @param stdClass $rec
     * @param stdClass $data
     * @param bool $deferBalanceFilter - при МО задържа и платените фактури до прилагането на корекциите
     *
     * @return array
     */
    private function prepareInvoiceRecs($rec, &$data = null, $deferBalanceFilter = false)
    {
        // Стари записи на справката може да нямат полетата, добавени по-късно
        $rec->unpaid = $rec->unpaid ?? 'all';
        $rec->typeOfInvoice = $rec->typeOfInvoice ?? 'out';
        $rec->checkDate = $rec->checkDate ?? dt::today(false);
        $rec->fromDate = $rec->fromDate ?? null;
        $rec->contragent = $rec->contragent ?? null;
        $rec->crmGroup = $rec->crmGroup ?? null;
        $rec->paymentType = $rec->paymentType ?? null;
        $rec->sill = $rec->sill ?? 0;
        $rec->{'seeProformаs'} = $rec->{'seeProformаs'} ?? null;
        $onlyOverdue = $rec->unpaid == 'overdue';
        $today = dt::today();
        $matchesPaymentType = function ($invoice) use ($rec) {
            return empty($rec->paymentType) || $rec->paymentType == (($invoice->paymentType ?? null) ?: ($invoice->autoPaymentType ?? null));
        };

        // Диагностика: пропуснатите фактури по причини и общото време за плащанията
        $startedOn = microtime(true);
        $skipped = $closedDealThreads = array();

        // При „Всички“ двата вида се логват отделно, без показатели от предишния
        if (is_object($data)) {
            $data->reportStatRows = array();
        }
        $paymentsSeconds = 0;

        $sRecs = $sRecsAll = $pRecs = $pRecsAll = array();
        $firstDocumentArr = array();
        $totalInvoiceContragent = $totalInvoiceContragentAll = array();

        // Дата „към“, към която се смятат плащанията и остатъците.
        if ($rec->unpaid != 'all' && !$rec->checkDate) {
            $checkDate = dt::now();
        } else {
            $checkDate = $rec->checkDate;
        }

        $recs = array();

        core_App::setTimeLimit(300);
        $invoicePaymentsByThread = $invoicePaymentsByGroup = $firstDocuments = $dealStates = $invoiceRecords = array();
        $getPayments = function ($threadId) use (&$invoicePaymentsByThread, &$invoicePaymentsByGroup, $checkDate, &$paymentsSeconds, $rec) {
            if (!array_key_exists($threadId, $invoicePaymentsByThread)) {
                $timer = microtime(true);

                // Нишките на една обединена сделка дават един и същ резултат - смята се веднъж
                $groupThreads = deals_Helper::getCombinedThreads($threadId);
                if (($rec->accountingCorrections ?? 'no') == 'yes') $rec->accountingThreadGroups[$threadId] = $groupThreads;
                ksort($groupThreads);
                $groupKey = implode('|', $groupThreads);
                if ($groupKey !== '' && array_key_exists($groupKey, $invoicePaymentsByGroup)) {
                    $invoicePaymentsByThread[$threadId] = $invoicePaymentsByGroup[$groupKey];
                } else {
                    $invoicePaymentsByThread[$threadId] = deals_Helper::getInvoicePayments($threadId, $checkDate, false, false);
                    if ($groupKey !== '') {
                        $invoicePaymentsByGroup[$groupKey] = $invoicePaymentsByThread[$threadId];
                    }
                }
                $paymentsSeconds += microtime(true) - $timer;
            }

            return $invoicePaymentsByThread[$threadId];
        };
        // Плащанията на много нишки с общи заявки; getPayments() после ги взима наготово
        $preloadPayments = function ($threadIds, $paymentThreads = array()) use (&$invoicePaymentsByThread, $checkDate, &$paymentsSeconds, $rec) {
            $threadIds = array_diff_key($threadIds, $invoicePaymentsByThread);
            if (!countR($threadIds)) return;

            $timer = microtime(true);
            $representatives = array();
            foreach ($threadIds as $threadId) {
                $representative = $paymentThreads[$threadId] ?? $threadId;
                $representatives[$representative] = $representative;
            }
            $payments = deals_Helper::getInvoicePaymentsBulk($representatives, $checkDate, false, false);
            foreach ($threadIds as $threadId) {
                $invoicePaymentsByThread[$threadId] = $payments[$paymentThreads[$threadId] ?? $threadId] ?? array();
                if (($rec->accountingCorrections ?? 'no') == 'yes') $rec->accountingThreadGroups[$threadId] = deals_Helper::getCombinedThreads($threadId);
            }
            $paymentsSeconds += microtime(true) - $timer;
        };
        // Фактурите от плащанията на всички нишки с общи заявки, вместо по една заявка на нишка
        $preloadInvoiceRecords = function ($className, $threadIds) use ($getPayments, &$invoiceRecords, &$invoiceFields) {
            $payments = array();
            foreach ($threadIds as $threadId) {
                $threadPayments = $getPayments($threadId);
                if (is_array($threadPayments)) {
                    $payments += $threadPayments;
                }
            }
            self::loadInvoiceRecords($className, $payments, $invoiceRecords, $invoiceFields);
        };
        $getFirstDocument = function ($threadId) use (&$firstDocuments) {
            if (!array_key_exists($threadId, $firstDocuments)) {
                $firstDocuments[$threadId] = doc_Threads::getFirstDocument($threadId);
            }

            return $firstDocuments[$threadId];
        };

        $getDealState = function ($document) use (&$dealStates) {
            $className = $document->className;
            $id = $document->that;
            $key = $className . '|' . $id;
            if (!array_key_exists($key, $dealStates)) {
                $dealStates[$key] = $className::fetch($id, 'state,closedOn');
            }

            return $dealStates[$key];
        };

        // Един и същ филтър за фактури, проформи и покупки.
        $folderIds = $rec->contragent ? keylist::toArray($rec->contragent) : null;
        if ($rec->crmGroup) {
            $groupFolders = self::getFoldersInGroups($rec);
            $folderIds = isset($folderIds) ? array_intersect($folderIds, $groupFolders) : $groupFolders;
        }
        $params = "{$rec->typeOfInvoice}, {$rec->unpaid}, " . ($rec->fromDate ?? '') . " - {$checkDate}, sill={$rec->sill}";
        if (($rec->accountingCorrections ?? 'no') == 'yes') $params .= ', accountingCorrections=yes';
        if (!empty($rec->paymentType)) {
            $params .= ", paymentType={$rec->paymentType}";
        }
        if ($rec->{'seeProformаs'} == 'yes') {
            $params .= ', proformas';
        }
        self::setReportStat($data, 'params', tr(static::$statCaptions['params']) . ": {$params}");
        if (isset($folderIds)) {
            self::addReportStat($data, 'folders', 0, countR($folderIds), $folderIds);
        }

        if (isset($folderIds) && !countR($folderIds)) {
            $rec->totalInvoiceValueAll = $rec->totalInvoicePayoutAll = $rec->totalInvoiceNotPaydAll = 0;
            $rec->totalInvoiceOverPaidAll = $rec->totalInvoiceOverDueAll = 0;
            if (($rec->accountingCorrections ?? 'no') != 'yes') $this->logReportStats($data, $startedOn);

            return $recs;
        }
        $invoiceFields = 'id,threadId,containerId,number,date,dueDate,currencyId,rate,dealValue,discountAmount,vatAmount,contragentName,type,paymentType,autoPaymentType';

        // ===================================================================
        // ИЗХОДЯЩИ ФАКТУРИ (продажби)
        // ===================================================================
        if ($rec->typeOfInvoice == 'out') {

            // $sRecs / $sRecsAll — редове за таблицата; $totalInvoiceContragent* — суми по контрагент
            $sRecs = array();
            $sRecsAll = array();
            $totalInvoiceContragent = $totalInvoiceContragentAll = array();

            $docsArr = array(sales_Invoices::class);

            //Когато се търсят неплатените фактури, и има избор за проформи, се гледат и проформите
            //към които има изрично насочени плащания
            if ($rec->{'seeProformаs'} == 'yes' && $rec->unpaid == 'all') {

                array_push($docsArr, sales_Proformas::class);

                $timer = microtime(true);
                $proformsWithPayDocArr = self::getProformsWithPaymant($rec, $folderIds);
                self::addReportStat($data, 'proformas', microtime(true) - $timer, countR($proformsWithPayDocArr));

            }

            // Само принадлежността към бърза/обединена сделка участва в справката.
            $timer = microtime(true);
            $salesUN = $fastSales = $salesCombiners = array();
            /** @var core_Query $salesQuery */
            $salesQuery = sales_Sales::getQuery();
            $salesQuery->where("#closedDocuments != '' OR #contoActions LIKE '%pay%'");
            $salesQuery->show('id,threadId,folderId,closedDocuments,contoActions');
            $salesQuery->selectOnReplica();
            $dealsCount = 0;
            while ($sale = $salesQuery->fetch()) {
                $dealsCount++;
                foreach (keylist::toArray($sale->closedDocuments ?? '') as $id) {
                    $salesUN[$id] = true;
                    $salesCombiners[$sale->id] = $sale;
                }
                if (strpos($sale->contoActions ?? '', 'pay') !== false) {
                    $fastSales[$sale->id ?? 0] = true;
                }
            }
            self::addReportStat($data, 'deals', microtime(true) - $timer, $dealsCount);
            self::addReportStat($data, 'unitedDeals', 0, countR($salesUN));
            self::addReportStat($data, 'fastDeals', 0, countR($fastSales));

            // Нишките без фактура с търсения начин на плащане отпадат преди скъпото разпределение на плащанията
            $paymentTypeThreads = null;
            if ($rec->unpaid != 'all' && !empty($rec->paymentType)) {
                $timer = microtime(true);
                $paymentTypeThreads = self::getThreadsWithPaymentType(sales_Invoices::class, $rec->paymentType, $checkDate, $folderIds);
                self::addReportStat($data, 'paymentTypeThreads', microtime(true) - $timer, countR($paymentTypeThreads));
            }

            foreach ($docsArr as $InvDoc) {

                // --- Query: активни фактури/проформи в периода, с филтър по контрагент ---
                /** @var core_Query $invQuery */
                $invQuery = $InvDoc::getQuery();

                $invQuery->where("#number IS NOT NULL");

                $invQuery->in('state', 'rejected, draft', true);

                //При избрани НЕПЛАТЕНИ махаме дебитните и кредитните известия
                if ($rec->unpaid != 'all' && $InvDoc == 'sales_Invoices') {
                    $invQuery->where("#type = 'invoice'");
                }

                // Ако е посочена начална дата на период
                if ($rec->fromDate) {
                    $invQuery->where(array(
                        "#date >= '[#1#]'",
                        $rec->fromDate
                    ));
                }

                //Крайна дата / 'към дата'
                $invQuery->where(array(
                    "#date <= '[#1#]'",
                    $checkDate
                ));

                if (isset($folderIds)) {
                    $invQuery->in('folderId', $folderIds);
                }
                if ($InvDoc == 'sales_Proformas') {
                    if (empty($proformsWithPayDocArr)) continue;
                    $invQuery->in('id', array_keys($proformsWithPayDocArr));
                }
                $fields = arr::make($invoiceFields, true);
                if ($InvDoc == 'sales_Proformas') {
                    unset($fields['type']);
                }
                $invQuery->show($fields);
                if ($rec->unpaid != 'all' && $InvDoc == sales_Invoices::class) {
                    self::excludeClosedDealThreads($invQuery, sales_Sales::class, $checkDate, $salesUN);
                }

                //Изваждаме нишките за проверка  са избрани НЕПЛАТЕНИ
                //Ако са избрани ВСИЧКИ записваме масив $allInvoices със всички фактури
                $threadsId = array();

                $timer = microtime(true);
                $fetched = 0;
                self::selectReportQuery($invQuery, 10);
                $invoices = $invQuery->fetchAll();
                self::prefetchFirstDocuments($invoices, $firstDocuments, $dealStates, $rec->unpaid != 'all');
                self::addReportStat($data, "{$InvDoc}Fetch", microtime(true) - $timer, countR($invoices));
                if ($rec->unpaid == 'all' && $InvDoc == sales_Invoices::class) {
                    $invoiceThreads = arr::extractValuesFromArray($invoices, 'threadId');
                    $paymentThreads = self::primeCombinedThreads($invoiceThreads, $firstDocuments, $salesCombiners);
                    $preloadPayments($invoiceThreads, $paymentThreads);
                }

                foreach ($invoices as $salesInvoice) {
                    $fetched++;

                    $firstDocument = $getFirstDocument($salesInvoice->threadId);
                    if (!$firstDocument) {
                        $skipped['noFirstDocument'][] = $salesInvoice->number;
                        continue;
                    }

                    $firstDocumentArr[$salesInvoice->threadId] = $firstDocument->that;


                    $fastMarker = isset($fastSales[$firstDocumentArr[$salesInvoice->threadId]]) ? 0 : 1;

                    // При „неплатени“: пропуска фактури от приключени (не обединени) сделки.
                    if ($rec->unpaid != 'all') {
                        $unitedCheck = false;

                        if (is_array($salesUN)) {
                            $unitedCheck = isset($salesUN[$firstDocument->that]);
                        }


                        //Ако продажбата е приключена с друг договор фактурите от тази сделка остават в справката, ако е приключена
                        //по друг начин сделката се прескача.
                        $dealRec = $getDealState($firstDocument);
                        if (($dealRec->state ?? null) == 'closed' &&
                            ($dealRec->closedOn ?? null) <= $checkDate &&
                            !$unitedCheck) {
                            $skipped['closedDeal'][] = $salesInvoice->number;
                            $closedDealThreads[$salesInvoice->threadId] = $salesInvoice->threadId;
                            continue;
                        }
                    }

                    if ($rec->unpaid != 'all') {
                        // Обединените сделки остават - плащанията им се разпределят върху няколко нишки
                        if (isset($paymentTypeThreads) && !isset($paymentTypeThreads[$salesInvoice->threadId]) &&
                            !isset($salesUN[$firstDocument->that]) && !isset($salesCombiners[$firstDocument->that])) {
                            $skipped['otherPaymentTypeThread'][] = $salesInvoice->number;
                            continue;
                        }

                        $invoiceRecords[$salesInvoice->containerId] = $salesInvoice;
                    }

                    //Масив от нишки в които има фактури
                    $threadsId[$salesInvoice->threadId] = $salesInvoice->threadId;


                    // Режим „всички“: записва всяка фактура с платена сума към checkDate.
                    if ($rec->unpaid == 'all') {
                        if (!$matchesPaymentType($salesInvoice)) {
                            $skipped['otherPaymentType'][] = $salesInvoice->number;
                            continue;
                        }

                        // масив от фактури в тази нишка //
                        $invoicePayments = ($InvDoc == 'sales_Proformas') ? array() : $getPayments($salesInvoice->threadId);

                        $paydocs = $invoicePayments[$salesInvoice->containerId] ?? (object)array(
                            'payout' => 0,
                            'date' => null,
                            'used' => array(),
                        );

                        // За проформите плащанията се взимат от изрично насочените платежни документи.
                        if ($InvDoc == 'sales_Proformas') {
                            $paydocs = new stdClass();
                            // Подготвя се структура, съвместима с данните от getInvoicePayments().
                            $paydocs->payout = 0;
                            $paydocs->date = null;
                            $paydocs->used = array();

                            if (isset($proformsWithPayDocArr[$salesInvoice->id])) {
                                // Сумират се всички платежни документи, насочени към текущата проформа.
                                foreach ($proformsWithPayDocArr[$salesInvoice->id]->documents as $pDocId) {
                                    $payDocClassId = $proformsWithPayDocArr[$salesInvoice->id]->docClassId;
                                    $payDocClass = cls::get($payDocClassId);

                                    $pDocRec = $payDocClass->fetch($pDocId);

                                    if (!$pDocRec || $pDocRec->state != 'active') {
                                        continue;
                                    }

                                    $paydocs->payout += $pDocRec->amount;
                                    $paydocs->date = $pDocRec->valior;

                                    $paydocs->used[] = (object)array(
                                        'containerId' => $pDocRec->containerId,
                                    );
                                }
                            }
                        }

                        // Разделя ключовете на фактури и проформи, за да не се припокриват еднакви id-та.
                        $subKey = ($InvDoc == 'sales_Proformas') ? 'P' : 'S';
                        $key = $salesInvoice->id . $subKey;

                        // Превалутиране за ЕЗ
                        $salesInvoice->dealValue = deals_Helper::getSmartBaseCurrency($salesInvoice->dealValue, $salesInvoice->date, $rec->checkDate);
                        $salesInvoice->discountAmount = deals_Helper::getSmartBaseCurrency($salesInvoice->discountAmount, $salesInvoice->date, $rec->checkDate);
                        $salesInvoice->vatAmount = deals_Helper::getSmartBaseCurrency($salesInvoice->vatAmount, $salesInvoice->date, $rec->checkDate);


                        $invoiceValue = ($salesInvoice->dealValue - $salesInvoice->discountAmount) + $salesInvoice->vatAmount;

                        // масива с фактурите за показване
                        if (!array_key_exists($key, $sRecsAll)) {
                            $sRecsAll[$key] = (object)array(

                                'threadId' => $salesInvoice->threadId,
                                'className' => $InvDoc,
                                'invoiceId' => $salesInvoice->id,
                                'invoiceNo' => $salesInvoice->number,
                                'invoiceDate' => $salesInvoice->date,
                                'dueDate' => $salesInvoice->dueDate,
                                'invoiceContainerId' => $salesInvoice->containerId,
                                'currencyId' => $salesInvoice->currencyId,
                                'rate' => $salesInvoice->rate,
                                'invoiceValue' => $invoiceValue,
                                'invoiceVAT' => $salesInvoice->vatAmount,
                                'contragent' => $salesInvoice->contragentName,
                                'type' => $salesInvoice->type ?? null,
                                'payDocuments' => $paydocs->used,
                                'fastMarker' => $fastMarker,
                                'invoicePayout' => deals_Helper::getSmartBaseCurrency($paydocs->payout, $paydocs->date, $rec->checkDate),
                                'invoiceCurrentSumm' => 0,
                                // 'dcPay' => $dcPay
                            );
                        }

                        // Масив с данни за сумите от фактурите  обединени по контрагенти


                        if ($InvDoc == 'sales_Invoices') {  //Да не влизат сумите на проформите в общата стойност по контрагент

                            if (!array_key_exists($salesInvoice->contragentName, $totalInvoiceContragentAll)) {

                                $totalInvoiceContragentAll[$salesInvoice->contragentName] = (object)array(
                                    'totalInvoiceValue' => $invoiceValue,                                        //общо стойност на фактурите за контрагента
                                    'totalInvoiceVAT' => $salesInvoice->vatAmount,                               //общо стойност на ДДС по фактурите за контрагента
                                    'totalInvoicePayout' => 0,
                                    'totalInvoiceNotPaid' => 0,
                                    'totalInvoiceOverPaid' => 0,
                                    'totalInvoiceOverDue' => 0,

                                );
                            } else {
                                $obj = &$totalInvoiceContragentAll[$salesInvoice->contragentName];

                                $obj->totalInvoiceValue += $invoiceValue;
                                $obj->totalInvoiceVAT += $salesInvoice->vatAmount;
                            }
                        }
                    }
                }
                self::addReportStat($data, $InvDoc, microtime(true) - $timer, $fetched);
            }

            // --- Втори проход по нишки: остатъци (неплатено / надплатено / просрочено) ---
            if ($rec->unpaid != 'all' && !empty($threadsId)) {
                self::addReportStat($data, 'threads', 0, countR($threadsId));
                $paymentThreads = self::primeCombinedThreads($threadsId, $firstDocuments, $salesCombiners);
                $preloadPayments($threadsId, $paymentThreads);
                $preloadInvoiceRecords(sales_Invoices::class, array_unique($paymentThreads + $threadsId));

                $checkedSInvoices = array();

                foreach (array_unique($paymentThreads + $threadsId) as $thread) {

                    $salesInvoiceNotPaid = 0;


                    // масив от фактури в тази нишка //
                    $invoicePayments = $getPayments($thread);

                    if (is_array($invoicePayments)) {
                        self::loadInvoiceRecords('sales_Invoices', $invoicePayments, $invoiceRecords, $invoiceFields);

                        // фактура от нишката и масив от платежни документи по тази фактура//
                        foreach ($invoicePayments as $inv => $paydocs) {
                            $salesInvoiceNotPaid = 0;
                            $salesInvoiceOverPaid = 0;
                            $salesInvoiceOverDue = 0;

                            //Проверка дали отчетена вече фактура не се повтаря
                            if (isset($checkedSInvoices[$inv])) {
                                $skipped['repeated'][] = $inv;
                                continue;
                            }

                            // Не променяме кеширания резултат при превалутиране.
                            $paydocs = clone $paydocs;
                            $paydocs->amount = deals_Helper::getSmartBaseCurrency($paydocs->amount, $paydocs->date, $rec->checkDate);
                            $paydocs->payout = deals_Helper::getSmartBaseCurrency($paydocs->payout, $paydocs->date, $rec->checkDate);


                            // Разлика стойност - платено; при бърза продажба се счита за нула.
                            $invDiff = $paydocs->amount - $paydocs->payout;

                            // TODO: премахни след корекция на deals_Helper::getInvoicePayments за бързи продажби
                            $invDiff = isset($fastSales[$firstDocumentArr[$thread] ?? 0]) ? 0 : $invDiff;

                            $fastMarker = isset($fastSales[$firstDocumentArr[$thread] ?? 0]) ? 0 : 1;

                            $iRec = $invoiceRecords[$inv] ?? null;
                            if (!$iRec) {
                                $skipped['missingInvoice'][] = $inv;
                                continue;
                            }

                            if (!$deferBalanceFilter && $onlyOverdue && (empty($iRec->dueDate) || $iRec->dueDate >= $today || round($invDiff, 2) <= 0)) {
                                $skipped['notOverdue'][] = $iRec->number ?? $inv;
                                continue;
                            }
                            if ($onlyOverdue && !empty($rec->fromDate) && ($iRec->date ?? '') < $rec->fromDate) continue;

                            // Ако са избрани само неплатените фактури пропускаме тези с отклонение под зададения минимум
                            if (($rec->unpaid ?? null) != 'all') {
                                if (!$deferBalanceFilter && ($invDiff >= (-1) * $rec->sill) &&
                                    ($invDiff <= $rec->sill)) {
                                    $skipped['belowSill'][] = $iRec->number;
                                    continue;
                                }

                                //Ако е избран филтър по начин на плащане
                                if (!$matchesPaymentType($iRec)) {
                                    $skipped['otherPaymentType'][] = $iRec->number;
                                    continue;
                                }

                            }

                            //Ако датата на фактурата е по голяма от избраната "към дата" не влиза в масива
                            if ($checkDate < $iRec->date) {
                                $skipped['afterCheckDate'][] = $iRec->number;
                                continue;
                            }

                            if (($invDiff) > 0) {
                                $salesInvoiceNotPaid = $invDiff;
                            }

                            if ($invDiff < 0) {
                                $salesInvoiceOverPaid = $invDiff;
                            }

                            if ($iRec->dueDate && $invDiff > 0 &&
                                $iRec->dueDate < $checkDate) {
                                $salesInvoiceOverDue = $invDiff;
                            }

                            // Масив с данни за сумите от фактурите  обединени по контрагенти

                            if (!array_key_exists($iRec->id, $sRecs)) {
                                if (!array_key_exists($iRec->contragentName, $totalInvoiceContragent)) {
                                    $totalInvoiceContragent[$iRec->contragentName] = (object)array(
                                        'totalInvoiceValue' => $paydocs->amount * $iRec->rate,                            //общо стойност на фактурите за контрагента
                                        'totalInvoicePayout' => $paydocs->payout * $iRec->rate,                           //плащания по фактурите за контрагента
                                        'totalInvoiceNotPaid' => $salesInvoiceNotPaid * $iRec->rate,                      //стойност на НЕДОплатените суми по фактурите за контрагента
                                        'totalInvoiceOverPaid' => $salesInvoiceOverPaid * $iRec->rate,                    //стойност на НАДплатените суми по фактурите за контрагента
                                        'totalInvoiceOverDue' => $salesInvoiceOverDue * $iRec->rate,                      //стойност за плащане по просрочените фактури за контрагента
                                    );
                                } else {
                                    $obj = &$totalInvoiceContragent[$iRec->contragentName];

                                    $obj->totalInvoiceValue += $paydocs->amount * $iRec->rate;
                                    $obj->totalInvoicePayout += $paydocs->payout * $iRec->rate;
                                    $obj->totalInvoiceNotPaid += $salesInvoiceNotPaid * $iRec->rate;
                                    $obj->totalInvoiceOverPaid += $salesInvoiceOverPaid * $iRec->rate;
                                    $obj->totalInvoiceOverDue += $salesInvoiceOverDue * $iRec->rate;
                                }
                            }

                            //Ако са избрани само НЕплатени и има посочена начална дата,
                            // то тези преди тази дата не ги записва в масива
                            if ($rec->unpaid != 'all' && !is_null($rec->fromDate)) {

                                if ($rec->fromDate > $iRec->date) {
                                    $skipped['beforeFromDate'][] = $iRec->number;
                                    continue;
                                }
                            }

                            // масива с фактурите за показване
                            if (!array_key_exists($iRec->id, $sRecs)) {
                                $sRecs[$iRec->id] = (object)array(
                                    'threadId' => $thread,
                                    'className' => 'sales_Invoices',
                                    'invoiceId' => $iRec->id,
                                    'invoiceNo' => $iRec->number,
                                    'invoiceDate' => $iRec->date,
                                    'dueDate' => $iRec->dueDate,
                                    'invoiceContainerId' => $iRec->containerId,
                                    'currencyId' => $iRec->currencyId,
                                    'rate' => $iRec->rate,
                                    'invoiceValue' => $paydocs->amount * $iRec->rate,
                                    'invoiceVAT' => deals_Helper::getSmartBaseCurrency($iRec->vatAmount, $iRec->date, $rec->checkDate),
                                    'invoicePayout' => $paydocs->payout,
                                    'type' => $iRec->type,
                                    'fastMarker' => $fastMarker,
                                    'invoiceCurrentSumm' => $invDiff,
                                    'payDocuments' => $paydocs->used,
                                    'contragent' => $iRec->contragentName
                                );
                            }
                            $checkedSInvoices[$inv] = $inv;
                        }
                    }
                }
            }
        }

        // ===================================================================
        // ВХОДЯЩИ ФАКТУРИ (покупки) — същата логика като при продажбите
        // ===================================================================
        if ($rec->typeOfInvoice == 'in') {
            // $pRecs / $pRecsAll — редове; $totalInvoiceContragent* — агрегати по контрагент
            $pRecs = $pRecsAll = array();
            $totalInvoiceContragent = $totalInvoiceContragentAll = array();

            /** @var core_Query $pQuery */
            $pQuery = purchase_Invoices::getQuery();

            $pQuery->where("#number IS NOT NULL");

            $pQuery->in('state', 'rejected, draft', true);

            //При избрани НЕПЛАТЕНИ махаме дебитните и кредитните известия
            if ($rec->unpaid != 'all') {
                $pQuery->where("#type = 'invoice'");
            }

            // Ако е посочена начална дата на период
            if ($rec->fromDate) {
                $pQuery->where(array(
                    "#date >= '[#1#]'",
                    $rec->fromDate
                ));
            }

            $pQuery->where(array(
                "#date <= '[#1#]'",
                $checkDate
            ));

            if (isset($folderIds)) {
                $pQuery->in('folderId', $folderIds);
            }
            $pQuery->show($invoiceFields);

            //Обединени покупки
            $timer = microtime(true);
            /** @var core_Query $purchasesQuery */
            $purchasesQuery = purchase_Purchases::getQuery();

            $purchasesQuery->where("#closedDocuments != '' OR #contoActions LIKE '%pay%'");
            $purchasesQuery->show('id,threadId,folderId,closedDocuments,contoActions');
            $purchasesQuery->selectOnReplica();

            //Масив с затварящи документи по обединени покупки  и масив с бързи покупки
            $purchasesUN = $purchaseCombiners = array();
            $fastPur = array();

            $dealsCount = 0;
            while ($purchase = $purchasesQuery->fetch()) {
                $dealsCount++;
                foreach ((keylist::toArray($purchase->closedDocuments ?? '')) as $v) {
                    $purchasesUN[$v] = ($v);
                    $purchaseCombiners[$purchase->id] = $purchase;
                }


                //Масив с бързи покупки
                if (strpos($purchase->contoActions ?? '', 'pay') !== false) {
                    $fastPur[$purchase->id ?? 0] = true;
                }
            }
            self::addReportStat($data, 'deals', microtime(true) - $timer, $dealsCount);
            self::addReportStat($data, 'unitedDeals', 0, countR($purchasesUN));
            self::addReportStat($data, 'fastDeals', 0, countR($fastPur));

            $timer = microtime(true);
            $fetched = 0;
            if ($rec->unpaid != 'all') {
                self::excludeClosedDealThreads($pQuery, purchase_Purchases::class, $checkDate, $purchasesUN);
            }
            self::selectReportQuery($pQuery, 5);
            $invoices = $pQuery->fetchAll();
            self::prefetchFirstDocuments($invoices, $firstDocuments, $dealStates, $rec->unpaid != 'all');
            self::addReportStat($data, 'purchase_InvoicesFetch', microtime(true) - $timer, countR($invoices));
            if ($rec->unpaid == 'all') {
                $invoiceThreads = arr::extractValuesFromArray($invoices, 'threadId');
                $paymentThreads = self::primeCombinedThreads($invoiceThreads, $firstDocuments, $purchaseCombiners, purchase_Purchases::class);
                $preloadPayments($invoiceThreads, $paymentThreads);
            }

            $pThreadsId = array();

            // Фактури ПОКУПКИ
            foreach ($invoices as $purchaseInvoices) {
                $fetched++;

                $firstDocument = $getFirstDocument($purchaseInvoices->threadId);
                if (!$firstDocument) {
                    $skipped['noFirstDocument'][] = $purchaseInvoices->number;
                    continue;
                }

                $firstDocumentArr[$purchaseInvoices->threadId] = $firstDocument->that;

                //НАЛОЖИТЕЛНА КОРЕКЦИЯ ЗА БЪРЗИ ПОКУПКИ
                //КОГАТО СЕ ОПРАВИ ФУНКЦИЯТА ЗА РАЗПРЕДЕЛЕНИЕ НА ПЛАЩАНИЯТА
                //ТОВА ДА СЕ МАХНЕ
                $fastMarker = isset($fastPur[$purchaseInvoices->id]) ? 0 : 1;
                if (($rec->accountingCorrections ?? 'no') == 'yes') {
                    $fastMarker = isset($fastPur[$firstDocumentArr[$purchaseInvoices->threadId] ?? 0]) ? 0 : 1;
                }

                if ($rec->unpaid != 'all') {
                    $purUnitedCheck = is_array($purchasesUN) && isset($purchasesUN[$firstDocument->that]);
                    $dealRec = $getDealState($firstDocument);
                    if (($dealRec->state ?? null) == 'closed' && ($dealRec->closedOn ?? null) <= $checkDate && !$purUnitedCheck) {
                        $skipped['closedDeal'][] = $purchaseInvoices->number;
                        $closedDealThreads[$purchaseInvoices->threadId] = $purchaseInvoices->threadId;
                        continue;
                    }
                }

                // Когато е избрано ВСИЧКИ в полето плащане
                if ($rec->unpaid == 'all') {
                    if (!$matchesPaymentType($purchaseInvoices)) {
                        $skipped['otherPaymentType'][] = $purchaseInvoices->number;
                        continue;
                    }

                    // Превалутиране за ЕЗ
                    $purchaseInvoices->dealValue = deals_Helper::getSmartBaseCurrency($purchaseInvoices->dealValue, $purchaseInvoices->date, $rec->checkDate);
                    $purchaseInvoices->discountAmount = deals_Helper::getSmartBaseCurrency($purchaseInvoices->discountAmount, $purchaseInvoices->date, $rec->checkDate);
                    $purchaseInvoices->vatAmount = deals_Helper::getSmartBaseCurrency($purchaseInvoices->vatAmount, $purchaseInvoices->date, $rec->checkDate);

                    $invoiceValue = (($purchaseInvoices->dealValue - $purchaseInvoices->discountAmount) + $purchaseInvoices->vatAmount);

                    // масив от фактури в тази нишка //
                    $invoicePayments = $getPayments($purchaseInvoices->threadId);

                    $paydocs = $invoicePayments[$purchaseInvoices->containerId] ?? (object)array(
                        'payout' => 0,
                        'date' => null,
                        'used' => array(),
                    );

                    // масива с фактурите за показване
                    if (!array_key_exists($purchaseInvoices->id, $pRecsAll)) {
                        $pRecsAll[$purchaseInvoices->id] = (object)array(

                            'threadId' => $purchaseInvoices->threadId,
                            'className' => 'purchase_Invoices',
                            'invoiceId' => $purchaseInvoices->id,
                            'invoiceNo' => $purchaseInvoices->number,
                            'invoiceDate' => $purchaseInvoices->date,
                            'dueDate' => $purchaseInvoices->dueDate,
                            'invoiceContainerId' => $purchaseInvoices->containerId,
                            'currencyId' => $purchaseInvoices->currencyId,
                            'rate' => $purchaseInvoices->rate,
                            'invoiceValue' => $invoiceValue,
                            'invoiceVAT' => $purchaseInvoices->vatAmount,
                            'contragent' => $purchaseInvoices->contragentName,
                            'type' => $purchaseInvoices->type,
                            'payDocuments' => $paydocs->used,
                            'invoicePayout' => deals_Helper::getSmartBaseCurrency($paydocs->payout, $paydocs->date, $rec->checkDate),
                            'invoiceCurrentSumm' => 0,
                            'fastMarker' => $fastMarker,
                            //'dcPay' => $dcPay
                        );
                    }

                    // Масив с данни за сумите от фактурите  обединени по контрагенти
                    if (!array_key_exists($purchaseInvoices->contragentName, $totalInvoiceContragentAll)) {
                        $totalInvoiceContragentAll[$purchaseInvoices->contragentName] = (object)array(
                            'totalInvoiceValue' => $invoiceValue, //общо стойност на фактурите за контрагента
                            'totalInvoiceVAT' => $purchaseInvoices->vatAmount,//общо стойност на ДДС по фактурите за контрагента
                            'totalInvoicePayout' => 0,
                            'totalInvoiceNotPaid' => 0,
                            'totalInvoiceOverPaid' => 0,
                            'totalInvoiceOverDue' => 0,

                        );
                    } else {
                        $obj = &$totalInvoiceContragentAll[$purchaseInvoices->contragentName];

                        $obj->totalInvoiceValue += $invoiceValue;
                        $obj->totalInvoiceVAT += $purchaseInvoices->vatAmount;
                    }
                    continue;
                }


                $invoiceRecords[$purchaseInvoices->containerId] = $purchaseInvoices;
                if (!isset($pThreadsId[$purchaseInvoices->threadId])) {
                    $pThreadsId[$purchaseInvoices->threadId] = $purchaseInvoices->threadId;
                }

            }

            self::addReportStat($data, 'purchase_Invoices', microtime(true) - $timer, $fetched);

            // --- Втори проход по нишки (покупки): остатъци по фактура ---
            if (is_array($pThreadsId)) {
                self::addReportStat($data, 'threads', 0, countR($pThreadsId));
                $paymentThreads = self::primeCombinedThreads($pThreadsId, $firstDocuments, $purchaseCombiners, purchase_Purchases::class);
                $preloadPayments($pThreadsId, $paymentThreads);
                $preloadInvoiceRecords(purchase_Invoices::class, array_unique($paymentThreads + $pThreadsId));
                $checkedPInvoices = array();

                foreach (array_unique($paymentThreads + $pThreadsId) as $pThread) {

                    $purchaseInvoiceNotPaid = 0;


                    // масив от фактури в тази нишка //
                    $pInvoicePayments = $getPayments($pThread);

                    if (is_array($pInvoicePayments)) {
                        self::loadInvoiceRecords('purchase_Invoices', $pInvoicePayments, $invoiceRecords, $invoiceFields);

                        // фактура от нишката и масив от платежни документи по тази фактура//
                        foreach ($pInvoicePayments as $pInv => $paydocs) {

                            $purchaseInvoiceNotPaid = 0;
                            $purchaseInvoiceOverDue = 0;
                            $purchaseInvoiceOverPaid = 0;

                            //Проверка дали отчетена вече фактура не се повтаря
                            if (isset($checkedPInvoices[$pInv])) {
                                $skipped['repeated'][] = $pInv;
                                continue;
                            }

                            // Не променяме кеширания резултат при превалутиране.
                            $paydocs = clone $paydocs;
                            $paydocs->amount = deals_Helper::getSmartBaseCurrency($paydocs->amount, $paydocs->date, $rec->checkDate);
                            $paydocs->payout = deals_Helper::getSmartBaseCurrency($paydocs->payout, $paydocs->date, $rec->checkDate);

                            //Разлика между стойност и платено по фактурата
                            $invDiff = $paydocs->amount - $paydocs->payout;

                            // Ако покупката е бърза, фактурата се счита за платена
                            //Когато се коригира функцията за разпределение на плащанията това да се премахне !!!
                            $invDiff = isset($fastPur[$firstDocumentArr[$pThread] ?? 0]) ? 0 : $invDiff;

                            $fastMarker = isset($fastPur[$firstDocumentArr[$pThread] ?? 0]) ? 0 : 1;

                            // Ако са избрани само неплатените фактури пропускаме тези с отклонение под зададения минимум
                            if ($rec->unpaid != 'all') {
                                if (!$deferBalanceFilter && ($invDiff >= (-1) * $rec->sill) &&
                                    ($invDiff <= $rec->sill)) {
                                    $skipped['belowSill'][] = $invoiceRecords[$pInv]->number ?? $pInv;
                                    continue;
                                }
                            }

                            $iRec = $invoiceRecords[$pInv] ?? null;
                            if (!$iRec) {
                                $skipped['missingInvoice'][] = $pInv;
                                continue;
                            }
                            if (!$matchesPaymentType($iRec)) {
                                $skipped['otherPaymentType'][] = $iRec->number;
                                continue;
                            }

                            if (!$deferBalanceFilter && $onlyOverdue && (empty($iRec->dueDate) || $iRec->dueDate >= $today || round($invDiff, 2) <= 0)) {
                                $skipped['notOverdue'][] = $iRec->number ?? $pInv;
                                continue;
                            }
                            if ($onlyOverdue && !empty($rec->fromDate) && ($iRec->date ?? '') < $rec->fromDate) continue;

                            //Ако датата на фактурата е по голяма от избраната "към дата" не влиза в масива
                            if ($checkDate < $iRec->date) {
                                $skipped['afterCheckDate'][] = $iRec->number;
                                continue;
                            }

                            if (($invDiff) > 0) {
                                $purchaseInvoiceNotPaid = ($invDiff);
                            }

                            if ($invDiff < 0) {
                                $purchaseInvoiceOverPaid = $invDiff;
                            }

                            if ($iRec->dueDate && ($invDiff) > 0 &&
                                $iRec->dueDate < $checkDate) {
                                $purchaseInvoiceOverDue = ($invDiff);
                            }

                            // Масив с данни за сумите от фактурите  обединени по контрагенти
                            if (!array_key_exists($iRec->contragentName, $totalInvoiceContragent)) {
                                $totalInvoiceContragent[$iRec->contragentName] = (object)array(
                                    'totalInvoiceValue' => $paydocs->amount * $iRec->rate,                               //общо стойност на фактурите за контрагента
                                    'totalInvoicePayout' => $paydocs->payout * $iRec->rate,                              //плащания по фактурите за контрагента
                                    'totalInvoiceNotPaid' => $purchaseInvoiceNotPaid * $iRec->rate,                      //стойност за плащане по фактурите за контрагента
                                    'totalInvoiceOverPaid' => $purchaseInvoiceOverPaid * $iRec->rate,                    //стойност на НАДплатените суми по фактурите за контрагента
                                    'totalInvoiceOverDue' => $purchaseInvoiceOverDue * $iRec->rate,                      //стойност за плащане по просрочените фактури за контрагента
                                );
                            } else {
                                $obj = &$totalInvoiceContragent[$iRec->contragentName];

                                $obj->totalInvoiceValue += $paydocs->amount * $iRec->rate;
                                $obj->totalInvoicePayout += $paydocs->payout * $iRec->rate;
                                $obj->totalInvoiceNotPaid += $purchaseInvoiceNotPaid * $iRec->rate;
                                $obj->totalInvoiceOverPaid += $purchaseInvoiceOverPaid * $iRec->rate;
                                $obj->totalInvoiceOverDue += $purchaseInvoiceOverDue * $iRec->rate;
                            }

                            //Ако са избрани само НЕплатени и има посочена начална дата,
                            // то тези преди тази дата не ги записва в масива
                            if ($rec->unpaid != 'all' && !is_null($rec->fromDate)) {

                                if ($rec->fromDate > $iRec->date) {
                                    $skipped['beforeFromDate'][] = $iRec->number;
                                    continue;
                                }
                            }


                            // масива с фактурите за показване
                            if (!array_key_exists($iRec->id, $pRecs)) {
                                $pRecs[$iRec->id] = (object)array(
                                    'threadId' => $pThread,
                                    'className' => 'purchase_Invoices',
                                    'invoiceId' => $iRec->id,
                                    'invoiceNo' => $iRec->number,
                                    'invoiceDate' => $iRec->date,
                                    'dueDate' => $iRec->dueDate,
                                    'invoiceContainerId' => $iRec->containerId,
                                    'currencyId' => $iRec->currencyId,
                                    'rate' => $iRec->rate,
                                    'invoiceValue' => $paydocs->amount * $iRec->rate,
                                    'invoiceVAT' => deals_Helper::getSmartBaseCurrency($iRec->vatAmount, $iRec->date, $rec->checkDate),
                                    'invoicePayout' => $paydocs->payout,
                                    'fastMarker' => $fastMarker,
                                    'invoiceCurrentSumm' => $invDiff,
                                    'payDocuments' => $paydocs->used,
                                    'contragent' => $iRec->contragentName,
                                    'type' => $iRec->type,
                                );
                            }
                            $checkedPInvoices[$pInv] = $pInv;
                        }

                    }
                }
            }
        }

        self::addReportStat($data, 'payments', $paymentsSeconds, countR($invoicePaymentsByThread));
        foreach ($skipped as $reason => $numbers) {
            // Фактура от обединена сделка се среща във всяка нейна нишка
            $numbers = array_values(array_unique($numbers));
            self::addReportStat($data, $reason, 0, countR($numbers), $numbers);
        }
        // Колко нишки би изключил филтър по приключени сделки още в заявката за фактурите
        if (countR($closedDealThreads)) {
            self::addReportStat($data, 'closedDealThreads', 0, countR($closedDealThreads));
        }

        if ($rec->unpaid == 'all') {
            $sRecs = $sRecsAll;
            $pRecs = $pRecsAll;
            $totalInvoiceContragent = $totalInvoiceContragentAll;
        }

        //Подрежда се по дата на фактура
        $sRecs = $sRecs ?? array();
        $pRecs = $pRecs ?? array();

        if (countR($sRecs)) {
            arr::sortObjects($sRecs, 'invoiceDate', 'asc', 'stri');
        }

        if (countR($pRecs)) {
            arr::sortObjects($pRecs, 'invoiceDate', 'asc', 'stri');
        }

        // Избира се крайният масив според типа фактури във филтъра.
        $recs = $rec->typeOfInvoice == 'out' ? $sRecs : $pRecs;

        unset(
            $rec->totalInvoiceValueAll,
            $rec->totalInvoicePayoutAll,
            $rec->totalInvoiceNotPaydAll,
            $rec->totalInvoiceOverPaidAll,
            $rec->totalInvoiceOverDueAll
        );
        $rec->totalInvoiceValueAll = 0;
        $rec->totalInvoicePayoutAll = 0;
        $rec->totalInvoiceNotPaydAll = 0;
        $rec->totalInvoiceOverPaidAll = 0;
        $rec->totalInvoiceOverDueAll = 0;

        $contragentCurrency = array();
        $flagAll = false;

        // --- Финална обработка: тотали по контрагент, проверка за смесени валути, сортиране ---
        foreach ($recs as $key => $val) {

            // Маркира контрагенти с фактури в различни валути (не се сумират в една).
            if (!array_key_exists($val->contragent, $contragentCurrency)) {
                $contragentCurrency[$val->contragent] = (object)array(
                    'currency' => $val->currencyId,
                    'flag' => false,
                    'contragent' => false,
                );
            } else {
                if (($contragentCurrency[$val->contragent]->currency != $val->currencyId) &&
                    !is_null($contragentCurrency[$val->contragent]->currency)) {
                    $contragentCurrency[$val->contragent]->flag = true;
                }
                $contragentCurrency[$val->contragent]->currency = $val->currencyId;
            }

            // Сумите вече са индексирани по контрагент.
            $v = $totalInvoiceContragent[$val->contragent] ?? null;
            if ($v) {
                $recs[$key]->totalInvoiceValue = $v->totalInvoiceValue ?? 0;
                $recs[$key]->totalInvoicePayout = $v->totalInvoicePayout ?? 0;
                $recs[$key]->totalInvoiceNotPayd = $v->totalInvoiceNotPaid ?? 0;
                $recs[$key]->totalInvoiceOverPaid = $v->totalInvoiceOverPaid ?? 0;
                $recs[$key]->totalInvoiceOverDue = $v->totalInvoiceOverDue ?? 0;
            }
        }


        //Проверка за различни валути във фактурите на избраните контрагенти(вдига flagAll ако има различни)
        $flagAll = $test = false;
        foreach ($contragentCurrency as $val) {
            if (($test != $val->currency) && $test != false) {
                $flagAll = true;
                break;
            }
            $test = $val->currency;
        }

        $mixedCurrency = array();
        foreach ($contragentCurrency as $name => $val) {
            if ($val->flag) {
                $mixedCurrency[] = $name;
            }
        }
        self::addReportStat($data, 'rows', 0, countR($recs));
        self::addReportStat($data, 'contragents', 0, countR($contragentCurrency));
        if (countR($mixedCurrency)) {
            self::addReportStat($data, 'mixedCurrency', 0, countR($mixedCurrency), $mixedCurrency);
        }

        //Сумира стойностите на всички избрани контрагенти, ако са в една валута

        foreach ($totalInvoiceContragent as $k => $v) {
            $rec->totalInvoiceValueAll += $v->totalInvoiceValue;
            $rec->totalInvoicePayoutAll += $v->totalInvoicePayout;
            $rec->totalInvoiceNotPaydAll += $v->totalInvoiceNotPaid;
            $rec->totalInvoiceOverPaidAll += $v->totalInvoiceOverPaid;
            $rec->totalInvoiceOverDueAll += $v->totalInvoiceOverDue;
        }

        if ($rec->unpaid == 'all') {
            $rec->totalInvoiceValueAll = 0;
            $cArr = array();
            foreach ($recs as $key => $val) {

                if (!isset($cArr[$val->contragent])) {
                    $rec->totalInvoiceValueAll += $val->totalInvoiceValue ?? 0;
                    $cArr[$val->contragent] = true;

                }

            }

        }

        if (countR($recs)) {
            // Групите запазват реда на първата поява при рендиране на таблицата.
            uasort($recs, function ($a, $b) {
                $aName = (string) ($a->contragent ?? '');
                $bName = (string) ($b->contragent ?? '');
                $result = strcmp(mb_strtolower($aName, 'UTF-8'), mb_strtolower($bName, 'UTF-8'));
                if ($result != 0) return $result;

                // Различно изписаните имена са отделни групи и трябва да останат събрани.
                $result = strcmp($aName, $bName);
                if ($result != 0) return $result;

                $result = strcmp((string) ($a->invoiceDate ?? ''), (string) ($b->invoiceDate ?? ''));
                if ($result != 0) return $result;

                return strnatcasecmp(ltrim((string) ($a->invoiceNo ?? ''), '0'), ltrim((string) ($b->invoiceNo ?? ''), '0'));
            });
        }
        if (($rec->accountingCorrections ?? 'no') != 'yes') $this->logReportStats($data, $startedOn);

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
        // Дефинира се структурата на колоните за изглед и експорт.
        $fld = cls::get('core_FieldSet');

        if ($export === false) {
            // Колони за екран — различни според режима all/unpaid.
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,smartCenter');
            // Добавяме нова колона "Документ" преди колоната за номера
            $fld->FLD('documentType', 'varchar', 'caption=Вид,after=contragentId,tdClass=centered');
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');

            $fld->FLD('invoiceDate', 'varchar', 'caption=Дата,tdClass=centered');
            $fld->FLD('dueDate', 'varchar', 'caption=Краен срок,tdClass=centered');

            if ($rec->unpaid == 'all') {
                $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
                //if (countR($rec->data->recs) != arr::sumValuesArray($rec->data->recs, 'rate')) {
                $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
                // }

                $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate);
                $fld->FLD('invoiceValueBaseCurr', 'double(smartRound,decimals=2)', "caption=Стойност $baseCurrency");
                $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', "caption=Платено->Сума->$baseCurrency,smartCenter");
                $fld->FLD('paidDates', 'varchar', 'caption=Платено->Плащания->дата,smartCenter');
            }

            if ($rec->unpaid != 'all') {
                $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate);

                $fld->FLD('currencyId', 'varchar', 'caption=Валу-та,tdClass=centered');
                //if (countR($rec->data->recs) != arr::sumValuesArray($rec->data->recs, 'rate')) {
                $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност-> Сума->валута,smartCenter');
                // }
                $fld->FLD('invoiceValueBaseCurr', 'double(decimals=2)', "caption=Стойност-> Сума-> $baseCurrency,smartCenter");
                $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', "caption=Платено->Сума->$baseCurrency,smartCenter");
                $fld->FLD('paidDates', 'varchar', 'caption=Платено->Плащания->дата,smartCenter');
                $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', "caption=Състояние->Неплатено->$baseCurrency,smartCenter");
                $fld->FLD('invoiceOverSumm', 'double(smartRound,decimals=2)', "caption=Състояние->Надплатено-> $baseCurrency,smartCenter");
            }
        } else {
            // Колони за CSV/Excel експорт.
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,smartCenter');
            if (($rec->typeOfInvoice ?? 'out') == 'all') {
                $fld->FLD('invoiceDirection', 'varchar', 'caption=Фактури');
            }
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
            $fld->FLD('invoiceDate', 'date', 'caption=Дата,smartCenter');
            $fld->FLD('dueDate', 'date', 'caption=Краен срок,smartCenter');
            $fld->FLD('dueDateStatus', 'varchar', 'caption=Състояние,smartCenter');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
            $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate);
            $fld->FLD('invoiceValueBaseCurr', 'double(decimals=2)', "caption=Стойност-> Сума-> {$baseCurrency},smartCenter");
            $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->сума');
            $fld->FLD('paidDates', 'varchar', 'caption=Платено->Плащания,smartCenter');
            if ($rec->unpaid != 'all') {
                $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Състояние->Неплатено');
                $fld->FLD('invoiceOverSumm', 'double(smartRound,decimals=2)', 'caption=Състояние->Надплатено');
            }
        }

        if (($rec->accountingCorrections ?? 'no') == 'yes') {
            $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate ?? null);
            $fld->FLD('accountingCorrection', 'double(decimals=2)', "caption=Корекции->Сума $baseCurrency,after=paidDates");
            $fld->FLD('accountingDocuments', 'varchar', 'caption=Корекции->МО,after=accountingCorrection');
            if (($rec->unpaid ?? 'all') == 'all') {
                $fld->FLD('invoiceCurrentSumm', 'double(decimals=2)', "caption=Остатък->Неплатено $baseCurrency");
                $fld->FLD('invoiceOverSumm', 'double(decimals=2)', "caption=Остатък->Надплатено $baseCurrency");
            }
        }

        return $fld;
    }


    /**
     * Общият ред следва последната фактура на контрагента, включително при странициране.
     */
    protected function groupRows($recs, &$rows, $listFields, $field, $data)
    {
        parent::groupRows($recs, $rows, $listFields, $field, $data);
        if (($data->groupedFieldOnNewRow ?? null) !== true) return;

        $last = $types = $newRows = array();
        foreach ($recs as $index => $dRec) {
            $contragent = $dRec->contragent ?? '';
            $last[$contragent] = $index;
            $types[$contragent][$dRec->className ?? ''] = true;
        }
        $previousGroup = null;
        $columns = countR($listFields);
        foreach ($rows as $index => $row) {
            $dRec = $recs[$index] ?? null;
            if (!$dRec) {
                $content = "<td colspan='{$columns}' style='height:5px;padding:0!important;border:0;line-height:0;background:transparent'></td>";
                $newRows['spacer' . $index] = ht::createElement('tr', array('style' => 'background:transparent'), $content);
            } else {
                $group = $dRec->invoiceGroup ?? $dRec->contragent ?? '';
                if ($group !== $previousGroup && isset($row->invoiceSummary)) {
                    $newRows['summary|' . $index] = $this->getSummaryRow($columns, $row->invoiceSummary, $data);
                }
                $previousGroup = $group;
            }
            $newRows[$index] = $row;
            if (!$dRec || ($last[$dRec->contragent ?? ''] ?? null) !== $index || !isset($row->combinedSummary)
                || countR($types[$dRec->contragent ?? ''] ?? array()) < 2) continue;

            $newRows['combined|' . $index] = $this->getSummaryRow($columns, $row->combinedSummary, $data);
        }
        $rows = $newRows;
    }


    /**
     * По-светъл ред за сумите под заглавието на фирмата.
     */
    private function getSummaryRow($columns, $caption, $data)
    {
        $content = $this->getGroupedTr($columns, '', $caption, $data);

        return ht::createElement('tr', array('class' => 'group-by-field-row', 'style' => 'background-color:#eee'), $content);
    }


    /**
     * Еднакво обобщение за отделния вид и за двата вида фактури.
     */
    private function getContragentSummary($rec, $dRec, $invoiceType, $isCombined = false)
    {
        $caption = '<span class="quiet">' . tr('Общо фактури') . '</span> ' . ($isCombined ? self::getCombinedInvoiceCaption() : tr($invoiceType));
        if (!$this->canSeePriceFields($rec)) return $caption;

        $Double = core_Type::getByName('double(decimals=2)');
        $balanceSign = ($dRec->className ?? null) == 'purchase_Invoices' ? -1 : 1;
        $styleAmount = function ($amount, $sign = null) use ($Double, $balanceSign) {
            $verbal = $Double->toVerbal($amount);
            $balance = round($amount * ($sign ?? $balanceSign), 2);

            return Mode::is('text', 'plain') ? $verbal : ht::styleNumber($verbal, $balance, '#080');
        };
        $value = $styleAmount($dRec->totalInvoiceValue ?? 0);
        if ($isCombined && isset($dRec->invoiceTotals['out'], $dRec->invoiceTotals['in'])) {
            $value = $styleAmount($dRec->invoiceTotals['out'], 1) . ' - ' . $styleAmount($dRec->invoiceTotals['in'], -1) . ' = ' . $value;
        }
        if (($rec->accountingCorrections ?? 'no') == 'yes') {
            $currency = acc_Periods::getBaseCurrencyCode($rec->checkDate ?? null);
            $breakdown = $isCombined ? $value . ' ' . $currency . (Mode::is('text', 'plain') ? "\n" : '<br>') : '';

            return $caption . ': ' . $breakdown . self::getAccountingReconciliation(
                $dRec->totalInvoiceValue ?? 0, $dRec->totalAccountingCorrection ?? 0, $dRec->totalInvoicePayout ?? 0,
                ($dRec->totalInvoiceNotPayd ?? 0) + ($dRec->totalInvoiceOverPaid ?? 0), $isCombined ? 1 : $balanceSign, $currency
            );
        }
        $caption .= ': ' . $value;
        if (($rec->unpaid ?? 'all') == 'all') {
            $caption .= ' ' . acc_Periods::getBaseCurrencyCode($rec->checkDate ?? null);
        } else {
            $caption .= ' » <span class="quiet">' . tr('Платено') . ': </span>' . $styleAmount($dRec->totalInvoicePayout ?? 0)
                . ' » <span class="quiet">' . tr('Недоплатено') . ': </span>' . $styleAmount($dRec->totalInvoiceNotPayd ?? 0);
        }
        if (abs($dRec->totalInvoiceOverPaid ?? 0) > 0.01) {
            $caption .= ' » <span class="quiet">' . tr('Надплатено') . ': </span>' . $styleAmount($dRec->totalInvoiceOverPaid ?? 0);
        }
        return $caption;
    }


    /**
     * Пълният разчет с МО е еднакъв в инфото и в обобщенията по контрагент.
     */
    private static function getAccountingReconciliation($value, $correction, $paid, $balance, $sign, $currency)
    {
        $Double = core_Type::getByName('double(decimals=2)');
        $plain = Mode::is('text', 'plain');
        $captions = array('Стойност', 'Корекции', 'Платено', 'Остатък');
        $amounts = array($value, $correction, $paid, $balance);
        $signs = array($sign, -$sign, $sign, $sign);
        $parts = array();
        foreach ($amounts as $i => $amount) {
            $verbal = $Double->toVerbal($amount);
            if (!$plain) $verbal = '<b>' . ht::styleNumber($verbal, round($amount * $signs[$i], 2), '#080') . '</b>';
            // При нетни входящи суми изваждането на отрицателно число трябва да е еднозначно.
            if (($i == 1 || $i == 2) && round($amount, 2) < 0) $verbal = '(' . $verbal . ')';
            $caption = tr($captions[$i]) . ': ';
            $parts[] = ($plain ? $caption : '<span class="quiet">' . $caption . '</span>') . $verbal;
        }

        return $parts[0] . ' - ' . $parts[1] . ' - ' . $parts[2] . ' = ' . $parts[3] . ' ' . $currency;
    }


    /**
     * Кратко означение на сделката за неразпределена корекция, включително при експорт.
     */
    private static function getAccountingDealTitle($dRec)
    {
        $className = $dRec->accountingDealClass ?? (($dRec->className ?? '') == purchase_Invoices::class ? purchase_Purchases::class : sales_Sales::class);

        return tr('Неразпред. по') . ' #' . $className::getHandle($dRec->accountingDealId ?? 0);
    }


    /**
     * Еднакви означения за посоките в общото и фирменото обобщение.
     */
    private static function getCombinedInvoiceCaption()
    {
        return Mode::is('text', 'plain') ? tr('ИЗХОДЯЩИ минус ВХОДЯЩИ')
            : tr("|*<span style='color:#080'>|ИЗХОДЯЩИ|*</span> |минус|* <span class='red'>|ВХОДЯЩИ|*</span>");
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
        self::normalizeDetailRec($dRec);
        $invoicePayout = $dRec->invoicePayout ?? 0;
        $rate = $dRec->rate ?? 1;

        // fastMarker=1 — обикновена продажба: сума × курс; fastMarker=0 — бърза продажба: вече в основна валута.
        if (($dRec->fastMarker ?? 1) == 1) {

            $paidAmount = $invoicePayout * $rate;

        } else {
            $paidAmount = $invoicePayout;
        }


        return $paidAmount;
    }


    /**
     * Връща дати на плащания
     *
     * @param stdClass $dRec
     * @param bool $verbal
     *
     * @return mixed $paidDates$data->rec->salesTotalNotPaid
     */
    private static function getPaidDates($dRec, $verbal = true)
    {
        self::normalizeDetailRec($dRec);
        static $paymentLinks = array();
        $paidDatesList = '';
        $paidDates = '';

        // Обхождат се платежните документи, записани към реда на фактурата/проформата.
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

                $payDocumentRec = $payDocClass::fetch($Document->that);

                if (!$payDocumentRec || $payDocumentRec->state != 'active') {
                    continue;
                }

                //if ($dRec->type != 'invoice') continue;

                // При директно насочено плащане се проверява дали сочи към текущия документ.
                $fromContainerId = $payDocumentRec->fromContainerId ?? null;
                if ($fromContainerId) {
                    if ($dRec->invoiceContainerId != $fromContainerId) {
                        continue;
                    }

                    if (!empty($payDocumentRec->valior)) {
                        $paidDatesList .= ',' . $payDocumentRec->valior;
                    }
                } else {
                    // При разпределено плащане датата се взима от връзката между плащане и документ.
                    $paymentContainerId = $payDocumentRec->containerId ?? null;
                    if ($paymentContainerId && !array_key_exists($paymentContainerId, $paymentLinks)) {
                        $paymentLinks[$paymentContainerId] = deals_InvoicesToDocuments::getInvoiceArr($paymentContainerId);
                    }
                    $invoiceArr = $paymentLinks[$paymentContainerId] ?? array();
                    if (is_array($invoiceArr)) {
                        foreach ($invoiceArr as $val) {

                            $documentContainerId = $val->documentContainerId ?? null;
                            if (!$documentContainerId) {
                                continue;
                            }
                            $pDocumnt = doc_Containers::getDocument($documentContainerId);
                            if (!$pDocumnt) {
                                continue;
                            }
                            $linkedPayRec = $payDocClass::fetch($pDocumnt->that);
                            if ($linkedPayRec && !empty($linkedPayRec->valior)) {
                                $paidDatesList .= ',' . $linkedPayRec->valior;
                            }
                            break;
                        }
                    }
                }
            }
        }

        // Форматира се списъкът с дати според режима на показване или експорт.
        if ($paidDatesList === '') {
            return '';
        }

        if ($verbal === true) {
            $amountsValiors = explode(',', trim($paidDatesList, ','));

            foreach ($amountsValiors as $v) {
                $paidDate = dt::mysql2verbal($v, $mask = 'd.m.Y');

                $paidDates .= "$paidDate" . '<br>';
            }
        } else {
            $amountsValiors = explode(',', trim($paidDatesList, ','));

            foreach ($amountsValiors as $v) {
                $paidDate = dt::mysql2verbal($v, $mask = 'd.m.Y');

                $paidDates .= "$paidDate" . "\n\r";
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
    private static function getDueDate($dRec, $verbal = true, $rec = null)
    {
        self::normalizeDetailRec($dRec);
        // Вербален формат + warning hint, ако фактурата е просрочена и има неплатен остатък.
        if ($rec->unpaid != 'all' && !$rec->checkDate) {
            $checkDate = dt::now();
        } else {
            $checkDate = $rec->checkDate;
        }
        if ($verbal === true) {
            if ($dRec->dueDate) {
                $dueDate = dt::mysql2verbal($dRec->dueDate, $mask = 'd.m.Y');

                if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $checkDate) {
                    $dueDate = "<span class='smallHintHolder'>" . ht::createHint($dueDate, 'фактурата е просрочена', 'warning') . "</span>";
                }
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
        self::normalizeDetailRec($dRec);
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        // Типове за форматиране на дати и суми в таблицата.
        $Date = cls::get('type_Date');
        $Double = core_Type::getByName('double(decimals=2)');
        $styleAmount = function ($amount) use ($Double, $isPlain) {
            $verbal = $Double->toVerbal($amount);

            return $isPlain ? $verbal : ht::styleNumber($verbal, $amount);
        };

        $euroZoneDate = acc_Setup::getEurozoneDate();

        $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate);

        $row = new stdClass();
        $row->contragent = core_Type::getByName('varchar')->toVerbal($dRec->contragent ?? '');

        // Линк към документа и означение: Ф=фактура, ПФ=проформа, ДИ/КИ=известие.
        $invoiceNo = str_pad($dRec->invoiceNo, 10, '0', STR_PAD_LEFT);

        $row->invoiceNo = ht::createLinkRef(

            $invoiceNo,
            array(
                $dRec->className,
                'single',
                $dRec->invoiceId
            )

        );
        $type = 'Ф';
        $row->documentType = $type;

        if ($dRec->type != 'invoice') {

            if ($dRec->className == 'sales_Proformas') {
                $type = 'ПФ';
            } else {
                if ($dRec->invoiceValue < 0) {
                    $type = 'КИ';
                    $statecolor = 'credit';
                } else {
                    $type = 'ДИ';
                    $statecolor = 'debit';
                }
            }

            // Определя се знакът за дебитни/кредитни документи при експорт.
            $dcMark = $dRec->invoiceValue < 0 ? -1 : 1;

            $row->documentType = $type;
            if (isset($statecolor)) {
                $row->ROW_ATTR['class'] = "state-{$statecolor}";
            }

        }

        // --- Превалутиране и показване: период преди въвеждане на EUR (основна валута BGN) ---
        if ($rec->checkDate < $euroZoneDate) {

            //режим ВСИЧКИ  дата на справката преди ЕВРОЗОНАТА
            if ($rec->unpaid == 'all') {

                $row->paidAmount = $styleAmount(self::getPaidAmount($dRec));

                $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, true) . '</span>';

            }

            //режим НЕПЛАТЕНИ  дата на справката преди ЕВРОЗОНАТА
            if ($rec->unpaid != 'all') {

                $row->paidAmount = $styleAmount(self::getPaidAmount($dRec));


                $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, true) . '</span>';
            }

            //Стойност на фактурата в основна валута
            $row->invoiceValueBaseCurr = $styleAmount($dRec->invoiceValue);

            //Остатък за плащане в основна валута
            if ($dRec->invoiceCurrentSumm > 0) {

                $row->invoiceCurrentSumm = $styleAmount($dRec->invoiceCurrentSumm * $dRec->rate);
            }

            if ($dRec->invoiceCurrentSumm < 0) {
                $invoiceOverSumm = -1 * $dRec->invoiceCurrentSumm;
                $row->invoiceOverSumm = $styleAmount($invoiceOverSumm * $dRec->rate);
            }


        }

        // --- Превалутиране и показване: период от въвеждане на EUR (основна валута EUR) ---
        if ($rec->checkDate >= $euroZoneDate) {

            //режим ВСИЧКИ ВЪВ ЕВРОЗОНАТА
            if ($rec->unpaid == 'all') {

                $row->paidAmount = $styleAmount(self::getPaidAmount($dRec));

                $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, true) . '</span>';

            }

            //режим НЕПЛАТЕНИ  дата на справката ВЪВ ЕВРОЗОНАТА
            if ($rec->unpaid != 'all') {

                $row->paidAmount = $styleAmount(self::getPaidAmount($dRec));

                $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, true) . '</span>';
            }

            //Стойност на фактурата в основна валута
            $row->invoiceValueBaseCurr = $styleAmount($dRec->invoiceValue);

            //Остатък за плащане в основна валута
            if ($dRec->invoiceCurrentSumm > 0) {

                $row->invoiceCurrentSumm = $styleAmount($dRec->invoiceCurrentSumm * $dRec->rate);
            }

            if ($dRec->invoiceCurrentSumm < 0) {
                $invoiceOverSumm = -1 * $dRec->invoiceCurrentSumm;
                $row->invoiceOverSumm = $styleAmount($invoiceOverSumm * $dRec->rate);
            }

        }

        //Стойност на фактурата във валутата на издаване
        $invoiceCurrencyValue = self::getInvoiceCurrencyValue($rec, $dRec);
        if (isset($invoiceCurrencyValue)) {
            $row->invoiceValue = $styleAmount($invoiceCurrencyValue);
        }

        $row->invoiceDate = $Date->toVerbal($dRec->invoiceDate);

        $row->dueDate = self::getDueDate($dRec, true, $rec);

        if (($rec->accountingCorrections ?? 'no') == 'yes') {
            $correction = $dRec->accountingCorrection ?? 0;
            $sign = ($dRec->className ?? '') == purchase_Invoices::class ? 1 : -1;
            $verbal = $Double->toVerbal($correction);
            $row->accountingCorrection = $isPlain ? $verbal : ht::styleNumber($verbal, $correction * $sign, '#080');
            $links = array();
            foreach ($dRec->accountingDocuments ?? array() as $document) {
                $title = 'МО' . ($document->id ?? '') . ' / ' . ($document->date ?? '');
                $links[] = $isPlain ? $title : ht::createLinkRef($title, array('acc_Articles', 'single', $document->id ?? null));
            }
            $row->accountingDocuments = implode($isPlain ? ', ' : '<br>', $links);
            if (!empty($dRec->isAccountingCorrection)) {
                $title = self::getAccountingDealTitle($dRec);
                $row->documentType = 'МО';
                $row->invoiceNo = $isPlain ? $title : ht::createLinkRef($title, array($dRec->accountingDealClass ?? '', 'single', $dRec->accountingDealId ?? null));
                $row->invoiceValue = $row->invoiceValueBaseCurr = $row->paidAmount = $row->paidDates = '';
            }
        }

        $row->currencyId = $dRec->currencyId;

        $invoiceValue = $rec->unpaid == 'all' ? $dRec->invoiceValue : $dRec->invoiceValue;

        $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate);

        // Червен ред при просрочен неплатен остатък.
        $cond = $rec->unpaid != 'all' ? $dRec->dueDate && $dRec->invoiceCurrentSumm > 0 : $dRec->invoiceCurrentSumm > 0;

        if ($cond) {
            $row->ROW_ATTR['class'] = 'bold red';
        }

        if ($dRec->className == 'purchase_Invoices') {
            $row->className = 'Фактури ПОКУПКИ';
        } else {
            $row->className = 'Фактури ПРОДАЖБИ';
        }
        $invoiceType = ($dRec->className ?? '') == 'purchase_Invoices' ? 'ПОКУПКИ' : 'ПРОДАЖБИ';
        $row->invoiceSummary = $this->getContragentSummary($rec, $dRec, $invoiceType);

        if (($rec->typeOfInvoice ?? 'out') == 'all') {
            $summary = $dRec->combinedSummary ?? null;
            if (is_object($summary)) {
                $row->combinedSummary = $this->getContragentSummary($rec, $summary, 'ИЗХОДЯЩИ минус ВХОДЯЩИ', true);
            }
        }

        return $row;
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {

        $Enum = cls::get('type_Enum', array('options' => array('cash' => 'В брой', 'bank' => 'По банков път', 'intercept' => 'С прихващане', 'card' => 'С карта', 'factoring' => 'Факторинг', 'postal' => 'Пощенски паричен превод')));

        $baseCurrency = acc_Periods::getBaseCurrencyCode($data->rec->checkDate);
        $invoiceType = $data->rec->typeOfInvoice ?? 'out';
        $valueCaption = $invoiceType == 'all'
            ? tr('Стойност') . ' (' . self::getCombinedInvoiceCaption() . ')'
            : tr('Стойност');

        // Блок „Филтър“ над таблицата: избрани критерии и обобщени суми.
        $fieldTpl = new core_ET(
            tr(
                "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN contragent--><div>|Контрагент|*: <b>[#contragent#]</b></div><!--ET_END contragent-->
                                        <!--ET_BEGIN crmGroup--><div>|Група контрагенти|*: [#crmGroup#]</div><!--ET_END crmGroup-->
                                        <!--ET_BEGIN typeOfInvoice--> <div>|Фактури|*: <b>[#typeOfInvoice#]</b></div><!--ET_END typeOfInvoice-->
                                        <!--ET_BEGIN unpaid--><div>|Плащане|*: <b>[#unpaid#]</b></div><!--ET_END unpaid-->
                                        <!--ET_BEGIN paymentType--><div>|Начин на плащане|*: <b>[#paymentType#]</b></div><!--ET_END paymentType-->
                                        <!--ET_BEGIN accountingCorrections--><div>|Компенсации и счетоводни корекции|*: <b>[#accountingCorrections#]</b></div><!--ET_END accountingCorrections-->
                                        <!--ET_BEGIN accountingNotice--><div>[#accountingNotice#]</div><!--ET_END accountingNotice-->
                                        <!--ET_BEGIN totalInvoiceValueAll--><div>[#valueCaption#]: <b>[#totalInvoiceValueAll#]</b></div><!--ET_END totalInvoiceValueAll-->
                                        <!--ET_BEGIN accountingTotals--><div>[#accountingTotals#]</div><!--ET_END accountingTotals-->
                                        <!--ET_BEGIN totalInvoicePayoutAll--><div>|Общо ПЛАТЕНА СУМА|*: <b>[#totalInvoicePayoutAll#] $baseCurrency</b></div><!--ET_END totalInvoicePayoutAll-->
                                        <!--ET_BEGIN totalInvoiceNotPaydAll--><div>|Общо НЕПЛАТЕНА СУМА|*: <b>[#totalInvoiceNotPaydAll#] $baseCurrency</b></div><!--ET_END totalInvoiceNotPaydAll-->
                                        <!--ET_BEGIN totalInvoiceOverPaidAll--><div>|Общо НАДПЛАТЕНА СУМА|*: <b>[#totalInvoiceOverPaidAll#] $baseCurrency</b></div><!--ET_END totalInvoiceOverPaidAll-->
                                        <!--ET_BEGIN totalInvoiceOverDueAll--><div>|Общо ПРОСРОЧЕНА СУМА|*: <b>[#totalInvoiceOverDueAll#] $baseCurrency</b></div><!--ET_END totalInvoiceOverDueAll-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"
            )
        );
        if (isset($data->rec->typeOfInvoice)) {
            $inv = array('out' => 'ИЗХОДЯЩИ', 'in' => 'ВХОДЯЩИ', 'all' => 'ВСИЧКИ')[$data->rec->typeOfInvoice] ?? '';

            $fieldTpl->append(
                $inv,
                'typeOfInvoice'
            );
        }

        if (isset($data->rec->contragent) || isset($data->rec->crmGroup)) {
            $marker = 0;
            $groupVerb = '';
            if (isset($data->rec->crmGroup)) {
                foreach (type_Keylist::toArray($data->rec->crmGroup) as $group) {
                    $marker++;

                    $groupVerb .= (crm_Groups::getTitleById($group));

                    if ((countR((type_Keylist::toArray($data->rec->crmGroup))) - $marker) != 0) {
                        $groupVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $groupVerb . '</b>', 'crmGroup');
            }
        }

        if (isset($data->rec->unpaid)) {
            $paid = array('all' => 'ВСИЧКИ', 'unpaid' => 'НЕПЛАТЕНИ', 'overdue' => 'ПРОСРОЧЕНИ')[$data->rec->unpaid] ?? '';
            $fieldTpl->append(
                $paid,
                'unpaid'
            );
        }

        if (isset($data->rec->paymentType)) {

            $fieldTpl->append($Enum->toVerbal($data->rec->paymentType), 'paymentType');

        }

        if (($data->rec->accountingCorrections ?? 'no') == 'yes') {
            $fieldTpl->append(tr('ДА'), 'accountingCorrections');
            $notices = array();
            if (!empty($data->rec->accountingUnallocatedGroups)) {
                $notices[] = tr('Неразпределените корекции са по цяла сделка и не погасяват конкретна фактура; не участват в просрочените суми');
            }
            if (!empty($data->rec->accountingIncompleteGroups)) {
                $notices[] = tr('Има корекции по сделки с фактури извън избрания филтър; тези корекции не участват в сумите');
            }
            if (countR($notices)) $fieldTpl->append(implode('<br>', $notices), 'accountingNotice');
        }

        // Без права за цени общите суми не се показват
        if (!$Driver->canSeePriceFields($data->rec)) {
            $tpl->append($fieldTpl, 'DRIVER_FIELDS');

            return;
        }

        if (($data->rec->accountingCorrections ?? 'no') == 'yes') {
            $sign = $invoiceType == 'in' ? -1 : 1;
            $fieldTpl->append(self::getAccountingReconciliation(
                $data->rec->totalInvoiceValueAll ?? 0, $data->rec->totalAccountingCorrectionAll ?? 0, $data->rec->totalInvoicePayoutAll ?? 0,
                ($data->rec->totalInvoiceNotPaydAll ?? 0) + ($data->rec->totalInvoiceOverPaidAll ?? 0), $sign, $baseCurrency
            ), 'accountingTotals');
        }

        //Всички фактури
        if (isset($data->rec->totalInvoiceValueAll)) {
            $fieldTpl->replace($valueCaption, 'valueCaption');
            if (is_numeric($data->rec->totalInvoiceValueAll)) {
                $Double = core_Type::getByName('double(decimals=2)');
                $styleAmount = function ($amount, $sign = 1) use ($Double, $baseCurrency) {
                    $verbal = $Double->toVerbal($amount) . ' ' . $baseCurrency;

                    return Mode::is('text', 'plain') ? $verbal : ht::styleNumber($verbal, round($amount * $sign, 2), '#080');
                };
                $value = $styleAmount($data->rec->totalInvoiceValueAll, $invoiceType == 'in' ? -1 : 1);
                $invoiceTotals = $data->rec->data->invoiceTotals ?? array();
                if ($invoiceType == 'all' && isset($invoiceTotals['out'], $invoiceTotals['in'])) {
                    $value = $styleAmount($invoiceTotals['out']) . ' - ' . $styleAmount($invoiceTotals['in'], -1) . ' = ' . $value;
                }
                $fieldTpl->append($value, 'totalInvoiceValueAll');
            } else {
                $fieldTpl->append(
                    $data->rec->totalInvoiceValueAll . ' ' . $baseCurrency,
                    'totalInvoiceValueAll'
                );
            }
        }


        //Само когато е избрано 'НЕПЛАТЕНИ' фактури
        if (($data->rec->unpaid ?? 'all') != 'all') {

            //Платено по фактури
            if (isset($data->rec->totalInvoicePayoutAll)) {
                if (is_numeric($data->rec->totalInvoicePayoutAll)) {
                    $fieldTpl->append(
                        core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->totalInvoicePayoutAll),
                        'totalInvoicePayoutAll'
                    );
                } else {
                    $fieldTpl->append(
                        ($data->rec->totalInvoicePayoutAll),
                        'totalInvoicePayoutAll'
                    );
                }
            }

            //НЕДОплатено по фактури
            if (isset($data->rec->totalInvoiceNotPaydAll)) {
                if (is_numeric($data->rec->totalInvoiceNotPaydAll)) {
                    $fieldTpl->append(
                        core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->totalInvoiceNotPaydAll),
                        'totalInvoiceNotPaydAll'
                    );
                } else {
                    $fieldTpl->append(
                        ($data->rec->totalInvoiceNotPaydAll),
                        'totalInvoiceNotPaydAll'
                    );
                }
            }

            //НАДплатено по фактури
            if (isset($data->rec->totalInvoiceOverPaidAll)) {
                if (is_numeric($data->rec->totalInvoiceOverPaidAll)) {
                    $fieldTpl->append(
                        core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->totalInvoiceOverPaidAll),
                        'totalInvoiceOverPaidAll'
                    );
                } else {
                    $fieldTpl->append(
                        ($data->rec->totalInvoiceOverPaidAll),
                        'totalInvoiceOverPaidAll'
                    );
                }
            }

            //Просрочено по фактури
            if (isset($data->rec->totalInvoiceOverDueAll)) {
                if (is_numeric($data->rec->totalInvoiceOverDueAll)) {
                    $fieldTpl->append(
                        core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->totalInvoiceOverDueAll),
                        'totalInvoiceOverDueAll'
                    );
                } else {
                    $fieldTpl->append(
                        ($data->rec->totalInvoiceOverDueAll),
                        'totalInvoiceOverDueAll'
                    );
                }
            }
        }
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * Връща папките на контрагентите от избраните групи
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public static function getFoldersInGroups($rec)
    {
        $foldersInGroups = array();

        // Търсят се папките както на фирми, така и на лица в избраните CRM групи.
        foreach (array(crm_Companies::class, crm_Persons::class) as $clsName) {
            /** @var class-string<crm_Companies>|class-string<crm_Persons> $clsName */
            /** @var core_Query $q */
            $q = $clsName::getQuery();
            plg_ExpandInput::applyExtendedInputSearch($clsName, $q, $rec->crmGroup);
            $q->where('#folderId IS NOT NULL');
            $q->show('folderId');
            $foldersInGroups = array_merge($foldersInGroups, arr::extractValuesFromArray($q->fetchAll(), 'folderId'));
        }

        return $foldersInGroups;
    }

    /**
     * Връща проформите към които има насочени плащания
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public static function getProformsWithPaymant($rec, $folderIds = null)
    {

        // Избират се активните проформи според зададените филтри.
        /** @var core_Query $proformInvQuery */
        $proformInvQuery = sales_Proformas::getQuery();
        $proformInvQuery->where("#state = 'active'");

        if (isset($folderIds)) {
            if (!countR($folderIds)) return array();
            $proformInvQuery->in('folderId', $folderIds);
        } elseif (!empty($rec->contragent)) {
            $proformInvQuery->in('folderId', keylist::toArray($rec->contragent));
        }
        $proformInvQuery->show('threadId');
        $proformInvQuery->groupBy('threadId');

        $profomInvArr = arr::extractValuesFromArray($proformInvQuery->fetchAll(), 'threadId');

        $proformWithPayDocArr = array();
        if (!countR($profomInvArr)) return $proformWithPayDocArr;

        // Проверяват се касовите и банковите приходни документи за връзка към проформи.
        foreach (array(cash_Pko::class, bank_IncomeDocuments::class) as $payDocs) {
            /** @var class-string<cash_Pko>|class-string<bank_IncomeDocuments> $payDocs */
            $payDocsClassId = $payDocs::getClassId();
            /** @var core_Query $payDocQuery */
            $payDocQuery = $payDocs::getQuery();
            $payDocQuery->in('threadId', $profomInvArr);
            $payDocQuery->where("#state = 'active'");
            $payDocQuery->show('id,containerId,valior');
            $payRecords = $payDocQuery->fetchAll();
            $linksByPayment = array();
            $paymentIds = arr::extractValuesFromArray($payRecords, 'containerId');
            foreach (array_chunk($paymentIds, 500) as $ids) {
                /** @var core_Query $linkQuery */
                $linkQuery = deals_InvoicesToDocuments::getQuery();
                $linkQuery->in('documentContainerId', $ids);
                $linkQuery->orderBy('id', 'ASC');
                $linkQuery->show('id,documentContainerId,containerId');
                while ($link = $linkQuery->fetch()) {
                    $linksByPayment[$link->documentContainerId ?? 0][] = $link;
                }
            }
            foreach ($payRecords as $pDocRec) {
                $invArr = $linksByPayment[$pDocRec->containerId ?? 0] ?? array();
                if (!empty($invArr)) {
                    foreach ($invArr as $key => $val) {

                        $targetContainerId = $val->containerId ?? null;
                        if (!$targetContainerId) {
                            continue;
                        }
                        $pDocoment = doc_Containers::getDocument($targetContainerId);
                        if (!$pDocoment) {
                            continue;
                        }

                        if ($pDocoment->className != 'sales_Proformas') {
                            continue;
                        } else {

                            if (!array_key_exists($pDocoment->that, $proformWithPayDocArr)) {
                                $proformWithPayDocArr[$pDocoment->that] = (object)array(

                                    'documents' => array($pDocRec->id),
                                    'docClassId' => $payDocsClassId,
                                    'date' => $pDocRec->valior,

                                );
                            } else {
                                $obj = &$proformWithPayDocArr[$pDocoment->that];

                                array_push($obj->documents, $pDocRec->id);

                            }
                        }
                    }
                }
            }
        }

        return $proformWithPayDocArr;
    }


    /**
     * Стойността на фактурата във валутата на издаване - invoiceValue е в основната валута към датата на справката
     *
     * @param stdClass $rec
     * @param stdClass $dRec
     *
     * @return float|null
     */
    private static function getInvoiceCurrencyValue($rec, $dRec)
    {
        $euroZoneDate = acc_Setup::getEurozoneDate();
        $baseCurrency = acc_Periods::getBaseCurrencyCode($rec->checkDate);
        $value = $dRec->invoiceValue;
        $rate = $dRec->rate;

        if ($rec->checkDate < $euroZoneDate) {
            if ($dRec->invoiceDate < $euroZoneDate && $baseCurrency == 'BGN') {
                if ($dRec->currencyId == 'BGN') {

                    return $value;
                }
                if ($dRec->currencyId == 'EUR') {

                    return $value / 1.95583;
                }

                return $value / $rate;
            }

            return null;
        }

        if ($baseCurrency != 'EUR') {

            return null;
        }
        if ($dRec->currencyId == 'EUR') {

            return $value;
        }
        if ($dRec->invoiceDate >= $euroZoneDate) {

            return $value / $rate;
        }
        if ($dRec->currencyId == 'BGN') {

            return $value * 1.95583;
        }

        return $value / ($rate / 1.95583);
    }


    /**
     * Допълва липсващите полета в редове от стари версии на справката.
     *
     * @param stdClass $dRec
     */
    private static function normalizeDetailRec($dRec)
    {
        $defaults = array(
            'threadId' => null,
            'className' => null,
            'invoiceId' => null,
            'invoiceNo' => '',
            'invoiceDate' => null,
            'dueDate' => null,
            'invoiceContainerId' => null,
            'currencyId' => null,
            'rate' => 1,
            'invoiceValue' => 0,
            'invoiceVAT' => 0,
            'invoicePayout' => 0,
            'invoiceCurrentSumm' => 0,
            'contragent' => '',
            'type' => 'invoice',
            'payDocuments' => array(),
            'dcPay' => array(),
            'fastMarker' => 1,
            'totalInvoiceValue' => 0,
            'totalInvoicePayout' => 0,
            'totalInvoiceNotPayd' => 0,
            'totalInvoiceOverPaid' => 0,
            'totalInvoiceOverDue' => 0,
        );

        foreach ($defaults as $name => $value) {
            if (!property_exists($dRec, $name)) {
                $dRec->{$name} = $value;
            }
        }
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
        self::normalizeDetailRec($dRec);
        // Форматира дати, суми и статус „просрочен“ за CSV/Excel експорт.
        $Date = cls::get('type_Date');
        if (($rec->typeOfInvoice ?? 'out') == 'all') {
            $res->invoiceDirection = ($dRec->className ?? '') == 'purchase_Invoices' ? tr('Входящи') : tr('Изходящи');
        }

        if (($rec->unpaid ?? 'all') != 'all' && empty($rec->checkDate)) {
            $checkDate = dt::now();
        } else {
            $checkDate = $rec->checkDate ?? dt::today(false);
        }
        $invoiceValue = $dRec->invoiceValue ?? 0;
        $invoiceCurrentSumm = $dRec->invoiceCurrentSumm ?? 0;
        $dcMark = $invoiceValue < 0 ? -1 : 1;
        if (($dRec->type ?? 'invoice') != 'invoice') {
            foreach ((array)($dRec->dcPay ?? array()) as $k => $val) {
                $res->paidAmount = ($res->paidAmount ?? '') . ($val->amount ?? 0) * $dcMark;
            }
        } else {
            $res->paidAmount = self::getPaidAmount($dRec);
        }

        if (($dRec->type ?? 'invoice') != 'invoice') {
            foreach ((array)($dRec->dcPay ?? array()) as $k => $val) {
                $res->paidDates = ($res->paidDates ?? '') . $Date->toVerbal($val->payDate ?? null) . "\n\r";
            }
        } else {
            $res->paidDates = self::getPaidDates($dRec, false);
        }

        $res->dueDate = self::getDueDate($dRec, false, $rec);

        if ($invoiceCurrentSumm < 0) {
            $invoiceOverSumm = -1 * $invoiceCurrentSumm;
            $res->invoiceCurrentSumm = '';
            $res->invoiceOverSumm = ($invoiceOverSumm);
        }

        if (!empty($dRec->dueDate) && $invoiceCurrentSumm > 0 && $dRec->dueDate < $checkDate) {
            $res->dueDateStatus = 'Просрочен';
        }

        $invoiceNo = str_pad($dRec->invoiceNo ?? '', 10, '0', STR_PAD_LEFT);

        $res->invoiceNo = $invoiceNo;

        // Като на екрана: invoiceValue вече е в основната валута
        $res->invoiceValue = self::getInvoiceCurrencyValue($rec, $dRec);
        $res->invoiceValueBaseCurr = $invoiceValue;
        if (($rec->accountingCorrections ?? 'no') == 'yes') {
            $res->accountingCorrection = $dRec->accountingCorrection ?? 0;
            $documents = array();
            foreach ($dRec->accountingDocuments ?? array() as $document) $documents[] = 'МО' . ($document->id ?? '') . ' / ' . ($document->date ?? '');
            $res->accountingDocuments = implode(', ', $documents);
            $res->invoiceCurrentSumm = max(0, $invoiceCurrentSumm * ($dRec->rate ?? 1));
            $res->invoiceOverSumm = max(0, -$invoiceCurrentSumm * ($dRec->rate ?? 1));
            if (!empty($dRec->isAccountingCorrection)) {
                $res->invoiceNo = self::getAccountingDealTitle($dRec);
                $res->invoiceValue = $res->invoiceValueBaseCurr = $res->paidAmount = '';
            }
        }
    }

}
