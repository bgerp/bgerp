<?php


/**
 * Библиотечен клас за сортиране на граф
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Milen Georgiev <milen@bags.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 */
class planning_GraphSort extends core_Mvc
{
    /**
     * Графът се представя като масив от масиви
     * Всеки ключ е ИД на елемент. Той сочи към масив от ИД-та на елементи, които са преди него
     * Връща масив с подредени ID-та 
     * Например:
     * [ 1 => [2, 3], 2 => [3,4], 3 => [3,2], 4 => [] ]
     */
    public static function sort($arr)
    {
        $res = array();
        
        while(count($arr)) {
            // Махаме всички ID-та от вътрешните масиви, които не са ключове в основния
            foreach($arr as $id => $precursor) {
                unset($precursor[$id]);
                foreach($precursor as $i => $x) {
                    if(!isset($arr[$x])) unset($arr[$id][$i]);
                }
            }
            
            // Сортираме масива, като елементите с най-малко предшественици са в началото
            uasort($arr, function ($a, $b) {return count($a) >= count($b) ? 1 : -1; });

            // Вземаме първия елемент за резултат
            $key = array_key_first($arr);
            $res[] = $key;
            unset($arr[$key]);
        }

        return $res;
    }


    public function act_Test()
    {
        $arr = [ 1 => [2, 3], 2 => [3,4], 3 => [3,2], 4 => [] ];

        $res = self::sort($arr);

        bp($res);
    }
}
