<?php


/**
 * Клас 'cms_LibraryRichTextPlg' - замества [lib=#...] в type_Richtext
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@experta.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_LibraryRichTextPlg extends core_Plugin
{
    /**
     * Регулярен израз за елементите
     */
    const ELM_PATTERN = "/\[elm=(?'title'[^\]]+)\]/si";
    
    
    
    /**
     * Обработваме елементите линковете, които сочат към докоментната система
     */
    public function on_AfterCatchRichElements($mvc, &$html)
    { 
        $this->mvc = $mvc;
 
        //Ако намери съвпадение на регулярния израз изпълнява функцията
        // Обработваме елементите [elm=????]
        $html = preg_replace_callback(self::ELM_PATTERN, array($this, 'catchTags'), $html);
    }
    
    
    /**
     * Заменяме линковете от система с абсолютни URL' та
     *
     * @param array $match - Масив с откритите резултати
     *
     * @return string $res - Ресурса, който ще се замества
     */
    public function catchTags($match)
    {
        $title = $match['title'];
        
        $dPos = strrpos($title, '-');

        $tag = substr($title, $dPos+1);

        $name = substr($title, 0, $dPos);

        $elmRec = cms_Library::fetch(array("#name = '[#1#]' AND #tag = '[#2#]'", $name, $tag));
        
        if (!$elmRec) {
            
            return $match[0];
        }

        //Ако принтираме или пращаме документа
        if ((Mode::is('text', 'xhtml')) || (Mode::is('text', 'plain'))) {
            
            // Добавяме атрибута за да използваме абсолютни линкове
            $absolute = true;
        }

        $tpl = cms_Library::render($elmRec, 2400, $absolute);
        
        $place = $this->mvc->getPlace();
        
        $this->mvc->_htmlBoard[$place] = $tpl;
        
        return "[#{$place}#]" . $match['end'];
    }
    
    
    /**
     * Връща всички картинки в подадения ричтекст
     *
     * @param string $rt
     *
     * @return array
     */
    public static function getImages($rt)
    {
        preg_match_all(static::ELM_PATTERN, $rt, $matches);
        
        $imagesArr = array();
        
        if (countR($matches['title'])) {
            foreach ($matches['title'] as $name) {
                $imagesArr[$name] = $name;
            }
        }
        
        return $imagesArr;
    }
    
    

}
