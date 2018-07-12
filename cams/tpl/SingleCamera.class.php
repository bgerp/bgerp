<?php


/**
 * Клас 'cams_tpl_SingleCamera' -
 *
 *
 * @category  bgerp
 * @package   cams
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class cams_tpl_SingleCamera extends core_ET
{
    /**
     * Инициализиране на обекта
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
