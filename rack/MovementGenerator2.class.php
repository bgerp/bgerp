<?php

/**
 * Генератор на движения в палетния склад
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_MovementGenerator2 extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'rack_Wrapper';

    /**
     * Генератор на движения
     */
    public $title = 'Генератор на движения';

    /**
     * Какъв процент от количеството трябва да е на палета, за да го смятаме за почти пълен?
     * Използва се само като „мек“ праг в помощни сравнения. НЕ променя дефиницията за „цял палет“ по-долу.
     */
    const ALMOST_FULL = 0.85;

    /**
     * Работен кеш
     */
    public static $firstRowTo = array();

    /**
     * Екшън за тест
     */
    public function act_Default()
    {
        requireRole('debug');

        $form = cls::get('core_Form');
        $form->FLD('pallets', 'table(columns=pallet|quantity|createdOn,captions=Палет|Количество|Създаване,widths=8em|8em|10em)', 'caption=Палети,mandatory');
        $form->FLD('zones', 'table(columns=zone|quantity,captions=Зона|Количество,widths=8em|8ем)', 'caption=Зони,mandatory');
        $form->FLD('packagings', 'table(columns=packagingId|quantity,captions=Опаковка|Количество,widths=8em|8ем)', 'caption=Опаковки,mandatory');
        $form->FLD('smallZonesPriority', 'enum(yes=Да,no=Не)', 'caption=Приоритетност на малките количества->Избор');

        $packOptions  = array('' => '') + cat_UoM::getPackagingOptions() + cat_UoM::getUomOptions();
        $createdOnOpt = array(dt::addDays(-4), dt::addDays(-3), dt::addDays(-2), dt::addDays(-1), dt::addDays(1), dt::now());
        $createdOnOpt = array('' => '') + arr::make($createdOnOpt, true);

        $form->setFieldTypeParams('packagings', array('packagingId_opt' => $packOptions));
        $form->setFieldTypeParams('pallets', array('createdOn_opt' => $createdOnOpt));

        $form->toolbar = cls::get('core_Toolbar');
        $form->toolbar->addSbBtn('Изпрати');

        $rec = $form->input();

        $p = $q = $packs = array();
        $mArr = array();

        if ($form->isSubmitted()) {
            $pArr    = json_decode($rec->pallets);
            $qArr    = json_decode($rec->zones);
            $packArr = json_decode($rec->packagings);

            foreach ($pArr->pallet as $i => $key) {
                if ($pArr->quantity[$i]) {
                    $qVerbal = core_Type::getByName('double')->fromVerbal($pArr->quantity[$i]);
                    $po = (object) array(
                        'position'  => $key,
                        'quantity'  => $qVerbal,
                        'createdOn' => $pArr->createdOn[$i] ?? null,
                    );
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
                if ($packArr->quantity[$i]) {
                    $packs[] = (object) array('packagingId' => $key, 'quantity' => $packArr->quantity[$i]);
                }
            }

            $storeId = Mode::get('pickupStoreId') ?: store_Stores::getCurrent();
            $mArr = self::mainP2Q($p, $q, $packs, 0, 0, $storeId, null);
        }

        $form->title = 'Генериране на движения по палети';

        $html = $form->renderHtml();

        if (countR($p)) {
            $html .= '<h2>Палети</h2>' . ht::mixedToHtml($p);
        }
        if (countR($q)) {
            $html .= '<h2>Зони</h2>' . ht::mixedToHtml($q);
        }
        if (countR($mArr)) {
            $html .= '<h2>Движения</h2>' . ht::mixedToHtml($mArr, 5, 7);
        }

        $html = $this->renderWrapping($html);
        return $html;
    }

    /**
     * Главен алгоритъм
     *
     * @param array      $pallets  масив от обекти {position, quantity, createdOn?}
     * @param array      $zones    ['zoneId' => qty]
     * @param array      $packaging
     * @param float|null $volume
     * @param float|null $weight
     * @param int|null   $storeId
     * @param bool|null  $preferOldest legacy (игнорира се; ползва се стратегия от склада)
     * @return array|false
     */
    public static function mainP2Q($pallets, $zones, $packaging = array(), $volume = null, $weight = null, $storeId = null, $preferOldest = null)
    {
        $sid = $storeId ?: store_Stores::getCurrent();
        $strategy = self::getFullPalletStrategy($sid); // 'oldest' | 'lowest' | 'closest'

        $sumZ  = array_sum($zones);
        $scale = 1;
        if ($scale > 1000000) return false;

        // нормализация на зоните (>0)
        foreach ($zones as $zI => $zQ) {
            $zones[$zI] = self::ffix($zones[$zI] * $scale);
            if ($zones[$zI] <= 0) unset($zones[$zI]);
        }

        // опаковки
        asort($packaging);
        $palletId = cat_UoM::fetchBySysId('pallet')->id;

        // „цял палет“
        $qInPallet = self::computeFullPalletSize($pallets, $packaging, $palletId);

        // за timeToCount
        $packArr = array();
        foreach ($packaging as $pack) {
            $k = $pack->quantity * $scale;
            $packArr["{$k}"] = $pack->packagingId;
        }
        krsort($packArr);

        Mode::push('pickupStoreId', $storeId);

        // подготвяме палетите
        $sumP = 0;
        $pArr = array();
        foreach ($pallets as $id => $p) {
            if ($p->quantity > 0) {
                $pArr[$id] = self::ffix($p->quantity * $scale);
                $sumP += $pArr[$id];
            }
            $pallets[$id]->_rowCol     = self::getRowCol($p->position);
            $pallets[$id]->_isFirstRow = self::isFirstRow($p->position);
            $pallets[$id]->_ordOldest  = self::createdToOrdinal($p->createdOn ?? null);
        }

        // недостиг – подрязване по малки зони (както и преди)
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

        $res = array();

        /* ===================== ЕТАП B (ПЪРВО): остатъци под цял палет ===================== */
        if ($qInPallet > 0) {
            foreach ($zones as $zId => $zQ) {
                list($fullCnt, $rem) = self::qtyDivRem($zQ, $qInPallet);
                if ($rem <= 0) continue;

                // работни списъци
                $brokenIdx = array();
                $firstRowIdx = array();
                $fullIdxNow = array();

                foreach ($pArr as $pId => $pQ) {
                    if ($pQ <= 0) continue;
                    if ($pQ >= $qInPallet) $fullIdxNow[] = $pId; else $brokenIdx[] = $pId;
                    if ($pallets[$pId]->_isFirstRow) $firstRowIdx[] = $pId;
                }

                // 3.2.4 (ПЪРВО): ако „първи ред до“ може да покрие целия rem – вземаме директно оттам
                $frSum = 0;
                foreach ($firstRowIdx as $frId) $frSum += self::ffix($pArr[$frId]);
                if ($frSum >= $rem) {
                    usort($firstRowIdx, function ($a, $b) use ($pallets, $strategy) {
                        return rack_MovementGenerator2::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, false);
                    });
                    foreach ($firstRowIdx as $frId) {
                        if ($rem <= 0) break;
                        $q = self::ffix($pArr[$frId]);
                        if ($q <= 0) continue;
                        $take = min($q, $rem);
                        $res[] = (object) array(
                            'pallet'   => $pallets[$frId]->position,
                            'quantity' => self::ffix($take),
                            'zones'    => array($zId => self::ffix($take)),
                            'pQ'       => self::ffix($pArr[$frId]),
                        );
                        $pArr[$frId] = self::ffix($pArr[$frId] - $take);
                        $zones[$zId] = self::ffix($zones[$zId] - $take);
                        $rem = self::ffix($rem - $take);
                    }
                    // остатъкът е покрит → към следващата зона
                    continue;
                }

                // сортиране по стратегия
                usort($brokenIdx,   function ($a, $b) use ($pallets, $strategy) { return rack_MovementGenerator2::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, false); });
                $deprioFirstRow = ($strategy !== 'oldest');
                usort($fullIdxNow, function ($a, $b) use ($pallets, $strategy, $deprioFirstRow) { return rack_MovementGenerator2::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, $deprioFirstRow); });

                // 3.2.1: точен разбутан
                $exactId = null;
                foreach ($brokenIdx as $pId) {
                    if (self::ffix($pArr[$pId]) == $rem) { $exactId = $pId; break; }
                }
                if ($exactId !== null) {
                    $take = $rem;
                    $res[] = (object) array(
                        'pallet'   => $pallets[$exactId]->position,
                        'quantity' => $take,
                        'zones'    => array($zId => $take),
                        'pQ'       => self::ffix($pArr[$exactId]),
                    );
                    $pArr[$exactId] = self::ffix($pArr[$exactId] - $take);
                    $zones[$zId] = self::ffix($zones[$zId] - $take);
                    continue;
                }

                // 3.2.2: разбутан(<rem) + първи ред до (ако общо стигат)
                $bestUnder = null; $bestUnderQty = 0.0;
                foreach ($brokenIdx as $pId) {
                    $q = self::ffix($pArr[$pId]);
                    if ($q <= 0 || $q >= $rem) continue;
                    if ($q > $bestUnderQty) { $bestUnderQty = $q; $bestUnder = $pId; }
                }
                if ($bestUnder !== null) {
                    $frSum = 0.0; foreach ($firstRowIdx as $frId) $frSum += self::ffix($pArr[$frId]);
                    if ($frSum + $bestUnderQty >= $rem) {
                        // вземи от разбутания
                        $take1 = $bestUnderQty;
                        $res[] = (object) array(
                            'pallet'   => $pallets[$bestUnder]->position,
                            'quantity' => $take1,
                            'zones'    => array($zId => $take1),
                            'pQ'       => self::ffix($pArr[$bestUnder]),
                        );
                        $pArr[$bestUnder] = 0.0;
                        $zones[$zId] = self::ffix($zones[$zId] - $take1);
                        $rem = self::ffix($rem - $take1);

                        // допълни от „първи ред до“
                        foreach ($firstRowIdx as $frId) {
                            if ($rem <= 0) break;
                            $q = self::ffix($pArr[$frId]); if ($q <= 0) continue;
                            $take2 = min($q, $rem);
                            $res[] = (object) array(
                                'pallet'   => $pallets[$frId]->position,
                                'quantity' => $take2,
                                'zones'    => array($zId => $take2),
                                'pQ'       => self::ffix($pArr[$frId]),
                            );
                            $pArr[$frId] = self::ffix($pArr[$frId] - $take2);
                            $zones[$zId] = self::ffix($zones[$zId] - $take2);
                            $rem = self::ffix($rem - $take2);
                        }
                        continue;
                    }
                }

                // 3.2.5: разбутан според стратегията
                $bestBroken = null; $bestBrokenScore = null;
                foreach ($brokenIdx as $pId) {
                    $q = self::ffix($pArr[$pId]); if ($q <= 0) continue;
                    $delta = abs($rem - $q);
                    $score = $delta * 1000 + self::strategyTieScore($pallets[$pId], $strategy);
                    if ($bestBrokenScore === null || $score < $bestBrokenScore) { $bestBrokenScore = $score; $bestBroken = $pId; }
                }
                if ($bestBroken !== null) {
                    $take = min(self::ffix($pArr[$bestBroken]), $rem);
                    $res[] = (object) array(
                        'pallet'   => $pallets[$bestBroken]->position,
                        'quantity' => $take,
                        'zones'    => array($zId => $take),
                        'pQ'       => self::ffix($pArr[$bestBroken]),
                    );
                    $pArr[$bestBroken] = self::ffix($pArr[$bestBroken] - $take);
                    $zones[$zId] = self::ffix($zones[$zId] - $take);
                    $rem = self::ffix($rem - $take);
                }

                // 4) Ако още има остатък – частично от цял палет: първо от „първи ред до“, иначе по стратегия
                if ($rem > 0) {
                    $fullIdxNow = array();
                    foreach ($pArr as $pId => $pQ) if ($pQ >= $qInPallet) $fullIdxNow[] = $pId;
                    $fullFirstRow = array_values(array_filter($fullIdxNow, function($pid) use ($pallets){ return $pallets[$pid]->_isFirstRow; }));
                    $sourceList = !empty($fullFirstRow) ? $fullFirstRow : $fullIdxNow;
                    $deprioFirstRow = ($strategy !== 'oldest');
                    usort($sourceList, function ($a, $b) use ($pallets, $strategy, $deprioFirstRow) {
                        return rack_MovementGenerator2::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, $deprioFirstRow);
                    });

                    foreach ($sourceList as $pid) {
                        if ($rem <= 0) break;
                        $q = self::ffix($pArr[$pid]); if ($q <= 0) continue;
                        $take = min($q, $rem);
                        $res[] = (object) array(
                            'pallet'   => $pallets[$pid]->position,
                            'quantity' => $take,
                            'zones'    => array($zId => $take),
                            'pQ'       => self::ffix($pArr[$pid]),
                            'partial'  => true,
                        );
                        $pArr[$pid] = self::ffix($pArr[$pid] - $take);
                        $zones[$zId] = self::ffix($zones[$zId] - $take);
                        $rem = self::ffix($rem - $take);
                    }
                }
            }
        }

        /* ===================== ЕТАП A (ВТОРО): цели палети ===================== */
        if ($qInPallet > 0) {
            // подредени цели палети по стратегия (Първи ред се деприоритизира само при lowest/closest)
            $fullIdx = array();
            foreach ($pArr as $pId => $pQ) if ($pQ >= $qInPallet) $fullIdx[] = $pId;
            $deprioFirstRow = ($strategy !== 'oldest');
            usort($fullIdx, function ($a, $b) use ($pallets, $strategy, $deprioFirstRow) {
                return rack_MovementGenerator2::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, $deprioFirstRow);
            });

            // раздаване на цели палети по зоните
            foreach ($zones as $zId => $zQ) {
                if ($zQ <= 0) continue;
                list($needFull, $remDummy) = self::qtyDivRem($zQ, $qInPallet);
                while ($needFull > 0 && !empty($fullIdx)) {
                    $pId = array_shift($fullIdx);
                    if ($pArr[$pId] < $qInPallet) continue;
                    $take = $qInPallet;

                    $res[] = (object) array(
                        'pallet'   => $pallets[$pId]->position,
                        'quantity' => self::ffix($take),
                        'zones'    => array($zId => self::ffix($take)),
                        'pQ'       => self::ffix($pArr[$pId]),
                    );

                    $pArr[$pId] = self::ffix($pArr[$pId] - $take);
                    $zones[$zId] = self::ffix($zones[$zId] - $take);
                    if ($pArr[$pId] >= $qInPallet) $fullIdx[] = $pId;
                    $needFull--;
                }
            }
        }

        /* ===================== ЕТАП C (финален): доизпълване на остатък, ако има наличност ===================== */
        foreach ($zones as $zId => $zQ) {
            $need = self::ffix($zQ);
            if ($need <= 0) continue;

            // 1) първо от „първи ред до“
            $firstRowIdx = array();
            foreach ($pArr as $pId => $pQ) {
                if ($pQ > 0 && $pallets[$pId]->_isFirstRow) $firstRowIdx[] = $pId;
            }
            usort($firstRowIdx, function ($a, $b) use ($pallets, $strategy) {
                return rack_MovementGenerator2::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, false);
            });
            foreach ($firstRowIdx as $frId) {
                if ($need <= 0) break;
                $q = self::ffix($pArr[$frId]); if ($q <= 0) continue;
                $take = min($q, $need);
                $res[] = (object) array(
                    'pallet'   => $pallets[$frId]->position,
                    'quantity' => self::ffix($take),
                    'zones'    => array($zId => self::ffix($take)),
                    'pQ'       => self::ffix($pArr[$frId]),
                );
                $pArr[$frId] = self::ffix($pArr[$frId] - $take);
                $zones[$zId] = self::ffix($zones[$zId] - $take);
                $need = self::ffix($need - $take);
            }

            // 2) после от всички останали по стратегия
            if ($need > 0) {
                $restIdx = array_keys(array_filter($pArr, function($q){ return $q > 0; }));
                usort($restIdx, function ($a, $b) use ($pallets, $strategy) {
                    return rack_MovementGenerator2::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, false);
                });
                foreach ($restIdx as $pid) {
                    if ($need <= 0) break;
                    $q = self::ffix($pArr[$pid]); if ($q <= 0) continue;
                    $take = min($q, $need);
                    $res[] = (object) array(
                        'pallet'   => $pallets[$pid]->position,
                        'quantity' => self::ffix($take),
                        'zones'    => array($zId => self::ffix($take)),
                        'pQ'       => self::ffix($pArr[$pid]),
                    );
                    $pArr[$pid] = self::ffix($pArr[$pid] - $take);
                    $zones[$zId] = self::ffix($zones[$zId] - $take);
                    $need = self::ffix($need - $take);
                }
            }
        }

        // Оценка на движенията
        self::evaluateMoves($res, $packArr, $pallets, $qInPallet);

        // Консолидация
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

    /** createdOn → ordinal (по-малък = по-стар) */
    private static function createdToOrdinal($createdOn)
    {
        if (!$createdOn) return PHP_INT_MAX;
        $ts = @strtotime($createdOn);
        return ($ts !== false && $ts !== -1) ? (int)$ts : PHP_INT_MAX;
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

    /** Tie-score за 3.2.5 */
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

    /** Номинал на „цял палет“ */
    private static function computeFullPalletSize($pallets, $packaging, $palletId)
    {
        // 1) Опаковка „палет“
        foreach ($packaging as $pack) {
            if ($pack->packagingId == $palletId && $pack->quantity > 0) {
                return self::ffix($pack->quantity);
            }
        }
        // 2) Най-голямото повтарящо се количество
        $cnt = array();
        foreach ($pallets as $iRec) {
            $q = self::ffix($iRec->quantity);
            if ($q <= 0) continue;
            if (!isset($cnt[$q])) $cnt[$q] = 0;
            $cnt[$q]++;
        }
        $bestRepeatQty = 0.0;
        foreach ($cnt as $q => $n) if ($n >= 2 && $q > $bestRepeatQty) $bestRepeatQty = $q;
        if ($bestRepeatQty > 0) return $bestRepeatQty;

        // 3) Най-голямото количество
        $max = 0.0;
        foreach ($pallets as $iRec) {
            $q = self::ffix($iRec->quantity);
            if ($q > $max) $max = $q;
        }
        return $max;
    }

    /* ===================== Помощни / налични методи ===================== */

    /** Целочислено деление на количества: връща [брой пълни палети, остатък] с устойчиво закръгляне */
    private static function qtyDivRem($total, $unit)
    {
        if ($unit <= 0) return array(0, self::ffix($total));
        // избягваме плаващи грешки: работим с „фиксиран“ остатък
        $n = (int)floor(($total + 1e-9) / $unit);
        $rem = self::ffix($total - $n * $unit);
        return array($n, $rem);
    }

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

        // ако няма дефинирани опаковки (артикулът е "в брой"), броим директно по единички
        if (empty($packs)) {
            $diff = abs($s - $d);
            return $sec * $diff;   // време = брой бройки * време за 1 бр.
        }
        
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

        if ($sTemp > 0 || $dTemp > 0) { $sArr[$i] = $sTemp; $dArr[$i] = $dTemp; $pArr[$i] = 1; } else { $i--; }

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

            if ($try++ >= $maxTries) { wp($sArr, $dArr, $sI, $dI, $s, $d, $packs); break; }
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

    /** Мин. остатък (запазено – не „взема-цял-и-връща“ в новата логика) */
    private static function getMinKeepQty($storeId, $qInPallet)
    {
        if (!$storeId || $qInPallet <= 0) return 0.0;
        $pct = (float)store_Stores::fetchField($storeId, 'minKeepPct'); // 0..1
        if ($pct <= 0) return 0.0;
        if ($pct > 0.8) $pct = 0.8;
        return $qInPallet * $pct;
    }

    /** Цели палети (поддържа се за съвместимост) */
    public static function getFullPallets($pallets, &$quantityPerPallet = null)
    {
        if (!$quantityPerPallet) {
            $cnt = array();
            foreach ($pallets as $i => $iRec) $cnt[self::ffix($iRec->quantity)]++;
            arsort($cnt);
            $best = key($cnt);
            foreach ($cnt as $q => $n) if ($q != $best) unset($cnt[$q]);
            krsort($cnt);
            $best = key($cnt);
            if (isset($cnt[$best]) && $cnt[$best] >= 1) $quantityPerPallet = $best;
        }

        $res = array();
        if ($quantityPerPallet > 0) {
            foreach ($pallets as $i => $iRec1) {
                if (self::ffix($iRec1->quantity) >= $quantityPerPallet) $res[$i] = (float)$iRec1->quantity;
            }
        }
        return $res;
    }

    /** Нормализиране на float */
    private static function ffix($v, $precision = 6)
    {
        $eps = pow(10, -$precision);
        $v = round((float)$v, $precision, PHP_ROUND_HALF_UP);
        if (abs($v) < $eps) return 0.0;
        return $v;
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
