<?php

cls::load('type_Varchar');


/**
 * Клас 'location_Type' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    location
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class location_Type extends type_Varchar {
    
    
    /**
     *  @todo Чака за документация...
     */
    function renderInput_($name, $value="", $attr = array())
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
}