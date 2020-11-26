<?php


/**
 * Поддържа системното меню и табове-те на пакета 'support'
 *
 * @category  bgerp
 * @package   support
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class support_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на опаковката от табове
     */
    public function description()
    {
        $this->TAB('support_Tasks', 'Сигнали', 'ceo, admin, support');
        $this->TAB('support_Systems', 'Системи', 'admin, support');
        $this->TAB('support_IssueTypes', 'Типове', 'admin, support');
    }
}
