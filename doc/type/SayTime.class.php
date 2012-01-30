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
     * @return int $timeInMins
     */
    function fromVerbal($timeStr)
    {
    	$timeStr = trim($timeStr);
    	
    	$timeStr = str_replace(' и ', ' ', $timeStr);
    	    	
    	// Седмица, седмици
    	$weekPos = strpos($timeStr, 'седм');
    	
    	if ($weekPos) {
    	    $weekData = substr($timeStr, 0, $weekPos);
    	    
    	    if (preg_match("/[0-9]+/", $weekData, $matches)) {
                $weekInt = $matches[0];
            }

	        // Cut $timeStr
	        $timeStr = substr($timeStr, $weekPos, strlen($timeStr) - $weekPos);
	        
	        // Намира първия интервал
	        $intervalPos = strpos($timeStr, ' ');
	         
	        // Cut $timeStr
	        $timeStr = substr($timeStr, $intervalPos + 1, strlen($timeStr) - $intervalPos - 1);
    	}
    	// ENDOF Седмица, седмици
    	
        // Ден, дена
        $dayPos = strpos($timeStr, 'ден');
        
        if ($dayPos) {
            $dayData = substr($timeStr, 0, $dayPos);
            
            if (preg_match("/[0-9]+/", $dayData, $matches)) {
                $dayInt = $matches[0];
            }

	        // Cut $timeStr
	        $timeStr = substr($timeStr, $dayPos, strlen($timeStr) - $dayPos);
	
	        // Намира първия интервал
	        $intervalPos = strpos($timeStr, ' ');
	         
	        // Cut $timeStr
	        $timeStr = substr($timeStr, $intervalPos + 1, strlen($timeStr) - $intervalPos - 1);
        }
        // ENDOF Ден, дена
        
        // Час, часа
        $hourPos = strpos($timeStr, 'час');
        
        if ($hourPos) {
        	$hourData = substr($timeStr, 0, $hourPos);

            if (preg_match("/[0-9]+/", $hourData, $matches)) {
                $hourInt = $matches[0];
            }

	        // Cut $timeStr
	        $timeStr = substr($timeStr, $dayPos, strlen($timeStr) - $hourPos);
	
	        // Намира първия интервал
	        $intervalPos = strpos($timeStr, ' ');
	         
	        // Cut $timeStr
	        $timeStr = substr($timeStr, $intervalPos + 1, strlen($timeStr) - $intervalPos - 1);
        }
        // ENDOF Час, часа
        
        // Мин
        $minPos = strpos($timeStr, 'мин');

        if ($minPos) {
            $minData = substr($timeStr, 0, $minPos);

            if (preg_match("/[0-9]+/", $minData, $matches)) {
                $minInt = $matches[0];
            }

            $timeInMins = $weekInt*7*24*60 + $dayInt*24*60 + $hourInt*60 + $minInt;            
        }
        // ENDOF Час, часа        
        
    	return $timeInMins;
    }
}