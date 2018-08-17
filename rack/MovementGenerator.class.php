<?php


/**
 * Генератор на движения в палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
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
        $form->FLD('pallets', 'table(columns=pallet|quantity,captions=Палет|Количество,widths=8em|8em)', 'caption=Палети');
        $form->FLD('zones', 'table(columns=zone|quantity,captions=Зона|Количество,widths=8em|8em)', 'caption=Зони');
        
        $form->toolbar = cls::get('core_Toolbar');
        $form->toolbar->addSbBtn('Изпрати');
        
        $rec = $form->input();
        
        $invArr = $payArr = array();
        
        if ($form->isSubmitted()) {
            $pArr = json_decode($rec->pallets);
            $qArr = json_decode($rec->zones);
            
            foreach($pArr->pallet as $i => $key) {
                if($pArr->quantity[$i]) {
                    $p[$key] = $pArr->quantity[$i];
                }
            }
            foreach($qArr->zone as $i => $key) {
                if($qArr->quantity[$i]) {
                    $q[$key] = $qArr->quantity[$i];
                }
            }
            
            
            $mArr = self::mainP2Q($p, $q);
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
    
    
    public static function mainP2Q($p, $q)
    {
        asort($p); asort($q);
        $variants = self::p2q($p, $q);
        
        uasort($variants, function ($a, $b)  {
            if(count($a) > count($b)) return 1;
            if(count($a) == count($b)) {
                $aSum = array_sum($a);
                $bSum = array_sum($b);
                
                if($aSum > $bSum) return 1;
                if($aSum < $bSum) return -1;
                if($aSum == $bSum) return 0;
                
            }
            if(count($a) < count($b)) return -1;
            
        });
            
        $moves = array_shift(array_slice($variants, 0, 1));
        $res = array();
        
        if(!is_array($moves)) return $res;
        
        $i = 0;
        foreach($moves as $m => $q) {
            list($l, $r) = explode('=>', $m);
            if($l == 'get') {
                $i++;
                $res[$i] = new stdClass();
                $o = &$res[$i];
                $o->pallet = $r;
                $o->quantity = $q;
                $o->zones = array();
            }
            if($l == $o->pallet) {
                $o->zones[$r] = $q;
            }
            if($l == 'ret') {
                // Ако върнатото количество е над 80% от палета, приемаме, че е по-добре да вземем
                // само това, което ни трябва за зоните. Тук трябва да се проеми това ограничение по зададено максимално тегло
                // на вземането от палета, което може да стане ръчно. Функцията трябва да получава макс количество,
                // при което не се взема целия палет, а само необходимата част
               if($q >= 0.8 * $o->quantity) {
                    $o->quantity = array_sum($o->zones);
               } else {
                    $o->ret = $q;
               }
            }
        }
            
        return $res;
    }
    
    
    /**
     * Връща масив от масиви. Вторите масиви, са движения, които изчепват или P или Q
     */
    private  static function p2q($p, $q)
    {
        $res = array();
        $z = 0;
        
        foreach($p as $i => $iP) {
            
            if($iP > 0 && !$used[$iP]) {
                $z++;
                if($z > 5) break;
                $used[$iP] = true;
                $moves = array();
                $qNext = $q; $pNext = $p;
                $permut = array();
                
                $combi = self::addCombi($q);
                $combi = self::addCombi($q, $combi);
                $combi = self::addCombi($q, $combi);
                
                if($klist = $combi[$iP]) {
                    foreach(keylist::toArray($klist) as $k) {
                        $moves["{$i}=>{$k}"] = $qNext[$k];
                        $pNext[$i] -= $qNext[$k];
                        $qNext[$k] = 0;
                    }
                    
                } else {
                    foreach($qNext as $j => $jQ) {
                        
                        if($jQ > 0) {
                            
                            $coef = (self::gcd($pNext[$i], $qNext[$j]) / $pNext[$i] + $pNext[$i] / $qNext[$j]) / 2;
                            
                            if($coef < 0.20) continue;
                            if($qNext[$j] >= $pNext[$i]) {
                                $moves["{$i}=>{$j}"] = $pNext[$i];
                                $qNext[$j] -= $pNext[$i];
                                $pNext[$i] = 0;
                            } else {
                                $moves["{$i}=>{$j}"] = $qNext[$j];
                                $pNext[$i] -= $qNext[$j];
                                $qNext[$j] = 0;
                            }
                        }
                        
                        if($pNext[$i] <= 0) break;
                    }
                    
                    
                    uksort($moves, function ($a, $b)  {
                        list($pA, $gA) = explode('=>', $a);
                        list($pB, $gB) = explode('=>', $b);
                        
                        
                        if($gA > $gB) return 1;
                        if($gA < $gB) return -1;
                        if($gA == $gB) return 0;
                    });
                }
                
                if(count($moves)) {
                    $moves = array_merge(array("get=>{$i}"   => $p[$i]), $moves);
                    if($pNext[$i]) {
                        $moves["ret=>{$i}"] = $pNext[$i];
                        $pNext[$i] = 0;
                    }
                }
                
                if(count($moves)) {
                    $nextMovesArr =  self::p2q($pNext, $qNext);
                    if(count($nextMovesArr)) {
                        foreach($nextMovesArr as $m) {
                            $res[] = array_merge($moves, $m);
                        }
                    } else {
                        $res[] = $moves;
                    }
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Добавя комбинации с ключове/стойности от следващо ниво
     */
    private static function addCombi($arr, $combi = NULL)
    {
        ksort($arr);
        
        foreach($combi ? $combi : array(0 => '|') as $mK => $m) {
            foreach($arr as $k => $qK) {
                
                if(strpos($m,  '|'. $k . '|') === FALSE) {
                    $qnt = $mK + $qK;
                    if(!$combi[$qnt]) {
                        $combi[$qnt] =  $m  . $k. '|';
                    }
                }
            }
        }
        
        return $combi;
    }
    
    
    /**
     * Връща най-големият общ делител на двете числа
     */
    private static function gcd($a,$b) {
        return ($a % $b) ? self::gcd($b, $a % $b) : $b;
    }
    
    
    public static function getMovements($allocatedArr, $productId, $packagingId, $storeId)
    {
        $res = array();
        if (!is_array($allocatedArr)) return $res;
        $cu = core_Users::getCurrent();
        $packRec = cat_products_Packagings::getPack($productId, $packagingId);
        $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
        
        foreach ($allocatedArr as $obj){
            $newRec  = (object)array('productId'      => $productId, 
                                     'packagingId'    => $packagingId, 
                                     'storeId'        => $storeId, 
                                     'quantityInPack' => $quantityInPack,
                                     'state'          => 'pending',
                                     'workerId'       => $cu,
                                     'quantity'       => $obj->quantity,
                                     'position'       => $obj->pallet,
            );
            
            if($palletRec = rack_Pallets::getByPosition($obj->pallet, $storeId)){
                $newRec->palletId = $palletRec->id;
                $newRec->palletToId = $palletRec->id;
                $newRec->positionTo = $obj->pallet;
            }
            
            expect(count($obj->zones), 'няма зони');
            $zoneArr = array('zone' => array(), 'quantity' => array());
            foreach ($obj->zones as $zoneId => $zoneQuantity){
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