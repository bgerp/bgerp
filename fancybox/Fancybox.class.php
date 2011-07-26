<?php

/**
 *  class fancybox_Fancybox
 *  Съдържа необходимите фунции за използването на
 *  Fancybox
 *
 *  @link http://fancybox.net/
 *
 */

defIfNot('FANCYBOX_PATH', 'fancybox/1.3.4');


/**
 * Клас 'fancybox_Fancybox'
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    fancybox
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class fancybox_Fancybox {
    
    
    /**
     *  @todo Чака за документация...
     */
    function getImage($fh, $thumbSize, $maxSize, $baseName = NULL)
    {
        $Thumb = cls::get('thumbnail_Thumbnail');
        $jQuery = cls::get('jquery_Jquery');
        
        $info['baseName'] = $baseName;
        $rec->smallUrl = $Thumb->getLink($fh, $thumbSize, $info);
        
        $rec->smallHeight = isset($info['height']) ? $info['height'] : $info[1];
        
        $rec->smallWidth = isset($info['width']) ? $info['width'] : $info[0];
        
        $sizes['baseName'] = $baseName;
        $rec->bigUrl = $Thumb->getLink($fh, $maxSize, $sizes);
        $rec->rel = $maxSize[0] . "_" . $maxSize[1];
        $tpl = new ET('
            <a href="[#bigUrl#]" class="fancybox" rel="[#rel#]">
                <img src="[#smallUrl#]" alt="Pict"
                    title="Click to enlarge" height="[#smallHeight#]" 
                    width="[#smallWidth#]" />
            </a>
        ');
        
        $tpl->placeObject($rec);
        
        $jQuery->enable($tpl);
        
        $tpl->push( FANCYBOX_PATH . '/jquery.fancybox-1.3.4.css', 'CSS');
        $tpl->push( FANCYBOX_PATH . '/jquery.fancybox-1.3.4.js', 'JS');
        
        $jQuery->run($tpl, "$('a.fancybox').fancybox();", TRUE);
        
        return $tpl;
    }
}