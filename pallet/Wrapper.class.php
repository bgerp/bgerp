<?php



/**
 * Клас 'store_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'store'
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pallet_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('pallet_Movements', 'Движения');
        $this->TAB('pallet_Pallets', 'Палети');
        $this->TAB('pallet_PalletTypes', 'Видове палети');
        $this->TAB('pallet_Racks', 'Стелажи');
        $this->TAB('pallet_Zones', 'Зони');
        
        $this->title = 'Палетен склад';
    }
}