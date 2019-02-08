<?php

class test_C extends core_Manager
{
    public function act_Do()
    {
        // Правим последователност от ХХХХ минути, започвайки от нова година
        // За всяка минута генерираме данни
        $res = array();
        $delta = 0;

        for($i = 0; $i < 10000; $i++) {

            //Каква е зададената температура
            $c['target'] = 23;

            // Каква е температурата отвън?
            $c['outside'] = self::getOutside($i);

            // Каква е новата температура вътре спрямо предходния запис
            $c['room'] = self::getRoom($c);

            // Прозореца отворен ли е?
            $c['window'] = self::getWindow($i);

            // Вратата отворена ли е?
            $c['door'] = self::getDoor($i);

            // Колко процента е отоплителната/охладителната мощност
            $c['power'] = self::getPower($c, $i);
            
            // Каква е температурата вътре в сградата
            $c['inside'] = 20;

            $res[] = $c;
            $delta += abs($c['room'] - $c['target']);
        }

      //  bp($delta, $res); //'
        
        $html = '';
        
        foreach($res as $c) {
            if($c['power']) {
                $bgcolor = '#ffffaa';
            } else {
                $bgcolor = '#ffffff';
            }
            $html .= "<tr bgcolor='{$bgcolor}'><td>{$c['room']}</td><td>{$c['outside']}</td><td>{$c['window']}</td><td>{$c['door']}</td></tr>";
        }

        $rtn = "<ul><li> Отклонение: {$delta}</li></ul>";

        $rtn .= "<table class='listTable'><tr><th>Стая</th><th>Навън</th><th>Прозорец</th><th>Врата</th></tr>" . $html . "</table>";

        return $rtn;
    }


    /**
     * Връща външната температура
     */
    public static function getOutside($i)
    {
        static $temp;

        if($i == 0) {
            $temp = 5;
        } else {
            $temp += rand(-20,20)/100;
            $temp = round($temp, 2);
            if($temp < -10) $temp = -10;
            if($temp > 35) $temp = 35;
        }

        return $temp;
    }


    /**
     *
     */
    public static function getRoom($c)
    {
        static $l1, $l2, $l3;

        if(!$l1) {
            $l1 = $l2 = $l3 = 20;

            return 20;
        }
        
        $r = $c['room'];


        // Обмяна с подовото
        self::thermoTrans($r, $l3, 1000, 1000, 600);
        
        
        // През стената и дограмата
        self::thermoTrans($r, $c['outside'], 1000, 100000, 180);

       // bp($r, $c['outside']);

        // Ако е отворен прозореца
        if($c['window']) {
            self::thermoTrans($r, $c['outside'], 1000, 100000, 400);
        }
        
        // Ако е отворена вратата
        if($c['door']) {
            self::thermoTrans($r, $c['inside'], 1000, 100000, 800);
        }

        // $l2 => $l3
        self::thermoTrans($l2,  $l3, 1000, 1000, 800);

        // $l1 => $l2
        self::thermoTrans($l1,  $l2, 1000, 1000, 800);

        // Подово => $l1
        if($c['power']) {
            $tFH = 45;
            self::thermoTrans($l1,  $tFH, 1000, 1000, 600);
        }

        return round($r, 4);

    }


    /**
     * Дали прозореца в този момент е отворен
     */
    public static function getWindow($i)
    {   
        static $open;

        if(!isset($open)) $open = 0;

        $h = floor($i / 60) % 24;
        
        if($open == 0) {
            if($h >= 8 && $h < 18) {
                if(($r = rand(1,100)) <= 12 ) {
                    $open = $r;
                }
            } else {
                 if(($r = rand(1,1000)) <= 12 ) {
                    $open = $r;
                }
            }
        }
        
        if($open > 0) {
            $open--;

            return 1;
        }

        return 0;
    }


    /**
     * Дали вратата в този момент е отворена
     */
    public static function getDoor($i)
    {   
        static $open;

        if(!isset($open)) $open = 0;

        $h = floor($i / 60) % 24;
        
        if($open == 0) {
            if($h >= 8 && $h < 18 ) {
                if(($r = rand(1,100)) <= 12) {
                    $open = $r;
                }
            } else {
                 if(($r = rand(1,2000)) <= 12) {
                    $open = $r;
                }
            }
        }
        
        if($open > 0) {
            $open--;

            return 1;
        }

        return 0;
    }


    /**
     * На каква мощност трябва да работи отоплението/охлаждането
     */
    public static function getPower($c, $i)
    {
        static $integ;

        static $last;

        static $lastFloat;

        static $lastI;

        static $lastRoom;

        static $deltaI;


        $d = $c['target'] - $c['room'];
        
        if(isset($lastRoom) && abs($d) < 1) {
            $d +=  -15 * ($c['room'] - $lastRoom);
        }
        
        $lastRoom = $c['room'];

        $resFloat = self::aprox($d, array(-1 => -1, 1 => 1));

        if($integ > $resFloat) {
            $resFloat = $integ;
        } else {
            $integ = $resFloat;
        }
        
        $integ += self::aprox($d, array(-5 => -0.3, 5 => 0.01));

         
        if($resFloat > 0) {
            
            if($last == 0 && ($i - $lastI) >= 5) {
                $deltaI = $i % 5;
            }

            $t = ($i - $deltaI) % 5 + 1;
            
            if($resFloat * 5 >= $t) {
                $res = 1;  
            } else {
                $res = 0;
            }
        } else {
            $res = 0;
        }

        if($res !== $last) {
            $last = $res;
            $lastI = $i;
        }


        return $res;
    }


    /**
     * Симулира топлинен трансфер между тела с температура $t1 и $t2, и специфичен топлинен капацитет $c1 и $c2 и мощност на пренасяне $w
     */
    public static function thermoTrans(&$t1, &$t2, $c1, $c2, $w)
    {
        $d = $t2 - $t1;

        $t1 = ($t1 * $c1 * 60 + $d * $w) / ($c1 * 60);

        $t2 = ($t2 * $c2 * 60 - $d * $w) / ($c2 * 60);
    }


    /**
     * Връща приблизителната стойност от картата на стойността
     */
    public static function aprox($x3, $mapInput = array())
    {
        if (is_scalar($mapInput)) {
            $n = func_num_args() - 1;
            expect($n % 2 == 0);
            expect($n >= 2);
            for ($i = 1; $i <= $n; $i += 2) {
                $map[func_get_arg($i)] = func_get_arg($i + 1);
            }
        } else {
            $map = $mapInput;
        }
        
        foreach ($map as $x2 => $y2) {
            if ($x2 == $x3) {
                
                return $y2;
            }
            if ($x2 > $x3) {
                if ($y1 && true) {
                    $b = ($y1 - $y2) / ($x1 - $x2);
                    
                    $a = $y1 - $x1 * $b;
                    
                    $y3 = $a + $b * $x3;
                    
                    return $y3;
                }
                
                return $y2;
            }
            $x1 = $x2;
            $y1 = $y2;
        }
        
        return $y2;
    }

}