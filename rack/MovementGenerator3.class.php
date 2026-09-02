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
        $form->FLD('pallets', 'table(columns=pallet|quantity|createdOn,captions=Палет|Количество|Създаване,widths=8em|8em|8em)', 'caption=Палети,mandatory');
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
                if (!empty($pArr->quantity[$i])) {
                    $qVerbal = core_Type::getByName('double')->fromVerbal($pArr->quantity[$i]);
                    $po = (object) ['position' => $key, 'quantity' => $qVerbal, 'createdOn' => $pArr->createdOn[$i] ?? null];
                    $p[] = $po;
                }
            }
            foreach ($qArr->zone as $i => $key) {
                if (!empty($qArr->quantity[$i])) {
                    $qVerbal = core_Type::getByName('double')->fromVerbal($qArr->quantity[$i]);
                    $q[$key] = $qVerbal;
                }
            }
            foreach ($packArr->packagingId as $i => $key) {
                if (!empty($packArr->quantity[$i])) $packs[] = (object)['packagingId' => $key, 'quantity' => $packArr->quantity[$i]];
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

		// ===================== ЕТАП B: остатъци под цял палет =====================
		if ($qInPallet > 0) {

			// 1️⃣ Групиране на зоните по групи (rack_ZoneGroups)
			$zonesGrouped = self::groupZonesByZoneGroup($zones);
			$zonesAfterB  = array(); // натрупваме реалните остатъци след обработка

			foreach ($zonesGrouped as $groupId => $groupInfo) {

				$usedPallets = array();
				$zonesInGroup = $groupInfo['zones']; // [zoneId => qty]
				$groupTotal   = self::ffix($groupInfo['sum']);
				if ($groupTotal <= 0) continue;

				// „жив“ остатък по подзоните (намалява при всяко вземане)
				$groupRemZones = $zonesInGroup;

				// Алокатор към подзоните според оставащите им нужди
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

				// 2️⃣ Разбивка на груповото количество по цели палети + остатък
				list($groupFullCnt, $groupRem) = self::qtyDivRem($groupTotal, $qInPallet);


				// 3.1.1 – вземат се целите палети съгласно стратегията,
				// като се игнорират (пропускат) палетите на редове,
				// избрани за всеки стелаж като "Първи ред до"
				$__guard = 0;
				while ($groupFullCnt > 0) {

					// предпазна скоба — за всеки случай
					if (++$__guard > 2000) {
						wrn('Прекъсване на 3.1.1 по защитна скоба (възможен безкраен цикъл)');
						break;
					}

					// само цели палети И НЕ на „Първи ред до“
					$fullIdxNow = array();
					foreach ($pArr as $pId => $pQ) {
						if ($pQ >= $qInPallet && !$pallets[$pId]->_isFirstRow) {
							$fullIdxNow[] = $pId;
						}
					}
					if (empty($fullIdxNow)) break;

					// сортиране по стратегия: oldest | lowest | closest
					$deprioFirstRow = ($strategy !== 'oldest');
					usort($fullIdxNow, function ($a, $b) use ($pallets, $strategy, $deprioFirstRow) {
						return rack_MovementGenerator3::cmpByStrategy(
							$pallets[$a],
							$pallets[$b],
							$strategy,
							$deprioFirstRow
						);
					});

					// първият по стратегия
					$pId = $fullIdxNow[0];

					$availFull = floor($pArr[$pId] / $qInPallet);
					if ($availFull <= 0) break;

					// вземаме максимум N цели палета от това място
					$takeFull = min($availFull, $groupFullCnt);
					$takeQty  = self::ffix($takeFull * $qInPallet);

					// разпределяме към подзоните
					$zonesSplit = $allocToZones($takeQty);
					if (!empty($zonesSplit)) {
						self::appendMove(
							$res,
							$pallets[$pId]->position,
							$takeQty,
							$zonesSplit,
							$pArr,
							'B.full_for_group'
						);
						$usedPallets[$pId] = true;
					}

					// актуализираме количествата
					$pArr[$pId] = self::ffix($pArr[$pId] - $takeQty);
					$groupFullCnt -= $takeFull;
					$groupTotal = self::ffix($groupTotal - $takeQty);
				}

				/* ────────────────────────────────
				 * 3.1.2 – Освобождаване на палетмества, от които вече сме взели цели палети
				 * Ако има остатъчно количество за заявката, и на същите палетмества е останало
				 * ≤ това количество, довземаме ги (зануляваме позициите).
				 * ──────────────────────────────── */
				if (!empty($usedPallets) && $groupTotal > 0) {
					foreach (array_keys($usedPallets) as $pid) {
						if ($pArr[$pid] > 0 && $pArr[$pid] <= $groupTotal) {
							$take = self::ffix($pArr[$pid]);
							$zonesSplit = $allocToZones($take);
							self::appendMove($res, $pallets[$pid]->position, $take, $zonesSplit, $pArr, 'B.cleanup_used');
							$groupTotal = self::ffix($groupTotal - $take);
							$pArr[$pid] = 0.0;
							if ($groupTotal <= 0) break;
						}
					}
				}

				/* ────────────────────────────────
				 * 3.1.3 – Преизчисляване на оставащото за групата
				 * ──────────────────────────────── */
				$remaining = self::ffix($groupTotal);
				if ($remaining <= 0) {
					foreach ($groupRemZones as $zId => $remQty) $zonesAfterB[$zId] = 0.0;
					continue;
				}

				/* ────────────────────────────────
				 * 3.2 – Малки / остатъчни количества
				 * ──────────────────────────────── */

				// Класификация на палетите
				$brokenIdx   = array(); // палети < qInPallet
				$firstRowIdx = array(); // палети на „Първи ред до“
				$fullIdxNow  = array();

				foreach ($pArr as $pId => $pQ) {
					if ($pQ <= 0) continue;
					if ($pQ >= $qInPallet) $fullIdxNow[] = $pId; else $brokenIdx[] = $pId;
					if ($pallets[$pId]->_isFirstRow) $firstRowIdx[] = $pId;
				}

				/* 3.2.1 – „Разбутан“ палет с точно търсеното количество */
				foreach ($brokenIdx as $pid) {
					if (abs(self::ffix($pArr[$pid] - $remaining)) < 1e-6) {
						$zonesSplit = $allocToZones($remaining);
						self::appendMove($res, $pallets[$pid]->position, $remaining, $zonesSplit, $pArr, 'B.broken_exact');
						$pArr[$pid] = 0.0;
						$remaining = 0.0;
						break;
					}
				}
				if ($remaining <= 0) continue;


				/* 3.2.2 – Нов алгоритъм за комбинация от „разбутани“ палети */
				$freeBroken = array_filter($brokenIdx, function($pid) use ($pallets) {
					return !$pallets[$pid]->_isFirstRow; // само редове ≠ „Първи ред до“
				});

				while ($remaining > 0 && !empty($freeBroken)) {

					// сортираме по количество (низходящо), при равенство – по стратегия
					usort($freeBroken, function ($a, $b) use ($pallets, $pArr, $strategy) {
						$qa = self::ffix($pArr[$a]);
						$qb = self::ffix($pArr[$b]);
						if ($qa != $qb) return ($qa > $qb) ? -1 : 1;
						return rack_MovementGenerator3::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, false);
					});

					$found = null;
					foreach ($freeBroken as $pid) {
						$q = self::ffix($pArr[$pid]);
						if ($q < $remaining) { $found = $pid; break; }
					}
					if ($found === null) break;

					// взимаме този палет
					$take = self::ffix($pArr[$found]);
					$zonesSplit = $allocToZones($take);
					self::appendMove($res, $pallets[$found]->position, $take, $zonesSplit, $pArr, 'B.broken_loop');
					$pArr[$found] = 0.0;
					$remaining = self::ffix($remaining - $take);

					// филтрираме взетите палети (вече не са „свободни“)
					$freeBroken = array_values(array_filter($freeBroken, function($pid) use ($found) {
						return $pid !== $found;
					}));

					// ако вече остатъкът е по-малък от най-малкия наличен, прекратяваме цикъла
					if (empty($freeBroken)) break;
					$minQ = min(array_map(function($pid) use ($pArr){ return self::ffix($pArr[$pid]); }, $freeBroken));
					if ($remaining < $minQ) break;
				}


				/* 3.2.4 – „Разбутан“ палет на „Първи ред до“, ако може да покрие остатъка */
				$firstRowBroken = array();
				foreach ($firstRowIdx as $frId) {
					$q = self::ffix($pArr[$frId]);
					if ($q > 0 && $q < $qInPallet) $firstRowBroken[] = $frId; // само „разбутани“ на first-row
				}

				$frBrokenSum = 0.0;
				foreach ($firstRowBroken as $frId) $frBrokenSum += self::ffix($pArr[$frId]);

				if ($remaining > 0 && !empty($firstRowBroken) && $frBrokenSum >= $remaining) {

					// 1) Ако има един „разбутан“ first-row палет, който сам може да покрие остатъка – взимаме от него
					$best = null; $bestScore = null;
					foreach ($firstRowBroken as $pid) {
						$q = self::ffix($pArr[$pid]);
						if ($q <= 0) continue;
						if ($q >= $remaining) {
							$delta = self::ffix($q - $remaining); // колко ще остане (по-малко е по-добре)
							$score = $delta * 1000 + self::strategyTieScore($pallets[$pid], $strategy);
							if ($bestScore === null || $score < $bestScore) {
								$bestScore = $score;
								$best = $pid;
							}
						}
					}

					if ($best !== null) {
						$zonesSplit = $allocToZones($remaining);
						self::appendMove($res, $pallets[$best]->position, $remaining, $zonesSplit, $pArr, 'B.fr_broken_cover');
						$pArr[$best] = self::ffix($pArr[$best] - $remaining);
						$remaining   = 0.0;
					} else {
						// 2) Иначе – комбинираме само „разбутани“ first-row палети (по количество низходящо, при равенство – по стратегия)
						usort($firstRowBroken, function ($a, $b) use ($pallets, $pArr, $strategy) {
							$qa = self::ffix($pArr[$a]);
							$qb = self::ffix($pArr[$b]);
							if ($qa != $qb) return ($qa > $qb) ? -1 : 1;
							return rack_MovementGenerator3::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, false);
						});

						foreach ($firstRowBroken as $pid) {
							if ($remaining <= 0) break;
							$q = self::ffix($pArr[$pid]);
							if ($q <= 0) continue;
							$take = min($q, $remaining);
							$zonesSplit = $allocToZones($take);
							self::appendMove($res, $pallets[$pid]->position, $take, $zonesSplit, $pArr, 'B.fr_broken_combo');
							$pArr[$pid] = self::ffix($pArr[$pid] - $take);
							$remaining   = self::ffix($remaining - $take);
						}
					}
				}


				/* 3.2.5 – Един „разбутан“ по стратегия, ако остане малко количество */
				if ($remaining > 0) {
					$bestBroken = null; $bestBrokenScore = null;
					foreach ($brokenIdx as $pid) {
						if ($pallets[$pid]->_isFirstRow) continue;
						$q = self::ffix($pArr[$pid]);
						if ($q <= 0) continue;
						$delta = abs($remaining - $q);
						$score = $delta * 1000 + self::strategyTieScore($pallets[$pid], $strategy);
						if ($bestBrokenScore === null || $score < $bestBrokenScore) {
							$bestBrokenScore = $score; $bestBroken = $pid;
						}
					}
					if ($bestBroken !== null) {
						$take = min(self::ffix($pArr[$bestBroken]), $remaining);
						$zonesSplit = $allocToZones($take);
						self::appendMove($res, $pallets[$bestBroken]->position, $take, $zonesSplit, $pArr, 'B.broken_final');
						$pArr[$bestBroken] = self::ffix($pArr[$bestBroken] - $take);
						$remaining = self::ffix($remaining - $take);
					}
				}

				/* 3.2.6 – Ако все още остава → взимаме от „цял палет“ по стратегия */
				if ($remaining > 0) {
					$fullIdxNow = array();
					foreach ($pArr as $pId => $pQ)
						if ($pQ >= $qInPallet) $fullIdxNow[] = $pId;
					if (!empty($fullIdxNow)) {
						$deprioFirstRow = ($strategy !== 'oldest');
						usort($fullIdxNow, function ($a, $b) use ($pallets, $strategy, $deprioFirstRow) {
							return rack_MovementGenerator3::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, $deprioFirstRow);
						});
						$pid = $fullIdxNow[0];
						$take = min(self::ffix($pArr[$pid]), $remaining);
						$zonesSplit = $allocToZones($take);
						self::appendMove($res, $pallets[$pid]->position, $take, $zonesSplit, $pArr, 'B.full_partial');
						$pArr[$pid] = self::ffix($pArr[$pid] - $take);
						$remaining = self::ffix($remaining - $take);
					}
				}

				// Записваме остатъците от тази група
				foreach ($groupRemZones as $zId => $remQty) {
					$zonesAfterB[$zId] = isset($zonesAfterB[$zId])
						? self::ffix($zonesAfterB[$zId] + $remQty)
						: self::ffix($remQty);
				}
			}

			// Обновяваме $zones според реалните остатъци след всички групи
			$zones = array();
			foreach ($zonesAfterB as $zId => $v) {
				$v = self::ffix($v);
				if ($v > 0) $zones[$zId] = $v;
			}
		}


		// ===================== ЕТАП A (ЦЕЛИ ПАЛЕТИ) =====================
		Mode::push('pickupStoreId', $storeId);

		// 4.1 Подготовка
		if (!empty($zones) && $qInPallet > 0) {

			foreach ($zones as $zId => $zQty) {
				$zones[$zId] = self::ffix($zQty);
			}

			// Обработка на всяка зона
			foreach ($zones as $zoneId => $zoneQty) {

				if ($zoneQty <= 0) continue;

				$remaining = self::ffix($zoneQty);

				// Палети, които имат наличност
				$validPallets = array();
				foreach ($pArr as $pId => $pQty) {
					if ($pQty > 0) $validPallets[$pId] = $pQty;
				}

				// Кандидати за цели палети
				$fullIdx = array();
				foreach ($validPallets as $pid => $qty) {
					if ($qty >= $qInPallet) $fullIdx[] = $pid;
				}
				if (empty($fullIdx)) continue;

				// 4.2 Подреждане според избраната стратегия
				$deprioFirstRow = ($strategy !== 'oldest');
				usort($fullIdx, function($a, $b) use ($pallets, $strategy, $deprioFirstRow) {
					return rack_MovementGenerator3::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, $deprioFirstRow);
				});

				// 4.3 Раздаване на цели палети
				foreach ($fullIdx as $pId) {
					if ($remaining <= 0) break;

					$pallet = $pallets[$pId];
					$pQty   = self::ffix($pArr[$pId]);
					if ($pQty <= 0) continue;

					// Взимаме цели палети от този източник
					$takeFullCnt = floor($pQty / $qInPallet);
					if ($takeFullCnt <= 0) continue;

					$takeFullQty = self::ffix($takeFullCnt * $qInPallet);
					$take = min($takeFullQty, $remaining);

					// Изграждаме разпределение към зоната
					$zonesSplit = array($zoneId => self::ffix($take));
					if (!empty($zonesSplit)) {
						self::appendMove($res, $pallet->position, $take, $zonesSplit, $pArr, 'A.full');
					}

					$pArr[$pId] = self::ffix($pArr[$pId] - $take);
					$remaining   = self::ffix($remaining - $take);

					// Smart Return при остатък и не-първи ред
					if ($pArr[$pId] > 0 && !$pallet->_isFirstRow) {
						$smart = self::trySmartReturn($pallet, $pArr[$pId], $storeId, $qInPallet);
						if ($smart) $res[] = $smart;
					}
				}

				// Ако има остатък – взимаме частични количества
				if ($remaining > 0) {

					$brokenIdx = array();
					foreach ($validPallets as $pid => $qty) {
						if ($qty < $qInPallet && $qty > 0) $brokenIdx[] = $pid;
					}

					if (!empty($brokenIdx)) {
						usort($brokenIdx, function($a, $b) use ($pallets, $strategy) {
							return rack_MovementGenerator3::cmpByStrategy($pallets[$a], $pallets[$b], $strategy, false);
						});

						foreach ($brokenIdx as $pId) {
							if ($remaining <= 0) break;

							$pallet = $pallets[$pId];
							$take = min(self::ffix($pArr[$pId]), $remaining);
							if ($take <= 0) continue;

							$zonesSplit = array($zoneId => self::ffix($take));
							self::appendMove($res, $pallet->position, $take, $zonesSplit, $pArr, 'A.broken');

							$pArr[$pId] = self::ffix($pArr[$pId] - $take);
							$remaining   = self::ffix($remaining - $take);

							if ($pArr[$pId] > 0 && !$pallet->_isFirstRow) {
								$smart = self::trySmartReturn($pallet, $pArr[$pId], $storeId, $qInPallet);
								if ($smart) $res[] = $smart;
							}
						}
					}
				}
			}
		}

		// Финална обработка и връщане на резултатите
		Mode::pop('pickupStoreId');

		// 🔹 Финална проверка и обединяване на движенията
		self::evaluateMoves($res, $packArr, $pallets, $qInPallet);
		$res = self::consolidateMoves($res);

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
