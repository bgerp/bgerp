<?php



/**
 * Клас 'gallery_RichTextPlg' - замества [img=#...] в type_RichText
 *
 *
 * @category  vendors
 * @package   gallery
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class gallery_RichTextPlg extends core_Plugin
{
    /**
     * Обработваме елементите линковете, които сочат към докъментната система
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {
        if (Request::get('Printing')) {
            return;
        }
        
        $this->mvc = $mvc;
        
        //Ако намери съвпадение на регулярния израз изпълнява функцията
        // Обработваме елементите [images=????]  
        $html = preg_replace_callback("/\[img(=\#([^\]]*)|)\]\s*/si", array($this, 'catchImages'), $html);
    }
    
    
    /**
     * Заменяме линковете от система с абсолютни URL' та
     *
     * @param array $match - Масив с откритите резултати
     *
     * @return string $res - Ресурса, който ще се замества
     */
    function catchImages($match)
    {
        $vid = $match[2];
        
        $imgRec = gallery_Images::fetch(array("#vid = '[#1#]'", $vid));

        $groupRec =  gallery_Groups::fetch($imgRec->groupId);
        
        $tArr = array($groupRec->tWidth ? $groupRec->tWidth : 128, $groupRec->tHeight ? $groupRec->tWidth : 128);
        $mArr = array($groupRec->width ? $groupRec->width : 600, $groupRec->height ? $groupRec->width : 600);
            
        $Fancybox = cls::get('fancybox_Fancybox');

        $res = $Fancybox->getImage($imgRec->src, $tArr, $mArr);
        
        $place = $this->mvc->getPlace();

        $this->mvc->_htmlBoard[$place] = $res;

        return "[#{$place}#]";
    }
}
