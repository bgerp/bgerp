<?php



/**
 * Клас 'gps_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'gps'
 *
 *
 * @category  bgerp
 * @package   gps
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class gps_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {

        $this->TAB('gps_Log', 'Точки', 'ceo,admin,gps');
        $this->TAB(array('gps_ListenerControl', 'ListenerControl'), 'Контрол', 'admin,gps');
        $this->TAB('gps_Trackers', 'Автомобили', 'ceo,admin,gps');
        
        $this->title = 'Мониторинг';
    }
}