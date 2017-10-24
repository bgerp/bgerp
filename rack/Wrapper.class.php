<?php



/**
 * Клас 'rack_Wrapper'
 *
 * Обвивква на палетния склад
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rack_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('rack_Movements', 'Движения');
        $this->TAB('rack_Products', 'Продукти');
        $this->TAB('rack_Pallets', 'Палети');
        $this->TAB('rack_Racks', 'Стелажи');
        
        $this->title = 'Палетен склад';
    }
}