<?php


/**
 * Клас 'bgerp_Index' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    bgerp
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class bgerp_Index extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    function act_Default()
    {
        if(Mode::is('screenMode', 'narrow')) {
            
            return new Redirect(array('bgerp_Menu', 'Show') );
        } else {
            
            return new Redirect(array('bgerp_Portal', 'Show') );
        }
    }
}