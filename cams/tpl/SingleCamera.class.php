<?php


/**
 * Клас 'cams_tpl_SingleCamera' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    cams
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class cams_tpl_SingleCamera extends core_ET
{
    /**
     *  @todo Чака за документация...
     */
    public function init($params = array())
    {
        
        $html = "[#SingleToolbar#]
                 [#title#], [#ip#]
                 <br/><br/>
                 <img height='303' width='370' src='http://10.0.0.221/image.cgi' alt='http://10.0.0.221/image.cgi' style='cursor: -moz-zoom-in;'>";
        
        return parent::core_ET($html);
    }
}