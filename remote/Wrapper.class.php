<?php


/**
 * Клас 'remote_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'remote'
 *
 *
 * @category  bgerp
 * @package   remote
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class remote_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('remote_Authorizations', 'Връзки', 'powerUser');
        $this->TAB('remote_Tokens', 'Кодове', 'debug,admin');
        
        // $this->title = 'Новини « Сайт';
        // Mode::set('menuPage','Сайт:Новини');
    }
}
