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
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class acc_WrapperSettings extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('acc_Periods', 'Периоди', 'ceo,acc');
        $this->TAB('acc_Lists', 'Номенклатури', 'ceo,acc');
        $this->TAB('acc_Items', 'Пера', 'ceo,acc');
        $this->TAB('acc_Accounts', 'Сметки', 'ceo,acc');
        $this->TAB('acc_Features', 'Свойства', 'ceo,acc');
        $this->TAB('acc_Limits', 'Лимити', 'ceo,acc');
        $this->TAB('acc_VatGroups', 'ДДС групи', 'ceo,acc');
        $this->TAB('acc_Operations', 'Операции', 'debug');
        
        $this->title = 'Настройки « Счетоводство';
        Mode::set('menuPage', 'Счетоводство:Настройки');
    }
}
