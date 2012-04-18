<?php
/**
 * 
 * Адаптер за пакета jqplot - http://www.jqplot.com/
 * 
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class jqplot_Jqplot
{
    static protected $jqplotBasePath = 'jqplot/jquery.jqplot.1.0.0b2_r1012';
    
    
    /**
     * Инициализиране на пакета jqplot
     * 
     * Зарежда необходимите ресурси - скриптове, CSS, jqplot-плъгини
     * 
     * @param core_ET $tpl
     * @param array $plugins масив с имена на jqplot-плъгини
     */
    static function setup($tpl, $plugins = array())
    {
        // Зареждане на jquery
        jquery_Jquery::enable($tpl);
        
        // Зареждане на базовите jqplot файлове
        $tpl->appendOnce("\n"
            . "<!--[if lt IE 9]>"
            . "<script type=\"text/javascript\" src=" . sbf(static::resource('excanvas.min.js')) . "></script>"
            . "<![endif]-->", 'HEAD');
        $tpl->push(static::resource('jquery.jqplot.min.js'), 'JS');
        $tpl->push(static::resource('jquery.jqplot.min.css'), 'CSS');
        
        // Зареждане на jqplot-плъгини
        $plugins = arr::make($plugins);
        
        foreach ($plugins as $plugin) {
            $tpl->push(static::resource("plugins/jqplot.{$plugin}.min.js"), 'JS');
        }
    }
    
    
    /**
     * 
     * 
     * @param array $series данните за визуализиране
     * @param array $options
     * @return core_ET
     */
    static function chart($series, $options = NULL)
    {
        $chartAttr = array();
        core_Html::setUniqId($chartAttr);
        
        $chart = core_Html::createElement('div', $chartAttr, '', TRUE);
        
        if (!isset($options)) {
            $options = new stdClass();
        }
        
        $series  = json_encode($series);
        $options = json_encode($options);
        
        jquery_Jquery::run($chart, "$.jqplot('{$chartAttr['id']}', {$series}, {$options})");
        
        return $chart;
    }
    
    
    /**
     * Помощен метод при зареждане на ресурсите на jqplot
     *  
     * @param string $rPath @see sbf()
     * @param string $qt @see sbf()
     * @param boolean $absolute @see sbf()
     * @return string @see sbf()
     */
    protected static function resource($rPath, $qt = '"', $absolute = FALSE)
    {
        return static::$jqplotBasePath . "/{$rPath}";
        return sbf(static::$jqplotBasePath . "/{$rPath}", $qt, $absolute);
    }
}