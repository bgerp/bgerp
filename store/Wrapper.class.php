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
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('store_Stores', 'Складове', 'ceo,store');
        $this->TAB('store_Transfers', 'Документи', 'store, ceo');
		$this->TAB('store_Movements', 'Движения', 'ceo,store');
        $this->TAB('store_Pallets', 'Палети', 'ceo,store');
        $this->TAB('store_Racks', 'Стелажи', 'ceo,store');
        $this->TAB('store_Zones', 'Зони', 'ceo,store');
        $this->TAB('store_Products', 'Продукти', 'ceo,store');
        
        $this->title = 'Склад';
    }
}