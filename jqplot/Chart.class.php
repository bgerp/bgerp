<?php


/**
 *
 * Адаптер за пакета jqplot - http://www.jqplot.com/
 *
 * @category  vendors
 * @package   jqplot
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 */
class jqplot_Chart
{
    /**
     * Масив с използваните jqplot-плъгини
     *
     * @see usePlugin()
     *
     * @var array
     */
    protected static $plugins = array();
    
    
    /**
     * jqplot-опции по подразбиране на всички генерирани графики
     *
     * @var array
     */
    protected static $defaultOptions = array(
        'seriesDefaults' => array(
            'rendererOptions' => array(
                'smooth' => true
            )
        ),
        'axesDefaults' => array(
            'pad' => 1.4,
        ),
    );
    
    
    /**
     * Текущо натрупаните jqplot опции
     *
     * @var array
     */
    protected $options = array();
    
    
    /**
     * Данните, които ще се визуализират графично
     *
     * @see addPoint()
     *
     * @var array
     */
    protected $series = array();
    
    
    /**
     * Име на оста на категориите
     *
     * По подразбиране е хоризонталната ос (X), но за хоризонтални бар-графики става вертикалната
     * ос (Y)
     *
     * @var string
     */
    protected $tickAxis = 'xaxis';
    
    
    /**
     * Име на оста на стойностите
     *
     * По подразбиране е вертикалната ос (Y), но за хоризонтални бар-графики става хоризонталната
     * ос (X)
     *
     * @var string
     */
    protected $valueAxis = 'yaxis';
    
    
    /**
     * Масив от HTML атрибути на елемента-контейнер на графиката.
     *
     * @see setHtmlAttr()
     *
     * @var array
     */
    protected $htmlAttr = array();
    
    
    /**
     * Конструктор.
     *
     * Създава обект-графика и го инициализира според зададен набор конфиг. параметри
     *
     * Възможните параметри са:
     *
     *     * [type]: Вид на графиката. Възможните стойности са (*lines | bars)
     *     * [dir]:  Ориентация - хоризонтално или вертикално. Възможните стойности са
     *               (horizontal | *vertical). Важи само за type = bars.
     *     * [log]:  Използване на логаритмична скала за стойностите. Възможните стойности са
     *               (TRUE | *FALSE)
     *     * [htmlAttr]: масив от HTML атрибути за елемента-контейнер на графиката
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->options = static::$defaultOptions;
        
        if ($config['type'] == 'bars') {
            $this->options['seriesDefaults']['renderer'] = '@$.jqplot.BarRenderer@';
            $this->usePlugin('barRenderer');
            
            if ($config['dir'] == 'horizontal') {
                $this->options['seriesDefaults']['rendererOptions']['barDirection'] = 'horizontal';
                
                $this->tickAxis = 'yaxis';
                $this->valueAxis = 'xaxis';
            }
        }
        
        if ($config['log']) {
            $this->options['axes'][$this->valueAxis]['renderer'] = '@$.jqplot.LogAxisRenderer@';
            $this->usePlugin('logAxisRenderer');
        }
        
        $this->options['axes'][$this->tickAxis]['renderer'] = '@$.jqplot.CategoryAxisRenderer@';
        $this->usePlugin('categoryAxisRenderer');
        
        if (isset($config['htmlAttr'])) {
            $this->setHtmlAttr($config['htmlAttr']);
        }
    }
    
    
    /**
     * Задава заглавие на графиката
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->options['title'] = $title;
    }
    
    
    public function setTickAxisFormat($format)
    {
        $this->options['axes'][$this->tickAxis]['tickOptions']['formatString'] = $format;
    }
    
    public function setValueAxisFormat($format)
    {
        $this->options['axes'][$this->valueAxis]['tickOptions']['formatString'] = $format;
    }
    
    
    /**
     * Задава заглавие на оста на категориите (оста X)
     *
     * @param string $label
     */
    public function setTickAxisLabel($label)
    {
        $this->options['axes'][$this->tickAxis]['label'] = $label;
        $this->options['axes'][$this->valueAxis]['labelRenderer'] = '@$.jqplot.CanvasAxisLabelRenderer@';
        
        $this->usePlugin('canvasAxisLabelRenderer');
        $this->usePlugin('canvasTextRenderer');
    }
    
    
    /**
     * Задава заглавие на оста на стойностите (оста Y)
     *
     * @param string $label
     */
    public function setValueAxisLabel($label)
    {
        $this->options['axes'][$this->valueAxis]['label'] = $label;
        $this->options['axes'][$this->valueAxis]['labelRenderer'] = '@$.jqplot.CanvasAxisLabelRenderer@';
        
        $this->usePlugin('canvasAxisLabelRenderer');
        $this->usePlugin('canvasTextRenderer');
    }
    
    
    /**
     * Добавя нова точка в графиката
     *
     * @param scalar $seriesKey стойност, която уникално идентидифицира серията
     * @param scalar $tick      категория на точката (X-координата)
     * @param scalar $value     стойност на точката (Y-координата)
     * @param string $label     текст, който да се изпише до точката
     */
    public function addPoint($seriesKey, $tick, $value, $label = null)
    {
        if ($this->tickAxis == 'xaxis') {
            $this->series[$seriesKey][] = array($tick, $value);
        } else {
            $this->series[$seriesKey][] = array($value, $tick);
        }
        
        $this->options['seriesDefaults']['pointLabels']['show'] = true;
        $this->options['seriesDefaults']['pointLabels']['escapeHTML'] = false;
        $this->options['seriesDefaults']['pointLabels']['edgeTolerance'] = -40;
        $this->options['series'][$seriesKey]['pointLabels']['labels'][] = $label;
        
        $this->usePlugin('pointLabels');
    }
    
    
    /**
     * Връща шаблон, готов за "инжектиране" на произволно място в DOM-дървото
     *
     * Използването на този шаблон води до зареждането на всички необходими допълнителни файлове,
     * необходими за генерирането на графики. Това включва зареждането на jquery, jqplot и
     * необходимите му jqplot плъгини и CSS.
     *
     * @return core_ET
     */
    public function getElement()
    {
        // Контейнера трябва да има уникален DOM id
        core_Html::setUniqId($this->htmlAttr);
        
        if (!isset($this->htmlAttr['style'])) {
            $this->htmlAttr['style'] = '';
        }
        
        // Сериите са индексирани с произволни ключове (@see addPoint()), преиндексираме с
        // последователни цели числа, за да може да се генерира коректен JSON-масив (а не обект!)
        $series = array_values($this->series);
        
        if ($this->valueAxis == 'xaxis') {
            // Нещо като autoheight възможност за хоризонтални бар-графики. Височината на
            // контейнера се адаптира според броя на баровете.
            $this->htmlAttr['style'] .=
                '; height: ' . count($series) * count($series[0]) * 20 . 'px';
        }
        
        // Създаваме контейнер елемента със зададените HTML атрибути
        $chartEl = core_Html::createElement('div', $this->htmlAttr, '', true);
        
        $options = $this->options;
        
        if (isset($options['series'])) {
            // Преиндексираме, за да получим коректен JSON-масив
            $options['series'] = array_values($options['series']);
        }
        
        if (isset($options['axes'][$this->tickAxis]['ticks'])) {
            // Преиндексираме, за да получим коректен JSON-масив
            // @todo: това вече май не се използва
            $options['axes'][$this->tickAxis]['ticks'] =
                array_values($options['axes'][$this->tickAxis]['ticks']);
        }
        
        // Конвертираме натрупаните данни до JSON-стрингове
        $series = json_encode($series);
        $options = json_encode((object) $options);
        
        //
        // Лек хак:
        //
        // json_encode() конвертира всички PHP стрингове до JS-стрингови литерали (символи в
        // кавички). Така обаче в крайния JSON не могат да се генерират JS-изрази, което в тук е
        // проблем - задаването на всички рендери например става с JS израз, а не с JS-литерал
        //
        // По тази причина, в PHP данните заграждаме всички JS изрази с '@', а тук махаме тези
        // '@', заедно със съседните им (генерирани от json_encode()) кавички. Не е съвсем
        // универсално решение, но върши работа за случая.
        //
        $options = preg_replace('/"@(.*?)@"/', '$1', $options);
        
        // "Запалваме" генерирането на графиката.
        jquery_Jquery::run($chartEl, ";$.jqplot('{$this->htmlAttr['id']}', {$series}, {$options});\n");
        
        // Зарежда всичко необходимо за работата на jqplot
        static::setup($chartEl);
        
        return $chartEl;
    }
    
    
    /**
     * HTML атрибути на елемента-контейнер на графиката
     *
     * @param string|array $name  име на атрибута. С масив се задават много атрибути наведнъж
     * @param string       $value стойност на атрибута. Използва се само ако първия аргумент не е масив
     */
    public function setHtmlAttr($name, $value = null)
    {
        // Първо нормализираме до масив
        if (!is_array($name)) {
            $name = array($name => $value);
        }
        
        foreach ($name as $n => $v) {
            $this->htmlAttr[$n] = $v;
        }
    }
    
    
    /**
     * Заявява необходимостта от зареждане на jqplot плъгин
     *
     * @param string $name име на плъгина
     */
    public static function usePlugin($name)
    {
        static::$plugins[$name] = $name;
    }
    
    
    /**
     * Инициализиране на пакета jqplot
     *
     * Зарежда необходимите jqplot ресурси - базови скриптове, плъгини, CSS
     *
     * @param core_ET $tpl
     */
    protected static function setup($tpl)
    {
        // Зареждане на базовите jqplot файлове
        $tpl->appendOnce("\n"
            . '<!--[if lt IE 9]>'
            . '<script type="text/javascript" src='
                 . sbf(static::resource('excanvas.js'))
            . '></script>'
            . '<![endif]-->', 'HEAD');
        $tpl->push(static::resource('jquery.jqplot.js'), 'JS');
        $tpl->push(static::resource('jquery.jqplot.css'), 'CSS');
        
        // Зареждаме натрупаните до момента плъгини (@see usePlugin())
        foreach (array_keys(static::$plugins) as $plugin) {
            $tpl->push(static::resource("plugins/jqplot.{$plugin}.js"), 'JS');
        }
    }
    
    
    /**
     * Пълно име на jqplot-файл (JS, CSS)
     *
     * Ако приложението не е в дебъг режим, метода вмъква '.min' точно преди разширението на
     * файла и така се зареждат продъкшън файловете на jqplot.
     *
     * @param string $name име на jqplot файл относително спрямо базовата jqplot директория.
     *
     * @return string име на файл, готово за sbf()
     */
    public static function resource($name)
    {
        if (isDebug() && ($dot = strrpos($name, '.')) !== false) {
            $name = substr($name, 0, $dot) . '.min' . substr($name, $dot);
        }
        $conf = core_Packs::getConfig('jqplot');
        $resource = 'jqplot/' . $conf->JQPLOT_VERSION . '/' . $name;
        
        return $resource;
    }
}
