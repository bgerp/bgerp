<?php


/**
 * Клас, който служи за създаване на Gauge с canvas
 *
 *
 * @category  vendors
 * @package   jsgauge
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link      https://canvas-gauges.com/
 */
class canvasgauge_Gauge
{
    /**
     *
     *
     * @param NULL|int    $value
     * @param NULL|string $canvasId
     * @param array       $valArr
     *
     * @return ET
     *
     * Допълнителните параметри за разчертаване се добавят в $valArr
     * $see https://canvas-gauges.com/documentation/user-guide/configuration
     */
    public static function drawLinear($value = null, $canvasId = null, $valArr = array())
    {
        return self::renderGauge($value, $canvasId, $valArr, 'linear');
    }
    
    
    /**
     *
     *
     * @param NULL|int    $value
     * @param NULL|string $canvasId
     * @param array       $valArr
     *
     * @return ET
     *
     * Допълнителните параметри за разчертаване се добавят в $valArr
     *
     * @see https://canvas-gauges.com/documentation/user-guide/configuration
     */
    public static function drawRadial($value = null, $canvasId = null, $valArr = array())
    {
        return self::renderGauge($value, $canvasId, $valArr, 'radial');
    }
    
    
    /**
     * Помощна функция за разчертване
     *
     * @param NULL|int    $value
     * @param NULL|string $canvasId
     * @param array       $valArr
     *
     * @return ET
     *
     * Допълнителните параметри за разчертаване се добавят в $valArr
     *
     * @link https://canvas-gauges.com/documentation/user-guide/configuration
     */
    protected static function renderGauge($value = null, $canvasId = null, $valArr = array(), $type = 'radial')
    {
        setIfNot($value, $valArr['value'], '0');
        setIfNot($canvasId, $valArr['canvasId'], str::getRand());
        
        $valArr['renderTo'] = $canvasId;
        $valArr['value'] = $value;
        
        setIfNot($valArr['height'], '200');
        setIfNot($valArr['width'], '200');
        setIfNot($valArr['units'], '°C');
        setIfNot($valArr['valueDec'], 0);
        setIfNot($valArr['valueInt'], 1);
        setIfNot($valArr['animationDuration'], 4000);
        setIfNot($valArr['animationRule'], 'elastic');
        setIfNot($valArr['animation'], true);
        setIfNot($valArr['animatedValue'], true);
        setIfNot($valArr['animateOnInit'], true);
        
        $valArr['gaugeType'] = 'RadialGauge';
        if ($type == 'linear') {
            $valArr['gaugeType'] = 'LinearGauge';
        }
        
        // Това е защита, когата са зададени стойности но не е начертано добре
        if (isset($valArr['minValue']) || isset($valArr['maxValue'])) {
            if (!$valArr['majorTicks']) {
                setIfNot($valArr['exactTicks'], true);
                expect($valArr['exactTicks'], 'Трябва да се зададе стойност на majorTicks');
            }
        }
        
        $str = json_encode($valArr);
        
        $jsTpl = new ET("var gauge_[#renderTo#] = new [#gaugeType#]( {$str} );
                gauge_[#renderTo#].draw();");
        $jsTpl->placeArray($valArr);
        
        $tpl = new ET('<canvas id="[#renderTo#]" height="[#height#]" width="[#width#]"></canvas>');
        $tpl->appendOnce($jsTpl, 'SCRIPTS');
        $tpl->push('canvasgauge/' . canvasgauge_Setup::get('VERSION') . '/' . 'gauge.min.js', 'JS');
        $tpl->placeArray($valArr);
        
        return $tpl;
    }
}
