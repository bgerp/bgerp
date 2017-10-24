<?php


/**
 * 
 */
defIfNot('GMAP3_VERSION', '6.0');


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
class location_Paths
{
    
    
    /**
     * Рендира показване на координати с google map
     * 
     * @param array $vArr - двумерен масив
     * array(array('coords' => array(array('coordStr')), info => 'str'))
     * @param array $attr
     * 
     * @return ET
     */
    static function renderView($vArr, $attr = array())
    {
        static $n;

        if(!$n) $n = 0;

        $n++;

        $id = 'map' . $n;
     
        setIfNot($width, $attr['width'], 400);
        setIfNot($height, $attr['height'], 300);

        $conf = core_Packs::getConfig('google');
        $apiKey = $conf->GOOGLE_API_KEY;
        
        if(isset($apiKey) && $apiKey != "") {
        	$keyString = "key={$apiKey}&";
        }
        
        $res = new ET("<div class='location-map'><div style='width:{$width}px;height:{$height}px;' id=\"{$id}\"></div></div>");
        
        $res->appendOnce("\n<script type=\"text/javascript\" src=\"https://maps.google.com/maps/api/js?". $keyString . "language=" . core_Lg::getCurrent() . "\"></script>", "HEAD", TRUE);
        $res->push("location/js/generateLocation.js", 'JS');
        $res->push('location/' . GMAP3_VERSION . '/gmap3.js', 'JS');
		
        $vArr = json_encode($vArr);
        jquery_Jquery::run($res, "generatePath({$vArr},{$id});");
        
        return $res;
    }
}