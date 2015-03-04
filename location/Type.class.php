<?php

cls::load('type_Varchar');

/**
 * Пътя до външния код за изчертаване на карти
 */
defIfNot('GMAP3_VERSION', '6.0');


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
     * Празната стойност има смисъл на NULL
     */
    var $nullIfEmpty = TRUE;


    /**
     * Параметър определящ максималната широчина на полето
     */
    var $maxFieldSize = 30;
    
    
    
    /**
     * @todo Чака за документация...
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $attr['class'] .= ' lnglat';
        
        if (!$attr['id']) {
            if (!$attr['name']) {
                $attr['name'] = 'lnglat';
            }
            ht::setUniqId($attr);
        }
        
        $stopGeolocation = FALSE;
            
        if (!$value) {
            if (!($value = $this->params['default'])) {
                $conf = core_Packs::getConfig('location');
                $value = $conf->LOCATION_DEFAULT_REGION;
            }
        } else {
            $stopGeolocation = TRUE;
        }
        
        $tpl = parent::createInput($name, $value, $attr);
        
        $tpl->appendOnce("\n<script type=\"text/javascript\" src=\"https://maps.google.com/maps/api/js?sensor=false&language=" . core_Lg::getCurrent() . "\"></script>", "HEAD", TRUE);
        
        $Lg = cls::get('core_Lg');
        
        if($Lg->getCurrent() == 'bg') {
            $tpl->push('location/js/jquery.locationpicker-bg.js', 'JS');
        } else {
            $tpl->push('location/js/jquery.locationpicker.js', 'JS');
        }
        
        jquery_Jquery::run($tpl, "$(\".location-input input\").locationPicker();", TRUE);
        
        $tpl->prepend("<div class='location-input'>");
		$tpl->append("</div>");
		
		if (!$stopGeolocation && $this->params['geolocation']) {
		    $tpl->append("\n runOnLoad(function(){getEO().setPosition('{$attr['id']}');});", 'JQRUN');
		}
		
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

        $res = new ET("<div class='location-map'><div style='width:{$width}px;height:{$height}px;' id=\"{$id}\"></div></div>");
        
        $res->appendOnce("\n<script type=\"text/javascript\" src=\"https://maps.google.com/maps/api/js?sensor=false&language=" . core_Lg::getCurrent() . "\"></script>", "HEAD", TRUE);
        
        $res->push("location/" . GMAP3_VERSION . "/gmap3.js", 'JS');

        jquery_Jquery::run($res, "\$('#{$id}').gmap3(
                          {
						    marker:{
						      latLng: [{$value}]
						    },
						    map:{
						      options:{
						        zoom: 14,
						         center: [{$value}]
						      }
						    }
						  });");
		
        return $res;
    }
}