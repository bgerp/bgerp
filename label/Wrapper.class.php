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
 * @copyright 2006 - 2018 Experta OOD
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
        $this->TAB('label_Prints', 'Отпечатвания', 'label, admin, ceo');
        $this->TAB('label_Templates', 'Шаблони', 'label, admin, ceo');
        $this->TAB('label_Media', 'Медии', 'labelMaster, admin, ceo');
    }
}
