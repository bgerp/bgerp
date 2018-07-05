<?php


/**
 * Клас 'status_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'status'
 *
 * @category  vendors
 * @package   status
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class status_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на опаковката от табове
     */
    public function description()
    {
        $this->TAB('status_Messages', 'Съобщения', 'admin');
        $this->TAB('status_Retrieving', 'Изтегляния', 'admin');
    }
}
