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
        $form->FLD('pallets', 'table(columns=pallet|quantity|createdOn,captions=Палет|Количество|Създаване,widths=8em|8em)', 'caption=Палети,mandatory');
        $form->FLD('zones', 'table(columns=zone|quantity,captions=Зона|Количество,widths=8em|8em)', 'caption=Зони,mandatory');
        $form->FLD('packagings', 'table(columns=packagingId|quantity,captions=Опаковка|Количество,widths=8em|8em)', 'caption=Опаковки,mandatory');
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
                    $p[] = (object) array(
                        'position'  => $key,
                        'quantity'  => $qVerbal,
                        'createdOn' => $pArr->createdOn[$i]
                    );
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
            $mArr = self::mainP2Q($p, $q, $packs, 0, 0, $storeId); // preferOldest се чете вътре от склада
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
     * @param array      $pallets
     * @param array      $zones
     * @param array      $packaging
     * @param float|null $volume
     * @param float|null $weight
     * @param int|null   $storeId
     * @param bool|null  $preferOldest ако е null, се чете от склада
     * @return array|false
     */
    public static function mainP2Q($pallets, $zones, $packaging = array(), $volume = null, $weight = null, $storeId = null, $preferOldest = null)
    {
        // Четене на preferOldest от склада, ако не е подаден
        if ($preferOldest === null) {
            $sid = $storeId ?: store_Stores::getCurrent();
            $preferOldest = self::getPreferOldest($sid);
        }

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

        // Най-често срещано количество на палет
        $quantityPerPallet = 0;
        static::getFullPallets($pallets, $quantityPerPallet);

        // Нормализиране на палетната опаковка
        $hasPalletPackaging = false;
        foreach ($packaging as &$packRec) {
            if ($packRec->packagingId == $palletId) {
                if ($quantityPerPallet && $packRec->quantity != $quantityPerPallet) {
                    $packRec->quantity = $quantityPerPallet;
                    $hasPalletPackaging = true;
                }
            }
        }
        if ($quantityPerPallet && !$hasPalletPackaging) {
            $packaging[] = (object) array('packagingId' => $palletId, 'quantity' => $quantityPerPallet);
        }

        // Масив с опаковките
        $packArr = array();
        foreach ($packaging as $pack) {
            $k = $pack->quantity * $scale;
            $packArr["{$k}"] = $pack->packagingId;
        }
        krsort($packArr);

        Mode::push('pickupStoreId', $storeId);

        // Подготвяме палетите
        $sumP = 0;
        $pArr = array();
        $maxAge = 0;
        foreach ($pallets as $id => $p) {
            if ($p->quantity > 0) {
                $pArr[$id] = $p->quantity * $scale;
                $sumP += $pArr[$id];
            }
            if ($preferOldest && isset($p->createdOn)) {
                $p->age = dt::daysBetween(dt::now(), $p->createdOn);
                $maxAge = max($maxAge, $p->age);
            } else {
                $p->age = 0;
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
        $fullPallets = array();

        // Колко е количеството в палет
        if (count($packArr)) {
            $qInPallet = max(array_keys($packArr));
        } else {
            $qInPallet = max($pArr);
        }

        // Цели палети
        if ($qInPallet) {
            foreach ($pArr as $pId => $pQ) {
                if ($pQ == $qInPallet) {
                    if ($preferOldest) {
                        $score = (int)$pallets[$pId]->age;
                        if (self::isFirstRow($pallets[$pId]->position)) {
                            $score -= $maxAge + 1; // наказание за първи ред
                        }
                    } else {
                        $score = self::isFirstRow($pallets[$pId]->position) ? -1 : 0;
                    }
                    $fullPallets[$pId] = $score;
                }
            }

            if (count($fullPallets)) {
                arsort($fullPallets);
                $fullPallets = array_keys($fullPallets);
                foreach ($zones as $zId => $zQ) {
                    if ($n = (floor($zQ / $qInPallet))) {
                        while (count($fullPallets) && $n > 0) {
                            $p = array_shift($fullPallets);

                            $res[] = (object) array(
                                'pallet'   => $pallets[$p]->position,
                                'quantity' => $qInPallet,
                                'zones'    => array($zId => $qInPallet),
                            );

                            $pArr[$p]   = self::ffix($pArr[$p] - $qInPallet);
                            if ($pArr[$p] <= 0) unset($pArr[$p]);

                            $zones[$zId] = self::ffix($zones[$zId] - $qInPallet);
                            if ($zones[$zId] <= 0) unset($zones[$zId]);

                            $n--;
                        }
                    }
                }
            }
        }

        $sumZ = array_sum($zones);

        // Всички комбинации на палети
        $cnt = count($pArr);
        $pCombi = array();
        while ($cnt-- > 0 && count($pCombi) < 20000) {
            $pCombi = self::addCombi($pArr, $pCombi, $sumZ);
        }

        // Филтриране на комбинациите
        $ages = array();
        foreach ($pCombi as $key => $q) {
            if ($q < $sumZ) {
                unset($pCombi[$key]);
                continue;
            }

            if (!$preferOldest) {
                continue;
            }

            $cpArr = explode('|', trim($key, '|'));
            $qMap = '|';
            $age = 0;
            foreach ($cpArr as $pId) {
                $qMap .= $pArr[$pId] . '|';
                if (isset($pallets[$pId]->age)) {
                    $age += $pallets[$pId]->age;
                }
            }

            if (isset($ages[$qMap]) && $ages[$qMap] >= $age) {
                unset($pCombi[$key]);
                continue;
            } else {
                $ages[$qMap] = $age;
            }
        }

        // Пермутации на зоните
        $permsZ = array();
        arsort($zones);
        $zoneKeys = array();
        arr::getPerms(array_keys($zones), $permsZ);
        foreach ($permsZ as $perm) {
            $zoneKeys[] = '|' . implode('|', $perm) . '|';
            if (count($zoneKeys) > 24) break;
        }

        // Избор на най-добър ход
        $bestMove = null;
        $bestRate = null;
        foreach ($pCombi as $cKey => $c) {
            foreach ($zoneKeys as $zKey) {
                $rate = 0;
                $move = self::moveGen($pArr, $zones, $cKey, $zKey, $rate, $packArr, $pallets, $qInPallet);
                if ($bestRate === null || $bestRate > $rate) {
                    $bestMove = $move;
                    $bestRate = $rate;
                }
            }
        }

        if (is_array($bestMove)) {
            foreach ($bestMove as $m) {
                // Пази изхода чист: няма празни движения
                if (empty($m->zones) && empty($m->ret)) continue;
                $res[] = $m;
            }
        }

        // Консолидация: слей движенията по палет → зона (за да няма по две/повече за един и същи ключ)
        $res = self::consolidateMoves($res);

        Mode::pop('pickupStoreId');
        return $res;
    }

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
     * Генерира движения за дадена комбинация палети/зони
     */
    private static function moveGen($p, $z, $pK, $zK, &$rate, $packs, $allPallets, $qInPallet)
    {
        static $timeGet, $timeGetA, $timeZone, $timeReturn;

        if (!isset($timeGet)) {
            $timeGet   = rack_Setup::get('TIME_GET');
            $timeGetA  = rack_Setup::get('TIME_GET_A');
            $timeZone  = rack_Setup::get('TIME_ZONE');
            $timeReturn= rack_Setup::get('TIME_RETURN');
        }

        // Склад и минимален остатък (процентно правило, 0..1; 0 = OFF)
        $sessionStoreId = Mode::get('pickupStoreId');
        if (!$sessionStoreId) {
            $sessionStoreId = store_Stores::getCurrent();
        }
        $minKeepQty = self::getMinKeepQty($sessionStoreId, $qInPallet);

        $moves = array();

        $pK = explode('|', trim($pK, '|'));
        $zK = explode('|', trim($zK, '|'));
        $i = 0;
        $rate = 0;

        foreach ($pK as $pI) {
            $pQ = (float)$p[$pI];
            if ($pQ <= 0) continue;

            $o = $moves[$i] = (object) array(
                'pallet'   => $allPallets[$pI]->position,
                'quantity' => $pQ,      // ще бъде пренастроено на реално "picked"
                'zones'    => array(),
                'pQ'       => $pQ,      // изходно количество на палета
            );

            foreach ($zK as $zI) {
                $zQ = (float)$z[$zI];
                if ($zQ <= 0) continue;
                if ($pQ <= 0) continue;

                // Наличност, достъпна за взимане (спазвайки минимума)
                $curPalletLeft = (float)$p[$pI];
                $available = $curPalletLeft;
                if ($minKeepQty > 0) {
                    $available = max(0.0, $curPalletLeft - $minKeepQty);
                }
                if ($available <= 0.0) continue;

                $q = min($zQ, $available);
                $q = self::ffix($q);
                if ($q <= 0) continue; // не добавяме ~0 по зоната

                $o->zones[$zI] = $q;

                // актуализираме източника и зоната
                $p[$pI] = self::ffix($p[$pI] - $q);
                $z[$zI] = self::ffix($z[$zI] - $q);
                $pQ     = $p[$pI];
                $zQ     = $z[$zI];

                if ($p[$pI] <= 0) unset($p[$pI]);
                if ($z[$zI] <= 0) unset($z[$zI]);
            }

            // реално взетото към зони
            $picked = self::ffix($o->pQ - $pQ);
            $o->quantity = $picked;

            // Ако няма взето към зони и няма „връщане“ – не пазим движение
            if ($picked <= 0 && empty($o->ret)) {
                unset($moves[$i]);
                continue;
            }

            if ($pQ) {
                // Ако връщаме над 1/3 от пълен палет, по-добре да вземем само нужното
                if (isset($qInPallet) && $pQ > $qInPallet / 3) {
                    $o->quantity = self::ffix($o->quantity); // вече е picked
                    $o->partial  = true;
                } else {
                    $p[$pI] = 0;
                    $o->ret = $pQ;
                    $o->retPos = $o->pallet;

                    $sessionStoreId = Mode::get('pickupStoreId');
                    if (!$sessionStoreId) {
                        wp('Форсиране на склад', $p, $z, $pK, $allPallets, $qInPallet);
                        $sessionStoreId = store_Stores::getCurrent();
                    }

                    $allowSmartRet = store_Stores::fetchField($sessionStoreId, 'allowSmartReturnPos');
                    if ($allowSmartRet == 'yes') {
                        if (isset($qInPallet)) {
                            foreach ($allPallets as $pallet) {
                                $pos = $pallet->position;
                                if (self::isFirstRow($pos) && $pallet->quantity > 0) {
                                    $maxLoad = self::getMaxLoad($pos);
                                    if ($pallet->quantity + $o->ret <= $qInPallet * $maxLoad) {
                                        $o->retPos = $pos;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                // Ако връщаме на същото място – по-добре да не връщаме, а да вземем по-малко
                if (isset($o->retPos) && ($o->retPos == $o->pallet) && $o->ret > 0) {
                    if (!isset($qInPallet) || $qInPallet != $o->quantity || $o->ret < 0.20 * $qInPallet || $o->ret > 0.80 * $qInPallet) {
                        $o->quantity = self::ffix($o->quantity - $o->ret);
                        // възстановяваме палета
                        $p[$pI] = $o->ret;
                        $o->ret = $o->retPos = null;
                    }
                }
            }

            $o->quantity = self::ffix($o->quantity);
            $i++;
        }

        // Оценка на движенията
        foreach ($moves as $m) {
            // прескачаме празни движения (без зони и без връщане)
            if (empty($m->zones) && empty($m->ret)) {
                continue;
            }

            // Вземане
            $rate += ($a = self::isFirstRow($m->pallet) ? $timeGetA : $timeGet);
            $m->timeTake = $a;

            // Броене от палета
            if ($m->pQ != $m->quantity) {
                $rate += ($a = self::timeToCount($m->pQ, $m->quantity, $packs));
                $m->timeCount = $a;
            }

            // Оставяне по зоните
            $q = $m->quantity;
            if (!empty($m->zones)) {
                foreach ($m->zones as $zI => $zQ) {
                    if ($zQ <= 0) continue;
                    $rate += $timeZone;
                    $m->zonesTimes[$zI] = $timeZone;
                    if ($q != $zQ) {
                        $rate += ($a = self::timeToCount($q, $zQ, $packs));
                        $m->zonesCountTimes[$zI] = $a;
                    }
                }
            }

            // Връщане
            if (!empty($m->ret)) {
                $rate += $timeReturn;
                $m->timeReturn = $timeReturn;
            }
        }

        $o->pallets = $p;
        return $moves;
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
            $sTemp = round($sTemp, 6);
            $dArr[$i] = (int)($dTemp / $pQ);
            $dTemp -= $dArr[$i] * $pQ;
            $dTemp = round($dTemp, 6);
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

            $m = round(min($sQ, $dQ), 6);

            if ($m > 0) {
                $sec = $sec / 1.8;
                $res += $sec * ($m / $pArr[$dI]);

                $sArr[$sI] -= $m / $pArr[$sI];
                $sArr[$sI] = round($sArr[$sI], 6);
                $dArr[$dI] -= $m / $pArr[$dI];
                $dArr[$dI] = round($dArr[$dI], 6);

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
     * Минимален остатък (брой единици) според ДРОБЕН дял (0..1) от пълен палет за склада.
     * 0 => правилото е изключено; горна граница 0.8 (80%), за да не блокира пик.
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
     * Чете preferOldest от склада; default TRUE (FIFO), освен ако изрично е 'no'
     */
    private static function getPreferOldest($storeId)
    {
        if (!$storeId) return true;
        $val = store_Stores::fetchField($storeId, 'preferOldest');
        return ($val !== 'no');
    }

    /**
     * Добавя комбинации с ключове/стойности от следващо ниво
     */
    private static function addCombi($arr, $combi = null, $limit = null)
    {
        foreach ($combi ? $combi : array('|' => 0) as $mK => $q) {
            if ($q >= $limit) continue;
            foreach ($arr as $k => $qK) {
                if ($q > 0 && $qK >= $limit) continue;
                if (strpos($mK, '|' . $k . '|') === false) {
                    $Q = $q + $qK;
                    $ind = $mK . $k . '|';
                    if (!isset($combi[$ind])) {
                        $combi[$ind] = $Q;
                    }
                }
            }
        }
        return $combi;
    }

    /**
     * Връща всички цели палети, ако има такива
     * Ако не се подаде параметъра за количество на цял палет,
     * намира целите палети чрез най-често срещаното количество
     */
    public static function getFullPallets($pallets, &$quantityPerPallet = null)
    {
        if (!$quantityPerPallet) {
            $cnt = array();
            foreach ($pallets as $i => $iRec) {
                $cnt[$iRec->quantity]++;
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
                if ($iRec1->quantity >= $quantityPerPallet) {
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
        $v = round((float)$v, $precision);
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