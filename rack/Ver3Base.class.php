<?php

class rack_Ver3Base
{
    public static function getBaseMeasureId($productId)
    {
        return cat_Products::fetchField($productId, 'measureId');
    }

    public static function qtyPerUnit($productId, $uomOrPackId)
    {
        $baseId = self::getBaseMeasureId($productId);
        if ($uomOrPackId == $baseId) return 1.0;

        // Продуктова опаковка по packagingId
        $pRec = cat_products_Packagings::getPack($productId, $uomOrPackId);
        if (is_object($pRec) && isset($pRec->quantity)) return (float)$pRec->quantity;

        // Като id в cat_products_Packagings
        $byId = cat_products_Packagings::fetch("#id = {$uomOrPackId} AND #productId = {$productId}");
        if (is_object($byId) && isset($byId->quantity)) return (float)$byId->quantity;

        // UoM конверсия (мерки от същия тип)
        $val = cat_UoM::convertValue(1, $uomOrPackId, $baseId);
        if ($val !== false && $val > 0) return (float)$val;

        return 1.0;
    }

    public static function toBase($productId, $uomId, $qty)
    {
        return (float)$qty * self::qtyPerUnit($productId, $uomId);
    }

    /**
     * Агрегира заявките към базова мярка, по продукт/партида/зона.
     * Връща масив от обекти: { productId, batch, zonesBase: [ zoneId => qtyBase ] }.
     */
    public static function aggregateExpectedBase($expected)
    {
        // Количествата в $expected->products->zones са В БАЗОВА МЯРКА!
        $agg = array(); // key "{$productId}|{$batch}" => (object)
        if (!is_object($expected) || !is_array($expected->products)) return $agg;
        
        foreach ($expected->products as $key => $pr) {
            $pid   = (int)$pr->productId;
            $batch = isset($pr->batch) ? $pr->batch : null;
            $k     = "{$pid}|" . ($batch === null ? '' : $batch);
            if (!isset($agg[$k])) {
                $agg[$k] = (object) array(
                    'productId' => $pid,
                    'batch'     => $batch,
                    'zonesBase' => array()
                );
            }
            if (is_array($pr->zones)) {
                foreach ($pr->zones as $zoneId => $baseQty) {
                    if (!isset($agg[$k]->zonesBase[$zoneId])) $agg[$k]->zonesBase[$zoneId] = 0.0;
                    $agg[$k]->zonesBase[$zoneId] += (float)$baseQty;
                }
            }
        }
        return $agg;
    }


    /**
     * Всички валидни опаковки/мерки, сортирани низходящо по базови единици
     * (продуктови опаковки + UoM от същия тип).
     */
    public static function getAllPacksDesc($productId)
    {
        $baseId = self::getBaseMeasureId($productId);
        $packs = array();

        // Базова мярка (fallback)
        $packs["u_{$baseId}"] = (object) array('packagingId' => $baseId, 'quantity' => 1.0);

        // Продуктови опаковки
        $pQuery = cat_products_Packagings::getQuery();
        $pQuery->where("#productId = {$productId} AND #state != 'closed'");
        $pQuery->show('packagingId,quantity');
        while ($pRec = $pQuery->fetch()) {
            $packs["p_{$pRec->packagingId}"] = (object) array(
                'packagingId' => $pRec->packagingId,
                'quantity'    => (float)$pRec->quantity
            );
        }

        // UoM мерки от същия тип
        $same = cat_UoM::getSameTypeMeasures($baseId);
        if (is_array($same)) {
            foreach ($same as $uId => $name) {
                if (!$uId || $uId == $baseId) continue;
                $q = cat_UoM::convertValue(1, $uId, $baseId);
                if ($q !== false && $q > 0) {
                    $packs["u_{$uId}"] = (object) array(
                        'packagingId' => $uId,
                        'quantity'    => (float)$q
                    );
                }
            }
        }

        uasort($packs, function ($a, $b) { return ($b->quantity <=> $a->quantity); });
        return array_values($packs);
    }

