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
class issue_Wrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на опаковката от табове
     */
    function description()
    {        
        $this->TAB('issue_Document', 'Документи');
        $this->TAB('issue_Systems', 'Системи', 'admin, issue');
        $this->TAB('issue_Components', 'Компоненти', 'admin, issue');
        $this->TAB('issue_Types', 'Типове', 'admin, issue');
    }
}
