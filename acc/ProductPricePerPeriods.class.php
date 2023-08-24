<?php


/**
 * Кеш на складовите цени към артикулите
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_ProductPricePerPeriods extends core_Manager
{


    /**
     * Заглавие на мениджъра
     */
    public $title = 'Кеш на цените на артикулите по месец';


    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'acc_Wrapper, plg_Sorting, plg_SaveAndNew';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,date,storeItemId,productItemId,price';


    /**
     * Кой може да пише?
     */
    public $canWrite = 'debug';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('date', 'date', 'caption=Дата,remember');
        $this->FLD('storeItemId', "acc_type_Item(select=titleNum,allowEmpty)", 'caption=Склад,mandatory,remember');
        $this->FLD('productItemId', "acc_type_Item(select=titleNum,allowEmpty)", 'caption=Артикул');
        $this->FLD('price', 'double', 'caption=Цена');

        $this->setDbIndex('date');
        $this->setDbIndex('productItemId');
        $this->setDbIndex('storeItemId');
        $this->setDbIndex('storeItemId,productItemId');
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $productItemRec = acc_Items::fetch($rec->productItemId);
        $storeItemRec = acc_Items::fetch($rec->storeItemId);
        $row->storeItemId = cls::get($storeItemRec->classId)->getHyperlink($storeItemRec->objectId, true);
        $row->productItemId = cls::get($productItemRec->classId)->getHyperlink($productItemRec->objectId, true);
        $row->price = ht::styleIfNegative($row->price, $rec->price);
        $url = array('acc_BalanceHistory', 'History', 'fromDate' => $productItemRec->earliestUsedOn, 'toDate' => $rec->date, 'accNum' => 321, 'ent1Id' => $rec->storeItemId, 'ent2Id' => $rec->productItemId);
        $row->date = ht::createLink($row->date, $url, false, 'ef_icon=img/16/clock_history.png');
    }


    /**
     * Връща крайната дата до която да се кешират записите
     *
     * @return mixed|string
     */
    public static function getCacheMaxDate()
    {
        $balanceBeforeCnt = acc_Setup::get('NOT_TO_CACHE_STOCK_PRICES_IN_LAST_BALANCE_COUNT');

        return dt::addMonths(-1 * $balanceBeforeCnt, dt::getLastDayOfMonth(), false);
    }


    /**
     * Извличане на данните от баланса
     *
     * @param date $fromDate
     * @param date|null $toDate
     * @param array $prevArr
     * @return array $res
     */
    public static function extractDataFromBalance($fromDate, $toDate = null, $prevArr = array())
    {
        $res = array();
        $storeAccSysId = acc_Accounts::getRecBySystemId(321)->id;
        $bQuery = acc_Balances::getQuery();
        $bQuery->orderBy('id', 'ASC');
        if(isset($fromDate)){
            $bQuery->where("#toDate >= '{$fromDate}'");
        }

        // Взимат се балансите до посочената дата в настройките
        if(!isset($toDate)){
            $toDate = static::getCacheMaxDate();
        }

        if(isset($toDate)){
            $bQuery->where("#toDate <= '{$toDate}'");
        }

        $count = 0;
        while ($bRec = $bQuery->fetch()) {

            // Извличане данните на конкретния баланс за посочените сметки
            core_Debug::startTimer('FETCH_BDETAILS');
            $dQuery = acc_BalanceDetails::getQuery();
            $dQuery->EXT('toDate', 'acc_Balances', 'externalName=toDate,externalKey=balanceId');
            $dQuery->EXT('periodId', 'acc_Balances', 'externalName=periodId,externalKey=balanceId');
            $dQuery->where("#balanceId = {$bRec->id} AND #accountId = {$storeAccSysId} AND #periodId IS NOT NULL AND #ent1Id IS NOT NULL");
            $dQuery->XPR('price', 'double', 'ROUND(#blAmount / NULLIF(#blQuantity, 0), 5)', 'column=none');
            $allRecs = $dQuery->fetchAll();
            core_Debug::stopTimer('FETCH_BDETAILS');
            $count = countR($allRecs);
            core_App::setTimeLimit($count * 0.4, false, 200);

            $saveArr = array();

            core_Debug::startTimer('FETCHED_EACH');
            foreach ($allRecs as $dRec) {
                if (is_null($dRec->price)) {
                    $dRec->price = 0;
                } else {
                    core_Debug::startTimer("ROUND");
                    $dRec->price = round($dRec->price, 5);
                    core_Debug::stopTimer("ROUND");
                }
                $dRec->price = ($dRec->price == 0) ? 0 : $dRec->price;
                if ($dRec->price < 0) continue;

                // Ако цената не е променяна няма да се обновява от предходния запис
                $key = "{$dRec->ent1Id}|{$dRec->ent2Id}";
                if (array_key_exists($key, $prevArr)) {
                    if (round($dRec->price, 5) == round($prevArr[$key], 5)) continue;
                }
                $rec = (object)array('date' => $dRec->toDate,
                                     'storeItemId' => $dRec->ent1Id,
                                     'productItemId' => $dRec->ent2Id,
                                     'price' => $dRec->price);
                $saveArr[] = $rec;
                $prevArr[$key] = $dRec->price;
            }
            $saveCount = countR($saveArr);
            $count += $saveCount;
            if ($saveCount) {
                $res[$bRec->toDate] = $saveArr;
            }
            core_Debug::stopTimer('FETCHED_EACH');
        }

        $fd = round(core_Debug::$timers["FETCH_BDETAILS"]->workingTime, 6);
        $feTime = round(core_Debug::$timers["FETCHED_EACH"]->workingTime, 6);
        $rTime = round(core_Debug::$timers["ROUND"]->workingTime, 6);

        static::logDebug("EXTRACT: ROUND{$rTime} / COUNT{$count} / FETCH_D: {$fd}/ FETCH_E: {$feTime}");

        return $res;
    }


    /**
     * Тестов екшън за първоначално наливане на данните
     */
    public function act_Test()
    {
        self::requireRightFor('debug');
        $this->truncate();

        return;
        core_App::setTimeLimit(300);
        $res = static::extractDataFromBalance(null);
        foreach ($res as $recs4Balance){
            $this->saveArray($recs4Balance);
        }
    }


    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FLD('balanceId', 'varchar', 'caption=Баланс');
        $data->listFilter->FLD('toDate', 'date', 'caption=Към дата');
        $balanceOptions = array('' => '') + acc_Balances::getSelectOptions('DESC', $skipClosed = false);
        $data->listFilter->setOptions('balanceId', $balanceOptions);
        $productListNum = acc_Lists::fetchBySystemId('catProducts')->num;
        $storeListNum = acc_Lists::fetchBySystemId('stores')->num;
        $data->listFilter->setFieldTypeParams('productItemId', array('lists' => $productListNum));
        $data->listFilter->setFieldTypeParams('storeItemId', array('lists' => $storeListNum));
        $data->listFilter->input();

        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'balanceId,storeItemId,productItemId,toDate';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        if ($rec = $data->listFilter->rec) {
            if (!empty($rec->productItemId)) {
                $data->query->where("#productItemId = {$rec->productItemId}");
            }
            if (!empty($rec->storeItemId)) {
                $data->query->where("#storeItemId = {$rec->storeItemId}");
            }
            if (!empty($rec->balanceId)) {
                $toDate = acc_Balances::fetchField($rec->balanceId, 'toDate');
                $data->query->where("#date = '{$toDate}'");
            }

            if (!empty($rec->toDate)) {
                redirect(array($mvc, 'filter', 'toDate' => $rec->toDate, 'productItemId' => $rec->productItemId, 'storeItemId' => $rec->storeItemId));
            }
        }
    }


    /**
     * Тестов екшън за дебъг
     */
    public function act_Filter()
    {
        requireRole('debug');
        $this->currentTab = 'Дебъг->Артикулни цени КЪМ дата';
        $toDate = Request::get('toDate', 'date');
        $productItemId = Request::get('productItemId', 'int');
        $storeItemId = Request::get('storeItemId', 'int');

        $toDate = empty($toDate) ? dt::today() : $toDate;
        $recs = static::getPricesToDate($toDate, $productItemId, $storeItemId);
        $countRecs = countR($recs);
        core_App::setTimeLimit($countRecs * 0.3, false, 300);

        $pager = cls::get('core_Pager', array('itemsPerPage' => 50));
        $Pager = $pager;
        $Pager->itemsCount = countR($recs);

        $rows = array();
        core_Debug::log("START RENDER_ROWS");
        core_Debug::startTimer('RENDER_ROWS');
        foreach ($recs as $rec) {
            if (!$Pager->isOnPage()) continue;
            $rows[$rec->id] = $this->recToVerbal($rec);
        }
        core_Debug::stopTimer('RENDER_ROWS');
        core_Debug::log("END RENDER_ROWS " . round(core_Debug::$timers["RENDER_ROWS"]->workingTime, 6));

        $table = cls::get('core_TableView', array('mvc' => $this));
        $fields = arr::make('date=Дата,storeItemId=Склад,productItemId=Артикул,price=Цена');
        $contentTpl = $table->get($rows, $fields);
        $toDate = dt::mysql2verbal($toDate, 'd.m.Y');
        $contentTpl->prepend(tr("|*<h2>|Към дата|* <span class='green'>{$toDate}</span></h2>"));
        $contentTpl->append($Pager->getHtml());

        return $this->renderWrapping($contentTpl);
    }


    /**
     * Връща последните цени на артикулите към дата
     *
     * @param date $toDate
     * @param int $productItemId
     * @param int $storeItemId
     * @return array $res
     */
    public static function getPricesToDate($toDate, $productItemId = null, $storeItemId = null)
    {
        $dateColName = str::phpToMysqlName('date');
        $storeColName = str::phpToMysqlName('storeItemId');
        $productColName = str::phpToMysqlName('productItemId');
        $priceColName = str::phpToMysqlName('price');

        $me = cls::get(get_called_class());
        $otherWhere = array();
        if (!empty($productItemId)) {
            $otherWhere[] = "`{$me->dbTableName}`.{$productColName} = {$productItemId}";
        }
        if (!empty($storeItemId)) {
            $otherWhere[] = "`{$me->dbTableName}`.{$storeColName} = {$storeItemId}";
        }
        $otherWhere = implode(' AND ', $otherWhere);
        if (!empty($otherWhere)) {
            $otherWhere = " AND {$otherWhere}";
        }

        core_Debug::log("START GROUP_ALL");
        core_Debug::startTimer('GROUP_ALL');
        $query1 = "SELECT * FROM (SELECT `{$me->dbTableName}`.`id` AS `id` , `{$me->dbTableName}`.`{$dateColName}` AS `date` , `{$me->dbTableName}`.`{$storeColName}` AS `storeItemId` , `{$me->dbTableName}`.`{$productColName}` AS `productItemId` , `{$me->dbTableName}`.`{$priceColName}` AS `{$priceColName}` FROM `{$me->dbTableName}` WHERE (`{$me->dbTableName}`.`{$dateColName}` <= '{$toDate}'{$otherWhere} )ORDER BY `{$me->dbTableName}`.`{$dateColName}` DESC LIMIT 1000000) as temp GROUP BY temp.storeItemId, temp.productItemId";
        $dbTableRes = $me->db->query($query1);
        core_Debug::stopTimer('GROUP_ALL');
        core_Debug::log("END GROUP_ALL " . round(core_Debug::$timers["GROUP_ALL"]->workingTime, 6));

        core_Debug::startTimer('PREV_EACH');
        $res = array();
        while ($arr = $me->db->fetchArray($dbTableRes)) {
            $res["{$arr['storeItemId']}|{$arr['productItemId']}"] = (object)$arr;
        }
        core_Debug::stopTimer('PREV_EACH');
        core_Debug::log("FETCHED PREV " . round(core_Debug::$timers["PREV_EACH"]->workingTime, 6));

        return $res;
    }


    public static function invalidateAfterDate($date)
    {
        core_Debug::startTimer('INVALIDATE_ALL');
        $me = cls::get(get_called_class());
        $toDate = dt::addMonths(-1, $date, false);

        core_Debug::startTimer('TO_DATE');
        $pricesToDate = static::getPricesToDate($toDate);
        $prevArr = array();
        array_walk($pricesToDate, function($arr, $key) use (&$prevArr) {$prevArr[$key] = $arr->price;});
        core_Debug::stopTimer('TO_DATE');

        core_Debug::startTimer('EXTRACT_DATE');
        $res = static::extractDataFromBalance($date, null, $prevArr);
        $allRes = array();
        array_walk($res, function($arr) use (&$allRes) {$allRes = array_merge($allRes, $arr);});
        core_Debug::stopTimer('EXTRACT_DATE');

        core_Debug::startTimer('SAVE_ARR');
        $exQuery = static::getQuery();
        $exQuery->where("#date >= '{$date}'");
        $exRecs = $exQuery->fetchAll();
        $synced = arr::syncArrays($allRes, $exRecs, 'storeItemId,productItemId,date', 'price');
        $iCount = countR($synced['insert']);
        $uCount = countR($synced['update']);
        $dCount = countR($synced['delete']);

        if ($iCount) {
            $me->saveArray($synced['insert']);
        }
        if ($uCount) {
            $me->saveArray($synced['update'], 'id,price');
        }
        if ($dCount) {
            $deleteIds = implode(',', $synced['delete']);
            static::delete("#id IN ({$deleteIds})");
        }
        core_Debug::stopTimer('SAVE_ARR');

        core_Debug::stopTimer('INVALIDATE_ALL');
        $wTime = round(core_Debug::$timers["INVALIDATE_ALL"]->workingTime, 6);
        $tTime = round(core_Debug::$timers["TO_DATE"]->workingTime, 6);
        $eTime = round(core_Debug::$timers["EXTRACT_DATE"]->workingTime, 6);
        $sTime = round(core_Debug::$timers["SAVE_ARR"]->workingTime, 6);

        $to = static::getCacheMaxDate();
        static::logDebug("FROM '{$date}' TO '{$to}'-RES(I{$iCount}:U{$uCount}:D{$dCount})-T'{$wTime}'/TO:{$tTime}/E:{$eTime}/S:{$sTime}");
    }


    function act_Invalidate()
    {
        $from = Request::get('FROM', 'date');
        $from = isset($from) ? $from : dt::addMonths(-5, dt::today());

        acc_ProductPricePerPeriods::invalidateAfterDate($from);
    }
}