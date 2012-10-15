<?php



/**
 * Клас 'bgerp_Index' -
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class bgerp_Index extends core_Manager
{
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Default()
    {   
        if(!cms_Content::fetch('1=1')) {
            if(Mode::is('screenMode', 'narrow')) {
                
                return new Redirect(array('bgerp_Menu', 'Show'));
            } else {
                
                return new Redirect(array('bgerp_Portal', 'Show'));
            }
        } else {
            return new Redirect(array('cms_Content', 'Show'));
        }
    }
}