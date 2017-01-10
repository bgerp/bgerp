<?php


/**
 * Поддържа системното меню и табове-те на пакета 'callcenter'
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_Wrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на опаковката от табове
     */
    function description()
    {        
        $this->TAB('callcenter_Talks', 'Разговори');
        $this->TAB('callcenter_Fax', 'Факсове');
        $this->TAB('callcenter_SMS', 'SMS-и');
        $this->TAB('callcenter_Numbers', 'Номера');
        $this->TAB('callcenter_Hosts', 'Хостове', 'admin');
        
        $this->title = 'КЦ';
    }
}
