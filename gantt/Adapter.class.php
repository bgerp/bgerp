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
     * начертаване на гант таблици по дадена структура
     * @param array $ganttData - структура, от която вземаме данните за гант таблицa
     */
    static function render_($ganttData)
    {
    	static $ganttChartCnt;
    	if(!$ganttChartCnt) $orgChartCnt = 0;
    	$ganttChartCnt++;
    	$idChart = 'ganttTableHolder' . $ganttChartCnt;
          
        $tpl = new ET();
        $ganttHolder = ht::createElement('div',  array('id' => $idChart), $tpl);
        $tpl->append($ganttHolder);
        
        $tpl->push('gantt/lib/ganttCustom.css', 'CSS');
        $tpl->push('gantt/lib/ganttCustom.js', 'JS');
            
        $ganttData = json_encode($ganttData);
        jquery_Jquery::run($tpl, "ganttRender($('#{$idChart}'),{$ganttData});");
        
        return $tpl;
    }
}