    /**
     * Събира заявените опаковки/мерки за даден продукт/партида от текущото $expected.
     * Връща: [packagingId => true].
     */
    public static function collectRequestedPackagings($expected, $productId, $batch)
    {
        $set = array();
        if (!is_object($expected) || !is_array($expected->products)) return $set;

        foreach ($expected->products as $pRec) {
            if ($pRec->productId != $productId) continue;
            if ((string)$pRec->batch !== (string)$batch) continue;
            if (!isset($pRec->packagingId)) continue;
            $set[$pRec->packagingId] = true;
        }
        return $set;
    }



    /**
     * Съвместим alias
     */
    public static function getPackListDesc($productId)
    {
        return static::getAllPacksDesc($productId);
    }




    /**
     * Коя зона какви опаковки е заявила (в БАЗОВА мярка)
     * @return array [zoneId => [packagingId => baseQtyRequested]]
     */
    public static function requestedByZoneBaseMap($expected, $productId, $batch)
    {
        $map = array();
        if (!is_object($expected) || !is_array($expected->products)) return $map;
        foreach ($expected->products as $key => $pr) {
            if ((int)$pr->productId !== (int)$productId) continue;
            $prBatch = isset($pr->batch) ? $pr->batch : null;
            if ($prBatch !== $batch) continue;
            $packId = isset($pr->packagingId) ? (int)$pr->packagingId : null;
            if (!$packId) continue;
            if (!is_array($pr->zones)) continue;
            foreach ($pr->zones as $zoneId => $baseQty) {
                $zoneId = (int)$zoneId;
                $base = (float)$baseQty;
                if ($base <= 0) continue;
                if (!isset($map[$zoneId])) $map[$zoneId] = array();
                if (!isset($map[$zoneId][$packId])) $map[$zoneId][$packId] = 0.0;
                $map[$zoneId][$packId] += $base; // ТУК qty вече е в базова мярка
            }
        }
        return $map;
    }




    /**
     * Разцепва MG3 алокацията (в БАЗОВА мярка) към отделни списъци по опаковки,
     * според заявките на зоните.
     *
     * @param int   $productId
     * @param array $allocatedPalletsBase  масив от обекти: ->position, ->zones (base units per zone)
     * @param array $packsDesc             [packagingId => qtyInBase]
     * @param array $requestedMap          [zoneId => [packagingId => baseQtyRequested]]
     * @return array $__byPack [packagingId => allocatedArr]
     */
    public static function splitAllocatedToPackaged($productId, $allocatedPalletsBase, $packsDesc, $requestedMap)
    {
        $byPack = array();
        $measureId = cat_Products::fetchField($productId, 'measureId'); // дефолтна опаковка (1:1)
        
        if (!is_array($allocatedPalletsBase) || !count($allocatedPalletsBase)) {
            return $byPack;
        }
        
        foreach ($allocatedPalletsBase as $obj) {
            if (!is_object($obj) || !is_array($obj->zones)) continue;
            
            // Готови „под-обекти“ по (packId, position)
            $acc = array(); // key "{$packId}|{$obj->position}" => zones array
            
            foreach ($obj->zones as $zoneId => $baseQty) {
                $zoneId  = (int)$zoneId;
                $remain  = (float)$baseQty;
                if ($remain <= 0) continue;
                
                // Какви опаковки са заявени в тази зона
                $pref = isset($requestedMap[$zoneId]) && is_array($requestedMap[$zoneId]) ? $requestedMap[$zoneId] : array();
                $prefSum = 0.0;
                foreach ($pref as $pid => $bq) { $prefSum += (float)$bq; }
                
                if ($prefSum > 0) {
                    // Пропорционално на заявките по опаковка
                    $firstKey = null;
                    foreach ($pref as $pId => $reqBase) {
                        $pId = (int)$pId;
                        if (!isset($packsDesc[$pId]) && $pId !== (int)$measureId) continue; // игнориране на невалидна опаковка
                        if ($firstKey === null) $firstKey = $pId;
                        $portion = $remain * ((float)$reqBase / $prefSum);
                        // Последната опаковка взема оставащото, за да не губим от закръгляния
                        
                        $key = "{$pId}|{$obj->position}";
                        if (!isset($acc[$key])) $acc[$key] = array();
                        if (!isset($acc[$key][$zoneId])) $acc[$key][$zoneId] = 0.0;
                        $acc[$key][$zoneId] += $portion;
                    }
                } else {
                    // Няма изрично заявена опаковка – слагаме към основната мярка
                    $pId = (int)$measureId;
                    $key = "{$pId}|{$obj->position}";
                    if (!isset($acc[$key])) $acc[$key] = array();
                    if (!isset($acc[$key][$zoneId])) $acc[$key][$zoneId] = 0.0;
                    $acc[$key][$zoneId] += $remain;
                }
            }
            
            // Превръщаме акумулираните зони в обекти
            foreach ($acc as $k => $zones) {
                list($packIdStr, $pos) = explode('|', $k, 2);
                $packId = (int)$packIdStr;
                $clone = clone $obj;
                $clone->zones = $zones;
                $clone->position = $pos;
                $byPack[$packId][] = $clone;
            }
        }
        
        return $byPack;
    }
	

