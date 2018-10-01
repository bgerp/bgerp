<?php


/**
 * Клас 'ssh_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'ssh'
 *
 *
 * @category  bgerp
 * @package   ssh
 *
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class ssh_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('ssh_Hosts', 'SSH', 'remote, admin');
        
        $this->title = 'Отдалечени SSH връзки';
    }
}
