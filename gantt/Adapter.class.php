<?php


/** Гант таблица
 *
 * @category  vendors
 * @package   orgchart
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class gantt_Adapter extends core_Mvc
{
    /**
     * начертаване на гант таблици по дадена структура
     *
     * @param array $ganttData - структура, от която вземаме данните за гант таблица
     */
    public static function render_($ganttData, $id = null)
    {
        static $ganttChartCnt = 0;
        $ganttChartCnt++;
        $idChart = $id ?? ('ganttTableHolder' . $ganttChartCnt);
        
        $tpl = new ET();
        $ganttHolder = ht::createElement('div', array('id' => $idChart, 'class' => 'gantt-chart', 'data-gantt' => json_encode($ganttData)), $tpl);
        $tpl->append($ganttHolder);
        
        $tpl->push('gantt/lib/ganttCustom.css', 'CSS');
        $tpl->push('gantt/lib/ganttCustom.js', 'JS');
        
        jquery_Jquery::run($tpl, 'ganttInit();');
        jquery_Jquery::runAfterAjax($tpl, 'ganttInit');
        
        return $tpl;
    }
}
