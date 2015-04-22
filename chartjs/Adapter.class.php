<?php


/**
 * 
 * 
 * @category  bgerp
 * @package   chartjs
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class chartjs_Adapter extends  core_Mvc
{
	
	
	/**
	 * Поддържани интерфейси
	 *
	 * var string|array
	 */
    public $interfaces = 'doc_chartAdapterIntf';

    
    /**
     * Заглавие в множествено число
     *
     * @var string
     */
    public $title = 'chartjs';
    
    
    /**
     * @param array $data - данните, които ще изчератаваме
     * @param string $chartType - тип на диаграмата:  'line', 'bar', 'pie'
     * @return core_ET $tpl
     */
    static function prepare_($data, $chartType)
    {

        $tpl = new ET();
        static $chartCnt;
        if(!$chartCnt) $orgChartCnt = 0;
        $chartCnt++;
        $idChart = 'myChart' . $chartCnt;

        $chart = ht::createElement('canvas',  array('id' => $idChart, "width" => "300", 'height' => '300'), $tpl);
        $tpl->append("<div class='chartHolder chart-$chartType'>" . $chart . "</div>");

        $tpl->push('chartjs/lib/preparechart.js', 'JS');
        $tpl->push('chartjs/1.0.2/Chart.min.js', 'JS');
        $tpl->push('chartjs/lib/Chart.css', 'CSS');

        $data = json_encode($data);

        jquery_Jquery::run($tpl, "prepareChart('$idChart', $data, '$chartType');");

        return $tpl;
	}
}