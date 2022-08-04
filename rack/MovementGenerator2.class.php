<?php


/**
 * Генератор на движения в палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
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

        $packOptions = array('' => '') + cat_UoM::getPackagingOptions() + cat_UoM::getUomOptions();
        $form->setFieldTypeParams('packagings', array('packagingId_opt' => $packOptions));
        $createdOnOpt = array(dt::addDays(-4), dt::addDays(-3), dt::addDays(-2), dt::addDays(-1), dt::addDays(1), dt::now());
        $createdOnOpt = array('' => '') + arr::make($createdOnOpt, true);
        $form->setFieldTypeParams('pallets', array('createdOn_opt' => $createdOnOpt));

        $form->toolbar = cls::get('core_Toolbar');
        $form->toolbar->addSbBtn('Изпрати');
        
        $rec = $form->input();
        
        $invArr = $payArr = array();
        
        if ($form->isSubmitted()) {
            $pArr = json_decode($rec->pallets);
            $qArr = json_decode($rec->zones);
            $packArr = json_decode($rec->packagings);
            $p = $q = $packs = array();

            foreach ($pArr->pallet as $i => $key) {
                if ($pArr->quantity[$i]) {
                    $qVerbal = core_Type::getByName('double')->fromVerbal($pArr->quantity[$i]);
                    $p[] = (object) array('position' => $key, 'quantity' => $qVerbal, 'createdOn' => $pArr->createdOn[$i]);
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

            $mArr = self::mainP2Q($p, $q, $packs, 0, 0);
        }
        
        $form->title = 'Генериране на движения по палети';
        
        $html = $form->renderHtml();
        
        if (countR($p)) {
            $html .= '<h2>Палети</h2>';
            $html .= ht::mixedToHtml($p);
        }
        
        if (countR($q)) {
            $html .= '<h2>Зони</h2>';
            $html .= ht::mixedToHtml($q);
        }
        
        if (countR($mArr)) {
            $html .= '<h2>Движения</h2>';
            $html .= ht::mixedToHtml($mArr, 5, 7);
        }
        
        $html = $this->renderWrapping($html);
        
        return $html;
    }
    
    
    /**
     * Входната точка на алгоритъма за изчисляване на движенията
     * 
     * @param array $pallets    Описание на наличните палети от търсения продукт
     *                              Масив с ключове id-та на палети (`f` за пода) сочещи към обекти:
     *                              о ->position  - Идентификатор на позицията
     *                              o ->quantity  - Количеството на позицията
     *                              o ->createdOn - Кога е създаден този палет
     *                              о ->maxVolume - Максимален обем за тази позиция, минус обема на други продукти на нея
     *                              о ->maxWeight - Максимално тегло на тази позиция, минус теглата на други продукти на нея
     * 
     * @param array $zones      Описание на потребностите в зоните
     *                              Масив [Име на зона] => [Количество]
     * 
     * @param array $packaging Масив с обекти `cat_products_Packagings` за дадения продукт
     *
     * @param float $volume    Обем на единица от продукта в литри
     *
     * @param float $weight    Тегло на единица продукт
     * 
     * @return array Генерираните движения или false в случай на грешка
     * 
     */
    public static function mainP2Q($pallets, $zones, $packaging = array(), $volume = null, $weight = null)
    { 
        // Сумарно колко трябва да доставим
        $sumZ = array_sum($zones); 
 
        // Ммножител за скалиране на количествата
        $scale = 1;
        
        // Ако имаме дробни количества в опаковки, умножаваме всичко по 1000
       /* foreach($packaging as $pack) {bp(fmod($pack->quantity * $scale, 1), $pack);
            while(fmod($pack->quantity * $scale, 1) > 0) {
                $scale *= 10;
            }
        } */
 
        
        // Изискването за твърде голямо скалиране се приема за грешка във входните данни
        if($scale > 1000000) {

            return false;
        }
        
        // Скалираме и зоните
        foreach ($zones as $zI => $zQ) {
            $zones[$zI] *= $scale;
        }
        
        // Сортираме опаковките
        asort($packaging);
 
        // Генерираме масива с опаковките
        $packArr = array();
        foreach($packaging as $pack) {
            $k = $pack->quantity * $scale;
            $packArr["{$k}"] = $pack->packagingId;
        }
        krsort($packArr);

        // Подготвяме данни свързани с палетите
        $sumP = 0;
        $pArr = array();
        $maxAge = 0;
        foreach($pallets as $id => $p) {
            
            // Масив само с количествата
            if($p->quantity > 0) {
                $pArr[$id] = $p->quantity * $scale;
            }

            // Сумарно колко имаме в склада?
            $sumP += $pArr[$id];
        
            // Определяме възрастта на всяки палет
            if(isset($p->createdOn)) {
                $p->age = dt::daysBetween(dt::now(), $p->createdOn);
                $maxAge = max($maxAge, $p->age);
            }
        }

        // Ако имаме недостиг, приоритизираме малките зони
        asort($zones);
      
        if ($sumZ > $sumP) {
            foreach ($zones as $zI => $zQ) {
                $sumP -= $zQ;
                if ($sumP < 0) {
                    $zones[$zI] += $sumP;
                    $sumP = 0;
                }
            }
        }
        
        // Масив за крайния резултат
        $res = array();
        $fullPallets = array();

        // Ако разполагаме с цели палети и в зоните има търсене за такива
        if(count($packArr)) {
            $qInPallet = max(array_keys($packArr));
        } else {
            $qInPallet = max($pArr);
        }
 
        if($qInPallet) {
            foreach($pArr as $pId => $pQ) {
                if($pQ == $qInPallet) {
                    $fullPallets[$pId] = (int) $pallets[$pId]->age;
                    if(self::isFirstRow($pallets[$pId]->position)) {
                        $fullPallets[$pId] -= $maxAge+1;
                    }
                }
            }

            if(count($fullPallets)) {
                arsort($fullPallets);
                $fullPallets = array_keys($fullPallets);
 
                foreach($zones as $zId => $zQ) {
                    if($n = (floor($zQ/$qInPallet))) {
                  
                        do {
                            // Вземаме най-горния елемент, генерираме движение и го махаме от наличните палети
                            $p = array_shift($fullPallets);
                            $res[] = (object) array(
                                'pallet' => $pallets[$p]->position,
                                'quantity' => $qInPallet,
                                'zones'  => array($zId => $qInPallet)
                                );
                            $pArr[$p] -= $qInPallet;
                            if($pArr[$p] == 0) {
                                unset($pArr[$p]);
                            }
                            $zones[$zId] -= $qInPallet;
                            if($zones[$zId] == 0) {
                                unset($zones[$zId]);
                            }
                            $n--;
                        } while(count($fullPallets) && $n > 0);
                    }
                }
            }
        }  

        $sumZ = array_sum($zones);

        // Правим всички комбинации на палети
        $cnt = count($pArr);
        $pCombi = array();
        while ($cnt-- > 0 && count($pCombi) < 20000) {
            $pCombi = self::addCombi($pArr, $pCombi, $sumZ);
        }
 
        // филтрираме масива с комбинациите
        $ages = array();
       
        foreach($pCombi as $key => $q) {
            // Махаме комбинациите, които са под общото количество в зоните
            if($q < $sumZ) {
                unset($pCombi[$key]);
                continue;
            }

            // Ако комбинацията е със същата последователност на количествата, като до сега
            // съществуваща, но не е с по-малка обща възраст на палетите, тогава я махаме
            $cpArr = explode('|', trim($key, '|'));
            $qMap = '|';
            $age = 0;
            foreach($cpArr as $pId) {
                $qMap .= $pArr[$pId] . '|';
                if(isset($pallets[$pId]->age)) {
                    $age += $pallets[$pId]->age;
                }
            }
  
            if(isset($ages[$qMap]) && $ages[$qMap] >= $age) {
                unset($pCombi[$key]);
                continue;
            } else {
                $ages[$qMap] = $age;
            }
        }

        // Генерираме пермутациите в последователността на зоните
        $permsZ = array();
        arsort($zones);
        arr::getPerms(array_keys($zones), $permsZ);
        foreach($permsZ as $perm) {
            $zoneKeys[] = '|' . implode('|', $perm) . '|';
            if(count($zoneKeys) > 24) break;
        }

        // За всяка комбинация на палети и зони, генерираме група движения
        $movements = array();
        $bestMove = null;
        $bestRate = null;
        foreach($pCombi as $cKey => $c) {
            foreach($zoneKeys as $zKey) {
                $rate = 0;
                $move = self::moveGen($pArr, $zones, $cKey, $zKey, $rate, $packArr, $pallets, $qInPallet);
                 $d[$cKey .'-' . $zKey] = (object) array('move' => $move, 'rate' => $rate);
                if($bestRate === null || $bestRate > $rate) {
                    $bestMove = $move;
                    $bestRate = $rate;
                }
            }
        }
 // bp($d);
        // Генерираме движенията за всяка група и изисляваме времето, което ще отнеме
        if(is_array($bestMove)){
            foreach($bestMove as $m) {
                $res[] = $m;
            }
        }
 
        return $res;
    }




    

    /**
     * Изчислява най-големият общ делител на $a и $b
     */
    public static function gcd($a, $b)
    {
        return ($a % $b) ? self::gcd($b, $a % $b) : $b;
    }


    /**
     * Проверява дали позицията е не първи ред
     */
    public static function isFirstRow($pos)
    {
        if($pos == rack_PositionType::FLOOR) return false;

        list($num, $row, ) = rack_PositionType::toArray($pos);
        $row = strtolower($row);

        if(!array_key_exists("{$num}|{$row}", static::$firstRowTo)){
            if($num){
                $storeId = store_Stores::getCurrent();
                static::$firstRowTo["{$num}|{$row}"] = strtolower(rack_Racks::fetchField(array('#storeId = [#1#] AND #num = [#2#]', $storeId, $num), 'firstRowTo'));
            } else {
                static::$firstRowTo["{$num}|{$row}"] = 'a';
            }
        }

        return $row <= static::$firstRowTo["{$num}|{$row}"];
    }
    
    
    /**
     * Генерира движение на база зададени кейлистове за палети и зони до пълни изчерпване
     */
    private static function moveGen($p, $z, $pK, $zK, &$rate, $packs, $allPallets, $qInPallet)
    {
        static $timeGet, $timeGetA, $timeZone, $timeReturn;

        if(!isset($timeGet)) {
            $timeGet = rack_Setup::get('TIME_GET');
            $timeGetA = rack_Setup::get('TIME_GET_A');
            $timeZone = rack_Setup::get('TIME_ZONE');
            $timeReturn = rack_Setup::get('TIME_RETURN');
        }
 
        $moves = array();
        
        $pK = explode('|', trim($pK, '|'));
        $zK = explode('|', trim($zK, '|'));
        $i = 0;
        $rate = 0;
 
        foreach ($pK as $pI) { 
            
            $pQ = (float) $p[$pI];  
            
            if ($pQ <= 0) continue;
 
            $o = $moves[$i] = (object) array('pallet' => $allPallets[$pI]->position, 'quantity' => $pQ, 'zones' => array(), 'pQ' => $pQ);

            foreach ($zK as $zI) {
                $zQ = (float) $z[$zI];  
                if ($zQ <= 0) {
                    continue;
                }
                if ($pQ <= 0) {
                    continue;
                }
                
                $q = min($zQ, $pQ);
                
                $o->zones[$zI] = $q;

                $pQ = $p[$pI] -= $q;
                $zQ = $z[$zI] -= $q;
                
                if ($p[$pI] == 0) {
                    unset($p[$pI]);
                }
                if ($z[$zI] == 0) {
                    unset($z[$zI]);
                }
            }

            if($pQ) {
                // Ако връщаме над 1/3 от пълен палет, по-добре да вземем само това, което ни трябва
                if(isset($qInPallet) && $pQ > $qInPallet/3) {
                    $o->quantity = $o->quantity - $pQ;
                    $o->partial  = true;
                } else {
                    $p[$pI] = 0;
                    $o->ret = $pQ;
                    $o->retPos = $o->pallet;

                    // Намираме най-добрата позиция за връщане на палет
                    // На първи ред с някаква предишна наличност
                    // На първи ред без предишна наличност
                    // Където и до сега си е бил
                    if(isset($qInPallet)) {
                        foreach($allPallets as $pallet) {
                            $pos = $pallet->position;
                            if(self::isFirstRow($pos) && $pallet->quantity > 0) {
                                $maxLoad = self::getMaxLoad($pos);
                                if($pallet->quantity + $o->ret <= $qInPallet * $maxLoad) {
                                    $o->retPos = $pos;
                                    break;
                                }
                            }
                        }
                    }
                }

                // Ако връщаме на същото място по-добре да не връщаме нищо, а да сме взели по-малко
                if(isset($o->retPos) && ($o->retPos == $o->pallet) && $o->ret > 0) {
                    // Todo: тук трябва да се оставят случаите, когато вземаме цял палет и връщаме между 25% и 75% от него
                    if(!isset($qInPallet) || $qInPallet != $o->quantity || $o->ret < 0.20 * $qInPallet || $o->ret > 0.80 * $qInPallet) {
                        $o->quantity = $o->quantity - $o->ret;
                        // Възстановяваме и палета
                        $p[$pI] = $o->ret;
                        $o->ret = $o->retPos = null;
                    }
                }
            } 
            $o->quantity = round($o->quantity, 6);
            $i++;
        }

        // Изчисляваме рейтинга на движенията
        foreach($moves as $m) {
            // Вземане от палета
            $rate += ($a = self::isFirstRow($m->pallet) ? $timeGetA : $timeGet);
            
            $m->timeTake = $a;

            // Броене от палета
            if($m->pQ != $m->quantity) {
                $rate += ($a = self::timeToCount($m->pQ, $m->quantity, $packs));
                $m->timeCount = $a;
            }
            
            $q = $m->quantity;
            // Оставяне по зоните
            foreach($m->zones as $zI => $zQ) {
                $rate += $timeZone;
                $m->zonesTimes[$zI] = $timeZone;
                if($q != $zQ) {
                    $rate += ($a = self::timeToCount($q, $zQ, $packs));
                    $m->zonesCountTimes[$zI] = $a;
                }
            }

            // Връщане
            if($o->ret) {
                $rate += $timeReturn;
                $m->timeReturn = $timeReturn;
            }
        }
        
        $o->pallets = $p;

        return $moves;
    }


    /**
     * Оценка на хода
     * От колко палета се взема?
     * Има ли остатък?
     * Колко преброявания на единици продук ще имаме?
     * 
     */
    private static function timeToCount($s, $d, $packs)
    {  
        set_time_limit(5);

        expect($pallet >= $q);
        
        $sec = rack_Setup::get('TIME_COUNT');

        krsort($packs);
         
        $sTemp = $s;
        $dTemp = $d;
        $i = 1;
        $p = $sArr = $dArr = array();
       
        foreach($packs as $pQ => $pI) {
            $sArr[$i] = (int) ($sTemp / $pQ);
            $sTemp -= $sArr[$i] * $pQ;
            $sTemp = round($sTemp, 6);
            $dArr[$i] = (int) ($dTemp / $pQ);
            $dTemp -= $dArr[$i] * $pQ;
            $dTemp = round($dTemp, 6);
            $pArr[$i] = $pQ;
            $i++;
        }
 
        if($sTemp > 0 || $dTemp > 0) {
            $sArr[$i] = $sTemp;
            $dArr[$i] = $dTemp;
            $pArr[$i] = 1;
        } else {
            $i--;
        }

        $sI = $dI = $i;

 //bp($sArr, $dArr, $pArr, $s, $d, $res);

        while($sI > 0 && $dI > 0) {
            $sQ = $sArr[$sI] * $pArr[$sI];
            $dQ = $dArr[$dI] * $pArr[$dI];

            // Отброяваме възможното
            $m = round(min($sQ, $dQ), 6);

            if($m > 0) {
                $sec = $sec/1.8;
                $res += $sec * ($m/$pArr[$dI]); 
 
            //bp($res, $sec, $m, $pArr[$dI]);
                $sArr[$sI] -= $m/$pArr[$sI];
                $sArr[$sI] = round($sArr[$sI], 6);
                $dArr[$dI] -= $m/$pArr[$dI];
                $dArr[$dI] = round($dArr[$dI], 6);

                // Ако разбутваме по-голяма опаковка, за по-малка в получателя, даваме наказание
                if($sI < $dI) {  
                    $res += $sec * 10; 
                }
            }

            // Ако източника недостига, преместваме индекса към по-голямата опаковка
            if($sArr[$sI] <= 0) {
                $sI--;
            }

            // Ако получателя е запълнен - преместваме към по-голямата опаковка
            if($dArr[$dI] <= 0) {
                $dI--;
            }
            
            // if($i++ > 10)  bp($sArr, $dArr, $sI, $dI);
        }

        //    bp($sArr, $dArr, $pArr, $s, $d, $res);

        return $res;
    }

    
 

    /**
     * Връща процента на максимално натоварване
     * Той отразява колко процента за дадения стелаж от пълен палет стока може да се натовари на една позиция
     */
    public static function getMaxLoad($pos)
    {
        $res = null;

        if($rack = (int) $pos) {
            $rRec = rack_Racks::fetch($rack);
            $res = $rRec->maxLoad;
        }

        if(!$res) {
            $res = 1;
        }

        return $res;
    }

    
    /**
     * Добавя комбинации с ключове/стойности от следващо ниво
     */
    private static function addCombi($arr, $combi = null, $limit = null)
    {
        foreach ($combi ? $combi : array('|' => 0) as $mK => $q) {
            if($q >= $limit) continue;
            foreach ($arr as $k => $qK) {
                if($q > 0 && $qK >= $limit) continue;
                if (strpos($mK, '|'. $k . '|') === false) {
                    $Q = $q + $qK;
                    $ind = $mK  . $k. '|';
                    if (!$combi[$ind]) {
                        $combi[$ind] = $Q;
                    }
                }
            }
        }
        
        return $combi;
    }


    
    
    /**
     * Връща всички цели палети, ако има такива
     * Ако не се подаде параметъра за количество на цял палет, се опитва да
     * намери целите палети, като палетите с най-често повтарящо се количество
     */
    public static function getFullPallets($pallets, &$quantityPerPallet = null)
    {
        if (!$quantityPerPallet) {
            $cnt = array();
            foreach ($pallets as $i => $iP) {
                $cnt[$iP]++;
            }
            
            arsort($cnt);
            $best = key($cnt);
            foreach($cnt as $q => $n) {
                if($q != $best) unset($ctn[$q]);
            }

            krsort($cnt);
            $best = key($cnt);

            if ($cnt[$best] > 1) {
                $quantityPerPallet = $best;
            }
        }
        
        $res = array();
        
        if ($quantityPerPallet > 0) {
            $res = array();
            foreach ($pallets as $i => $iP) {
                if ($iP >= $quantityPerPallet) {
                    $res[$i] = (float) $iP;  
                }
            }
        }
        
        return $res;
    }


    /**
     * Връща масив с генерирани движения, на базата на функцията rack_MovementGenerator::mainP2Q
     * 
     * @param array    $allocatedArr Масив с резултата от фунцията mainP2Q
     * @param int      $productId ID на продукта
     * @param int      $packagingId ID на опаковката
     * @param string   $batch Партида
     * @param int      $storeId ИД na склада
     * @param int|null $workerId - ид на потребител
     *
     * @return array
     */
    public static function getMovements($allocatedArr, $productId, $packagingId, $batch, $storeId, $workerId = null)
    {
        $res = array();
        if (!is_array($allocatedArr)) {
            
            return $res;
        }

        $packRec = cat_products_Packagings::getPack($productId, $packagingId);
        $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
        
        foreach ($allocatedArr as $obj) {
            $newRec = (object) array('productId' => $productId,
                'packagingId' => $packagingId,
                'storeId' => $storeId,
                'quantityInPack' => $quantityInPack,
                'state' => isset($workerId) ? 'waiting' : 'pending',
                'brState' => isset($workerId) ? 'pending' : 'null',
                'batch' => $batch,
                'workerId' => $workerId,
                'quantity' => $obj->quantity,
                'position' => $obj->pallet,
            );
            
            if ($palletRec = rack_Pallets::getByPosition($obj->pallet, $storeId, $productId)) {
                $newRec->palletId = $palletRec->id;
                $newRec->palletToId = $palletRec->id;
                $newRec->batch = $palletRec->batch;
                $newRec->positionTo = ($obj->retPos) ? $obj->retPos : $obj->pallet;
            } else {
                // Липсва палет в движението
                wp($allocatedArr, $productId, $packagingId, $batch);
            }
            
            if(!countR($obj->zones)){
                wp($allocatedArr, $productId, $packagingId, $batch);
                continue;
            }
            
            $zoneArr = array('zone' => array(), 'quantity' => array());
            foreach ($obj->zones as $zoneId => $zoneQuantity) {
                $zoneArr['zone'][] = $zoneId;
                $zoneArr['quantity'][] = $zoneQuantity / $quantityInPack ;
            }
            
            $TableType = core_Type::getByName('table(columns=zone|quantity,captions=Зона|Количество)');
            $newRec->zones = $TableType->fromVerbal($zoneArr);
            
            $res[] = $newRec;
        }
        
        return $res;
    }



}
