<?php



/**
 * Клас 'sens_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class sens2_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('sens2_Indicators', 'Индикатори', 'sens, ceo, admin');
        $this->TAB('sens2_DataLogs', 'Записи');
        $this->TAB('sens2_Controllers', 'Контролери');
        $this->TAB('sens2_Scripts', 'Скриптове');

        $this->title = 'В/И Контролери';
        //Mode::set('pageMenu', 'Мониторинг');
        //Mode::set('pageSubMenu', 'MOM');
    }
}
