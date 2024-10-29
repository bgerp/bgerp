<?php


/**
 * Кеш на складовите цени към артикулите
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
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
    public $loadList = 'acc_Wrapper, plg_Sorting';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,date,productItemId,otherItemId,price,updatedOn';


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
        $this->FLD('date', 'date', 'caption=Дата');
        $this->FLD('otherItemId', "acc_type_Item(select=titleNum,allowEmpty)", 'caption=Перо,mandatory,remember,oldFieldName=storeItemId');
        $this->FLD('productItemId', "acc_type_Item(select=titleNum,allowEmpty)", 'caption=Артикул');
        $this->FLD('price', 'double', 'caption=Цена');
        $this->FLD('updatedOn', 'datetime(format=smartTime)', 'caption=Промяна');
        $this->FLD('type', 'enum(stores=Складове,production=Незавършено производство,costs=Разходи)', 'caption=Цена,silent,input=hidden');

        $this->setDbIndex('type');
        $this->setDbIndex('date');
        $this->setDbIndex('productItemId');
        $this->setDbIndex('otherItemId');
        $this->setDbIndex('otherItemId,productItemId');
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $productItemRec = acc_Items::fetch($rec->productItemId);

        if(isset($rec->otherItemId)){
            $otherItemRec = acc_Items::fetch($rec->otherItemId);
            $row->otherItemId = acc_Items::getVerbal($otherItemRec, 'titleLink');;
        }

        $row->productItemId = cls::get($productItemRec->classId)->getHyperlink($productItemRec->objectId, true);
        $row->price = ht::styleNumber($row->price, $rec->price);

        if($rec->type == 'stores'){
            $url = array('acc_BalanceHistory', 'History', 'fromDate' => $productItemRec->earliestUsedOn, 'toDate' => $rec->date, 'accNum' => 321, 'ent1Id' => $rec->otherItemId, 'ent2Id' => $rec->productItemId);
        } elseif($rec->type == 'costs'){
            $url = array('acc_BalanceHistory', 'History', 'fromDate' => $productItemRec->earliestUsedOn, 'toDate' => $rec->date, 'accNum' => 60201, 'ent1Id' => $rec->otherItemId, 'ent2Id' => $rec->productItemId);
        } else {
            $url = array('acc_BalanceHistory', 'History', 'fromDate' => $productItemRec->earliestUsedOn, 'toDate' => $rec->date, 'accNum' => 61101, 'ent1Id' => $rec->productItemId);
        }

        $row->date = ht::createLink($row->date, $url, false, 'ef_icon=img/16/clock_history.png');
    }


    /**
     * Извличане на данните от баланса
     *
     * @param date $fromDate
     * @param string $sysId
     * @param date|null $toDate
     * @param array $prevArr
     * @return array $res
     */
    public static function extractDataFromBalance($fromDate, $sysId, $toDate = null, $prevArr = array())
    {
        $res = array();
        $accId = acc_Accounts::getRecBySystemId($sysId)->id;
        $type = ($sysId == '61101') ? 'production' : (($sysId == '321') ? 'stores' : 'costs');

        $bQuery = acc_Balances::getQuery();
        $bQuery->where("#periodId IS NOT NULL");
        $bQuery->orderBy('toDate', 'ASC');
        $bQuery->show('toDate,id');
        if(isset($fromDate)){
            $bQuery->where("#toDate >= '{$fromDate}'");
        }

        // Взимат се балансите до посочената дата в настройките
        $toDate = $toDate ?? dt::getLastDayOfMonth();
        $bQuery->where("#toDate <= '{$toDate}'");

        $bRecs = $bQuery->fetchAll();
        $balanceIds = array_keys($bRecs);
        if(!countR($balanceIds)) {
            wp("PRICE_CACHE_NO_BALANCE", $fromDate, $toDate);
            return $res;
        }

        $groupedDetails = array();
        $dQuery = acc_BalanceDetails::getQuery();
        $dQuery->EXT('toDate', 'acc_Balances', 'externalName=toDate,externalKey=balanceId');
        $dQuery->EXT('periodId', 'acc_Balances', 'externalName=periodId,externalKey=balanceId');
        $dQuery->where("#accountId = {$accId} AND #ent1Id IS NOT NULL");
        $dQuery->in('balanceId', $balanceIds);
        $dQuery->show('balanceId,blAmount,blQuantity,ent1Id,ent2Id,debitQuantity,debitAmount');
        while($dRec1 = $dQuery->fetch()){
            $groupedDetails[$bRecs[$dRec1->balanceId]->toDate][] = $dRec1;
        }
        ksort($groupedDetails);

        $now = dt::now();
        foreach ($groupedDetails as $toDate => $details){
            $saveArr = array();
            $countC = countR($details);
            core_App::setTimeLimit($countC * 0.4, false, 200);

            foreach ($details as $dRec){
                if(empty($dRec->blQuantity)){
                    if(empty($dRec->debitQuantity)){
                        // Ако има ненулево крайно к-во и нулево дебитно к-во значи е 0
                        $dRec->price = 0;
                    } else {
                        // Ако има дебитно к-во цената е частното между дебитното салдо и количество
                        @$dRec->price = round($dRec->debitAmount / $dRec->debitQuantity, 5);
                    }
                } else {
                    // Ако има ненулево крайно к-во - цената е частното на крайното салдо и количество
                    @$dRec->price = round($dRec->blAmount / $dRec->blQuantity, 5);
                }
                $dRec->price = ($dRec->price == 0) ? 0 : $dRec->price;

                if ($dRec->price < 0) continue;

                // Ако цената не е променяна няма да се обновява от предходния запис
                $key = ($sysId == '61101') ?  $dRec->ent1Id : "{$dRec->ent1Id}|{$dRec->ent2Id}";
                if (array_key_exists($key, $prevArr)) {
                    $roundPrev = round($prevArr[$key], 5);
                    $roundCurrent = round($dRec->price, 5);

                    // Ако е имало стара положителна цена, но новата е по-малка от 0.00001 - да не се записва
                    if($roundPrev >= 0.00001 && $roundCurrent <= 0.00001) continue;

                    // Ако старата цена е колко новата - да не се записва
                    if ($roundCurrent == $roundPrev) continue;
                }

                $item1Id = ($sysId == '61101') ? null : $dRec->ent1Id;
                $item2Id = ($sysId == '61101') ? $dRec->ent1Id : $dRec->ent2Id;

                if(empty($toDate)){
                    wp("PRICE_CACHE_NO_DATE", $dRec, $toDate, $balanceIds);
                }
                $rec = (object)array('date' => $toDate,
                                     'otherItemId' => $item1Id,
                                     'productItemId' => $item2Id,
                                     'updatedOn' => $now,
                                     'price' => round($dRec->price, 5),
                                     'type' => $type);
                $saveArr[] = $rec;
                $prevArr[$key] = $dRec->price;
            }

            $saveCount = countR($saveArr);
            if ($saveCount) {
                $res[$toDate] = $saveArr;
            }
        }

        return $res;
    }


    /**
     * Функция, която се вика по крон по разписание
     * Синхронизира перата
     */
    public static function callback_SyncStockPrices()
    {
        $me = cls::get(get_called_class());
        $me->truncate();

        core_App::setTimeLimit(300);
        $res = static::extractDataFromBalance(null, '321');

        foreach ($res as $key => $recs4Balance){
            $me->saveArray($recs4Balance);
        }

        $res1 = static::extractDataFromBalance(null, '61101');
        foreach ($res1 as $recs4Balance){
            $me->saveArray($recs4Balance);
        }

        $res2 = static::extractDataFromBalance(null, '60201');
        foreach ($res2 as $recs4Balance){
            $me->saveArray($recs4Balance);
        }
    }


    /**
     * Тестов екшън за първоначално наливане на данните
     */
    public function act_Test()
    {
        self::requireRightFor('debug');
        $this->callback_SyncStockPrices();

        redirect(array($this, 'list', 'type' => 'stores'));
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
        $type = Request::get('type', 'enum(stores,production,costs)');
        $data->listFilter->input(null, 'silent');

        $data->listFilter->FLD('balanceId', 'varchar', 'caption=Баланс');
        $data->listFilter->FLD('toDate', 'date', 'caption=Към дата');
        $balanceOptions = array('' => '') + acc_Balances::getSelectOptions('DESC', $skipClosed = false);
        $data->listFilter->setOptions('balanceId', $balanceOptions);
        $productListNum = acc_Lists::fetchBySystemId('catProducts')->num;

        $data->listFilter->setFieldTypeParams('productItemId', array('lists' => $productListNum));
        if($type == 'production'){
            unset($data->listFields['otherItemId']);
            $mvc->currentTab = 'Цени от баланса->Незавършено производство';
        } elseif($type == 'costs'){
            $mvc->currentTab = 'Цени от баланса->Разходи';
        }
        if($type == 'stores'){
            $storeListNum = acc_Lists::fetchBySystemId('stores')->num;
            $data->listFilter->setFieldTypeParams('otherItemId', array('lists' => $storeListNum));
            $data->listFilter->showFields = 'balanceId,otherItemId,productItemId,toDate';
        } else {
            $data->listFilter->showFields = 'balanceId,productItemId,toDate';
        }

        $data->listFilter->input();
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->query->orderBy('date', 'DESC');

        if ($rec = $data->listFilter->rec) {
            $data->query->where("#type = '{$rec->type}'");
            if (!empty($rec->productItemId)) {
                $data->query->where("#productItemId = {$rec->productItemId}");
            }
            if (!empty($rec->otherItemId)) {
                $data->query->where("#otherItemId = {$rec->otherItemId}");
            }
            if (!empty($rec->balanceId)) {
                $toDate = acc_Balances::fetchField($rec->balanceId, 'toDate');
                $data->query->where("#date = '{$toDate}'");
            }

            if (!empty($rec->toDate)) {
                redirect(array($mvc, 'filter', 'toDate' => $rec->toDate, 'productItemId' => $rec->productItemId, 'otherItemId' => $rec->otherItemId, 'type' => $rec->type));
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
        $type = Request::get('type', 'varchar');
        $productItemId = Request::get('productItemId', 'int');
        $otherItemId = Request::get('otherItemId', 'int');

        $toDate = empty($toDate) ? dt::today() : $toDate;
        $recs = static::getPricesToDate($toDate, $productItemId, $otherItemId, $type);
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
        $fields = arr::make('date=Дата,otherItemId=Перо,productItemId=Артикул,price=Цена');
        $contentTpl = $table->get($rows, $fields);
        $toDate = dt::mysql2verbal($toDate, 'd.m.Y');
        $contentTpl->prepend(tr("|*<h2>|Към дата|* <span class='green'>{$toDate}</span></h2>"));
        $contentTpl->append($Pager->getHtml());

        return $this->renderWrapping($contentTpl);
    }


    /**
     * Връща последните цени на артикулите към дата
     *
     * @param datetime $toDate
     * @param mixed $productItems
     * @param mixed $otherItems
     * @param string $type
     * @return array $res
     */
    public static function getPricesToDate($toDate, $productItems = null, $otherItems = null, $type = 'stores')
    {
        $dateColName = str::phpToMysqlName('date');
        $storeColName = str::phpToMysqlName('otherItemId');
        $productColName = str::phpToMysqlName('productItemId');
        $priceColName = str::phpToMysqlName('price');
        $typeColName = str::phpToMysqlName('type');

        $me = cls::get(get_called_class());
        $otherWhere = array("`{$me->dbTableName}`.{$typeColName} = '{$type}'");
        if (!empty($productItems)) {
            $productItemsArr = arr::make($productItems);
            $productItemsArr = implode(',', $productItemsArr);
            $otherWhere[] = "`{$me->dbTableName}`.{$productColName} IN ({$productItemsArr})";
        }
        if (!empty($otherItems)) {
            $otherItemsArr = arr::make($otherItems);
            $otherItemsArr = implode(',', $otherItemsArr);
            $otherWhere[] = "`{$me->dbTableName}`.{$storeColName} IN ({$otherItemsArr})";
        }
        $otherWhere = implode(' AND ', $otherWhere);
        if (!empty($otherWhere)) {
            $otherWhere = " AND {$otherWhere}";
        }

        $toDate = $toDate ?? dt::getLastDayOfMonth();
        $query1 = "SELECT * FROM (SELECT `{$me->dbTableName}`.`id` AS `id` , `{$me->dbTableName}`.`{$dateColName}` AS `date` , `{$me->dbTableName}`.`{$storeColName}` AS `otherItemId` , `{$me->dbTableName}`.`{$productColName}` AS `productItemId` , `{$me->dbTableName}`.`{$priceColName}` AS `{$priceColName}` FROM `{$me->dbTableName}` WHERE (`{$me->dbTableName}`.`{$dateColName}` <= '{$toDate}'{$otherWhere} ) ORDER BY `{$me->dbTableName}`.`{$dateColName}` DESC LIMIT 1000000) as temp GROUP BY temp.otherItemId, temp.productItemId";
        $dbTableRes = $me->db->query($query1);

        $res = array();
        while ($arr = $me->db->fetchArray($dbTableRes)) {
            $res["{$arr['otherItemId']}|{$arr['productItemId']}"] = (object)$arr;
        }

        return $res;
    }


    /**
     * Инвалидира данните след
     *
     * @param datetime $date
     * @return void
     */
    public static function invalidateAfterDate($date)
    {
        core_Debug::startTimer('INVALIDATE_ALL');
        $me = cls::get(get_called_class());

        $toDate = dt::getLastDayOfMonth(dt::addMonths(-1, dt::getLastDayOfMonth($date), false));
        $date = $date ?? '000-00-00';

        foreach (array('stores' => 'type,otherItemId,productItemId,date', 'production' => 'type,productItemId,date', 'costs' => 'type,otherItemId,productItemId,date') as $type => $keyFields){

            core_Debug::startTimer("CALC_{$type}");
            $pricesToDate = static::getPricesToDate($toDate, null, null, $type);

            $prevArr = array();
            array_walk($pricesToDate, function($arr, $key) use (&$prevArr) {$prevArr[$key] = $arr->price;});
            $sysId = ($type == 'stores') ? '321' : (($type == 'production') ? '61101' : '60201');

            $res = static::extractDataFromBalance($date, $sysId, null, $prevArr);

            $allRes = array();
            array_walk($res, function($arr) use (&$allRes) {$allRes = array_merge($allRes, $arr);});

            $exQuery = static::getQuery();
            $exQuery->where("#date >= '{$date}' AND #type = '{$type}'");
            $exRecs = $exQuery->fetchAll();
            $synced = arr::syncArrays($allRes, $exRecs, $keyFields, 'price');

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

            core_Debug::stopTimer("CALC_{$type}");
            $time = round(core_Debug::$timers["RENDER_ROWS"]->workingTime, 6);

            static::logDebug("FROM_{$type} TIMER: {$time} '{$date}' TO '{$toDate}'-RES(I{$iCount}:U{$uCount}:D{$dCount})");
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $cronRec = core_Cron::getRecForSystemId('UpdateStockPricesPerPeriod');
        $url = array('core_Cron', 'ProcessRun', str::addHash($cronRec->id), 'forced' => 'yes');

        $data->toolbar->addBtn('Преизчисляване', $url, 'ef_icon=img/16/arrow_refresh.png, title = Преизчисляване');
    }


    /**
     * Обновяване на себестойностите по разписание
     */
    public function cron_UpdateStockPricesPerPeriod()
    {
        $from = dt::addSecs(-3600);

        $bQuery = acc_Balances::getQuery();
        $bQuery->orderBy('fromDate', 'ASC');
        $bQuery->where("#lastCalculate >= '{$from}'");
        $invalidateAfterDate = $bQuery->fetch()->fromDate;

        core_Debug::log("INVALIDATE AFTER {$invalidateAfterDate}");
        if($invalidateAfterDate){
            acc_ProductPricePerPeriods::invalidateAfterDate($invalidateAfterDate);
        }
    }


    /**
     * Екшън за инвалидиране на данните
     */
    function act_Invalidate()
    {
        acc_ProductPricePerPeriods::invalidateAfterDate(null);
    }
}