	/**
     * Единствена заявка към rack_Movements за всички нужни:
     *  - продукти от $iterProducts
     *  - зони (ключовете на zonesBase от $iterProducts)
     *  - състояния: active + closed
     * Връща map: "{$productId}|{$batchKey}|{$zoneId}" => sumBaseDone
     * като сумира само движенията, които съдържат "документа на конкретната зона".
     *
     * @param array $iterProducts (от aggregateExpectedBase), с полета: productId, batch, zonesBase
     * @return array
     */
    public static function prefetchDoneBaseForZones(array $iterProducts)
    {
        $zoneToContainer = array();     // zoneId => containerId (само ако има)
        $zoneIds = array();             // всички зони, които ни трябват
        $productIds = array();          // всички продукти
        $batchesPresentNull = false;    // има ли зони с NULL/'' batch
        $batches = array();             // сет от непразни партиди (ако искаш да ползваш филтър по batch)

        foreach ($iterProducts as $pRec) {
            $productIds[(int)$pRec->productId] = (int)$pRec->productId;

            // съберем партиди (по-лесно е да не филтрираме по партида в SQL, но оставям хук)
            if ($pRec->batch !== null && $pRec->batch !== '') {
                $batches[$pRec->batch] = $pRec->batch;
            } else {
                $batchesPresentNull = true;
            }

            if (isset($pRec->zonesBase) && is_array($pRec->zonesBase)) {
                foreach ($pRec->zonesBase as $zId => $need) {
                    $zId = (int)$zId;
                    $zoneIds[$zId] = $zId;

                    if (!array_key_exists($zId, $zoneToContainer)) {
                        $cId = rack_Zones::fetchField($zId, 'containerId');
                        if (!empty($cId)) {
                            $zoneToContainer[$zId] = (int)$cId;
                        }
                    }
                }
            }
        }

        if (!count($zoneToContainer) || !count($productIds)) {
            return array();
        }

        // --- ЕДНА заявка към rack_Movements ---
        $q = rack_Movements::getQuery();
        $q->in('state', array('active', 'closed'));
        $q->in('productId', array_values($productIds));

        // Филтър по зони чрез zoneList (keylist)
        $q->likeKeylist('zoneList', keylist::fromArray($zoneIds));

        // НЕ филтрираме по documents на SQL ниво (за да не правим дълго OR/LOCATE);
        // ще го отсеем в PHP спрямо map-а zoneId => containerId.

        // Ако искаш да стесниш и по партиди, можеш да добавиш:
        // - ако имаме само непразни партиди и няма NULL: $q->in('batch', array_values($batches));
        // - иначе оставяме без where за batch (безопасно и по-просто).

        $q->show('productId,batch,quantityInPack,zones,documents');

        $done = array(); // key "{$pid}|{$batchKey}|{$zoneId}" => sumBase

        while ($m = $q->fetch()) {
            // set от документи на движението
            $docIds = array();
            if (!empty($m->documents)) {
                $docIds = keylist::toArray($m->documents);
            }

            // зони в движението (таблично поле)
            $zones = type_Table::toArray($m->zones);
            if (!is_array($zones) || !count($zones)) continue;

            $pid = (int)$m->productId;
            $batchKey = ($m->batch !== null && $m->batch !== '') ? (string)$m->batch : '';

            foreach ($zones as $z) {
                $zId = (int)$z->zone;
                if (!isset($zoneToContainer[$zId])) continue;         // зона без документ => не я броим
                $cId = $zoneToContainer[$zId];

                // движението „валидно“ ли е за документа на тази зона?
                if (!isset($docIds[$cId])) continue;

                // z->quantity е в брой опаковки; quantityInPack => към базовата мярка
                $sumBase = (float)$z->quantity * (float)$m->quantityInPack;

                // 0 няма смисъл да се сумаризира; отрицателните ТРЯБВА да участват,
                // за да „нулират“ обратните движения в зоната
                if ($sumBase == 0.0) continue;

                $key = "{$pid}|{$batchKey}|{$zId}";
                if (!isset($done[$key])) $done[$key] = 0.0;
                $done[$key] += $sumBase;
            }
        }

        return $done;
    }

