<?php


/**
 * генератор за движения
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
        $form->input();
        
        $invArr = $payArr = array();
        
        if ($form->isSubmitted()) {
            $rec = $form->rec;
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
            
            $mArr2 = self::mainP2Q($p, $q, true);
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

        if (count($mArr2)) {
            $html .= '<h2>Движения НОВИ</h2>';
            $html .= ht::mixedToHtml($mArr2);
        }
        
        
        
        return "<div style='padding:10px;'>" . $html . '</div>';
    }


    public static function mainP2Q($p, $q, $isTest = false)
    {
        
        asort($p); asort($q);
        
        $res = ($isTest === false) ? self::p2q($p, $q) : self::p2qTEST($p, $q);
       
        uasort($res, function ($a, $b)  {
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
 
        return array_shift(array_slice($res, 0, 1));
    }


    public  static function p2qTEST($p, $q)
    {
        $res = $used = array();
        $z = 0;
        
        foreach($p as $i => $iP) {
           
            if($iP > 0 && !$used[$iP]) {
                $z++;
                //if($z > 7) break;
                $used[$iP] = true;
                $moves = array();
                $moves2 = array();
                $qNext = $q; $pNext = $p;
                $permut = array();
                
                $combi = self::addCombi($q);
                $combi = self::addCombi($q, $combi);
                $combi = self::addCombi($q, $combi);
               
                if($klist = $combi[$iP]) {
                    foreach(keylist::toArray($klist) as $k) {
                        $moves2[$i]['zones'][$k] = $qNext[$k];
                        
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
                                
                                $moves2[$i]['zones'][$j] = $pNext[$i];
                                
                                $moves["{$i}=>{$j}"] = $pNext[$i];
                                $qNext[$j] -= $pNext[$i];
                                $pNext[$i] = 0;
                            } else {
                                
                                $moves2[$i]['zones'][$j] = $qNext[$j];
                                
                                $moves["{$i}=>{$j}"] = $qNext[$j];
                                $pNext[$i] -= $qNext[$j];
                                $qNext[$j] = 0;
                            }
                        }
                        
                        if($pNext[$i] <= 0) break;
                    }
                    
                    /*
                     uksort($moves, function ($a, $b)  {
                     
                     list($pA, $gA) = explode('=>', $a);
                     list($pB, $gB) = explode('=>', $b);
                     if($gA > $gB) return 1;
                     if($gA < $gB) return -1;
                     if($gA == $gB) return 0;
                     });*/
                }
                
                if(count($moves2)) {
                    $moves2[$i]['quantity'] = $p[$i];
                    if($pNext[$i]) {
                        $moves2['to'] = $i;
                        $moves2['toQuantity'] = $pNext[$i];
                        $moves["ret {$i} "  ] = $pNext[$i];
                        $pNext[$i] = 0;
                    }
                }
                
                if(count($moves2)) {
                    
                    
                    $nextMovesArr =  self::p2qTEST($pNext, $qNext);
                    
                    //bp($pNext, $qNext, $nextMovesArr);
                    
                    if(count($nextMovesArr)) {
                        foreach($nextMovesArr as $m) {
                            
                            $res[] = $moves2 + $m;
                        }
                    } else {
                        $res[] = $moves2;
                    }
                }
            }
        }
        
        
        return $res;
    }
    
    
    
    /**
     * Връща масив от масиви. Вторите масиви, са движения, които изчепват или P или Q
     */
    public  static function p2q($p, $q)
    {
        $res = $used = array();
        $z = 0;
        
        foreach($p as $i => $iP) {

            if($iP > 0 && !$used[$iP]) {
                $z++;
                //if($z > 7) break;
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
                    $moves = array_merge(array("get {$i} "   => $p[$i]), $moves);
                    if($pNext[$i]) {
                        $moves["ret {$i} "  ] = $pNext[$i];
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
                //bp( $m,  '|'. $k . '|');
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

    
    public static function getMovementsArr($productId, $storeId, $packagingId, $allocatedPallets)
    {
        $res = array();
        bp($productId, $storeId, $packagingId, $allocatedPallets);
        foreach ($allocatedPallets as $obj){
            //bp($obj);
        }
         
        return $res;
       
    }
}