<?php


/**
 * Клас 'label_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'label'
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class label_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на опаковката от табове
     */
    public function description()
    {
        $this->TAB('label_Prints', 'Отпечатвания', 'label, admin, ceo, seeLabelAll');
        $this->TAB('label_Templates', 'Шаблони', 'label, admin, ceo');
        $this->TAB('label_Media', 'Медии', 'label, labelMaster, admin, ceo');
        $this->TAB('label_Counters', 'Броячи', 'label, admin, ceo');
    }
}
