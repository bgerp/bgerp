<?php


/**
 *
 *
 * @category  bgerp
 * @package   chartjs
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class chartjs_Adapter extends core_Mvc
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
     * @param array  $data      - данните, които ще изчератаваме
     * @param string $chartType - тип на диаграмата:  'line', 'bar', 'pie'
     *
     * @return core_ET $tpl
     */
    public static function prepare_($data, $chartType)
    {
        $tpl = new ET();
        static $chartCnt;
        
        $data = json_encode($data);
        
        $chart = ht::createElement('canvas', array('class' => 'diagramCanvas', 'width' => '700', 'height' => '570', 'data-data' => $data, 'data-type' => $chartType), $tpl);
        $tpl->append("<div class='chartHolder chart-${chartType}'>" . $chart . '</div>');
        
        $tpl->push('chartjs/lib/preparechart.js', 'JS');
        $tpl->push('chartjs/' . chartjs_Setup::get('VERSION') . '/Chart.min.js', 'JS');
        $tpl->push('chartjs/lib/Chart.css', 'CSS');
        
        
        $animation = true;
        
        jquery_Jquery::run($tpl, "prepareChart({$animation});");
        jquery_Jquery::runAfterAjax($tpl, 'prepareChart');
        
        return $tpl;
    }
}
