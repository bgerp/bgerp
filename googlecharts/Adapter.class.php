<?php


/**
 *
 *
 * @category  bgerp
 * @package   googlecharts
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class googlecharts_Adapter extends core_Mvc
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
    public $title = 'googlecharts';
    
    
    /**
     * @param array  $data      - данните, които ще изчератаваме
     * @param string $chartType - тип на диаграмата:  'line', 'bar', 'pie'
     *
     * @return core_ET $tpl
     */
    public static function prepare_($data, $chartType)
    {
        $tpl = new ET();
        static $chartCnt;
        if (!$chartCnt) {
            $orgChartCnt = 0;
        }
        $chartCnt++;
        $idChart = 'myChart' . $chartCnt;
        
        $chart = ht::createElement('div', array('id' => $idChart, 'class' => "google-chart {$chartType}Chart"), $tpl);
        $tpl->append("<div class='googleChartsHolder' style=' width: 100%; height: auto; overflow: hidden;'>" . $chart . '</div>');
        
        if ($chartType == 'bar') {
            $tpl->push("https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1.1','packages':['bar']}]}", 'JS');
        } else {
            $tpl->push("https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1.1','packages':['corechart']}]}", 'JS');
        }
        
        $tpl->push('googlecharts/lib/googlecharts-custom.css', 'CSS');
        $tpl->push('googlecharts/lib/preparechart.js', 'JS');
        
        $data = json_encode($data);
        
        jquery_Jquery::run($tpl, "prepareGoogleChart('${idChart}', ${data}, '${chartType}');", true);
        
        return $tpl;
    }
}
