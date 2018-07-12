<?php


/**
 * Клас 'vislog_Wrapper'
 *
 * Поддържа табове-те на пакета 'vislog'
 *
 *
 * @category  vendors
 * @package   vislog
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class vislog_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('vislog_History', 'История', 'admin, ceo, cms');
        $this->TAB('vislog_Referer', 'Рефериране', 'admin, ceo, cms');
        $this->TAB('vislog_Adwords', 'Adwords', 'admin, ceo, cms');
        $this->TAB('vislog_HistoryResources', 'Ресурси', 'admin, ceo, cms');
        $this->TAB('vislog_IpNames', 'IP-та', 'admin, ceo, cms');
        
        $this->title = 'История';
    }
}
