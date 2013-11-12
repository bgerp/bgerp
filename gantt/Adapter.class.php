<?php


/** Гант таблицa
 * 
 * @category  vendors
 * @package   orgchart
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class gantt_Adapter extends core_Mvc
{    

    /**
     * начертаване на гант таблици по даден двумерен масив
     * 
     * @param array $ganttData - двумерен масив, от който вземаме данните за гант таблицa
     * @param $startTime - timestamp на началото на таблицата
   	 * @param $endTime - timestamp на края на таблицата
     * $ganttData трябва да има следната структура:
     * $ganttData = array(
	 *		  	'0' => array (
    					'taskId' => "Tsk23",
    					'rowId' => 0,
    					'duration' => 3600,
    					'startTask' => 1451599199, 
    					'color' => "#c00",
    					'hint' => '',
    					'url' => ''
    			),
	 * taskId - id на задачата
	 * rowId - в кой ред от таблицата се намира
     * duration - продължителност в секунди
     * startTask - timestamp на началото на задачата
     * color - фон на графиката й
     * hint - hint  при ховър
     * url - url към сингъла й
     * 
     */
    static function render_($ganttData, $startTime, $endTime)
    {
    	static $ganttChartCnt;
    	
    	if(!$ganttChartCnt) $orgChartCnt = 0;
    	
    	$ganttChartCnt++;
    	
    	$idChart = 'ganttTable' . $ganttChartCnt;
    
    	
    
        // тестов шаблон
        $tpl = getTplFromFile('gantt/mockup/mockup.shtml');
        jquery_Jquery::enable($tpl);
         
        $tpl->push('gantt/lib/ganttCustom.css', 'CSS');
        $tpl->push('gantt/lib/ganttCustom.js', 'JS');
            
        $ganttData = json_encode($ganttData);
        jquery_Jquery::run($tpl, "ganttRender($('#{$idChart}'),{$startTime},{$endTime},{$ganttData});");
        
        return $tpl;
    }
}
