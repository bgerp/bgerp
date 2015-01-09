<?php



/**
 * Клас 'remote_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'remote'
 *
 *
 * @category  bgerp
 * @package   remote
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class remote_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('remote_Hosts', 'Хостове', 'remote, admin');

        $this->title = 'Отдалечени машини';
    }
}