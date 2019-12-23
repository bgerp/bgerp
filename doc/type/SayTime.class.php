<?php


/**
 * Клас 'store_type_SayTime' - тип за вербално време
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_type_SayTime extends type_Varchar
{
    /**
     * Параметър определящ максималната широчина на полето
     */
    public $maxFieldSize = 16;
    
    
    /**
     * Преобразуване на време от вербална стойност към представяне в минути
     *
     * @param string $timeStr
     *
     * @return array $timeInMins
     */
    public function fromVerbal($timeStr)
    {
        $timeStr = trim($timeStr);
        
        if ($timeStr == 'на момента') {
            $timeInMins['value'] = 0;
            
            return $timeInMins;
        }
        
        $timeStr = str_replace(' и ', ' ', $timeStr);
        
        // Init
        $weekInt = 0;
        $dayInt = 0;
        $hourInt = 0;
        $minInt = 0;
        
        // Седмица, седмици
        $weekPos = strpos($timeStr, 'седм');
        
        if ($weekPos) {
            $weekData = substr($timeStr, 0, $weekPos);
            
            if (preg_match('/[0-9]+/', $weekData, $matches)) {
                $weekInt = $matches[0];
            } else {
                $weekParse = false;
            }
            
            // Cut $timeStr
            $timeStr = substr($timeStr, $weekPos, strlen($timeStr) - $weekPos);
            
            // Намира първия интервал
            $intervalPos = strpos($timeStr, ' ');
            
            // Cut $timeStr
            $timeStr = substr($timeStr, $intervalPos + 1, strlen($timeStr) - $intervalPos - 1);
        } elseif ($weekPos === 0) {
            $weekParse = false;
        }
        
        // ENDOF Седмица, седмици
        
        // Ден, дена, дни
        $dayPos = strpos($timeStr, 'ден');
        
        if ($dayPos) {
            $dayData = substr($timeStr, 0, $dayPos);
            
            if (preg_match('/[0-9]+/', $dayData, $matches)) {
                $dayInt = $matches[0];
            } else {
                $dayParse = false;
            }
            
            // Cut $timeStr
            $timeStr = substr($timeStr, $dayPos, strlen($timeStr) - $dayPos);
            
            // Намира първия интервал
            $intervalPos = strpos($timeStr, ' ');
            
            // Cut $timeStr
            $timeStr = substr($timeStr, $intervalPos + 1, strlen($timeStr) - $intervalPos - 1);
        } elseif ($dayPos === 0) {
            $dayParse = false;
        } else {
            $dayPos = strpos($timeStr, 'дни');
            
            if ($dayPos) {
                $dayData = substr($timeStr, 0, $dayPos);
                
                if (preg_match('/[0-9]+/', $dayData, $matches)) {
                    $dayInt = $matches[0];
                } else {
                    $dayParse = false;
                }
                
                // Cut $timeStr
                $timeStr = substr($timeStr, $dayPos, strlen($timeStr) - $dayPos);
                
                // Намира първия интервал
                $intervalPos = strpos($timeStr, ' ');
                
                // Cut $timeStr
                $timeStr = substr($timeStr, $intervalPos + 1, strlen($timeStr) - $intervalPos - 1);
            } elseif ($dayPos === 0) {
                $dayParse = false;
            }
        }
        
        // ENDOF Ден, дена, дни
        
        // Час, часа
        $hourPos = strpos($timeStr, 'час');
        
        if ($hourPos) {
            $hourData = substr($timeStr, 0, $hourPos);
            
            if (preg_match('/[0-9]+/', $hourData, $matches)) {
                $hourInt = $matches[0];
            } else {
                $hourParse = false;
            }
            
            // Cut $timeStr
            $timeStr = substr($timeStr, $dayPos, strlen($timeStr) - $hourPos);
            
            // Намира първия интервал
            $intervalPos = strpos($timeStr, ' ');
            
            // Cut $timeStr
            $timeStr = substr($timeStr, $intervalPos + 1, strlen($timeStr) - $intervalPos - 1);
        } elseif ($hourPos === 0) {
            $hourParse = false;
        }
        
        // ENDOF Час, часа
        
        // Мин
        $minPos = strpos($timeStr, 'мин');
        
        if ($minPos) {
            $minData = substr($timeStr, 0, $minPos);
            
            if (preg_match('/[0-9]+/', $minData, $matches)) {
                $minInt = $matches[0];
            } else {
                $minParse = false;
            }
        } elseif ($minPos === 0) {
            $minParse = false;
        }
        
        // ENDOF Мин
        
        // Ако има зададена седмица/ден/час/минути, но не може да се определи int стойността
        if ($weekPos === false && $dayPos === false && $hourPos === false && $minPos === false) {
            $timeInMins['value'] = false;
        } elseif ($weekParse === false || $dayParse === false || $hourParse === false || $minParse === false) {
            $timeInMins['value'] = false;
        } else {
            $timeInMins['value'] = $weekInt * 7 * 24 * 60 + $dayInt * 24 * 60 + $hourInt * 60 + $minInt;
        }
        
        $timeInMins['week'] = $weekInt;
        $timeInMins['weekParse'] = $weekParse;
        $timeInMins['day'] = $dayInt;
        $timeInMins['dayParse'] = $dayParse;
        $timeInMins['hour'] = $hourInt;
        $timeInMins['hourParse'] = $hourParse;
        $timeInMins['min'] = $minInt;
        $timeInMins['minParse'] = $minParse;
        
        return $timeInMins;
    }
    
    
    /**
     * на време от минути във вербална стойност
     *
     * @param int $timeMin
     *
     * @return string $timeStr
     */
    public static function toVerbal($timeMin)
    {
        if ($timeMin) {
            // седмица, седмици
            $weekInt = floor($timeMin / 10080);
            
            if ($weekInt > 5) {
                $timeStr['value'] = false;
            } elseif ($weekInt > 1 && $weekInt < 5) {
                $weekStr = $weekInt . ' седмици ';
            } elseif ($weekInt == 1) {
                $weekStr = $weekInt . ' седмица ';
            } elseif ($weekInt == 0) {
                $weekStr = '';
            }
            
            // ENDOF седмица, седмици
            
            $timeMin = $timeMin - ($weekInt * 10080);
            
            // ден, дни
            $dayInt = floor($timeMin / 1440);
            
            if ($dayInt > 1) {
                $dayStr = $dayInt . ' дни ';
            } elseif ($dayInt == 1) {
                $dayStr = $dayInt . ' ден ';
            } elseif ($dayInt == 0) {
                $dayStr = '';
            }
            
            // ENDOF ден, дни
            
            $timeMin = $timeMin - ($dayInt * 1440);
            
            // час, часа
            $hourInt = floor($timeMin / 60);
            
            if ($hourInt > 1) {
                $hourStr = $hourInt . ' часа ';
            } elseif ($hourInt == 1) {
                $hourStr = $hourInt . ' час ';
            } elseif ($hourInt == 0) {
                $hourStr = '';
            }
            
            // ENDOF час, часа
            
            $minInt = $timeMin - ($hourInt * 60);
            
            // минута, минути
            if ($minInt > 1) {
                $minStr = $minInt . ' минути ';
            } elseif ($minInt == 1) {
                $minStr = $minInt . ' минута';
            } elseif ($minInt == 0) {
                if (!$weekStr && !$dayStr && !$hourStr) {
                    $minStr = 'на момента';
                }
            }
            
            // ENDOF минута, минути
            
            $timeStr = $weekStr . $dayStr . $hourStr . $minStr;
        } else {
            $timeStr = '';
        }
        
        return $timeStr;
    }
}
