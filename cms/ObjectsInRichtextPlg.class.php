<?php



/**
 * Клас 'cms_ObjectsInRichtextPlg' - Плъгин за публикуване на cms обекти
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_ObjectsInRichtextPlg extends core_Plugin
{
    
    /**
     * Обработваме елементите линковете, които сочат към докъментната система
     */
    public function on_AfterCatchRichElements($mvc, &$html)
    {
        $this->mvc = $mvc;
        
        //Ако намери съвпадение на регулярния израз изпълнява функцията
        // Обработваме елементите [obj=????]
        $html = preg_replace_callback("/\[obj(=([^\]]*)|)\]/si", array($this, 'catchObjects'), $html);
    }
    
    
    /**
     * Заменяме линковете от система с абсолютни URL' та
     *
     * @param array $match - Масив с откритите резултати
     *
     * @return string $res - Ресурса, който ще се замества
     */
    public function catchObjects($match)
    {
        $vid = $match[2];

        $res = cms_Objects::getObjectByTag($vid);
        
        $place = $this->mvc->getPlace();

        $this->mvc->_htmlBoard[$place] = $res;

        return "[#{$place}#]";
    }
}
