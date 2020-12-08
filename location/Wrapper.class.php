<?php


/**
 * Клас 'location_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета
 *
 *
 * @category  bgerp
 * @package   location
 *
* @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class location_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('location_Places', 'Бази', 'ceo,admin');
        $this->TAB('tracking_Vehicles', 'Автомобили', 'ceo,admin');
        $this->TAB('tracking_Log', 'Хронология', 'ceo,admin');
        
        
    }
}
