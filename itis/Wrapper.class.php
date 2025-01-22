<?php


/**
 * Клас 'itis_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  bgerp
 * @package   itis
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class itis_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('itis_Devices', 'Устройства', 'itis, admin');
        $this->TAB('itis_Changelog', 'Промени', 'itis, admin');
        $this->TAB('itis_Deployments', 'Агенти', 'itis, admin');
        $this->TAB('itis_Groups', 'Групи', 'itis, admin');
        $this->TAB('itis_Ports', 'Портове', 'itis, admin');
        $this->TAB('itis_Process', 'Процеси', 'itis, admin');
        $this->TAB('itis_Values', 'Стойности', 'itis, admin');
    }
}
