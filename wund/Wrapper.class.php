<?php


/**
 * Клас 'wund_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Wund'
 *
 *
 * @category  vendors
 * @package   wund
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class wund_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('wund_Forecast', 'Прогнози');
        
        $this->title = 'Прогнози за времето';
    }
}
