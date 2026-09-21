<?php


/**
 * Мениджър на отчети за пасивни клиенти
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Пасивни клиенти
 */
class sales_reports_PassiveCustomers extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, admin, debug, sales';



    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'amountDelivered,numberOfSales';


    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck = 'folderId';


    /**
     * По кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'periodPassive,periodActive,dealers,crmGroup,minShipment';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('periodPassive', 'time(suggestions=|1 седмица|1 месец|3 месеца|6 месеца)', 'caption=Период->Пасивен, after=title,mandatory,single=none,removeAndRefreshForm');
        $fieldset->FLD('periodActive', 'time(suggestions=1 месец|3 месеца|6 месеца|1 година|2 години)', 'caption=Период->Активен, after=periodPassive,mandatory,single=none,removeAndRefreshForm');

        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Търговци->Търговци,placeholderType=all,single=none,mandatory,after=periodActive');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Групи->Група контрагенти,placeholderType=all,after=dealers,single=none');
        $fieldset->FLD('minShipment', 'double', 'caption=Мин. продажби, after=crmGroup,single=none, unit= лв.');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;

        $form->setDefault('periodPassive', '6 месеца');
        $form->setDefault('periodActive', '2 години');
        $form->setDefault('minShipment', 1000);
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
        core_App::setTimeLimit(250);

        $recs = $shipmentActiveContragents = $shipmentPassiveContragents = array();
        $rec->periodPassive = $rec->periodPassive ?? 6 * 30 * 24 * 60 * 60;
        $rec->periodActive = $rec->periodActive ?? 2 * 365 * 24 * 60 * 60;
        $rec->dealers = $rec->dealers ?? null;
        $rec->crmGroup = $rec->crmGroup ?? null;
        $rec->minShipment = $rec->minShipment ?? 1000;

        $dealers = keylist::toArray($rec->dealers);
        $filterByDealer = (!empty($dealers) && !in_array(-1, $dealers));

        $passivePeriodStart = dt::addSecs(-$rec->periodPassive, dt::today(), false);
        $activePeriodStart = dt::addSecs(-$rec->periodActive, dt::addDays(-1, $passivePeriodStart, false), false);

        // Папките на контрагентите от избраните групи се определят наведнъж
        $foldersInGroups = $this->getFoldersInGroups($rec->crmGroup);

        // Определяме контрагентите с експедиции в периода на активност и периода на пасивност
        $shQuery = store_ShipmentOrders::getQuery();
        $shQuery->in('state', array('rejected', 'draft'), true);
        $shQuery->where("#valior >= '$activePeriodStart'");

        // Първият документ на нишката се взима от денормализираните ѝ полета
        $shQuery->EXT('firstDocClass', 'doc_Threads', 'externalKey=threadId');
        $shQuery->EXT('firstDocId', 'doc_Threads', 'externalKey=threadId');
        $shQuery->show('folderId,threadId,valior,amountDelivered,firstDocClass,firstDocId');
        $shQuery->selectOnProxy();

        // Първи обход - остават само експедициите, чиято нишка започва с продажба
        $salesClassId = sales_Sales::getClassId();
        $shipmentRecs = $saleIds = array();
        while ($shRec = $shQuery->fetch()) {
            $firstDocId = $shRec->firstDocId ?? null;

            if (empty($shRec->firstDocClass) || empty($firstDocId)) {

                // За нишките без изчислени данни се пита самият документ
                $firstDoc = doc_Threads::getFirstDocument($shRec->threadId);
                if (!$firstDoc || !(cls::get($firstDoc) instanceof sales_Sales)) continue;
                $firstDocId = $firstDoc->that;
            } elseif ($shRec->firstDocClass != $salesClassId) {
                continue;
            }

            $shRec->_saleId = $firstDocId;
            $shipmentRecs[] = $shRec;
            if (!empty($firstDocId)) {
                $saleIds[$firstDocId] = $firstDocId;
            }
        }

        // Търговците на продажбите, само ако се филтрира по тях
        $saleDealers = array();
        if ($filterByDealer && countR($saleIds)) {
            $dQuery = sales_Sales::getQuery();
            $dQuery->in('id', $saleIds);
            $dQuery->show('id,dealerId');
            $dQuery->selectOnProxy();
            while ($dRec = $dQuery->fetch()) {
                $saleDealers[$dRec->id] = $dRec->dealerId ?? null;
            }
        }

        foreach ($shipmentRecs as $shRec) {

            $id = $shRec->folderId;

            // Филтър по дилър
            if ($filterByDealer) {
                $docDealer = $saleDealers[$shRec->_saleId] ?? null;
                if (!in_array($docDealer, $dealers)) continue;
            }

            // Филтър по група на контрагента на експедицията
            if ($rec->crmGroup) {
                if (!isset($foldersInGroups[$id])) continue;
            }

            // Активните клиенти - тези с експедиции преди началото на пасивния период
            if ($shRec->valior < $passivePeriodStart) {
                if (!array_key_exists($id, $shipmentActiveContragents)) {
                    $shipmentActiveContragents[$id] = (object)array(
                        'folderId' => $shRec->folderId,
                        'amountDelivered' => $shRec->amountDelivered ?? 0,
                        'numberOfSales' => 1,
                        'numberOfInMails' => '',
                        'numberOfOutMails' => '',
                    );
                } else {
                    $obj = &$shipmentActiveContragents[$id];
                    $obj->amountDelivered += $shRec->amountDelivered ?? 0;
                    $obj->numberOfSales++;
                }
            }

            // Клиентите с експедиции в пасивния период
            if ($shRec->valior >= $passivePeriodStart && ($shRec->amountDelivered ?? 0) > 0) {
                $shipmentPassiveContragents[$shRec->folderId] = $shRec->folderId;
            }
        }

        // Добавяне на експедициите от БЪРЗИ ПРОДАЖБИ
        $salQuery = sales_Sales::getQuery();
        $salQuery->in('state', array('rejected', 'draft'), true);
        $salQuery->like('contoActions', 'ship');
        $salQuery->where("#valior >= '$activePeriodStart'");
        $salQuery->show('folderId,valior,amountDelivered,dealerId');
        $salQuery->selectOnProxy();

        while ($salRec = $salQuery->fetch()) {

            $id = $salRec->folderId;

            // Филтър по дилър
            if ($filterByDealer) {
                if (!in_array($salRec->dealerId, $dealers)) continue;
            }

            // Филтър по група на контрагента на бързата продажба
            if ($rec->crmGroup) {
                if (!isset($foldersInGroups[$id])) continue;
            }

            // Активните клиенти - тези с бързи продажби преди началото на пасивния период
            if ($salRec->valior < $passivePeriodStart) {
                if (!array_key_exists($id, $shipmentActiveContragents)) {
                    $shipmentActiveContragents[$id] = (object)array(
                        'folderId' => $salRec->folderId,
                        'amountDelivered' => $salRec->amountDelivered ?? 0,
                        'numberOfSales' => 1,
                        'numberOfInMails' => '',
                        'numberOfOutMails' => '',
                    );
                } else {
                    $obj = &$shipmentActiveContragents[$id];
                    $obj->amountDelivered += $salRec->amountDelivered ?? 0;
                    $obj->numberOfSales++;
                }
            }

            // Клиентите с бързи продажби в пасивния период
            if ($salRec->valior >= $passivePeriodStart && ($salRec->amountDelivered ?? 0) > 0) {
                $shipmentPassiveContragents[$salRec->folderId] = $salRec->folderId;
            }
        }

        // Ако е зададен праг, отпадат клиентите с по-малко продажби през активния период
        if ($rec->minShipment != 0 && (countR($shipmentActiveContragents) > 0)) {
            foreach ($shipmentActiveContragents as $val) {
                if ($val->amountDelivered < $rec->minShipment) {
                    unset($shipmentActiveContragents[$val->folderId]);
                }
            }
        }

        // Остават активните клиенти без продажби през пасивния период
        foreach ($shipmentActiveContragents as $key => $val) {
            if (!isset($shipmentPassiveContragents[$key])) {
                $recs[$key] = $val;
            }
        }

        // Без редове няма и какво да се брои - иначе заявките остават без филтър по папка
        if (!countR($recs)) {

            return $recs;
        }

        core_App::setTimeLimit(0.05 * countR($recs), false, 250);

        // Входящи и изходящи имейли през пасивния период
        $incomingMailsCount = $this->getMailsCount('email_Incomings', array_keys($recs), $passivePeriodStart);
        $outgoingMailsCount = $this->getMailsCount('email_Outgoings', array_keys($recs), $passivePeriodStart);

        foreach ($recs as $key => $val) {
            if (isset($incomingMailsCount[$key])) {
                $recs[$key]->numberOfInMails = $incomingMailsCount[$key];
            }

            if (isset($outgoingMailsCount[$key])) {
                $recs[$key]->numberOfOutMails = $outgoingMailsCount[$key];
            }

        }

        arr::sortObjects($recs, 'amountDelivered', 'DESC');

        return $recs;
    }


    /**
     * Папките на контрагентите, които са в избраните групи
     *
     * При лицата групите идват от фирмата им, както ги връща doc_Folders::getContragentData()
     *
     * @param string|null $crmGroup
     *
     * @return array - ид на папка => ид на папка
     */
    private function getFoldersInGroups($crmGroup)
    {
        $res = array();
        if (empty($crmGroup)) return $res;

        // Фирмите в избраните групи
        $cQuery = crm_Companies::getQuery();
        plg_ExpandInput::applyExtendedInputSearch('crm_Companies', $cQuery, $crmGroup);
        $cQuery->show('id,folderId');
        $cQuery->selectOnProxy();

        $companyIds = array();
        while ($cRec = $cQuery->fetch()) {
            $companyIds[$cRec->id] = $cRec->id;

            if (!empty($cRec->folderId)) {
                $res[$cRec->folderId] = $cRec->folderId;
            }
        }

        if (!countR($companyIds)) return $res;

        // Лицата, чиято фирма е в избраните групи
        $pQuery = crm_Persons::getQuery();
        $pQuery->in('buzCompanyId', $companyIds);
        $pQuery->where("#folderId IS NOT NULL");
        $pQuery->show('folderId');
        $pQuery->selectOnProxy();

        while ($pRec = $pQuery->fetch()) {
            $res[$pRec->folderId] = $pRec->folderId;
        }

        return $res;
    }


    /**
     * Брой писма по папки след посочената дата
     *
     * @param string $mvc
     * @param array $folderIds
     * @param string $from
     *
     * @return array - ид на папка => брой
     */
    private function getMailsCount($mvc, $folderIds, $from)
    {
        $res = array();
        if (!countR($folderIds)) return $res;

        $query = cls::get($mvc)->getQuery();
        $query->in('folderId', $folderIds);
        $query->where(array("#createdOn >= '[#1#]'", $from));
        $query->XPR('mailsCount', 'int', 'COUNT(#id)');
        $query->groupBy('folderId');
        $query->show('folderId,mailsCount');
        $query->selectOnProxy();

        while ($mRec = $query->fetch()) {
            $res[$mRec->folderId] = $mRec->mailsCount;
        }

        return $res;
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
            $fld->FLD('folderId', 'key(mvc=doc_Folders,select=name)', 'caption=Контрагент');
            $fld->FLD('numberOfSales', 'int', 'caption=Активен продажби->Брой');
            $fld->FLD('amountDelivered', 'double(decimals=2)', 'caption=Активен продажби->Стойност');
            $fld->FLD('numberOfInMails', 'int', 'caption=Пасивен Писма->Входящи');
            $fld->FLD('numberOfOutMails', 'int', 'caption=Пасивен Писма->Изходящи');

        } else {

            $fld->FLD('folderId', 'varchar', 'caption=Контрагент');
            $fld->FLD('numberOfSales', 'int', 'caption=Активен продажби->Брой');
            $fld->FLD('amountDelivered', 'double(decimals=2)', 'caption=Активен продажби->Стойност');
            $fld->FLD('numberOfInMails', 'int', 'caption=Пасивен Писма->Входящи');
            $fld->FLD('numberOfOutMails', 'int', 'caption=Пасивен Писма->Изходящи');

        }

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = core_Type::getByName('int');
        $Double = core_Type::getByName('double(decimals=2)');
        $row = new stdClass();

        $row->folderId = doc_Folders::getHyperlink($dRec->folderId ?? null);
        $row->numberOfSales = $Int->toVerbal($dRec->numberOfSales ?? 0);
        $row->amountDelivered = $Double->toVerbal($dRec->amountDelivered ?? 0);
        if (isset($dRec->numberOfInMails)) {
            $row->numberOfInMails = $Int->toVerbal($dRec->numberOfInMails);
        }

        if (isset($dRec->numberOfOutMails)) {
            $row->numberOfOutMails = $Int->toVerbal($dRec->numberOfOutMails);
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
        $Time = cls::get('type_Time');
        $Date = cls::get('type_Date');
        $groupVerb = $dealersVerb = '';

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN periodPassive--><div>|Пасивен период|*: [#periodPassive#]</div><!--ET_END periodPassive-->
                                        <!--ET_BEGIN periodActive--><div>|Активен период|*: [#periodActive#]</div><!--ET_END periodActive-->
                                        <!--ET_BEGIN minShipment--><div>|Мин. продажби|*: [#minShipment#]</div><!--ET_END minShipment-->
                                        <!--ET_BEGIN crmGroup--><div>|Група контрагенти|*: [#crmGroup#]</div><!--ET_END crmGroup-->
                                        <!--ET_BEGIN dealers--><div>|Търговци|*: [#dealers#]</div><!--ET_END dealers-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        $periodPassive = $data->rec->periodPassive ?? 6 * 30 * 24 * 60 * 60;
        $periodActive = $data->rec->periodActive ?? 2 * 365 * 24 * 60 * 60;
        $lastRefreshed = $data->rec->lastRefreshed ?? dt::now();
        $passivePeriodStart = dt::addSecs(-$periodPassive, $lastRefreshed, false);
        $activePeriodStart = dt::addSecs(-$periodActive, dt::addDays(-1, $passivePeriodStart), false);

        if (isset($data->rec->periodPassive)) {
            $fieldTpl->append('<b>' . $Time->toVerbal($periodPassive) . ' (' . $Date->toVerbal($passivePeriodStart) . ' - ' . $Date->toVerbal($lastRefreshed) . ')' . '</b>', 'periodPassive');
        }

        if (isset($data->rec->periodActive)) {
            $fieldTpl->append('<b>' . $Time->toVerbal($periodActive) . ' (' . $Date->toVerbal($activePeriodStart) . ' - ' . $Date->toVerbal(dt::addDays(-1, $passivePeriodStart, false)) . ')' . '</b>', 'periodActive');
        }
        if (isset($data->rec->minShipment)) {
            $fieldTpl->append('<b>' . ($data->rec->minShipment) . '</b>', 'minShipment');
        }

        if (isset($data->rec->crmGroup)) {
            $marker = 0;
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
        } else {
            $fieldTpl->append(tr('|*<b>|Всички|*</b>'), 'crmGroup');
        }

        $dealers = keylist::toArray($data->rec->dealers ?? null);
        if (!empty($dealers) && min(array_keys($dealers)) >= 1) {
            foreach ($dealers as $dealer) {
                $dealersVerb .= (core_Users::getTitleById($dealer) . ', ');
            }

            $fieldTpl->append('<b>' . trim($dealersVerb, ',  ') . '</b>', 'dealers');
        } else {
            $fieldTpl->append(tr('|*<b>|Всички|*</b>'), 'dealers');
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
     * @param $ExportClass
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $folderRec = doc_Folders::fetch($dRec->folderId ?? null);
        $res->folderId = $folderRec->title ?? null;
        $res->numberOfSales = $dRec->numberOfSales ?? 0;
        $res->amountDelivered = $dRec->amountDelivered ?? 0;
        $res->numberOfInMails = $dRec->numberOfInMails ?? 0;
        $res->numberOfOutMails = $dRec->numberOfOutMails ?? 0;
    }
}
