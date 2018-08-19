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
class rack_MovementGenerator extends core_Manager
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
     * Екшън за тест
     */
    public function act_Default()
    {
        requireRole('debug');
        $form = cls::get('core_Form');
        $form->FLD('pallets', 'table(columns=pallet|quantity,captions=Палет|Количество,widths=8em|8em)', 'caption=Палети,mandatory');
        $form->FLD('zones', 'table(columns=zone|quantity,captions=Зона|Количество,widths=8em|8em)', 'caption=Зони,mandatory');
        $form->FLD('smallZonesPriority', 'enum(yes=Да,no=Не)', 'caption=Приоритетност на малките количества->Избор');
        
        $form->toolbar = cls::get('core_Toolbar');
        $form->toolbar->addSbBtn('Изпрати');
        
        $rec = $form->input();
        
        $invArr = $payArr = array();
        
        if ($form->isSubmitted()) {
            $pArr = json_decode($rec->pallets);
            $qArr = json_decode($rec->zones);
            
            foreach ($pArr->pallet as $i => $key) {
                if ($pArr->quantity[$i]) {
                    $p[$key] = $pArr->quantity[$i];
                }
            }
            foreach ($qArr->zone as $i => $key) {
                if ($qArr->quantity[$i]) {
                    $q[$key] = $qArr->quantity[$i];
                }
            }
            
            
            $mArr = self::mainP2Q($p, $q, null, $rec->smallZonesPriority);
        }
        
        $form->title = 'Генериране на движения по палети';
        
        $html = $form->renderHtml();
        
        if (count($p)) {
            $html .= '<h2>Палети</h2>';
            $html .= ht::mixedToHtml($p);
        }
        
        if (count($q)) {
            $html .= '<h2>Зони</h2>';
            $html .= ht::mixedToHtml($q);
        }
        
        if (count($mArr)) {
            $html .= '<h2>Движения</h2>';
            $html .= ht::mixedToHtml($mArr);
        }
        
        $html = $this->renderWrapping($html);
        
        return $html;
    }
    
    
    /**
     * Входната точка на алгоритъма за изчисляване на движенията
     */
    public function mainP2Q($p, $z, $quantityPerPallet = null, $smallZonesPriority = false)
    {
        $smallZonesPriority = ($smallZonesPriority == 'yes' || $smallZonesPriority === true) ? true : false;
        
        asort($p);
        asort($z);
        
        $pOrg = $p;
        
        // Ако малките количества са с приоритет, в случай на недостиг - орязваме големите
        if ($smallZonesPriority) {
            $sumP = array_sum($p);
            $sumZ = array_sum($z);
            
            if ($sumZ > $sumP) {
                foreach ($z as $zI => $zQ) {
                    $sumP -= $zQ;
                    if ($sumP < 0) {
                        $z[$zI] += $sumP;
                        $sumP = 0;
                    }
                }
            }
        }
        
        $moves = array();
        
        do {
            $fullPallets = self::getFullPallets($p, $quantityPerPallet);
            $res = self::p2q($p, $z, $fullPallets);
            
            $moves = arr::combine($moves, $res);
            $i++;
            if ($i > 100) {
                // Ременна защита срещу безкраен цикъл;
                bp($res);
            }
        } while (count($res) > 0);
        
        
        $res = array();
        $i = 0;
        foreach ($moves as $m => $q) {
            list($l, $r) = explode('=>', $m);
            if ($l == 'get') {
                $i++;
                $res[$i] = new stdClass();
                $o = &$res[$i];
                $o->pallet = $r;
                $o->quantity = $q;
                $o->zones = array();
            }
            if ($l == $o->pallet) {
                $o->zones[$r] = $q;
            }
            if ($l == 'ret') {
                // Ако върнатото количество е над 80% от палета, приемаме, че е по-добре да вземем
                // само това, което ни трябва за зоните. Тук трябва да се проеми това ограничение по зададено максимално тегло
                // на вземането от палета, което може да стане ръчно. Функцията трябва да получава макс количество,
                // при което не се взема целия палет, а само необходимата част
                if ($q >= 0.8 * $o->quantity) {
                    $o->quantity = array_sum($o->zones);
                } else {
                    $o->ret = $q;
                    
                    // Къде да е върнат палета?
                    // Първо добавяме нулевите палети
                    foreach ($pOrg as $pI => $pQ) {
                        if (!isset($p[$pI])) {
                            $p[$pI] = 0;
                        }
                    }
                    
                    // Търси палет на първия ред, който има най-малко бройки
                    foreach ($p as $pI => $pQ) {
                        if (stripos($pI, 'a')) {
                            $qNew = $p[$pI] ? $p[$pI] : 0;
                            if (($quantityPerPallet && $quantityPerPallet > $pQ + $q) || ($pQ + $q < 1.3 * $q)) {
                                $o->retPos = $pI;
                                
                                // Ако връщаме към палет, който сега има 0 количество, повече, от колкото е имал в началото,
                                // то обединяваме движенията
                                if ($pQ == 0 && $pOrig <= $q) {
                                    foreach ($res as $id => $mv) {
                                        if ($mv->pallet == $pI) {
                                            foreach ($mv->zones as $zI => $zQ) {
                                                $o->zones[$zI] += $zQ;
                                                $o->ret -= $zQ;
                                            }
                                            break;
                                        }
                                    }
                                    unset($res[$id]);
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща масив от масиви. Вторите масиви, са движения, които изчепват или P или Q
     */
    public static function p2q(&$p, &$z, $fullPallets)
    {
        $moves = array();
        
        if (!count($p) || !count($z)) {
            
            return $moves;
        }
        
        asort($p);
        asort($z);
        
        $pCombi = array();
        $cnt = count($p);
        while ($cnt-- > 0 && count($pCombi) < 20000) {
            $pCombi = self::addCombi($p, $pCombi);
        }
        
        $zCombi = array();
        $cnt = count($z);
        while ($cnt-- > 0 && count($zCombi) < 20000) {
            $zCombi = self::addCombi($z, $zCombi);
        }
        
        // Вкарваме точните съответсвия
        foreach ($pCombi as $pQ => $pK) {
            if ($zK = $zCombi[$pQ]) {
                $moves = self::moveGen($p, $z, $pK, $zK);
                break;
            }
        }
        
        if (!count($moves)) {
            $zR = array_reverse($z, true);
            foreach ($fullPallets as $i => $pQ) {
                if ($pQ <= 0) {
                    continue;
                }
                foreach ($zR as $j => $zQ) {
                    if ($zQ >= $pQ) {
                        $moves["get=>{$i}"] = $pQ;
                        $moves["${i}=>{$j}"] = $pQ;
                        $z[$j] -= $p[$i];
                        unset($p[$i]);
                        if ($z[$j] == 0) {
                            unset($z[$j]);
                        }
                        break 2;
                    }
                }
            }
        }
        
        if (!count($moves)) {
            $kZ = '';
            $t = 0;
            $zR = array_reverse($z, true);
            foreach ($zR as $zI => $zQ) {
                $zK .= ($zK == '' ? '|' : '') .$zI . '|';
                $t += $zQ;
            }
            
            if ($t) {
                foreach ($pCombi as $pQ => $pK) {
                    if ($pQ >= $t) {
                        break;
                    }
                }
                
                $moves = self::moveGen($p, $z, $pK, $zK);
            }
        }
        
        return $moves;
    }
    
    
    /**
     * Генерира движение на база зададени кейлистове за палети и зони до пълни изчерпване
     */
    private static function moveGen(&$p, &$z, $pK, $zK)
    {
        $moves = array();
        
        $pK = explode('|', trim($pK, '|'));
        $zK = explode('|', trim($zK, '|'));
        
        foreach ($pK as $pI) {
            $pQ = $p[$pI];
            if ($pQ <= 0) {
                continue;
            }
            $moves["get=>{$pI}"] = $pQ;
            foreach ($zK as $zI) {
                $zQ = $z[$zI];
                if ($zQ <= 0) {
                    continue;
                }
                if ($pQ <= 0) {
                    continue;
                }
                
                $q = min($zQ, $pQ);
                
                $moves["{$pI}=>{$zI}"] = $q ;
                $pQ = $p[$pI] -= $q;
                $zQ = $z[$zI] -= $q;
                
                if ($p[$pI] == 0) {
                    unset($p[$pI]);
                }
                if ($z[$zI] == 0) {
                    unset($z[$zI]);
                }
            }
        }
        
        if ($pQ > 0) {
            $moves["ret=>{$pI}"] = $pQ;
        }
        
        return $moves;
    }
    
    
    /**
     * Добавя комбинации с ключове/стойности от следващо ниво
     */
    private static function addCombi($arr, $combi = null)
    {
        foreach ($combi ? $combi : array(0 => '|') as $mK => $m) {
            foreach ($arr as $k => $qK) {
                //bp( $m,  '|'. $k . '|');
                if (strpos($m, '|'. $k . '|') === false) {
                    $qnt = $mK + $qK;
                    if (!$combi[$qnt]) {
                        $combi[$qnt] = $m  . $k. '|';
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
            if ($cnt[$best] > 1) {
                $quantityPerPallet = $best;
            }
        }
        
        $res = array();
        
        if ($quantityPerPallet > 0) {
            $res = array();
            foreach ($pallets as $i => $iP) {
                if ($iP >= $quantityPerPallet) {
                    $res[$i] = $iP;
                }
            }
        }
        
        return $res;
    }
    
    
    public static function getMovements($allocatedArr, $productId, $packagingId, $storeId)
    {
        $res = array();
        if (!is_array($allocatedArr)) {
            
            return $res;
        }
        $cu = core_Users::getCurrent();
        $packRec = cat_products_Packagings::getPack($productId, $packagingId);
        $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
        
        foreach ($allocatedArr as $obj) {
            $newRec = (object) array('productId' => $productId,
                'packagingId' => $packagingId,
                'storeId' => $storeId,
                'quantityInPack' => $quantityInPack,
                'state' => 'pending',
                'workerId' => $cu,
                'quantity' => $obj->quantity,
                'position' => $obj->pallet,
            );
            
            if ($palletRec = rack_Pallets::getByPosition($obj->pallet, $storeId)) {
                $newRec->palletId = $palletRec->id;
                $newRec->palletToId = $palletRec->id;
                $newRec->positionTo = $obj->pallet;
            }
            
            expect(count($obj->zones), 'няма зони');
            $zoneArr = array('zone' => array(), 'quantity' => array());
            foreach ($obj->zones as $zoneId => $zoneQuantity) {
                $zoneArr['zone'][] = $zoneId;
                $zoneArr['quantity'][] = $zoneQuantity / $quantityInPack;
            }
            $TableType = core_Type::getByName('table(columns=zone|quantity,captions=Зона|Количество)');
            $newRec->zones = $TableType->fromVerbal($zoneArr);
            
            $res[] = $newRec;
        }
        
        return $res;
    }
}
