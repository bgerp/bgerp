<?php


/**
 * Поддържа системното меню и табове-те на пакета 'issue'
 *
 * @category  bgerp
 * @package   issue
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Wrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на опаковката от табове
     */
    function description()
    {        
        $this->TAB('support_Issues', 'Сигнали');
        $this->TAB('support_Systems', 'Поддържани системи', 'admin, issue');
        $this->TAB('support_Components', 'Поддържани компоненти', 'admin, issue');
        $this->TAB('support_IssueTypes', 'Типове сигнали', 'admin, issue');
    }
}
