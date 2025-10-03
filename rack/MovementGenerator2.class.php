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
        $form->FLD('pallets', 'table(columns=pallet|quantity|createdOn|sysNo,captions=Палет|Количество|Създаване|Системен №,widths=8em|8em|8em|7em)', 'caption=Палети,mandatory');
        $form->FLD('zones', 'table(columns=zone|quantity,captions=Зона|Количество,widths=8em|8em)', 'caption=Зони,mandatory');
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
                        'createdOn' => $pArr->createdOn[$i]
                    );
                    if (!empty($pArr->sysNo[$i])) {
                        // за стратегия „Най-стария“ – най-малък системен №
                        $po->sysNo = (int)$pArr->sysNo[$i];
                    }
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
            $mArr = self::mainP2Q($p, $q, $packs, 0, 0, $storeId, null); // параметърът preferOldest е legacy и се игнорира
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
     * @param array      $pallets  масив от обекти {position, quantity, createdOn?, sysNo?}
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
        // (НОВО) Четене на стратегия за „цял палет“ – само от fullPalletStrategy
        $sid = $storeId ?: store_Stores::getCurrent();
        $strategy = self::getFullPalletStrategy($sid); // 'oldest' | 'lowest' | 'closest'

        $sumZ  = array_sum($zones);
        $scale = 1;

        if ($scale > 1000000) {
            return false;
        }

        // Скалиране + нормализация на зоните (без ~0 стойности)
        foreach ($zones as $zI => $zQ) {
            $zones[$zI] = self::ffix($zones[$zI] * $scale);
            if ($zones[$zI] <= 0) {
                unset($zones[$zI]);
            }
        }

        // Сортираме опаковките
        asort($packaging);
        $palletId = cat_UoM::fetchBySysId('pallet')->id;

        // --- ДЕФИНИЦИЯ ЗА „ЦЯЛ ПАЛЕТ“
        // 1) Опаковка „палет“ от артикула; иначе 2) най-голямото повтарящо се количество; иначе 3) най-голямото количество
        $qInPallet = self::computeFullPalletSize($pallets, $packaging, $palletId);

        // Масив с опаковките (за timeToCount)
        $packArr = array();
        foreach ($packaging as $pack) {
            $k = $pack->quantity * $scale;
            $packArr["{$k}"] = $pack->packagingId;
        }
        krsort($packArr);

        Mode::push('pickupStoreId', $storeId);

        // Подготвяме палетите
        $sumP = 0;
        $pArr = array();          // qty по индекс на подадения масив $pallets
        foreach ($pallets as $id => $p) {
            if ($p->quantity > 0) {
                $pArr[$id] = self::ffix($p->quantity * $scale);
                $sumP += $pArr[$id];
            }
            // помощни метрики
            $pallets[$id]->_rowCol = self::getRowCol($p->position); // ['row'=>'A', 'col'=>3]
            $pallets[$id]->_isFirstRow = self::isFirstRow($p->position);
            if (isset($p->createdOn)) {
                $pallets[$id]->_ageDays = dt::daysBetween(dt::now(), $p->createdOn);
            } else {
                $pallets[$id]->_ageDays = 0;
            }
        }

        // Ако имаме недостиг – приоритизиране на малките зони
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

        // --- ЕТАП A: ЦЕЛИ ПАЛЕТИ (3.1 + стратегия; „Първи ред до“ е с по-нисък приоритет при равенство)
        if ($qInPallet > 0) {
            $fullIdx = array();
            foreach ($pArr as $pId => $pQ) {
                if ($pQ >= $qInPallet) {
                    $fullIdx[] = $pId;
                }
            }
            usort($fullIdx, function ($a, $b) use ($pallets, $strategy) {
                return rack_MovementGenerator2::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, /*deprioFirstRow=*/true);
            });

            foreach ($zones as $zId => $zQ) {
                if ($zQ <= 0) continue;
                $need = (int)floor($zQ / $qInPallet);
                while ($need > 0 && !empty($fullIdx)) {
                    $pId = array_shift($fullIdx);
                    $take = min($qInPallet, $pArr[$pId]);
                    if ($take <= 0) continue;

                    $res[] = (object) array(
                        'pallet'   => $pallets[$pId]->position,
                        'quantity' => self::ffix($take),
                        'zones'    => array($zId => self::ffix($take)),
                        'pQ'       => self::ffix($pArr[$pId]),
                    );

                    $pArr[$pId] = self::ffix($pArr[$pId] - $take);
                    $zones[$zId] = self::ffix($zones[$zId] - $take);

                    if ($pArr[$pId] >= $qInPallet) {
                        $fullIdx[] = $pId;
                    }
                    $need--;
                }
            }
        }

        // --- ЕТАП B: ОСТАТЪЧНИ / МАЛКИ КОЛИЧЕСТВА (3.2 и 4)
        foreach ($zones as $zId => $zQ) {
            $rem = self::ffix($zQ);
            if ($rem <= 0) continue;

            // работни списъци
            $brokenIdx = array();
            $firstRowIdx = array();
            $fullIdxNow = array();

            foreach ($pArr as $pId => $pQ) {
                if ($pQ <= 0) continue;
                if ($qInPallet > 0 && $pQ >= $qInPallet) {
                    $fullIdxNow[] = $pId;
                } else {
                    $brokenIdx[] = $pId;
                }
                if ($pallets[$pId]->_isFirstRow) {
                    $firstRowIdx[] = $pId;
                }
            }

            // сортиране по стратегия
            usort($brokenIdx, function ($a, $b) use ($pallets, $strategy) {
                return rack_MovementGenerator2::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, /*deprioFirstRow=*/false);
            });
            usort($firstRowIdx, function ($a, $b) use ($pallets, $strategy) {
                return rack_MovementGenerator2::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, /*deprioFirstRow=*/false);
            });
            usort($fullIdxNow, function ($a, $b) use ($pallets, $strategy) {
                return rack_MovementGenerator2::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, /*deprioFirstRow=*/true);
            });

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
                $rem = self::ffix($rem - $take);
            }

            if ($rem > 0) {
                // 3.2.2: разбутан(<rem) + първи ред до (ако общо стигат)
                $bestUnder = null; $bestUnderQty = 0.0;
                foreach ($brokenIdx as $pId) {
                    $q = self::ffix($pArr[$pId]);
                    if ($q <= 0 || $q >= $rem) continue;
                    if ($q > $bestUnderQty) { $bestUnderQty = $q; $bestUnder = $pId; }
                }
                if ($bestUnder !== null) {
                    $frSum = 0.0;
                    foreach ($firstRowIdx as $frId) { $frSum += self::ffix($pArr[$frId]); }
                    if ($frSum + $bestUnderQty >= $rem) {
                        $take1 = $bestUnderQty;
                        $res[] = (object) array(
                            'pallet'   => $pallets[$bestUnder]->position,
                            'quantity' => $take1,
                            'zones'    => array($zId => $take1),
                            'pQ'       => self::ffix($pArr[$bestUnder]),
                        );
                        $pArr[$bestUnder] = 0.0;
                        $rem = self::ffix($rem - $take1);

                        foreach ($firstRowIdx as $frId) {
                            if ($rem <= 0) break;
                            $q = self::ffix($pArr[$frId]);
                            if ($q <= 0) continue;
                            $take2 = min($q, $rem);
                            $res[] = (object) array(
                                'pallet'   => $pallets[$frId]->position,
                                'quantity' => $take2,
                                'zones'    => array($zId => $take2),
                                'pQ'       => self::ffix($pArr[$frId]),
                            );
                            $pArr[$frId] = self::ffix($pArr[$frId] - $take2);
                            $rem = self::ffix($rem - $take2);
                        }
                    }
                }
            }

            if ($rem > 0) {
                // 3.2.3: два разбутани, които >= rem (минимален overshoot)
                $pair = null; $bestOvershoot = null;
                $bList = $brokenIdx;
                $n = count($bList);
                for ($i=0; $i<$n; $i++) {
                    $id1 = $bList[$i]; $q1 = self::ffix($pArr[$id1]);
                    if ($q1 <= 0 || $q1 >= $rem) continue;
                    for ($j=$i+1; $j<$n; $j++) {
                        $id2 = $bList[$j]; $q2 = self::ffix($pArr[$id2]);
                        if ($q2 <= 0) continue;
                        $sum = self::ffix($q1 + $q2);
                        if ($sum >= $rem) {
                            $overshoot = $sum - $rem;
                            if ($bestOvershoot === null || $overshoot < $bestOvershoot) {
                                $bestOvershoot = $overshoot;
                                $pair = array($id1, $id2);
                                if ($overshoot == 0.0) break 2;
                            }
                        }
                    }
                }
                if ($pair) {
                    foreach ($pair as $pid) {
                        if ($rem <= 0) break;
                        $q = self::ffix($pArr[$pid]);
                        if ($q <= 0) continue;
                        $take = min($q, $rem);
                        $res[] = (object) array(
                            'pallet'   => $pallets[$pid]->position,
                            'quantity' => $take,
                            'zones'    => array($zId => $take),
                            'pQ'       => self::ffix($pArr[$pid]),
                        );
                        $pArr[$pid] = self::ffix($pArr[$pid] - $take);
                        $rem = self::ffix($rem - $take);
                    }
                }
            }

            if ($rem > 0) {
                // 3.2.4: само „първи ред до“, ако стига
                $frSum = 0.0;
                foreach ($firstRowIdx as $frId) { $frSum += self::ffix($pArr[$frId]); }
                if ($frSum >= $rem) {
                    foreach ($firstRowIdx as $frId) {
                        if ($rem <= 0) break;
                        $q = self::ffix($pArr[$frId]);
                        if ($q <= 0) continue;
                        $take = min($q, $rem);
                        $res[] = (object) array(
                            'pallet'   => $pallets[$frId]->position,
                            'quantity' => $take,
                            'zones'    => array($zId => $take),
                            'pQ'       => self::ffix($pArr[$frId]),
                        );
                        $pArr[$frId] = self::ffix($pArr[$frId] - $take);
                        $rem = self::ffix($rem - $take);
                    }
                }
            }

            if ($rem > 0) {
                // 3.2.5: разбутан според стратегията (най-добър single pick)
                $bestBroken = null; $bestBrokenScore = null;
                foreach ($brokenIdx as $pId) {
                    $q = self::ffix($pArr[$pId]);
                    if ($q <= 0) continue;
                    $delta = abs($rem - $q);
                    $score = $delta * 1000 + self::strategyTieScore($pallets[$pId], $strategy);
                    if ($bestBrokenScore === null || $score < $bestBrokenScore) {
                        $bestBrokenScore = $score; $bestBroken = $pId;
                    }
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
                    $rem = self::ffix($rem - $take);
                }
            }

            if ($rem > 0 && $qInPallet > 0) {
                // 4) Частично от цял палет — първо от „първи ред до“, иначе по стратегия
                $fullFirstRow = array_values(array_filter($fullIdxNow, function($pid) use ($pallets){ return $pallets[$pid]->_isFirstRow; }));
                $sourceList = !empty($fullFirstRow) ? $fullFirstRow : $fullIdxNow;

                foreach ($sourceList as $pid) {
                    if ($rem <= 0) break;
                    $q = self::ffix($pArr[$pid]);
                    if ($q <= 0) continue;
                    $take = min($q, $rem);
                    $res[] = (object) array(
                        'pallet'   => $pallets[$pid]->position,
                        'quantity' => $take,
                        'zones'    => array($zId => $take),
                        'pQ'       => self::ffix($pArr[$pid]),
                        'partial'  => true,
                    );
                    $pArr[$pid] = self::ffix($pArr[$pid] - $take);
                    $rem = self::ffix($rem - $take);
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

    /**
     * Взема стратегията от склада:
     * - 'oldest' | 'lowest' | 'closest'
     * Без fallback към preferOldest.
     */
    private static function getFullPalletStrategy($storeId)
    {
        $val = $storeId ? store_Stores::fetchField($storeId, 'fullPalletStrategy') : null;
        return in_array($val, array('oldest','lowest','closest'), true) ? $val : 'oldest';
    }

    /**
     * Композитен компаратор за сортиране според „Стратегия за цял палет“
     * $deprioFirstRow=true -> редовете „Първи ред до“ са с най-нисък приоритет при равенство (за цели палети)
     */
    private static function cmpByStrategy($a, $b, $strategy, $deprioFirstRow)
    {
        $ar = $a->_rowCol ?: self::getRowCol($a->position);
        $br = $b->_rowCol ?: self::getRowCol($b->position);

        if ($strategy === 'oldest') {
            $as = isset($a->sysNo) ? (int)$a->sysNo : PHP_INT_MAX;
            $bs = isset($b->sysNo) ? (int)$b->sysNo : PHP_INT_MAX;
            if ($as != $bs) return ($as < $bs) ? -1 : 1; // по-малък sysNo е „по-стар“
        } elseif ($strategy === 'lowest') {
            if ($ar['row'] != $br['row']) return strcmp($ar['row'], $br['row']);               // по-нисък ред (A<B<C...)
            if ((int)$ar['col'] != (int)$br['col']) return ((int)$ar['col'] < (int)$br['col']) ? -1 : 1; // tie -> по-малка колона
        } else { // 'closest'
            if ((int)$ar['col'] != (int)$br['col']) return ((int)$ar['col'] < (int)$br['col']) ? -1 : 1; // по-близка колона
            if ($ar['row'] != $br['row']) return strcmp($ar['row'], $br['row']);               // tie -> по-нисък ред
        }

        if ($deprioFirstRow) {
            $af = !empty($a->_isFirstRow);
            $bf = !empty($b->_isFirstRow);
            if ($af != $bf) return $af ? 1 : -1; // firstRow назад
        }

        return strcmp((string)$a->position, (string)$b->position);
    }

    /**
     * Скалярен „score“ за tie-break при селекция на single разбутан (3.2.5)
     */
    private static function strategyTieScore($p, $strategy)
    {
        $rc = $p->_rowCol ?: self::getRowCol($p->position);
        if ($strategy === 'oldest') {
            return isset($p->sysNo) ? (int)$p->sysNo : PHP_INT_MAX;
        } elseif ($strategy === 'lowest') {
            return ord($rc['row']) * 1000 + (int)$rc['col'];
        } else { // closest
            return (int)$rc['col'] * 1000 + ord($rc['row']);
        }
    }

    /**
     * Парсва позиция до ред/колона
     * Приема, че rack_PositionType::toArray($pos) връща [rackNum, rowLetter, colNumber]
     */
    private static function getRowCol($pos)
    {
        if ($pos == rack_PositionType::FLOOR) {
            return array('row' => 'Z', 'col' => 9999);
        }
        list($num, $row, $col) = rack_PositionType::toArray($pos);
        $row = strtoupper($row);
        $col = (int)$col;
        if (!$row) $row = 'Z';
        if (!$col) $col = 9999;
        return array('row' => $row, 'col' => $col);
    }

    /**
     * Изчислява номинала на „цял палет“
     */
    private static function computeFullPalletSize($pallets, $packaging, $palletId)
    {
        // 1) Ако има дефинирана опаковка „палет“
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
        foreach ($cnt as $q => $n) {
            if ($n >= 2) {
                if ($q > $bestRepeatQty) $bestRepeatQty = $q;
            }
        }
        if ($bestRepeatQty > 0) return $bestRepeatQty;

        // 3) Най-голямото количество на палетмясто
        $max = 0.0;
        foreach ($pallets as $iRec) {
            $q = self::ffix($iRec->quantity);
            if ($q > $max) $max = $q;
        }
        return $max;
    }

    /* ===================== Пренасяне на съществуващи помощни методи ===================== */

    /**
     * НОД
     */
    public static function gcd($a, $b)
    {
        return ($a % $b) ? self::gcd($b, $a % $b) : $b;
    }

    /**
     * Позицията е от първи ред?
     */
    public static function isFirstRow($pos)
    {
        if ($pos == rack_PositionType::FLOOR) return false;

        list($num, $row, ) = rack_PositionType::toArray($pos);
        $row = strtolower($row);

        if (!array_key_exists("{$num}|{$row}", static::$firstRowTo)) {
            if ($num) {
                $sessionStoreId = Mode::get('pickupStoreId');
                if (isset($sessionStoreId)) {
                    $storeId = $sessionStoreId;
                } else {
                    wp('Форсиране на склад', $pos);
                    $storeId = store_Stores::getCurrent();
                }
                static::$firstRowTo["{$num}|{$row}"] =
                    strtolower(rack_Racks::fetchField(array('#storeId = [#1#] AND #num = [#2#]', $storeId, $num), 'firstRowTo'));
            } else {
                static::$firstRowTo["{$num}|{$row}"] = 'a';
            }
        }

        return $row <= static::$firstRowTo["{$num}|{$row}"];
    }

    /**
     * Оценка на движенията – пренесена логика за time/count
     */
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
            $a = self::isFirstRow($m->pallet) ? $timeGetA : $timeGet;
            $m->timeTake = $a;

            // Броене от палета
            if (isset($m->pQ) && $m->pQ != $m->quantity) {
                $a = self::timeToCount($m->pQ, $m->quantity, $packs);
                $m->timeCount = $a;
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

            // Връщане (рядко в новата логика)
            if (!empty($m->ret)) {
                $m->timeReturn = $timeReturn;
            }
        }
    }

    /**
     * Оценка на броене/разопаковане
     */
    private static function timeToCount($s, $d, $packs)
    {
        $sec = rack_Setup::get('TIME_COUNT');
        krsort($packs);

        $sTemp = $s;
        $dTemp = $d;
        $i = 1;
        $pArr = $sArr = $dArr = array();

        foreach ($packs as $pQ => $pI) {
            $sArr[$i] = (int)($sTemp / $pQ);
            $sTemp -= $sArr[$i] * $pQ;
            $sTemp = round($sTemp, 6, PHP_ROUND_HALF_UP);
            $dArr[$i] = (int)($dTemp / $pQ);
            $dTemp -= $dArr[$i] * $pQ;
            $dTemp = round($dTemp, 6,PHP_ROUND_HALF_UP);
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

        $sI = $dI = $i;
        $maxTries = 10;
        $try = 1;
        $res = 0;

        while ($sI > 0 && $dI > 0) {
            $sQ = $sArr[$sI] * $pArr[$sI];
            $dQ = $dArr[$dI] * $pArr[$dI];

            $m = round(min($sQ, $dQ), 6, PHP_ROUND_HALF_UP);

            if ($m > 0) {
                $sec = $sec / 1.8;
                $res += $sec * ($m / $pArr[$dI]);

                $sArr[$sI] -= $m / $pArr[$sI];
                $sArr[$sI] = round($sArr[$sI], 6, PHP_ROUND_HALF_UP);
                $dArr[$dI] -= $m / $pArr[$dI];
                $dArr[$dI] = round($dArr[$dI], 6, PHP_ROUND_HALF_UP);

                if ($sI < $dI) { // по-голяма опаковка -> по-малка
                    $res += $sec * 10;
                }
            }

            if ($sArr[$sI] <= 0) $sI--;
            if ($dArr[$dI] <= 0) $dI--;

            if ($try++ >= $maxTries) {
                wp($sArr, $dArr, $sI, $dI, $s, $d, $packs);
                break;
            }
        }

        return $res;
    }

    /**
     * Максимално натоварване на позиция (бр. палети)
     */
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

    /**
     * Минимален остатък – запазено за съвместимост (новата логика по правило не „взема-цял-и-връща“)
     */
    private static function getMinKeepQty($storeId, $qInPallet)
    {
        if (!$storeId || $qInPallet <= 0) return 0.0;

        $pct = (float)store_Stores::fetchField($storeId, 'minKeepPct'); // 0..1
        if ($pct <= 0) return 0.0;
        if ($pct > 0.8) $pct = 0.8;

        return $qInPallet * $pct;
    }

    /**
     * Връща всички цели палети, ако има такива (не е критично в новия поток)
     */
    public static function getFullPallets($pallets, &$quantityPerPallet = null)
    {
        if (!$quantityPerPallet) {
            $cnt = array();
            foreach ($pallets as $i => $iRec) {
                $cnt[self::ffix($iRec->quantity)]++;
            }

            arsort($cnt);
            $best = key($cnt);
            foreach ($cnt as $q => $n) {
                if ($q != $best) unset($cnt[$q]);
            }

            krsort($cnt);
            $best = key($cnt);
            if (isset($cnt[$best]) && $cnt[$best] >= 1) {
                $quantityPerPallet = $best;
            }
        }

        $res = array();
        if ($quantityPerPallet > 0) {
            foreach ($pallets as $i => $iRec1) {
                if (self::ffix($iRec1->quantity) >= $quantityPerPallet) {
                    $res[$i] = (float)$iRec1->quantity;
                }
            }
        }

        return $res;
    }

    /**
     * Нормализиране на float: закръгля и клампва близо до 0
     */
    private static function ffix($v, $precision = 6)
    {
        $eps = pow(10, -$precision);
        $v = round((float)$v, $precision, PHP_ROUND_HALF_UP);
        if (abs($v) < $eps) return 0.0;
        return $v;
    }

    /**
     * Сливане на движения по ключ (палет → зона); quantity е сборът към зоните;
     * ret се акумулира, retPos запазва последната ненулева. Премахва празните.
     */
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

            // акумулирай по зони
            if (!empty($m->zones)) {
                foreach ($m->zones as $zId => $q) {
                    $q = self::ffix($q);
                    if ($q <= 0) continue;
                    if (!isset($byPallet[$pallet]->zones[$zId])) {
                        $byPallet[$pallet]->zones[$zId] = 0.0;
                    }
                    $byPallet[$pallet]->zones[$zId] = self::ffix($byPallet[$pallet]->zones[$zId] + $q);
                }
            }

            // ret/retPos
            if (!empty($m->ret)) {
                $byPallet[$pallet]->ret = self::ffix((float)$byPallet[$pallet]->ret + (float)$m->ret);
                if (!empty($m->retPos)) {
                    $byPallet[$pallet]->retPos = $m->retPos;
                }
            }
        }

        // финализиране
        $out = array();
        foreach ($byPallet as $p => $mm) {
            // чисти ~0 по зони
            foreach ($mm->zones as $zId => $q) {
                $q = self::ffix($q);
                if ($q <= 0) {
                    unset($mm->zones[$zId]);
                } else {
                    $mm->zones[$zId] = $q;
                }
            }

            // quantity = сбор към зоните
            $qty = 0.0;
            foreach ($mm->zones as $q) $qty += $q;
            $mm->quantity = self::ffix($qty);

            if (empty($mm->zones) && empty($mm->ret)) {
                continue;
            }

            $out[] = $mm;
        }

        return $out;
    }
}
