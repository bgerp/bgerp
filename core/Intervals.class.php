<?php

/**
 * Помощен клас за работа с масиви от целочислени интервали
 * В частен случай може да се използва за времеви интервали, представени като 
 * timestamps
 */
class core_Intervals {
    
    /**
     * Масив с числовите интервали
     */
    private $data = array();
    
    /**
     * Добавя възможен числов интервеал. Ако началото му или краят му попада в съществуващ интервал - то те се обединяват
     */
    public function add($begin, $end)
    {
        expect($begin <= $end, $begin, $end);

        $new = array($begin, $end);
        
        // Бързо проверяваме дали не трябва да го сложим в края
        if(!count($this->data) || self::comp1($new, end($this->data)) === 1)  {
            $this->data[] = $new;

            return;
        }
 
        // Циклим по всички интервали
        $last = null;
        $first = null;
        foreach($this->data as $i => $int) {
 
            $c = self::comp1($new, $int);
            switch($c) {
                case 1: // $new > $int
                    $last = $i;
                    break;
                case -1:  // $new < $int
                    $first = $i;
                    break 2;
                default: // $new overlap $int
                    expect($c === 0);
                    $new = self::getUnion($new, $int);
             }
        }

        $this->data = $this->combine($last, array($new), $first);
    }


    /**
     * Изрязва подадения интервал време от наличните
     *
     */
    public function cut($begin, $end)
    {
        expect($begin <= $end, $begin, $end);

        $new = array($begin, $end);

        // Циклим по всички интервали
        $last = null;
        $first = null;
        $add = array();
        foreach($this->data as $i => $int) {
            $c = self::comp($new, $int);
            switch($c) {
                case 1: // $new > $int
                    $last = $i;
                    break;
                case -1:  // $new < $int
                    $first = $i;
                    break 2;
                default: // $new overlap $int
                    expect($c === 0, $c);
                    $add = array_merge($add, self::getDiff($int, $new));
             }
        }

        $this->data = $this->combine($last, $add, $first);
    }


    /**
     * Консумира посоченият интервал, като се среми да използва само интервали между $begin и $end
     * Връща масив с начало на консумацията и края й, или false в случай на неуспех
     *
     * @param int $duration             - продължителност в секунди
     * @param int|null $begin           - timestamp на от коя дата или null за без такава
     * @param int|null$end              - timestamp на до коя дата или null за без такава
     * @param int|null $interruptOffset - секунди, при прекъсване или null ако няма
     * @return array|false              - масив с начална и крайна дата или false ако не може да се сметне
     * @throws core_exception_Expect
     */
    public function consume($duration, $begin = null, $end = null, $interruptOffset = null)
    {
        if(isset($begin) && isset($end)) {
            expect($begin <= $end, $begin, $end);
        }

        $last = null;
        $first = null;
        $add = array();
        foreach($this->data as $i => $int) {
            // Ако края на интервала е по-малък от началото на разрешеното - пропускаме
            if(isset($begin) && $begin > $int[1] ) {
                $last = $i;
                continue;
            }

            // Ако началото на интервала е по-голямо от параметъра $end то спираме цикъла
            if(isset($end) && $end > $int[0] || $duration == 0) {
                $first = $i;
                break;
            }

            expect($duration > 0, $duration);
            
            // Масив за консумация, която ще бъде отрязана
            $new = array();

            // От къде можем да започнем да консумираме
            $new[0] = max(isset($begin) ? $begin : PHP_INT_MIN, $int[0]);

            // До къде можем да консумираме
            $new[1] = min(isset($end) ? $end : PHP_INT_MAX, $int[1], $new[0] + $duration - 1);

            $add = array_merge($add, self::getDiff($int, $new));

            $min = isset($min) ? min($min, $new[0]) : $new[0];
            $max = isset($max) ? max($max, $new[1]) : $new[1];

            $duration -= $new[1] - $new[0] + 1;
        }

        $this->data = $this->combine($last, $add, $first);

        if(isset($min) && isset($max)) {

            return array($min, $max);
        }

        return false;
    }


