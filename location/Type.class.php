<?php

cls::load('type_Varchar');


/**
 * Клас 'location_Type' -
 *
 *
 * @category  vendors
 * @package   location
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class location_Type extends type_Varchar {
    
    
    /**
     * @todo Чака за документация...
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        
        setIfNot($attr['size'], $this->params[0], $this->params['size']);
        
        $attr['class'] .= 'lnglat';
        
        $tpl = parent::createInput($name, $value, $attr);
        
        $JQuery = cls::get('jquery_Jquery');
        
        $JQuery->enable($tpl);
        
        $tpl->appendOnce("\n<script type=\"text/javascript\" src=\"http://maps.google.com/maps/api/js?sensor=false\"></script>", "HEAD", TRUE);
        
        $Lg = cls::get('core_Lg');
        
        if($Lg->getCurrent() == 'bg') {
            $tpl->push('location/js/jquery.locationpicker-bg.js', 'JS');
        } else {
            $tpl->push('location/js/jquery.locationpicker.js', 'JS');
        }
        
        $JQuery->run($tpl, "$(\"input.lnglat\").locationPicker();");
        
        return $tpl;
    }


    function toVerbal_($value)
    {
        $coords = explode(',', $value);

        static $n;

        if(!$n) $n = 0;

        $n++;

        $id = 'map' . $n;

        setIfNot($width, $this->params['width'], 400);
        setIfNot($height, $this->params['height'], 300);

        $res = new ET("<div style='width:{$width}px;height:{$height}px;' id=\"{$id}\"></div>");
        
        $JQuery = cls::get('jquery_Jquery');
        
        $JQuery->enable($res);
        
        $res->appendOnce("\n<script type=\"text/javascript\" src=\"http://maps.google.com/maps/api/js?sensor=false\"></script>", "HEAD", TRUE);
        
        $res->push('location/js/gmap3.min.js', 'JS');

        $JQuery->run($res, "\$('#{$id}').gmap3(
                            { action:'init',
                            options:{
                            center:[{$value}],
                            zoom: 10
                            }
                            },
                            { action: 'addMarker',
                              latLng:[{$value}]
                            }
                            );");

        return $res;
    }
}