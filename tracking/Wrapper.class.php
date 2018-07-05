<?php



/**
 * Клас 'tracking_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'tracking'
 *
 *
 * @category  bgerp
 * @package   tracking
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class tracking_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('tracking_Vehicles', 'Автомобили', 'ceo,admin,tracking');
        $this->TAB('tracking_Log', 'Лог', 'ceo,admin,tracking');

        $this->title = 'Мониторинг';
    }
}