    /**
     * Приспада "done" (active+closed) върху $iterProducts в базова мярка,
     * като използва предварително изчислен map от prefetchDoneBaseForZones().
     *
     * @param array $iterProducts (по референция)
     * @return void
     */
    public static function subtractDoneBaseForAllZoneDocsOptimized(array &$iterProducts)
    {
        $pref = self::prefetchDoneBaseForZones($iterProducts);
        if (!count($pref)) return;

        foreach ($iterProducts as &$pRec) {
            if (!isset($pRec->zonesBase) || !is_array($pRec->zonesBase)) continue;

            $pid = (int)$pRec->productId;
            $batchKey = ($pRec->batch !== null && $pRec->batch !== '') ? (string)$pRec->batch : '';

            foreach ($pRec->zonesBase as $zId => $needBase) {
                $zId = (int)$zId;
                if ($needBase <= 0) continue;

                $k = "{$pid}|{$batchKey}|{$zId}";
                if (isset($pref[$k]) && $pref[$k] > 0) {
                    $left = (float)$needBase - (float)$pref[$k];
                    $pRec->zonesBase[$zId] = ($left > 0) ? $left : 0.0;
                }
            }
        }
    }
	
    /**
     * Връща "предпочитаната" опаковка за редакция на движение.
     *
     * Приоритети:
     *  1) Ако има currentZoneId в заявката -> взимаме packagingId от
     *     rack_ZoneDetails за този продукт/партида в тази зона.
     *  2) Иначе: събираме всички зони от движението и избира
     *     най-малката опаковка (по най-малко базови единици).
     *
     * @param stdClass $mRec   Запис от rack_Movements
     * @return int|null        packagingId или NULL
     */
    public static function getPreferredEditPackagingId($mRec)
    {
        if (rack_Setup::get('PICKUP_STRATEGY') != 'ver3') {
            return null;
        }

        if (!is_object($mRec) || empty($mRec->productId)) {
            return null;
        }

        $productId = (int)$mRec->productId;
        $batch     = isset($mRec->batch) ? (string)$mRec->batch : null;

        /*--------------------------------------------------------------
         * 1) Опит: конкретна зона от която идва формата (currentZoneId)
         *------------------------------------------------------------*/
        $zoneId = Request::get('currentZoneId', 'int');

        if ($zoneId) {
            $zd = rack_ZoneDetails::getQuery();
            $zd->where("#zoneId = {$zoneId} AND #productId = {$productId}");

            if ($batch === null || $batch === '') {
                $zd->where("#batch IS NULL OR #batch = ''");
            } else {
                $zd->where(array("#batch = '[#1#]'", $batch));
            }

            // Само заявки с количество, все пак
            $zd->where('#documentQuantity IS NOT NULL AND #documentQuantity <> 0');
            $zd->orderBy('#id', 'DESC');
			$zd->limit(1);
			$zd->show('packagingId');

            if ($dRec = $zd->fetch()) {
                if (!empty($dRec->packagingId)) {
                    return (int)$dRec->packagingId;
                }
            }
        }

        /*--------------------------------------------------------------
         * 2) Фолбек: всички зони от комбинираното движение
         *    -> най-малката опаковка (досегашното поведение)
         *------------------------------------------------------------*/
        $zoneIds = array();

        if (!empty($mRec->zoneList)) {
            $zoneIds = keylist::toArray($mRec->zoneList);
        }

        if (!count($zoneIds) && !empty($mRec->zones)) {
            $zObj = @json_decode($mRec->zones);
            if (is_object($zObj) && isset($zObj->zone) && is_array($zObj->zone)) {
                foreach ($zObj->zone as $zId) {
                    $zId = (int)$zId;
                    if ($zId > 0) {
                        $zoneIds[$zId] = $zId;
                    }
                }
            }
        }

        if (!count($zoneIds)) {
            return null;
        }

        $q = rack_ZoneDetails::getQuery();
        $q->in('zoneId', $zoneIds);
        $q->where("#productId = {$productId}");

        if ($batch === null || $batch === '') {
            $q->where("#batch IS NULL OR #batch = ''");
        } else {
            $q->where(array("#batch = '[#1#]'", $batch));
        }

        $q->where('#documentQuantity IS NOT NULL AND #documentQuantity <> 0');
        $q->show('packagingId');

        $packs = array();   // packagingId => qtyPerUnit (към базова мярка)

        while ($dRec = $q->fetch()) {
            if (empty($dRec->packagingId)) continue;
            $packs[$dRec->packagingId] = self::qtyPerUnit($productId, $dRec->packagingId);
        }

        if (!count($packs)) {
            return null;
        }

        // Най-малката опаковка = тази с най-малко базови единици
        asort($packs, SORT_NUMERIC);
        reset($packs);

        return (int)key($packs);
    }

