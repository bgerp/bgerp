<?php


/**
 * Клас 'jsgauge_Gauge'
 *
 * Клас, който служи за създаване на Gauge.
 * Съдържа необходимите функции за използването на
 * Gauge
 *
 *
 * @category  vendors
 * @package   jsgauge
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link      http://code.google.com/p/jsgauge/
 */
class jsgauge_Gauge
{
    
    
    /**
     * Рендира уред за измерване на температурата
     * @param string $canvasId - Уникално id на всеки canvas елемент
     * @param number $value - Текущата стойност на елемента
     * @param array  $arr - Масив от атрибути
     * @param string $arr['label'] - Надписа, който се показва в измервателния уред
     * @param string $arr['unitsLabel'] - Единицата за измерване
     * @param number $arr['min'] - Минималната стойност в измервателния уред
     * @param number $arr['max'] - Максималната стойност в измервателния уред
     * @param number $arr['majorTicks'] - Броя на големите линии
     * @param number $arr['minorTicks'] - Броя на малките линии
     * @param string $arr['colorOfText'] - Цвят на текста
     * @param string $arr['colorOfWarningText'] - Цвят на предупредителния текст
     * @param array  $arr['colorOfFill'] - Цветове, които се използват за чертане на измервателния уред
     * @param string $arr['colorOfPointerFill'] - Цвят, който се използва за запълване на иглата
     * @param string $arr['colorOfPointerStroke'] - Цвят, който се използва за външната линия на иглата
     * @param string $arr['colorOfCenterCircleFill'] - Цвят, който се използва за запълване на кръга на иглата
     * @param string $arr['colorOfCenterCircleStroke'] - Цвят, който се използва за външната линия на кръга на иглата
     * @param number $arr['greenFrom'] - Начало на зеления цвят
     * @param number $arr['greenTo'] - Край на зеления цвят
     * @param number $arr['yellowFrom'] - Начало на жълтия цвят
     * @param number $arr['yellowTo'] - Край на жълтия цвят
     * @param number $arr['redFrom'] - Начало на червения цвят
     * @param number $arr['redTo'] - Край на червения цвят
     * @param string $arr['redColor'] - Цвят на "червената" лента
     * @param string $arr['yellowColor'] - Цвят на "жълтата" лента
     * @param string $arr['greenColor'] - Цвят на "зелената" лента
     *
     * return $tpl
     */
    public static function renderTemperature($value = NULL, $canvasId = NULL, $arr = NULL)
    {
        setIfNot($value, '0');
        setIfNot($canvasId, str::getRand());
        setIfNot($arr['label'], 'Темп');
        setIfNot($arr['unitsLabel'], '°C');
        setIfNot($arr['min'], '0');
        setIfNot($arr['max'], '50');
        setIfNot($arr['majorTicks'], '6');
        setIfNot($arr['minorTicks'], '4');
        setIfNot($arr['greenFrom'], '20');
        setIfNot($arr['greenTo'], '30');
        setIfNot($arr['greenColor'], '#00FF00');     //#RRGGBB - Зелен цвят
        setIfNot($arr['yellowFrom'], '0');
        setIfNot($arr['yellowTo'], '15');
        setIfNot($arr['yellowColor'], '#0000FF');     //#RRGGBB - Син цвят
        setIfNot($arr['redFrom'], '35');
        setIfNot($arr['redTo'], '50');
        setIfNot($arr['redColor'], '#FF0000');     //#RRGGBB - Червен цвят
        setIfNot($arr['colorOfText'], '#000');
        setIfNot($arr['colorOfWarningText'], 'FF0000');
        setIfNot($arr['colorOfFill'], '[ "#111", "#ccc", "#ddd", "#eee" ]');
        setIfNot($arr['colorOfPointerFill'], 'rgba(255, 100, 0, 0.7)');
        setIfNot($arr['colorOfPointerStroke'], 'rgba(255, 100, 100, 0.9)');
        setIfNot($arr['colorOfCenterCircleFill'], 'rgba(0, 100, 255, 1)');
        setIfNot($arr['colorOfCenterCircleStroke'], 'rgba(0, 0, 255, 1)');
        setIfNot($arr['height'], '175');
        setIfNot($arr['width'], '175');
        $arr['canvasId'] = $canvasId;
        $arr['value'] = $value;
        $tpl = new ET('
            <canvas id="[#canvasId#]" height="[#height#]" width="[#width#]"></canvas>
        ');
        $tpl2 = new ET('
        addLoadEvent( function() {
            options_[#canvasId#] = {
                label: "[#label#]",
                unitsLabel: " [#unitsLabel#]",
                min: [#min#],
                max: [#max#],
                majorTicks: [#majorTicks#],
                minorTicks: [#minorTicks#], 
                greenFrom: [#greenFrom#],
                greenTo: [#greenTo#],
                greenColor: "[#greenColor#]", 
                yellowFrom: [#yellowFrom#],
                yellowTo: [#yellowTo#],
                yellowColor: "[#yellowColor#]",
                redFrom: [#redFrom#],
                redTo: [#redTo#],
                redColor:  "[#redColor#]",
                colorOfText: "[#colorOfText#]",
                colorOfWarningText: "[#colorOfWarningText#]",
                colorOfFill: [#colorOfFill#],
                colorOfPointerFill: "[#colorOfPointerFill#]",
                colorOfPointerStroke: "[#colorOfPointerStroke#]",
                colorOfCenterCircleFill: "[#colorOfCenterCircleFill#]",
                colorOfCenterCircleStroke: "[#colorOfCenterCircleStroke#]",
            };
            [#canvasId#] = new Gauge( document.getElementById("[#canvasId#]"), options_[#canvasId#] );
            [#canvasId#].setValue( [#value#] );
        })
        ');
        
        self::enable($tpl);
        $tpl2->placeObject($arr);
        $tpl->placeObject($arr);
        $tpl->append($tpl2, 'SCRIPTS');
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на уред за измерване на налягане
     */
    static function renderPressure($value = NULL, $canvasId = NULL, $arr = NULL)
    {
        setIfNot($value, '0');
        setIfNot($canvasId, str::getRand());
        setIfNot($arr['label'], 'Налягане');
        setIfNot($arr['unitsLabel'], 'bar');
        setIfNot($arr['min'], '0');
        setIfNot($arr['max'], '10');
        setIfNot($arr['majorTicks'], '11');
        setIfNot($arr['minorTicks'], '4');
        setIfNot($arr['greenFrom'], '0');
        setIfNot($arr['greenTo'], '0');
        setIfNot($arr['greenColor'], '#00FF00');     //#RRGGBB - Зелен цвят, който не е активен
        setIfNot($arr['yellowFrom'], '7');
        setIfNot($arr['yellowTo'], '8');
        setIfNot($arr['yellowColor'], '#FFFF00');     //#RRGGBB - Жълт цвят
        setIfNot($arr['redFrom'], '8');
        setIfNot($arr['redTo'], '10');
        setIfNot($arr['redColor'], '#FF0000');     //#RRGGBB - Червен цвят
        setIfNot($arr['colorOfText'], '#000');
        setIfNot($arr['colorOfWarningText'], 'FF0000');
        setIfNot($arr['colorOfFill'], '[ "#111", "#ccc", "#ddd", "#eee" ]');
        setIfNot($arr['colorOfPointerFill'], 'rgba(255, 100, 0, 0.7)');
        setIfNot($arr['colorOfPointerStroke'], 'rgba(255, 100, 100, 0.9)');
        setIfNot($arr['colorOfCenterCircleFill'], 'rgba(0, 100, 255, 1)');
        setIfNot($arr['colorOfCenterCircleStroke'], 'rgba(0, 0, 255, 1)');
        setIfNot($arr['height'], '175');
        setIfNot($arr['width'], '175');
        $arr['canvasId'] = $canvasId;
        $arr['value'] = $value;
        $tpl = new ET('
            <canvas id="[#canvasId#]" height="[#height#]" width="[#width#]"></canvas>
        ');
        
        $tpl2 = new ET('
        addLoadEvent( function() {
            options_[#canvasId#] = {
                label: "[#label#]",
                unitsLabel: " [#unitsLabel#]",
                min: [#min#],
                max: [#max#],
                majorTicks: [#majorTicks#],
                minorTicks: [#minorTicks#], 
                greenFrom: [#greenFrom#],
                greenTo: [#greenTo#],
                greenColor: "[#greenColor#]", 
                yellowFrom: [#yellowFrom#],
                yellowTo: [#yellowTo#],
                yellowColor: "[#yellowColor#]",
                redFrom: [#redFrom#],
                redTo: [#redTo#],
                redColor:  "[#redColor#]",
                colorOfText: "[#colorOfText#]",
                colorOfWarningText: "[#colorOfWarningText#]",
                colorOfFill: [#colorOfFill#],
                colorOfPointerFill: "[#colorOfPointerFill#]",
                colorOfPointerStroke: "[#colorOfPointerStroke#]",
                colorOfCenterCircleFill: "[#colorOfCenterCircleFill#]",
                colorOfCenterCircleStroke: "[#colorOfCenterCircleStroke#]",
            };
            [#canvasId#] = new Gauge( document.getElementById("[#canvasId#]"), options_[#canvasId#] );
            [#canvasId#].setValue( [#value#] );
        })
        ');
        
        self::enable($tpl);
        $tpl2->placeObject($arr);
        $tpl->placeObject($arr);
        $tpl->append($tpl2, 'SCRIPTS');
        
        return $tpl;
    }
    
    
    /**
     * Рендиране на уред за измерване на влажността
     */
    static function renderHumidity($value = NULL, $canvasId = NULL, $arr = NULL)
    {
        setIfNot($value, '0');
        setIfNot($canvasId, str::getRand());
        setIfNot($arr['label'], 'Влажност');
        setIfNot($arr['unitsLabel'], '%');
        setIfNot($arr['min'], '0');
        setIfNot($arr['max'], '100');
        setIfNot($arr['majorTicks'], '11');
        setIfNot($arr['minorTicks'], '4');
        setIfNot($arr['greenFrom'], '30');
        setIfNot($arr['greenTo'], '80');
        setIfNot($arr['greenColor'], '#00FF00');     //#RRGGBB - Зелен цвят, който не е активен
        setIfNot($arr['yellowFrom'], '90');
        setIfNot($arr['yellowTo'], '100');
        setIfNot($arr['yellowColor'], '#0000FF');     //#RRGGBB - Син цвят
        setIfNot($arr['redFrom'], '0');
        setIfNot($arr['redTo'], '10');
        setIfNot($arr['redColor'], '#FF0000');     //#RRGGBB - Червен цвят
        setIfNot($arr['colorOfText'], '#000');
        setIfNot($arr['colorOfWarningText'], 'FF0000');
        setIfNot($arr['colorOfFill'], '[ "#111", "#ccc", "#ddd", "#eee" ]');
        setIfNot($arr['colorOfPointerFill'], 'rgba(255, 100, 0, 0.7)');
        setIfNot($arr['colorOfPointerStroke'], 'rgba(255, 100, 100, 0.9)');
        setIfNot($arr['colorOfCenterCircleFill'], 'rgba(0, 100, 255, 1)');
        setIfNot($arr['colorOfCenterCircleStroke'], 'rgba(0, 0, 255, 1)');
        setIfNot($arr['height'], '175');
        setIfNot($arr['width'], '175');
        $arr['canvasId'] = $canvasId;
        $arr['value'] = $value;
        $tpl = new ET('
            <canvas id="[#canvasId#]" height="[#height#]" width="[#width#]"></canvas>
        ');
        
        $tpl2 = new ET('
        addLoadEvent( function() {
            options_[#canvasId#] = {
                label: "[#label#]",
                unitsLabel: " [#unitsLabel#]",
                min: [#min#],
                max: [#max#],
                majorTicks: [#majorTicks#],
                minorTicks: [#minorTicks#], 
                greenFrom: [#greenFrom#],
                greenTo: [#greenTo#],
                greenColor: "[#greenColor#]", 
                yellowFrom: [#yellowFrom#],
                yellowTo: [#yellowTo#],
                yellowColor: "[#yellowColor#]",
                redFrom: [#redFrom#],
                redTo: [#redTo#],
                redColor:  "[#redColor#]",
                colorOfText: "[#colorOfText#]",
                colorOfWarningText: "[#colorOfWarningText#]",
                colorOfFill: [#colorOfFill#],
                colorOfPointerFill: "[#colorOfPointerFill#]",
                colorOfPointerStroke: "[#colorOfPointerStroke#]",
                colorOfCenterCircleFill: "[#colorOfCenterCircleFill#]",
                colorOfCenterCircleStroke: "[#colorOfCenterCircleStroke#]",
            };
            [#canvasId#] = new Gauge( document.getElementById("[#canvasId#]"), options_[#canvasId#] );
            [#canvasId#].setValue( [#value#] );
        })
        ');
        
        self::enable($tpl);
        $tpl2->placeObject($arr);
        $tpl->placeObject($arr);
        $tpl->append($tpl2, 'SCRIPTS');
        
        return $tpl;
    }
    
    
    /**
     * Данни и библиотеки, които се зареждат само един път.
     */
    static function enable($tpl)
    {
        $tpl->appendOnce("
                function addLoadEvent(func) {
                    var oldonload = window.onload;
                    if (typeof window.onload != 'function') {
                        window.onload = func;
                    } else {
                        window.onload = function() {
                            if (oldonload) {
                                oldonload();
                            }
                            func();
                        }
                    }
                }
            ", 'SCRIPTS');
        
        $conf = core_Packs::getConfig('jsgauge');
        $tpl->push($conf->GAUGE_PATH . '/' . "gauge.js", "JS");
        $tpl->appendOnce("\n" . '<!--[if IE]><script type="text/javascript" src="' .
            GAUGE_PATH . '/' . 'excanvas.js"></script><![endif]-->', "HEAD");
        
        return ;
    }
}