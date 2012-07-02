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
      
        
        if ($selectedStoreId) {
            $this->TAB('store_Movements', 'Движения', 'admin,store');
            $this->TAB('store_Pallets', 'Палети', 'admin,store');
            $this->TAB('store_Racks', 'Стелажи', 'admin,store');
            $this->TAB('store_Zones', 'Зони', 'admin,store');
            $this->TAB('store_Products', 'Продукти', 'admin,store');
            $this->TAB('store_Stores', 'Складове', 'admin,store');
            $this->TAB('store_PalletTypes', 'Видове палети', 'admin,store');
            $this->TAB('store_Documents', 'Документи');
        } else {
            $this->TAB('store_Stores', 'Складове', 'admin,store');
        }
        
            $this->title = 'Склад';
       
    }
}