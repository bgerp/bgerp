<?php


/**
 * Клас 'geshi_Import' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    geshi
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
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
        $language = $language?$language:'text';
        
        require_once( getFullPath('/geshi/geshi.php') );
        
        $GeSHi = new GeSHi($source, $language);
        
        $GeSHi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 2);
        
        $GeSHi->set_line_style('color:#111;', 'color:#111; background-color:#fffff0;');
        
        $tpl = new ET("<div style='font-size:0.9em;'>" . $GeSHi->parse_code() . "</div>");
        
        return $tpl;
    }
}
