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
        
        // Ако е зададено да е абсолютен линк
        $isAbsolute = $imgAttr['isAbsolute'];
        
        // Премахваме от масива
        unset($imgAttr['isAbsolute']);
        
        // Създаваме изображението
        if(is_int($thumbSize)) {
            $thumbWidth = $thumbHeight = $thumbSize;
        } elseif(is_array($thumbSize)) {
            setIfNot($thumbWidth, $thumbSize['width'], $thumbSize[0]);
            setIfNot($thumbHeight, $thumbSize['height'], $thumbSize[1]);
        } else {
            expect(FALSE, $thumbSize);
        }

        $thumb = new img_Thumb($fh, $thumbWidth, $thumbHeight, 'fileman', $baseName);
        
        // Ако е абсолютен
        if ($isAbsolute) {
            
            // Вдигаме флага
            $thumb->isAbsolute = TRUE;
        }
        
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

        $bigImg = new img_Thumb($fh, $bigWidth, $bigHeight, 'fileman', $baseName);
        
        // Ако е абсолютен
        if ($isAbsolute) {
            
            // Вдигаме флага
            $bigImg->isAbsolute = TRUE;
            
            // Вземаме деферед URL
            $aAttr['href'] = $bigImg->getDeferredUrl();
        } else {
            
            // Вземаме URL към sbf директорията
            $aAttr['href'] = $bigImg->getUrl();
        }
        
        setIfNot($aAttr['rel'], $maxSize[0] . "_" . $maxSize[1]);
        $aAttr['class'] .= 'fancybox';
        $tpl = ht::createElement('a', $aAttr, $imgTpl);

        $conf = core_Packs::getConfig('fancybox');
        
        $tpl->push($conf->FANCYBOX_PATH . '/jquery.fancybox.css', 'CSS');
        $tpl->push($conf->FANCYBOX_PATH . '/jquery.fancybox.js', 'JS');
        
        jquery_Jquery::run($tpl, "$('a.fancybox').fancybox();", TRUE);
        
        return $tpl;
    }
}