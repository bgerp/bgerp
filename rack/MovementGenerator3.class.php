<?php

/**
 * Генератор на движения в палетния склад
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov, Stefan Arsov
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_MovementGenerator3 extends core_Manager
{
    public $loadList = 'rack_Wrapper';
    public $title = 'Генератор на движения';
    const ALMOST_FULL = 0.85;
    public static $firstRowTo = array();

    /** Екшън за тест */
    public function act_Default()
    {
        requireRole('debug');

        $form = cls::get('core_Form');
        $form->FLD('pallets', 'table(columns=pallet|quantity|createdOn|sysNo,captions=Палет|Количество|Създаване|Системен №,widths=8em|8em|8em|7em)', 'caption=Палети,mandatory');
        $form->FLD('zones', 'table(columns=zone|quantity,captions=Зона|Количество,widths=8em|8em)', 'caption=Зони,mandatory');
        $form->FLD('packagings', 'table(columns=packagingId|quantity,captions=Опаковка|Количество,widths=8em|8em)', 'caption=Опаковки,mandatory');
        $packOptions = array('' => '') + cat_UoM::getPackagingOptions() + cat_UoM::getUomOptions();
        $createdOnOpt = array('' => '') + arr::make([dt::addDays(-4), dt::addDays(-3), dt::addDays(-2), dt::addDays(-1), dt::addDays(1), dt::now()], true);
        $form->setFieldTypeParams('packagings', array('packagingId_opt' => $packOptions));
        $form->setFieldTypeParams('pallets', array('createdOn_opt' => $createdOnOpt));
        $form->toolbar = cls::get('core_Toolbar');
        $form->toolbar->addSbBtn('Изпрати');

        $rec = $form->input();
        $p = $q = $packs = array();
        $mArr = array();

        if ($form->isSubmitted()) {
            $pArr = json_decode($rec->pallets);
            $qArr = json_decode($rec->zones);
            $packArr = json_decode($rec->packagings);

            foreach ($pArr->pallet as $i => $key) {
                if ($pArr->quantity[$i]) {
                    $qVerbal = core_Type::getByName('double')->fromVerbal($pArr->quantity[$i]);
                    $po = (object) ['position' => $key, 'quantity' => $qVerbal, 'createdOn' => $pArr->createdOn[$i] ?? null];
                    if (!empty($pArr->sysNo[$i])) $po->sysNo = (int)$pArr->sysNo[$i];
                    $p[] = $po;
                }
            }
            foreach ($qArr->zone as $i => $key) {
                if ($qArr->quantity[$i]) {
                    $qVerbal = core_Type::getByName('double')->fromVerbal($qArr->quantity[$i]);
                    $q[$key] = $qVerbal;
                }
            }
            foreach ($packArr->packagingId as $i => $key) {
                if ($packArr->quantity[$i]) $packs[] = (object)['packagingId' => $key, 'quantity' => $packArr->quantity[$i]];
            }

            $storeId = Mode::get('pickupStoreId') ?: store_Stores::getCurrent();
            $mArr = self::mainP2Q($p, $q, $packs, 0, 0, $storeId, null);
        }

        $form->title = 'Генериране на движения по палети';
        $html = $form->renderHtml();

        if (countR($p)) $html .= '<h2>Палети</h2>' . ht::mixedToHtml($p);
        if (countR($q)) $html .= '<h2>Зони</h2>' . ht::mixedToHtml($q);
        if (countR($mArr)) $html .= '<h2>Движения</h2>' . ht::mixedToHtml($mArr, 5, 7);

        return $this->renderWrapping($html);
    }

    /**
     * Главен алгоритъм за генериране на движения
     */
    public static function mainP2Q($pallets, $zones, $packaging = [], $volume = null, $weight = null, $storeId = null, $preferOldest = null)
    {
        $sid = $storeId ?: store_Stores::getCurrent();
        $strategy = self::getFullPalletStrategy($sid);
        $sumZ = array_sum($zones);
        $scale = 1;
        if ($scale > 1000000) return false;

        foreach ($zones as $zI => $zQ) {
            $zones[$zI] = self::ffix($zones[$zI] * $scale);
            if ($zones[$zI] <= 0) unset($zones[$zI]);
        }

        asort($packaging);
        $palletId = cat_UoM::fetchBySysId('pallet')->id;
        $qInPallet = self::computeFullPalletSize($pallets, $packaging, $palletId);

        $packArr = [];
        foreach ($packaging as $pack) {
            $k = $pack->quantity * $scale;
            $packArr["{$k}"] = $pack->packagingId;
        }
        krsort($packArr);

        Mode::push('pickupStoreId', $storeId);

        $sumP = 0;
        $pArr = [];
        foreach ($pallets as $id => $p) {
            if ($p->quantity > 0) {
                $pArr[$id] = self::ffix($p->quantity * $scale);
                $sumP += $pArr[$id];
            }
            $pallets[$id]->_rowCol = self::getRowCol($p->position);
            $pallets[$id]->_isFirstRow = self::isFirstRow($p->position);
            $pallets[$id]->_ageDays = isset($p->createdOn) ? dt::daysBetween(dt::now(), $p->createdOn) : 0;
        }
        self::ensureOldestOrdinal($pallets);

        asort($zones);
        if ($sumZ > $sumP) {
            foreach ($zones as $zI => $zQ) {
                $sumP -= $zQ;
                if ($sumP < 0) {
                    $zones[$zI] = self::ffix($zones[$zI] + $sumP);
                    if ($zones[$zI] <= 0) unset($zones[$zI]);
                    $sumP = 0;
                }
            }
        }

        $res = [];

        // ===================== ЕТАП B (ПЪРВО): остатъци под цял палет =====================
        if ($qInPallet > 0) {

            // 1) Групиране на зоните по групи от зони
            $zonesGrouped = self::groupZonesByZoneGroup($zones);
            $zonesAfterB  = array(); // тук натрупваме реалните остатъци по зони след обработка на всички групи

            foreach ($zonesGrouped as $groupId => $groupInfo) {

                $zonesInGroup = $groupInfo['zones']; // [zoneId => qty]
                $groupTotal   = self::ffix($groupInfo['sum']);
                if ($groupTotal <= 0) continue;

                // Жив „остатък“ по подзоните – намалява при всяко вземане
                $groupRemZones = $zonesInGroup;

                // Локален алокатор: разпределя $take по подзоните според оставащите им нужди
                $allocToZones = function ($take) use (&$groupRemZones) {
                    $assigned = array();
                    $rem = rack_MovementGenerator3::ffix($take);
                    foreach ($groupRemZones as $zId => $zNeed) {
                        if ($rem <= 0) break;
                        $zNeed = rack_MovementGenerator3::ffix($zNeed);
                        if ($zNeed <= 0) continue;
                        $put = min($zNeed, $rem);
                        $assigned[$zId] = rack_MovementGenerator3::ffix($put);
                        $groupRemZones[$zId] = rack_MovementGenerator3::ffix($zNeed - $put);
                        $rem = rack_MovementGenerator3::ffix($rem - $put);
                    }
                    return $assigned;
                };

                // 2) За групата: колко цели палети + остатък?
                list($groupFullCnt, $groupRem) = self::qtyDivRem($groupTotal, $qInPallet);

                // 2.1) Първо покриваме ЦЕЛИТЕ ПАЛЕТИ за групата по стратегия
                while ($groupFullCnt > 0) {
                    // кандидати – палети с qty >= qInPallet
                    $fullIdxNow = array();
                    foreach ($pArr as $pId => $pQ) {
                        if ($pQ >= $qInPallet) $fullIdxNow[] = $pId;
                    }
                    if (empty($fullIdxNow)) break;

                    $deprioFirstRow = ($strategy !== 'oldest');
                    usort($fullIdxNow, function ($a, $b) use ($pallets, $strategy, $deprioFirstRow) {
                        return rack_MovementGenerator3::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, $deprioFirstRow);
                    });

                    $pId = $fullIdxNow[0];
                    $availFull = floor($pArr[$pId] / $qInPallet);
                    if ($availFull <= 0) break;

                    $takeFull = min($availFull, $groupFullCnt);
                    $takeQty  = self::ffix($takeFull * $qInPallet);

                    $zonesSplit = $allocToZones($takeQty);
                    if (!empty($zonesSplit)) {
                        self::appendMove($res, $pallets[$pId]->position, $takeQty, $zonesSplit, $pArr, 'B.full_for_group');
                    }

                    $pArr[$pId] = self::ffix($pArr[$pId] - $takeQty);
                    $groupFullCnt -= $takeFull;
                    $groupTotal = self::ffix($groupTotal - $takeQty);
                }

                // 2.2) Остатък след вземането на целите палети
                $remaining = self::ffix($groupTotal);
                if ($remaining <= 0) {
                    // Нищо не остава за тази група
                    foreach ($groupRemZones as $zId => $remQty) $zonesAfterB[$zId] = 0.0;
                    continue;
                }

                // --- Правила 3.2.2 – 3.2.6 ---
                $brokenIdx   = array(); // палети < qInPallet
                $firstRowIdx = array(); // палети на „Първи ред до“
                $fullIdxNow  = array(); // текущи цели палети

                foreach ($pArr as $pId => $pQ) {
                    if ($pQ <= 0) continue;
                    if ($pQ >= $qInPallet) $fullIdxNow[] = $pId; else $brokenIdx[] = $pId;
                    if ($pallets[$pId]->_isFirstRow) $firstRowIdx[] = $pId;
                }

                // 3.2.4 – само „Първи ред до“, ако могат да покрият целия остатък
                $frSum = 0.0; foreach ($firstRowIdx as $frId) $frSum += self::ffix($pArr[$frId]);
                if ($frSum >= $remaining) {
                    usort($firstRowIdx, function ($a, $b) use ($pallets, $strategy) {
                        return rack_MovementGenerator3::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, false);
                    });
                    foreach ($firstRowIdx as $frId) {
                        if ($remaining <= 0) break;
                        $q = self::ffix($pArr[$frId]); if ($q <= 0) continue;
                        $take = min($q, $remaining);
                        $zonesSplit = $allocToZones($take);
                        if (!empty($zonesSplit)) {
                            self::appendMove($res, $pallets[$frId]->position, $take, $zonesSplit, $pArr, 'B.fr_cover');
                        }
                        $pArr[$frId] = self::ffix($pArr[$frId] - $take);
                        $remaining   = self::ffix($remaining - $take);
                    }
                    // записваме остатъците за тази група
                    foreach ($groupRemZones as $zId => $remQty) {
                        $zonesAfterB[$zId] = isset($zonesAfterB[$zId]) ? self::ffix($zonesAfterB[$zId] + $remQty) : self::ffix($remQty);
                    }
                    continue;
                }

                // 3.2.2 – Разбутан (<rem) извън първи ред + първи ред до, ако общо покриват
                $bestUnder = null; $bestUnderQty = 0.0;
                foreach ($brokenIdx as $pid) {
                    if ($pallets[$pid]->_isFirstRow) continue;
                    $q = self::ffix($pArr[$pid]);
                    if ($q <= 0 || $q >= $remaining) continue;
                    if ($q > $bestUnderQty) { $bestUnderQty = $q; $bestUnder = $pid; }
                }
                if ($bestUnder !== null) {
                    $frSum = 0.0; foreach ($firstRowIdx as $frId) $frSum += self::ffix($pArr[$frId]);
                    if ($frSum + $bestUnderQty >= $remaining) {
                        // взимаме целия „под-остатък“ от разбутан извън „Първи ред“
                        $take1 = $bestUnderQty;
                        $zonesSplit1 = $allocToZones($take1);
                        if (!empty($zonesSplit1)) {
                            self::appendMove($res, $pallets[$bestUnder]->position, $take1, $zonesSplit1, $pArr, 'B.under_then_fr');
                        }
                        $pArr[$bestUnder] = 0.0;
                        $remaining = self::ffix($remaining - $take1);

                        // допокриване от „Първи ред до“
                        usort($firstRowIdx, function ($a, $b) use ($pallets, $strategy) {
                            return rack_MovementGenerator3::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, false);
                        });
                        foreach ($firstRowIdx as $frId) {
                            if ($remaining <= 0) break;
                            $q = self::ffix($pArr[$frId]); if ($q <= 0) continue;
                            $take2 = min($q, $remaining);
                            $zonesSplit2 = $allocToZones($take2);
                            if (!empty($zonesSplit2)) {
                                self::appendMove($res, $pallets[$frId]->position, $take2, $zonesSplit2, $pArr, 'B.under_then_fr');
                            }
                            $pArr[$frId] = self::ffix($pArr[$frId] - $take2);
                            $remaining    = self::ffix($remaining - $take2);
                        }

                        foreach ($groupRemZones as $zId => $remQty) {
                            $zonesAfterB[$zId] = isset($zonesAfterB[$zId]) ? self::ffix($zonesAfterB[$zId] + $remQty) : self::ffix($remQty);
                        }
                        continue;
                    }
                }

                // 3.2.3 – Разбутан + друг разбутан по стратегия (минимален „over“)
                $bestPair = null; $bestLeftover = null; $bestTie = null;
                for ($i = 0; $i < count($brokenIdx); $i++) {
                    $p1 = $brokenIdx[$i]; if ($pallets[$p1]->_isFirstRow) continue;
                    $q1 = self::ffix($pArr[$p1]); if ($q1 <= 0 || $q1 >= $remaining) continue;
                    for ($j = 0; $j < count($brokenIdx); $j++) {
                        if ($j == $i) continue;
                        $p2 = $brokenIdx[$j]; if ($pallets[$p2]->_isFirstRow) continue;
                        $q2 = self::ffix($pArr[$p2]); if ($q2 <= 0) continue;
                        $sum = $q1 + $q2;
                        if ($sum >= $remaining) {
                            $left = self::ffix($sum - $remaining);
                            $tie  = self::strategyTieScore($pallets[$p2], $strategy);
                            if ($bestLeftover === null || $left < $bestLeftover || ($left == $bestLeftover && $tie < $bestTie)) {
                                $bestLeftover = $left; $bestTie = $tie; $bestPair = array($p1,$p2,$q1,$q2);
                            }
                        }
                    }
                }
                if ($bestPair) {
                    list($p1,$p2,$q1,$q2) = $bestPair;
                    // взимаме целия q1 от първия
                    $take1 = $q1;
                    $zonesSplit1 = $allocToZones($take1);
                    if (!empty($zonesSplit1)) {
                        self::appendMove($res, $pallets[$p1]->position, $take1, $zonesSplit1, $pArr, 'B.pair_first');
                    }
                    $pArr[$p1] = 0.0;
                    $remaining = self::ffix($remaining - $take1);

                    if ($remaining > 0) {
                        $take2 = min($remaining, $q2);
                        $zonesSplit2 = $allocToZones($take2);
                        if (!empty($zonesSplit2)) {
                            self::appendMove($res, $pallets[$p2]->position, $take2, $zonesSplit2, $pArr, 'B.pair_second');
                        }
                        $pArr[$p2] = self::ffix($pArr[$p2] - $take2);
                        $remaining = self::ffix($remaining - $take2);

                        // Остатък върху втория палет → Smart Return (само ако e извън „Първи ред до“)
                        $leftOnP2 = self::ffix($q2 - $take2);
                        if ($leftOnP2 > 0 && !$pallets[$p2]->_isFirstRow) {
                            if ($smart = self::trySmartReturn($pallets[$p2], $leftOnP2, $storeId, $qInPallet)) {
                                $res[] = $smart;
                            }
                        }
                    }

                    foreach ($groupRemZones as $zId => $remQty) {
                        $zonesAfterB[$zId] = isset($zonesAfterB[$zId]) ? self::ffix($zonesAfterB[$zId] + $remQty) : self::ffix($remQty);
                    }
                    continue;
                }

                // 3.2.5 – един разбутан по стратегия (минимизира |remaining - q|)
                $bestBroken = null; $bestBrokenScore = null;
                foreach ($brokenIdx as $pid) {
                    if ($pallets[$pid]->_isFirstRow) continue;
                    $q = self::ffix($pArr[$pid]); if ($q <= 0) continue;
                    $delta = abs($remaining - $q);
                    $score = $delta * 1000 + self::strategyTieScore($pallets[$pid], $strategy);
                    if ($bestBrokenScore === null || $score < $bestBrokenScore) {
                        $bestBrokenScore = $score; $bestBroken = $pid;
                    }
                }
                if ($bestBroken !== null) {
                    $q = self::ffix($pArr[$bestBroken]);
                    $take = min($q, $remaining);
                    $zonesSplit = $allocToZones($take);
                    if (!empty($zonesSplit)) {
                        self::appendMove($res, $pallets[$bestBroken]->position, $take, $zonesSplit, $pArr, 'B.broken');
                    }
                    $pArr[$bestBroken] = self::ffix($pArr[$bestBroken] - $take);
                    $remaining = self::ffix($remaining - $take);

                    // остатък върху този палет → Smart Return (ако е извън „Първи ред до“)
                    $leftOnBroken = self::ffix($q - $take);
                    if ($leftOnBroken > 0 && !$pallets[$bestBroken]->_isFirstRow) {
                        if ($smart = self::trySmartReturn($pallets[$bestBroken], $leftOnBroken, $storeId, $qInPallet)) {
                            $res[] = $smart;
                        }
                    }

                    foreach ($groupRemZones as $zId => $remQty) {
                        $zonesAfterB[$zId] = isset($zonesAfterB[$zId]) ? self::ffix($zonesAfterB[$zId] + $remQty) : self::ffix($remQty);
                    }
                    continue;
                }

                // 3.2.6 – от ЦЯЛ палет по стратегия, взимаме само колкото е остатъкът
                if ($remaining > 0) {
                    $fullIdxNow = array();
                    foreach ($pArr as $pId => $pQ) if ($pQ >= $qInPallet) $fullIdxNow[] = $pId;
                    if (!empty($fullIdxNow)) {
                        $deprioFirstRow = ($strategy !== 'oldest');
                        usort($fullIdxNow, function ($a, $b) use ($pallets, $strategy, $deprioFirstRow) {
                            return rack_MovementGenerator3::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, $deprioFirstRow);
                        });
                        $pid  = $fullIdxNow[0];
                        $take = min(self::ffix($pArr[$pid]), $remaining);
                        $zonesSplit = $allocToZones($take);
                        if (!empty($zonesSplit)) {
                            self::appendMove($res, $pallets[$pid]->position, $take, $zonesSplit, $pArr, 'B.full_partial');
                        }
                        $pArr[$pid] = self::ffix($pArr[$pid] - $take);
                        $remaining  = self::ffix($remaining - $take);

                        // ако оставяме „разбутан“ върху този палет и НЕ е „Първи ред до“ → Smart Return
                        $leftOnPid = self::ffix($pArr[$pid]);
                        if ($leftOnPid > 0 && !$pallets[$pid]->_isFirstRow) {
                            if ($smart = self::trySmartReturn($pallets[$pid], $leftOnPid, $storeId, $qInPallet)) {
                                $res[] = $smart;
                            }
                        }
                    }
                }

                // 2.3) Край на групата: натрупваме остатъците от подзоните ѝ
                foreach ($groupRemZones as $zId => $remQty) {
                    $zonesAfterB[$zId] = isset($zonesAfterB[$zId]) ? self::ffix($zonesAfterB[$zId] + $remQty) : self::ffix($remQty);
                }
            }

            // 🔹 Обновяваме $zones според реалните остатъци след всички групи – така Етап A ще доразпредели само недостига
            $zones = array();
            foreach ($zonesAfterB as $zId => $v) {
                $v = self::ffix($v);
                if ($v > 0) $zones[$zId] = $v;
            }
        }


        // ===================== ЕТАП A =====================
        if ($qInPallet > 0) {
            $remaining = 0.0;
            foreach ($zones as $v) $remaining += self::ffix($v);
            if ($remaining <= 0) {
                Mode::pop('pickupStoreId');
                return $res;
            }

            $fullIdx = [];
            foreach ($pArr as $pId => $pQ) if ($pQ >= $qInPallet) $fullIdx[] = $pId;
            $deprioFirstRow = ($strategy !== 'oldest');
            usort($fullIdx, function ($a, $b) use ($pallets, $strategy, $deprioFirstRow) {
                return rack_MovementGenerator3::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, $deprioFirstRow);
            });

            foreach ($zones as $zId => $zQ) {
                if ($zQ <= 0) continue;
                $needFull = (int)floor($zQ / $qInPallet);
                $remZone = self::ffix($zQ - $needFull * $qInPallet);

                while ($needFull > 0 && !empty($fullIdx)) {
                    $pId = array_shift($fullIdx);
                    if ($pArr[$pId] < $qInPallet) continue;
                    $take = $qInPallet;
                    self::appendMove($res, $pallets[$pId]->position, $take, [$zId => $take], $pArr);
                    $pArr[$pId] = self::ffix($pArr[$pId] - $take);
                    $needFull--;
                }

                // 🟢 Новият блок за остатъка
                if ($remZone > 0) {
                    while ($remZone > 0) {
                        $candidates = [];
                        foreach ($pArr as $pid => $pq) if ($pq > 0) $candidates[] = $pid;
                        if (empty($candidates)) break;
                        $deprioFirstRow = ($strategy !== 'oldest');
                        usort($candidates, function ($a, $b) use ($pallets, $strategy, $deprioFirstRow) {
                            return rack_MovementGenerator3::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, $deprioFirstRow);
                        });
                        $pId = $candidates[0];
                        $take = min($pArr[$pId], $remZone);
                        self::appendMove($res, $pallets[$pId]->position, $take, [$zId => $take], $pArr);
                        $pArr[$pId] -= $take;
                        $remZone -= $take;
                    }
                }
            }
        }

        self::evaluateMoves($res, $packArr, $pallets, $qInPallet);
        $res = self::consolidateMoves($res);
        Mode::pop('pickupStoreId');
        return $res;
    }


    /* ===================== Помощни функции за стратегията ===================== */

    /** 'oldest' | 'lowest' | 'closest' (без fallback към preferOldest) */
    private static function getFullPalletStrategy($storeId)
    {
        $val = $storeId ? store_Stores::fetchField($storeId, 'fullPalletStrategy') : null;
        return in_array($val, array('oldest','lowest','closest'), true) ? $val : 'oldest';
    }

    /** Нормализира „старшинство“ само по createdOn (по-старо = по-малък timestamp). */
    private static function ensureOldestOrdinal(array &$pallets)
    {
        foreach ($pallets as &$p) {
            if (isset($p->_ordOldest)) continue;
            if (!empty($p->createdOn)) {
                $ts = @strtotime($p->createdOn);
                $p->_ordOldest = ($ts !== false && $ts !== -1) ? (int)$ts : PHP_INT_MAX;
            } else {
                $p->_ordOldest = PHP_INT_MAX;
            }
        }
    }

    /** Компаратор според стратегията; $deprioFirstRow=true → „Първи ред до“ назад при равенство */
    private static function cmpByStrategy($a, $b, $strategy, $deprioFirstRow)
    {
        $ar = $a->_rowCol ?: self::getRowCol($a->position);
        $br = $b->_rowCol ?: self::getRowCol($b->position);

        if ($strategy === 'oldest') {
            $as = isset($a->_ordOldest) ? (int)$a->_ordOldest : PHP_INT_MAX;
            $bs = isset($b->_ordOldest) ? (int)$b->_ordOldest : PHP_INT_MAX;
            if ($as !== $bs) return ($as < $bs) ? -1 : 1; // по-старото (по-малък ts) е с приоритет
        } elseif ($strategy === 'lowest') {
            if ($ar['row'] != $br['row']) return strcmp($ar['row'], $br['row']); // по-нисък ред
            if ((int)$ar['col'] != (int)$br['col']) return ((int)$ar['col'] < (int)$br['col']) ? -1 : 1; // tie -> по-малка колона
        } else { // 'closest'
            if ((int)$ar['col'] != (int)$br['col']) return ((int)$ar['col'] < (int)$br['col']) ? -1 : 1; // по-близка колона
            if ($ar['row'] != $br['row']) return strcmp($ar['row'], $br['row']); // tie -> по-нисък ред
        }

        if ($deprioFirstRow) {
            $af = !empty($a->_isFirstRow);
            $bf = !empty($b->_isFirstRow);
            if ($af != $bf) return $af ? 1 : -1;
        }

        return strcmp((string)$a->position, (string)$b->position);
    }

    /** Tie-score за комбиниране/равенства */
    private static function strategyTieScore($p, $strategy)
    {
        $rc = $p->_rowCol ?: self::getRowCol($p->position);
        if ($strategy === 'oldest') {
            return isset($p->_ordOldest) ? (int)$p->_ordOldest : PHP_INT_MAX;
        } elseif ($strategy === 'lowest') {
            return ord($rc['row']) * 1000 + (int)$rc['col'];
        } else {
            return (int)$rc['col'] * 1000 + ord($rc['row']);
        }
    }

    /** row/col от позиция */
    private static function getRowCol($pos)
    {
        if ($pos == rack_PositionType::FLOOR) return array('row' => 'Z', 'col' => 9999);
        list($num, $row, $col) = rack_PositionType::toArray($pos);
        $row = strtoupper($row);
        $col = (int)$col;
        if (!$row) $row = 'Z';
        if (!$col) $col = 9999;
        return array('row' => $row, 'col' => $col);
    }

	/**
	 * Изчислява номинала на „цял палет“
	 *
	 * 1) Ако артикулът има дефинирана опаковка „палет“ — използва се точно нейното количество.
	 * 2) Ако няма, търси най-голямото повтарящо се количество по палетмясто.
	 * 3) Ако няма повтарящи се — най-голямото количество от наличните палетмества.
	 */
	private static function computeFullPalletSize($pallets, $packaging, $palletId)
	{
		// 1) Ако има дефинирана опаковка „палет“ — това е реалният номинал на цял палет
		foreach ($packaging as $pack) {
			if ($pack->packagingId == $palletId && $pack->quantity > 0) {
				return self::ffix($pack->quantity);
			}
		}

		// 2) Най-голямото повтарящо се количество
		$cnt = [];
		foreach ($pallets as $iRec) {
			$q = self::ffix($iRec->quantity);
			if ($q <= 0) continue;
			if (!isset($cnt[$q])) $cnt[$q] = 0;
			$cnt[$q]++;
		}

		$bestRepeatQty = 0.0;
		foreach ($cnt as $q => $n) {
			if ($n >= 2 && $q > $bestRepeatQty) {
				$bestRepeatQty = $q;
			}
		}
		if ($bestRepeatQty > 0) {
			return $bestRepeatQty;
		}

		// 3) Най-голямото количество на палетмясто
		$max = 0.0;
		foreach ($pallets as $iRec) {
			$q = self::ffix($iRec->quantity);
			if ($q > $max) $max = $q;
		}

		return $max;
	}

    /* ===================== Помощни / налични методи ===================== */

    public static function gcd($a, $b) { return ($a % $b) ? self::gcd($b, $a % $b) : $b; }

    public static function isFirstRow($pos)
    {
        if ($pos == rack_PositionType::FLOOR) return false;

        list($num, $row, ) = rack_PositionType::toArray($pos);
        $row = strtolower($row);

        if (!array_key_exists("{$num}|{$row}", static::$firstRowTo)) {
            if ($num) {
                $sessionStoreId = Mode::get('pickupStoreId');
                $storeId = isset($sessionStoreId) ? $sessionStoreId : store_Stores::getCurrent();
                static::$firstRowTo["{$num}|{$row}"] =
                    strtolower(rack_Racks::fetchField(array('#storeId = [#1#] AND #num = [#2#]', $storeId, $num), 'firstRowTo'));
            } else {
                static::$firstRowTo["{$num}|{$row}"] = 'a';
            }
        }

        return $row <= static::$firstRowTo["{$num}|{$row}"];
    }
	
	
	 /**
     * Групира подадените зони по групи (rack_ZoneGroups)
     * Връща масив: [groupId => ['zones' => [zoneId => qty], 'sum' => totalQty]]
     * Ако дадена зона няма група – получава собствен groupId = 'zone:{id}'
     */
    private static function groupZonesByZoneGroup(array $zones)
    {
        $out = [];
        foreach ($zones as $zId => $zQ) {
            $groupId = rack_Zones::fetchField($zId, 'groupId');
            if (empty($groupId)) {
                $groupId = "zone:{$zId}"; // без група – самостоятелна
            }
            if (!isset($out[$groupId])) {
                $out[$groupId] = ['zones' => [], 'sum' => 0.0];
            }
            $out[$groupId]['zones'][$zId] = self::ffix($zQ);
            $out[$groupId]['sum'] += self::ffix($zQ);
        }
        return $out;
    }

	

    /** Оценка на движенията */
    private static function evaluateMoves(array &$moves, $packs, $allPallets, $qInPallet)
    {
        static $timeGet, $timeGetA, $timeZone, $timeReturn;
        if (!isset($timeGet)) {
            $timeGet   = rack_Setup::get('TIME_GET');
            $timeGetA  = rack_Setup::get('TIME_GET_A');
            $timeZone  = rack_Setup::get('TIME_ZONE');
            $timeReturn= rack_Setup::get('TIME_RETURN');
        }

        foreach ($moves as $m) {
            if (empty($m->zones) && empty($m->ret)) continue;

            // Вземане
            $m->timeTake = self::isFirstRow($m->pallet) ? $timeGetA : $timeGet;

            // Броене от палета
            if (isset($m->pQ) && $m->pQ != $m->quantity) {
                $m->timeCount = self::timeToCount($m->pQ, $m->quantity, $packs);
            }

            // Оставяне по зоните
            $q = $m->quantity;
            if (!empty($m->zones)) {
                foreach ($m->zones as $zI => $zQ) {
                    if ($zQ <= 0) continue;
                    $m->zonesTimes[$zI] = $timeZone;
                    if ($q != $zQ) {
                        $m->zonesCountTimes[$zI] = self::timeToCount($q, $zQ, $packs);
                    }
                }
            }

            if (!empty($m->ret)) {
                $m->timeReturn = $timeReturn;
            }
        }
    }

    /** Оценка на броене/разопаковане */
    private static function timeToCount($s, $d, $packs)
    {
        $sec = rack_Setup::get('TIME_COUNT');
        krsort($packs);

        $sTemp = $s; $dTemp = $d; $i = 1;
        $pArr = $sArr = $dArr = array();

        foreach ($packs as $pQ => $pI) {
            $sArr[$i] = (int)($sTemp / $pQ);
            $sTemp -= $sArr[$i] * $pQ;
            $sTemp = round($sTemp, 6, PHP_ROUND_HALF_UP);
            $dArr[$i] = (int)($dTemp / $pQ);
            $dTemp -= $dArr[$i] * $pQ;
            $dTemp = round($dTemp, 6, PHP_ROUND_HALF_UP);
            $pArr[$i] = $pQ;
            $i++;
        }

        if ($sTemp > 0 || $dTemp > 0) {
            $sArr[$i] = $sTemp;
            $dArr[$i] = $dTemp;
            $pArr[$i] = 1;
        } else {
            $i--;
        }

        $sI = $dI = $i; $maxTries = 10; $try = 1; $res = 0;

        while ($sI > 0 && $dI > 0) {
            $sQ = $sArr[$sI] * $pArr[$sI];
            $dQ = $dArr[$dI] * $pArr[$dI];

            $m = round(min($sQ, $dQ), 6, PHP_ROUND_HALF_UP);

            if ($m > 0) {
                $unit = $sec / 1.8;
                $res += $unit * ($m / $pArr[$dI]);

                $sArr[$sI] -= $m / $pArr[$sI];
                $sArr[$sI] = round($sArr[$sI], 6, PHP_ROUND_HALF_UP);
                $dArr[$dI] -= $m / $pArr[$dI];
                $dArr[$dI] = round($dArr[$dI], 6, PHP_ROUND_HALF_UP);

                if ($sI < $dI) { $res += $unit * 10; }
            }

            if ($sArr[$sI] <= 0) $sI--;
            if ($dArr[$dI] <= 0) $dI--;

            if ($try++ >= $maxTries) { break; }
        }

        return $res;
    }

    /** Максимално натоварване на позиция (бр. палети) */
    public static function getMaxLoad($pos)
    {
        $res = null;
        if ($rack = (int)$pos) {
            $rRec = rack_Racks::fetch($rack);
            $res = $rRec->maxLoad;
        }
        if (!$res) $res = 1;
        return $res;
    }

    /** Мин. остатък – запазено за съвместимост (новата логика не „взема-цял-и-връща“) */
    private static function getMinKeepQty($storeId, $qInPallet)
    {
        if (!$storeId || $qInPallet <= 0) return 0.0;
        $pct = (float)store_Stores::fetchField($storeId, 'minKeepPct'); // 0..1
        if ($pct <= 0) return 0.0;
        if ($pct > 0.8) $pct = 0.8;
        return $qInPallet * $pct;
    }

    /** Нормализиране на float */
    private static function ffix($v, $precision = 6)
    {
        $eps = pow(10, -$precision);
        $v = round((float)$v, $precision, PHP_ROUND_HALF_UP);
        if (abs($v) < $eps) return 0.0;
        return $v;
    }
	
	/**
	 * Добавя движение, като при нужда обединява с вече съществуващо
	 */
	private static function appendMove(&$res, $palletPos, $take, $zonesSplit, $pArr, $note = '')
	{
		$merged = false;

		foreach ($res as $r) {
			// Ако е едно и също палетмясто
			if ($r->pallet === $palletPos) {
				// Проверяваме дали има поне една обща зона
				$commonZones = array_intersect_key($zonesSplit, $r->zones);
				if (!empty($commonZones)) {
					foreach ($zonesSplit as $zId => $q) {
						if (!isset($r->zones[$zId])) {
							$r->zones[$zId] = 0.0;
						}
						$r->zones[$zId] = self::ffix($r->zones[$zId] + $q);
					}
					$r->quantity = self::ffix($r->quantity + $take);
					if (!empty($note)) {
						$r->note .= ',' . $note;
					}
					$merged = true;
					break;
				}
			}
		}

		if (!$merged) {
			// нов запис
			$res[] = (object)[
				'pallet'   => $palletPos,
				'quantity' => self::ffix($take),
				'zones'    => $zonesSplit,
				'pQ'       => self::ffix($pArr[$palletPos] ?? 0),
				'note'     => $note,
			];
		}
	}
	
	
	/**
	 * Smart Return – намира оптимално A-място за връщане на остатъка
	 *
	 * 1️. Ако има друг палет на ред A със същия артикул и сборът <= qInPallet → консолидира към него
	 * 1.2. Ако има няколко такива → приоритет: същия стелаж, после по-малка разлика в колоната
	 * 2. Ако няма такъв → търси напълно свободно A-място
	 * 2.1. Приоритет: същия стелаж, после минимална разлика в колоната
	 *
	 * @param stdClass $palletRec текущият палет
	 * @param float $leftQty остатъчното количество
	 * @param int $storeId складът
	 * @param float $qInPallet количеството на цял палет за артикула
	 * @return object|null движение с ret/retPos или null
	 */
	private static function trySmartReturn($palletRec, $leftQty, $storeId, $qInPallet)
	{
		// Проверка дали е разрешено
		if (store_Stores::fetchField($storeId, 'allowSmartReturnPos') !== 'yes') return null;
		if ($leftQty <= 0) return null;
		if (self::isFirstRow($palletRec->position)) return null;

		$articleId = $palletRec->articleId;
		list($rackNum, $row, $col) = rack_PositionType::toArray($palletRec->position);

		// Извличаме firstRowTo за склада
		$firstRowTo = strtolower(
			rack_Racks::fetchField(array('#storeId = [#1#] AND #num = [#2#]', $storeId, $rackNum), 'firstRowTo')
		) ?: 'a';

		// --- 1. Търсим съществуващи палети на ред <= firstRowTo със същия артикул ---
		$existing = rack_Positions::select()
			->where(array(
				"#storeId = [#1#] AND #articleId = [#2#] AND LCASE(SUBSTRING_INDEX(#pos, '-', 2)) <= [#3#]",
				$storeId,
				$articleId,
				$firstRowTo
			))
			->fetchAll();

		$best = null; $bestScore = PHP_INT_MAX;

		foreach ($existing as $rec) {
			// Изчисляваме колко би станал сборът
			$currentQty = self::ffix($rec->quantity ?? 0);
			$sum = $currentQty + $leftQty;
			if ($sum > $qInPallet) continue; // не може да се консолидира

			list($rRack, $rRow, $rCol) = rack_PositionType::toArray($rec->pos);
			$rackDiff = ($rRack == $rackNum) ? 0 : 1;
			$colDiff = abs((int)$rCol - (int)$col);

			// приоритет: същия стелаж, после по-близка колона
			$score = $rackDiff * 1000 + $colDiff;
			if ($score < $bestScore) {
				$bestScore = $score;
				$best = $rec->pos;
			}
		}

		if ($best) {
			return (object)[
				'pallet' => $palletRec->position,
				'ret'    => self::ffix($leftQty),
				'retPos' => $best,
				'note'   => 'smartReturn_consolidate'
			];
		}

		// --- 2. Ако няма подходящ палет → търсим свободни A-места ---
		$free = rack_Positions::select()
			->where(array("#storeId = [#1#] AND LCASE(SUBSTRING_INDEX(#pos, '-', 2)) <= [#2#]", $storeId, $firstRowTo))
			->where("#isEmpty = 'yes' OR #articleId IS NULL")
			->fetchAll();

		$best = null; $bestScore = PHP_INT_MAX;

		foreach ($free as $rec) {
			list($rRack, $rRow, $rCol) = rack_PositionType::toArray($rec->pos);
			$rackDiff = ($rRack == $rackNum) ? 0 : 1;
			$colDiff = abs((int)$rCol - (int)$col);
			$score = $rackDiff * 1000 + $colDiff;

			if ($score < $bestScore) {
				$bestScore = $score;
				$best = $rec->pos;
			}
		}

		if ($best) {
			return (object)[
				'pallet' => $palletRec->position,
				'ret'    => self::ffix($leftQty),
				'retPos' => $best,
				'note'   => 'smartReturn_freeA'
			];
		}

		// --- 3️. Ако няма абсолютно нищо подходящо ---
		return null;
	}
	
	
	/**
	 * Целочислено деление на количества: връща [брой цели палети, остатък]
	 * Пази се от плаващи грешки чрез леко „буферче“ и нормализация.
	 *
	 * @param float $total общо количество
	 * @param float $unit  размер на "цял палет"
	 * @return array [int $fullCnt, float $rem]
	 */
	private static function qtyDivRem($total, $unit)
	{
		$total = self::ffix($total);
		$unit  = self::ffix($unit);
		if ($unit <= 0) return [0, $total];

		// малък буфер за стабилност при делението с float
		$n = (int) floor(($total + 1e-9) / $unit);
		$rem = self::ffix($total - $n * $unit);
		if ($rem < 0) $rem = 0.0;

		return [$n, $rem];
}


    /** Сливане на движения по ключ (палет → зона) */
    private static function consolidateMoves($moves)
    {
        $byPallet = array();

        foreach ((array)$moves as $m) {
            if (empty($m) || !isset($m->pallet)) continue;

            $pallet = $m->pallet;
            if (!isset($byPallet[$pallet])) {
                $byPallet[$pallet] = (object) array(
                    'pallet' => $pallet,
                    'zones'  => array(),
                    'quantity' => 0.0,
                    'ret' => null,
                    'retPos' => null,
                );
            }

            if (!empty($m->zones)) {
                foreach ($m->zones as $zId => $q) {
                    $q = self::ffix($q);
                    if ($q <= 0) continue;
                    if (!isset($byPallet[$pallet]->zones[$zId])) $byPallet[$pallet]->zones[$zId] = 0.0;
                    $byPallet[$pallet]->zones[$zId] = self::ffix($byPallet[$pallet]->zones[$zId] + $q);
                }
            }

            if (!empty($m->ret)) {
                $byPallet[$pallet]->ret = self::ffix((float)$byPallet[$pallet]->ret + (float)$m->ret);
                if (!empty($m->retPos)) $byPallet[$pallet]->retPos = $m->retPos;
            }
        }

        $out = array();
        foreach ($byPallet as $p => $mm) {
            foreach ($mm->zones as $zId => $q) {
                $q = self::ffix($q);
                if ($q <= 0) unset($mm->zones[$zId]); else $mm->zones[$zId] = $q;
            }

            $qty = 0.0; foreach ($mm->zones as $q) $qty += $q;
            $mm->quantity = self::ffix($qty);

            if (empty($mm->zones) && empty($mm->ret)) continue;
            $out[] = $mm;
        }

        return $out;
    }
}
