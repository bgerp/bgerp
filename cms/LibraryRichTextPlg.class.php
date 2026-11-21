<?php


/**
 * Клас 'cms_LibraryRichTextPlg' - замества [elm=...] в type_Richtext
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@experta.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
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
        
        $elmRec = cms_Library::findEmbedded($title);

        if (!$elmRec) {
            
            return $match[0];
        }

        $absolute = Mode::is('text', 'xhtml') || Mode::is('text', 'plain');
        $interactive = !$absolute && !Mode::is('printing') && !Mode::is('pdf');
        $chatMedia = $interactive && !empty($this->mvc->params['libraryMediaFrames']);
        if (!cms_Library::hasAccessContext($elmRec) && !$chatMedia) {
            $tpl = cms_Library::render(cms_Library::fetch($elmRec->id), 2400, $absolute);
        } elseif (!$interactive) {
            $tpl = '';
        } else {
            // Дори при споделен кеш тук остава само адресът. Проверката е при всяко зареждане.
            $tpl = ht::createElement('iframe', array(
                'src' => toUrl(array('cms_Library', 'embedded', 'elm' => $title)),
                'title' => tr('Мултимедиен обект||Multimedia object'),
                'style' => 'display:block;width:100%;max-width:700px;height:340px;border:0',
                'allowfullscreen' => 'allowfullscreen',
            ), '');
        }
        
        $place = $this->mvc->getPlace();
        
        $this->mvc->_htmlBoard[$place] = $tpl;
        
        return "[#{$place}#]" . ($match['end'] ?? '');
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
