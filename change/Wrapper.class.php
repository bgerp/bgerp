<?php



/**
 * 
 * 
 * @category  bgerp
 * @package   change
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class change_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('change_Log', 'Промени', 'admin');
        
        $this->title = 'Промени';
        Mode::set('menuPage','Система:Данни');
    }
}