    /**
     * Нормализира запис от rack_Movements за редакция така, че:
     *  - packagingId да е мярката/опаковката от заявката/документа;
     *  - packQuantity и количествата по зони да са конвертирани към нея;
     *  - реалното количество в базова мярка да остане СЪЩОТО.
     *
     * ВАЖНО: Работи само върху подадения обект ($mRec) – НЕ записва в БД.
     *
     * @param stdClass $mRec  Запис от rack_Movements (по референция!)
     */
    public static function normalizeMovementForEdit(&$mRec)
    {
        // Само за стратегия ver3
        if (rack_Setup::get('PICKUP_STRATEGY') != 'ver3') {
            return;
        }

        if (!is_object($mRec) || empty($mRec->productId)) {
            return;
        }

        // Ако няма никакви зони към движението – няма какво да търсим
        if (empty($mRec->zoneList) && empty($mRec->zones)) {
            return;
        }

        $productId   = (int) $mRec->productId;
        $oldPackId   = isset($mRec->packagingId) ? (int) $mRec->packagingId : 0;
        $preferredId = self::getPreferredEditPackagingId($mRec);

        // Няма по-добра опаковка или съвпада със сегашната
        if (empty($preferredId) || $preferredId == $oldPackId) {
            return;
        }

        // Колко базови единици има в 1 брой от старата и новата мярка
        $oldPer = self::qtyPerUnit($productId, $oldPackId);
        $newPer = self::qtyPerUnit($productId, $preferredId);

        if ($oldPer <= 0 || $newPer <= 0) {
            return;
        }

        // ratio = старите базови / новите базови
        // базовото количество остава същото:
        //   qtyBase = packQty_old * oldPer = (packQty_old * ratio) * newPer
        $ratio = $oldPer / $newPer;

        // Конвертиране на общото количество в опаковки (ако има зададено)
        if (isset($mRec->packQuantity) && $mRec->packQuantity !== null && $mRec->packQuantity !== '') {
            $mRec->packQuantity = (double) $mRec->packQuantity * $ratio;
        }

        // Конвертиране на количествата по зони – работим с JSON като масив
        if (!empty($mRec->zones)) {
            $zArr = @json_decode($mRec->zones, true);
            if (is_array($zArr) && isset($zArr['quantity']) && is_array($zArr['quantity'])) {
                foreach ($zArr['quantity'] as $idx => $q) {
                    $q = (double) $q;
                    $zArr['quantity'][$idx] = $q * $ratio;
                }
                $mRec->zones = json_encode($zArr);
            }
        }

        // Задаваме новата опаковка – quantityInPack ще се преизчисли
        // в on_AfterPrepareEditForm на rack_Movements
        $mRec->packagingId = $preferredId;
    }
}