<?php



/**
 * Клас 'cams_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cams_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
     
        
        $this->TAB('cams_Cameras', 'Камери', 'cams, admin');
        $this->TAB('cams_Records', 'Записи', 'cams, admin');
        $this->TAB('cams_Positions', 'Позиции', 'cams, admin');
        
        $this->title = 'Камери';
    
    }
}