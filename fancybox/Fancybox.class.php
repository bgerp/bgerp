<?php


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
class fancybox_Fancybox extends core_Manager {
    
    
    /**
     * @todo Чака за документация...
     */
    static function getImage_($fh, $thumbSize, $maxSize, $baseName = NULL, $imgAttr = array(), $aAttr = array())
    {
        // Ако е текстов режим, да не сработва
        if (Mode::is('text', 'plain')) return '';
        
        if (!$fh) return ;
        
        // Ако е зададено да е абсолютен линк
        $isAbsolute = $imgAttr['isAbsolute'];
        
        // Премахваме от масива
        unset($imgAttr['isAbsolute']);
        
        if (!isset($isAbsolute)) {
            $isAbsolute = Mode::isReadOnly();
        }
        
        // Създаваме изображението
        if(is_int($thumbSize)) {
            $thumbWidth = $thumbHeight = $thumbSize;
        } elseif(is_array($thumbSize)) {
            setIfNot($thumbWidth, $thumbSize['width'], $thumbSize[0]);
            setIfNot($thumbHeight, $thumbSize['height'], $thumbSize[1]);
        } else {
            expect(FALSE, $thumbSize);
        }

        $thumb = new thumb_Img(array($fh, $thumbWidth, $thumbHeight, 'fileman', 'isAbsolute' => $isAbsolute, 'mode' => 'small-no-change', 'verbalName' => $baseName));
        
        if($thumbSize[0] >= $maxSize[0] && $thumbSize[1] >= $maxSize[1]) {
  
            $imgTpl = $thumb->createImg($imgAttr);
            
            return $imgTpl;
        }

        $attr = array('title' => tr('Кликни за увеличение')) + $imgAttr;
     
        $imgTpl = $thumb->createImg($attr);

        // Създаваме хипервръзката
        if(is_int($maxSize)) {
            $bigWidth = $bigHeight = $maxSize;
        } elseif(is_array($thumbSize)) {
            setIfNot($bigWidth, $maxSize['width'], $maxSize[0]);
            setIfNot($bigHeight, $maxSize['height'], $maxSize[1]);
        } else {
            expect(FALSE, $maxSize);
        }

        $bigImg = new thumb_Img(array($fh, $bigWidth, $bigHeight, 'fileman', 'isAbsolute' => $isAbsolute, 'mode' => 'small-no-change', 'verbalName' => $baseName));
        
        // Ако е абсолютен
        if ($isAbsolute) {
            
            // Вземаме деферед URL
            $aAttr['href'] = $bigImg->getUrl('deferred');
        } else {
            
            // Вземаме URL към sbf директорията
            $aAttr['href'] = $bigImg->getUrl();
        }
        
        setIfNot($aAttr['rel'], $maxSize[0] . "_" . $maxSize[1]);
        $aAttr['class'] .= 'fancybox';
        $tpl = ht::createElement('a', $aAttr, $imgTpl);
        
        // Когато отпечатваме да не сработва плъгина
        if (!Mode::is('printing')) {
            $conf = core_Packs::getConfig('fancybox');
            
            $tpl->push('fancybox/' . $conf->FANCYBOX_VERSION . '/jquery.fancybox.css', 'CSS');
            $tpl->push('fancybox/' . $conf->FANCYBOX_VERSION . '/jquery.fancybox.js', 'JS');
            
            jquery_Jquery::run($tpl, "$('a.fancybox').fancybox();", TRUE);
        }
        
        return $tpl;
    }
}