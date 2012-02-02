<?php
/**
 * Клас 'store_type_SayTime' - тип за вербално време
 *
 * @category  bgerp
 * @package   store
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_type_SayTime extends type_Varchar {
    /**
     * Преобразуване на време от вербална стойност към представяне в минути 
     * 
     * @param string $timeStr
     * @return array $timeInMins
     */
    function fromVerbal($timeStr)
    {
    	$timeStr = trim($timeStr);
    	
    	$timeStr = str_replace(' и ', ' ', $timeStr);
    	
    	// Init
    	$weekInt = 0;
    	$dayInt  = 0;
    	$hourInt = 0;
    	$minInt  = 0;
    	    	
    	// Седмица, седмици
    	$weekPos = strpos($timeStr, 'седм');
    	
    	if ($weekPos) {
    		$weekData = substr($timeStr, 0, $weekPos);
    	    
    	    if (preg_match("/[0-9]+/", $weekData, $matches)) {
                $weekInt = $matches[0];
            } else {
            	$weekParse = FALSE;
            }

	        // Cut $timeStr
	        $timeStr = substr($timeStr, $weekPos, strlen($timeStr) - $weekPos);
	        
	        // Намира първия интервал
	        $intervalPos = strpos($timeStr, ' ');
	         
	        // Cut $timeStr
	        $timeStr = substr($timeStr, $intervalPos + 1, strlen($timeStr) - $intervalPos - 1);
    	} elseif ($weekPos === 0) {
    		$weekParse = FALSE;
    	}
    	// ENDOF Седмица, седмици
    	
        // Ден, дена, дни
        $dayPos = strpos($timeStr, 'ден');
        
        if ($dayPos) {
            $dayData = substr($timeStr, 0, $dayPos);
            
            if (preg_match("/[0-9]+/", $dayData, $matches)) {
                $dayInt = $matches[0];
            } else {
                $dayParse = FALSE;
            }

	        // Cut $timeStr
	        $timeStr = substr($timeStr, $dayPos, strlen($timeStr) - $dayPos);
	
	        // Намира първия интервал
	        $intervalPos = strpos($timeStr, ' ');
	         
	        // Cut $timeStr
	        $timeStr = substr($timeStr, $intervalPos + 1, strlen($timeStr) - $intervalPos - 1);
        } elseif ($dayPos === 0) {
            $dayParse = FALSE;
        } else {
            $dayPos = strpos($timeStr, 'дни');
            
            if ($dayPos) {
	            $dayData = substr($timeStr, 0, $dayPos);
	            
	            if (preg_match("/[0-9]+/", $dayData, $matches)) {
	                $dayInt = $matches[0];
	            } else {
                    $dayParse = FALSE;
                }
	
	            // Cut $timeStr
	            $timeStr = substr($timeStr, $dayPos, strlen($timeStr) - $dayPos);
	    
	            // Намира първия интервал
	            $intervalPos = strpos($timeStr, ' ');
	             
	            // Cut $timeStr
	            $timeStr = substr($timeStr, $intervalPos + 1, strlen($timeStr) - $intervalPos - 1);
	        } elseif ($dayPos === 0) {
	            $dayParse = FALSE;
	        }            
        }
        // ENDOF Ден, дена, дни
        
        // Час, часа
        $hourPos = strpos($timeStr, 'час');
        
        if ($hourPos) {
        	$hourData = substr($timeStr, 0, $hourPos);

            if (preg_match("/[0-9]+/", $hourData, $matches)) {
                $hourInt = $matches[0];
            } else {
                $hourParse = FALSE;
            }
        
	        // Cut $timeStr
	        $timeStr = substr($timeStr, $dayPos, strlen($timeStr) - $hourPos);
	
	        // Намира първия интервал
	        $intervalPos = strpos($timeStr, ' ');
	         
	        // Cut $timeStr
	        $timeStr = substr($timeStr, $intervalPos + 1, strlen($timeStr) - $intervalPos - 1);
        } elseif ($hourPos === 0) {
            $hourParse = FALSE;
        }
        // ENDOF Час, часа
        
        // Мин
        $minPos = strpos($timeStr, 'мин');

        if ($minPos) {
            $minData = substr($timeStr, 0, $minPos);

            if (preg_match("/[0-9]+/", $minData, $matches)) {
                $minInt = $matches[0];
            } else {
                $minParse = FALSE;
            }
        } elseif ($minPos === 0) {
            $minParse = FALSE;        	
        }
        // ENDOF Мин

        // Ако има зададена седмица/ден/час/минути, но не може да се определи int стойността
        if ($weekParse === FALSE || $dayParse  === FALSE || $hourParse === FALSE || $minParse  === FALSE) {
            $timeInMins['value']= FALSE;
        } else {
            $timeInMins['value'] = $weekInt*7*24*60 + $dayInt*24*60 + $hourInt*60 + $minInt;
        }

        $timeInMins['week']      = $weekInt;
        $timeInMins['weekParse'] = $weekParse;
        $timeInMins['day']       = $dayInt;
        $timeInMins['dayParse']  = $dayParse;
        $timeInMins['hour']      = $hourInt;
        $timeInMins['hourParse'] = $hourParse;
        $timeInMins['min']       = $minInt;
        $timeInMins['minParse']  = $minParse;
        
    	return $timeInMins;
    }
    
    
    /**
     * Преообразуване на време от минути във вербална стойност
     * 
     * @param int $timeMin
     * @return string $timeStr
     */
    function toVerbal($timeMin)
    {
        // седмица, седмици
    	$weekInt = floor($timeMin / 10080);
        
    	if ($weekInt > 5) {
    	   $timeStr['value'] = FALSE;
    	} elseif ($weekInt > 1 && $weekInt <5) {
    	   $weekStr = $weekInt . ' седмици ';
    	} elseif ($weekInt == 1) {
    	   $weekStr = $weekInt . ' седмица ';
    	} elseif ($weekInt == 0) {
    	   $weekStr = '';
    	}
        // ENDOF седмица, седмици
    	
        $timeMin = $timeMin - ($weekInt * 10080);
        
        // ден, дни
        $dayInt  = floor($timeMin / 1440);
        
        if ($dayInt > 1) {
            $dayStr = $dayInt . " дни ";
        } elseif ($dayInt == 1) {
            $dayStr = $dayInt . " ден ";
        } elseif ($dayInt == 0) {
            $dayStr = '';
        }
        // ENDOF ден, дни
        
        $timeMin = $timeMin - ($dayInt * 1440);
        
        // час, часа
        $hourInt = floor($timeMin / 60);
        
        if ($hourInt > 1) {
            $hourStr = $hourInt . " часа ";
        } elseif ($hourInt == 1) {
            $hourStr = $hourInt . " час ";
        } elseif ($hourInt == 0) {
            $hourStr = '';
        }
        // ENDOF час, часа
        
        $minInt  = $timeMin  - ($hourInt * 60);
        
        // минута, минути
        if ($minInt > 1) {
            $minStr = $minInt . " минути ";
        } elseif ($minInt == 1) {
            $minStr = $minInt . " минута";
        } elseif ($minInt == 0) {
            $minStr = 'на момента';
        }        
        // ENDOF минута, минути

        $timeStr = $weekStr . $dayStr . $hourStr . $minStr;
        
        return $timeStr;
    }
}