    /**
     * Връща интервалите, заключени в тази рамка
     */
    public function getFrame($begin, $end)
    {
        expect($begin <= $end, $begin, $end);
        $new = array($begin, $end);
        $sect = array();

        foreach($this->data as $i => $int) {
            $sect = array_merge($sect, self::getIntersect($new, $int));
        }

        return $sect;
    }



    /**
     * Връща интервала, който съдържа входната точка
     */
    public function getByPoint($x)
    {
        foreach($this->data as $i => $int) {
           if($x >= $int[0] && $x <= $int[1]) {

               return $int;
           }
        }

        return null;
    }


    /**
     * Връща общата сума на продължителността на всички интервали
     */
    public function getTotalSum()
    {
        $sum = 0;
        foreach($this->data as $i => $int) {
            $sum += $int[1] - $int[0];
        }
 
        return $sum;
    }




    /**
     * Комбинира резултата
     */
    private function combine($last, $add, $first) 
    {
        $res = array();

        // Добавяме началните интервали, ако има
        if(is_int($last)) {
            $res = array_slice($this->data, 0, $last+1); 
        }
        
        // Добавяме новите интервали
        $res = array_merge($res, $add);
        
        // Добавяме останалите до края интервали
        if(is_int($first)) {
            $res = array_merge($res, array_slice($this->data, $first)); 
        }

        return $res;
    }


    /**
     * Връща разликата между интервалите
     * @param array $a Основен интервал
     * @param array $b Интервал, който ще се премахне
     *
     * @return array
     */
    public static function getDiff($a, $b, $minLength = null)
    {
        $diff = array();
        if($b[0] > $a[0] && $b[1] < $a[1]) {
            if(!isset($minLength) || ($b[0] - $a[0]) >= $minLength) {
                $diff[] = array($a[0], $b[0]-1);
            }
            if(!isset($minLength) || ($a[1] - $b[1]) >= $minLength) {
                $diff[] = array($b[1]+1, $a[1]);
            }
        } elseif($b[0] <= $a[0] && $b[1] < $a[1]) {
            if(!isset($minLength) || ($a[1] - $b[1]) >= $minLength) {
                $diff[] = array($b[1]+1, $a[1]);
            }
        } elseif($b[0] > $a[0] && $b[1] >= $a[1]) {
            if(!isset($minLength) || ($b[0] - $a[0]) >= $minLength) {
                $diff[] = array($a[0], $b[0]-1);
            }
        } else {
            expect($b[0] <= $a[0] && $b[1] >= $a[1], $b, $a);
        }

        return $diff;
    }


    /**
     * Намира сечението между два интервала
     */
    public static function getIntersect($a, $b)
    {
        $res = array();
        $max = max($a[0], $b[0]);
        $min = min($a[1], $b[1]);

        if($max <= $min) {
            $res[] = array($max, $min);
        }

        return $res;
    }


    /**
     * Намира обедиднението между два интервала
     */
    public static function getUnion($a, $b)
    {
        $res = array();
        $min = min($a[0], $b[0]);
        $max = max($a[1], $b[1]);
  
        $res = array($min, $max);
    
        return $res;
    }


    /**
     * 1 ако $b > $a, -1 ako $a > $b, 0 ако се пресичат
     */
    public static function comp($a, $b)
    {
        return ($b[1] < $a[0]) - ($a[1] < $b[0]);
    }


    /**
     * Същото, но дава пресичане и само при докосване на интервалите
     */
    public function comp1($a, $b)
    {
        return ($b[1] < $a[0] - 1) - ($a[1] < $b[0] + 1);
    }
    

    /**
     * Връща масива с интервалите, като преобразува числата към дати, третирайки ги като timestamps
     */
    public function getDates()
    {
        foreach($this->data as $i => $int) {
            $res[$i] = array(dt::timestamp2Mysql($int[0]), dt::timestamp2Mysql($int[1]));
        }

        return $res;
    }
}