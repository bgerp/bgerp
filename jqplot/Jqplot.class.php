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
    protected static $jqplotBasePath = 'jqplot/jquery.jqplot.1.0.0b2_r1012';

    protected static $min = '';

    protected static $plugins = array();

    /**
     * Текущо натрупаните jqplot опции
     *
     * @var array
     */
    protected $options = array(
        'axesDefaults' => array(
            'pad' => 1.4,
        )
    );

    protected $series = array();

    protected $tickAxis  = 'xaxis';

    protected $valueAxis = 'yaxis';

    protected $htmlAttr  = array();

    public function __construct($config)
    {
        if ($config['type'] == 'bars') {

            $this->options['seriesDefaults']['renderer'] = '@$.jqplot.BarRenderer@';
            $this->usePlugin('barRenderer');

            if ($config['dir'] == 'horizontal') {
                $this->options['seriesDefaults']['rendererOptions']['barDirection'] = 'horizontal';

                $this->tickAxis  = 'yaxis';
                $this->valueAxis = 'xaxis';
            }
        }

        if ($config['log']) {
            $this->options['axes'][$this->valueAxis]['renderer'] = '@$.jqplot.LogAxisRenderer@';
            $this->usePlugin('logAxisRenderer');
        }

        $this->options['axes'][$this->tickAxis]['renderer'] = '@$.jqplot.CategoryAxisRenderer@';
        $this->usePlugin('categoryAxisRenderer');
    }

    public function setTitle($title)
    {
        $this->options['title'] = $title;
    }

    public function setValueAxisFormat($format)
    {
        $this->options[$this->valueAxis]['tickOptions']['formatString'] = $format;
    }

    public function addPoint($seriesKey, $tick, $value, $label = NULL)
    {

        if ($this->tickAxis == 'xaxis') {
            $this->series[$seriesKey][] = array($tick, $value);
        } else {
            $this->series[$seriesKey][] = array($value, $tick);
        }

        $this->options['seriesDefaults']['pointLabels']['show']     = TRUE;
        $this->options['seriesDefaults']['pointLabels']['escapeHTML'] = FALSE;
        $this->options['seriesDefaults']['pointLabels']['edgeTolerance'] = -40;
        $this->options['series'][$seriesKey]['pointLabels']['labels'][] = $label;

        $this->usePlugin('pointLabels');
    }

    public function appendTo($tpl)
    {
        static::setup($tpl);

        foreach (array_keys(static::$plugins) as $plugin) {
            $tpl->push(static::resource("plugins/jqplot.{$plugin}.js"), 'JS');
        }

        core_Html::setUniqId($this->htmlAttr);

        if (!isset($this->htmlAttr['style'])) {
            $this->htmlAttr['style'] = '';
        }

        $series = array_values($this->series);

        if ($this->valueAxis == 'xaxis') {
            $this->htmlAttr['style'] .= '; height: ' . count($series) * count($series[0]) * 20 . 'px';
        }

        $chartEl = core_Html::createElement('div', $this->htmlAttr, '', TRUE);

        $options = $this->options;

        if (isset($options['series'])) {
            $options['series'] = array_values($options['series']);
        }


        if (isset($options['axes'][$this->tickAxis]['ticks'])) {
            $options['axes'][$this->tickAxis]['ticks'] = array_values($options['axes'][$this->tickAxis]['ticks']);
        }

        $series  = json_encode($series);
        $options = json_encode((object)$options);

        $options = preg_replace('/"@(.*?)@"/', '$1', $options);

        jquery_Jquery::run($chartEl, ";$.jqplot('{$this->htmlAttr['id']}', {$series}, {$options});\n");

        $tpl->append($chartEl);
    }

    public function setHtmlAttr($name, $value = NULL)
    {
        if (!is_array($name)) {
            $name = array($name=>$value);
        }

        foreach ($name as $n=>$v) {
            $this->htmlAttr[$n] = $v;
        }
    }


    public static function usePlugin($name)
    {
        static::$plugins[$name] = $name;
    }


    public function getOptions()
    {
        return $this->options;
    }


    /**
     * Инициализиране на пакета jqplot
     *
     * Зарежда базовите jqplot ресурси - скриптове, CSS
     *
     * @param core_ET $tpl
     */
    static function setup($tpl)
    {
        // Зареждане на jquery
        jquery_Jquery::enable($tpl);

        // Зареждане на базовите jqplot файлове
        $tpl->appendOnce("\n"
            . "<!--[if lt IE 9]>"
            . "<script type=\"text/javascript\" src=" . sbf(static::resource('excanvas.js')) . "></script>"
            . "<![endif]-->", 'HEAD');
        $tpl->push(static::resource('jquery.jqplot.js'), 'JS');
        $tpl->push(static::resource('jquery.jqplot.css'), 'CSS');
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
        if (($dot = strrpos($rPath, '.')) !== FALSE) {
            $rPath = substr($rPath, 0, $dot) . static::$min . substr($rPath, $dot);
        }
        return static::$jqplotBasePath . "/{$rPath}";
    }
}