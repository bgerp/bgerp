<?php



/**
 * Път до външния пакет
 */
defIfNot('FANCYBOX_PATH', 'fancybox/1.3.4');


/**
 * Клас 'fancybox_Fancybox'
 *
 * Съдържа необходимите функции за използването на
 * Fancybox
 *
 *
 * @category  vendors
 * @package   fancybox
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 * @link      http://fancybox.net/
 */
class fancybox_Fancybox {
    
    
    /**
     * @todo Чака за документация...
     */
    function getImage($fh, $thumbSize, $maxSize, $baseName = NULL, $imgAttr = array(), $aAttr = array())
    {
        $Thumb = cls::get('thumbnail_Thumbnail');
        $jQuery = cls::get('jquery_Jquery');
        
        if(Mode::is('text', 'xhtml') || Mode::is('printing')) {
            $info['baseName'] = str::utf2ascii($baseName);
            $info['baseName'] = preg_replace('/[^a-zA-Z0-9\-_\.]+/', '_', $info['baseName']);
        } else {
            $info['baseName'] = $baseName;
        }
        
        // Създаваме изображението
        $imgAttr['src']    = $Thumb->getLink($fh, $thumbSize, $info);
        $imgAttr['height'] = isset($info['height']) ? $info['height'] : $info[1];
        $imgAttr['width']  = isset($info['width']) ? $info['width'] : $info[0];
        $imgAttr['title']  = tr('Кликни за увеличение');
        setIfNot($imgAttr['alt'], $baseName);
        $imgTpl = ht::createElement('img', $imgAttr);

        if($imgAttr['height'] >= $maxSize[0] && $imgAttr['width'] >= $maxSize[1]) {

            return $imgTpl;
        }

        // Създаваме хипервръзката
        $sizes['baseName'] = $baseName;
        $aAttr['href'] = $Thumb->getLink($fh, $maxSize, $sizes);
        setIfNot($aAttr['rel'], $maxSize[0] . "_" . $maxSize[1]);
        $aAttr['class'] .= 'fancybox';
        $tpl = ht::createElement('a', $aAttr, $imgTpl);

    /*    $tpl = new ET('
            <a href="[#bigUrl#]" class="fancybox" rel="[#rel#]">
                <img src="[#smallUrl#]" 
                    alt="' . $baseName '"
                    title="' . tr('Кликни за увеличение' .'" 
                    height="[#smallHeight#]" 
                    width="[#smallWidth#]" />
            </a>
        '); */
        
        $tpl->placeObject($rec);
        
        $jQuery->enable($tpl);
        
        $tpl->push(FANCYBOX_PATH . '/jquery.fancybox-1.3.4.css', 'CSS');
        $tpl->push(FANCYBOX_PATH . '/jquery.fancybox-1.3.4.js', 'JS');
        
        $jQuery->run($tpl, "$('a.fancybox').fancybox();", TRUE);
        
        return $tpl;
    }
}