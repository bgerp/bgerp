<?php


/**
 * Клас 'rack_Wrapper'
 *
 * Обвивква на палетния склад
 *
 *
 * @category  bgerp
 * @package   pallet
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('rack_Movements', 'Движения', 'ceo,rack');
        $this->TAB('rack_Products', 'Продукти', 'ceo,rack');
        $this->TAB('rack_Pallets', 'Палети', 'ceo,rack');
        $this->TAB('rack_Racks', 'Стелажи', 'ceo,rack');
        $this->TAB('rack_Zones', 'Зони', 'ceo,rack');
        
        $this->title = 'Палетен склад';
    }
}
