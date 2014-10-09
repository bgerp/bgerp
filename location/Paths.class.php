<?php

defIfNot('GMAP3_VERSION', '6.0');


cls::load('type_Blob');


/**
 * Клас 'location_Paths' - изчертаване на пътища по зададени координати
 *
 *
 * @category  vendors
 * @package   location
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class location_Paths { 
    

    /**
     * Празната стойност има смисъл на NULL
     */
    var $nullIfEmpty = TRUE;


    static function renderView($value, $attr = array())
    {
        static $n;

        if(!$n) $n = 0;

        $n++;

        $id = 'map' . $n;
     
        setIfNot($width, $attr['width'], 400);
        setIfNot($height, $attr['height'], 300);

        $res = new ET("<div class='location-map'><div style='width:{$width}px;height:{$height}px;' id=\"{$id}\"></div></div>");
        
        $res->appendOnce("\n<script type=\"text/javascript\" src=\"http://maps.google.com/maps/api/js?sensor=false&language=" . core_Lg::getCurrent() . "\"></script>", "HEAD", TRUE);
        $res->push("location/js/generateLocation.js", 'JS');
        $res->push('location/' . GMAP3_VERSION . '/gmap3.js', 'JS');
		
        $value = json_encode($value);
        jquery_Jquery::run($res, "generatePath({$value},{$id});");
        
        return $res;
    }
}