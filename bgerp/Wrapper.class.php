<?php



/**
 * Клас 'bgerp_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'bgerp'
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class bgerp_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('bgerp_Menu', 'Меню', 'admin');
        
        // $this->TAB('bgerp_Portal', 'Портал', 'admin');
        $this->TAB('bgerp_Notifications', 'Известия', 'admin');
        $this->TAB('bgerp_Recently', 'Последни', 'admin');
        $this->TAB('bgerp_Bookmark', 'Отметки', 'user'); 
        $this->TAB('bgerp_LastTouch', 'Дебъг->Докосвания', 'debug');

    }
}