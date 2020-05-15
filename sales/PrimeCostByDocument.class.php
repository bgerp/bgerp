<?php


/**
 * Модел за делти при продажби
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_PrimeCostByDocument extends core_Manager
{
    /**
     * Себестойности към документ
     */
    public $title = 'Делти в нишки на продажба';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper,plg_AlignDecimals2,plg_Sorting';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'hr_IndicatorsSourceIntf';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin,ceo,debug';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,valior=Вальор,containerId,productId,quantity,sellCost,primeCost,delta,expenses,dealerId,initiatorId,state,isPublic,folderId';
    
    
    /**
     * Работен кеш
     */
    public static $cache = array();
    
    
    /**
     * Работен кеш
     */
    public static $groupNames = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date(smartTime)', 'caption=Вальор,mandatory');
        $this->FLD('detailClassId', 'class(interface=core_ManagerIntf)', 'caption=Детайл,mandatory');
        $this->FLD('detailRecId', 'int', 'caption=Ред от детайл,mandatory, tdClass=leftCol');
        $this->FLD('containerId', 'int', 'caption=Документ,mandatory');
        $this->FLD('productId', 'int', 'caption=Артикул,mandatory, tdClass=productCell leftCol wrap');
        $this->FLD('quantity', 'double', 'caption=Количество,mandatory');
        $this->FLD('sellCost', 'double', 'caption=Цени->Продажна,mandatory');
        $this->FLD('primeCost', 'double', 'caption=Цени->Себестойност,mandatory');
        $this->FNC('delta', 'double', 'caption=Цени->Делта,mandatory');
        $this->FLD('dealerId', 'user', 'caption=Дилър,mandatory');
        $this->FLD('initiatorId', 'user', 'caption=Инициатор,mandatory');
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно,pending=Заявка,closed=Затворено)', 'caption=Статус, input=none');
        $this->FLD('folderId', 'int', 'caption=Папка,tdClass=leftCol');
        $this->FLD('threadId', 'int', 'caption=Нишка,tdClass=leftCol');
        $this->FLD('isPublic', 'enum(no=Частен,yes=Публичен)', 'caption=Публичен');
        $this->FLD('contragentId', 'int', 'caption=Контрагент,tdClass=leftCol');
        $this->FLD('contragentClassId', 'int', 'caption=Контрагент');
        $this->FLD('expenses', 'double', 'caption=Разходи,mandatory');
        
        $this->setDbIndex('productId,containerId');
        $this->setDbIndex('productId');
        $this->setDbIndex('containerId');
        $this->setDbIndex('folderId');
        $this->setDbIndex('valior');
        $this->setDbIndex('detailClassId,detailRecId,productId');
    }
    
    
    /**
     * Изчисляване на цена за опаковка на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_CalcDelta(core_Mvc $mvc, $rec)
    {
        if (isset($rec->primeCost, $rec->sellCost)) {
            $delta = $rec->sellCost - $rec->primeCost;
            $rec->delta = $delta * $rec->quantity;
        }
    }
    
    
    /**
     * Изтрива кешираните записи за документа
     *
     * @param mixed $class
     * @param int   $id
     *
     * @return void
     */
    public static function removeByDoc($class, $id)
    {
        $Class = cls::get($class);
        expect($Detail = cls::get($Class->mainDetail));
        
        $query = $Detail->getQuery();
        $query->where("#{$Detail->masterKey} = {$id}");
        $query->show('id');
        $ids = arr::extractValuesFromArray($query->fetchAll(), 'id');
        if (!countR($ids)) {
            
            return;
        }
        
        $ids = implode(',', $ids);
        self::delete("#detailClassId = {$Detail->getClassId()} AND #detailRecId IN ({$ids})");
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row    Това ще се покаже
     * @param stdClass $rec    Това е записа в машинно представяне
     * @param array    $fields - полета
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->ROW_ATTR['class'] = "state-{$rec->state}";
        
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        try {
             $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
        } catch (core_exception_Expect $e) {
             $row->containerId = "<span class='red'>" . tr('Проблем с показването') . '</span>';
        }
        
        $row->delta = ht::styleIfNegative($row->delta, $rec->delta);
        
        if(isset($rec->folderId)){
            $row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
        }
    }
    
    
    /**
     * Намиране на дилъра и инициатора на документа, данните се кешират
     *
     * @param int $containerId - контейнер на документ
     *
     * @return array
     *               ['dealerId']    - Ид на визитката на дилъра
     *               ['initiatorId'] - Ид на визитката на инициатора
     */
    public static function getDealerAndInitiatorId($containerId)
    {
        // Ако няма кеширани данни
        if (!isset(static::$cache[$containerId])) {
            try {
                $Document = doc_Containers::getDocument($containerId);
            } catch (core_exception_Expect $e) {
                
                return array();
            }
            
            // Кой е първия документ в нишката
            $threadId = $Document->fetchField('threadId');
            $firstDoc = doc_Threads::getFirstDocument($threadId);
            $fields = 'dealerId, folderId';
            if($firstDoc->getInstance()->getField('initiatorId', false)){
                $fields .= ", initiatorId";
            }
            $firstDocRec = $firstDoc->fetch($fields);
            
            // Ако няма дилър това е отговорника на папката ако има права `sales`
            if (empty($firstDocRec->dealerId)) {
                $inCharge = doc_Folders::fetchField($firstDocRec->folderId, 'inCharge');
                if (core_Users::haveRole('sales', $inCharge)) {
                    $firstDocRec->dealerId = $inCharge;
                }
            }
            
            // Ид-та на визитките на дилъра и инициатора
            $dealerId = $initiatorId = null;
            if (isset($firstDocRec->dealerId)) {
                $dealerId = $firstDocRec->dealerId;
            }
            
            if (isset($firstDocRec->initiatorId)) {
                $initiatorId = $firstDocRec->initiatorId;
            }
            
            // Кеширане на дилъра и инициатора на документа
            static::$cache[$containerId] = array('dealerId' => $dealerId, 'initiatorId' => $initiatorId);
        }
        
        // Връщане на кешираните данни
        return static::$cache[$containerId];
    }
    
    
    /**
     * Заявката към записите от модела, чиито документи са променяни след $timeline
     *
     * @param datetime $timeline - времева линия след който ще се филтрират документите
     * @param array    $masters  - помощен масив
     *
     * @return core_Query $iQuery - подготвената заявка
     */
    public static function getIndicatorQuery($timeline, &$masters)
    {
        $iQuery = self::getQuery();
        
        // Кои документи ще се проверяват дали са променяни
        $documents = array('sales_Sales' => 'sales_SalesDetails', 'sales_Services' => 'sales_ServicesDetails', 'purchase_Services' => 'purchase_ServicesDetails', 'store_ShipmentOrders' => 'store_ShipmentOrderDetails', 'store_Receipts' => 'store_ReceiptDetails');
        $or = false;
        
        $masters = array();
        
        // Обхождане на всички документи за продажба
        foreach ($documents as $Master => $Detail) {
            $Detail = cls::get($Detail);
            
            // За всеки документ, му извличаме детайлите ако той е променян след $timeline
            $dQuery = $Detail->getQuery();
            $dQuery->EXT('state', $Master, "externalName=state,externalKey={$Detail->masterKey}");
            $dQuery->EXT('containerId', $Master, "externalName=containerId,externalKey={$Detail->masterKey}");
            $dQuery->EXT('modifiedOn', $Master, "externalName=modifiedOn,externalKey={$Detail->masterKey}");
            
            $dQuery->where("#modifiedOn >= '{$timeline}'");
            $dQuery->where("#state != 'draft' AND #state != 'pending' AND #state != 'stopped'");
            
            $fields = 'modifiedOn,state,containerId,amountDiscount';
            if ($Master != 'sales_Sales') {
                $dQuery->EXT('isReverse', $Master, "externalName=isReverse,externalKey={$Detail->masterKey}");
                $dQuery->EXT('amountDelivered', $Master, "externalName=amountDelivered,externalKey={$Detail->masterKey}");
                $dQuery->EXT('amountDeliveredVat', $Master, "externalName=amountDeliveredVat,externalKey={$Detail->masterKey}");
                $dQuery->EXT('amountDiscount', $Master, "externalName=amountDiscount,externalKey={$Detail->masterKey}");
                $fields .= ',isReverse,amountDeliveredVat,amountDelivered';
            } else {
                $dQuery->EXT('amountDeal', $Master, "externalName=amountDeal,externalKey={$Detail->masterKey}");
                $dQuery->EXT('amountVat', $Master, "externalName=amountVat,externalKey={$Detail->masterKey}");
                $dQuery->EXT('amountDiscount', $Master, "externalName=amountDiscount,externalKey={$Detail->masterKey}");
                $fields .= ',amountVat,amountDeal';
            }
            
            $ids = array();
            $dQuery->show($fields);
            
            // Извличане на ид-та на детайлите му и кеш на мастър данните
            while ($dRec = $dQuery->fetch()) {
                if (!isset($masters[$dRec->containerId])) {
                    try {
                        $Document = doc_Containers::getDocument($dRec->containerId);
                        $masters[$dRec->containerId] = array($Document, $dRec->state, $dRec->isReverse);
                        
                        if($Document->isInstanceOf('sales_Sales')){
                            $masters[$dRec->containerId]['total'] = $dRec->amountDeal - $dRec->amountDiscount;
                        } else {
                            $masters[$dRec->containerId]['total'] = $dRec->amountDelivered  - $dRec->amountDiscount;
                        }
                    } catch (core_exception_Expect $e) {
                        reportException($e);
                        continue;
                    }
                }
                
                $ids[$dRec->id] = $dRec->id;
            }
            
            // Ако има детайли от модела ще търсим точно записите от детайли на документи променяни след timeline
            if (countR($ids)) {
                $ids = implode(',', $ids);
                $iQuery->where("#detailClassId = {$Detail->getClassId()} AND #detailRecId IN (${ids})", $or);
                $or = true;
            }
        }
        
        // Добаване и на ПОС отчетите
        $posIds = array();
        $posQuery = pos_Reports::getQuery();
        $posQuery->where("#modifiedOn >= '{$timeline}'");
        $posQuery->show('modifiedOn,state,containerId,details,total');
        while ($pRec = $posQuery->fetch()) {
            $masters[$pRec->containerId] = array(doc_Containers::getDocument($pRec->containerId), $pRec->state, null);
            $masters[$pRec->containerId]['total'] = $pRec->total;
            
            foreach ($pRec->details['receiptDetails'] as $pdRec){
                if($pdRec->action != 'sale') continue;
                $key = "{$pRec->id}000{$pdRec->value}";
                $posIds[$key] = $key;
            }
        }
        
        if (countR($posIds)) {
            $posIds = implode(',', $posIds);
            $iQuery->where("#detailClassId = " . pos_Reports::getClassId() . " AND #detailRecId IN ($posIds)", $or);
        }
        
        // Връщане на готовата заявка
        return $iQuery;
    }
    
    
    /**
     * Връща индикаторите за делта на търговеца и инициаторът
     *
     * @param array $indicatorRecs - филтрираните записи
     * @param array $masters       - помощен масив
     * @param array $personIds     - масив с ид-та на визитките на дилърите
     *
     * @return array $result       - @see hr_IndicatorsSourceIntf::getIndicatorValues($timeline)
     */
    private static function getDeltaIndicators($indicatorRecs, $masters, &$personIds)
    {
        $result = $personIds = array();
        if (!countR($indicatorRecs)) {
            
            return $result;
        }
        
        $deltaId = hr_IndicatorNames::force('Delta', __CLASS__, 1)->id;
        $deltaIId = hr_IndicatorNames::force('DeltaI', __CLASS__, 2)->id;
        
        foreach ($indicatorRecs as $rec) {
            
            // Намиране на дилъра, инициатора и взимане на данните на мастъра на детайла
            $Document = $masters[$rec->containerId][0];
            
            // За дилъра и инициатора, ако има ще се подават делтите
            foreach (array('dealerId', 'initiatorId') as $personFld) {
                if (!isset($rec->{$personFld})) {
                    continue;
                }
                
                // Намиране на визитката на потребителя
                if (!isset($personIds[$rec->{$personFld}])) {
                    $personIds[$rec->{$personFld}] = crm_Profiles::fetchField("#userId = '{$rec->{$personFld}}'", 'personId');
                }
                
                $personFldValue = $personIds[$rec->{$personFld}];
                $indicatorId = ($personFld == 'dealerId') ? $deltaId : $deltaIId;
                
                // Ключа по който ще събираме е лицето, документа и вальора
                $key = "{$personFldValue}|{$Document->getClassId()}|{$Document->that}|{$rec->valior}|{$indicatorId}";
                
                // Ако документа е обратен
                $sign = ($masters[$rec->containerId][2] == 'yes') ? -1 : 1;
                $delta = round($sign * $rec->delta, 2);
                $delta = self::addSurchargeToDelta($delta, $rec->productId);
                
                // Ако няма данни, добавят се
                if (!array_key_exists($key, $result)) {
                    $result[$key] = (object) array('date' => $rec->valior,
                        'personId' => $personFldValue,
                        'docId' => $Document->that,
                        'docClass' => $Document->getClassId(),
                        'indicatorId' => $indicatorId,
                        'value' => $delta,
                        'isRejected' => ($masters[$rec->containerId][1] == 'rejected'),);
                } else {
                     
                     // Ако има вече се сумират
                    $ref = &$result[$key];
                    $ref->value += $delta;
                }
            }
        }
        
        // Връщане на записите
        return $result;
    }
    
    
    /**
     * Добавя надценка от артикула към делтата
     *
     * @param float $delta
     * @param int   $productId
     *
     * @return float $delta
     */
    public static function addSurchargeToDelta($delta, $productId)
    {
        $surcharge = 1;
        if ($Driver = cat_Products::getDriver($productId)) {
            $surcharge = $Driver->getDeltaSurcharge($productId);
        }
        $delta = $delta * $surcharge;
        
        return $delta;
    }
    
    
    /**
     * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал
     *
     * @param datetime $timeline - Времето, след което да се вземат всички модифицирани/създадени записи
     *
     * @return array $result  - масив с обекти
     *
     * 			o date        - дата на стайноста
     * 		    o personId    - ид на лицето
     *          o docId       - ид на документа
     *          o docClass    - клас ид на документа
     *          o indicatorId - ид на индикатора
     *          o value       - стойноста на индикатора
     *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от индикаторите
     */
    public static function getIndicatorValues($timeline)
    {
        $result = $masters = array();
        
        // Подготовка на заявката
        $iQuery = self::getIndicatorQuery($timeline, $masters);
        
        // Ако не е намерен променен документ, връща се празен масив
        $wh = $iQuery->getWhereAndHaving();
        if (empty($wh->w)) {
            
            return array();
        }
        
        // Всички записи
        $indicatorRecs = $iQuery->fetchAll();
        core_App::setTimeLimit(countR($indicatorRecs) * 0.8);
        
        
        // Ако няма делта се пропуска
        foreach ($indicatorRecs as $k => $r2) {
            if (!isset($r2->delta)) {
                unset($indicatorRecs[$k]);
            }
        }
        
        if ($dPercent = sales_Setup::get('DELTA_MIN_PERCENT')) {
            foreach ($indicatorRecs as &$r1) {
                if (isset($r1->delta)) {
                    $r1->delta = max($r1->delta, ($r1->sellCost * $r1->quantity * $dPercent));
                }
            }
        }
        
        // Връщане на индикаторите за делта на търговеца и инициатора
        $result1 = self::getDeltaIndicators($indicatorRecs, $masters, $personIds);
        if (countR($result1)) {
            $result = array_merge($result1, $result);
        }
        
        // Връщане на индикаторите за сумата на продадените артикули по групи
        $result2 = self::getProductGroupIndicators($indicatorRecs, $masters, $personIds);
        if (countR($result2)) {
            $result = array_merge($result2, $result);
        }
        
        // Връщане на всички индикатори
        return $result;
    }
    
    
    /**
     * Връща индикаторите за сумата на продадените артикули по групи
     *
     * @param array $indicatorRecs - филтрираните записи
     * @param array $masters       - помощен масив
     * @param array $personIds     - масив с ид-та на визитките на дилърите
     *
     * @return array $result       - @see hr_IndicatorsSourceIntf::getIndicatorValues($timeline)
     */
    private static function getProductGroupIndicators($indicatorRecs, $masters, $personIds)
    {
        $result = array();
        if (!countR($indicatorRecs)) {
            
            return $result;
        }
        
        $selectedGroups = self::cacheGroupNames();
        if (!countR($selectedGroups)) {
            
            return $result;
        }
        
        $productGroups = self::getAllProductGroups($indicatorRecs);
        
        $groupSumId = hr_IndicatorNames::force('GroupSum', __CLASS__, 3)->id;
        $noGroupSumId = hr_IndicatorNames::force('NoGroupSum', __CLASS__, 4)->id;
        
        // За всеки запис
        foreach ($indicatorRecs as $rec) {
            if (!$rec->dealerId) {
                continue;
            }
            
            // Намира се в колко от търсените групи участва
            $groups = $productGroups[$rec->productId];
            $diff = array_intersect_key($selectedGroups, $groups);
            $delimiter = countR($diff);
            
            $Document = $masters[$rec->containerId][0];
            $personFldValue = $personIds[$rec->dealerId];
            $isRejected = ($masters[$rec->containerId][1] == 'rejected');
            $sign = ($masters[$rec->containerId][2] == 'yes') ? -1 : 1;
            
            
            if (!empty($delimiter)) {
                foreach ($diff as $groupId => $obj) {
                    // Сумата е X / броя на групите в които се среща от тези, които се следят
                    $indicatorId = $selectedGroups[$groupId]->groupRec->id;
                    $value = $sign * (round(($rec->quantity * $rec->sellCost) / $delimiter, 2));
                    hr_Indicators::addIndicatorToArray($result, $rec->valior, $personFldValue, $Document->that, $Document->getClassId(), $indicatorId, $value, $isRejected);
                    
                    // Индикатор за делта по групите
                    $indicatorDeltaId = $selectedGroups[$groupId]->deltaRec->id;
                    $delta = $sign * (round($rec->delta / $delimiter, 2));
                    $delta = self::addSurchargeToDelta($delta, $rec->productId);
                    
                    hr_Indicators::addIndicatorToArray($result, $rec->valior, $personFldValue, $Document->that, $Document->getClassId(), $indicatorDeltaId, $delta, $isRejected);
                    
                    // Сумиране по индикатор на общата сума на групите
                    hr_Indicators::addIndicatorToArray($result, $rec->valior, $personFldValue, $Document->that, $Document->getClassId(), $groupSumId, $value, $isRejected);
                }
            } else {
                
                // Сумиране на индикатор без група
                $value = $sign * (round(($rec->quantity * $rec->sellCost), 2));
                hr_Indicators::addIndicatorToArray($result, $rec->valior, $personFldValue, $Document->that, $Document->getClassId(), $noGroupSumId, $value, $isRejected);
            }
        }
        
        // Връщане на индикаторите
        return $result;
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        $result = array();
        
        // Индикатор за делта на търговеца
        $rec = hr_IndicatorNames::force('Delta', __CLASS__, 1);
        $result[$rec->id] = $rec->name;
        
        // Индикатор за делта на инициатора
        $rec = hr_IndicatorNames::force('DeltaI', __CLASS__, 2);
        $result[$rec->id] = $rec->name;
        
        // Индикатори за избраните артикулни групи
        $groupNames = self::cacheGroupNames();
        if (countR($groupNames)) {
            foreach ($groupNames as $indRec) {
                $result[$indRec->groupRec->id] = $indRec->groupRec->name;
                $result[$indRec->deltaRec->id] = $indRec->deltaRec->name;
            }
            
            $rec = hr_IndicatorNames::force('GroupSum', __CLASS__, 3);
            $result[$rec->id] = $rec->name;
            
            $rec = hr_IndicatorNames::force('NoGroupSum', __CLASS__, 4);
            $result[$rec->id] = $rec->name;
        }
        
        // Връщане на всички индикатори
        return $result;
    }
    
    
    /**
     * Връща и кешира имената на груповите индикатори
     *
     * @return array
     */
    private static function cacheGroupNames()
    {
        if (!countR(self::$groupNames)) {
            
            // Ако има селектирани групи
            $selectedGroups = sales_Setup::get('DELTA_CAT_GROUPS');
            $selectedGroups = keylist::toArray($selectedGroups);
            if (countR($selectedGroups)) {
                
                // Форсират им се индикатори
                foreach ($selectedGroups as $groupId) {
                    $groupName = cat_Groups::getVerbal($groupId, 'name');
                    $rec = hr_IndicatorNames::force($groupName, __CLASS__, "group{$groupId}");
                    
                    $groupName2 = 'Delta ' . $groupName;
                    $rec2 = hr_IndicatorNames::force($groupName2, __CLASS__, "dgroup{$groupId}");
                    self::$groupNames[$groupId] = (object) array('groupRec' => $rec, 'deltaRec' => $rec2);
                }
            }
        }
        
        // Връщане на кешираните групи
        return self::$groupNames;
    }
    
    
    /**
     * Помощна ф-я връщаща всички  групи на артикулите
     *
     * @param array $indicatorRecs
     *
     * @return array $groups
     */
    private static function getAllProductGroups($indicatorRecs)
    {
        $groups = array();
        if (!countR($indicatorRecs)) {
            
            return $groups;
        }
        
        // Извличане на всички артикули от записите
        $productArr = arr::extractValuesFromArray($indicatorRecs, 'productId');
        $pQuery = cat_Products::getQuery();
        $pQuery->show('groups');
        $pQuery->in('id', $productArr);
        while ($pRec = $pQuery->fetch()) {
            $groups[$pRec->id] = keylist::toArray($pRec->groups);
        }
        
        return $groups;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('documentId', 'varchar', 'caption=Документ или контейнер, silent');
        $data->listFilter->showFields = 'documentId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input(null, 'silent');
        $data->listFilter->input();
        $data->query->orderBy('valior', 'DESC');
        
        if ($rec = $data->listFilter->rec) {
            if (!empty($rec->documentId)) {
                
                // Търсене и на последващите документи
                if ($document = doc_Containers::getDocumentByHandle($rec->documentId)) {
                    $in = array($document->fetchField('containerId'));
                    if ($document->isInstanceOf('sales_Sales')) {
                        $descendants = $document->getDescendants();
                        $descendantArr = array_values(array_map(function ($obj) {
                            
                            return $obj->fetchField('containerId');
                        }, $descendants));
                        $in = array_merge($in, $descendantArr);
                    }
                    
                    $data->query->in('containerId', $in);
                } elseif(type_Int::isInt($rec->documentId)){
                    $data->query->where("#containerId = {$rec->documentId}");
                }
            }
        }
    }
    
    
    /**
     * Обновява дилърите и инциаторите на подадените документи
     *
     * @param array $containerIds
     *
     * @return void
     */
    public static function updatePersons($containerIds)
    {
        $containerIds = arr::make($containerIds);
        if (!countR($containerIds)) {
            
            return;
        }
        
        $touchedDocuments = array();
        $query = self::getQuery();
        $query->in('containerId', $containerIds);
        while ($rec = $query->fetch()) {
            $persons = self::getDealerAndInitiatorId($rec->containerId);
            if ($rec->dealerId != $persons['dealerId'] || $rec->initiatorId != $persons['initiatorId']) {
                $rec->dealerId = $persons['dealerId'];
                $rec->initiatorId = $persons['initiatorId'];
                self::save($rec);
                
                if(!array_key_exists($rec->containerId, $touchedDocuments)){
                    $touchedDocuments[$rec->containerId] = doc_Containers::getDocument($rec->containerId);
                }
            }
        }
        
        // Нотифициране на контейнерите че са променени делтите
        foreach ($touchedDocuments as $doc){
            $doc->touchRec();
            $doc->getInstance()->logInAct('Сменен дилър и/или инициатор на делтата', $doc->that);
        }
    }
    
    
    /**
     * Колко е себестойноста на продажбата
     *
     * @param int      $productId
     * @param int      $packagingId
     * @param float    $quantity
     * @param stdClass $saleRec
     * @param int      $deltaListId
     *
     * @return NULL|float $primeCost
     */
    public static function getPrimeCostInSale($productId, $packagingId, $quantity, $saleRec, $deltaListId)
    {
        $productRec = cat_Products::fetchField($productId, 'isPublic,code');
        
        // Ако има зададена политика за делта, връща се цената по нея
        if(isset($deltaListId)){
            $primeCost = price_ListRules::getPrice($deltaListId, $productId, $packagingId, $saleRec->valior);
            
            return $primeCost;
        }
        
        $primeCost = cat_Products::getPrimeCost($productId, $packagingId, $quantity, $saleRec->valior, price_ListRules::PRICE_LIST_COST);
        if (isset($primeCost)) {
            $costs = sales_Sales::getCalcedTransports($saleRec->threadId);
            if (isset($costs[$productId])) {
                $primeCost += $costs[$productId]->fee / $costs[$productId]->quantity;
            }
        }
        
        // Ако артикулът е 'Надценка' няма себестойност
        if ($productRec->code == 'surcharge') {
            $primeCost = 0;
        }
        
        return $primeCost;
    }
    
    
    /**
     * Колко е себестойноста на документа според продажбата към която е
     *
     * @param int      $productId
     * @param int      $packagingId
     * @param float    $quantity
     * @param int      $containerId
     * @param int|NULL $deltaListId
     *
     * @return NULL|float
     */
    public static function getPrimeCostFromSale($productId, $packagingId, $quantity, $containerId, $deltaListId = null)
    {
        $threadId = doc_Containers::fetchField($containerId, 'threadId');
        if (empty($threadId)) {
            
            return;
        }
        
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        if (!$firstDoc->isInstanceOf('sales_Sales')) {
            
            return;
        }
        
        $containerId = $firstDoc->fetchField('containerId');
        $query = self::getQuery();
        $query->where("#productId = {$productId} AND #containerId = {$containerId}");
        $query->where("#primeCost IS NOT NULL");
        $query->show('quantity,primeCost');
        $sum = $totalQ = 0;
        
        while ($rec = $query->fetch()) {
            $sum += $rec->quantity * $rec->primeCost;
            $totalQ += $rec->quantity;
        }
       
        // Сумата на себестойноста е среднопритеглената себестойност
        if ($totalQ) {
            
            return $sum / $totalQ;
        }
        
        if (isset($deltaListId)) {
            
            return self::getPrimeCostInSale($productId, $packagingId, $quantity, $firstDoc->fetch(), $deltaListId);
        }
    }
    
    
    /**
     * Дали цената е под себестойноста на артикула в продажбата
     * 
     * @param double $price
     * @param int $productId
     * @param int $packagingId
     * @param double $quantity
     * @param int $containerId
     * @param datetime $valior
     * @return double $primeCost
     */
    public static function isPriceBellowPrimeCost($price, $productId, $packagingId, $quantity, $containerId, $valior)
    {
        $primeCost = self::getPrimeCostFromSale($productId, $packagingId, $quantity, $containerId);
       
        if(empty($primeCost)){
            $primeCost = cat_Products::getPrimeCost($productId, $packagingId, $quantity, $valior);
        }
        
        return (round($price, 4) < round($primeCost, 4));
    }
    
    
    /**
     * Помощна ф-я връщаща дилъра и инциатора за артикула от обединения договор с най-голямо количество
     * 
     * @param int $productId
     * @param mixed $closedSales
     * @return array|null
     */
    public static function getDealerAndInitiatorFromCombinedDeals($productId, $closedSales)
    {
        $quantities = array();
        
        // Търсене в обединените договори, където се среща количеството
        $closedSales = keylist::toArray($closedSales);
        foreach ($closedSales as $saleId){
            $dQuery = sales_SalesDetails::getQuery();
            $dQuery->XPR('sum', 'double', 'SUM(#quantity)');
            $dQuery->where("#saleId = {$saleId} AND #productId = {$productId}");
            if($sum = $dQuery->fetch()->sum){
                $quantities[$saleId] = $sum;
            }
        }
        
        // Сортира се по най-голямо количество
        if(!countR($quantities)) return null;
        arsort($quantities);
        $quantities = array_keys($quantities);
        
        if(isset($quantities[0])){
            $containerId = sales_Sales::fetchField($quantities[0], 'containerId');
            $persons = sales_PrimeCostByDocument::getDealerAndInitiatorId($containerId);
            
            return $persons;
        }
    }
}
