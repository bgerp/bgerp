<?php



/**
 * Клас 'replace_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'replace'
 *
 *
 * @category  vendors
 * @package   replace
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class replace_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('replace_Dictionary', 'Речник', 'admin');
        $this->TAB('replace_Groups', 'Групи', 'admin');
    }
}
