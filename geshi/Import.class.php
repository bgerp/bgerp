<?php



/**
 * Клас 'geshi_Import' -
 *
 *
 * @category  all
 * @package   geshi
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class geshi_Import
{
    
    
    /**
     * Return the HTML code required to run GeShi
     *
     * @return string
     */
    function renderHtml($source, $language, $attr = array())
    {
        $language = $language ? $language : 'text';
        
        require_once(getFullPath('/geshi/geshi.php'));
        
        $GeSHi = new GeSHi($source, $language);
        
        $GeSHi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 2);
        
        $GeSHi->set_line_style('color:#111;', 'color:#111; background-color:#fffff0;');
        
        $tpl = new ET("<div style='font-size:0.9em;'>" . $GeSHi->parse_code() . "</div>");
        
        return $tpl;
    }
}
