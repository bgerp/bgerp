<?php



/**
 * Клас 'acc_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Acc'
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class acc_WrapperSettings extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        
        $this->TAB('acc_Periods', 'Периоди', 'admin,acc');
        $this->TAB('acc_Lists', 'Номенклатури', 'admin,acc');
        $this->TAB('acc_Items', 'Пера', 'admin,acc');
        $this->TAB('acc_Accounts', 'Сметки', 'admin,acc,broker,designer');
        $this->TAB('acc_Limits', 'Лимити', 'admin,acc');
            
        $this->title = 'Настройки « Счетоводство';
        Mode::set('menuPage','Счетоводство:Настройки');
    }
}