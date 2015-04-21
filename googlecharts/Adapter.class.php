<?php


/**
 * 
 * 
 * @category  bgerp
 * @package   googlecharts
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class googlecharts_Adapter extends  core_Mvc
{

    public $interfaces = 'doc_chartAdapterIntf';

    public $title = 'googlecharts';
    /**
     * @param $data - данните, които ще изчератаваме
     * @param $chartType - тип на диаграмата:  'line', 'bar', 'pie'
     * @return $tpl
     */
    function prepare($data, $chartType)
    {


        $tpl = new ET();
        static $chartCnt;
        if(!$chartCnt) $orgChartCnt = 0;
        $chartCnt++;
        $idChart = 'myChart' . $chartCnt;

        $chart = ht::createElement('div',  array('id' => $idChart, "style" => "width:800px; height:300px"), $tpl);
        $tpl->append("<div class='chartHolder chart-$chartType'>" . $chart . "</div>");

        if($chartType == 'bar') {
            $tpl->push("https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1.1','packages':['bar']}]}", 'JS');
        } else {
            $tpl->push("https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1.1','packages':['corechart']}]}", 'JS');
        }

        $tpl->push('googlecharts/lib/preparechart.js', 'JS');

        $data = json_encode($data);

        jquery_Jquery::run($tpl, "prepareGoogleChart('$idChart', $data, '$chartType');", TRUE);

        return $tpl;
	}
}