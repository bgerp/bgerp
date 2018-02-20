<?php


/**
 * Клас 'label_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'label'
 *
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class label_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на опаковката от табове
     */
    function description()
    {
        $this->TAB('label_Prints', 'Отпечатвания', 'label, admin, ceo');
        $this->TAB('label_Labels', 'Етикети', 'label, admin, ceo');
        $this->TAB('label_Templates', 'Шаблони', 'label, admin, ceo');
        $this->TAB('label_Media', 'Медия', 'labelMaster, admin, ceo');
        $this->TAB('label_Counters', 'Брояч', 'label, admin, ceo');
        $this->TAB('label_Serials', 'Серийни номера', 'label, admin, ceo');
    }
}
