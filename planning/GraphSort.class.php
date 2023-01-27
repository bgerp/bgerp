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

    function topologicalSort($data) {
        $sorted = array();
        $visited = array();
        $graph = array();
        $n = count($data);
        foreach ($data as $key => $value) {
            $graph[$key] = $value;
            $visited[$key] = false;
        }

        foreach ($data as $key1 => $value) {
            if (!$visited[$key1]) {
                static::topologicalSortUtil($key1, $visited, $sorted, $graph);
            }
        }

        return array_keys($sorted);
    }

    private function topologicalSortUtil($v, &$visited, &$sorted, $graph) {
        $visited[$v] = true;
        if (!empty($graph[$v])) {
            foreach ($graph[$v] as $neighbor) {
                if (!$visited[$neighbor]) {
                    static::topologicalSortUtil($neighbor, $visited, $sorted, $graph);
                }
            }
        }
        $sorted[$v] = true;
    }





    public function act_Test()
    {
        requireRole('debug');

        //$arr = [ 1 => [2, 3], 2 => [3,4], 3 => [3,2], 4 => [] ];
        //$arr = array(1 => array(2, 3), 2 => array(3,4), 3 => array(1,2), 4 => array(1));
        $arr = array(1 => array(3), 2 => array(), 3 => array(4), 4 => array(2)) ;
        $arr = array(1 => array(), 2 => array(3), 3 => array(), 4 => array());
        //$res = self::sort($arr);
        $res1 = self::topologicalSort($arr);


        bp($res1, $arr);
    }
}
