<?php


/**
 * Клас 'rack_Wrapper'
 *
 * Обвивква на палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
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
        $this->TAB('rack_Movements', 'Движения->Списък', 'ceo,rack');
        $this->TAB('rack_OldMovements', 'Движения->История', 'ceo,rack');

        $this->TAB('rack_Products', 'Продукти', 'ceo,rack');
        $this->TAB('rack_Pallets', 'Палети', 'ceo,rack');
        $this->TAB(array('rack_Zones', 'list', 'terminal' => true), 'Зони->Терминал', 'ceo,rack');
        $this->TAB(array('rack_Zones'), 'Зони->Списък', 'ceo,rack');
        $this->TAB('rack_ZoneGroups', 'Зони->Групи', 'ceo,rack');
        $this->TAB('rack_Racks', 'Стелажи', 'ceo,rack');
        $this->TAB('rack_Logs', 'Логове', 'ceo,rack');
        $this->TAB('rack_MovementGenerator2', 'Дебъг->Генератор (ver2)', 'debug');
//        $this->TAB('rack_MovementGenerator', 'Дебъг->Генератор (ver1)', 'debug');
        $this->TAB('rack_OccupancyOfRacks', 'Дебъг->Заетост', 'debug');
        
        $this->title = 'Палетен склад';
    }
}
